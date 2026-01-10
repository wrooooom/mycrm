<?php
require_once 'config.php';
require_once 'auth.php';
requireLogin(); // Требуем авторизацию

// Для административных страниц (companies.php, analytics.php) используйте:
// requireAdmin();
?>
<?php
/**
 * Обработчик сохранения новой заявки
 */

session_start();
require_once 'config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: applications.php');
    exit;
}

try {
    $pdo = connectDatabase();
    
    // Подготавливаем данные
    $data = [
        'application_number' => $_POST['application_number'],
        'company_id' => $_POST['company_id'] ?: null,
        'driver_id' => $_POST['driver_id'] ?: null,
        'vehicle_id' => $_POST['vehicle_id'] ?: null,
        'pickup_address' => $_POST['pickup_address'],
        'destination_address' => $_POST['destination_address'],
        'passenger_name' => $_POST['passenger_name'],
        'passenger_phone' => $_POST['passenger_phone'],
        'scheduled_date' => $_POST['scheduled_date'],
        'status' => $_POST['status'],
        'price' => $_POST['price'] ?: null,
        'notes' => $_POST['notes'] ?: null
    ];
    
    // Вставляем заявку в БД
    $sql = "INSERT INTO applications (
        application_number, company_id, driver_id, vehicle_id,
        pickup_address, destination_address, passenger_name, passenger_phone,
        scheduled_date, status, price, notes
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute(array_values($data));
    
    // Перенаправляем с сообщением об успехе
    header('Location: applications.php?success=1');
    exit;
    
} catch (Exception $e) {
    // Перенаправляем с ошибкой
    header('Location: applications.php?error=' . urlencode($e->getMessage()));
    exit;
}