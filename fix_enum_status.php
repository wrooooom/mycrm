<?php
/**
 * Исправленная миграция для обновления enum статусов
 */
require_once __DIR__ . '/config.php';

echo "Применение исправленной миграции...\n";

try {
    // Сначала обновляем данные в поле status
    echo "1. Обновляем существующие статусы...\n";
    $pdo->exec("UPDATE applications SET status = 'new' WHERE status = '' OR status IS NULL");
    $pdo->exec("UPDATE applications SET status = 'assigned' WHERE status = 'confirmed'");
    $pdo->exec("UPDATE applications SET status = 'in_progress' WHERE status = 'inwork'");
    echo "✅ Статусы обновлены\n";
    
    // Теперь можем безопасно изменить enum
    echo "2. Обновляем структуру enum для поля status...\n";
    $pdo->exec("ALTER TABLE applications MODIFY COLUMN status ENUM('new', 'assigned', 'in_progress', 'completed', 'cancelled') DEFAULT 'new'");
    echo "✅ Enum status обновлен\n";
    
    // Проверяем результат
    echo "3. Проверяем результат...\n";
    $result = $pdo->query("SELECT status, COUNT(*) as count FROM applications GROUP BY status")->fetchAll(PDO::FETCH_ASSOC);
    echo "Распределение статусов:\n";
    foreach ($result as $row) {
        echo "- {$row['status']}: {$row['count']} записей\n";
    }
    
    // Обновляем недостающие поля payment_status если они пустые
    echo "4. Устанавливаем значение payment_status по умолчанию...\n";
    $pdo->exec("UPDATE applications SET payment_status = 'pending' WHERE payment_status IS NULL OR payment_status = ''");
    echo "✅ Payment status установлен\n";
    
    echo "\n🎉 Миграция успешно завершена!\n";
    
    // Проверяем финальную структуру
    echo "\nФинальная структура таблицы applications:\n";
    $columns = $pdo->query("SHOW COLUMNS FROM applications")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($columns as $col) {
        echo "- " . $col['Field'] . " (" . $col['Type'] . ")\n";
    }
    
} catch (Exception $e) {
    echo "❌ Ошибка: " . $e->getMessage() . "\n";
}
?>