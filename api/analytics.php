<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
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

// GET - Получение аналитики
if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    $user = validateUser($db);
    if (!$user) {
        sendResponse(false, 'Не авторизован', null, 401);
    }
    
    $type = isset($_GET['type']) ? $_GET['type'] : 'general';
    $period = isset($_GET['period']) ? $_GET['period'] : 'month';
    
    try {
        $analytics = [];
        
        switch($type) {
            case 'general':
                $analytics = getGeneralAnalytics($db, $period);
                break;
            case 'drivers':
                $analytics = getDriversAnalytics($db, $period);
                break;
            case 'vehicles':
                $analytics = getVehiclesAnalytics($db, $period);
                break;
            case 'users':
                $analytics = getUsersAnalytics($db, $period);
                break;
            case 'companies':
                $analytics = getCompaniesAnalytics($db, $period);
                break;
            case 'finance':
                $analytics = getFinanceAnalytics($db, $period);
                break;
            default:
                $analytics = getGeneralAnalytics($db, $period);
        }
        
        sendResponse(true, 'Аналитика получена', $analytics);
        
    } catch (Exception $e) {
        sendResponse(false, 'Ошибка получения аналитики: ' . $e->getMessage(), null, 500);
    }
}

// Общая аналитика
function getGeneralAnalytics($db, $period) {
    // Статистика по заявкам
    $applicationsQuery = "SELECT 
        COUNT(*) as total_applications,
        SUM(CASE WHEN status = 'new' THEN 1 ELSE 0 END) as new_applications,
        SUM(CASE WHEN status = 'confirmed' THEN 1 ELSE 0 END) as confirmed_applications,
        SUM(CASE WHEN status = 'inwork' THEN 1 ELSE 0 END) as inwork_applications,
        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_applications,
        SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_applications,
        AVG(order_amount) as avg_cost
    FROM applications";
    
    $stmt = $db->prepare($applicationsQuery);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    return [
        'total_applications' => intval($result['total_applications']),
        'new_applications' => intval($result['new_applications']),
        'confirmed_applications' => intval($result['confirmed_applications']),
        'inwork_applications' => intval($result['inwork_applications']),
        'completed_applications' => intval($result['completed_applications']),
        'cancelled_applications' => intval($result['cancelled_applications']),
        'avg_cost' => floatval($result['avg_cost']),
        'monthly_growth' => 15 // Процент роста
    ];
}

// Аналитика по водителям
function getDriversAnalytics($db, $period) {
    $query = "SELECT 
        COUNT(*) as total_drivers,
        SUM(CASE WHEN status = 'work' THEN 1 ELSE 0 END) as active_drivers,
        SUM(CASE WHEN status = 'vacation' THEN 1 ELSE 0 END) as vacation_drivers,
        AVG(rating) as avg_rating,
        SUM(total_earnings) as total_earnings
    FROM drivers";
    
    $stmt = $db->prepare($query);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Средний заказ на водителя
    $avgOrderQuery = "SELECT AVG(order_amount) as avg_order FROM applications WHERE driver_id IS NOT NULL";
    $avgStmt = $db->prepare($avgOrderQuery);
    $avgStmt->execute();
    $avgResult = $avgStmt->fetch(PDO::FETCH_ASSOC);
    
    return [
        'total_drivers' => intval($result['total_drivers']),
        'active_drivers' => intval($result['active_drivers']),
        'vacation_drivers' => intval($result['vacation_drivers']),
        'avg_rating' => floatval($result['avg_rating']),
        'total_earnings' => floatval($result['total_earnings']),
        'avg_driver_order' => floatval($avgResult['avg_order'])
    ];
}

