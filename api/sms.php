<?php
/**
 * SMS API Endpoint
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
require_once '../includes/integrations/SmsProvider.php';

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Требуется авторизация']);
    exit();
}

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

if ($method === 'POST') {
    if ($action === 'send') {
        sendSms();
    } elseif ($action === 'status') {
        getSmsStatus();
    } elseif ($action === 'balance') {
        getSmsBalance();
    } else {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
}

function sendSms() {
    $data = json_decode(file_get_contents("php://input"), true);
    
    if (empty($data['phone']) || empty($data['message'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Phone and message are required']);
        return;
    }
    
    try {
        $smsProvider = SmsProvider::create();
        
        $options = [
            'user_id' => $_SESSION['user_id'],
            'application_id' => $data['application_id'] ?? null
        ];
        
        $result = $smsProvider->send($data['phone'], $data['message'], $options);
        
        if ($result['success']) {
            http_response_code(200);
            echo json_encode([
                'success' => true,
                'message' => 'SMS sent successfully',
                'message_id' => $result['message_id'] ?? null
            ]);
        } else {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Failed to send SMS',
                'error' => $result['error'] ?? 'Unknown error'
            ]);
        }
    } catch (Exception $e) {
        logger()->error("SMS send error", ['error' => $e->getMessage()]);
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function getSmsStatus() {
    $messageId = $_GET['message_id'] ?? '';
    
    if (empty($messageId)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Message ID required']);
        return;
    }
    
    try {
        $smsProvider = SmsProvider::create();
        $result = $smsProvider->getStatus($messageId);
        
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'status' => $result['status'] ?? 'unknown'
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function getSmsBalance() {
    try {
        $smsProvider = SmsProvider::create();
        $result = $smsProvider->getBalance();
        
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'balance' => $result['balance'] ?? 0
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}
