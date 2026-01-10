<?php
/**
 * Public database configuration loader
 * This file should remain in config/ folder
 */

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Define protected path (adjust according to your server structure)
$protected_path = dirname(dirname(__FILE__)) . '/protected/config.php';

// Check if protected config exists
if (!file_exists($protected_path)) {
    die('Configuration file not found. Please contact administrator.');
}

// Include protected configuration
define('PROTECTED_ACCESS', true);
require_once $protected_path;

// Database connection function
function connectDatabase() {
    try {
        $config = getDatabaseConfig();
        
        // Create PDO connection
        $dsn = "mysql:host=" . $config['host'] . ";dbname=" . $config['database'] . ";charset=" . $config['charset'];
        $pdo = new PDO($dsn, $config['username'], $config['password']);
        
        // Set PDO error mode to exception
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
        
        return $pdo;
        
    } catch (PDOException $e) {
        // Log error instead of displaying it
        error_log("Database connection failed: " . $e->getMessage());
        
        // Display user-friendly message
        if (defined('APP_DEBUG') && APP_DEBUG) {
            die("Database connection error: " . $e->getMessage());
        } else {
            die("Database connection failed. Please try again later.");
        }
    }
}

// Test connection (optional)
function testDatabaseConnection() {
    try {
        $pdo = connectDatabase();
        return [
            'success' => true,
            'message' => 'Database connection successful',
            'tables' => getDatabaseTables($pdo)
        ];
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => $e->getMessage()
        ];
    }
}

// Get list of tables (for test page)
function getDatabaseTables($pdo) {
    $stmt = $pdo->query("SHOW TABLES");
    $tables = [];
    while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
        $tables[] = $row[0];
    }
    return $tables;
}

// Get record counts for test page
function getTableRecordCounts($pdo) {
    $tables = getDatabaseTables($pdo);
    $counts = [];
    
    foreach ($tables as $table) {
        try {
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM $table");
            $counts[$table] = $stmt->fetch()['count'];
        } catch (Exception $e) {
            $counts[$table] = 'Error';
        }
    }
    
    return $counts;
}
?>