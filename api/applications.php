<?php
/**
 * API for Applications Management
 */

require_once '../config.php';
require_once '../includes/functions.php';
require_once '../includes/ACL.php';

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

if (!isLoggedIn()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['user_role'];
$action = $_GET['action'] ?? '';

// Handle POST data
$input = json_decode(file_get_contents("php://input"), true);
if (!$input) $input = $_POST;

switch ($action) {
    case 'getAll':
        getAllApplications($pdo, $user_role, $user_id);
        break;
    case 'getById':
        getApplicationById($pdo, $_GET['id'] ?? null, $user_role, $user_id);
        break;
    case 'create':
        if (!ACL::canCreateApplication($user_role)) {
            sendError('Permission denied', 403);
        }
        createApplication($pdo, $input, $user_id);
        break;
    case 'update':
        updateApplication($pdo, $input, $user_role, $user_id);
        break;
    case 'assignDriver':
        if (!ACL::canAssignDriver($user_role)) {
            sendError('Permission denied', 403);
        }
        assignDriver($pdo, $input);
        break;
    case 'assignVehicle':
        if (!ACL::canAssignVehicle($user_role)) {
            sendError('Permission denied', 403);
        }
        assignVehicle($pdo, $input);
        break;
    case 'updateStatus':
        updateStatus($pdo, $input, $user_role, $user_id);
        break;
    case 'addComment':
        addComment($pdo, $input, $user_id);
        break;
    case 'getComments':
        getComments($pdo, $_GET['id'] ?? null, $user_role);
        break;
    case 'delete':
        if (!ACL::canDeleteApplication($user_role)) {
            sendError('Permission denied', 403);
        }
        deleteApplication($pdo, $_GET['id'] ?? $input['id'] ?? null);
        break;
    default:
        sendError('Invalid action');
        break;
}

function sendSuccess($data = [], $message = '') {
    echo json_encode(['success' => true, 'data' => $data, 'message' => $message]);
    exit();
}

function sendError($message, $code = 400) {
    http_response_code($code);
    echo json_encode(['success' => false, 'message' => $message]);
    exit();
}

function getAllApplications($pdo, $role, $userId) {
    try {
        $query = "SELECT a.*, 
                         d.first_name as driver_first_name, 
                         d.last_name as driver_last_name,
                         v.brand as vehicle_brand, 
                         v.model as vehicle_model,
                         v.license_plate as vehicle_plate,
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
        
        // ACL Filters
        if ($role === ACL::ROLE_DRIVER) {
            $query .= " AND a.driver_id = (SELECT id FROM drivers WHERE user_id = ?)";
            $params[] = $userId;
        } elseif ($role === ACL::ROLE_CLIENT) {
            $query .= " AND (a.created_by = ? OR a.customer_company_id = (SELECT company_id FROM users WHERE id = ?))";
            $params[] = $userId;
            $params[] = $userId;
        }

        // Additional Filters from GET
        if (!empty($_GET['status'])) {
            $query .= " AND a.status = ?";
            $params[] = $_GET['status'];
        }
        if (!empty($_GET['date'])) {
            $query .= " AND DATE(a.trip_date) = ?";
            $params[] = $_GET['date'];
        }

        $query .= " ORDER BY a.trip_date DESC";
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $applications = $stmt->fetchAll();

        // Get routes for each
        foreach ($applications as &$app) {
            $stmtR = $pdo->prepare("SELECT * FROM application_routes WHERE application_id = ? ORDER BY point_order");
            $stmtR->execute([$app['id']]);
            $app['routes'] = $stmtR->fetchAll();
        }

        sendSuccess($applications);
    } catch (Exception $e) {
        sendError($e->getMessage(), 500);
    }
}

function getApplicationById($pdo, $id, $role, $userId) {
    if (!$id) sendError('ID missing');
    try {
        $stmt = $pdo->prepare("SELECT * FROM applications WHERE id = ?");
        $stmt->execute([$id]);
        $app = $stmt->fetch();
        
        if (!$app) sendError('Application not found', 404);

        // ACL Check
        if ($role === ACL::ROLE_DRIVER) {
            $stmtD = $pdo->prepare("SELECT id FROM drivers WHERE user_id = ?");
            $stmtD->execute([$userId]);
            $driver = $stmtD->fetch();
            if (!$driver || $app['driver_id'] != $driver['id']) {
                sendError('Access denied', 403);
            }
        } elseif ($role === ACL::ROLE_CLIENT) {
            // Check if it's their order
            if ($app['created_by'] != $userId) {
                $stmtU = $pdo->prepare("SELECT company_id FROM users WHERE id = ?");
                $stmtU->execute([$userId]);
                $user = $stmtU->fetch();
                if ($app['customer_company_id'] != $user['company_id']) {
                    sendError('Access denied', 403);
                }
            }
        }

        // Load routes and passengers
        $stmtR = $pdo->prepare("SELECT * FROM application_routes WHERE application_id = ? ORDER BY point_order");
        $stmtR->execute([$id]);
        $app['routes'] = $stmtR->fetchAll();

        $stmtP = $pdo->prepare("SELECT * FROM application_passengers WHERE application_id = ?");
        $stmtP->execute([$id]);
        $app['passengers'] = $stmtP->fetchAll();

        sendSuccess($app);
    } catch (Exception $e) {
        sendError($e->getMessage(), 500);
    }
}

function createApplication($pdo, $data, $userId) {
    try {
        $pdo->beginTransaction();

        $appNumber = 'A' . date('Ymd') . strtoupper(substr(uniqid(), -4));
        
        $sql = "INSERT INTO applications (
            application_number, status, city, country, trip_date, service_type, tariff,
            cancellation_hours, customer_name, customer_phone, additional_services_amount,
            flight_number, sign_text, notes, manager_comment, internal_comment,
            customer_company_id, executor_company_id, order_amount, executor_amount,
            created_by
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $appNumber,
            $data['status'] ?? 'new',
            $data['city'] ?? '',
            $data['country'] ?? 'ru',
            $data['trip_date'],
            $data['service_type'] ?? 'other',
            $data['tariff'] ?? 'standard',
            $data['cancellation_hours'] ?? 0,
            $data['customer_name'] ?? '',
            $data['customer_phone'] ?? '',
            $data['additional_services_amount'] ?? 0,
            $data['flight_number'] ?? '',
            $data['sign_text'] ?? '',
            $data['notes'] ?? '',
            $data['manager_comment'] ?? '',
            $data['internal_comment'] ?? '',
            !empty($data['customer_company_id']) ? $data['customer_company_id'] : null,
            !empty($data['executor_company_id']) ? $data['executor_company_id'] : null,
            $data['order_amount'] ?? 0,
            $data['executor_amount'] ?? 0,
            $userId
        ]);

        $appId = $pdo->lastInsertId();

        // Routes
        if (!empty($data['routes']) && is_array($data['routes'])) {
            $stmtR = $pdo->prepare("INSERT INTO application_routes (application_id, point_order, address) VALUES (?, ?, ?)");
            foreach ($data['routes'] as $i => $addr) {
                if (trim($addr)) $stmtR->execute([$appId, $i, trim($addr)]);
            }
        }

        // Passengers
        if (!empty($data['passengers']) && is_array($data['passengers'])) {
            $stmtP = $pdo->prepare("INSERT INTO application_passengers (application_id, name, phone) VALUES (?, ?, ?)");
            foreach ($data['passengers'] as $p) {
                if (!empty($p['name'])) $stmtP->execute([$appId, $p['name'], $p['phone'] ?? '']);
            }
        }

        logAction("Создан заказ $appNumber", $userId);
        $pdo->commit();
        sendSuccess(['id' => $appId, 'number' => $appNumber], 'Заказ успешно создан');
    } catch (Exception $e) {
        $pdo->rollBack();
        sendError($e->getMessage(), 500);
    }
}

