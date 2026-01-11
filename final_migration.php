<?php
/**
 * Правильная миграция enum в MySQL
 */
require_once __DIR__ . '/config.php';

echo "Правильная миграция enum в таблице applications...\n";

try {
    // 1. Добавляем новые значения в enum
    echo "1. Добавляем новые значения в enum...\n";
    $pdo->exec("ALTER TABLE applications MODIFY COLUMN status ENUM('new','confirmed','inwork','completed','cancelled','assigned','in_progress') DEFAULT 'new'");
    echo "✅ Новые значения добавлены в enum\n";
    
    // 2. Обновляем данные
    echo "2. Обновляем данные...\n";
    $pdo->exec("UPDATE applications SET status = 'assigned' WHERE status = 'confirmed'");
    $pdo->exec("UPDATE applications SET status = 'in_progress' WHERE status = 'inwork'");
    echo "✅ Данные обновлены\n";
    
    // 3. Удаляем старые значения из enum
    echo "3. Удаляем старые значения из enum...\n";
    $pdo->exec("ALTER TABLE applications MODIFY COLUMN status ENUM('new','assigned','in_progress','completed','cancelled') DEFAULT 'new'");
    echo "✅ Старые значения удалены из enum\n";
    
    // 4. Устанавливаем значения для новых полей
    echo "4. Устанавливаем значения для новых полей...\n";
    $pdo->exec("UPDATE applications SET payment_status = 'pending' WHERE payment_status IS NULL");
    echo "✅ Payment status установлен\n";
    
    // 5. Создаем представление
    echo "5. Создаем представление...\n";
    try {
        $pdo->exec("DROP VIEW IF EXISTS applications_detailed");
        $pdo->exec("CREATE VIEW applications_detailed AS
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
                u.name as creator_name
            FROM applications a
            LEFT JOIN drivers d ON a.driver_id = d.id
            LEFT JOIN vehicles v ON a.vehicle_id = v.id
            LEFT JOIN companies c ON a.customer_company_id = c.id
            LEFT JOIN companies ec ON a.executor_company_id = ec.id
            LEFT JOIN users u ON a.created_by = u.id");
        echo "✅ Представление создано\n";
    } catch (Exception $e) {
        echo "⚠️ Не удалось создать представление: " . $e->getMessage() . "\n";
    }
    
    echo "\n🎉 Миграция завершена успешно!\n";
    
    // Проверяем результат
    echo "\nРезультат миграции:\n";
    $result = $pdo->query("SELECT status, COUNT(*) as count FROM applications GROUP BY status ORDER BY status")->fetchAll(PDO::FETCH_ASSOC);
    echo "Распределение статусов:\n";
    foreach ($result as $row) {
        echo "- {$row['status']}: {$row['count']} записей\n";
    }
    
    echo "\nСтруктура поля status:\n";
    $structure = $pdo->query("SHOW COLUMNS FROM applications WHERE Field = 'status'")->fetch(PDO::FETCH_ASSOC);
    echo "Type: {$structure['Type']}\n";
    
} catch (Exception $e) {
    echo "❌ Ошибка: " . $e->getMessage() . "\n";
}
?>