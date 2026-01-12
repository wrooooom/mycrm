<?php
/**
 * Payment Gateway API Endpoint
 */

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once '../config/database.php';
require_once '../includes/logger.php';
require_once '../includes/integrations/PaymentGateway.php';

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

if ($action === 'webhook') {
    processWebhook();
    exit();
}

require_once '../auth.php';

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Требуется авторизация']);
    exit();
}

switch ($method) {
    case 'POST':
        if ($action === 'create') {
            createPayment();
        } elseif ($action === 'refund') {
            refundPayment();
        } else {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
        }
        break;
    case 'GET':
        if ($action === 'status') {
            getPaymentStatus();
        } else {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
        }
        break;
    default:
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
}

function createPayment() {
    $data = json_decode(file_get_contents("php://input"), true);
    
    if (empty($data['amount']) || empty($data['application_id'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Amount and application_id are required']);
        return;
    }
    
    try {
        $gateway = PaymentGateway::create();
        
        $options = [
            'application_id' => $data['application_id'],
            'user_id' => $_SESSION['user_id'],
            'description' => $data['description'] ?? 'Payment for application',
            'customer_email' => $data['customer_email'] ?? null,
            'return_url' => $data['return_url'] ?? null
        ];
        
        $result = $gateway->createPayment($data['amount'], $options);
        
        if ($result['success']) {
            http_response_code(200);
            echo json_encode([
                'success' => true,
                'transaction_id' => $result['transaction_id'],
                'payment_link' => $result['payment_link'] ?? null,
                'status' => $result['status']
            ]);
        } else {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Failed to create payment',
                'error' => $result['error'] ?? 'Unknown error'
            ]);
        }
    } catch (Exception $e) {
        logger()->error("Payment creation error", ['error' => $e->getMessage()]);
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function getPaymentStatus() {
    $transactionId = $_GET['transaction_id'] ?? '';
    
    if (empty($transactionId)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Transaction ID required']);
        return;
    }
    
    try {
        $gateway = PaymentGateway::create();
        $result = $gateway->getPaymentStatus($transactionId);
        
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

function refundPayment() {
    $data = json_decode(file_get_contents("php://input"), true);
    
    if (empty($data['transaction_id'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Transaction ID required']);
        return;
    }
    
    try {
        $gateway = PaymentGateway::create();
        $result = $gateway->refundPayment($data['transaction_id'], $data['amount'] ?? null);
        
        if ($result['success']) {
            http_response_code(200);
            echo json_encode([
                'success' => true,
                'refund_id' => $result['refund_id'] ?? null
            ]);
        } else {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => $result['error'] ?? 'Refund failed'
            ]);
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function processWebhook() {
    $payload = file_get_contents('php://input');
    $signature = $_SERVER['HTTP_X_SIGNATURE'] ?? $_SERVER['HTTP_X_YOOKASSA_SIGNATURE'] ?? '';
    
    logger()->info("Payment webhook received", ['signature' => substr($signature, 0, 20)]);
    
    $database = new Database();
    $conn = $database->getConnection();
    
    $stmt = $conn->prepare(
        "INSERT INTO webhook_events (provider, event_type, payload, signature, ip_address, is_verified, created_at) 
         VALUES (:provider, :event_type, :payload, :signature, :ip, :verified, NOW())"
    );
    
    $stmt->execute([
        ':provider' => 'payment',
        ':event_type' => 'payment_notification',
        ':payload' => $payload,
        ':signature' => $signature,
        ':ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        ':verified' => 0
    ]);
    
    try {
        $gateway = PaymentGateway::create();
        
        if (!$gateway->verifyWebhook($payload, $signature)) {
            logger()->warning("Invalid webhook signature");
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Invalid signature']);
            return;
        }
        
        $result = $gateway->processWebhook($payload);
        
        logger()->info("Webhook processed", ['result' => $result]);
        
        http_response_code(200);
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        logger()->error("Webhook processing error", ['error' => $e->getMessage()]);
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}
