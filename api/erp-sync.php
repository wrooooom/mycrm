<?php
/**
 * ERP Sync API Endpoint
 */

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once '../config/database.php';
require_once '../auth.php';
require_once '../includes/logger.php';
require_once '../includes/integrations/ErpSync.php';

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Требуется авторизация']);
    exit();
}

if ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'manager') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Недостаточно прав']);
    exit();
}

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

if ($method === 'POST') {
    syncEntity();
} elseif ($method === 'GET' && $action === 'status') {
    getSyncStatus();
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
}

function syncEntity() {
    $data = json_decode(file_get_contents("php://input"), true);
    
    if (empty($data['entity_type']) || empty($data['entity_id'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Entity type and ID are required']);
        return;
    }
    
    try {
        $erpSync = new ErpSync();
        
        $method = 'sync' . ucfirst($data['entity_type']);
        
        if (!method_exists($erpSync, $method)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid entity type']);
            return;
        }
        
        $result = $erpSync->$method($data['entity_id']);
        
        http_response_code(200);
        echo json_encode($result);
    } catch (Exception $e) {
        logger()->error("ERP sync error", ['error' => $e->getMessage()]);
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function getSyncStatus() {
    $entityType = $_GET['entity_type'] ?? '';
    $entityId = $_GET['entity_id'] ?? '';
    
    if (empty($entityType) || empty($entityId)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Entity type and ID are required']);
        return;
    }
    
    try {
        $erpSync = new ErpSync();
        $status = $erpSync->getSyncStatus($entityType, $entityId);
        
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'status' => $status
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}
