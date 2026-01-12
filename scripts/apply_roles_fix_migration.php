<?php
/**
 * Migration script for fixing roles system
 * Replaces dispatcher role with client role
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../config/database.php';

function applyRolesMigration() {
    global $pdo;
    try {
        echo "=== Applying Roles Migration ===\n";
        
        // Step 1: Update existing users with dispatcher role
        echo "Step 1: Updating users with dispatcher role to client...\n";
        $stmt = $pdo->prepare("UPDATE users SET role = 'client' WHERE role = 'dispatcher'");
        $stmt->execute();
        $updatedCount = $stmt->rowCount();
        echo "Updated {$updatedCount} users from dispatcher to client role\n";
        
        // Step 2: Update test data dispatcher user
        echo "Step 2: Updating test dispatcher user to manager...\n";
        $stmt = $pdo->prepare("UPDATE users SET username = 'manager2', email = 'manager2@proftransfer.ru' WHERE email = 'dispatcher@proftransfer.ru'");
        $stmt->execute();
        echo "Updated test dispatcher user to manager\n";
        
        // Step 3: Verify the changes
        echo "Step 3: Verifying role distribution...\n";
        $stmt = $pdo->query("SELECT role, COUNT(*) as count FROM users GROUP BY role ORDER BY role");
        $roles = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "Current role distribution:\n";
        foreach ($roles as $role) {
            echo "  - {$role['role']}: {$role['count']} users\n";
        }
        
        // Step 4: Log the migration
        echo "Step 4: Logging migration activity...\n";
        $stmt = $pdo->prepare("INSERT INTO activity_log (user_id, action, ip_address) VALUES (1, 'Applied roles migration: dispatcher->client, updated test users', '127.0.0.1')");
        $stmt->execute();
        
        echo "=== Migration completed successfully ===\n";
        return true;
        
    } catch (Exception $e) {
        echo "ERROR: Migration failed - " . $e->getMessage() . "\n";
        return false;
    }
}

// Run migration
if (php_sapi_name() === 'cli') {
    $success = applyRolesMigration();
    exit($success ? 0 : 1);
}
?>