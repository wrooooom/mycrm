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
        createVehicle();
        break;
    case 'update':
        updateVehicle();
        break;
    case 'delete':
        deleteVehicle();
        break;
    case 'getAll':
    default:
        getAllVehicles();
        break;
}

// Получение всех автомобилей
function getAllVehicles() {
    $status = $_GET['status'] ?? null;
    $class = $_GET['class'] ?? null;
    $brand = $_GET['brand'] ?? null;
    
    try {
        $database = new Database();
        $conn = $database->getConnection();
        
        $query = "SELECT v.*, 
                         c.name as company_name,
                         d.first_name as driver_first_name,
                         d.last_name as driver_last_name,
                         d.middle_name as driver_middle_name
                  FROM vehicles v
                  LEFT JOIN companies c ON v.company_id = c.id
                  LEFT JOIN driver_vehicles dv ON v.id = dv.vehicle_id AND dv.is_active = true
                  LEFT JOIN drivers d ON dv.driver_id = d.id
                  WHERE 1=1";
        
        $params = [];
        
        if ($status) {
            $query .= " AND v.status = :status";
            $params[':status'] = $status;
        }
        
        if ($class) {
            $query .= " AND v.class = :class";
            $params[':class'] = $class;
        }
        
        if ($brand) {
            $query .= " AND v.brand LIKE :brand";
            $params[':brand'] = "%{$brand}%";
        }
        
        $query .= " ORDER BY v.created_at DESC";
        
        $stmt = $conn->prepare($query);
        $stmt->execute($params);
        
        $vehicles = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'data' => [
                'vehicles' => $vehicles,
                'pagination' => [
                    'page' => 1,
                    'limit' => 50,
                    'total' => count($vehicles),
                    'pages' => 1
                ]
            ]
        ]);
        
    } catch (PDOException $e) {
        error_log("Vehicles fetch error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Ошибка получения автомобилей'
        ]);
    }
}

// Создание автомобиля
function createVehicle() {
    $data = json_decode(file_get_contents("php://input"), true);
    
    if (empty($data)) {
        $data = $_POST;
    }
    
    // Валидация обязательных полей
    $required = ['brand', 'model', 'class', 'license_plate'];
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
        
        // Создаем автомобиль
        $query = "INSERT INTO vehicles (
                    company_id, brand, model, class, license_plate, year,
                    passenger_seats, status, mileage, salon_type, salon_color, body_color
                  ) VALUES (
                    :company_id, :brand, :model, :class, :license_plate, :year,
                    :passenger_seats, :status, :mileage, :salon_type, :salon_color, :body_color
                  )";
        
        $stmt = $conn->prepare($query);
        $stmt->execute([
            ':company_id' => $data['company_id'] ?? 1,
            ':brand' => $data['brand'],
            ':model' => $data['model'],
            ':class' => $data['class'],
            ':license_plate' => $data['license_plate'],
            ':year' => $data['year'] ?? null,
            ':passenger_seats' => $data['passenger_seats'] ?? 4,
            ':status' => $data['status'] ?? 'working',
            ':mileage' => $data['mileage'] ?? 0,
            ':salon_type' => $data['salon_type'] ?? null,
            ':salon_color' => $data['salon_color'] ?? null,
            ':body_color' => $data['body_color'] ?? null
        ]);
        
        $vehicleId = $conn->lastInsertId();
        
        // Обработка загрузки фото
        if (!empty($data['photo_base64'])) {
            $photoPath = saveVehiclePhoto($vehicleId, $data['photo_base64']);
            if ($photoPath) {
                $updateQuery = "UPDATE vehicles SET photo_url = :photo_url WHERE id = :id";
                $updateStmt = $conn->prepare($updateQuery);
                $updateStmt->execute([
                    ':photo_url' => $photoPath,
                    ':id' => $vehicleId
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
            ':action' => "Создан автомобиль {$data['brand']} {$data['model']}",
            ':ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ]);
        
        http_response_code(201);
        echo json_encode([
            'success' => true,
            'message' => 'Автомобиль успешно создан',
            'data' => [
                'vehicle_id' => $vehicleId
            ]
        ]);
        
    } catch (PDOException $e) {
        if (isset($conn)) {
            $conn->rollBack();
        }
        error_log("Vehicle creation error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Ошибка создания автомобиля: ' . $e->getMessage()
        ]);
    }
}

// Обновление автомобиля
function updateVehicle() {
    $data = json_decode(file_get_contents("php://input"), true);
    
    if (empty($data)) {
        $data = $_POST;
    }
    
    $vehicleId = $data['id'] ?? null;
    if (!$vehicleId) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'ID автомобиля не указан'
        ]);
        return;
    }
    
    try {
        $database = new Database();
        $conn = $database->getConnection();
        
        $query = "UPDATE vehicles SET 
                    brand = :brand,
                    model = :model,
                    class = :class,
                    license_plate = :license_plate,
                    year = :year,
                    passenger_seats = :passenger_seats,
                    status = :status,
                    mileage = :mileage,
                    salon_type = :salon_type,
                    salon_color = :salon_color,
                    body_color = :body_color,
                    updated_at = NOW()
                  WHERE id = :id";
        
        $stmt = $conn->prepare($query);
        $stmt->execute([
            ':brand' => $data['brand'],
            ':model' => $data['model'],
            ':class' => $data['class'],
            ':license_plate' => $data['license_plate'],
            ':year' => $data['year'] ?? null,
            ':passenger_seats' => $data['passenger_seats'] ?? 4,
            ':status' => $data['status'] ?? 'working',
            ':mileage' => $data['mileage'] ?? 0,
            ':salon_type' => $data['salon_type'] ?? null,
            ':salon_color' => $data['salon_color'] ?? null,
            ':body_color' => $data['body_color'] ?? null,
            ':id' => $vehicleId
        ]);
        
        // Обработка загрузки фото
        if (!empty($data['photo_base64'])) {
            $photoPath = saveVehiclePhoto($vehicleId, $data['photo_base64']);
            if ($photoPath) {
                $updateQuery = "UPDATE vehicles SET photo_url = :photo_url WHERE id = :id";
                $updateStmt = $conn->prepare($updateQuery);
                $updateStmt->execute([
                    ':photo_url' => $photoPath,
                    ':id' => $vehicleId
                ]);
            }
        }
        
        // Логируем действие
        $logQuery = "INSERT INTO activity_log (user_id, action, ip_address) 
                    VALUES (:user_id, :action, :ip)";
        $logStmt = $conn->prepare($logQuery);
        $logStmt->execute([
            ':user_id' => $data['updated_by'] ?? 1,
            ':action' => "Обновлен автомобиль {$data['brand']} {$data['model']}",
            ':ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ]);
        
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => 'Автомобиль успешно обновлен'
        ]);
        
    } catch (PDOException $e) {
        error_log("Vehicle update error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Ошибка обновления автомобиля: ' . $e->getMessage()
        ]);
    }
}

