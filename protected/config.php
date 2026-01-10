<?php
/**
 * Protected configuration file
 * Move this file outside of public_html for maximum security
 */

// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'ca991909_crm');
define('DB_USER', 'ca991909_crm');
define('DB_PASS', '!Mazay199');
define('DB_CHARSET', 'utf8mb4');

// Application settings
define('APP_DEBUG', true);
define('APP_VERSION', '1.0');

// Session settings
define('SESSION_TIMEOUT', 3600); // 1 hour

// Return database configuration as array (for backward compatibility)
function getDatabaseConfig() {
    return [
        'host' => DB_HOST,
        'database' => DB_NAME,
        'username' => DB_USER,
        'password' => DB_PASS,
        'charset' => DB_CHARSET
    ];
}

// Prevent direct access
if (!defined('PROTECTED_ACCESS')) {
    die('Direct access not permitted');
}
?>