<?php
/**
 * API для управления заказами (applications)
 * Включает ACL, пагинацию, сортировку и валидацию
 */

// CORS headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once '../config/database.php';
require_once '../auth.php';
require_once '../includes/notifications.php';

// Проверяем авторизацию для API
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Требуется авторизация'
    ]);
    exit();
}

/**
 * Получение данных текущего пользователя с проверкой роли
 */
function getCurrentUserContext() {
    return [
        'user_id' => $_SESSION['user_id'],
        'role' => $_SESSION['user_role'],
        'company_id' => $_SESSION['company_id'] ?? null,
        'name' => $_SESSION['full_name'] ?? $_SESSION['username'] ?? 'Unknown'
    ];
}

/**
 * Фильтрация заказов по ролям (ACL)
 */
function getApplicationsByRole($userContext) {
    $role = $userContext['role'];
    $userId = $userContext['user_id'];
    $companyId = $userContext['company_id'];
    
    switch ($role) {
        case 'admin':
            return "1=1"; // Админ видит все
        case 'dispatcher':
        case 'manager':
            // Видит заказы своей компании или заказы без назначенной компании
            return "(a.executor_company_id = :company_id OR a.executor_company_id IS NULL)";
        case 'driver':
            // Видит только свои заказы
            return "a.driver_id = (SELECT id FROM drivers WHERE user_id = :user_id)";
        default:
            return "0=1"; // Ничего не видит
    }
}

/**
 * Получение всех заявок с фильтрацией, пагинацией и сортировкой
 */
