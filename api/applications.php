<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require_once '../config/database.php';
require_once '../includes/ACL.php';

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check authentication
if (!ACL::isLoggedIn()) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Требуется авторизация'
    ]);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Get user role and ID
$role = ACL::getUserRole();
$userId = ACL::getUserId();

// Get action from GET or POST
$action = $_GET['action'] ?? ($_POST['action'] ?? '');

// Handle different actions
switch ($action) {
    case 'getAll':
        getAllApplications($role, $userId);
        break;
    case 'getById':
        getApplicationById($role, $userId);
        break;
    case 'create':
        ACL::requirePermission(ACL::canCreateApplication($role), 'У вас нет прав для создания заказов');
        createApplication($userId, $role);
        break;
    case 'update':
        updateApplication($role, $userId);
        break;
    case 'delete':
        deleteApplication($role, $userId);
        break;
    case 'assignDriver':
        ACL::requirePermission(ACL::canAssignDriver($role), 'У вас нет прав для назначения водителя');
        assignDriver($userId);
        break;
    case 'assignVehicle':
        ACL::requirePermission(ACL::canAssignVehicle($role), 'У вас нет прав для назначения автомобиля');
        assignVehicle($userId);
        break;
    case 'updateStatus':
        updateStatus($role, $userId);
        break;
    case 'getComments':
        getComments($role);
        break;
    case 'addComment':
        ACL::requirePermission(ACL::canAddComment($role), 'У вас нет прав для добавления комментариев');
        addComment($userId, $role);
        break;
    default:
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Неизвестное действие'
        ]);
        break;
}

/**
 * Get all applications with filtering
 */
