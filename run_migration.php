<?php
/**
 * Скрипт для выполнения миграции базы данных
 */

require_once 'config/database.php';

try {
    $pdo = connectDatabase();
    
    echo "Подключение к базе данных установлено\n";
    
    // Добавляем недостающие поля в таблицу applications
    $alterQuery = "ALTER TABLE applications 
                  ADD COLUMN payment_status ENUM('pending', 'paid', 'refunded', 'cancelled') DEFAULT 'pending' AFTER status,
                  ADD COLUMN pickup_time DATETIME NULL AFTER trip_date,
                  ADD COLUMN delivery_time DATETIME NULL AFTER pickup_time";
    
    try {
        $pdo->exec($alterQuery);
        echo "Поля успешно добавлены в таблицу applications\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
            echo "Некоторые поля уже существуют\n";
        } else {
            throw $e;
        }
    }
    
    // Обновляем существующие записи
    $updateQuery = "UPDATE applications SET status = CASE 
        WHEN status = 'new' THEN 'new'
        WHEN status = 'confirmed' THEN 'assigned'
        WHEN status = 'inwork' THEN 'in_progress'
        WHEN status = 'completed' THEN 'completed'
        WHEN status = 'cancelled' THEN 'cancelled'
        ELSE 'new'
    END";
    
    $pdo->exec($updateQuery);
    echo "Статусы обновлены\n";
    
    // Устанавливаем pickup_time и delivery_time для существующих записей
    $updateTimesQuery = "UPDATE applications SET 
        pickup_time = COALESCE(pickup_time, trip_date),
        delivery_time = COALESCE(delivery_time, DATE_ADD(trip_date, INTERVAL 2 HOUR))";
    
    $pdo->exec($updateTimesQuery);
    echo "Время подачи и доставки установлено\n";
    
    echo "Миграция успешно выполнена!\n";
    
} catch (Exception $e) {
    echo "Ошибка: " . $e->getMessage() . "\n";
}
?>