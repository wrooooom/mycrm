<?php
/**
 * Полная миграция с очисткой данных
 */
require_once __DIR__ . '/config.php';

echo "Полная миграция таблицы applications...\n";

try {
    // 1. Очищаем и обновляем статусы
    echo "1. Обновляем все статусы...\n";
    $pdo->exec("UPDATE applications SET status = 'new' WHERE status IS NULL OR status = ''");
    $pdo->exec("UPDATE applications SET status = 'assigned' WHERE status = 'confirmed'");
    $pdo->exec("UPDATE applications SET status = 'in_progress' WHERE status = 'inwork'");
    echo "✅ Статусы обновлены\n";
    
    // 2. Устанавливаем значения для новых полей
    echo "2. Устанавливаем значения для новых полей...\n";
    $pdo->exec("UPDATE applications SET payment_status = 'pending' WHERE payment_status IS NULL");
    echo "✅ Payment status установлен\n";
    
    // 3. Теперь можем изменить enum безопасно
    echo "3. Обновляем структуру enum...\n";
    $pdo->exec("ALTER TABLE applications MODIFY COLUMN status ENUM('new', 'assigned', 'in_progress', 'completed', 'cancelled') DEFAULT 'new'");
    echo "✅ Enum обновлен\n";
    
    // 4. Проверяем результат
    echo "4. Проверяем результат...\n";
    $result = $pdo->query("SELECT status, COUNT(*) as count FROM applications GROUP BY status ORDER BY status")->fetchAll(PDO::FETCH_ASSOC);
    echo "Распределение статусов после миграции:\n";
    foreach ($result as $row) {
        echo "- {$row['status']}: {$row['count']} записей\n";
    }
    
    // 5. Создаем представление
    echo "5. Создаем представление applications_detailed...\n";
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
            u.name as creator_name
        FROM applications a
        LEFT JOIN drivers d ON a.driver_id = d.id
        LEFT JOIN vehicles v ON a.vehicle_id = v.id
        LEFT JOIN companies c ON a.customer_company_id = c.id
        LEFT JOIN companies ec ON a.executor_company_id = ec.id
        LEFT JOIN users u ON a.created_by = u.id");
    echo "✅ Представление создано\n";
    
    echo "\n🎉 Миграция завершена успешно!\n";
    
    // Финальная проверка
    echo "\nФинальная структура поля status:\n";
    $structure = $pdo->query("SHOW COLUMNS FROM applications WHERE Field = 'status'")->fetch(PDO::FETCH_ASSOC);
    echo "Type: {$structure['Type']}\n";
    
    echo "\nНовые поля:\n";
    $newFields = ['payment_status', 'pickup_time', 'delivery_time'];
    foreach ($newFields as $field) {
        $fieldInfo = $pdo->query("SHOW COLUMNS FROM applications WHERE Field = '$field'")->fetch(PDO::FETCH_ASSOC);
        if ($fieldInfo) {
            echo "- $field: {$fieldInfo['Type']}\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ Ошибка миграции: " . $e->getMessage() . "\n";
}
?>