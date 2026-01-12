#!/usr/bin/env php
<?php
/**
 * Apply Stage 4 Security Migration
 * Adds security tables and indexes for production
 */

require_once __DIR__ . '/../config/database.php';

try {
    echo "🚀 Starting Stage 4 Migration...\n\n";
    
    // Read SQL file
    $sqlFile = __DIR__ . '/../sql/stage4_security.sql';
    
    if (!file_exists($sqlFile)) {
        throw new Exception("Migration file not found: $sqlFile");
    }
    
    $sql = file_get_contents($sqlFile);
    
    // Connect to database
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false
    ]);
    
    echo "✓ Connected to database: " . DB_NAME . "\n\n";
    
    // Split SQL into statements
    $statements = array_filter(
        array_map('trim', explode(';', $sql)),
        function($stmt) {
            return !empty($stmt) && 
                   stripos($stmt, 'DELIMITER') === false &&
                   stripos($stmt, '//') === false;
        }
    );
    
    echo "📝 Executing " . count($statements) . " SQL statements...\n\n";
    
    $pdo->beginTransaction();
    
    $success = 0;
    $failed = 0;
    
    foreach ($statements as $statement) {
        try {
            // Skip comments
            if (strpos(trim($statement), '--') === 0) continue;
            
            $pdo->exec($statement);
            $success++;
            
            // Show what was executed
            $firstLine = strtok($statement, "\n");
            echo "  ✓ " . substr($firstLine, 0, 80) . "\n";
            
        } catch (PDOException $e) {
            // Some errors are okay (e.g., table already exists)
            if (stripos($e->getMessage(), 'already exists') !== false ||
                stripos($e->getMessage(), 'Duplicate') !== false) {
                echo "  ⚠ " . $e->getMessage() . "\n";
            } else {
                $failed++;
                echo "  ✗ Error: " . $e->getMessage() . "\n";
                echo "  Statement: " . substr($statement, 0, 100) . "...\n\n";
            }
        }
    }
    
    if ($failed === 0) {
        $pdo->commit();
        echo "\n✅ Migration completed successfully!\n";
        echo "   Executed: $success statements\n\n";
        
        // Show table summary
        showTableSummary($pdo);
        
    } else {
        $pdo->rollBack();
        echo "\n❌ Migration failed with $failed errors\n";
        echo "   Changes have been rolled back\n\n";
        exit(1);
    }
    
} catch (Exception $e) {
    echo "\n❌ Migration failed: " . $e->getMessage() . "\n\n";
    exit(1);
}

/**
 * Show summary of created tables
 */
function showTableSummary($pdo) {
    echo "📊 Database Summary:\n";
    echo str_repeat('-', 60) . "\n";
    
    $tables = [
        'security_logs',
        'sessions',
        'rate_limits',
        'system_settings',
        'backup_history',
        'api_tokens'
    ];
    
    foreach ($tables as $table) {
        try {
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM $table");
            $count = $stmt->fetch()['count'];
            printf("%-30s %10s rows\n", $table, $count);
        } catch (Exception $e) {
            printf("%-30s %10s\n", $table, "N/A");
        }
    }
    
    echo str_repeat('-', 60) . "\n\n";
}
