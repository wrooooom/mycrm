<?php
/**
 * Корректная миграция с обработкой внешних ключей
 */

require_once 'config/database.php';

try {
    $pdo = connectDatabase();
    
    echo "Подключение к базе данных установлено\n\n";
    
    // 1. Отключаем проверку внешних ключей
    echo "1. Отключаем проверку внешних ключей...\n";
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
    echo "   ✓ Проверка отключена\n";
    
    // 2. Удаляем существующие таблицы
    echo "\n2. Удаляем существующие таблицы...\n";
    $pdo->exec("DROP TABLE IF EXISTS applications_new");
    $pdo->exec("DROP TABLE IF EXISTS applications");
    echo "   ✓ Таблицы удалены\n";
    
    // 3. Создаем новую таблицу applications
    echo "\n3. Создаем новую таблицу applications...\n";
    
    $createSQL = "
    CREATE TABLE applications (
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
    
    $pdo->exec($createSQL);
    echo "   ✓ Новая таблица applications создана\n";
    
    // 4. Добавляем тестовые данные
    echo "\n4. Добавляем тестовые данные...\n";
    
    $testData = [
        [
            'id' => 1,
            'application_number' => 'A2025010001',
            'status' => 'completed',
            'city' => 'Москва',
            'country' => 'ru',
            'trip_date' => '2025-01-25 14:30:00',
            'service_type' => 'airport_arrival',
            'tariff' => 'comfort',
            'customer_name' => 'Иванов Иван Иванович',
            'customer_phone' => '+79991234567',
            'order_amount' => 2500,
            'created_by' => 2,
            'driver_id' => 1,
            'vehicle_id' => 1
        ],
        [
            'id' => 2,
            'application_number' => 'A2025010002',
            'status' => 'assigned',
            'city' => 'Москва',
            'country' => 'ru',
            'trip_date' => '2025-01-25 09:00:00',
            'service_type' => 'city_transfer',
            'tariff' => 'business',
            'customer_name' => 'Петров Петр Петрович',
            'customer_phone' => '+79997654321',
            'order_amount' => 3500,
            'created_by' => 2,
            'driver_id' => 2,
            'vehicle_id' => 2
        ],
        [
            'id' => 3,
            'application_number' => 'A2025010003',
            'status' => 'new',
            'city' => 'Москва',
            'country' => 'ru',
            'trip_date' => '2025-01-26 16:45:00',
            'service_type' => 'airport_departure',
            'tariff' => 'comfort',
            'customer_name' => 'Сидоров Алексей Сергеевич',
            'customer_phone' => '+79994561234',
            'order_amount' => 2800,
            'created_by' => 2,
            'driver_id' => null,
            'vehicle_id' => null
        ],
        [
            'id' => 4,
            'application_number' => 'A2025010004',
            'status' => 'in_progress',
            'city' => 'Москва',
            'country' => 'ru',
            'trip_date' => '2025-01-26 12:00:00',
            'service_type' => 'train_station',
            'tariff' => 'business',
            'customer_name' => 'Кузнецова Мария Владимировна',
            'customer_phone' => '+79993216547',
            'order_amount' => 4200,
            'created_by' => 2,
            'driver_id' => 3,
            'vehicle_id' => 3
        ]
    ];
    
    foreach ($testData as $app) {
        $pickupTime = $app['trip_date'];
        $deliveryTime = date('Y-m-d H:i:s', strtotime($app['trip_date']) + 7200);
        
        $insert = $pdo->prepare("
            INSERT INTO applications (
                id, application_number, status, payment_status, city, country, trip_date,
                pickup_time, delivery_time, service_type, tariff, customer_name, 
                customer_phone, order_amount, created_by, driver_id, vehicle_id
            ) VALUES (?, ?, ?, 'pending', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $insert->execute([
            $app['id'],
            $app['application_number'],
            $app['status'],
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
    
    echo "   ✓ " . count($testData) . " записей добавлено\n";
    
    // 5. Создаем индексы
    echo "\n5. Создаем индексы...\n";
    
    $pdo->exec("CREATE INDEX idx_applications_status ON applications(status)");
    $pdo->exec("CREATE INDEX idx_applications_payment_status ON applications(payment_status)");
    $pdo->exec("CREATE INDEX idx_applications_trip_date ON applications(trip_date)");
    $pdo->exec("CREATE INDEX idx_applications_pickup_time ON applications(pickup_time)");
    $pdo->exec("CREATE INDEX idx_applications_created_by ON applications(created_by)");
    $pdo->exec("CREATE INDEX idx_applications_driver_id ON applications(driver_id)");
    $pdo->exec("CREATE INDEX idx_applications_vehicle_id ON applications(vehicle_id)");
    
    echo "   ✓ Индексы созданы\n";
    
    // 6. Создаем заново таблицы с внешними ключами
    echo "\n6. Создаем связанные таблицы...\n";
    
    // Пересоздаем таблицы для маршрутов и пассажиров
    $pdo->exec("DROP TABLE IF EXISTS application_routes");
    $pdo->exec("DROP TABLE IF EXISTS application_passengers");
    
    $pdo->exec("
        CREATE TABLE application_routes (
            id INT AUTO_INCREMENT PRIMARY KEY,
            application_id INT NOT NULL,
            point_order INT NOT NULL,
            city VARCHAR(100),
            country ENUM('ru', 'by', 'other') DEFAULT 'ru',
            address TEXT NOT NULL,
            FOREIGN KEY (application_id) REFERENCES applications(id) ON DELETE CASCADE,
            INDEX idx_application (application_id),
            INDEX idx_point_order (point_order)
        )
    ");
    
    $pdo->exec("
        CREATE TABLE application_passengers (
            id INT AUTO_INCREMENT PRIMARY KEY,
            application_id INT NOT NULL,
            name VARCHAR(255) NOT NULL,
            phone VARCHAR(20),
            FOREIGN KEY (application_id) REFERENCES applications(id) ON DELETE CASCADE,
            INDEX idx_application (application_id)
        )
    ");
    
    echo "   ✓ Связанные таблицы созданы\n";
    
    // 7. Добавляем данные в связанные таблицы
    echo "\n7. Добавляем данные в связанные таблицы...\n";
    
    // Маршруты
    $routes = [
        [1, 0, 'Москва', 'ru', 'Аэропорт Шереметьево, терминал B'],
        [1, 1, 'Москва', 'ru', 'ул. Тверская, д. 15'],
        [2, 0, 'Москва', 'ru', 'ул. Арбат, д. 25'],
        [2, 1, 'Москва', 'ru', 'Аэропорт Домодедово, терминал А'],
        [3, 0, 'Москва', 'ru', 'Киевский вокзал, главный вход'],
        [3, 1, 'Москва', 'ru', 'Аэропорт Внуково, терминал B'],
        [4, 0, 'Москва', 'ru', 'офис Газпром, пр. Мира, д. 120'],
        [4, 1, 'Москва', 'ru', 'Ленинградский вокзал']
    ];
    
    foreach ($routes as $route) {
        $stmt = $pdo->prepare("INSERT INTO application_routes (application_id, point_order, city, country, address) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute($route);
    }
    
    echo "   ✓ " . count($routes) . " маршрутов добавлено\n";
    
    // Пассажиры
    $passengers = [
        [1, 'Иванов Иван Иванович', '+79991234567'],
        [2, 'Петров Петр Петрович', '+79997654321'],
        [2, 'Петрова Анна Сергеевна', '+79997654322'],
        [3, 'Сидоров Алексей Сергеевич', '+79994561234'],
        [4, 'Кузнецова Мария Владимировна', '+79993216547']
    ];
    
    foreach ($passengers as $passenger) {
        $stmt = $pdo->prepare("INSERT INTO application_passengers (application_id, name, phone) VALUES (?, ?, ?)");
        $stmt->execute($passenger);
    }
    
    echo "   ✓ " . count($passengers) . " пассажиров добавлено\n";
    
    // 8. Включаем проверку внешних ключей
    echo "\n8. Включаем проверку внешних ключей...\n";
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    echo "   ✓ Проверка включена\n";
    
    // 9. Проверяем результат
    echo "\n9. Проверяем результат...\n";
    
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM applications");
    $count = $stmt->fetchColumn();
    echo "   Записей в applications: {$count}\n";
    
    $stmt = $pdo->query("SELECT DISTINCT status, COUNT(*) as count FROM applications GROUP BY status");
    $statusCounts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "   Статусы:\n";
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
    
    echo "\n✅ Миграция завершена успешно!\n";
    echo "База данных полностью обновлена согласно новым требованиям.\n";
    
} catch (Exception $e) {
    // Включаем проверку внешних ключей в случае ошибки
    try {
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    } catch (Exception $e2) {
        // Игнорируем ошибки при восстановлении
    }
    
    echo "\n❌ Ошибка: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
?>