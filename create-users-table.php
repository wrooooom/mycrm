<?php
require_once 'config.php';
require_once 'auth.php';
requireLogin(); // Требуем авторизацию

// Для административных страниц (companies.php, analytics.php) используйте:
// requireAdmin();
?>
<?php
/**
 * Создание таблицы пользователей
 */

session_start();
require_once 'config/database.php';

try {
    $pdo = connectDatabase();
    
    // Создаем таблицу пользователей
    $sql = "CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(100) UNIQUE NOT NULL,
        email VARCHAR(255) UNIQUE NOT NULL,
        password_hash VARCHAR(255) NOT NULL,
        full_name VARCHAR(255) NOT NULL,
        role ENUM('admin', 'dispatcher', 'manager') DEFAULT 'dispatcher',
        is_active BOOLEAN DEFAULT TRUE,
        last_login TIMESTAMP NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $pdo->exec($sql);
    
    // Добавляем администратора по умолчанию
    $admin_password = password_hash('admin123', PASSWORD_DEFAULT);
    
    $stmt = $pdo->prepare("
        INSERT IGNORE INTO users (username, email, password_hash, full_name, role) 
        VALUES (?, ?, ?, ?, ?)
    ");
    
    $stmt->execute(['admin', 'admin@crmproftransfer.ru', $admin_password, 'Администратор', 'admin']);
    
    echo "<div style='padding: 20px; background: #e8f5e8; color: #388e3c; border-radius: 5px;'>
            ✅ Таблица пользователей создана успешно!<br>
            🔑 Логин: <strong>admin</strong><br>
            🔒 Пароль: <strong>admin123</strong><br>
            ⚠️ Смените пароль после первого входа!
          </div>";
    
} catch (Exception $e) {
    echo "<div style='padding: 20px; background: #ffebee; color: #d32f2f; border-radius: 5px;'>
            ❌ Ошибка: " . $e->getMessage() . "
          </div>";
}
?>