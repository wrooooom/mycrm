<?php
require_once 'config.php';
require_once 'auth.php';
requireLogin(); // Требуем авторизацию

// Для административных страниц (companies.php, analytics.php) используйте:
// requireAdmin();
?>
<?php
/**
 * Удаление водителя
 */

session_start();
require_once 'config/database.php';

$driver_id = $_GET['id'] ?? 0;

if (!$driver_id) {
    header('Location: drivers.php');
    exit;
}

try {
    $pdo = connectDatabase();
    
    // Проверяем есть ли заявки у водителя
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM applications WHERE driver_id = ?");
    $stmt->execute([$driver_id]);
    $applications_count = $stmt->fetch()['count'];
    
    if ($applications_count > 0) {
        header('Location: drivers.php?error=Невозможно удалить водителя - у него есть активные заявки');
        exit;
    }
    
    // Удаляем водителя
    $stmt = $pdo->prepare("DELETE FROM drivers WHERE id = ?");
    $stmt->execute([$driver_id]);
    
    header('Location: drivers.php?success=Водитель успешно удален');
    exit;
    
} catch (Exception $e) {
    header('Location: drivers.php?error=' . urlencode($e->getMessage()));
    exit;
}
?>