function getAllApplications($role, $userId) {
    $pdo = connectDatabase();

    $status = $_GET['status'] ?? null;
    $date = $_GET['date'] ?? null;
    $driverId = $_GET['driver_id'] ?? null;
    $search = $_GET['search'] ?? null;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 100;
    $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;

    try {
        $query = "SELECT a.*,
                         d.first_name as driver_first_name,
                         d.last_name as driver_last_name,
                         d.user_id as driver_user_id,
                         v.brand as vehicle_brand,
                         v.model as vehicle_model,
                         v.license_plate as vehicle_plate,
                         c.name as customer_company_name,
                         ec.name as executor_company_name,
                         u.name as creator_name,
                         COUNT(DISTINCT ar.id) as route_count
                  FROM applications a
                  LEFT JOIN drivers d ON a.driver_id = d.id
                  LEFT JOIN vehicles v ON a.vehicle_id = v.id
                  LEFT JOIN companies c ON a.customer_company_id = c.id
                  LEFT JOIN companies ec ON a.executor_company_id = ec.id
                  LEFT JOIN users u ON a.created_by = u.id
                  LEFT JOIN application_routes ar ON a.id = ar.application_id
                  WHERE 1=1";

        $params = [];

        // Apply ACL-based filtering
        $aclFilter = ACL::getAccessibleApplications($userId, $role, $pdo);
        if ($aclFilter) {
            $query .= " AND (" . str_replace(':user_id', $userId, $aclFilter) . ")";
        }

        // Apply filters
        if ($status) {
            $query .= " AND a.status = :status";
            $params[':status'] = $status;
        }

        if ($date) {
            $query .= " AND DATE(a.trip_date) = :date";
            $params[':date'] = $date;
        }

        if ($driverId) {
            $query .= " AND a.driver_id = :driver_id";
            $params[':driver_id'] = $driverId;
        }

        if ($search) {
            $query .= " AND (a.application_number LIKE :search OR a.customer_name LIKE :search OR a.customer_phone LIKE :search)";
            $params[':search'] = "%$search%";
        }

        $query .= " GROUP BY a.id ORDER BY a.trip_date DESC LIMIT :limit OFFSET :offset";
        $params[':limit'] = $limit;
        $params[':offset'] = $offset;

        $stmt = $pdo->prepare($query);
        foreach ($params as $key => $value) {
            if (is_int($value)) {
                $stmt->bindValue($key, $value, PDO::PARAM_INT);
            } else {
                $stmt->bindValue($key, $value);
            }
        }
        $stmt->execute();

        $applications = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Filter comments based on role
        foreach ($applications as &$app) {
            $app['can_edit'] = ACL::canEditApplication($role, $app['status']);
            $app['can_delete'] = ACL::canDeleteApplication($role, $app['status']);
            $app['can_assign_driver'] = ACL::canAssignDriver($role);
            $app['can_assign_vehicle'] = ACL::canAssignVehicle($role);
            $app['show_manager_comment'] = ACL::canViewManagerComments($role);
            $app['show_internal_comment'] = ACL::canViewInternalComments($role);
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

/**
 * Get single application by ID
 */
function getApplicationById($role, $userId) {
    $pdo = connectDatabase();
    $applicationId = $_GET['id'] ?? null;

    if (!$applicationId) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Не указан ID заявки'
        ]);
        return;
    }

    try {
        $query = "SELECT a.*,
                         d.first_name as driver_first_name,
                         d.last_name as driver_last_name,
                         v.brand as vehicle_brand,
                         v.model as vehicle_model,
                         v.license_plate as vehicle_plate,
                         v.id as vehicle_id,
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

        $stmt = $pdo->prepare($query);
        $stmt->execute([':id' => $applicationId]);
        $app = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$app) {
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'message' => 'Заявка не найдена'
            ]);
            return;
        }

        // Check access permission
        if (!ACL::canViewAllApplications($role)) {
            if ($role === 'driver' && $app['driver_user_id'] != $userId) {
                http_response_code(403);
                echo json_encode([
                    'success' => false,
                    'message' => 'У вас нет прав для просмотра этой заявки'
                ]);
                return;
            }
            if ($role === 'client' && $app['created_by'] != $userId) {
                http_response_code(403);
                echo json_encode([
                    'success' => false,
                    'message' => 'У вас нет прав для просмотра этой заявки'
                ]);
                return;
            }
        }

        // Get routes
        $routeQuery = "SELECT * FROM application_routes WHERE application_id = :app_id ORDER BY point_order";
        $routeStmt = $pdo->prepare($routeQuery);
        $routeStmt->execute([':app_id' => $applicationId]);
        $app['routes'] = $routeStmt->fetchAll(PDO::FETCH_ASSOC);

        // Get passengers
        $passengerQuery = "SELECT * FROM application_passengers WHERE application_id = :app_id";
        $passengerStmt = $pdo->prepare($passengerQuery);
        $passengerStmt->execute([':app_id' => $applicationId]);
        $app['passengers'] = $passengerStmt->fetchAll(PDO::FETCH_ASSOC);

        // Get files
        $fileQuery = "SELECT * FROM application_files WHERE application_id = :app_id";
        $fileStmt = $pdo->prepare($fileQuery);
        $fileStmt->execute([':app_id' => $applicationId]);
        $app['files'] = $fileStmt->fetchAll(PDO::FETCH_ASSOC);

        // Add permissions
        $app['can_edit'] = ACL::canEditApplication($role, $app['status']);
        $app['can_delete'] = ACL::canDeleteApplication($role, $app['status']);
        $app['can_assign_driver'] = ACL::canAssignDriver($role);
        $app['can_assign_vehicle'] = ACL::canAssignVehicle($role);
        $app['show_manager_comment'] = ACL::canViewManagerComments($role);
        $app['show_internal_comment'] = ACL::canViewInternalComments($role);
        $app['can_view_financial'] = ACL::canViewFinancialData($role);

        http_response_code(200);
        echo json_encode([
            'success' => true,
            'data' => $app
        ]);

    } catch (PDOException $e) {
        error_log("Application fetch error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Ошибка получения заявки'
        ]);
    }
}

/**
 * Create new application
 */
