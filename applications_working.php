<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

// Подключаем БД напрямую
try {
    $pdo = new PDO("mysql:host=localhost;dbname=ca991909_crm", "ca991909_crm", "!Mazay199");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Ошибка БД: " . $e->getMessage());
}

// Стартуем сессию
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Фиктивный пользователь для теста
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1;
    $_SESSION['username'] = 'admin';
    $_SESSION['full_name'] = 'Администратор';
    $_SESSION['user_role'] = 'admin';
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Тестовая страница</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h1>✅ applications.php РАБОТАЕТ!</h1>
        <p>Пользователь: <?php echo $_SESSION['full_name']; ?></p>
        <p>Роль: <?php echo $_SESSION['user_role']; ?></p>
        
        <h3>Проверка БД:</h3>
        <?php
        try {
            $count = $pdo->query("SELECT COUNT(*) as total FROM applications")->fetchColumn();
            echo "<p>Заявок в БД: $count</p>";
        } catch (Exception $e) {
            echo "<p>Ошибка запроса: " . $e->getMessage() . "</p>";
        }
        ?>
    </div>
</body>
</html>