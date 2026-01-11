<?php
/**
 * API для управления уведомлениями
 */

// CORS headers
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

// Проверяем авторизацию
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Требуется авторизация'
    ]);
    exit();
}

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

switch ($method) {
    case 'GET':
        if ($action === 'getAll') {
            getAllNotifications();
        } elseif ($action === 'getUnread') {
            getUnreadNotifications();
        } elseif ($action === 'getCount') {
            getUnreadCount();
        } else {
            getAllNotifications();
        }
        break;
    case 'POST':
        if ($action === 'markAsRead') {
            markAsRead();
        } elseif ($action === 'markAllAsRead') {
            markAllAsRead();
        } else {
            createNotification();
        }
        break;
    case 'DELETE':
        deleteNotification();
        break;
    default:
        http_response_code(405);
        echo json_encode([
            'success' => false,
            'message' => 'Метод не поддерживается'
        ]);
        break;
}

/**
 * Получение всех уведомлений текущего пользователя
 */
function getAllNotifications() {
    try {
        $database = new Database();
        $conn = $database->getConnection();
        
        $userId = $_SESSION['user_id'];
        $limit = min(100, max(1, intval($_GET['limit'] ?? 50)));
        $offset = max(0, intval($_GET['offset'] ?? 0));
        
        $query = "SELECT * FROM notifications 
                  WHERE user_id = :user_id 
                  ORDER BY created_at DESC 
                  LIMIT :limit OFFSET :offset";
        
        $stmt = $conn->prepare($query);
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Получаем общее количество
        $countQuery = "SELECT COUNT(*) FROM notifications WHERE user_id = :user_id";
        $countStmt = $conn->prepare($countQuery);
        $countStmt->execute([':user_id' => $userId]);
        $total = $countStmt->fetchColumn();
        
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'data' => $notifications,
            'total' => $total,
            'unread' => count(array_filter($notifications, function($n) { return !$n['is_read']; }))
        ]);
        
    } catch (Exception $e) {
        error_log("Notifications fetch error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Ошибка получения уведомлений'
        ]);
    }
}

/**
 * Получение непрочитанных уведомлений
 */
function getUnreadNotifications() {
    try {
        $database = new Database();
        $conn = $database->getConnection();
        
        $userId = $_SESSION['user_id'];
        
        $query = "SELECT * FROM notifications 
                  WHERE user_id = :user_id AND is_read = 0 
                  ORDER BY created_at DESC 
                  LIMIT 50";
        
        $stmt = $conn->prepare($query);
        $stmt->execute([':user_id' => $userId]);
        
        $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'data' => $notifications,
            'count' => count($notifications)
        ]);
        
    } catch (Exception $e) {
        error_log("Unread notifications fetch error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Ошибка получения непрочитанных уведомлений'
        ]);
    }
}

/**
 * Получение количества непрочитанных уведомлений
 */
function getUnreadCount() {
    try {
        $database = new Database();
        $conn = $database->getConnection();
        
        $userId = $_SESSION['user_id'];
        
        $query = "SELECT COUNT(*) FROM notifications 
                  WHERE user_id = :user_id AND is_read = 0";
        
        $stmt = $conn->prepare($query);
        $stmt->execute([':user_id' => $userId]);
        
        $count = $stmt->fetchColumn();
        
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'count' => $count
        ]);
        
    } catch (Exception $e) {
        error_log("Unread count error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Ошибка получения количества уведомлений'
        ]);
    }
}

/**
 * Создание нового уведомления
 */
function createNotification() {
    $data = json_decode(file_get_contents("php://input"), true);
    
    if (empty($data['user_id']) || empty($data['message'])) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Не указан пользователь или сообщение'
        ]);
        return;
    }
    
    try {
        $database = new Database();
        $conn = $database->getConnection();
        
        $query = "INSERT INTO notifications (user_id, type, title, message, related_type, related_id) 
                  VALUES (:user_id, :type, :title, :message, :related_type, :related_id)";
        
        $stmt = $conn->prepare($query);
        $stmt->execute([
            ':user_id' => $data['user_id'],
            ':type' => $data['type'] ?? 'info',
            ':title' => $data['title'] ?? 'Уведомление',
            ':message' => $data['message'],
            ':related_type' => $data['related_type'] ?? null,
            ':related_id' => $data['related_id'] ?? null
        ]);
        
        http_response_code(201);
        echo json_encode([
            'success' => true,
            'message' => 'Уведомление создано',
            'id' => $conn->lastInsertId()
        ]);
        
    } catch (Exception $e) {
        error_log("Notification creation error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Ошибка создания уведомления'
        ]);
    }
}

/**
 * Пометка уведомления как прочитанного
 */
function markAsRead() {
    $data = json_decode(file_get_contents("php://input"), true);
    
    if (empty($data['id'])) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Не указан ID уведомления'
        ]);
        return;
    }
    
    try {
        $database = new Database();
        $conn = $database->getConnection();
        
        $query = "UPDATE notifications SET is_read = 1 
                  WHERE id = :id AND user_id = :user_id";
        
        $stmt = $conn->prepare($query);
        $stmt->execute([
            ':id' => $data['id'],
            ':user_id' => $_SESSION['user_id']
        ]);
        
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => 'Уведомление отмечено как прочитанное'
        ]);
        
    } catch (Exception $e) {
        error_log("Mark as read error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Ошибка обновления уведомления'
        ]);
    }
}

/**
 * Пометка всех уведомлений как прочитанных
 */
function markAllAsRead() {
    try {
        $database = new Database();
        $conn = $database->getConnection();
        
        $query = "UPDATE notifications SET is_read = 1 
                  WHERE user_id = :user_id AND is_read = 0";
        
        $stmt = $conn->prepare($query);
        $stmt->execute([':user_id' => $_SESSION['user_id']]);
        
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => 'Все уведомления отмечены как прочитанные'
        ]);
        
    } catch (Exception $e) {
        error_log("Mark all as read error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Ошибка обновления уведомлений'
        ]);
    }
}

/**
 * Удаление уведомления
 */
function deleteNotification() {
    $data = json_decode(file_get_contents("php://input"), true);
    
    if (empty($data['id'])) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Не указан ID уведомления'
        ]);
        return;
    }
    
    try {
        $database = new Database();
        $conn = $database->getConnection();
        
        $query = "DELETE FROM notifications 
                  WHERE id = :id AND user_id = :user_id";
        
        $stmt = $conn->prepare($query);
        $stmt->execute([
            ':id' => $data['id'],
            ':user_id' => $_SESSION['user_id']
        ]);
        
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => 'Уведомление удалено'
        ]);
        
    } catch (Exception $e) {
        error_log("Notification delete error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Ошибка удаления уведомления'
        ]);
    }
}
?>
