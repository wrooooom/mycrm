<?php
/**
 * Простое и рабочее обновление структуры applications
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
    
    // 2. Создаем новую таблицу
    echo "\n2. Создаем новую таблицу...\n";
    
    // Создаем таблицу с новой структурой
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
        cancellation_hours INT DEFAULT 0,
        customer_name VARCHAR(255) NOT NULL,
        customer_phone VARCHAR(20) NOT NULL,
        additional_services_amount DECIMAL(10,2) DEFAULT 0,
        flight_number VARCHAR(50),
        sign_text VARCHAR(255),
        manager_comment TEXT,
        toll_roads_amount DECIMAL(10,2) DEFAULT 0,
        vehicle_class VARCHAR(50),
        notes TEXT,
        requires_correction BOOLEAN DEFAULT false,
        correction_reason TEXT,
        order_amount DECIMAL(10,2) DEFAULT 0,
        customer_company_id INT,
        executor_company_id INT,
        executor_amount DECIMAL(10,2) DEFAULT 0,
        created_by INT NOT NULL,
        driver_id INT,
        vehicle_id INT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    
    $pdo->exec($createTable);
    echo "   ✓ Новая таблица создана\n";
    
    // 3. Переносим данные с обновленными статусами
    echo "\n3. Переносим данные с обновленными статусами...\n";
    
    foreach ($applications as $app) {
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
        
        // Определяем время подачи и доставки
        $pickupTime = $app['trip_date'];
        $deliveryTime = date('Y-m-d H:i:s', strtotime($app['trip_date']) + 7200); // +2 часа
        
        // Вставляем в новую таблицу
        $insert = $pdo->prepare("
            INSERT INTO applications_new (
                id, application_number, status, payment_status, city, country, trip_date,
                pickup_time, delivery_time, service_type, tariff, customer_name, customer_phone,
                order_amount, created_by, driver_id, vehicle_id, created_at, updated_at
            ) VALUES (
                ?, ?, ?, 'pending', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP
            )
        ");
        
        $insert->execute([
            $app['id'],
            $app['application_number'],
            $newStatus,
            $app['city'],
            $app['country'],
            $app['trip_date'],
            $pickupTime,
            $deliveryTime,
            $app['service_type'],
            $app['tariff'],
            $app['customer_name'],
            $app['customer_phone'],
            $app['order_amount'],
            $app['created_by'],
            $app['driver_id'],
            $app['vehicle_id']
        ]);
    }
    
    echo "   ✓ " . count($applications) . " записей перенесено\n";
    
    // 4. Заменяем таблицы
    echo "\n4. Заменяем таблицы...\n";
    
    $pdo->exec("RENAME TABLE applications TO applications_old");
    $pdo->exec("RENAME TABLE applications_new TO applications");
    
    echo "   ✓ Таблицы заменены\n";
    
    // 5. Проверяем результат
    echo "\n5. Проверяем результат...\n";
    
    $stmt = $pdo->query("SELECT DISTINCT status, COUNT(*) as count FROM applications GROUP BY status");
    $statusCounts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "   Новые статусы:\n";
    foreach ($statusCounts as $status) {
        echo "   - '{$status['status']}': {$status['count']} записей\n";
    }
    
    // Проверяем структуру
    $stmt = $pdo->query("DESCRIBE applications");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "\n   Новые поля:\n";
    foreach ($columns as $column) {
        if (in_array($column['Field'], ['status', 'payment_status', 'pickup_time', 'delivery_time'])) {
            echo "   - {$column['Field']}: {$column['Type']} (Default: {$column['Default']})\n";
        }
    }
    
    // 6. Создаем индексы
    echo "\n6. Создаем индексы...\n";
    
    $pdo->exec("CREATE INDEX idx_applications_status ON applications(status)");
    echo "   ✓ Индекс для status создан\n";
    
    $pdo->exec("CREATE INDEX idx_applications_payment_status ON applications(payment_status)");
    echo "   ✓ Индекс для payment_status создан\n";
    
    $pdo->exec("CREATE INDEX idx_applications_pickup_time ON applications(pickup_time)");
    echo "   ✓ Индекс для pickup_time создан\n";
    
    $pdo->exec("CREATE INDEX idx_applications_delivery_time ON applications(delivery_time)");
    echo "   ✓ Индекс для delivery_time создан\n";
    
    // 7. Копируем связанные данные (маршруты и пассажиры)
    echo "\n7. Копируем связанные данные...\n";
    
    // Копируем маршруты
    $stmt = $pdo->query("SELECT * FROM application_routes");
    $routes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($routes as $route) {
        $insert = $pdo->prepare("
            INSERT INTO application_routes (application_id, point_order, city, country, address) 
            VALUES (?, ?, ?, ?, ?)
        ");
        $insert->execute([
            $route['application_id'],
            $route['point_order'],
            $route['city'],
            $route['country'],
            $route['address']
        ]);
    }
    echo "   ✓ " . count($routes) . " маршрутов скопировано\n";
    
    // Копируем пассажиров
    $stmt = $pdo->query("SELECT * FROM application_passengers");
    $passengers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($passengers as $passenger) {
        $insert = $pdo->prepare("
            INSERT INTO application_passengers (application_id, name, phone) 
            VALUES (?, ?, ?)
        ");
        $insert->execute([
            $passenger['application_id'],
            $passenger['name'],
            $passenger['phone']
        ]);
    }
    echo "   ✓ " . count($passengers) . " пассажиров скопировано\n";
    
    // 8. Удаляем старую таблицу
    echo "\n8. Удаляем старую таблицу...\n";
    
    $pdo->exec("DROP TABLE applications_old");
    echo "   ✓ Старая таблица удалена\n";
    
    echo "\n✅ Миграция завершена успешно!\n";
    echo "Структура базы данных обновлена согласно новым требованиям.\n";
    
} catch (Exception $e) {
    echo "\n❌ Ошибка: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
?>