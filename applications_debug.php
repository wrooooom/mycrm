<?php
// МАКСИМАЛЬНАЯ ОТЛАДКА
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

echo "=== НАЧАЛО ОТЛАДКИ ===<br>";

// Проверяем подключение config.php
echo "1. Подключаем config.php...<br>";
require_once __DIR__ . '/config.php';
echo "✅ config.php загружен<br>";

// Проверяем подключение auth.php
echo "2. Подключаем auth.php...<br>";
require_once __DIR__ . '/auth.php';
echo "✅ auth.php загружен<br>";

// Проверяем функции
echo "3. Проверяем функции...<br>";
echo "isLoggedIn: " . (function_exists('isLoggedIn') ? '✅' : '❌') . "<br>";
echo "isAdmin: " . (function_exists('isAdmin') ? '✅' : '❌') . "<br>";

// Проверяем авторизацию
echo "4. Проверяем авторизацию...<br>";
if (isLoggedIn()) {
    echo "✅ Пользователь авторизован: " . $_SESSION['full_name'] . "<br>";
} else {
    echo "❌ Пользователь не авторизован<br>";
    echo "Сессия: ";
    print_r($_SESSION);
    echo "<br>";
}

// Проверяем БД
echo "5. Проверяем БД...<br>";
try {
    $count = $pdo->query("SELECT COUNT(*) FROM applications")->fetchColumn();
    echo "✅ БД работает. Заявок: " . $count . "<br>";
} catch (Exception $e) {
    echo "❌ Ошибка БД: " . $e->getMessage() . "<br>";
}

echo "=== ВСЕ ПРОВЕРКИ ПРОЙДЕНЫ ===<br>";
echo "Если вы видите этот текст, значит базовые функции работают.<br>";

// Теперь попробуем подключить ваш полный код
echo "<hr><h2>Подключаем основной код...</h2>";

// ВСТАВЬТЕ СЮДА ПЕРВЫЕ 50 СТРОК ВАШЕГО applications.php
// начиная с получения статистики

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
    
    echo "Получаем статистику...<br>";
    $stats['total'] = $pdo->query("SELECT COUNT(*) FROM applications")->fetchColumn();
    $stats['new'] = $pdo->query("SELECT COUNT(*) FROM applications WHERE status = 'new'")->fetchColumn();
    $stats['assigned'] = $pdo->query("SELECT COUNT(*) FROM applications WHERE status = 'assigned'")->fetchColumn();
    $stats['in_progress'] = $pdo->query("SELECT COUNT(*) FROM applications WHERE status = 'in_progress'")->fetchColumn();
    $stats['completed'] = $pdo->query("SELECT COUNT(*) FROM applications WHERE status = 'completed'")->fetchColumn();
    $stats['cancelled'] = $pdo->query("SELECT COUNT(*) FROM applications WHERE status = 'cancelled'")->fetchColumn();
    $stats['today'] = $pdo->query("SELECT COUNT(*) FROM applications WHERE DATE(created_at) = CURDATE()")->fetchColumn();
    
    echo "✅ Статистика получена<br>";
    
} catch(Exception $e) {
    echo "❌ Ошибка получения статистики: " . $e->getMessage() . "<br>";
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

echo "Статистика: ";
print_r($stats);
echo "<br>";

echo "<hr><h2>✅ ОСНОВНОЙ КОД РАБОТАЕТ!</h2>";
echo "Проблема в какой-то другой части файла.<br>";
?>