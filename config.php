<?php
session_start();

// Настройки базы данных
define('DB_HOST', 'localhost');
define('DB_USER', 'ca991909_crm');
define('DB_PASS', '!Mazay199');
define('DB_NAME', 'ca991909_crm');

// Подключение к БД
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec("set names utf8");

    // Проверяем и создаем таблицы если нужно
    $tables = $pdo->query("SHOW TABLES LIKE 'users'")->fetchAll();
    if (count($tables) == 0) {
        $createUsers = "CREATE TABLE users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(100) NOT NULL UNIQUE,
            email VARCHAR(255) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            full_name VARCHAR(255) NOT NULL,
            phone VARCHAR(20),
            role ENUM('admin','manager','driver','client') DEFAULT 'client',
            company_id INT,
            status ENUM('active','blocked') DEFAULT 'active',
            last_login TIMESTAMP NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )";
        $pdo->exec($createUsers);

        // Создаем администратора
        $hashedPassword = password_hash('admin123', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password, full_name, phone, role, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute(['admin', 'admin@example.com', $hashedPassword, 'Главный администратор', '+79990000001', 'admin', 'active']);
    }

    // Hotfix: legacy DBs may have `name` instead of `username`
    try {
        $columns = $pdo->query("SHOW COLUMNS FROM users")->fetchAll(PDO::FETCH_ASSOC);
        $fields = array_column($columns, 'Field');
        if (!in_array('username', $fields, true) && in_array('name', $fields, true)) {
            $pdo->exec("ALTER TABLE users CHANGE COLUMN name username VARCHAR(255) NOT NULL");
        }
    } catch (Exception $e) {
        // ignore
    }

} catch (PDOException $e) {
    die("Ошибка подключения к базе данных: " . $e->getMessage());
}

// Функция для логирования действий
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
    } catch (Exception $e) {
        // Игнорируем ошибки логирования чтобы не ломать основной функционал
    }
}

// Автоматическое создание таблицы для логов если не существует
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS activity_log (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NULL,
        action TEXT NOT NULL,
        ip_address VARCHAR(45),
        user_agent TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
    )");
} catch (Exception $e) {
    // Таблица уже существует или нет прав для создания
}
?>
