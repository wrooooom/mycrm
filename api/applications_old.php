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

// Получение action из GET или POST
$action = $_GET['action'] ?? ($_POST['action'] ?? '');

// Обработка разных действий
switch ($action) {
    case 'getAll':
        getAllApplications();
        break;
    case 'create':
        createApplication();
        break;
    case 'assignDriver':
        assignDriver();
        break;
    case 'assignVehicle':
        assignVehicle();
        break;
    default:
        // По умолчанию возвращаем все заявки
        getAllApplications();
        break;
}

// Получение всех заявок
function getAllApplications() {
    $user_id = $_GET['user_id'] ?? null;
    $status = $_GET['status'] ?? null;
    $date = $_GET['date'] ?? null;
    
    try {
        $database = new Database();
        $conn = $database->getConnection();
        
        $query = "SELECT a.*, 
                         d.first_name as driver_first_name, 
                         d.last_name as driver_last_name,
                         v.brand as vehicle_brand, 
                         v.model as vehicle_model,
                         v.license_plate as vehicle_plate,
                         c.name as customer_company_name,
                         ec.name as executor_company_name,
                         u.username as creator_name
                  FROM applications a
                  LEFT JOIN drivers d ON a.driver_id = d.id
                  LEFT JOIN vehicles v ON a.vehicle_id = v.id
                  LEFT JOIN companies c ON a.customer_company_id = c.id
                  LEFT JOIN companies ec ON a.executor_company_id = ec.id
                  LEFT JOIN users u ON a.created_by = u.id
                  WHERE 1=1";
        
        $params = [];
        
        if ($status) {
            $query .= " AND a.status = :status";
            $params[':status'] = $status;
        }
        
        if ($date) {
            $query .= " AND DATE(a.trip_date) = :date";
            $params[':date'] = $date;
        }
        
        $query .= " ORDER BY a.trip_date DESC";
        
        $stmt = $conn->prepare($query);
        $stmt->execute($params);
        
        $applications = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Получаем маршруты для каждой заявки
        foreach ($applications as &$app) {
            $routeQuery = "SELECT * FROM application_routes WHERE application_id = :app_id ORDER BY point_order";
            $routeStmt = $conn->prepare($routeQuery);
            $routeStmt->execute([':app_id' => $app['id']]);
            $app['routes'] = $routeStmt->fetchAll(PDO::FETCH_ASSOC);
            
            $passengerQuery = "SELECT * FROM application_passengers WHERE application_id = :app_id";
            $passengerStmt = $conn->prepare($passengerQuery);
            $passengerStmt->execute([':app_id' => $app['id']]);
            $app['passengers'] = $passengerStmt->fetchAll(PDO::FETCH_ASSOC);
        }
        
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'data' => $applications
        ]);
        
    } catch (PDOException $e) {
        error_log("Applications fetch error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Ошибка получения заявок'
        ]);
    }
}

