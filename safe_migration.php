<?php
/**
 * Безопасное обновление структуры и данных applications
 */

require_once 'config/database.php';

try {
    $pdo = connectDatabase();
    
    echo "Подключение к базе данных установлено\n\n";
    
    // 1. Сначала обновляем данные
    echo "1. Обновляем существующие данные...\n";
    
    // Обновляем статусы заявок
    $updateStatusQuery = "UPDATE applications SET status = CASE 
        WHEN status = 'new' THEN 'new'
        WHEN status = 'confirmed' THEN 'assigned'
        WHEN status = 'inwork' THEN 'in_progress'
        WHEN status = 'completed' THEN 'completed'
        WHEN status = 'cancelled' THEN 'cancelled'
        ELSE 'new'
    END";
    
    $pdo->exec($updateStatusQuery);
    echo "   ✓ Статусы обновлены\n";
    
    // 2. Добавляем недостающие поля
    echo "2. Добавляем недостающие поля...\n";
    
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
    
    // 3. Устанавливаем значения по умолчанию
    echo "3. Устанавливаем значения по умолчанию...\n";
    
    // Устанавливаем pickup_time и delivery_time
    $updateTimesQuery = "UPDATE applications SET 
        pickup_time = COALESCE(pickup_time, trip_date),
        delivery_time = COALESCE(delivery_time, DATE_ADD(trip_date, INTERVAL 2 HOUR))
        WHERE pickup_time IS NULL OR delivery_time IS NULL";
    
    $pdo->exec($updateTimesQuery);
    echo "   ✓ Время подачи и доставки установлено\n";
    
    // 4. Обновляем структуру enum
    echo "4. Обновляем структуру enum...\n";
    
    // Обновляем enum для статусов
    $pdo->exec("ALTER TABLE applications MODIFY COLUMN status ENUM('new', 'assigned', 'in_progress', 'completed', 'cancelled') DEFAULT 'new'");
    echo "   ✓ Enum для status обновлен\n";
    
    // 5. Создаем индексы
    echo "5. Создаем индексы...\n";
    
    try {
        $pdo->exec("CREATE INDEX idx_applications_status ON applications(status)");
        echo "   ✓ Индекс для status создан\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'already exists') !== false) {
            echo "   ✓ Индекс для status уже существует\n";
        }
    }
    
    try {
        $pdo->exec("CREATE INDEX idx_applications_payment_status ON applications(payment_status)");
        echo "   ✓ Индекс для payment_status создан\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'already exists') !== false) {
            echo "   ✓ Индекс для payment_status уже существует\n";
        }
    }
    
    try {
        $pdo->exec("CREATE INDEX idx_applications_pickup_time ON applications(pickup_time)");
        echo "   ✓ Индекс для pickup_time создан\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'already exists') !== false) {
            echo "   ✓ Индекс для pickup_time уже существует\n";
        }
    }
    
    try {
        $pdo->exec("CREATE INDEX idx_applications_delivery_time ON applications(delivery_time)");
        echo "   ✓ Индекс для delivery_time создан\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'already exists') !== false) {
            echo "   ✓ Индекс для delivery_time уже существует\n";
        }
    }
    
    // 6. Проверяем результат
    echo "\n6. Проверяем результат...\n";
    
    // Проверяем структуру
    $stmt = $pdo->query("DESCRIBE applications");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "   Структура таблицы applications:\n";
    foreach ($columns as $column) {
        if (in_array($column['Field'], ['status', 'payment_status', 'pickup_time', 'delivery_time'])) {
            echo "   - {$column['Field']}: {$column['Type']} (Default: {$column['Default']})\n";
        }
    }
    
    // Проверяем данные
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM applications");
    $count = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "\n   Количество записей: {$count['count']}\n";
    
    // Проверяем статусы
    $stmt = $pdo->query("SELECT DISTINCT status FROM applications");
    $statuses = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "   Уникальные статусы: " . implode(', ', $statuses) . "\n";
    
    // Проверяем payment_status
    $stmt = $pdo->query("SELECT DISTINCT payment_status FROM applications");
    $paymentStatuses = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "   Статусы оплаты: " . implode(', ', $paymentStatuses) . "\n";
    
    echo "\n✅ Миграция завершена успешно!\n";
    echo "База данных готова к использованию с новой структурой.\n";
    
} catch (Exception $e) {
    echo "\n❌ Ошибка: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
?>