function updateApplication($pdo, $data, $role, $userId) {
    $id = $data['app_id'] ?? null;
    if (!$id) sendError('ID missing');
    
    try {
        $stmt = $pdo->prepare("SELECT status, application_number FROM applications WHERE id = ?");
        $stmt->execute([$id]);
        $app = $stmt->fetch();
        if (!$app) sendError('Not found');

        if (!ACL::canEditApplication($role, $app['status'])) {
            sendError('Permission denied', 403);
        }

        $pdo->beginTransaction();

        $sql = "UPDATE applications SET 
            city = ?, country = ?, trip_date = ?, service_type = ?, tariff = ?,
            cancellation_hours = ?, customer_name = ?, customer_phone = ?, additional_services_amount = ?,
            flight_number = ?, sign_text = ?, notes = ?, manager_comment = ?, internal_comment = ?,
            customer_company_id = ?, executor_company_id = ?, order_amount = ?, executor_amount = ?,
            status = ?
            WHERE id = ?";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $data['city'], $data['country'], $data['trip_date'], $data['service_type'], $data['tariff'],
            $data['cancellation_hours'], $data['customer_name'], $data['customer_phone'], $data['additional_services_amount'],
            $data['flight_number'], $data['sign_text'], $data['notes'], $data['manager_comment'], $data['internal_comment'],
            !empty($data['customer_company_id']) ? $data['customer_company_id'] : null,
            !empty($data['executor_company_id']) ? $data['executor_company_id'] : null,
            $data['order_amount'], $data['executor_amount'],
            $data['status'],
            $id
        ]);

        // Refresh routes
        $pdo->prepare("DELETE FROM application_routes WHERE application_id = ?")->execute([$id]);
        if (!empty($data['routes']) && is_array($data['routes'])) {
            $stmtR = $pdo->prepare("INSERT INTO application_routes (application_id, point_order, address) VALUES (?, ?, ?)");
            foreach ($data['routes'] as $i => $addr) {
                if (trim($addr)) $stmtR->execute([$id, $i, trim($addr)]);
            }
        }

        // Refresh passengers
        $pdo->prepare("DELETE FROM application_passengers WHERE application_id = ?")->execute([$id]);
        if (!empty($data['passengers']) && is_array($data['passengers'])) {
            $stmtP = $pdo->prepare("INSERT INTO application_passengers (application_id, name, phone) VALUES (?, ?, ?)");
            foreach ($data['passengers'] as $p) {
                if (!empty($p['name'])) $stmtP->execute([$id, $p['name'], $p['phone'] ?? '']);
            }
        }

        logAction("Обновлен заказ {$app['application_number']}", $userId);
        $pdo->commit();
        sendSuccess([], 'Заказ успешно обновлен');
    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        sendError($e->getMessage(), 500);
    }
}