// Создание новой заявки
function createApplication() {
    $data = json_decode(file_get_contents("php://input"), true);
    
    if (empty($data)) {
        $data = $_POST;
    }
    
    try {
        $database = new Database();
        $conn = $database->getConnection();
        $conn->beginTransaction();
        
        // Генерируем номер заявки
        $appNumber = 'A' . date('Ymd') . sprintf('%04d', rand(1000, 9999));
        
        // Форматируем дату и время
        $tripDateTime = $data['trip_date'] ?? '';
        if ($tripDateTime) {
            $tripDateTime = date('Y-m-d H:i:s', strtotime($tripDateTime));
        }
        
        // Создаем основную заявку
        $query = "INSERT INTO applications (
                    application_number, status, city, country, trip_date, service_type, tariff,
                    customer_name, customer_phone, order_amount, created_by,
                    flight_number, manager_comment, notes
                  ) VALUES (
                    :app_number, :status, :city, :country, :trip_date, :service_type, :tariff,
                    :customer_name, :customer_phone, :order_amount, :created_by,
                    :flight_number, :manager_comment, :notes
                  )";
        
        $stmt = $conn->prepare($query);
        $stmt->execute([
            ':app_number' => $appNumber,
            ':status' => $data['status'] ?? 'new',
            ':city' => $data['city'] ?? 'Москва',
            ':country' => $data['country'] ?? 'ru',
            ':trip_date' => $tripDateTime,
            ':service_type' => $data['service_type'] ?? 'transfer',
            ':tariff' => $data['vehicle_class'] ?? 'comfort',
            ':customer_name' => $data['customer_name'] ?? '',
            ':customer_phone' => $data['customer_phone'] ?? '',
            ':order_amount' => $data['order_amount'] ?? 0,
            ':created_by' => $data['created_by'] ?? 1,
            ':flight_number' => $data['flight_number'] ?? null,
            ':manager_comment' => $data['driver_comment'] ?? $data['manager_comment'] ?? null,
            ':notes' => $data['notes'] ?? null
        ]);
        
        $applicationId = $conn->lastInsertId();
        
        // Сохраняем маршруты
        if (!empty($data['routes'])) {
            // Если routes - массив адресов
            if (is_array($data['routes'])) {
                foreach ($data['routes'] as $index => $address) {
                    if (!empty(trim($address))) {
                        $routeQuery = "INSERT INTO application_routes (application_id, point_order, city, country, address) 
                                      VALUES (:app_id, :order, :city, :country, :address)";
                        $routeStmt = $conn->prepare($routeQuery);
                        $routeStmt->execute([
                            ':app_id' => $applicationId,
                            ':order' => $index,
                            ':city' => $data['city'] ?? 'Москва',
                            ':country' => $data['country'] ?? 'ru',
                            ':address' => trim($address)
                        ]);
                    }
                }
            }
        } else {
            // Сохраняем стандартные точки маршрута
            $routeFrom = $data['route_from'] ?? ($data['routes'][0] ?? '');
            $routeTo = $data['route_to'] ?? ($data['routes'][1] ?? '');
            
            if (!empty($routeFrom)) {
                $routeQuery = "INSERT INTO application_routes (application_id, point_order, city, country, address) 
                              VALUES (:app_id, 0, :city, :country, :address)";
                $routeStmt = $conn->prepare($routeQuery);
                $routeStmt->execute([
                    ':app_id' => $applicationId,
                    ':city' => $data['city'] ?? 'Москва',
                    ':country' => $data['country'] ?? 'ru',
                    ':address' => trim($routeFrom)
                ]);
            }
            
            if (!empty($routeTo)) {
                $routeQuery = "INSERT INTO application_routes (application_id, point_order, city, country, address) 
                              VALUES (:app_id, 1, :city, :country, :address)";
                $routeStmt = $conn->prepare($routeQuery);
                $routeStmt->execute([
                    ':app_id' => $applicationId,
                    ':city' => $data['city'] ?? 'Москва',
                    ':country' => $data['country'] ?? 'ru',
                    ':address' => trim($routeTo)
                ]);
            }
        }
        
        // Сохраняем пассажиров
        if (!empty($data['passengers'])) {
            foreach ($data['passengers'] as $passenger) {
                if (!empty(trim($passenger['name']))) {
                    $passengerQuery = "INSERT INTO application_passengers (application_id, name, phone) 
                                      VALUES (:app_id, :name, :phone)";
                    $passengerStmt = $conn->prepare($passengerQuery);
                    $passengerStmt->execute([
                        ':app_id' => $applicationId,
                        ':name' => trim($passenger['name']),
                        ':phone' => $passenger['phone'] ?? null
                    ]);
                }
            }
        } else if (!empty($data['customer_name'])) {
            // Добавляем заказчика как пассажира
            $passengerQuery = "INSERT INTO application_passengers (application_id, name, phone) 
                              VALUES (:app_id, :name, :phone)";
            $passengerStmt = $conn->prepare($passengerQuery);
            $passengerStmt->execute([
                ':app_id' => $applicationId,
                ':name' => $data['customer_name'],
                ':phone' => $data['customer_phone'] ?? null
            ]);
        }
        
        $conn->commit();
        
        // Логируем действие
        $logQuery = "INSERT INTO activity_log (user_id, action, ip_address) 
                    VALUES (:user_id, :action, :ip)";
        $logStmt = $conn->prepare($logQuery);
        $logStmt->execute([
            ':user_id' => $data['created_by'] ?? 1,
            ':action' => "Создана заявка {$appNumber}",
            ':ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ]);
        
        http_response_code(201);
        echo json_encode([
            'success' => true,
            'message' => 'Заявка успешно создана',
            'application_id' => $applicationId,
            'application_number' => $appNumber
        ]);
        
    } catch (PDOException $e) {
        if (isset($conn)) {
            $conn->rollBack();
        }
        error_log("Application creation error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Ошибка создания заявки: ' . $e->getMessage()
        ]);
    }
}