function getAllApplications() {
    $userContext = getCurrentUserContext();
    
    // Параметры из запроса
    $page = max(1, intval($_GET['page'] ?? 1));
    $limit = min(100, max(1, intval($_GET['limit'] ?? 20)));
    $offset = ($page - 1) * $limit;
    
    $status = $_GET['status'] ?? null;
    $paymentStatus = $_GET['payment_status'] ?? null;
    $dateFrom = $_GET['date_from'] ?? null;
    $dateTo = $_GET['date_to'] ?? null;
    $sortBy = $_GET['sort_by'] ?? 'trip_date';
    $sortOrder = strtoupper($_GET['sort_order'] ?? 'DESC');
    $search = $_GET['search'] ?? null;
    
    // Валидация параметров сортировки
    $allowedSortFields = ['trip_date', 'status', 'order_amount', 'created_at', 'application_number'];
    if (!in_array($sortBy, $allowedSortFields)) {
        $sortBy = 'trip_date';
    }
    if (!in_array($sortOrder, ['ASC', 'DESC'])) {
        $sortOrder = 'DESC';
    }
    
    try {
        $database = new Database();
        $conn = $database->getConnection();
        
        // Базовый запрос с JOIN для получения связанных данных
        $baseQuery = "
            SELECT a.*, 
                   d.first_name as driver_first_name, 
                   d.last_name as driver_last_name,
                   d.phone as driver_phone,
                   v.brand as vehicle_brand, 
                   v.model as vehicle_model,
                   v.license_plate as vehicle_plate,
                   v.class as vehicle_class,
                   c.name as customer_company_name,
                   ec.name as executor_company_name,
                   u.name as creator_name
            FROM applications a
            LEFT JOIN drivers d ON a.driver_id = d.id
            LEFT JOIN vehicles v ON a.vehicle_id = v.id
            LEFT JOIN companies c ON a.customer_company_id = c.id
            LEFT JOIN companies ec ON a.executor_company_id = ec.id
            LEFT JOIN users u ON a.created_by = u.id
            WHERE {$userContext['role'] === 'admin' ? '1=1' : getApplicationsByRole($userContext)}
        ";
        
        $countQuery = "
            SELECT COUNT(*) 
            FROM applications a
            WHERE {$userContext['role'] === 'admin' ? '1=1' : getApplicationsByRole($userContext)}
        ";
        
        $params = [];
        
        // Добавляем параметры для ACL
        if ($userContext['role'] === 'dispatcher' || $userContext['role'] === 'manager') {
            $params[':company_id'] = $userContext['company_id'];
        } elseif ($userContext['role'] === 'driver') {
            $params[':user_id'] = $userContext['user_id'];
        }
        
        // Фильтры
        if ($status) {
            $statusFilter = " AND a.status = :status";
            $baseQuery .= $statusFilter;
            $countQuery .= $statusFilter;
            $params[':status'] = $status;
        }
        
        if ($paymentStatus) {
            $paymentFilter = " AND a.payment_status = :payment_status";
            $baseQuery .= $paymentFilter;
            $countQuery .= $paymentFilter;
            $params[':payment_status'] = $paymentStatus;
        }
        
        if ($dateFrom) {
            $dateFromFilter = " AND DATE(a.trip_date) >= :date_from";
            $baseQuery .= $dateFromFilter;
            $countQuery .= $dateFromFilter;
            $params[':date_from'] = $dateFrom;
        }
        
        if ($dateTo) {
            $dateToFilter = " AND DATE(a.trip_date) <= :date_to";
            $baseQuery .= $dateToFilter;
            $countQuery .= $dateToFilter;
            $params[':date_to'] = $dateTo;
        }
        
        if ($search) {
            $searchFilter = " AND (a.application_number LIKE :search OR a.customer_name LIKE :search OR a.customer_phone LIKE :search)";
            $baseQuery .= $searchFilter;
            $countQuery .= $searchFilter;
            $params[':search'] = "%{$search}%";
        }
        
        // Добавляем сортировку и пагинацию
        $baseQuery .= " ORDER BY a.{$sortBy} {$sortOrder} LIMIT :limit OFFSET :offset";
        
        // Выполняем запрос для подсчета общего количества
        $countStmt = $conn->prepare($countQuery);
        $countStmt->execute($params);
        $totalRecords = $countStmt->fetchColumn();
        $totalPages = ceil($totalRecords / $limit);
        
        // Выполняем основной запрос
        $stmt = $conn->prepare($baseQuery);
        
        // Привязываем параметры для пагинации
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        
        $stmt->execute();
        $applications = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Получаем маршруты и пассажиров для каждой заявки
        foreach ($applications as &$app) {
            // Маршруты
            $routeQuery = "SELECT * FROM application_routes WHERE application_id = :app_id ORDER BY point_order";
            $routeStmt = $conn->prepare($routeQuery);
            $routeStmt->execute([':app_id' => $app['id']]);
            $app['routes'] = $routeStmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Пассажиры
            $passengerQuery = "SELECT * FROM application_passengers WHERE application_id = :app_id";
            $passengerStmt = $conn->prepare($passengerQuery);
            $passengerStmt->execute([':app_id' => $app['id']]);
            $app['passengers'] = $passengerStmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Формируем читаемое имя водителя
            if ($app['driver_first_name'] && $app['driver_last_name']) {
                $app['driver_name'] = $app['driver_first_name'] . ' ' . $app['driver_last_name'];
            } else {
                $app['driver_name'] = null;
            }
            
            // Формируем читаемое название автомобиля
            if ($app['vehicle_brand'] && $app['vehicle_model']) {
                $app['vehicle_name'] = $app['vehicle_brand'] . ' ' . $app['vehicle_model'];
            } else {
                $app['vehicle_name'] = null;
            }
        }
        
        // Логируем действие
        logAction('view_applications_list', $userContext['user_id']);
        
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'data' => $applications,
            'pagination' => [
                'current_page' => $page,
                'total_pages' => $totalPages,
                'total_records' => $totalRecords,
                'per_page' => $limit,
                'has_next' => $page < $totalPages,
                'has_prev' => $page > 1
            ],
            'filters' => [
                'status' => $status,
                'payment_status' => $paymentStatus,
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'search' => $search,
                'sort_by' => $sortBy,
                'sort_order' => $sortOrder
            ]
        ]);
        
    } catch (PDOException $e) {
        error_log("Applications fetch error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Ошибка получения заявок: ' . $e->getMessage()
        ]);
    }
}

/**
 * Создание новой заявки с валидацией
 */
