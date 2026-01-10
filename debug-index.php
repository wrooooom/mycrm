<?php
require_once 'config.php';
require_once 'auth.php';
requireLogin(); // Требуем авторизацию

// Для административных страниц (companies.php, analytics.php) используйте:
// requireAdmin();
?>
<?php
// Простой файл для диагностики ошибок
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html>
<html lang='ru'>
<head>
    <meta charset='UTF-8'>
    <title>Диагностика</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; }
        .success { background: #e8f5e8; padding: 20px; margin: 10px 0; }
        .error { background: #ffebee; padding: 20px; margin: 10px 0; }
    </style>
</head>
<body>
    <h1>🔧 Диагностика CRM системы</h1>";

// Проверяем базовый PHP
echo "<div class='success'><strong>✅ PHP работает</strong><br>Версия: " . phpversion() . "</div>";

// Проверяем сессии
session_start();
echo "<div class='success'><strong>✅ Сессии работают</strong></div>";

// Проверяем подключение к БД
try {
    require_once 'config/database.php';
    $pdo = connectDatabase();
    echo "<div class='success'><strong>✅ База данных подключена</strong></div>";
    
    // Проверяем таблицы
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    echo "<div class='success'><strong>✅ Таблицы в БД:</strong><br>" . implode(', ', $tables) . "</div>";
    
} catch (Exception $e) {
    echo "<div class='error'><strong>❌ Ошибка БД:</strong> " . $e->getMessage() . "</div>";
}

echo "<hr>
      <h2>📋 Проверка файлов:</h2>
      <ul>
        <li><a href='index.php'>index.php</a> - главная страница</li>
        <li><a href='applications.php'>applications.php</a> - заявки</li>
        <li><a href='test-db.php'>test-db.php</a> - тест БД</li>
      </ul>";

echo "</body></html>";
?>