// Аналитика по автомобилям
function getVehiclesAnalytics($db, $period) {
    $query = "SELECT 
        COUNT(*) as total_vehicles,
        SUM(CASE WHEN status = 'working' THEN 1 ELSE 0 END) as working_vehicles,
        SUM(CASE WHEN status = 'repair' THEN 1 ELSE 0 END) as repair_vehicles,
        AVG(mileage) as avg_mileage
    FROM vehicles";
    
    $stmt = $db->prepare($query);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Самый популярный класс
    $classQuery = "SELECT class, COUNT(*) as count FROM vehicles GROUP BY class ORDER BY count DESC LIMIT 1";
    $classStmt = $db->prepare($classQuery);
    $classStmt->execute();
    $classResult = $classStmt->fetch(PDO::FETCH_ASSOC);
    
    return [
        'total_vehicles' => intval($result['total_vehicles']),
        'working_vehicles' => intval($result['working_vehicles']),
        'repair_vehicles' => intval($result['repair_vehicles']),
        'avg_mileage' => intval($result['avg_mileage']),
        'total_costs' => 156000, // Статические данные для демо
        'popular_class' => $classResult['class'] ?? 'comfort'
    ];
}

// Аналитика по пользователям
function getUsersAnalytics($db, $period) {
    $query = "SELECT 
        COUNT(*) as total_users,
        SUM(CASE WHEN role = 'admin' THEN 1 ELSE 0 END) as admin_users,
        SUM(CASE WHEN role = 'manager' THEN 1 ELSE 0 END) as manager_users,
        SUM(CASE WHEN role = 'driver' THEN 1 ELSE 0 END) as driver_users,
        SUM(CASE WHEN role = 'client' THEN 1 ELSE 0 END) as client_users
    FROM users WHERE status = 'active'";
    
    $stmt = $db->prepare($query);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    return [
        'total_users' => intval($result['total_users']),
        'admin_users' => intval($result['admin_users']),
        'manager_users' => intval($result['manager_users']),
        'driver_users' => intval($result['driver_users']),
        'client_users' => intval($result['client_users']),
        'active_today' => intval($result['total_users'] * 0.7) // 70% активны сегодня
    ];
}

// Аналитика по компаниям
function getCompaniesAnalytics($db, $period) {
    $query = "SELECT 
        COUNT(*) as total_companies,
        SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_companies
    FROM companies";
    
    $stmt = $db->prepare($query);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Статистика по заказам компаний
    $ordersQuery = "SELECT 
        c.name,
        COUNT(a.id) as orders,
        SUM(a.order_amount) as total_amount,
        AVG(a.order_amount) as avg_check
    FROM companies c
    LEFT JOIN applications a ON c.id = a.customer_company_id
    GROUP BY c.id
    ORDER BY total_amount DESC
    LIMIT 1";
    
    $ordersStmt = $db->prepare($ordersQuery);
    $ordersStmt->execute();
    $bestClient = $ordersStmt->fetch(PDO::FETCH_ASSOC);
    
    return [
        'total_companies' => intval($result['total_companies']),
        'active_companies' => intval($result['active_companies']),
        'corporate_clients' => 8, // Статические данные
        'avg_company_check' => 3850, // Статические данные
        'total_company_revenue' => 1560000, // Статические данные
        'best_client' => $bestClient['name'] ?? 'ООО Газпром трансфер'
    ];
}

// Финансовая аналитика
function getFinanceAnalytics($db, $period) {
    // Выручка
    $revenueQuery = "SELECT SUM(order_amount) as total_revenue FROM applications WHERE status = 'completed'";
    $revenueStmt = $db->prepare($revenueQuery);
    $revenueStmt->execute();
    $revenue = $revenueStmt->fetch(PDO::FETCH_ASSOC);
    
    $totalRevenue = floatval($revenue['total_revenue']) || 890000;
    $totalExpenses = 356000; // Статические данные
    $totalProfit = $totalRevenue - $totalExpenses;
    $totalTaxes = $totalProfit * 0.2; // 20% налогов
    $netProfit = $totalProfit - $totalTaxes;
    $profitability = ($netProfit / $totalRevenue) * 100;
    
    return [
        'total_revenue' => $totalRevenue,
        'total_expenses' => $totalExpenses,
        'total_profit' => $totalProfit,
        'total_taxes' => $totalTaxes,
        'net_profit' => $netProfit,
        'profitability' => $profitability
    ];
}
?>