function createApplication() {
    $userContext = getCurrentUserContext();
    $data = json_decode(file_get_contents("php://input"), true);
    
    if (empty($data)) {
        $data = $_POST;
    }
    
    // Валидация обязательных полей
    $requiredFields = ['customer_name', 'customer_phone', 'trip_date'];
    foreach ($requiredFields as $field) {
        if (empty($data[$field])) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => "Обязательное поле '{$field}' не заполнено"
            ]);
            return;
        }
    }
    
    try {
        $database = new Database();
        $conn = $database->getConnection();
        $conn->beginTransaction();
        
        // Генерируем номер заявки
        $appNumber = 'A' . date('Ymd') . sprintf('%04d', rand(1000, 9999));
        
        // Форматируем дату и время
        $tripDateTime = date('Y-m-d H:i:s', strtotime($data['trip_date']));
        $pickupTime = !empty($data['pickup_time']) ? date('Y-m-d H:i:s', strtotime($data['pickup_time'])) : null;
        $deliveryTime = !empty($data['delivery_time']) ? date('Y-m-d H:i:s', strtotime($data['delivery_time'])) : null;
        
        // Проверяем права на создание заказов
        if (!in_array($userContext['role'], ['admin', 'dispatcher', 'manager'])) {
            throw new Exception('Недостаточно прав для создания заявок');
        }
        
        // Создаем основную заявку
        $query = "INSERT INTO applications (
                    application_number, status, city, country, trip_date, pickup_time, delivery_time, service_type, tariff,
                    customer_name, customer_phone, order_amount, created_by, driver_id, vehicle_id,
                    flight_number, manager_comment, notes, payment_status,
                    customer_company_id, executor_company_id
                  ) VALUES (
                    :app_number, :status, :city, :country, :trip_date, :pickup_time, :delivery_time, :service_type, :tariff,
                    :customer_name, :customer_phone, :order_amount, :created_by, :driver_id, :vehicle_id,
                    :flight_number, :manager_comment, :notes, :payment_status,
                    :customer_company_id, :executor_company_id
                  )";
        
        $stmt = $conn->prepare($query);
        $stmt->execute([
            ':app_number' => $appNumber,
            ':status' => $data['status'] ?? 'new',
            ':city' => $data['city'] ?? 'Москва',
            ':country' => $data['country'] ?? 'ru',
            ':trip_date' => $tripDateTime,
            ':pickup_time' => $pickupTime,
            ':delivery_time' => $deliveryTime,
            ':service_type' => $data['service_type'] ?? 'transfer',
            ':tariff' => $data['tariff'] ?? 'comfort',
            ':customer_name' => trim($data['customer_name']),
            ':customer_phone' => trim($data['customer_phone']),
            ':order_amount' => floatval($data['order_amount'] ?? 0),
            ':created_by' => $userContext['user_id'],
            ':driver_id' => !empty($data['driver_id']) ? intval($data['driver_id']) : null,
            ':vehicle_id' => !empty($data['vehicle_id']) ? intval($data['vehicle_id']) : null,
            ':flight_number' => $data['flight_number'] ?? null,
            ':manager_comment' => $data['manager_comment'] ?? null,
            ':notes' => $data['notes'] ?? null,
            ':payment_status' => $data['payment_status'] ?? 'pending',
            ':customer_company_id' => !empty($data['customer_company_id']) ? intval($data['customer_company_id']) : null,
            ':executor_company_id' => $userContext['company_id'] ?? null
        ]);
        
        $applicationId = $conn->lastInsertId();
        
        // Сохраняем маршруты
        if (!empty($data['routes']) && is_array($data['routes'])) {
            foreach ($data['routes'] as $index => $route) {
                if (!empty(trim($route['address'] ?? $route))) {
                    $routeQuery = "INSERT INTO application_routes (application_id, point_order, city, country, address) 
                                  VALUES (:app_id, :order, :city, :country, :address)";
                    $routeStmt = $conn->prepare($routeQuery);
                    $routeStmt->execute([
                        ':app_id' => $applicationId,
                        ':order' => $index,
                        ':city' => $data['city'] ?? 'Москва',
                        ':country' => $data['country'] ?? 'ru',
                        ':address' => trim($route['address'] ?? $route)
                    ]);
                }
            }
        }
        
        // Сохраняем пассажиров
        if (!empty($data['passengers']) && is_array($data['passengers'])) {
            foreach ($data['passengers'] as $passenger) {
                if (!empty(trim($passenger['name'] ?? ''))) {
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
        }
        
        $conn->commit();
        
        // Логируем действие
        logAction("create_application", $userContext['user_id'], "Создана заявка {$appNumber}");
        
        http_response_code(201);
        echo json_encode([
            'success' => true,
            'message' => 'Заявка успешно создана',
            'application_id' => $applicationId,
            'application_number' => $appNumber
        ]);
        
    } catch (Exception $e) {
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

/**
 * Обновление статуса заявки
 */
function updateApplicationStatus() {
    $userContext = getCurrentUserContext();
    $data = json_decode(file_get_contents("php://input"), true);
    
    if (empty($data)) {
        $data = $_POST;
    }
    
    $applicationId = intval($data['application_id'] ?? 0);
    $newStatus = $data['status'] ?? '';
    
    if (!$applicationId || !$newStatus) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Не указаны ID заявки или новый статус'
        ]);
        return;
    }
    
    // Валидация статуса
    $allowedStatuses = ['new', 'assigned', 'in_progress', 'completed', 'cancelled'];
    if (!in_array($newStatus, $allowedStatuses)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Недопустимый статус заявки'
        ]);
        return;
    }
    
    try {
        $database = new Database();
        $conn = $database->getConnection();
        
        // Проверяем права на изменение статуса
        $checkQuery = "SELECT a.*, d.user_id as driver_user_id 
                      FROM applications a 
                      LEFT JOIN drivers d ON a.driver_id = d.id 
                      WHERE a.id = :app_id";
        $checkStmt = $conn->prepare($checkQuery);
        $checkStmt->execute([':app_id' => $applicationId]);
        $application = $checkStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$application) {
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'message' => 'Заявка не найдена'
            ]);
            return;
        }
        
        // Проверяем права доступа
        $canUpdate = false;
        if ($userContext['role'] === 'admin') {
            $canUpdate = true;
        } elseif (in_array($userContext['role'], ['dispatcher', 'manager'])) {
            $canUpdate = ($application['executor_company_id'] == $userContext['company_id']);
        } elseif ($userContext['role'] === 'driver') {
            $canUpdate = ($application['driver_user_id'] == $userContext['user_id']);
        }
        
        if (!$canUpdate) {
            http_response_code(403);
            echo json_encode([
                'success' => false,
                'message' => 'Недостаточно прав для изменения статуса этой заявки'
            ]);
            return;
        }
        
        // Обновляем статус
        $query = "UPDATE applications SET status = :status, updated_at = CURRENT_TIMESTAMP WHERE id = :app_id";
        $stmt = $conn->prepare($query);
        $stmt->execute([
            ':status' => $newStatus,
            ':app_id' => $applicationId
        ]);
        
        // Отправляем уведомления диспетчерам
        if (!empty($application['application_number'])) {
            notifyDispatchersStatusChange(
                $conn,
                $application['application_number'],
                $application['status'],
                $newStatus,
                $application['executor_company_id'] ?? null
            );
        }

        // Логируем действие
        logAction("update_application_status", $userContext['user_id'], 
                 "Изменен статус заявки {$application['application_number']} на {$newStatus}");
        
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => 'Статус заявки успешно обновлен'
        ]);
        
    } catch (Exception $e) {
        error_log("Update status error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Ошибка обновления статуса: ' . $e->getMessage()
        ]);
    }
}