function createApplication($userId, $role) {
    $pdo = connectDatabase();

    $data = json_decode(file_get_contents("php://input"), true);
    if (empty($data)) {
        $data = $_POST;
    }

    // Validate required fields
    $requiredFields = ['customer_name', 'customer_phone', 'city', 'trip_date', 'service_type', 'tariff'];
    foreach ($requiredFields as $field) {
        if (empty($data[$field])) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => "Поле $field обязательно для заполнения"
            ]);
            return;
        }
    }

    // Validate phone format
    if (!preg_match('/^[\+]?[(]?[0-9]{3}[)]?[-\s\.]?[0-9]{3}[-\s\.]?[0-9]{4,6}$/', $data['customer_phone'])) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Некорректный формат телефона'
        ]);
        return;
    }

    // Validate date
    $tripDate = date('Y-m-d H:i:s', strtotime($data['trip_date']));
    if (strtotime($tripDate) < time()) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Дата поездки не может быть в прошлом'
        ]);
        return;
    }

    try {
        $pdo->beginTransaction();

        // Generate application number
        $appNumber = 'A' . date('Ymd') . sprintf('%04d', rand(1000, 9999));

        // Check if number already exists
        while (true) {
            $checkStmt = $pdo->prepare("SELECT id FROM applications WHERE application_number = ?");
            $checkStmt->execute([$appNumber]);
            if (!$checkStmt->fetch()) {
                break;
            }
            $appNumber = 'A' . date('Ymd') . sprintf('%04d', rand(1000, 9999));
        }

        // Create main application
        $query = "INSERT INTO applications (
                    application_number, status, city, country, trip_date, service_type, tariff,
                    customer_name, customer_phone, customer_email,
                    cancellation_hours, additional_services_amount,
                    flight_number, sign_text, manager_comment, internal_comment,
                    order_amount, customer_company_id, executor_company_id, executor_amount,
                    rental_hours, notes, created_by
                  ) VALUES (
                    :app_number, :status, :city, :country, :trip_date, :service_type, :tariff,
                    :customer_name, :customer_phone, :customer_email,
                    :cancellation_hours, :additional_services_amount,
                    :flight_number, :sign_text, :manager_comment, :internal_comment,
                    :order_amount, :customer_company_id, :executor_company_id, :executor_amount,
                    :rental_hours, :notes, :created_by
                  )";

        $stmt = $pdo->prepare($query);
        $stmt->execute([
            ':app_number' => $appNumber,
            ':status' => 'new',
            ':city' => $data['city'],
            ':country' => $data['country'] ?? 'ru',
            ':trip_date' => $tripDate,
            ':service_type' => $data['service_type'],
            ':tariff' => $data['tariff'],
            ':customer_name' => $data['customer_name'],
            ':customer_phone' => $data['customer_phone'],
            ':customer_email' => $data['customer_email'] ?? null,
            ':cancellation_hours' => $data['cancellation_hours'] ?? 0,
            ':additional_services_amount' => $data['additional_services_amount'] ?? 0,
            ':flight_number' => $data['flight_number'] ?? null,
            ':sign_text' => $data['sign_text'] ?? null,
            ':manager_comment' => $data['manager_comment'] ?? null,
            ':internal_comment' => ACL::canViewInternalComments($role) ? ($data['internal_comment'] ?? null) : null,
            ':order_amount' => $data['order_amount'] ?? 0,
            ':customer_company_id' => $data['customer_company_id'] ?? null,
            ':executor_company_id' => $data['executor_company_id'] ?? null,
            ':executor_amount' => $data['executor_amount'] ?? 0,
            ':rental_hours' => $data['rental_hours'] ?? null,
            ':notes' => $data['notes'] ?? null,
            ':created_by' => $userId
        ]);

        $applicationId = $pdo->lastInsertId();

        // Save routes
        if (!empty($data['routes']) && is_array($data['routes'])) {
            foreach ($data['routes'] as $index => $route) {
                if (!empty(trim($route['address']))) {
                    $routeQuery = "INSERT INTO application_routes (application_id, point_order, city, country, address)
                                   VALUES (:app_id, :order, :city, :country, :address)";
                    $routeStmt = $pdo->prepare($routeQuery);
                    $routeStmt->execute([
                        ':app_id' => $applicationId,
                        ':order' => $index,
                        ':city' => $data['city'],
                        ':country' => $data['country'] ?? 'ru',
                        ':address' => trim($route['address'])
                    ]);
                }
            }
        }

        // Save passengers
        if (!empty($data['passengers']) && is_array($data['passengers'])) {
            foreach ($data['passengers'] as $passenger) {
                if (!empty(trim($passenger['name']))) {
                    $passengerQuery = "INSERT INTO application_passengers (application_id, name, phone)
                                       VALUES (:app_id, :name, :phone)";
                    $passengerStmt = $pdo->prepare($passengerQuery);
                    $passengerStmt->execute([
                        ':app_id' => $applicationId,
                        ':name' => trim($passenger['name']),
                        ':phone' => $passenger['phone'] ?? null
                    ]);
                }
            }
        }

        // Log action
        logAction($pdo, $userId, "Создана заявка {$appNumber}");

        $pdo->commit();

        http_response_code(201);
        echo json_encode([
            'success' => true,
            'message' => 'Заявка успешно создана',
            'application_id' => $applicationId,
            'application_number' => $appNumber
        ]);

    } catch (PDOException $e) {
        if (isset($pdo)) {
            $pdo->rollBack();
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
 * Update existing application
 */
function updateApplication($role, $userId) {
    $pdo = connectDatabase();

    $data = json_decode(file_get_contents("php://input"), true);
    if (empty($data)) {
        $data = $_POST;
    }

    $applicationId = $data['id'] ?? null;

    if (!$applicationId) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Не указан ID заявки'
        ]);
        return;
    }

    // Get current application
    $stmt = $pdo->prepare("SELECT * FROM applications WHERE id = ?");
    $stmt->execute([$applicationId]);
    $app = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$app) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'Заявка не найдена'
        ]);
        return;
    }

    // Check edit permissions
    if (!ACL::canEditApplication($role, $app['status'])) {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'message' => 'У вас нет прав для редактирования этой заявки'
        ]);
        return;
    }

    // For drivers, only allow status updates (handled separately)
    if ($role === 'driver') {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'message' => 'Водители могут только менять статус заявки'
        ]);
        return;
    }

    try {
        $pdo->beginTransaction();

        // Build update query
        $updateFields = [];
        $params = [':id' => $applicationId];

        $allowedFields = [
            'customer_name', 'customer_phone', 'customer_email',
            'city', 'country', 'service_type', 'tariff',
            'cancellation_hours', 'additional_services_amount',
            'flight_number', 'sign_text', 'notes',
            'order_amount', 'customer_company_id', 'executor_company_id', 'executor_amount',
            'rental_hours'
        ];

        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $updateFields[] = "$field = :$field";
                $params[":$field"] = $data[$field];
            }
        }

        // Add manager comment based on role
        if (ACL::canViewManagerComments($role) && isset($data['manager_comment'])) {
            $updateFields[] = "manager_comment = :manager_comment";
            $params[':manager_comment'] = $data['manager_comment'];
        }

        // Add internal comment for admin/manager only
        if (ACL::canViewInternalComments($role) && isset($data['internal_comment'])) {
            $updateFields[] = "internal_comment = :internal_comment";
            $params[':internal_comment'] = $data['internal_comment'];
        }

        if (!empty($updateFields)) {
            $query = "UPDATE applications SET " . implode(', ', $updateFields) . " WHERE id = :id";
            $stmt = $pdo->prepare($query);
            $stmt->execute($params);
        }

        // Update routes
        if (isset($data['routes']) && is_array($data['routes'])) {
            // Delete old routes
            $pdo->prepare("DELETE FROM application_routes WHERE application_id = ?")->execute([$applicationId]);

            // Add new routes
            foreach ($data['routes'] as $index => $route) {
                if (!empty(trim($route['address']))) {
                    $routeQuery = "INSERT INTO application_routes (application_id, point_order, city, country, address)
                                   VALUES (?, ?, ?, ?, ?)";
                    $routeStmt = $pdo->prepare($routeQuery);
                    $routeStmt->execute([
                        $applicationId,
                        $index,
                        $data['city'] ?? $app['city'],
                        $data['country'] ?? $app['country'],
                        trim($route['address'])
                    ]);
                }
            }
        }

        // Update passengers
        if (isset($data['passengers']) && is_array($data['passengers'])) {
            // Delete old passengers
            $pdo->prepare("DELETE FROM application_passengers WHERE application_id = ?")->execute([$applicationId]);

            // Add new passengers
            foreach ($data['passengers'] as $passenger) {
                if (!empty(trim($passenger['name']))) {
                    $passengerQuery = "INSERT INTO application_passengers (application_id, name, phone)
                                       VALUES (?, ?, ?)";
                    $passengerStmt = $pdo->prepare($passengerQuery);
                    $passengerStmt->execute([
                        $applicationId,
                        trim($passenger['name']),
                        $passenger['phone'] ?? null
                    ]);
                }
            }
        }

        logAction($pdo, $userId, "Обновлена заявка #{$applicationId}");

        $pdo->commit();

        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => 'Заявка успешно обновлена'
        ]);

    } catch (PDOException $e) {
        if (isset($pdo)) {
            $pdo->rollBack();
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
 * Delete application
 */
function deleteApplication($role, $userId) {
    $pdo = connectDatabase();

    $applicationId = $_GET['id'] ?? ($_POST['id'] ?? null);

    if (!$applicationId) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Не указан ID заявки'
        ]);
        return;
    }

    // Get current application
    $stmt = $pdo->prepare("SELECT * FROM applications WHERE id = ?");
    $stmt->execute([$applicationId]);
    $app = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$app) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'Заявка не найдена'
        ]);
        return;
    }

    // Check delete permissions
    if (!ACL::canDeleteApplication($role, $app['status'])) {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'message' => 'У вас нет прав для удаления этой заявки'
        ]);
        return;
    }

    try {
        $pdo->beginTransaction();

        // Delete application (CASCADE will delete related records)
        $stmt = $pdo->prepare("DELETE FROM applications WHERE id = ?");
        $stmt->execute([$applicationId]);

        logAction($pdo, $userId, "Удалена заявка #{$applicationId}");

        $pdo->commit();

        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => 'Заявка успешно удалена'
        ]);

    } catch (PDOException $e) {
        if (isset($pdo)) {
            $pdo->rollBack();
        }
        error_log("Application delete error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Ошибка удаления заявки: ' . $e->getMessage()
        ]);
    }
}

