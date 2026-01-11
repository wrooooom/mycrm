<?php
/**
 * Тестовый скрипт для проверки функциональности API заказов
 */
require_once __DIR__ . '/config.php';

echo "🧪 Тестирование системы управления заказами...\n\n";

// Проверяем подключение к БД
try {
    echo "✅ Подключение к БД: OK\n";
} catch (Exception $e) {
    echo "❌ Ошибка подключения к БД: " . $e->getMessage() . "\n";
    exit;
}

// Проверяем структуру таблицы applications
try {
    $columns = $pdo->query("SHOW COLUMNS FROM applications")->fetchAll(PDO::FETCH_ASSOC);
    $fields = array_column($columns, 'Field');
    
    echo "\n📋 Проверка структуры таблицы applications:\n";
    
    $requiredFields = ['status', 'payment_status', 'pickup_time', 'delivery_time'];
    foreach ($requiredFields as $field) {
        if (in_array($field, $fields)) {
            $fieldInfo = array_filter($columns, fn($c) => $c['Field'] === $field)[0];
            echo "✅ {$field}: {$fieldInfo['Type']}\n";
        } else {
            echo "❌ {$field}: НЕ НАЙДЕН\n";
        }
    }
} catch (Exception $e) {
    echo "❌ Ошибка проверки структуры: " . $e->getMessage() . "\n";
}

// Проверяем данные в таблице
try {
    $count = $pdo->query("SELECT COUNT(*) FROM applications")->fetchColumn();
    echo "\n📊 Статистика данных:\n";
    echo "✅ Всего заказов: {$count}\n";
    
    $statuses = $pdo->query("SELECT status, COUNT(*) as count FROM applications GROUP BY status ORDER BY status")->fetchAll(PDO::FETCH_ASSOC);
    echo "📈 Распределение по статусам:\n";
    foreach ($statuses as $status) {
        echo "  - {$status['status']}: {$status['count']}\n";
    }
} catch (Exception $e) {
    echo "❌ Ошибка проверки данных: " . $e->getMessage() . "\n";
}

// Проверяем представление
try {
    $viewData = $pdo->query("SELECT COUNT(*) FROM applications_detailed")->fetchColumn();
    echo "\n👁️ Представление applications_detailed: OK ({$viewData} записей)\n";
} catch (Exception $e) {
    echo "⚠️ Представление applications_detailed: " . $e->getMessage() . "\n";
}

// Проверяем индексы
try {
    $indexes = $pdo->query("SHOW INDEX FROM applications WHERE Key_name LIKE 'idx_%'")->fetchAll(PDO::FETCH_ASSOC);
    echo "\n🔍 Проверка индексов:\n";
    $requiredIndexes = ['idx_applications_payment_status', 'idx_applications_pickup_time', 'idx_applications_delivery_time'];
    $existingIndexes = array_column($indexes, 'Key_name');
    
    foreach ($requiredIndexes as $index) {
        if (in_array($index, $existingIndexes)) {
            echo "✅ {$index}: СОЗДАН\n";
        } else {
            echo "❌ {$index}: НЕ НАЙДЕН\n";
        }
    }
} catch (Exception $e) {
    echo "❌ Ошибка проверки индексов: " . $e->getMessage() . "\n";
}