/**
 * Назначение водителя на заявку
 */
function assignDriver() {
    $userContext = getCurrentUserContext();
    $data = json_decode(file_get_contents("php://input"), true);
    
    if (empty($data)) {
        $data = $_POST;
    }
    
    $applicationId = intval($data['application_id'] ?? 0);
    $driverId = intval($data['driver_id'] ?? 0);
    
    if (!$applicationId || !$driverId) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Не указаны ID заявки или водителя'
        ]);
        return;
    }
    
    // Проверяем права на назначение водителей
    if (!in_array($userContext['role'], ['admin', 'dispatcher', 'manager'])) {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'message' => 'Недостаточно прав для назначения водителей'
        ]);
        return;
    }
    
    try {
        $database = new Database();
        $conn = $database->getConnection();
        
        // Проверяем, что водитель существует и доступен
        $driverQuery = "SELECT * FROM drivers WHERE id = :driver_id AND status = 'work'";
        $driverStmt = $conn->prepare($driverQuery);
        $driverStmt->execute([':driver_id' => $driverId]);
        $driver = $driverStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$driver) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Водитель не найден или недоступен'
            ]);
            return;
        }
        
        // Получаем номер заявки для уведомления
        $appInfoStmt = $conn->prepare("SELECT application_number FROM applications WHERE id = :app_id");
        $appInfoStmt->execute([':app_id' => $applicationId]);
        $appInfo = $appInfoStmt->fetch(PDO::FETCH_ASSOC);
        
        // Назначаем водителя и обновляем статус
        $query = "UPDATE applications SET driver_id = :driver_id, status = 'assigned', updated_at = CURRENT_TIMESTAMP WHERE id = :app_id";
        $stmt = $conn->prepare($query);
        $stmt->execute([
            ':driver_id' => $driverId,
            ':app_id' => $applicationId
        ]);
        
        // Отправляем уведомление водителю
        if (!empty($appInfo['application_number'])) {
            notifyDriverAssignment($conn, $driverId, $appInfo['application_number']);
        }
        
        // Логируем действие
        logAction("assign_driver", $userContext['user_id'], 
                 "Назначен водитель {$driver['first_name']} {$driver['last_name']} на заявку #{$applicationId}");
        
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => 'Водитель успешно назначен на заявку'
        ]);
        
    } catch (Exception $e) {
        error_log("Assign driver error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Ошибка назначения водителя: ' . $e->getMessage()
        ]);
    }
}

