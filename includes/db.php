<?php
// Подключение к базе данных
try {
    $pdo = new PDO("mysql:host=localhost;dbname=ca991909_crm", "ca991909_crm", "!Mazay199");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec("set names utf8");
} catch(PDOException $e) {
    die("Ошибка подключения к базе данных: " . $e->getMessage());
}
?>
