<?php
/**
 * API для управления заказами (applications)
 * Обеспечивает полную функциональность CRUD операций с фильтрацией, пагинацией и сортировкой
 */

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require_once '../config/database.php';
require_once '../auth.php';

// Обработка CORS preflight запросов
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
    case 'getById':
        getApplicationById();
        break;
    case 'create':
        createApplication();
        break;
    case 'update':
        updateApplication();
        break;
    case 'delete':
        deleteApplication();
        break;
    case 'assignDriver':
        assignDriver();
        break;
    case 'assignVehicle':
        assignVehicle();
        break;
    case 'updateStatus':
        updateApplicationStatus();
        break;
    case 'getFilters':
        getFilterOptions();
        break;
    default:
        // По умолчанию возвращаем все заявки
        getAllApplications();
        break;
}

/**
 * Получение всех заявок с фильтрацией, пагинацией и сортировкой
 */
function getAllApplications() {
    // Параметры фильтрации
    $user_id = $_GET['user_id'] ?? null;
    $status = $_GET['status'] ?? null;
    $date = $_GET['date'] ?? null;
    $date_from = $_GET['date_from'] ?? null;
    $date_to = $_GET['date_to'] ?? null;
    $driver_id = $_GET['driver_id'] ?? null;
    $vehicle_id = $_GET['vehicle_id'] ?? null;
    $page = max(1, intval($_GET['page'] ?? 1));
    $limit = max(1, min(100, intval($_GET['limit'] ?? 20)));
    $sort_by = $_GET['sort_by'] ?? 'trip_date';
    $sort_order = strtoupper($_GET['sort_order'] ?? 'DESC');
    
    // Валидация параметров сортировки
    $allowed_sort_fields = ['trip_date', 'status', 'order_amount', 'created_at', 'application_number'];
    $sort_by = in_array($sort_by, $allowed_sort_fields) ? $sort_by : 'trip_date';
    $sort_order = in_array($sort_order, ['ASC', 'DESC']) ? $sort_order : 'DESC';
    
    try {
        $database = new Database();
        $conn = $database->getConnection();
        
        // Базовая часть запроса с JOIN для получения связанных данных
        $query = "SELECT a.*, 
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
                  WHERE 1=1";
        
        $params = [];
        
        // Фильтрация по статусу
        if ($status) {
            $query .= " AND a.status = :status";
            $params[':status'] = $status;
        }
        
        // Фильтрация по дате
        if ($date) {
            $query .= " AND DATE(a.trip_date) = :date";
            $params[':date'] = $date;
        }
        
        // Фильтрация по диапазону дат
        if ($date_from) {
            $query .= " AND DATE(a.trip_date) >= :date_from";
            $params[':date_from'] = $date_from;
        }
        
        if ($date_to) {
            $query .= " AND DATE(a.trip_date) <= :date_to";
            $params[':date_to'] = $date_to;
        }
        
        // Фильтрация по водителю
        if ($driver_id) {
            $query .= " AND a.driver_id = :driver_id";
            $params[':driver_id'] = $driver_id;
        }
        
        // Фильтрация по автомобилю
        if ($vehicle_id) {
            $query .= " AND a.vehicle_id = :vehicle_id";
            $params[':vehicle_id'] = $vehicle_id;
        }
        
        // Фильтрация по ролям пользователей (ACL)
        if (isLoggedIn()) {
            $user_role = $_SESSION['user_role'] ?? '';
            $user_id_session = $_SESSION['user_id'] ?? 0;
            
            switch ($user_role) {
                case 'driver':
                    // Водители видят только свои заказы
                    $query .= " AND a.driver_id = :driver_user_id";
                    $params[':driver_user_id'] = $user_id_session;
                    break;
                    
                case 'manager':
                case 'dispatcher':
                    // Менеджеры и диспетчеры видят заказы своей компании
                    $query .= " AND (a.executor_company_id = :user_company_id OR a.created_by = :user_id)";
                    $params[':user_company_id'] = $_SESSION['user_company_id'] ?? 0;
                    $params[':user_id'] = $user_id_session;
                    break;
                    
                case 'admin':
                    // Администраторы видят все заказы
                    break;
                    
                default:
                    // По умолчанию показываем только активные заказы
                    $query .= " AND a.status IN ('new', 'assigned', 'in_progress')";
            }
        }
        
        // Подсчет общего количества записей для пагинации
        $count_query = str_replace("SELECT a.*, d.first_name as driver_first_name, 
                         d.last_name as driver_last_name,
                         d.phone as driver_phone,
                         v.brand as vehicle_brand, 
                         v.model as vehicle_model,
                         v.license_plate as vehicle_plate,
                         v.class as vehicle_class,
                         c.name as customer_company_name,
                         ec.name as executor_company_name,
                         u.name as creator_name", 
                                  "SELECT COUNT(DISTINCT a.id)", $query);
        $count_query = preg_replace('/ORDER BY.*$/', '', $count_query);
        
        $count_stmt = $conn->prepare($count_query);
        $count_stmt->execute($params);
        $total_records = $count_stmt->fetchColumn();
        $total_pages = ceil($total_records / $limit);
        
        // Основной запрос с сортировкой и пагинацией
        $query .= " ORDER BY a.{$sort_by} {$sort_order}";
        $offset = ($page - 1) * $limit;
        $query .= " LIMIT {$limit} OFFSET {$offset}";
        
        $stmt = $conn->prepare($query);
        $stmt->execute($params);
        
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
            
            // Форматируем отображаемые данные
            $app['display_name'] = $app['customer_name'];
            $app['display_driver'] = $app['driver_first_name'] ? 
                $app['driver_first_name'] . ' ' . $app['driver_last_name'] : 'Не назначен';
            $app['display_vehicle'] = $app['vehicle_brand'] ? 
                $app['vehicle_brand'] . ' ' . $app['vehicle_model'] . ' (' . $app['vehicle_plate'] . ')' : 'Не назначен';
            $app['formatted_amount'] = number_format($app['order_amount'], 0, ',', ' ') . ' ₽';
            $app['formatted_date'] = date('d.m.Y H:i', strtotime($app['trip_date']));
        }
        
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'data' => $applications,
            'pagination' => [
                'current_page' => $page,
                'total_pages' => $total_pages,
                'total_records' => $total_records,
                'per_page' => $limit,
                'has_next_page' => $page < $total_pages,
                'has_prev_page' => $page > 1
            ],
            'filters' => [
                'status' => $status,
                'date' => $date,
                'date_from' => $date_from,
                'date_to' => $date_to,
                'driver_id' => $driver_id,
                'vehicle_id' => $vehicle_id,
                'sort_by' => $sort_by,
                'sort_order' => $sort_order
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
 * Получение заявки по ID
 */
function getApplicationById() {
    $id = $_GET['id'] ?? null;
    
    if (!$id) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'ID заявки не указан'
        ]);
        return;
    }
    
    try {
        $database = new Database();
        $conn = $database->getConnection();
        
        $query = "SELECT a.*, 
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
                  WHERE a.id = :id";
        
        $stmt = $conn->prepare($query);
        $stmt->execute([':id' => $id]);
        
        $application = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$application) {
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'message' => 'Заявка не найдена'
            ]);
            return;
        }
        
        // Получаем маршруты
        $routeQuery = "SELECT * FROM application_routes WHERE application_id = :app_id ORDER BY point_order";
        $routeStmt = $conn->prepare($routeQuery);
        $routeStmt->execute([':app_id' => $id]);
        $application['routes'] = $routeStmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Получаем пассажиров
        $passengerQuery = "SELECT * FROM application_passengers WHERE application_id = :app_id";
        $passengerStmt = $conn->prepare($passengerQuery);
        $passengerStmt->execute([':app_id' => $id]);
        $application['passengers'] = $passengerStmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Форматируем отображаемые данные
        $application['display_name'] = $application['customer_name'];
        $application['display_driver'] = $application['driver_first_name'] ? 
            $application['driver_first_name'] . ' ' . $application['driver_last_name'] : 'Не назначен';
        $application['display_vehicle'] = $application['vehicle_brand'] ? 
            $application['vehicle_brand'] . ' ' . $application['vehicle_model'] . ' (' . $application['vehicle_plate'] . ')' : 'Не назначен';
        $application['formatted_amount'] = number_format($application['order_amount'], 0, ',', ' ') . ' ₽';
        $application['formatted_date'] = date('d.m.Y H:i', strtotime($application['trip_date']));
        
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'data' => $application
        ]);
        
    } catch (PDOException $e) {
        error_log("Application fetch error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Ошибка получения заявки: ' . $e->getMessage()
        ]);
    }
}