// Удаление автомобиля
function deleteVehicle() {
    $data = json_decode(file_get_contents("php://input"), true);
    
    if (empty($data)) {
        $data = $_POST;
    }
    
    $vehicleId = $data['id'] ?? null;
    if (!$vehicleId) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'ID автомобиля не указан'
        ]);
        return;
    }
    
    try {
        $database = new Database();
        $conn = $database->getConnection();
        
        // Получаем данные автомобиля для лога
        $selectQuery = "SELECT brand, model FROM vehicles WHERE id = :id";
        $selectStmt = $conn->prepare($selectQuery);
        $selectStmt->execute([':id' => $vehicleId]);
        $vehicle = $selectStmt->fetch();
        
        if (!$vehicle) {
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'message' => 'Автомобиль не найден'
            ]);
            return;
        }
        
        // Удаляем автомобиль
        $query = "DELETE FROM vehicles WHERE id = :id";
        $stmt = $conn->prepare($query);
        $stmt->execute([':id' => $vehicleId]);
        
        // Логируем действие
        $logQuery = "INSERT INTO activity_log (user_id, action, ip_address) 
                    VALUES (:user_id, :action, :ip)";
        $logStmt = $conn->prepare($logQuery);
        $logStmt->execute([
            ':user_id' => $data['deleted_by'] ?? 1,
            ':action' => "Удален автомобиль {$vehicle['brand']} {$vehicle['model']}",
            ':ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ]);
        
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => 'Автомобиль успешно удален'
        ]);
        
    } catch (PDOException $e) {
        error_log("Vehicle delete error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Ошибка удаления автомобиля: ' . $e->getMessage()
        ]);
    }
}

// Сохранение фото автомобиля
function saveVehiclePhoto($vehicleId, $base64Image) {
    try {
        $uploadDir = '../uploads/vehicles/';
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
        
        $filename = "vehicle_{$vehicleId}_" . time() . ".{$type}";
        $filepath = $uploadDir . $filename;
        
        if (file_put_contents($filepath, $data)) {
            return "uploads/vehicles/{$filename}";
        }
        
        return false;
        
    } catch (Exception $e) {
        error_log("Vehicle photo save error: " . $e->getMessage());
        return false;
    }
}
?>