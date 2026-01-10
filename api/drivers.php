<?php
// ВРЕМЕННАЯ ОТЛАДКА - УБРАТЬ ПОСЛЕ ИСПРАВЛЕНИЯ
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

// Остальной код файла...
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
// ...
?>
<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$action = $_GET['action'] ?? ($_POST['action'] ?? '');

switch ($action) {
    case 'create':
        createDriver();
        break;
    case 'update':
        updateDriver();
        break;
    case 'delete':
        deleteDriver();
        break;
    case 'getAll':
    default:
        getAllDrivers();
        break;
}

// Получение всех водителей
function getAllDrivers() {
    $status = $_GET['status'] ?? null;
    $city = $_GET['city'] ?? null;
    
    try {
        $database = new Database();
        $conn = $database->getConnection();
        
        $query = "SELECT d.*, 
                         c.name as company_name,
                         v.brand as vehicle_brand,
                         v.model as vehicle_model,
                         v.license_plate as vehicle_plate
                  FROM drivers d
                  LEFT JOIN companies c ON d.company_id = c.id
                  LEFT JOIN driver_vehicles dv ON d.id = dv.driver_id AND dv.is_active = true
                  LEFT JOIN vehicles v ON dv.vehicle_id = v.id
                  WHERE 1=1";
        
        $params = [];
        
        if ($status) {
            $query .= " AND d.status = :status";
            $params[':status'] = $status;
        }
        
        if ($city) {
            $query .= " AND d.city = :city";
            $params[':city'] = $city;
        }
        
        $query .= " ORDER BY d.created_at DESC";
        
        $stmt = $conn->prepare($query);
        $stmt->execute($params);
        
        $drivers = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'data' => [
                'drivers' => $drivers,
                'pagination' => [
                    'page' => 1,
                    'limit' => 50,
                    'total' => count($drivers),
                    'pages' => 1
                ]
            ]
        ]);
        
    } catch (PDOException $e) {
        error_log("Drivers fetch error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Ошибка получения водителей'
        ]);
    }
}

// Создание водителя
function createDriver() {
    $data = json_decode(file_get_contents("php://input"), true);
    
    if (empty($data)) {
        $data = $_POST;
    }
    
    // Валидация обязательных полей
    $required = ['first_name', 'last_name', 'phone'];
    foreach ($required as $field) {
        if (empty($data[$field])) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => "Обязательное поле {$field} не заполнено"
            ]);
            return;
        }
    }
    
    try {
        $database = new Database();
        $conn = $database->getConnection();
        $conn->beginTransaction();
        
        // Создаем водителя
        $query = "INSERT INTO drivers (
                    company_id, first_name, last_name, middle_name, phone, phone_secondary,
                    email, city, country, district, comments, passport_series_number,
                    passport_issued_by, passport_issue_date, passport_registration_address,
                    schedule, status
                  ) VALUES (
                    :company_id, :first_name, :last_name, :middle_name, :phone, :phone_secondary,
                    :email, :city, :country, :district, :comments, :passport_series_number,
                    :passport_issued_by, :passport_issue_date, :passport_registration_address,
                    :schedule, :status
                  )";
        
        $stmt = $conn->prepare($query);
        $stmt->execute([
            ':company_id' => $data['company_id'] ?? 1,
            ':first_name' => $data['first_name'],
            ':last_name' => $data['last_name'],
            ':middle_name' => $data['middle_name'] ?? null,
            ':phone' => $data['phone'],
            ':phone_secondary' => $data['phone_secondary'] ?? null,
            ':email' => $data['email'] ?? null,
            ':city' => $data['city'] ?? 'Москва',
            ':country' => $data['country'] ?? 'ru',
            ':district' => $data['district'] ?? null,
            ':comments' => $data['comments'] ?? null,
            ':passport_series_number' => $data['passport_series_number'] ?? null,
            ':passport_issued_by' => $data['passport_issued_by'] ?? null,
            ':passport_issue_date' => $data['passport_issue_date'] ?? null,
            ':passport_registration_address' => $data['passport_registration_address'] ?? null,
            ':schedule' => $data['schedule'] ?? 'day',
            ':status' => $data['status'] ?? 'work'
        ]);
        
        $driverId = $conn->lastInsertId();
        
        // Обработка загрузки фото
        if (!empty($data['photo_base64'])) {
            $photoPath = saveDriverPhoto($driverId, $data['photo_base64']);
            if ($photoPath) {
                $updateQuery = "UPDATE drivers SET photo_url = :photo_url WHERE id = :id";
                $updateStmt = $conn->prepare($updateQuery);
                $updateStmt->execute([
                    ':photo_url' => $photoPath,
                    ':id' => $driverId
                ]);
            }
        }
        
        $conn->commit();
        
        // Логируем действие
        $logQuery = "INSERT INTO activity_log (user_id, action, ip_address) 
                    VALUES (:user_id, :action, :ip)";
        $logStmt = $conn->prepare($logQuery);
        $logStmt->execute([
            ':user_id' => $data['created_by'] ?? 1,
            ':action' => "Создан водитель {$data['first_name']} {$data['last_name']}",
            ':ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ]);
        
        http_response_code(201);
        echo json_encode([
            'success' => true,
            'message' => 'Водитель успешно создан',
            'data' => [
                'driver_id' => $driverId
            ]
        ]);
        
    } catch (PDOException $e) {
        if (isset($conn)) {
            $conn->rollBack();
        }
        error_log("Driver creation error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Ошибка создания водителя: ' . $e->getMessage()
        ]);
    }
}

