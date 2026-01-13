<?php
/**
 * Скрипт для применения миграции исправления колонки status в таблице users
 * Исправляет ошибку "Unknown column 'is_active'"
 */

require_once 'config/database.php';

echo "<h2>🔧 Миграция: Исправление колонки status в таблице users</h2>\n";

try {
    $pdo = connectDatabase();
    
    echo "<p>✅ Подключение к базе данных успешно</p>\n";
    
    // Проверяем текущую структуру таблицы users
    echo "<h3>📋 Текущая структура таблицы users:</h3>\n";
    $columns = $pdo->query("SHOW COLUMNS FROM users")->fetchAll(PDO::FETCH_ASSOC);
    
    $hasStatus = false;
    $hasIsActive = false;
    
    foreach ($columns as $column) {
        $colName = $column['Field'];
        $colType = $column['Type'];
        $colNull = $column['Null'];
        $colDefault = $column['Default'];
        
        echo "<p>• {$colName}: {$colType}" . ($colDefault ? " DEFAULT {$colDefault}" : "") . "</p>\n";
        
        if ($colName === 'status') {
            $hasStatus = true;
        }
        if ($colName === 'is_active') {
            $hasIsActive = true;
        }
    }
    
    // Если есть is_active но нет status, переименовываем
    if ($hasIsActive && !$hasStatus) {
        echo "<p>🔄 Переименовываем колонку 'is_active' в 'status'...</p>\n";
        
        // Сначала обновляем все значения на 'active'
        $pdo->exec("UPDATE users SET is_active = 1 WHERE is_active IS NULL OR is_active = ''");
        
        // Переименовываем и меняем тип колонки
        $pdo->exec("ALTER TABLE users CHANGE is_active status ENUM('active', 'blocked') DEFAULT 'active'");
        
        echo "<p>✅ Колонка успешно переименована в 'status'</p>\n";
    } elseif ($hasStatus) {
        echo "<p>✅ Колонка 'status' уже существует</p>\n";
    } elseif ($hasIsActive) {
        echo "<p>❌ Обнаружена колонка 'is_active', но не удалось выполнить миграцию</p>\n";
    } else {
        echo "<p>⚠️  Колонка 'status' не найдена. Возможно, нужно добавить её вручную.</p>\n";
    }
    
    // Проверяем пользователей
    echo "<h3>👥 Текущие пользователи в системе:</h3>\n";
    $users = $pdo->query("SELECT id, username, email, role, status FROM users")->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($users)) {
        echo "<p>⚠️  Пользователи не найдены. Создаем администратора по умолчанию...</p>\n";
        
        $adminPassword = password_hash('admin123', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password, full_name, phone, role, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute(['admin', 'admin@proftransfer.ru', $adminPassword, 'Администратор', '+79990000001', 'admin', 'active']);
        
        echo "<p>✅ Создан администратор: admin / admin123</p>\n";
    } else {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>ID</th><th>Username</th><th>Email</th><th>Role</th><th>Status</th></tr>";
        
        foreach ($users as $user) {
            // Обновляем статус для всех пользователей
            if (!$user['status']) {
                $pdo->prepare("UPDATE users SET status = 'active' WHERE id = ?")->execute([$user['id']]);
                $user['status'] = 'active';
            }
            
            echo "<tr>";
            echo "<td>" . htmlspecialchars($user['id']) . "</td>";
            echo "<td>" . htmlspecialchars($user['username']) . "</td>";
            echo "<td>" . htmlspecialchars($user['email']) . "</td>";
            echo "<td>" . htmlspecialchars($user['role']) . "</td>";
            echo "<td>" . htmlspecialchars($user['status'] ?? 'active') . "</td>";
            echo "</tr>";
        }
        echo "</table>\n";
    }
    
    echo "<h3>✅ Миграция завершена!</h3>\n";
    echo "<p>🎉 Теперь система должна работать без ошибок 'Unknown column is_active'</p>\n";
    echo "<p>🔗 <a href='login.php'>Попробовать войти в систему</a></p>\n";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Ошибка: " . htmlspecialchars($e->getMessage()) . "</p>\n";
}
?>