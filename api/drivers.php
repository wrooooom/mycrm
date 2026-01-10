<?php
require_once '../config.php';
require_once '../includes/functions.php';

header("Content-Type: application/json; charset=UTF-8");

if (!isLoggedIn()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'getAll':
        try {
            $stmt = $pdo->query("SELECT d.*, 
                                 (SELECT COUNT(*) FROM applications a WHERE a.driver_id = d.id AND a.status IN ('confirmed', 'inwork')) as active_orders_count
                                 FROM drivers d ORDER BY d.last_name, d.first_name");
            $drivers = $stmt->fetchAll();
            echo json_encode(['success' => true, 'data' => $drivers]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        break;
}