/**
 * Назначение автомобиля на заявку
 */
function assignVehicle() {
    $userContext = getCurrentUserContext();
    $data = json_decode(file_get_contents("php://input"), true);
    
    if (empty($data)) {
        $data = $_POST;
    }
    
    $applicationId = intval($data['application_id'] ?? 0);
    $vehicleId = intval($data['vehicle_id'] ?? 0);
    
    if (!$applicationId || !$vehicleId) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Не указаны ID заявки или автомобиля'
        ]);
        return;
    }
    
    // Проверяем права на назначение автомобилей
    if (!in_array($userContext['role'], ['admin', 'dispatcher', 'manager'])) {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'message' => 'Недостаточно прав для назначения автомобилей'
        ]);
        return;
    }
    
    try {
        $database = new Database();
        $conn = $database->getConnection();
        
        // Проверяем, что автомобиль существует и доступен
        $vehicleQuery = "SELECT * FROM vehicles WHERE id = :vehicle_id AND status = 'working'";
        $vehicleStmt = $conn->prepare($vehicleQuery);
        $vehicleStmt->execute([':vehicle_id' => $vehicleId]);
        $vehicle = $vehicleStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$vehicle) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Автомобиль не найден или недоступен'
            ]);
            return;
        }
        
        // Назначаем автомобиль
        $query = "UPDATE applications SET vehicle_id = :vehicle_id, updated_at = CURRENT_TIMESTAMP WHERE id = :app_id";
        $stmt = $conn->prepare($query);
        $stmt->execute([
            ':vehicle_id' => $vehicleId,
            ':app_id' => $applicationId
        ]);
        
        // Логируем действие
        logAction("assign_vehicle", $userContext['user_id'], 
                 "Назначен автомобиль {$vehicle['brand']} {$vehicle['model']} ({$vehicle['license_plate']}) на заявку #{$applicationId}");
        
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => 'Автомобиль успешно назначен на заявку'
        ]);
        
    } catch (Exception $e) {
        error_log("Assign vehicle error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Ошибка назначения автомобиля: ' . $e->getMessage()
        ]);
    }
}

/**
 * Функция логирования действий
 */
function logAction($action, $userId, $description = null) {
    try {
        $database = new Database();
        $conn = $database->getConnection();
        
        $logQuery = "INSERT INTO activity_log (user_id, action, ip_address, user_agent) 
                    VALUES (:user_id, :action, :ip, :user_agent)";
        $logStmt = $conn->prepare($logQuery);
        $logStmt->execute([
            ':user_id' => $userId,
            ':action' => $description ?? $action,
            ':ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            ':user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
        ]);
    } catch (Exception $e) {
        error_log("Logging error: " . $e->getMessage());
    }
}

// Получение action из GET или POST
$action = $_GET['action'] ?? ($_POST['action'] ?? '');

// Обработка разных действий
switch ($action) {
    case 'getAll':
    case 'list':
        getAllApplications();
        break;
    case 'create':
        createApplication();
        break;
    case 'updateStatus':
        updateApplicationStatus();
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
?>