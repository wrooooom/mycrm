<?php
/**
 * Push Notifications API Endpoint
 */

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once '../config/database.php';
require_once '../auth.php';
require_once '../includes/logger.php';
require_once '../includes/integrations/PushNotification.php';

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Требуется авторизация']);
    exit();
}

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

if ($method === 'POST') {
    if ($action === 'send') {
        sendPushNotification();
    } elseif ($action === 'register_token') {
        registerDeviceToken();
    } else {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
}

function sendPushNotification() {
    $data = json_decode(file_get_contents("php://input"), true);
    
    if (empty($data['user_id']) || empty($data['title']) || empty($data['body'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'User ID, title and body are required']);
        return;
    }
    
    try {
        $pushService = new PushNotification();
        $result = $pushService->send($data['user_id'], $data['title'], $data['body'], $data['data'] ?? []);
        
        http_response_code(200);
        echo json_encode($result);
    } catch (Exception $e) {
        logger()->error("Push notification error", ['error' => $e->getMessage()]);
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function registerDeviceToken() {
    $data = json_decode(file_get_contents("php://input"), true);
    
    if (empty($data['token']) || empty($data['device_type'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Token and device_type are required']);
        return;
    }
    
    try {
        $pushService = new PushNotification();
        $result = $pushService->registerDeviceToken(
            $_SESSION['user_id'],
            $data['token'],
            $data['device_type'],
            $data['device_name'] ?? null
        );
        
        http_response_code(200);
        echo json_encode(['success' => $result]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}