/**
 * Создание новой заявки с валидацией
 */
function createApplication() {
    $data = json_decode(file_get_contents("php://input"), true);
    
    if (empty($data)) {
        $data = $_POST;
    }
    
    // Базовая валидация
    $required_fields = ['customer_name', 'customer_phone', 'trip_date', 'service_type'];
    foreach ($required_fields as $field) {
        if (empty($data[$field])) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => "Поле '{$field}' обязательно для заполнения"
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
        $tripDateTime = $data['trip_date'];
        if ($tripDateTime) {
            $tripDateTime = date('Y-m-d H:i:s', strtotime($tripDateTime));
        }
        
        // Время подачи и доставки
        $pickupTime = null;
        if (!empty($data['pickup_time'])) {
            $pickupTime = date('Y-m-d H:i:s', strtotime($data['pickup_time']));
        }
        
        $deliveryTime = null;
        if (!empty($data['delivery_time'])) {
            $deliveryTime = date('Y-m-d H:i:s', strtotime($data['delivery_time']));
        }
        
        // Создаем основную заявку
        $query = "INSERT INTO applications (
                    application_number, status, city, country, trip_date, 
                    pickup_time, delivery_time,
                    service_type, tariff, customer_name, customer_phone, 
                    order_amount, created_by, flight_number, manager_comment, 
                    notes, payment_status,
                    customer_company_id, executor_company_id
                  ) VALUES (
                    :app_number, :status, :city, :country, :trip_date,
                    :pickup_time, :delivery_time,
                    :service_type, :tariff, :customer_name, :customer_phone,
                    :order_amount, :created_by, :flight_number, :manager_comment,
                    :notes, :payment_status,
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
            ':service_type' => $data['service_type'],
            ':tariff' => $data['tariff'] ?? $data['vehicle_class'] ?? 'comfort',
            ':customer_name' => $data['customer_name'],
            ':customer_phone' => $data['customer_phone'],
            ':order_amount' => $data['order_amount'] ?? 0,
            ':created_by' => $_SESSION['user_id'] ?? 1,
            ':flight_number' => $data['flight_number'] ?? null,
            ':manager_comment' => $data['driver_comment'] ?? $data['manager_comment'] ?? null,
            ':notes' => $data['notes'] ?? null,
            ':payment_status' => $data['payment_status'] ?? 'pending',
            ':customer_company_id' => $data['customer_company_id'] ?? null,
            ':executor_company_id' => $data['executor_company_id'] ?? ($_SESSION['user_company_id'] ?? 1)
        ]);
        
        $applicationId = $conn->lastInsertId();
        
        // Сохраняем маршруты
        if (!empty($data['routes']) && is_array($data['routes'])) {
            foreach ($data['routes'] as $index => $route) {
                if (!empty(trim($route['address'] ?? $route))) {
                    $address = is_array($route) ? $route['address'] : $route;
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
        logAction("Создана заявка {$appNumber}");
        
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

/**
 * Обновление заявки
 */
function updateApplication() {
    $data = json_decode(file_get_contents("php://input"), true);
    
    if (empty($data)) {
        $data = $_POST;
    }
    
    $id = $data['id'] ?? null;
    
    if (!$id) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'ID заявки не указан'
        ]);
        return;
    }
    
    try {
        $database = new Database();
        $conn = $database->getConnection();
        $conn->beginTransaction();
        
        // Форматируем даты
        $tripDateTime = null;
        if (!empty($data['trip_date'])) {
            $tripDateTime = date('Y-m-d H:i:s', strtotime($data['trip_date']));
        }
        
        $pickupTime = null;
        if (!empty($data['pickup_time'])) {
            $pickupTime = date('Y-m-d H:i:s', strtotime($data['pickup_time']));
        }
        
        $deliveryTime = null;
        if (!empty($data['delivery_time'])) {
            $deliveryTime = date('Y-m-d H:i:s', strtotime($data['delivery_time']));
        }
        
        // Обновляем основную заявку
        $query = "UPDATE applications SET 
                    status = :status,
                    city = :city,
                    country = :country,
                    trip_date = :trip_date,
                    pickup_time = :pickup_time,
                    delivery_time = :delivery_time,
                    service_type = :service_type,
                    tariff = :tariff,
                    customer_name = :customer_name,
                    customer_phone = :customer_phone,
                    order_amount = :order_amount,
                    flight_number = :flight_number,
                    manager_comment = :manager_comment,
                    notes = :notes,
                    payment_status = :payment_status,
                    updated_at = CURRENT_TIMESTAMP
                  WHERE id = :id";
        
        $stmt = $conn->prepare($query);
        $stmt->execute([
            ':status' => $data['status'] ?? 'new',
            ':city' => $data['city'] ?? 'Москва',
            ':country' => $data['country'] ?? 'ru',
            ':trip_date' => $tripDateTime,
            ':pickup_time' => $pickupTime,
            ':delivery_time' => $deliveryTime,
            ':service_type' => $data['service_type'] ?? 'transfer',
            ':tariff' => $data['tariff'] ?? $data['vehicle_class'] ?? 'comfort',
            ':customer_name' => $data['customer_name'],
            ':customer_phone' => $data['customer_phone'],
            ':order_amount' => $data['order_amount'] ?? 0,
            ':flight_number' => $data['flight_number'] ?? null,
            ':manager_comment' => $data['manager_comment'] ?? null,
            ':notes' => $data['notes'] ?? null,
            ':payment_status' => $data['payment_status'] ?? 'pending',
            ':id' => $id
        ]);
        
        // Обновляем маршруты (удаляем старые и добавляем новые)
        $deleteRoutesQuery = "DELETE FROM application_routes WHERE application_id = :app_id";
        $deleteRoutesStmt = $conn->prepare($deleteRoutesQuery);
        $deleteRoutesStmt->execute([':app_id' => $id]);
        
        if (!empty($data['routes']) && is_array($data['routes'])) {
            foreach ($data['routes'] as $index => $route) {
                if (!empty(trim($route['address'] ?? $route))) {
                    $address = is_array($route) ? $route['address'] : $route;
                    $routeQuery = "INSERT INTO application_routes (application_id, point_order, city, country, address) 
                                  VALUES (:app_id, :order, :city, :country, :address)";
                    $routeStmt = $conn->prepare($routeQuery);
                    $routeStmt->execute([
                        ':app_id' => $id,
                        ':order' => $index,
                        ':city' => $data['city'] ?? 'Москва',
                        ':country' => $data['country'] ?? 'ru',
                        ':address' => trim($address)
                    ]);
                }
            }
        }
        
        // Обновляем пассажиров
        $deletePassengersQuery = "DELETE FROM application_passengers WHERE application_id = :app_id";
        $deletePassengersStmt = $conn->prepare($deletePassengersQuery);
        $deletePassengersStmt->execute([':app_id' => $id]);
        
        if (!empty($data['passengers']) && is_array($data['passengers'])) {
            foreach ($data['passengers'] as $passenger) {
                if (!empty(trim($passenger['name'] ?? ''))) {
                    $passengerQuery = "INSERT INTO application_passengers (application_id, name, phone) 
                                      VALUES (:app_id, :name, :phone)";
                    $passengerStmt = $conn->prepare($passengerQuery);
                    $passengerStmt->execute([
                        ':app_id' => $id,
                        ':name' => trim($passenger['name']),
                        ':phone' => $passenger['phone'] ?? null
                    ]);
                }
            }
        }
        
        $conn->commit();
        
        // Логируем действие
        logAction("Обновлена заявка #{$id}");
        
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => 'Заявка успешно обновлена'
        ]);
        
    } catch (PDOException $e) {
        if (isset($conn)) {
            $conn->rollBack();
        }
        error_log("Application update error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Ошибка обновления заявки: ' . $e->getMessage()
        ]);
    }
}