/**
 * Assign driver to application
 */
function assignDriver($userId) {
    $pdo = connectDatabase();

    $data = json_decode(file_get_contents("php://input"), true);
    if (empty($data)) {
        $data = $_POST;
    }

    $applicationId = $data['application_id'] ?? null;
    $driverId = $data['driver_id'] ?? null;

    if (!$applicationId) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Не указан ID заявки'
        ]);
        return;
    }

    try {
        $pdo->beginTransaction();

        $query = "UPDATE applications SET driver_id = :driver_id WHERE id = :app_id";
        $stmt = $pdo->prepare($query);
        $stmt->execute([
            ':driver_id' => $driverId,
            ':app_id' => $applicationId
        ]);

        logAction($pdo, $userId, "Назначен водитель #{$driverId} на заявку #{$applicationId}");

        $pdo->commit();

        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => 'Водитель успешно назначен'
        ]);

    } catch (PDOException $e) {
        if (isset($pdo)) {
            $pdo->rollBack();
        }
        error_log("Assign driver error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Ошибка назначения водителя: ' . $e->getMessage()
        ]);
    }
}

/**
 * Assign vehicle to application
 */
function assignVehicle($userId) {
    $pdo = connectDatabase();

    $data = json_decode(file_get_contents("php://input"), true);
    if (empty($data)) {
        $data = $_POST;
    }

    $applicationId = $data['application_id'] ?? null;
    $vehicleId = $data['vehicle_id'] ?? null;

    if (!$applicationId) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Не указан ID заявки'
        ]);
        return;
    }

    try {
        $pdo->beginTransaction();

        $query = "UPDATE applications SET vehicle_id = :vehicle_id WHERE id = :app_id";
        $stmt = $pdo->prepare($query);
        $stmt->execute([
            ':vehicle_id' => $vehicleId,
            ':app_id' => $applicationId
        ]);

        logAction($pdo, $userId, "Назначен автомобиль #{$vehicleId} на заявку #{$applicationId}");

        $pdo->commit();

        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => 'Автомобиль успешно назначен'
        ]);

    } catch (PDOException $e) {
        if (isset($pdo)) {
            $pdo->rollBack();
        }
        error_log("Assign vehicle error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Ошибка назначения автомобиля: ' . $e->getMessage()
        ]);
    }
}

