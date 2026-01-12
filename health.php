<?php
/**
 * Health Check Endpoint
 * Used for monitoring and load balancers
 */

header('Content-Type: application/json');

$health = [
    'status' => 'healthy',
    'timestamp' => time(),
    'checks' => []
];

// Check database connection
try {
    require_once __DIR__ . '/includes/db.php';
    
    $stmt = $pdo->query('SELECT 1');
    $health['checks']['database'] = [
        'status' => 'healthy',
        'message' => 'Database connection OK'
    ];
} catch (Exception $e) {
    $health['status'] = 'unhealthy';
    $health['checks']['database'] = [
        'status' => 'unhealthy',
        'message' => 'Database connection failed',
        'error' => $e->getMessage()
    ];
}

// Check PHP version
$health['checks']['php'] = [
    'status' => 'healthy',
    'version' => PHP_VERSION
];

// Check disk space
$diskFree = disk_free_space(__DIR__);
$diskTotal = disk_total_space(__DIR__);
$diskUsage = (1 - ($diskFree / $diskTotal)) * 100;

$health['checks']['disk'] = [
    'status' => $diskUsage < 90 ? 'healthy' : 'warning',
    'usage_percent' => round($diskUsage, 2),
    'free_space' => $this->formatBytes($diskFree ?? 0),
    'total_space' => $this->formatBytes($diskTotal ?? 0)
];

// Check memory usage
$memoryUsage = memory_get_usage(true);
$memoryLimit = ini_get('memory_limit');

$health['checks']['memory'] = [
    'status' => 'healthy',
    'usage' => $this->formatBytes($memoryUsage),
    'limit' => $memoryLimit
];

// Check log directory writable
$logDir = __DIR__ . '/logs';
$health['checks']['logs'] = [
    'status' => is_writable($logDir) ? 'healthy' : 'unhealthy',
    'writable' => is_writable($logDir)
];

// Check upload directory writable
$uploadDir = __DIR__ . '/uploads';
if (is_dir($uploadDir)) {
    $health['checks']['uploads'] = [
        'status' => is_writable($uploadDir) ? 'healthy' : 'warning',
        'writable' => is_writable($uploadDir)
    ];
}

// Overall health status
foreach ($health['checks'] as $check) {
    if ($check['status'] === 'unhealthy') {
        $health['status'] = 'unhealthy';
        http_response_code(503);
        break;
    }
}

// Helper function
function formatBytes($bytes) {
    $units = ['B', 'KB', 'MB', 'GB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= (1 << (10 * $pow));
    return round($bytes, 2) . ' ' . $units[$pow];
}

echo json_encode($health, JSON_PRETTY_PRINT);
