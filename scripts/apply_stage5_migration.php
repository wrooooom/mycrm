<?php
/**
 * Apply Stage 5 Migration - Integrations and Extended Functionality
 */

require_once __DIR__ . '/../config/database.php';

echo "==========================================\n";
echo "Stage 5 Migration - Integrations\n";
echo "==========================================\n\n";

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    echo "✓ Database connection established\n\n";
    
    // Read migration file
    $migrationFile = __DIR__ . '/../sql/stage5_integrations.sql';
    
    if (!file_exists($migrationFile)) {
        throw new Exception("Migration file not found: {$migrationFile}");
    }
    
    $sql = file_get_contents($migrationFile);
    
    // Remove comments and split by semicolons
    $lines = explode("\n", $sql);
    $cleanedLines = array_filter($lines, function($line) {
        $trimmed = trim($line);
        return !empty($trimmed) && strpos($trimmed, '--') !== 0;
    });
    
    $cleanedSql = implode("\n", $cleanedLines);
    
    // Split by semicolons
    $statements = array_filter(
        array_map('trim', explode(';', $cleanedSql)),
        function($stmt) {
            return !empty($stmt) && strlen($stmt) > 10;
        }
    );
    
    echo "Found " . count($statements) . " SQL statements to execute\n\n";
    
    $successCount = 0;
    $errorCount = 0;
    
    foreach ($statements as $index => $statement) {
        try {
            // Extract table name for logging
            if (preg_match('/CREATE TABLE[^`]*`?(\w+)`?/i', $statement, $matches)) {
                $tableName = $matches[1];
                echo "Creating table: {$tableName}... ";
                $conn->exec($statement);
                echo "✓\n";
                $successCount++;
            } elseif (preg_match('/INSERT INTO[^`]*`?(\w+)`?/i', $statement, $matches)) {
                $tableName = $matches[1];
                echo "Inserting data into: {$tableName}... ";
                $conn->exec($statement);
                echo "✓\n";
                $successCount++;
            } else {
                $conn->exec($statement);
                $successCount++;
            }
        } catch (PDOException $e) {
            // Ignore duplicate key/table exists errors
            if (strpos($e->getMessage(), 'already exists') !== false ||
                strpos($e->getMessage(), 'Duplicate') !== false) {
                echo "⚠ Already exists, skipping\n";
            } else {
                echo "✗ Error: " . $e->getMessage() . "\n";
                $errorCount++;
            }
        }
    }
    
    echo "\n==========================================\n";
    echo "Migration Summary:\n";
    echo "- Successful: {$successCount}\n";
    echo "- Errors: {$errorCount}\n";
    echo "==========================================\n\n";
    
    // Verify tables
    echo "Verifying created tables:\n\n";
    
    $tables = [
        'sms_log',
        'email_log',
        'payment_transactions',
        'device_tokens',
        'push_notification_log',
        'erp_sync_log',
        'notification_queue',
        'telegram_users',
        'export_jobs',
        'webhook_events',
        'gps_tracking',
        'integration_settings'
    ];
    
    foreach ($tables as $table) {
        $stmt = $conn->query("SHOW TABLES LIKE '{$table}'");
        if ($stmt->rowCount() > 0) {
            $countStmt = $conn->query("SELECT COUNT(*) FROM {$table}");
            $count = $countStmt->fetchColumn();
            echo "✓ {$table} (rows: {$count})\n";
        } else {
            echo "✗ {$table} not found\n";
        }
    }
    
    echo "\n==========================================\n";
    echo "✓ Stage 5 Migration completed successfully!\n";
    echo "==========================================\n\n";
    
    echo "Next steps:\n";
    echo "1. Configure integration settings in .env\n";
    echo "2. Enable required integrations\n";
    echo "3. Test each integration\n";
    echo "4. Review INTEGRATIONS.md for setup instructions\n\n";
    
} catch (Exception $e) {
    if (isset($conn) && $conn->inTransaction()) {
        $conn->rollBack();
    }
    
    echo "\n✗ Migration failed: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
