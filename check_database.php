<?php
/**
 * Скрипт для проверки структуры таблицы applications
 */

require_once 'config/database.php';

try {
    $pdo = connectDatabase();
    
    echo "Подключение к базе данных установлено\n\n";
    
    // Получаем структуру таблицы applications
    $stmt = $pdo->query("DESCRIBE applications");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Структура таблицы applications:\n";
    echo str_repeat('-', 80) . "\n";
    printf("%-20s %-30s %-10s %-10s %-10s\n", "Field", "Type", "Null", "Key", "Default");
    echo str_repeat('-', 80) . "\n";
    
    foreach ($columns as $column) {
        printf("%-20s %-30s %-10s %-10s %-10s\n", 
               $column['Field'], 
               $column['Type'], 
               $column['Null'], 
               $column['Key'], 
               $column['Default']);
    }
    
    echo "\n";
    
    // Проверяем данные
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM applications");
    $count = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "Количество записей в таблице: " . $count['count'] . "\n\n";
    
    // Показываем образцы данных
    $stmt = $pdo->query("SELECT id, application_number, status, payment_status, trip_date FROM applications LIMIT 5");
    $samples = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if ($samples) {
        echo "Образцы данных:\n";
        echo str_repeat('-', 80) . "\n";
        printf("%-5s %-15s %-15s %-15s %-20s\n", "ID", "Номер", "Статус", "Оплата", "Дата поездки");
        echo str_repeat('-', 80) . "\n";
        
        foreach ($samples as $sample) {
            printf("%-5s %-15s %-15s %-15s %-20s\n", 
                   $sample['id'], 
                   $sample['application_number'], 
                   $sample['status'], 
                   $sample['payment_status'] ?? 'N/A', 
                   $sample['trip_date']);
        }
    }
    
    echo "\nПроверка завершена!\n";
    
} catch (Exception $e) {
    echo "Ошибка: " . $e->getMessage() . "\n";
}
?>