function assignDriver($pdo, $data) {
    $appId = $data['application_id'];
    $driverId = $data['driver_id'];
    try {
        $stmt = $pdo->prepare("UPDATE applications SET driver_id = ?, status = IF(status='new', 'confirmed', status) WHERE id = ?");
        $stmt->execute([$driverId, $appId]);
        logAction("Назначен водитель $driverId на заказ $appId");
        sendSuccess([], 'Водитель назначен');
    } catch (Exception $e) {
        sendError($e->getMessage());
    }
}

function assignVehicle($pdo, $data) {
    $appId = $data['application_id'];
    $vehicleId = $data['vehicle_id'];
    try {
        $stmt = $pdo->prepare("UPDATE applications SET vehicle_id = ? WHERE id = ?");
        $stmt->execute([$vehicleId, $appId]);
        logAction("Назначена машина $vehicleId на заказ $appId");
        sendSuccess([], 'Машина назначена');
    } catch (Exception $e) {
        sendError($e->getMessage());
    }
}

function updateStatus($pdo, $data, $role, $userId) {
    $appId = $data['application_id'];
    $status = $data['status'];
    try {
        // ACL check
        $stmt = $pdo->prepare("SELECT * FROM applications WHERE id = ?");
        $stmt->execute([$appId]);
        $app = $stmt->fetch();
        
        // Find driver_user_id for ACL check
        if ($app['driver_id']) {
            $stmtD = $pdo->prepare("SELECT user_id FROM drivers WHERE id = ?");
            $stmtD->execute([$app['driver_id']]);
            $d = $stmtD->fetch();
            $app['driver_user_id'] = $d['user_id'] ?? null;
        } else {
            $app['driver_user_id'] = null;
        }

        if (!ACL::canUpdateStatus($role, $app)) {
            sendError('Permission denied', 403);
        }

        $stmtU = $pdo->prepare("UPDATE applications SET status = ? WHERE id = ?");
        $stmtU->execute([$status, $appId]);
        logAction("Статус заказа $appId изменен на $status", $userId);
        sendSuccess([], 'Статус обновлен');
    } catch (Exception $e) {
        sendError($e->getMessage());
    }
}

function deleteApplication($pdo, $id) {
    if (!$id) sendError('ID missing');
    try {
        $stmt = $pdo->prepare("DELETE FROM applications WHERE id = ?");
        $stmt->execute([$id]);
        sendSuccess([], 'Заказ удален');
    } catch (Exception $e) {
        sendError($e->getMessage());
    }
}

function addComment($pdo, $data, $userId) {
    // Implement comment adding if needed
}

function getComments($pdo, $id, $role) {
    // Implement comment fetching if needed
}