// Назначение водителя
function assignDriver() {
    $data = json_decode(file_get_contents("php://input"), true);
    
    if (empty($data)) {
        $data = $_POST;
    }
    
    $applicationId = $data['application_id'] ?? null;
    $driverId = $data['driver_id'] ?? null;
    
    if (!$applicationId || !$driverId) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Не указаны ID заявки или водителя'
        ]);
        return;
    }
    
    try {
        $database = new Database();
        $conn = $database->getConnection();
        
        $query = "UPDATE applications SET driver_id = :driver_id, status = 'confirmed' WHERE id = :app_id";
        $stmt = $conn->prepare($query);
        $stmt->execute([
            ':driver_id' => $driverId,
            ':app_id' => $applicationId
        ]);
        
        // Логируем действие
        $logQuery = "INSERT INTO activity_log (user_id, action, ip_address) 
                    VALUES (:user_id, :action, :ip)";
        $logStmt = $conn->prepare($logQuery);
        $logStmt->execute([
            ':user_id' => 1, // Временно
            ':action' => "Назначен водитель #{$driverId} на заявку #{$applicationId}",
            ':ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ]);
        
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => 'Водитель успешно назначен'
        ]);
        
    } catch (PDOException $e) {
        error_log("Assign driver error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Ошибка назначения водителя: ' . $e->getMessage()
        ]);
    }
}

// Назначение автомобиля
function assignVehicle() {
    $data = json_decode(file_get_contents("php://input"), true);
    
    if (empty($data)) {
        $data = $_POST;
    }
    
    $applicationId = $data['application_id'] ?? null;
    $vehicleId = $data['vehicle_id'] ?? null;
    
    if (!$applicationId || !$vehicleId) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Не указаны ID заявки или автомобиля'
        ]);
        return;
    }
    
    try {
        $database = new Database();
        $conn = $database->getConnection();
        
        $query = "UPDATE applications SET vehicle_id = :vehicle_id WHERE id = :app_id";
        $stmt = $conn->prepare($query);
        $stmt->execute([
            ':vehicle_id' => $vehicleId,
            ':app_id' => $applicationId
        ]);
        
        // Логируем действие
        $logQuery = "INSERT INTO activity_log (user_id, action, ip_address) 
                    VALUES (:user_id, :action, :ip)";
        $logStmt = $conn->prepare($logQuery);
        $logStmt->execute([
            ':user_id' => 1, // Временно
            ':action' => "Назначен автомобиль #{$vehicleId} на заявку #{$applicationId}",
            ':ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ]);
        
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => 'Автомобиль успешно назначен'
        ]);
        
    } catch (PDOException $e) {
        error_log("Assign vehicle error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Ошибка назначения автомобиля: ' . $e->getMessage()
        ]);
    }
}
?>