<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, OPTIONS");
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

// GET - Получение данных трекинга
if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    $user = validateUser($db);
    if (!$user) {
        sendResponse(false, 'Не авторизован', null, 401);
    }
    
    try {
        $query = "SELECT 
            dt.*,
            CONCAT(d.first_name, ' ', d.last_name, ' ', COALESCE(d.middle_name, '')) as driver_name,
            CONCAT(v.brand, ' ', v.model, ' (', COALESCE(v.license_plate, 'без номера'), ')') as vehicle_info,
            a.application_number as order_number
        FROM driver_tracking dt
        LEFT JOIN drivers d ON dt.driver_id = d.id
        LEFT JOIN vehicles v ON dt.vehicle_id = v.id
        LEFT JOIN applications a ON dt.current_order_id = a.id
        ORDER BY dt.last_update DESC";
        
        $stmt = $db->prepare($query);
        $stmt->execute();
        
        $tracking = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        sendResponse(true, 'Данные трекинга получены', [
            'tracking' => $tracking,
            'last_update' => date('Y-m-d H:i:s')
        ]);
        
    } catch (Exception $e) {
        sendResponse(false, 'Ошибка получения данных трекинга: ' . $e->getMessage(), null, 500);
    }
}

// POST - Обновление позиции водителя
elseif ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user = validateUser($db);
    if (!$user || $user['role'] != 'driver') {
        sendResponse(false, 'Доступ запрещен', null, 403);
    }
    
    $data = json_decode(file_get_contents("php://input"), true);
    
    try {
        // Находим driver_id по user_id
        $driverQuery = "SELECT id FROM drivers WHERE user_id = :user_id";
        $driverStmt = $db->prepare($driverQuery);
        $driverStmt->bindValue(':user_id', $user['id']);
        $driverStmt->execute();
        
        if ($driverStmt->rowCount() === 0) {
            sendResponse(false, 'Водитель не найден', null, 404);
        }
        
        $driver = $driverStmt->fetch(PDO::FETCH_ASSOC);
        $driverId = $driver['id'];
        
        // Проверяем существование записи трекинга
        $checkQuery = "SELECT id FROM driver_tracking WHERE driver_id = :driver_id";
        $checkStmt = $db->prepare($checkQuery);
        $checkStmt->bindValue(':driver_id', $driverId);
        $checkStmt->execute();
        
        if ($checkStmt->rowCount() > 0) {
            // Обновляем существующую запись
            $updateQuery = "UPDATE driver_tracking SET 
                latitude = :latitude,
                longitude = :longitude,
                status = :status,
                location_address = :location_address,
                last_update = NOW()
            WHERE driver_id = :driver_id";
            
            $updateStmt = $db->prepare($updateQuery);
            $updateStmt->bindValue(':latitude', $data['latitude']);
            $updateStmt->bindValue(':longitude', $data['longitude']);
            $updateStmt->bindValue(':status', $data['status']);
            $updateStmt->bindValue(':location_address', $data['location_address'] ?? null);
            $updateStmt->bindValue(':driver_id', $driverId);
            $updateStmt->execute();
        } else {
            // Создаем новую запись
            $insertQuery = "INSERT INTO driver_tracking (
                driver_id, vehicle_id, latitude, longitude, status, location_address
            ) VALUES (
                :driver_id, :vehicle_id, :latitude, :longitude, :status, :location_address
            )";
            
            // Получаем vehicle_id водителя
            $vehicleQuery = "SELECT vehicle_id FROM driver_vehicles WHERE driver_id = :driver_id AND is_active = 1";
            $vehicleStmt = $db->prepare($vehicleQuery);
            $vehicleStmt->bindValue(':driver_id', $driverId);
            $vehicleStmt->execute();
            $vehicle = $vehicleStmt->fetch(PDO::FETCH_ASSOC);
            $vehicleId = $vehicle ? $vehicle['vehicle_id'] : null;
            
            $insertStmt = $db->prepare($insertQuery);
            $insertStmt->bindValue(':driver_id', $driverId);
            $insertStmt->bindValue(':vehicle_id', $vehicleId);
            $insertStmt->bindValue(':latitude', $data['latitude']);
            $insertStmt->bindValue(':longitude', $data['longitude']);
            $insertStmt->bindValue(':status', $data['status']);
            $insertStmt->bindValue(':location_address', $data['location_address'] ?? null);
            $insertStmt->execute();
        }
        
        sendResponse(true, 'Позиция обновлена');
        
    } catch (Exception $e) {
        sendResponse(false, 'Ошибка обновления позиции: ' . $e->getMessage(), null, 500);
    }
}
?>