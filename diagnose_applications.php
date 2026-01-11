<?php
/**
 * Диагностика текущих данных в таблице applications
 */
require_once __DIR__ . '/config.php';

echo "Диагностика таблицы applications...\n";

try {
    // Показываем текущие статусы
    echo "Текущие статусы в таблице:\n";
    $statuses = $pdo->query("SELECT DISTINCT status FROM applications ORDER BY status")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($statuses as $status) {
        $count = $pdo->query("SELECT COUNT(*) FROM applications WHERE status = '{$status['status']}'")->fetchColumn();
        echo "- '{$status['status']}': $count записей\n";
    }
    
    // Показываем структуру поля status
    echo "\nТекущая структура поля status:\n";
    $structure = $pdo->query("SHOW COLUMNS FROM applications WHERE Field = 'status'")->fetch(PDO::FETCH_ASSOC);
    echo "Type: {$structure['Type']}\n";
    echo "Null: {$structure['Null']}\n";
    echo "Default: {$structure['Default']}\n";
    
    // Показываем несколько записей для понимания данных
    echo "\nПримеры записей:\n";
    $samples = $pdo->query("SELECT id, application_number, status, customer_name FROM applications LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($samples as $sample) {
        echo "- ID: {$sample['id']}, Номер: {$sample['application_number']}, Статус: '{$sample['status']}', Клиент: {$sample['customer_name']}\n";
    }
    
} catch (Exception $e) {
    echo "Ошибка: " . $e->getMessage() . "\n";
}
?>