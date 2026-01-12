<?php
/**
 * Server-Sent Events (SSE) Endpoint
 * Real-time notifications stream
 */

// Disable output buffering
if (ob_get_level()) ob_end_clean();

// Set headers for SSE
header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive');
header('X-Accel-Buffering: no'); // Disable nginx buffering

// CORS headers
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: X-Requested-With');

// Disable time limit
set_time_limit(0);

// Start session to get user
session_start();

// Check authentication
if (!isset($_SESSION['user_id'])) {
    echo "event: error\n";
    echo "data: " . json_encode(['error' => 'Unauthorized']) . "\n\n";
    flush();
    exit;
}

$userId = $_SESSION['user_id'];
$userRole = $_SESSION['role'] ?? 'driver';
$lastEventId = $_GET['lastEventId'] ?? null;

// Database connection
require_once __DIR__ . '/../includes/db.php';

/**
 * Send SSE message
 */
function sendSSE($event, $data, $id = null) {
    if ($id) {
        echo "id: $id\n";
    }
    echo "event: $event\n";
    echo "data: " . json_encode($data) . "\n\n";
    
    // Flush output
    if (ob_get_level()) ob_flush();
    flush();
}

/**
 * Get user notifications
 */
function getUserNotifications($pdo, $userId, $sinceId = null) {
    try {
        $query = "SELECT * FROM notifications WHERE user_id = ? ";
        $params = [$userId];
        
        if ($sinceId) {
            $query .= "AND id > ? ";
            $params[] = $sinceId;
        }
        
        $query .= "ORDER BY created_at DESC LIMIT 10";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Failed to get notifications: " . $e->getMessage());
        return [];
    }
}

/**
 * Get application status updates
 */
function getApplicationUpdates($pdo, $userRole, $userId, $sinceTime) {
    try {
        $query = "SELECT a.*, 
                         d.full_name as driver_name,
                         v.license_plate as vehicle_plate
                  FROM applications a
                  LEFT JOIN drivers d ON a.driver_id = d.id
                  LEFT JOIN vehicles v ON a.vehicle_id = v.id
                  WHERE a.updated_at > ? ";
        
        $params = [$sinceTime];
        
        // Filter by role
        if ($userRole === 'driver') {
            $query .= "AND a.driver_id = ? ";
            $params[] = $userId;
        }
        
        $query .= "ORDER BY a.updated_at DESC LIMIT 5";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Failed to get application updates: " . $e->getMessage());
        return [];
    }
}

// Send initial connection message
sendSSE('connected', [
    'message' => 'Connected to notification stream',
    'userId' => $userId,
    'timestamp' => time()
], 'init_' . time());

// Track last check time
$lastCheckTime = date('Y-m-d H:i:s');
$eventCounter = 0;

// Main event loop
while (true) {
    // Check if connection is still alive
    if (connection_aborted()) {
        break;
    }
    
    // Get new notifications
    $notifications = getUserNotifications($pdo, $userId, $lastEventId);
    
    foreach ($notifications as $notification) {
        sendSSE('notification', [
            'id' => $notification['id'],
            'title' => $notification['title'],
            'body' => $notification['message'],
            'type' => $notification['type'],
            'icon' => 'fas fa-bell',
            'timestamp' => strtotime($notification['created_at']),
            'read' => (bool)$notification['is_read']
        ], 'notif_' . $notification['id']);
        
        $lastEventId = $notification['id'];
    }
    
    // Get application status updates
    $updates = getApplicationUpdates($pdo, $userRole, $userId, $lastCheckTime);
    
    foreach ($updates as $update) {
        sendSSE('status_update', [
            'id' => $update['id'],
            'type' => 'application',
            'status' => $update['status'],
            'application_id' => $update['id'],
            'driver_name' => $update['driver_name'],
            'vehicle_plate' => $update['vehicle_plate'],
            'timestamp' => strtotime($update['updated_at'])
        ], 'update_' . $update['id'] . '_' . time());
    }
    
    $lastCheckTime = date('Y-m-d H:i:s');
    
    // Send heartbeat every 30 seconds
    $eventCounter++;
    if ($eventCounter % 6 == 0) { // 6 * 5 seconds = 30 seconds
        sendSSE('heartbeat', [
            'timestamp' => time(),
            'active' => true
        ], 'heartbeat_' . time());
    }
    
    // Sleep for 5 seconds before next check
    sleep(5);
    
    // Prevent memory leaks
    if ($eventCounter > 1000) {
        sendSSE('reconnect', [
            'message' => 'Connection refresh required',
            'timestamp' => time()
        ], 'reconnect_' . time());
        break;
    }
}

// Close connection
$pdo = null;
exit;
