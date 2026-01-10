#!/usr/bin/env php
<?php
/**
 * apply-migrations.php
 *
 * CLI script to apply all .sql files from sql/migrations in alphabetical order
 * to a MySQL database using mysqli::multi_query.
 *
 * Reads DB connection settings from environment variables: DB_HOST, DB_PORT,
 * DB_NAME, DB_USER, DB_PASS. Any of these can be overridden via CLI options.
 *
 * Usage:
 *   php scripts/apply-migrations.php [--db-host=HOST] [--db-port=PORT]
 *       [--db-name=NAME] [--db-user=USER] [--db-pass=PASS] [--dir=DIR]
 *       [--help]
 */

// Ensure script is run on CLI
if (php_sapi_name() !== 'cli') {
    fwrite(STDERR, "This script must be run from the command line.\n");
    exit(1);
}

$longopts = [
    "db-host:",
    "db-port:",
    "db-name:",
    "db-user:",
    "db-pass:",
    "dir:",
    "help"
];
$options = getopt("", $longopts);

if (isset($options['help'])) {
    $usage = <<<'USAGE'
Usage: php scripts/apply-migrations.php [options]

Options:
  --db-host=HOST    Database host (overrides DB_HOST environment variable)
  --db-port=PORT    Database port (overrides DB_PORT environment variable)
  --db-name=NAME    Database name (overrides DB_NAME environment variable)
  --db-user=USER    Database user (overrides DB_USER environment variable)
  --db-pass=PASS    Database password (overrides DB_PASS environment variable)
  --dir=DIR         Directory containing .sql migration files (defaults to sql/migrations)
  --help            Show this help message

The script will apply all .sql files from the migrations directory in
alphabetical order using mysqli->multi_query() and will exit with a non-zero
status if any error occurs.
USAGE;
    echo $usage . "\n";
    exit(0);
}

// Helper to pick CLI override or environment variable
function getSetting(array $options, string $optName, string $envName, $default = null)
{
    if (isset($options[$optName]) && $options[$optName] !== false && $options[$optName] !== null) {
        return $options[$optName];
    }
    $val = getenv($envName);
    if ($val !== false) {
        return $val;
    }
    return $default;
}

$dbHost = getSetting($options, 'db-host', 'DB_HOST');
$dbPort = getSetting($options, 'db-port', 'DB_PORT', 3306);
$dbName = getSetting($options, 'db-name', 'DB_NAME');
$dbUser = getSetting($options, 'db-user', 'DB_USER');
$dbPass = getSetting($options, 'db-pass', 'DB_PASS');
$migrationsDir = getSetting($options, 'dir', 'MIGRATIONS_DIR', __DIR__ . '/../sql/migrations');

// Validate required settings
$missing = [];
if (empty($dbHost)) $missing[] = 'DB_HOST (or --db-host)';
if (empty($dbName)) $missing[] = 'DB_NAME (or --db-name)';
if (empty($dbUser)) $missing[] = 'DB_USER (or --db-user)';

if (!empty($missing)) {
    fwrite(STDERR, "Missing required database settings: " . implode(', ', $missing) . "\n");
    fwrite(STDERR, "Provide them via environment variables or CLI options. Use --help for details.\n");
    exit(1);
}

// Normalize migrations directory
$migrationsDir = rtrim($migrationsDir, DIRECTORY_SEPARATOR);
if (!is_dir($migrationsDir)) {
    fwrite(STDERR, "Migrations directory not found: {$migrationsDir}\n");
    exit(1);
}

// Find .sql files
$pattern = $migrationsDir . DIRECTORY_SEPARATOR . '*.sql';
$files = glob($pattern);
if ($files === false) $files = [];
sort($files, SORT_STRING);

if (count($files) === 0) {
    echo "No .sql migration files found in {$migrationsDir}. Nothing to do.\n";
    exit(0);
}

// Connect to MySQL
echo "Connecting to MySQL at {$dbHost}:{$dbPort}, database '{$dbName}', user '{$dbUser}'...\n";
$mysqli = @new mysqli($dbHost, $dbUser, $dbPass ?? '', $dbName, (int)$dbPort);
if ($mysqli->connect_errno) {
    fwrite(STDERR, "MySQL connection failed ({$mysqli->connect_errno}): {$mysqli->connect_error}\n");
    exit(1);
}

echo "Connected successfully. Found " . count($files) . " migration file(s).\n";

$total = count($files);
$idx = 0;
foreach ($files as $file) {
    $idx++;
    $base = basename($file);
    echo "[{$idx}/{$total}] Applying {$base}... ";

    $sql = file_get_contents($file);
    if ($sql === false) {
        fwrite(STDERR, "\nFailed to read file: {$file}\n");
        $mysqli->close();
        exit(1);
    }

    // Skip empty files
    if (trim($sql) === '') {
        echo "skipped (empty)\n";
        continue;
    }

    // Execute the SQL file using multi_query
    if (!$mysqli->multi_query($sql)) {
        fwrite(STDERR, "\nError executing {$base}: ({$mysqli->errno}) {$mysqli->error}\n");
        $mysqli->close();
        exit(1);
    }

    // Drain all results; check for errors while iterating
    do {
        if ($result = $mysqli->store_result()) {
            $result->free();
        } else {
            // If there is no result set, check for an error code
            if ($mysqli->errno) {
                fwrite(STDERR, "\nError after running statements in {$base}: ({$mysqli->errno}) {$mysqli->error}\n");
                $mysqli->close();
                exit(1);
            }
        }
        // Continue to next result if any
        if ($mysqli->more_results()) {
            $hasNext = $mysqli->next_result();
            if ($hasNext === false && $mysqli->errno) {
                fwrite(STDERR, "\nError advancing to next result for {$base}: ({$mysqli->errno}) {$mysqli->error}\n");
                $mysqli->close();
                exit(1);
            }
        } else {
            break;
        }
    } while (true);

    echo "done\n";
}

echo "All migrations applied successfully.\n";
$mysqli->close();
exit(0);
