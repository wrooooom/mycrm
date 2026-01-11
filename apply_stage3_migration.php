<?php
/**
 * ЭТАП 3 - Миграция базы данных
 * Применяет все изменения для уведомлений, платежей, отслеживания и ТО
 */
require_once __DIR__ . '/config.php';

echo "=== ЭТАП 3: Применение миграции ===\n\n";

try {
    echo "1. Проверка подключения к базе данных...\n";
    $pdo->query("SELECT 1");
    echo "✅ Подключение успешно\n\n";
    
    // Создаем таблицу notifications
    echo "2. Создание таблицы notifications...\n";
    $pdo->exec("CREATE TABLE IF NOT EXISTS notifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        type VARCHAR(50) NOT NULL,
        title VARCHAR(255) NOT NULL DEFAULT 'Уведомление',
        message TEXT NOT NULL,
        related_type VARCHAR(50) NULL,
        related_id INT NULL,
        is_read TINYINT(1) NOT NULL DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_notifications_user (user_id),
        INDEX idx_notifications_is_read (is_read),
        INDEX idx_notifications_created_at (created_at),
        CONSTRAINT fk_notifications_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "✅ Таблица notifications создана\n\n";
    
    // Создаем таблицу payments
    echo "3. Создание таблицы payments...\n";
    $pdo->exec("CREATE TABLE IF NOT EXISTS payments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        application_id INT NOT NULL,
        user_id INT NULL,
        amount DECIMAL(10,2) NOT NULL,
        status VARCHAR(30) NOT NULL DEFAULT 'pending',
        method VARCHAR(30) NOT NULL DEFAULT 'cash',
        payment_date DATETIME NULL,
        notes TEXT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_payments_application (application_id),
        INDEX idx_payments_user (user_id),
        INDEX idx_payments_status (status),
        INDEX idx_payments_created_at (created_at),
        CONSTRAINT fk_payments_application FOREIGN KEY (application_id) REFERENCES applications(id) ON DELETE CASCADE,
        CONSTRAINT fk_payments_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "✅ Таблица payments создана\n\n";
    
    // Создаем таблицу vehicle_maintenance
    echo "4. Создание таблицы vehicle_maintenance...\n";
    $pdo->exec("CREATE TABLE IF NOT EXISTS vehicle_maintenance (
        id INT AUTO_INCREMENT PRIMARY KEY,
        vehicle_id INT NOT NULL,
        maintenance_type VARCHAR(50) NOT NULL,
        description TEXT,
        cost DECIMAL(10,2) DEFAULT 0,
        mileage INT NULL,
        maintenance_date DATE NOT NULL,
        next_maintenance_date DATE NULL,
        performed_by VARCHAR(255) NULL,
        notes TEXT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_vehicle_maintenance_vehicle (vehicle_id),
        INDEX idx_vehicle_maintenance_date (maintenance_date),
        CONSTRAINT fk_vehicle_maintenance_vehicle FOREIGN KEY (vehicle_id) REFERENCES vehicles(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "✅ Таблица vehicle_maintenance создана\n\n";
    
    // Добавляем координаты в applications
    echo "5. Добавление координат в таблицу applications...\n";
    
    // Проверяем существующие поля
    $columns = $pdo->query("SHOW COLUMNS FROM applications")->fetchAll(PDO::FETCH_COLUMN);
    
    if (!in_array('pickup_lat', $columns)) {
        $pdo->exec("ALTER TABLE applications ADD COLUMN pickup_lat DECIMAL(10,8) NULL AFTER notes");
        echo "✅ Добавлено поле pickup_lat\n";
    } else {
        echo "⚠️ Поле pickup_lat уже существует\n";
    }
    
    if (!in_array('pickup_lon', $columns)) {
        $pdo->exec("ALTER TABLE applications ADD COLUMN pickup_lon DECIMAL(11,8) NULL AFTER pickup_lat");
        echo "✅ Добавлено поле pickup_lon\n";
    } else {
        echo "⚠️ Поле pickup_lon уже существует\n";
    }
    
    if (!in_array('delivery_lat', $columns)) {
        $pdo->exec("ALTER TABLE applications ADD COLUMN delivery_lat DECIMAL(10,8) NULL AFTER pickup_lon");
        echo "✅ Добавлено поле delivery_lat\n";
    } else {
        echo "⚠️ Поле delivery_lat уже существует\n";
    }
    
    if (!in_array('delivery_lon', $columns)) {
        $pdo->exec("ALTER TABLE applications ADD COLUMN delivery_lon DECIMAL(11,8) NULL AFTER delivery_lat");
        echo "✅ Добавлено поле delivery_lon\n";
    } else {
        echo "⚠️ Поле delivery_lon уже существует\n";
    }
    
    echo "\n6. Создание индексов для оптимизации...\n";
    
    // Создаем индексы
    try {
        $pdo->exec("CREATE INDEX idx_applications_pickup_coords ON applications(pickup_lat, pickup_lon)");
        echo "✅ Создан индекс idx_applications_pickup_coords\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate key') !== false) {
            echo "⚠️ Индекс idx_applications_pickup_coords уже существует\n";
        } else {
            echo "❌ Ошибка создания индекса: " . $e->getMessage() . "\n";
        }
    }
    
    try {
        $pdo->exec("CREATE INDEX idx_applications_delivery_coords ON applications(delivery_lat, delivery_lon)");
        echo "✅ Создан индекс idx_applications_delivery_coords\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate key') !== false) {
            echo "⚠️ Индекс idx_applications_delivery_coords уже существует\n";
        } else {
            echo "❌ Ошибка создания индекса: " . $e->getMessage() . "\n";
        }
    }
    
    echo "\n7. Проверка созданных таблиц...\n";
    
    $tables = ['notifications', 'payments', 'vehicle_maintenance'];
    foreach ($tables as $table) {
        $count = $pdo->query("SELECT COUNT(*) FROM $table")->fetchColumn();
        echo "✅ Таблица $table: $count записей\n";
    }
    
    echo "\n🎉 ЭТАП 3 - Миграция успешно завершена!\n\n";
    
    // Финальный отчет
    echo "=== Итоговый отчет ===\n";
    echo "✅ Система уведомлений готова к использованию\n";
    echo "✅ Система платежей готова к использованию\n";
    echo "✅ Отслеживание координат настроено\n";
    echo "✅ Техобслуживание автомобилей подключено\n";
    echo "\nAPI Endpoints:\n";
    echo "  - GET/POST /api/notifications.php\n";
    echo "  - GET/POST /api/payments.php\n";
    echo "\nФункции в includes/functions.php:\n";
    echo "  - sendNotification()\n";
    echo "  - notifyDriverAssignment()\n";
    echo "  - notifyStatusChange()\n";
    
} catch (PDOException $e) {
    echo "❌ ОШИБКА МИГРАЦИИ: " . $e->getMessage() . "\n";
    echo "Трассировка:\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}
?>
