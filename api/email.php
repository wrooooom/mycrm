<?php
/**
 * Email API Endpoint
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
require_once '../includes/integrations/EmailProvider.php';

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Требуется авторизация']);
    exit();
}

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

if ($method === 'POST') {
    if ($action === 'send') {
        sendEmail();
    } elseif ($action === 'send_template') {
        sendEmailTemplate();
    } else {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
}

function sendEmail() {
    $data = json_decode(file_get_contents("php://input"), true);
    
    if (empty($data['to']) || empty($data['subject']) || empty($data['body'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'To, subject and body are required']);
        return;
    }
    
    try {
        $emailProvider = new EmailProvider();
        
        $options = [
            'user_id' => $_SESSION['user_id'],
            'application_id' => $data['application_id'] ?? null,
            'cc' => $data['cc'] ?? null,
            'is_html' => $data['is_html'] ?? true
        ];
        
        $result = $emailProvider->send($data['to'], $data['subject'], $data['body'], $options);
        
        if ($result['success']) {
            http_response_code(200);
            echo json_encode([
                'success' => true,
                'message' => 'Email sent successfully'
            ]);
        } else {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Failed to send email',
                'error' => $result['error'] ?? 'Unknown error'
            ]);
        }
    } catch (Exception $e) {
        logger()->error("Email send error", ['error' => $e->getMessage()]);
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function sendEmailTemplate() {
    $data = json_decode(file_get_contents("php://input"), true);
    
    if (empty($data['to']) || empty($data['template']) || empty($data['data'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'To, template and data are required']);
        return;
    }
    
    try {
        $emailProvider = new EmailProvider();
        
        $options = [
            'user_id' => $_SESSION['user_id'],
            'application_id' => $data['application_id'] ?? null
        ];
        
        $result = $emailProvider->sendTemplate($data['to'], $data['template'], $data['data'], $options);
        
        if ($result['success']) {
            http_response_code(200);
            echo json_encode([
                'success' => true,
                'message' => 'Email sent successfully'
            ]);
        } else {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Failed to send email',
                'error' => $result['error'] ?? 'Unknown error'
            ]);
        }
    } catch (Exception $e) {
        logger()->error("Email template send error", ['error' => $e->getMessage()]);
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}
