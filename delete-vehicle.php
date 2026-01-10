<?php
require_once 'config.php';
require_once 'auth.php';
requireLogin(); // Требуем авторизацию

// Для административных страниц (companies.php, analytics.php) используйте:
// requireAdmin();
?>
<?php
/**
 * Удаление транспортного средства
 */

session_start();
require_once 'config/database.php';

$vehicle_id = $_GET['id'] ?? 0;

if (!$vehicle_id) {
    header('Location: vehicles.php');
    exit;
}

try {
    $pdo = connectDatabase();
    
    // Проверяем есть ли заявки у транспорта
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM applications WHERE vehicle_id = ?");
    $stmt->execute([$vehicle_id]);
    $applications_count = $stmt->fetch()['count'];
    
    if ($applications_count > 0) {
        header('Location: vehicles.php?error=Невозможно удалить транспорт - у него есть активные заявки');
        exit;
    }
    
    // Удаляем связи с водителями
    $pdo->prepare("DELETE FROM driver_vehicles WHERE vehicle_id = ?")->execute([$vehicle_id]);
    
    // Удаляем транспорт
    $stmt = $pdo->prepare("DELETE FROM vehicles WHERE id = ?");
    $stmt->execute([$vehicle_id]);
    
    header('Location: vehicles.php?success=Транспорт успешно удален');
    exit;
    
} catch (Exception $e) {
    header('Location: vehicles.php?error=' . urlencode($e->getMessage()));
    exit;
}
?>