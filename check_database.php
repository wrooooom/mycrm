<?php
session_start();

// Настройки базы данных
define('DB_HOST', 'localhost');
define('DB_USER', 'ca991909_crm');
define('DB_PASS', '!Mazay199');
define('DB_NAME', 'ca991909_crm');

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h2>Проверка базы данных</h2>";
    
    // Проверяем структуру таблицы users
    echo "<h3>Структура таблицы users:</h3>";
    $columns = $pdo->query("DESCRIBE users")->fetchAll();

    // Hotfix: legacy DBs may have `name` instead of `username`
    $fields = array_column($columns, 'Field');
    if (!in_array('username', $fields, true) && in_array('name', $fields, true)) {
        $pdo->exec("ALTER TABLE users CHANGE COLUMN name username VARCHAR(255) NOT NULL");
        $columns = $pdo->query("DESCRIBE users")->fetchAll();
        echo "<p style='color: green;'>✓ Колонка name переименована в username</p>";
    }

    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Поле</th><th>Тип</th><th>NULL</th><th>Ключ</th><th>По умолчанию</th></tr>";
    foreach ($columns as $col) {
        echo "<tr>";
        echo "<td>" . $col['Field'] . "</td>";
        echo "<td>" . $col['Type'] . "</td>";
        echo "<td>" . $col['Null'] . "</td>";
        echo "<td>" . $col['Key'] . "</td>";
        echo "<td>" . $col['Default'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Проверяем есть ли колонка password
    $hasPassword = false;
    foreach ($columns as $col) {
        if ($col['Field'] == 'password') {
            $hasPassword = true;
            break;
        }
    }
    
    if (!$hasPassword) {
        echo "<p style='color: red;'>✗ Колонка 'password' отсутствует в таблице users</p>";
        echo "<p>Добавляем колонку password...</p>";
        
        // Добавляем колонку password
        $pdo->exec("ALTER TABLE users ADD COLUMN password VARCHAR(255) NOT NULL AFTER username");
        echo "<p style='color: green;'>✓ Колонка password добавлена</p>";
        
        // Перепроверяем структуру
        $columns = $pdo->query("DESCRIBE users")->fetchAll();
        echo "<h3>Обновленная структура таблицы users:</h3>";
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>Поле</th><th>Тип</th><th>NULL</th><th>Ключ</th><th>По умолчанию</th></tr>";
        foreach ($columns as $col) {
            echo "<tr>";
            echo "<td>" . $col['Field'] . "</td>";
            echo "<td>" . $col['Type'] . "</td>";
            echo "<td>" . $col['Null'] . "</td>";
            echo "<td>" . $col['Key'] . "</td>";
            echo "<td>" . $col['Default'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // Проверяем пользователей
    $users = $pdo->query("SELECT COUNT(*) as count FROM users")->fetch();
    echo "<p>Пользователей в системе: " . $users['count'] . "</p>";
    
    if ($users['count'] == 0) {
        echo "<p style='color: orange;'>⚠ В системе нет пользователей</p>";
        echo "<p>Создаем администратора...</p>";
        
        // Создаем администратора
        $hashedPassword = password_hash('admin123', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (username, password, email, full_name, role) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute(['admin', $hashedPassword, 'admin@example.com', 'Главный администратор', 'admin']);
        
        echo "<p style='color: green;'>✓ Администратор создан (логин: admin, пароль: admin123)</p>";
    }
    
    // Показываем список пользователей
    $userList = $pdo->query("SELECT id, username, email, full_name, role, created_at FROM users")->fetchAll();
    echo "<h3>Список пользователей:</h3>";
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>Логин</th><th>Имя</th><th>Email</th><th>Роль</th><th>Создан</th></tr>";
    foreach ($userList as $user) {
        echo "<tr>";
        echo "<td>" . $user['id'] . "</td>";
        echo "<td>" . $user['username'] . "</td>";
        echo "<td>" . $user['full_name'] . "</td>";
        echo "<td>" . $user['email'] . "</td>";
        echo "<td>" . $user['role'] . "</td>";
        echo "<td>" . $user['created_at'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Проверяем пароль администратора
    $admin = $pdo->query("SELECT username, password FROM users WHERE username = 'admin'")->fetch();
    if ($admin) {
        echo "<h3>Проверка администратора:</h3>";
        echo "<p>Логин: " . $admin['username'] . "</p>";
        echo "<p>Длина хэша пароля: " . strlen($admin['password']) . "</p>";
        
        // Проверяем пароль
        $testPassword = 'admin123';
        if (password_verify($testPassword, $admin['password'])) {
            echo "<p style='color: green;'>✓ Пароль admin123 корректный</p>";
        } else {
            echo "<p style='color: red;'>✗ Пароль admin123 не подходит</p>";
            echo "<p>Сбрасываем пароль...</p>";
            
            // Сбрасываем пароль администратора
            $newHash = password_hash('admin123', PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE username = 'admin'");
            $stmt->execute([$newHash]);
            echo "<p style='color: green;'>✓ Пароль администратора сброшен на 'admin123'</p>";
        }
    }
    
} catch(PDOException $e) {
    echo "<p style='color: red;'>Ошибка: " . $e->getMessage() . "</p>";
    
    // Показываем дополнительную информацию об ошибке
    if (strpos($e->getMessage(), 'password') !== false) {
        echo "<p><strong>Решение:</strong> Нужно добавить колонку password в таблицу users</p>";
        echo "<p>Выполните в phpMyAdmin:</p>";
        echo "<pre>ALTER TABLE users ADD COLUMN password VARCHAR(255) NOT NULL AFTER username;</pre>";
    }
}
?>

<p><a href="login.php">Перейти к авторизации</a></p>
<p><a href="index.php">Перейти на главную</a></p>

<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    table { border-collapse: collapse; margin: 10px 0; }
    th, td { padding: 8px 12px; border: 1px solid #ddd; }
    th { background-color: #f5f5f5; }
</style>