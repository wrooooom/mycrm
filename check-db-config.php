<?php
require_once 'config.php';
require_once 'auth.php';
requireLogin(); // Требуем авторизацию

// Для административных страниц (companies.php, analytics.php) используйте:
// requireAdmin();
?>
<?php
/**
 * Проверка конфигурации базы данных
 */

echo "<!DOCTYPE html>
<html lang='ru'>
<head>
    <meta charset='UTF-8'>
    <title>Проверка конфигурации БД</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .info { background: #e3f2fd; padding: 10px; margin: 10px 0; }
        .success { background: #e8f5e8; padding: 10px; margin: 10px 0; }
        .error { background: #ffebee; padding: 10px; margin: 10px 0; }
    </style>
</head>
<body>
    <h1>Проверка конфигурации базы данных</h1>";

// Проверяем существование защищенного config
$protected_path = __DIR__ . '/protected/config.php';

if (!file_exists($protected_path)) {
    echo "<div class='error'>
            <h3>❌ Файл конфигурации не найден</h3>
            <p>Путь: {$protected_path}</p>
            <p>Создайте файл protected/config.php</p>
          </div>";
} else {
    echo "<div class='success'>
            <h3>✅ Файл конфигурации найден</h3>
            <p>Путь: {$protected_path}</p>
          </div>";
    
    // Читаем конфиг для проверки
    $config_content = file_get_contents($protected_path);
    
    // Извлекаем данные БД из конфига
    preg_match("/define\('DB_HOST', '([^']+)'/", $config_content, $host_match);
    preg_match("/define\('DB_NAME', '([^']+)'/", $config_content, $dbname_match);
    preg_match("/define\('DB_USER', '([^']+)'/", $config_content, $user_match);
    preg_match("/define\('DB_PASS', '([^']+)'/", $config_content, $pass_match);
    
    $db_host = $host_match[1] ?? 'Не найден';
    $db_name = $dbname_match[1] ?? 'Не найден';
    $db_user = $user_match[1] ?? 'Не найден';
    $db_pass = $pass_match[1] ?? 'Не найден';
    
    echo "<div class='info'>
            <h3>📊 Данные из конфигурации:</h3>
            <p><strong>Хост:</strong> {$db_host}</p>
            <p><strong>База данных:</strong> {$db_name}</p>
            <p><strong>Пользователь:</strong> {$db_user}</p>
            <p><strong>Пароль:</strong> " . str_repeat('*', strlen($db_pass)) . " (длина: " . strlen($db_pass) . ")</p>
          </div>";
}

// Проверяем доступные методы подключения
echo "<div class='info'>
        <h3>🔧 Проверка методов подключения:</h3>";

// Проверяем MySQLi
if (function_exists('mysqli_connect')) {
    echo "<p>✅ MySQLi доступен</p>";
} else {
    echo "<p>❌ MySQLi недоступен</p>";
}

// Проверяем PDO
if (class_exists('PDO')) {
    $pdo_drivers = PDO::getAvailableDrivers();
    echo "<p>✅ PDO доступен. Драйверы: " . implode(', ', $pdo_drivers) . "</p>";
} else {
    echo "<p>❌ PDO недоступен</p>";
}

echo "</div>";

// Простой тест подключения с разными параметрами
echo "<div class='info'>
        <h3>🧪 Тестовые подключения:</h3>";

// Тест 1: Текущие настройки
if (file_exists($protected_path)) {
    define('PROTECTED_ACCESS', true);
    require_once $protected_path;
    
    try {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        $pdo = new PDO($dsn, DB_USER, DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        echo "<p style='color:green;'>✅ Подключение с текущими настройками: УСПЕХ</p>";
    } catch (PDOException $e) {
        echo "<p style='color:red;'>❌ Подключение с текущими настройками: " . $e->getMessage() . "</p>";
    }
}

echo "</div>";

echo "</body></html>";
?>