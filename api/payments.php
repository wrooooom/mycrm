<?php
/**
 * API для управления платежами
 */

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once '../config/database.php';
require_once '../auth.php';

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Требуется авторизация']);
    exit();
}

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

switch ($method) {
    case 'GET':
        if ($action === 'getByApplication') {
            getPaymentsByApplication();
        } else {
            getAllPayments();
        }
        break;
    case 'POST':
        createPayment();
        break;
    case 'PUT':
        updatePayment();
        break;
    case 'DELETE':
        deletePayment();
        break;
    default:
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Метод не поддерживается']);
}

function getAllPayments() {
    try {
        $database = new Database();
        $conn = $database->getConnection();
        
        $limit = min(100, max(1, intval($_GET['limit'] ?? 50)));
        $offset = max(0, intval($_GET['offset'] ?? 0));
        $status = $_GET['status'] ?? null;
        
        $query = "SELECT p.*, a.application_number, a.customer_name, u.username as user_name
                  FROM payments p
                  LEFT JOIN applications a ON p.application_id = a.id
                  LEFT JOIN users u ON p.user_id = u.id
                  WHERE 1=1";
        
        $params = [];
        
        if ($status) {
            $query .= " AND p.status = :status";
            $params[':status'] = $status;
        }
        
        $query .= " ORDER BY p.created_at DESC LIMIT :limit OFFSET :offset";
        
        $stmt = $conn->prepare($query);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Получаем общее количество
        $countQuery = "SELECT COUNT(*) FROM payments WHERE 1=1";
        if ($status) {
            $countQuery .= " AND status = :status";
        }
        $countStmt = $conn->prepare($countQuery);
        if ($status) {
            $countStmt->execute([':status' => $status]);
        } else {
            $countStmt->execute();
        }
        $total = $countStmt->fetchColumn();
        
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'data' => $payments,
            'total' => $total
        ]);
        
    } catch (Exception $e) {
        error_log("Payments fetch error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Ошибка получения платежей']);
    }
}

function getPaymentsByApplication() {
    $applicationId = intval($_GET['application_id'] ?? 0);
    
    if (!$applicationId) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Не указан ID заявки']);
        return;
    }
    
    try {
        $database = new Database();
        $conn = $database->getConnection();
        
        $query = "SELECT p.*, u.username as user_name
                  FROM payments p
                  LEFT JOIN users u ON p.user_id = u.id
                  WHERE p.application_id = :application_id
                  ORDER BY p.created_at DESC";
        
        $stmt = $conn->prepare($query);
        $stmt->execute([':application_id' => $applicationId]);
        
        $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Подсчитываем общую сумму
        $totalAmount = array_reduce($payments, function($carry, $payment) {
            if ($payment['status'] === 'completed') {
                return $carry + $payment['amount'];
            }
            return $carry;
        }, 0);
        
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'data' => $payments,
            'total_amount' => $totalAmount
        ]);
        
    } catch (Exception $e) {
        error_log("Payments by application fetch error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Ошибка получения платежей']);
    }
}

function createPayment() {
    $data = json_decode(file_get_contents("php://input"), true);
    
    if (empty($data)) {
        $data = $_POST;
    }
    
    // Валидация
    if (empty($data['application_id']) || empty($data['amount'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Не указаны обязательные поля']);
        return;
    }
    
    try {
        $database = new Database();
        $conn = $database->getConnection();
        
        $query = "INSERT INTO payments (application_id, user_id, amount, status, method, payment_date, notes)
                  VALUES (:application_id, :user_id, :amount, :status, :method, :payment_date, :notes)";
        
        $stmt = $conn->prepare($query);
        $stmt->execute([
            ':application_id' => intval($data['application_id']),
            ':user_id' => $_SESSION['user_id'],
            ':amount' => floatval($data['amount']),
            ':status' => $data['status'] ?? 'pending',
            ':method' => $data['method'] ?? 'cash',
            ':payment_date' => $data['payment_date'] ?? null,
            ':notes' => $data['notes'] ?? null
        ]);
        
        $paymentId = $conn->lastInsertId();
        
        // Логируем
        $logQuery = "INSERT INTO activity_log (user_id, action, ip_address) 
                    VALUES (:user_id, :action, :ip)";
        $logStmt = $conn->prepare($logQuery);
        $logStmt->execute([
            ':user_id' => $_SESSION['user_id'],
            ':action' => "Создан платеж #{$paymentId} для заявки #{$data['application_id']}",
            ':ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ]);
        
        http_response_code(201);
        echo json_encode([
            'success' => true,
            'message' => 'Платеж создан',
            'payment_id' => $paymentId
        ]);
        
    } catch (Exception $e) {
        error_log("Payment creation error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Ошибка создания платежа']);
    }
}

function updatePayment() {
    $data = json_decode(file_get_contents("php://input"), true);
    
    if (empty($data['id'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Не указан ID платежа']);
        return;
    }
    
    try {
        $database = new Database();
        $conn = $database->getConnection();
        
        $query = "UPDATE payments SET 
                  amount = :amount,
                  status = :status,
                  method = :method,
                  payment_date = :payment_date,
                  notes = :notes,
                  updated_at = CURRENT_TIMESTAMP
                  WHERE id = :id";
        
        $stmt = $conn->prepare($query);
        $stmt->execute([
            ':amount' => floatval($data['amount']),
            ':status' => $data['status'],
            ':method' => $data['method'],
            ':payment_date' => $data['payment_date'] ?? null,
            ':notes' => $data['notes'] ?? null,
            ':id' => intval($data['id'])
        ]);
        
        http_response_code(200);
        echo json_encode(['success' => true, 'message' => 'Платеж обновлен']);
        
    } catch (Exception $e) {
        error_log("Payment update error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Ошибка обновления платежа']);
    }
}

function deletePayment() {
    $data = json_decode(file_get_contents("php://input"), true);
    
    if (empty($data['id'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Не указан ID платежа']);
        return;
    }
    
    try {
        $database = new Database();
        $conn = $database->getConnection();
        
        $query = "DELETE FROM payments WHERE id = :id";
        $stmt = $conn->prepare($query);
        $stmt->execute([':id' => intval($data['id'])]);
        
        http_response_code(200);
        echo json_encode(['success' => true, 'message' => 'Платеж удален']);
        
    } catch (Exception $e) {
        error_log("Payment delete error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Ошибка удаления платежа']);
    }
}
?>