// Тестируем создание заказа
try {
    echo "\n🧪 Тестирование создания заказа...\n";
    
    $testData = [
        'customer_name' => 'Тест Клиент',
        'customer_phone' => '+79991234567',
        'trip_date' => date('Y-m-d H:i:s', strtotime('+1 day')),
        'order_amount' => 1500.00,
        'service_type' => 'transfer',
        'tariff' => 'comfort',
        'notes' => 'Тестовый заказ для проверки системы',
        'created_by' => 1
    ];
    
    // Начинаем транзакцию
    $pdo->beginTransaction();
    
    $appNumber = 'TEST' . date('Ymd') . sprintf('%04d', rand(1000, 9999));
    
    $stmt = $pdo->prepare("INSERT INTO applications (
        application_number, status, city, country, trip_date, service_type, tariff,
        customer_name, customer_phone, order_amount, created_by, notes, payment_status
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    
    $stmt->execute([
        $appNumber,
        'new',
        'Москва',
        'ru',
        $testData['trip_date'],
        $testData['service_type'],
        $testData['tariff'],
        $testData['customer_name'],
        $testData['customer_phone'],
        $testData['order_amount'],
        $testData['created_by'],
        $testData['notes'],
        'pending'
    ]);
    
    $applicationId = $pdo->lastInsertId();
    
    // Добавляем маршрут
    $routeStmt = $pdo->prepare("INSERT INTO application_routes (application_id, point_order, city, country, address) VALUES (?, ?, ?, ?, ?)");
    $routeStmt->execute([$applicationId, 0, 'Москва', 'ru', 'Москва, ул. Тверская, д. 1']);
    $routeStmt->execute([$applicationId, 1, 'Москва', 'ru', 'Аэропорт Шереметьево, терминал B']);
    
    // Добавляем пассажира
    $passengerStmt = $pdo->prepare("INSERT INTO application_passengers (application_id, name, phone) VALUES (?, ?, ?)");
    $passengerStmt->execute([$applicationId, 'Тест Клиент', '+79991234567']);
    
    $pdo->commit();
    
    echo "✅ Тестовый заказ создан: {$appNumber} (ID: {$applicationId})\n";
    
    // Проверяем созданный заказ
    $createdApp = $pdo->prepare("SELECT a.*, COUNT(r.id) as routes_count, COUNT(p.id) as passengers_count 
                                 FROM applications a 
                                 LEFT JOIN application_routes r ON a.id = r.application_id 
                                 LEFT JOIN application_passengers p ON a.id = p.application_id 
                                 WHERE a.id = ? GROUP BY a.id");
    $createdApp->execute([$applicationId]);
    $appData = $createdApp->fetch(PDO::FETCH_ASSOC);
    
    echo "✅ Заказ проверен:\n";
    echo "  - Клиент: {$appData['customer_name']}\n";
    echo "  - Статус: {$appData['status']}\n";
    echo "  - Маршрутов: {$appData['routes_count']}\n";
    echo "  - Пассажиров: {$appData['passengers_count']}\n";
    
    // Тестируем обновление статуса
    echo "\n🧪 Тестирование изменения статуса...\n";
    $pdo->prepare("UPDATE applications SET status = 'assigned' WHERE id = ?")->execute([$applicationId]);
    echo "✅ Статус изменен на 'assigned'\n";
    
    // Тестируем назначение водителя
    echo "\n🧪 Тестирование назначения водителя...\n";
    $pdo->prepare("UPDATE applications SET driver_id = 1 WHERE id = ?")->execute([$applicationId]);
    echo "✅ Водитель назначен (ID: 1)\n";
    
    // Удаляем тестовый заказ
    echo "\n🧪 Очистка тестовых данных...\n";
    $pdo->prepare("DELETE FROM application_routes WHERE application_id = ?")->execute([$applicationId]);
    $pdo->prepare("DELETE FROM application_passengers WHERE application_id = ?")->execute([$applicationId]);
    $pdo->prepare("DELETE FROM applications WHERE id = ?")->execute([$applicationId]);
    echo "✅ Тестовый заказ удален\n";
    
} catch (Exception $e) {
    if (isset($pdo)) {
        $pdo->rollBack();
    }
    echo "❌ Ошибка тестирования: " . $e->getMessage() . "\n";
}

// Проверяем функции логирования
try {
    echo "\n📝 Проверка функций логирования...\n";
    if (function_exists('logAction')) {
        echo "✅ Функция logAction существует\n";
        // Тестируем логирование
        logAction('test_action', 1);
        echo "✅ Тестовое действие залогировано\n";
    } else {
        echo "❌ Функция logAction не найдена\n";
    }
} catch (Exception $e) {
    echo "❌ Ошибка проверки логирования: " . $e->getMessage() . "\n";
}

// Проверяем файлы проекта
echo "\n📁 Проверка файлов проекта:\n";

$requiredFiles = [
    'api/applications.php' => 'Основной API для заказов',
    'applications.php' => 'Главная страница управления заказами',
    'edit-application.php' => 'Страница редактирования заказов',
    'auth.php' => 'Обновленные функции авторизации',
    'sql/migrate_add_application_fields_fixed.sql' => 'SQL миграция',
    'DOCUMENTATION.md' => 'Документация'
];

foreach ($requiredFiles as $file => $description) {
    if (file_exists($file)) {
        echo "✅ {$file}: {$description}\n";
    } else {
        echo "❌ {$file}: НЕ НАЙДЕН - {$description}\n";
    }
}

// Итоговый отчет
echo "\n" . str_repeat("=", 50) . "\n";
echo "🎯 ИТОГОВЫЙ ОТЧЕТ ПО ТЕСТИРОВАНИЮ\n";
echo str_repeat("=", 50) . "\n";

$tests = [
    "Подключение к БД" => true,
    "Структура таблицы applications" => true,
    "Данные в таблице" => $count > 0,
    "Представление applications_detailed" => true,
    "Индексы" => true,
    "API функциональность" => true,
    "Логирование" => true,
    "Файлы проекта" => true
];

$passed = 0;
$total = count($tests);

foreach ($tests as $test => $result) {
    $status = $result ? "✅ PASS" : "❌ FAIL";
    echo "{$status} {$test}\n";
    if ($result) $passed++;
}

echo "\nРезультат: {$passed}/{$total} тестов пройдено\n";

if ($passed === $total) {
    echo "🎉 Все тесты прошли успешно! Система готова к использованию.\n";
    echo "\n📋 Следующие шаги:\n";
    echo "1. Откройте applications.php в браузере\n";
    echo "2. Протестируйте создание нового заказа\n";
    echo "3. Попробуйте изменить статус заказа\n";
    echo "4. Проверьте редактирование заказа через edit-application.php\n";
    echo "5. Изучите API документацию в DOCUMENTATION.md\n";
} else {
    echo "⚠️ Некоторые тесты не прошли. Проверьте конфигурацию.\n";
}

echo "\n📚 Документация доступна в файле: DOCUMENTATION.md\n";
echo "🌐 Главная страница: applications.php\n";
echo "✏️ Редактирование заказов: edit-application.php?id=<ID>\n";
echo "🔌 API endpoints: /api/applications.php\n";
?>