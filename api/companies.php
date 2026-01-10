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

// Check permissions
ACL::requirePermission(ACL::canViewCompanies($role), 'У вас нет прав для просмотра компаний');

// Get action from GET or POST
$action = $_GET['action'] ?? ($_POST['action'] ?? '');

// Handle different actions
switch ($action) {
    case 'getAll':
        getAllCompanies();
        break;
    case 'getById':
        getCompanyById();
        break;
    default:
        getAllCompanies();
        break;
}

/**
 * Get all companies
 */
function getAllCompanies() {
    $pdo = connectDatabase();

    try {
        $query = "SELECT * FROM companies WHERE status = 'active' ORDER BY name ASC";
        $stmt = $pdo->prepare($query);
        $stmt->execute();

        $companies = $stmt->fetchAll(PDO::FETCH_ASSOC);

        http_response_code(200);
        echo json_encode([
            'success' => true,
            'data' => $companies
        ]);

    } catch (PDOException $e) {
        error_log("Companies fetch error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Ошибка получения компаний'
        ]);
    }
}

/**
 * Get single company by ID
 */
function getCompanyById() {
    $pdo = connectDatabase();
    $companyId = $_GET['id'] ?? null;

    if (!$companyId) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Не указан ID компании'
        ]);
        return;
    }

    try {
        $query = "SELECT * FROM companies WHERE id = :id";
        $stmt = $pdo->prepare($query);
        $stmt->execute([':id' => $companyId]);
        $company = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$company) {
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'message' => 'Компания не найдена'
            ]);
            return;
        }

        http_response_code(200);
        echo json_encode([
            'success' => true,
            'data' => $company
        ]);

    } catch (PDOException $e) {
        error_log("Company fetch error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Ошибка получения компании'
        ]);
    }
}
?>