/**
 * Update application status
 */
function updateStatus($role, $userId) {
    $pdo = connectDatabase();

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
            'message' => 'Не указан ID заявки или статус'
        ]);
        return;
    }

    // Get current application
    $stmt = $pdo->prepare("SELECT * FROM applications WHERE id = ?");
    $stmt->execute([$applicationId]);
    $app = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$app) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'Заявка не найдена'
        ]);
        return;
    }

    // Check if user can update to this status
    if (!ACL::canUpdateStatus($role, $app['status'], $newStatus)) {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'message' => 'У вас нет прав для изменения статуса на ' . $newStatus
        ]);
        return;
    }

    try {
        $pdo->beginTransaction();

        $query = "UPDATE applications SET status = :status WHERE id = :app_id";
        $stmt = $pdo->prepare($query);
        $stmt->execute([
            ':status' => $newStatus,
            ':app_id' => $applicationId
        ]);

        logAction($pdo, $userId, "Изменен статус заявки #{$applicationId} на {$newStatus}");

        $pdo->commit();

        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => 'Статус успешно изменен'
        ]);

    } catch (PDOException $e) {
        if (isset($pdo)) {
            $pdo->rollBack();
        }
        error_log("Update status error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Ошибка изменения статуса: ' . $e->getMessage()
        ]);
    }
}