/**
 * Удаление заявки
 */
function deleteApplication() {
    $data = json_decode(file_get_contents("php://input"), true);
    
    if (empty($data)) {
        $data = $_POST;
    }
    
    $id = $data['id'] ?? null;
    
    if (!$id) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'ID заявки не указан'
        ]);
        return;
    }
    
    try {
        $database = new Database();
        $conn = $database->getConnection();
        
        // Проверяем, можно ли удалить заявку (не должна быть в работе)
        $checkQuery = "SELECT status, application_number FROM applications WHERE id = :id";
        $checkStmt = $conn->prepare($checkQuery);
        $checkStmt->execute([':id' => $id]);
        $application = $checkStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$application) {
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'message' => 'Заявка не найдена'
            ]);
            return;
        }
        
        if (in_array($application['status'], ['in_progress', 'assigned'])) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Нельзя удалить заявку в статусе "В работе" или "Назначена"'
            ]);
            return;
        }
        
        $deleteQuery = "DELETE FROM applications WHERE id = :id";
        $deleteStmt = $conn->prepare($deleteQuery);
        $deleteStmt->execute([':id' => $id]);
        
        // Логируем действие
        logAction("Удалена заявка {$application['application_number']}");
        
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => 'Заявка успешно удалена'
        ]);
        
    } catch (PDOException $e) {
        error_log("Application delete error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Ошибка удаления заявки: ' . $e->getMessage()
        ]);
    }
}

