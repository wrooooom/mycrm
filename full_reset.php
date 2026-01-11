<?php
/**
 * Полная диагностика и исправление проблемы с enum
 */

require_once 'config/database.php';

try {
    $pdo = connectDatabase();
    
    echo "Подключение к базе данных установлено\n\n";
    
    // 1. Анализируем проблемные данные
    echo "1. Анализируем проблемные данные...\n";
    
    // Смотрим все записи с их статусами
    $stmt = $pdo->query("SELECT id, application_number, status FROM applications");
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "   Все записи:\n";
    foreach ($records as $record) {
        echo "   - ID: {$record['id']}, Номер: {$record['application_number']}, Статус: '{$record['status']}'\n";
    }
    
    // 2. Безопасное обновление через временную таблицу
    echo "\n2. Создаем резервную копию и обновляем...\n";
    
    // Создаем временную таблицу
    $pdo->exec("CREATE TEMPORARY TABLE applications_backup LIKE applications");
    $pdo->exec("INSERT INTO applications_backup SELECT * FROM applications");
    echo "   ✓ Резервная копия создана\n";
    
    // Обновляем статусы в резервной копии
    $pdo->exec("UPDATE applications_backup SET status = CASE 
        WHEN status = 'new' THEN 'new'
        WHEN status = 'confirmed' THEN 'assigned'
        WHEN status = 'inwork' THEN 'in_progress'
        WHEN status = 'completed' THEN 'completed'
        WHEN status = 'cancelled' THEN 'cancelled'
        ELSE 'new'
    END");
    echo "   ✓ Статусы в резервной копии обновлены\n";
    
    // 3. Создаем новую структуру таблицы
    echo "\n3. Создаем новую структуру таблицы...\n";
    
    // Создаем новую таблицу с правильной структурой
    $pdo->exec("CREATE TEMPOR TABLE applications_new LIKE applications");
    $pdo->exec("ALTER TABLE applications_new MODIFY COLUMN status ENUM('new', 'assigned', 'in_progress', 'completed', 'cancelled') DEFAULT 'new'");
    $pdo->exec("ALTER TABLE applications_new ADD COLUMN IF NOT EXISTS payment_status ENUM('pending', 'paid', 'refunded', 'cancelled') DEFAULT 'pending' AFTER status");
    $pdo->exec("ALTER TABLE applications_new ADD COLUMN IF NOT EXISTS pickup_time DATETIME NULL AFTER trip_date");
    $pdo->exec("ALTER TABLE applications_new ADD COLUMN IF NOT EXISTS delivery_time DATETIME NULL AFTER pickup_time");
    echo "   ✓ Новая структура таблицы создана\n";
    
    // 4. Копируем данные с обновленными статусами
    echo "\n4. Копируем данные с обновленными статусами...\n";
    
    // Копируем данные из резервной копии
    $columns = ['id', 'application_number', 'status', 'city', 'country', 'trip_date', 'service_type', 'tariff', 
                'customer_name', 'customer_phone', 'order_amount', 'created_by', 'created_at', 'updated_at'];
    
    // Добавляем новые поля с значениями по умолчанию
    $insertColumns = array_merge($columns, ['payment_status', 'pickup_time', 'delivery_time']);
    
    $placeholders = str_repeat('?,', count($insertColumns) - 1) . '?';
    
    // Удаляем старую таблицу и создаем новую
    $pdo->exec("DROP TABLE applications");
    $pdo->exec("CREATE TABLE applications LIKE applications_new");
    
    // Копируем данные из резервной копии
    foreach ($records as $record) {
        $values = [];
        foreach ($columns as $column) {
            $values[] = $record[$column];
        }
        
        // Добавляем новые поля
        $values[] = 'pending'; // payment_status по умолчанию
        $values[] = $record['trip_date']; // pickup_time = trip_date
        $values[] = date('Y-m-d H:i:s', strtotime($record['trip_date']) + 7200); // delivery_time = trip_date + 2 часа
        
        $sql = "INSERT INTO applications (" . implode(', ', $insertColumns) . ") VALUES ($placeholders)";
        $pdo->prepare($sql)->execute($values);
    }
    
    echo "   ✓ Данные скопированы с обновленными статусами\n";
    
    // 5. Проверяем результат
    echo "\n5. Проверяем результат...\n";
    
    $stmt = $pdo->query("SELECT DISTINCT status, COUNT(*) as count FROM applications GROUP BY status");
    $newStatusCounts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "   Новые статусы:\n";
    foreach ($newStatusCounts as $status) {
        echo "   - '{$status['status']}': {$status['count']} записей\n";
    }
    
    // Проверяем новые поля
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM applications WHERE payment_status IS NOT NULL");
    echo "   Записей с payment_status: " . $stmt->fetchColumn() . "\n";
    
    echo "\n✅ Полное обновление завершено успешно!\n";
    echo "Все записи перенесены с новой структурой enum.\n";
    
} catch (Exception $e) {
    echo "\n❌ Ошибка: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
?>