// Обновление водителя
function updateDriver() {
    $data = json_decode(file_get_contents("php://input"), true);
    
    if (empty($data)) {
        $data = $_POST;
    }
    
    $driverId = $data['id'] ?? null;
    if (!$driverId) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'ID водителя не указан'
        ]);
        return;
    }
    
    try {
        $database = new Database();
        $conn = $database->getConnection();
        
        $query = "UPDATE drivers SET 
                    first_name = :first_name,
                    last_name = :last_name,
                    middle_name = :middle_name,
                    phone = :phone,
                    phone_secondary = :phone_secondary,
                    email = :email,
                    city = :city,
                    country = :country,
                    district = :district,
                    comments = :comments,
                    passport_series_number = :passport_series_number,
                    passport_issued_by = :passport_issued_by,
                    passport_issue_date = :passport_issue_date,
                    passport_registration_address = :passport_registration_address,
                    schedule = :schedule,
                    status = :status,
                    updated_at = NOW()
                  WHERE id = :id";
        
        $stmt = $conn->prepare($query);
        $stmt->execute([
            ':first_name' => $data['first_name'],
            ':last_name' => $data['last_name'],
            ':middle_name' => $data['middle_name'] ?? null,
            ':phone' => $data['phone'],
            ':phone_secondary' => $data['phone_secondary'] ?? null,
            ':email' => $data['email'] ?? null,
            ':city' => $data['city'] ?? 'Москва',
            ':country' => $data['country'] ?? 'ru',
            ':district' => $data['district'] ?? null,
            ':comments' => $data['comments'] ?? null,
            ':passport_series_number' => $data['passport_series_number'] ?? null,
            ':passport_issued_by' => $data['passport_issued_by'] ?? null,
            ':passport_issue_date' => $data['passport_issue_date'] ?? null,
            ':passport_registration_address' => $data['passport_registration_address'] ?? null,
            ':schedule' => $data['schedule'] ?? 'day',
            ':status' => $data['status'] ?? 'work',
            ':id' => $driverId
        ]);
        
        // Обработка загрузки фото
        if (!empty($data['photo_base64'])) {
            $photoPath = saveDriverPhoto($driverId, $data['photo_base64']);
            if ($photoPath) {
                $updateQuery = "UPDATE drivers SET photo_url = :photo_url WHERE id = :id";
                $updateStmt = $conn->prepare($updateQuery);
                $updateStmt->execute([
                    ':photo_url' => $photoPath,
                    ':id' => $driverId
                ]);
            }
        }
        
        // Логируем действие
        $logQuery = "INSERT INTO activity_log (user_id, action, ip_address) 
                    VALUES (:user_id, :action, :ip)";
        $logStmt = $conn->prepare($logQuery);
        $logStmt->execute([
            ':user_id' => $data['updated_by'] ?? 1,
            ':action' => "Обновлен водитель {$data['first_name']} {$data['last_name']}",
            ':ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ]);
        
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => 'Водитель успешно обновлен'
        ]);
        
    } catch (PDOException $e) {
        error_log("Driver update error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Ошибка обновления водителя: ' . $e->getMessage()
        ]);
    }
}

// Удаление водителя
function deleteDriver() {
    $data = json_decode(file_get_contents("php://input"), true);
    
    if (empty($data)) {
        $data = $_POST;
    }
    
    $driverId = $data['id'] ?? null;
    if (!$driverId) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'ID водителя не указан'
        ]);
        return;
    }
    
    try {
        $database = new Database();
        $conn = $database->getConnection();
        
        // Получаем данные водителя для лога
        $selectQuery = "SELECT first_name, last_name FROM drivers WHERE id = :id";
        $selectStmt = $conn->prepare($selectQuery);
        $selectStmt->execute([':id' => $driverId]);
        $driver = $selectStmt->fetch();
        
        if (!$driver) {
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'message' => 'Водитель не найден'
            ]);
            return;
        }
        
        // Удаляем водителя
        $query = "DELETE FROM drivers WHERE id = :id";
        $stmt = $conn->prepare($query);
        $stmt->execute([':id' => $driverId]);
        
        // Логируем действие
        $logQuery = "INSERT INTO activity_log (user_id, action, ip_address) 
                    VALUES (:user_id, :action, :ip)";
        $logStmt = $conn->prepare($logQuery);
        $logStmt->execute([
            ':user_id' => $data['deleted_by'] ?? 1,
            ':action' => "Удален водитель {$driver['first_name']} {$driver['last_name']}",
            ':ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ]);
        
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => 'Водитель успешно удален'
        ]);
        
    } catch (PDOException $e) {
        error_log("Driver delete error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Ошибка удаления водителя: ' . $e->getMessage()
        ]);
    }
}

// Сохранение фото водителя
function saveDriverPhoto($driverId, $base64Image) {
    try {
        $uploadDir = '../uploads/drivers/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        // Извлекаем данные из base64
        if (preg_match('/^data:image\/(\w+);base64,/', $base64Image, $type)) {
            $data = substr($base64Image, strpos($base64Image, ',') + 1);
            $type = strtolower($type[1]); // jpg, png, gif
            
            if (!in_array($type, ['jpg', 'jpeg', 'png', 'gif'])) {
                return false;
            }
            
            $data = base64_decode($data);
            if ($data === false) {
                return false;
            }
        } else {
            return false;
        }
        
        $filename = "driver_{$driverId}_" . time() . ".{$type}";
        $filepath = $uploadDir . $filename;
        
        if (file_put_contents($filepath, $data)) {
            return "uploads/drivers/{$filename}";
        }
        
        return false;
        
    } catch (Exception $e) {
        error_log("Photo save error: " . $e->getMessage());
        return false;
    }
}
?>