/**
 * Назначение водителя
 */
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
        
        // Проверяем, что заявка существует и доступна для назначения
        $checkQuery = "SELECT status, application_number FROM applications WHERE id = :app_id";
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
        
        if (!in_array($application['status'], ['new', 'assigned'])) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Нельзя назначить водителя на заявку в текущем статусе'
            ]);
            return;
        }
        
        // Назначаем водителя и меняем статус
        $newStatus = ($application['status'] === 'new') ? 'assigned' : $application['status'];
        
        $query = "UPDATE applications SET driver_id = :driver_id, status = :status WHERE id = :app_id";
        $stmt = $conn->prepare($query);
        $stmt->execute([
            ':driver_id' => $driverId,
            ':status' => $newStatus,
            ':app_id' => $applicationId
        ]);
        
        // Логируем действие
        logAction("Назначен водитель #{$driverId} на заявку {$application['application_number']}");
        
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

/**
 * Назначение автомобиля
 */
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
        
        // Проверяем, что заявка существует
        $checkQuery = "SELECT application_number FROM applications WHERE id = :app_id";
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
        
        $query = "UPDATE applications SET vehicle_id = :vehicle_id WHERE id = :app_id";
        $stmt = $conn->prepare($query);
        $stmt->execute([
            ':vehicle_id' => $vehicleId,
            ':app_id' => $applicationId
        ]);
        
        // Логируем действие
        logAction("Назначен автомобиль #{$vehicleId} на заявку {$application['application_number']}");
        
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

