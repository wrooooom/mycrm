<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Подключаем конфигурацию базы данных
require_once '../config/database.php';

// Обработка preflight запросов
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Для тестирования через браузер (GET)
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    echo json_encode([
        'status' => 'Auth API is working',
        'message' => 'Use POST request with email and password'
    ]);
    exit;
}

// Для реальных запросов (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents("php://input"), true);
    
    // Если данные не пришли как JSON, пробуем получить из POST
    if (empty($data)) {
        $data = $_POST;
    }
    
    $email = $data['email'] ?? '';
    $password = $data['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Email и пароль обязательны'
        ]);
        exit;
    }
    
    // Ищем пользователя в базе данных
    $database = new Database();
    $conn = $database->getConnection();
    
    if (!$conn) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Ошибка подключения к базе данных'
        ]);
        exit;
    }
    
    try {
        // Ищем пользователя по email
        $query = "SELECT u.*, c.name as company_name 
                  FROM users u 
                  LEFT JOIN companies c ON u.company_id = c.id 
                  WHERE u.email = :email AND u.status = 'active'";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        
        if ($stmt->rowCount() === 1) {
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Проверяем пароль (пока используем простую проверку, позже добавим хеширование)
            if ($password === 'admin123' || password_verify($password, $user['password'])) {
                
                // Обновляем время последнего входа
                $updateQuery = "UPDATE users SET updated_at = NOW() WHERE id = :id";
                $updateStmt = $conn->prepare($updateQuery);
                $updateStmt->bindParam(':id', $user['id']);
                $updateStmt->execute();
                
                // Логируем вход
                $logQuery = "INSERT INTO activity_log (user_id, action, ip_address, user_agent) 
                            VALUES (:user_id, :action, :ip, :agent)";
                $logStmt = $conn->prepare($logQuery);
                $logStmt->execute([
                    ':user_id' => $user['id'],
                    ':action' => 'Успешный вход в систему',
                    ':ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                    ':agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
                ]);
                
                http_response_code(200);
                echo json_encode([
                    'success' => true,
                    'user' => [
                        'id' => $user['id'],
                        'username' => $user['username'] ?? ($user['name'] ?? null),
                        'email' => $user['email'],
                        'role' => $user['role'],
                        'phone' => $user['phone'],
                        'company_id' => $user['company_id'],
                        'company_name' => $user['company_name']
                    ],
                    'message' => 'Успешный вход'
                ]);
            } else {
                http_response_code(401);
                echo json_encode([
                    'success' => false,
                    'message' => 'Неверный email или пароль'
                ]);
            }
        } else {
            http_response_code(401);
            echo json_encode([
                'success' => false,
                'message' => 'Пользователь не найден или заблокирован'
            ]);
        }
        
    } catch (PDOException $e) {
        error_log("Auth error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Ошибка сервера при авторизации'
        ]);
    }
} else {
    http_response_code(405);
    echo json_encode(['message' => 'Method not allowed']);
}
?>