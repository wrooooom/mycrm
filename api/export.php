<?php
/**
 * Export API Endpoint
 */

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once '../config/database.php';
require_once '../auth.php';
require_once '../includes/logger.php';
require_once '../includes/integrations/ExportService.php';

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Требуется авторизация']);
    exit();
}

$method = $_SERVER['REQUEST_METHOD'];
$exportType = $_GET['type'] ?? '';
$format = $_GET['format'] ?? 'csv';

if ($method !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

$data = json_decode(file_get_contents("php://input"), true);
$filters = $data['filters'] ?? [];

try {
    $exportService = new ExportService();
    
    switch ($exportType) {
        case 'applications':
            $result = $exportService->exportApplications($format, $filters);
            break;
        case 'drivers':
            $result = $exportService->exportDrivers($format, $filters);
            break;
        case 'vehicles':
            $result = $exportService->exportVehicles($format, $filters);
            break;
        case 'payments':
            $result = $exportService->exportPayments($format, $filters);
            break;
        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid export type']);
            exit();
    }
    
    if ($result['success']) {
        logger()->info("Export created", [
            'type' => $exportType,
            'format' => $format,
            'filename' => $result['filename']
        ]);
        
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'filename' => $result['filename'],
            'download_url' => '/exports/' . $result['filename'],
            'rows' => $result['rows']
        ]);
    } else {
        http_response_code(500);
        echo json_encode($result);
    }
} catch (Exception $e) {
    logger()->error("Export error", ['error' => $e->getMessage()]);
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
