<?php
/**
 * Скрипт для применения миграции к базе данных
 */
require_once __DIR__ . '/config.php';

echo "Применение миграции к базе данных...\n";

try {
    // Применяем миграцию
    $sql = file_get_contents(__DIR__ . '/sql/migrate_add_application_fields.sql');
    
    // Разбиваем SQL на отдельные команды
    $commands = array_filter(array_map('trim', explode(';', $sql)));
    
    foreach ($commands as $command) {
        if (!empty($command) && !preg_match('/^--/', $command)) {
            try {
                $pdo->exec($command);
                echo "✅ Выполнено: " . substr($command, 0, 50) . "...\n";
            } catch (PDOException $e) {
                echo "⚠️ Ошибка: " . $e->getMessage() . "\n";
                echo "Команда: " . substr($command, 0, 100) . "...\n\n";
            }
        }
    }
    
    echo "Миграция завершена!\n";
    
    // Проверяем результат
    $result = $pdo->query("DESCRIBE applications")->fetchAll(PDO::FETCH_ASSOC);
    echo "\nСтруктура таблицы applications:\n";
    foreach ($result as $column) {
        echo "- " . $column['Field'] . " (" . $column['Type'] . ")\n";
    }
    
} catch (Exception $e) {
    echo "❌ Ошибка миграции: " . $e->getMessage() . "\n";
}
?>