<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

include_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

$database = new Database();
$db = $database->getConnection();

function sendResponse($success, $message = '', $data = null, $code = 200) {
    http_response_code($code);
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data
    ]);
    exit;
}

// GET - Получение списка пользователей
if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    $user = validateUser($db);
    if (!$user) {
        sendResponse(false, 'Не авторизован', null, 401);
    }
    
    try {
        $query = "SELECT u.*, c.name as company_name 
                  FROM users u 
                  LEFT JOIN companies c ON u.company_id = c.id 
                  ORDER BY u.created_at DESC";
        $stmt = $db->prepare($query);
        $stmt->execute();
        
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Убираем пароли из ответа
        foreach ($users as &$user) {
            unset($user['password']);
        }
        
        sendResponse(true, 'Пользователи получены', $users);
        
    } catch (Exception $e) {
        sendResponse(false, 'Ошибка получения пользователей: ' . $e->getMessage(), null, 500);
    }
}

// POST - Создание нового пользователя
elseif ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user = validateUser($db);
    if (!$user || $user['role'] != 'admin') {
        sendResponse(false, 'Доступ запрещен', null, 403);
    }
    
    $data = json_decode(file_get_contents("php://input"), true);
    
    try {
        // Проверяем существование email
        $checkQuery = "SELECT id FROM users WHERE email = :email";
        $checkStmt = $db->prepare($checkQuery);
        $checkStmt->bindValue(':email', $data['email']);
        $checkStmt->execute();
        
        if ($checkStmt->rowCount() > 0) {
            sendResponse(false, 'Пользователь с таким email уже существует', null, 400);
        }
        
        $query = "INSERT INTO users (name, email, password, phone, role, company_id, status) 
                  VALUES (:name, :email, :password, :phone, :role, :company_id, 'active')";
        
        $stmt = $db->prepare($query);
        $stmt->bindValue(':name', $data['name']);
        $stmt->bindValue(':email', $data['email']);
        $stmt->bindValue(':password', password_hash($data['password'], PASSWORD_DEFAULT));
        $stmt->bindValue(':phone', $data['phone'] ?? null);
        $stmt->bindValue(':role', $data['role']);
        $stmt->bindValue(':company_id', $data['company_id'] ?? null);
        
        $stmt->execute();
        $userId = $db->lastInsertId();
        
        // Записываем в лог
        $logQuery = "INSERT INTO activity_log (user_id, action, ip_address, user_agent) 
                    VALUES (:user_id, :action, :ip, :agent)";
        $logStmt = $db->prepare($logQuery);
        $action = "Добавлен новый пользователь: {$data['name']}";
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $agent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
        $logStmt->bindValue(':user_id', $user['id']);
        $logStmt->bindValue(':action', $action);
        $logStmt->bindValue(':ip', $ip);
        $logStmt->bindValue(':agent', $agent);
        $logStmt->execute();
        
        sendResponse(true, 'Пользователь успешно создан', ['user_id' => $userId]);
        
    } catch (Exception $e) {
        sendResponse(false, 'Ошибка создания пользователя: ' . $e->getMessage(), null, 500);
    }
}

// PUT - Обновление пользователя
elseif ($_SERVER['REQUEST_METHOD'] == 'PUT') {
    $user = validateUser($db);
    if (!$user || $user['role'] != 'admin') {
        sendResponse(false, 'Доступ запрещен', null, 403);
    }
    
    $data = json_decode(file_get_contents("php://input"), true);
    $userId = isset($_GET['id']) ? intval($_GET['id']) : null;
    
    if (!$userId) {
        sendResponse(false, 'ID пользователя не указан', null, 400);
    }
    
    try {
        $query = "UPDATE users SET 
                    name = :name,
                    email = :email,
                    phone = :phone,
                    role = :role,
                    company_id = :company_id,
                    status = :status,
                    updated_at = NOW()
                WHERE id = :id";
        
        $stmt = $db->prepare($query);
        $stmt->bindValue(':name', $data['name']);
        $stmt->bindValue(':email', $data['email']);
        $stmt->bindValue(':phone', $data['phone'] ?? null);
        $stmt->bindValue(':role', $data['role']);
        $stmt->bindValue(':company_id', $data['company_id'] ?? null);
        $stmt->bindValue(':status', $data['status']);
        $stmt->bindValue(':id', $userId);
        
        $stmt->execute();
        
        // Обновляем пароль если указан
        if (isset($data['password']) && $data['password']) {
            $passwordQuery = "UPDATE users SET password = :password WHERE id = :id";
            $passwordStmt = $db->prepare($passwordQuery);
            $passwordStmt->bindValue(':password', password_hash($data['password'], PASSWORD_DEFAULT));
            $passwordStmt->bindValue(':id', $userId);
            $passwordStmt->execute();
        }
        
        // Записываем в лог
        $logQuery = "INSERT INTO activity_log (user_id, action, ip_address, user_agent) 
                    VALUES (:user_id, :action, :ip, :agent)";
        $logStmt = $db->prepare($logQuery);
        $action = "Обновлен пользователь ID: {$userId}";
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $agent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
        $logStmt->bindValue(':user_id', $user['id']);
        $logStmt->bindValue(':action', $action);
        $logStmt->bindValue(':ip', $ip);
        $logStmt->bindValue(':agent', $agent);
        $logStmt->execute();
        
        sendResponse(true, 'Пользователь успешно обновлен');
        
    } catch (Exception $e) {
        sendResponse(false, 'Ошибка обновления пользователя: ' . $e->getMessage(), null, 500);
    }
}
?>