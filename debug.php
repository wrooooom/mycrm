<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

echo "<h1>Debug информация</h1>";

// Проверяем базовый PHP
echo "PHP работает: ✅<br>";

// Проверяем сессию
session_start();
echo "Сессия: " . session_status() . "<br>";

// Проверяем БД
try {
    $pdo = new PDO("mysql:host=localhost;dbname=ca991909_crm", "ca991909_crm", "!Mazay199");
    echo "БД: ✅<br>";
} catch (Exception $e) {
    echo "БД: ❌ " . $e->getMessage() . "<br>";
}

// Проверяем подключение config.php
echo "Подключаем config.php...<br>";
require_once 'config.php';
echo "config.php: ✅<br>";

// Проверяем подключение auth.php  
echo "Подключаем auth.php...<br>";
require_once 'auth.php';
echo "auth.php: ✅<br>";

echo "<h2>Все проверки пройдены</h2>";
?>