/**
 * Get application comments
 */
function getComments($role) {
    $pdo = connectDatabase();
    $applicationId = $_GET['id'] ?? null;

    if (!$applicationId) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Не указан ID заявки'
        ]);
        return;
    }

    try {
        $query = "SELECT ac.*, u.name as user_name, u.role as user_role
                  FROM application_comments ac
                  LEFT JOIN users u ON ac.user_id = u.id
                  WHERE ac.application_id = :app_id
                  ORDER BY ac.created_at DESC";

        $stmt = $pdo->prepare($query);
        $stmt->execute([':app_id' => $applicationId]);
        $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Filter based on role
        if (!ACL::canViewInternalComments($role)) {
            // Drivers and clients should not see internal comments
            $comments = array_filter($comments, function($comment) use ($role) {
                // For now, return all comments from application_comments table
                // Internal comments are stored in the main applications table
                return true;
            });
        }

        http_response_code(200);
        echo json_encode([
            'success' => true,
            'data' => array_values($comments)
        ]);

    } catch (PDOException $e) {
        error_log("Get comments error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Ошибка получения комментариев'
        ]);
    }
}

/**
 * Add comment to application
 */
function addComment($userId, $role) {
    $pdo = connectDatabase();

    $data = json_decode(file_get_contents("php://input"), true);
    if (empty($data)) {
        $data = $_POST;
    }

    $applicationId = $data['application_id'] ?? null;
    $comment = $data['comment'] ?? null;

    if (!$applicationId || !$comment) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Не указан ID заявки или комментарий'
        ]);
        return;
    }

    try {
        $pdo->beginTransaction();

        $query = "INSERT INTO application_comments (application_id, user_id, comment)
                  VALUES (:app_id, :user_id, :comment)";
        $stmt = $pdo->prepare($query);
        $stmt->execute([
            ':app_id' => $applicationId,
            ':user_id' => $userId,
            ':comment' => $comment
        ]);

        logAction($pdo, $userId, "Добавлен комментарий к заявке #{$applicationId}");

        $pdo->commit();

        http_response_code(201);
        echo json_encode([
            'success' => true,
            'message' => 'Комментарий успешно добавлен'
        ]);

    } catch (PDOException $e) {
        if (isset($pdo)) {
            $pdo->rollBack();
        }
        error_log("Add comment error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Ошибка добавления комментария: ' . $e->getMessage()
        ]);
    }
}

/**
 * Helper function to log actions
 */
function logAction($pdo, $userId, $action) {
    try {
        $stmt = $pdo->prepare("INSERT INTO activity_log (user_id, action, ip_address, user_agent)
                              VALUES (?, ?, ?, ?)");
        $stmt->execute([
            $userId,
            $action,
            $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
        ]);
    } catch (Exception $e) {
        error_log("Log action error: " . $e->getMessage());
    }
}
?>
