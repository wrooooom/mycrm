<?php
// ВРЕМЕННЫЙ ФАЙЛ ДЛЯ ТЕСТИРОВАНИЯ - УБРАТЬ ПОСЛЕ РАБОТЫ

// Включить все ошибки
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

// Сначала стартуем сессию
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Подключаем БД напрямую
try {
    $pdo = new PDO("mysql:host=localhost;dbname=ca991909_crm", "ca991909_crm", "!Mazay199");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec("set names utf8");
} catch(PDOException $e) {
    die("Ошибка подключения к базе данных: " . $e->getMessage());
}

// Простые функции для теста
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

function logAction($action, $user_id = null) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("INSERT INTO activity_log (user_id, action, ip_address, user_agent) VALUES (?, ?, ?, ?)");
        $stmt->execute([
            $user_id ?? ($_SESSION['user_id'] ?? null),
            $action,
            $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
        ]);
    } catch(Exception $e) {
        // Игнорируем ошибки
    }
}

// Для теста - установим фиктивного пользователя
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1;
    $_SESSION['username'] = 'testuser';
    $_SESSION['full_name'] = 'Тестовый Пользователь';
    $_SESSION['user_role'] = 'admin';
}

logAction('view_applications_page', $_SESSION['user_id']);

// Дальше ваш полный код applications.php который я отправлял ранее
// ВСТАВЬТЕ СЮДА ВЕСЬ ТОТ БОЛЬШОЙ КОД applications.php который я вам отправлял
// начиная с получения статистики и заканчивая подключением footer.php
?>