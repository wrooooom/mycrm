<?php
/**
 * Простое и безопасное обновление структуры applications
 */

require_once 'config/database.php';

try {
    $pdo = connectDatabase();
    
    echo "Подключение к базе данных установлено\n\n";
    
    // 1. Получаем текущие данные
    echo "1. Получаем данные...\n";
    
    $stmt = $pdo->query("SELECT * FROM applications");
    $applications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "   Найдено " . count($applications) . " записей\n";
    
    // 2. Создаем новую таблицу с нужными полями
    echo "\n2. Создаем новую таблицу...\n";
    
    $createTable = "
    CREATE TABLE applications_new (
        id INT AUTO_INCREMENT PRIMARY KEY,
        application_number VARCHAR(50) UNIQUE NOT NULL,
        status ENUM('new', 'assigned', 'in_progress', 'completed', 'cancelled') DEFAULT 'new',
        payment_status ENUM('pending', 'paid', 'refunded', 'cancelled') DEFAULT 'pending',
        city VARCHAR(100),
        country ENUM('ru', 'by', 'other') DEFAULT 'ru',
        trip_date DATETIME NOT NULL,
        pickup_time DATETIME NULL,
        delivery_time DATETIME NULL,
        service_type ENUM('rent', 'transfer', 'city_transfer', 'airport_arrival', 
                         'airport_departure', 'train_station', 'remote_area', 'other') NOT NULL,
        tariff ENUM('standard', 'comfort', 'crossover', 'business', 'premium', 'other',
                   'minivan5', 'minivan6', 'microbus8', 'microbus10', 'microbus14',
                   'microbus16', 'microbus18', 'microbus24', 'bus35', 'bus44', 'bus50') NOT NULL,
        customer_name VARCHAR(255) NOT NULL,
        customer_phone VARCHAR(20) NOT NULL,
        order_amount DECIMAL(10,2) DEFAULT 0,
        created_by INT NOT NULL,
        driver_id INT,
        vehicle_id INT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    
    $pdo->exec($createTable);
    echo "   ✓ Новая таблица создана\n";
    
    // 3. Переносим данные с правильными статусами
    echo "\n3. Переносим данные...\n";
    
    foreach ($applications as $index => $app) {
        try {
            // Определяем новый статус
            $newStatus = 'new';
            switch ($app['status']) {
                case 'new':
                    $newStatus = 'new';
                    break;
                case 'confirmed':
                    $newStatus = 'assigned';
                    break;
                case 'inwork':
                    $newStatus = 'in_progress';
                    break;
                case 'completed':
                    $newStatus = 'completed';
                    break;
                case 'cancelled':
                    $newStatus = 'cancelled';
                    break;
            }
            
            // Вставляем в новую таблицу
            $insert = $pdo->prepare("
                INSERT INTO applications_new (
                    id, application_number, status, payment_status, city, country, trip_date,
                    pickup_time, delivery_time, service_type, tariff, customer_name, 
                    customer_phone, order_amount, created_by, driver_id, vehicle_id
                ) VALUES (?, ?, ?, 'pending', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $params = [
                $app['id'],
                $app['application_number'],
                $newStatus,
                $app['city'] ?? 'Москва',
                $app['country'] ?? 'ru',
                $app['trip_date'],
                $app['trip_date'], // pickup_time = trip_date
                date('Y-m-d H:i:s', strtotime($app['trip_date']) + 7200), // delivery_time = trip_date + 2 часа
                $app['service_type'],
                $app['tariff'],
                $app['customer_name'],
                $app['customer_phone'],
                $app['order_amount'] ?? 0,
                $app['created_by'],
                $app['driver_id'],
                $app['vehicle_id']
            ];
            
            $insert->execute($params);
            
        } catch (Exception $e) {
            echo "   ⚠ Ошибка при вставке записи {$app['id']}: " . $e->getMessage() . "\n";
            continue;
        }
    }
    
    echo "   ✓ Данные перенесены\n";
    
    // 4. Заменяем таблицы
    echo "\n4. Заменяем таблицы...\n";
    
    try {
        $pdo->exec("DROP TABLE applications");
        $pdo->exec("RENAME TABLE applications_new TO applications");
        echo "   ✓ Таблицы заменены\n";
    } catch (Exception $e) {
        echo "   ⚠ Ошибка при замене таблиц: " . $e->getMessage() . "\n";
    }
    
    // 5. Проверяем результат
    echo "\n5. Проверяем результат...\n";
    
    try {
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM applications");
        $count = $stmt->fetchColumn();
        echo "   Записей в новой таблице: {$count}\n";
        
        $stmt = $pdo->query("SELECT DISTINCT status, COUNT(*) as count FROM applications GROUP BY status");
        $statusCounts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "   Статусы:\n";
        foreach ($statusCounts as $status) {
            echo "   - '{$status['status']}': {$status['count']} записей\n";
        }
        
        // Проверяем новые поля
        $stmt = $pdo->query("DESCRIBE applications");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "\n   Новые поля:\n";
        foreach ($columns as $column) {
            if (in_array($column['Field'], ['status', 'payment_status', 'pickup_time', 'delivery_time'])) {
                echo "   - {$column['Field']}: {$column['Type']} (Default: {$column['Default']})\n";
            }
        }
        
    } catch (Exception $e) {
        echo "   ⚠ Ошибка при проверке: " . $e->getMessage() . "\n";
    }
    
    echo "\n✅ Миграция завершена!\n";
    
} catch (Exception $e) {
    echo "\n❌ Ошибка: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
?>