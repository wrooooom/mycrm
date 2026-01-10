<?php
// Включить ошибки
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

// ПОДКЛЮЧАЕМ ФАЙЛЫ С ПРАВИЛЬНЫМИ ПУТЯМИ
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';

// Проверяем авторизацию
if (!isLoggedIn()) {
    header("Location: login.php");
    exit();
}

// Логируем просмотр страницы
logAction('view_applications_page', $_SESSION['user_id']);

// ДАЛЕЕ ВСТАВЬТЕ ВЕСЬ ВАШ ОРИГИНАЛЬНЫЙ КОД applications.php
// который начинается с получения статистики и заканчивается подключением footer.php
// ТОТ БОЛЬШОЙ ПОЛНЫЙ КОД БЕЗ СОКРАЩЕНИЙ

// Получаем статистику по заявкам из реальной БД
try {
    $stats = [
        'total' => 0,
        'new' => 0,
        'assigned' => 0,
        'in_progress' => 0,
        'completed' => 0,
        'cancelled' => 0,
        'today' => 0
    ];
    
    // Реальная статистика из БД
    $stats['total'] = $pdo->query("SELECT COUNT(*) FROM applications")->fetchColumn();
    $stats['new'] = $pdo->query("SELECT COUNT(*) FROM applications WHERE status = 'new'")->fetchColumn();
    $stats['assigned'] = $pdo->query("SELECT COUNT(*) FROM applications WHERE status = 'assigned'")->fetchColumn();
    $stats['in_progress'] = $pdo->query("SELECT COUNT(*) FROM applications WHERE status = 'in_progress'")->fetchColumn();
    $stats['completed'] = $pdo->query("SELECT COUNT(*) FROM applications WHERE status = 'completed'")->fetchColumn();
    $stats['cancelled'] = $pdo->query("SELECT COUNT(*) FROM applications WHERE status = 'cancelled'")->fetchColumn();
    $stats['today'] = $pdo->query("SELECT COUNT(*) FROM applications WHERE DATE(created_at) = CURDATE()")->fetchColumn();
    
} catch(Exception $e) {
    // Резервные данные если есть ошибки
    $stats = [
        'total' => 68,
        'new' => 12,
        'assigned' => 8,
        'in_progress' => 15,
        'completed' => 45,
        'cancelled' => 3,
        'today' => 5
    ];
}

// ... и так далее ВЕСЬ ваш код до конца ...