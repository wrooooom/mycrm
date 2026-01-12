<?php
/**
 * Безопасная миграция для добавления полей в таблицу applications
 */
require_once __DIR__ . '/config.php';

echo "Применение безопасной миграции к базе данных...\n";

try {
    // Проверяем текущую структуру таблицы applications
    $columns = $pdo->query("SHOW COLUMNS FROM applications")->fetchAll(PDO::FETCH_ASSOC);
    $existingColumns = array_column($columns, 'Field');
    
    echo "Текущие поля в таблице applications:\n";
    foreach ($existingColumns as $col) {
        echo "- $col\n";
    }
    echo "\n";
    
    // Добавляем недостающие поля по одному
    if (!in_array('payment_status', $existingColumns)) {
        $pdo->exec("ALTER TABLE applications ADD COLUMN payment_status ENUM('pending', 'paid', 'refunded') DEFAULT 'pending' AFTER status");
        echo "✅ Добавлено поле: payment_status\n";
    } else {
        echo "⚠️ Поле payment_status уже существует\n";
    }
    
    if (!in_array('pickup_time', $existingColumns)) {
        $pdo->exec("ALTER TABLE applications ADD COLUMN pickup_time DATETIME NULL AFTER trip_date");
        echo "✅ Добавлено поле: pickup_time\n";
    } else {
        echo "⚠️ Поле pickup_time уже существует\n";
    }
    
    if (!in_array('delivery_time', $existingColumns)) {
        $pdo->exec("ALTER TABLE applications ADD COLUMN delivery_time DATETIME NULL AFTER pickup_time");
        echo "✅ Добавлено поле: delivery_time\n";
    } else {
        echo "⚠️ Поле delivery_time уже существует\n";
    }
    
    // Обновляем enum статус
    try {
        $pdo->exec("ALTER TABLE applications MODIFY COLUMN status ENUM('new', 'assigned', 'in_progress', 'completed', 'cancelled') DEFAULT 'new'");
        echo "✅ Обновлен enum для поля status\n";
    } catch (Exception $e) {
        echo "⚠️ Не удалось обновить enum status: " . $e->getMessage() . "\n";
    }
    
    // Добавляем индексы
    $indexes = $pdo->query("SHOW INDEX FROM applications WHERE Key_name LIKE 'idx_%'")->fetchAll(PDO::FETCH_ASSOC);
    $existingIndexes = array_column($indexes, 'Key_name');
    
    if (!in_array('idx_applications_payment_status', $existingIndexes)) {
        $pdo->exec("CREATE INDEX idx_applications_payment_status ON applications(payment_status)");
        echo "✅ Создан индекс: idx_applications_payment_status\n";
    } else {
        echo "⚠️ Индекс idx_applications_payment_status уже существует\n";
    }
    
    if (!in_array('idx_applications_pickup_time', $existingIndexes)) {
        $pdo->exec("CREATE INDEX idx_applications_pickup_time ON applications(pickup_time)");
        echo "✅ Создан индекс: idx_applications_pickup_time\n";
    } else {
        echo "⚠️ Индекс idx_applications_pickup_time уже существует\n";
    }
    
    if (!in_array('idx_applications_delivery_time', $existingIndexes)) {
        $pdo->exec("CREATE INDEX idx_applications_delivery_time ON applications(delivery_time)");
        echo "✅ Создан индекс: idx_applications_delivery_time\n";
    } else {
        echo "⚠️ Индекс idx_applications_delivery_time уже существует\n";
    }
    
    // Обновляем существующие записи для соответствия новому формату статуса
    $pdo->exec("UPDATE applications SET status = CASE 
        WHEN status = 'confirmed' THEN 'assigned'
        WHEN status = 'inwork' THEN 'in_progress' 
        ELSE status 
    END");
    echo "✅ Обновлены существующие статусы\n";
    
    // Создаем представление
    try {
        $pdo->exec("CREATE OR REPLACE VIEW applications_detailed AS
            SELECT 
                a.*,
                d.first_name as driver_first_name,
                d.last_name as driver_last_name,
                d.phone as driver_phone,
                v.brand as vehicle_brand,
                v.model as vehicle_model,
                v.license_plate as vehicle_plate,
                c.name as customer_company_name,
                ec.name as executor_company_name,
                u.username as creator_name
            FROM applications a
            LEFT JOIN drivers d ON a.driver_id = d.id
            LEFT JOIN vehicles v ON a.vehicle_id = v.id
            LEFT JOIN companies c ON a.customer_company_id = c.id
            LEFT JOIN companies ec ON a.executor_company_id = ec.id
            LEFT JOIN users u ON a.created_by = u.id");
        echo "✅ Создано представление: applications_detailed\n";
    } catch (Exception $e) {
        echo "⚠️ Не удалось создать представление: " . $e->getMessage() . "\n";
    }
    
    echo "\n🎉 Миграция успешно завершена!\n\n";
    
    // Проверяем результат
    $result = $pdo->query("SHOW COLUMNS FROM applications")->fetchAll(PDO::FETCH_ASSOC);
    echo "Обновленная структура таблицы applications:\n";
    foreach ($result as $column) {
        echo "- " . $column['Field'] . " (" . $column['Type'] . ")\n";
    }
    
} catch (Exception $e) {
    echo "❌ Ошибка миграции: " . $e->getMessage() . "\n";
}
?>