<?php
/**
 * Диагностика и безопасное исправление статусов в applications
 */

require_once 'config/database.php';

try {
    $pdo = connectDatabase();
    
    echo "Подключение к базе данных установлено\n\n";
    
    // 1. Анализируем текущие данные
    echo "1. Анализируем текущие данные...\n";
    
    // Проверяем все статусы в таблице
    $stmt = $pdo->query("SELECT DISTINCT status, COUNT(*) as count FROM applications GROUP BY status");
    $statusCounts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "   Текущие статусы:\n";
    foreach ($statusCounts as $status) {
        echo "   - '{$status['status']}': {$status['count']} записей\n";
    }
    
    // Проверяем структуру таблицы
    echo "\n   Структура поля status:\n";
    $stmt = $pdo->query("DESCRIBE applications");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($columns as $column) {
        if ($column['Field'] === 'status') {
            echo "   - Type: {$column['Type']}\n";
            echo "   - Default: {$column['Default']}\n";
            echo "   - Null: {$column['Null']}\n";
        }
    }
    
    // 2. Безопасное обновление статусов
    echo "\n2. Безопасное обновление статусов...\n";
    
    // Сначала устанавливаем все неизвестные статусы в 'new'
    $pdo->exec("UPDATE applications SET status = 'new' WHERE status NOT IN ('new', 'confirmed', 'inwork', 'completed', 'cancelled')");
    echo "   ✓ Неизвестные статусы установлены в 'new'\n";
    
    // Обновляем конкретные статусы
    $pdo->exec("UPDATE applications SET status = 'assigned' WHERE status = 'confirmed'");
    echo "   ✓ 'confirmed' → 'assigned'\n";
    
    $pdo->exec("UPDATE applications SET status = 'in_progress' WHERE status = 'inwork'");
    echo "   ✓ 'inwork' → 'in_progress'\n";
    
    // 3. Обновляем структуру enum
    echo "\n3. Обновляем структуру enum...\n";
    
    try {
        $pdo->exec("ALTER TABLE applications MODIFY COLUMN status ENUM('new', 'assigned', 'in_progress', 'completed', 'cancelled') DEFAULT 'new'");
        echo "   ✓ Enum успешно обновлен\n";
    } catch (PDOException $e) {
        echo "   ⚠ Проблема с обновлением enum: " . $e->getMessage() . "\n";
        echo "   Попробуем другой подход...\n";
        
        // Создаем временную таблицу и копируем данные
        $pdo->exec("CREATE TEMPORARY TABLE temp_applications LIKE applications");
        $pdo->exec("INSERT INTO temp_applications SELECT * FROM applications");
        $pdo->exec("ALTER TABLE applications DROP COLUMN status");
        $pdo->exec("ALTER TABLE applications ADD COLUMN status ENUM('new', 'assigned', 'in_progress', 'completed', 'cancelled') DEFAULT 'new' AFTER application_number");
        $pdo->exec("INSERT INTO applications (id, application_number, status, city, country, trip_date, service_type, tariff, customer_name, customer_phone, order_amount, created_by, created_at, updated_at) 
                   SELECT id, application_number, status, city, country, trip_date, service_type, tariff, customer_name, customer_phone, order_amount, created_by, created_at, updated_at FROM temp_applications");
        echo "   ✓ Enum обновлен через временную таблицу\n";
    }
    
    // 4. Проверяем результат
    echo "\n4. Проверяем результат...\n";
    
    $stmt = $pdo->query("SELECT DISTINCT status, COUNT(*) as count FROM applications GROUP BY status");
    $newStatusCounts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "   Новые статусы:\n";
    foreach ($newStatusCounts as $status) {
        echo "   - '{$status['status']}': {$status['count']} записей\n";
    }
    
    // 5. Добавляем недостающие поля
    echo "\n5. Добавляем недостающие поля...\n";
    
    // Добавляем payment_status
    try {
        $pdo->exec("ALTER TABLE applications ADD COLUMN payment_status ENUM('pending', 'paid', 'refunded', 'cancelled') DEFAULT 'pending' AFTER status");
        echo "   ✓ Поле payment_status добавлено\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
            echo "   ✓ Поле payment_status уже существует\n";
        } else {
            throw $e;
        }
    }
    
    // Добавляем pickup_time
    try {
        $pdo->exec("ALTER TABLE applications ADD COLUMN pickup_time DATETIME NULL AFTER trip_date");
        echo "   ✓ Поле pickup_time добавлено\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
            echo "   ✓ Поле pickup_time уже существует\n";
        } else {
            throw $e;
        }
    }
    
    // Добавляем delivery_time
    try {
        $pdo->exec("ALTER TABLE applications ADD COLUMN delivery_time DATETIME NULL AFTER pickup_time");
        echo "   ✓ Поле delivery_time добавлено\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
            echo "   ✓ Поле delivery_time уже существует\n";
        } else {
            throw $e;
        }
    }
    
    // 6. Устанавливаем значения по умолчанию
    echo "\n6. Устанавливаем значения по умолчанию...\n";
    
    $pdo->exec("UPDATE applications SET 
        pickup_time = COALESCE(pickup_time, trip_date),
        delivery_time = COALESCE(delivery_time, DATE_ADD(trip_date, INTERVAL 2 HOUR))
        WHERE pickup_time IS NULL OR delivery_time IS NULL");
    echo "   ✓ Время подачи и доставки установлено\n";
    
    echo "\n✅ Диагностика и обновление завершены успешно!\n";
    
} catch (Exception $e) {
    echo "\n❌ Ошибка: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
?>