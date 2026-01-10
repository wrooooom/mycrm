<?php
require_once 'config.php';
require_once 'auth.php';
requireLogin(); // Требуем авторизацию

// Для административных страниц (companies.php, analytics.php) используйте:
// requireAdmin();
?>
<?php
/**
 * Удаление компании
 */

session_start();
require_once 'config/database.php';

$company_id = $_GET['id'] ?? 0;

if (!$company_id) {
    header('Location: companies.php');
    exit;
}

try {
    $pdo = connectDatabase();
    
    // Проверяем есть ли заявки у компании
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM applications WHERE company_id = ?");
    $stmt->execute([$company_id]);
    $applications_count = $stmt->fetch()['count'];
    
    if ($applications_count > 0) {
        header('Location: companies.php?error=Невозможно удалить компанию - у нее есть активные заявки');
        exit;
    }
    
    // Удаляем компанию
    $stmt = $pdo->prepare("DELETE FROM companies WHERE id = ?");
    $stmt->execute([$company_id]);
    
    header('Location: companies.php?success=Компания успешно удалена');
    exit;
    
} catch (Exception $e) {
    header('Location: companies.php?error=' . urlencode($e->getMessage()));
    exit;
}
?>