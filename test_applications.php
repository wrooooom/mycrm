<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Тестовая страница applications.php</h1>";
echo "<p>Если вы видите этот текст, значит PHP работает.</p>";

// Простая проверка БД
try {
    $pdo = new PDO("mysql:host=localhost;dbname=ca991909_crm", "ca991909_crm", "!Mazay199");
    echo "<p style='color: green;'>✅ Подключение к БД успешно</p>";
} catch (PDOException $e) {
    echo "<p style='color: red;'>❌ Ошибка БД: " . $e->getMessage() . "</p>";
}

// Простая проверка сессии
session_start();
echo "<p>Статус сессии: " . session_status() . "</p>";
echo "<p>ID сессии: " . session_id() . "</p>";
?>