/**
 * Обновление статуса заявки
 */
function updateApplicationStatus() {
    $data = json_decode(file_get_contents("php://input"), true);
    
    if (empty($data)) {
        $data = $_POST;
    }
    
    $applicationId = $data['application_id'] ?? null;
    $newStatus = $data['status'] ?? null;
    
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
        
        // Проверяем заявку
        $checkQuery = "SELECT status, application_number FROM applications WHERE id = :app_id";
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
        
        // Обновляем статус
        $query = "UPDATE applications SET status = :status, updated_at = CURRENT_TIMESTAMP WHERE id = :app_id";
        $stmt = $conn->prepare($query);
        $stmt->execute([
            ':status' => $newStatus,
            ':app_id' => $applicationId
        ]);
        
        // Логируем действие
        logAction("Изменен статус заявки {$application['application_number']} на '{$newStatus}'");
        
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => 'Статус заявки успешно обновлен'
        ]);
        
    } catch (PDOException $e) {
        error_log("Update status error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Ошибка обновления статуса: ' . $e->getMessage()
        ]);
    }
}

/**
 * Получение опций для фильтров
 */
function getFilterOptions() {
    try {
        $database = new Database();
        $conn = $database->getConnection();
        
        // Получаем доступные статусы
        $statuses = [
            ['value' => 'new', 'label' => 'Новые'],
            ['value' => 'assigned', 'label' => 'Назначенные'],
            ['value' => 'in_progress', 'label' => 'В работе'],
            ['value' => 'completed', 'label' => 'Завершенные'],
            ['value' => 'cancelled', 'label' => 'Отмененные']
        ];
        
        // Получаем водителей
        $driversQuery = "SELECT id, first_name, last_name FROM drivers WHERE status = 'work' ORDER BY first_name, last_name";
        $driversStmt = $conn->prepare($driversQuery);
        $driversStmt->execute();
        $drivers = $driversStmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Получаем автомобили
        $vehiclesQuery = "SELECT id, brand, model, license_plate FROM vehicles WHERE status = 'working' ORDER BY brand, model";
        $vehiclesStmt = $conn->prepare($vehiclesQuery);
        $vehiclesStmt->execute();
        $vehicles = $vehiclesStmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Получаем типы услуг
        $serviceTypes = [
            ['value' => 'rent', 'label' => 'Аренда'],
            ['value' => 'transfer', 'label' => 'Трансфер'],
            ['value' => 'city_transfer', 'label' => 'Городской трансфер'],
            ['value' => 'airport_arrival', 'label' => 'Встреча в аэропорту'],
            ['value' => 'airport_departure', 'label' => 'Трансфер в аэропорт'],
            ['value' => 'train_station', 'label' => 'Вокзал'],
            ['value' => 'remote_area', 'label' => 'Удаленный район'],
            ['value' => 'other', 'label' => 'Другое']
        ];
        
        // Получаем тарифы
        $tariffs = [
            ['value' => 'standard', 'label' => 'Стандарт'],
            ['value' => 'comfort', 'label' => 'Комфорт'],
            ['value' => 'business', 'label' => 'Бизнес'],
            ['value' => 'premium', 'label' => 'Премиум'],
            ['value' => 'crossover', 'label' => 'Кроссовер'],
            ['value' => 'minivan5', 'label' => 'Минивэн 5 мест'],
            ['value' => 'minivan6', 'label' => 'Минивэн 6 мест'],
            ['value' => 'microbus8', 'label' => 'Микроавтобус 8 мест'],
            ['value' => 'microbus10', 'label' => 'Микроавтобус 10 мест'],
            ['value' => 'microbus14', 'label' => 'Микроавтобус 14 мест'],
            ['value' => 'microbus16', 'label' => 'Микроавтобус 16 мест'],
            ['value' => 'microbus18', 'label' => 'Микроавтобус 18 мест'],
            ['value' => 'microbus24', 'label' => 'Микроавтобус 24 места'],
            ['value' => 'bus35', 'label' => 'Автобус 35 мест'],
            ['value' => 'bus44', 'label' => 'Автобус 44 места'],
            ['value' => 'bus50', 'label' => 'Автобус 50 мест']
        ];
        
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'data' => [
                'statuses' => $statuses,
                'drivers' => $drivers,
                'vehicles' => $vehicles,
                'service_types' => $serviceTypes,
                'tariffs' => $tariffs
            ]
        ]);
        
    } catch (PDOException $e) {
        error_log("Get filter options error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Ошибка получения опций фильтров: ' . $e->getMessage()
        ]);
    }
}

/**
 * Функция логирования действий
 */
function logAction($action, $userId = null) {
    try {
        $database = new Database();
        $conn = $database->getConnection();
        
        $logQuery = "INSERT INTO activity_log (user_id, action, ip_address, user_agent) 
                    VALUES (:user_id, :action, :ip, :user_agent)";
        $logStmt = $conn->prepare($logQuery);
        $logStmt->execute([
            ':user_id' => $userId ?? ($_SESSION['user_id'] ?? 1),
            ':action' => $action,
            ':ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            ':user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
        ]);
        
    } catch (PDOException $e) {
        error_log("Logging error: " . $e->getMessage());
    }
}
?>