<?php
require_once 'config.php';
require_once 'auth.php';
requireLogin(); // Требуем авторизацию

// Для административных страниц (companies.php, analytics.php) используйте:
// requireAdmin();
?>
<?php
/**
 * Добавление тестовых заявок
 */

echo "<!DOCTYPE html>
<html lang='ru'>
<head>
    <meta charset='UTF-8'>
    <title>Добавление тестовых заявок</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .success { background: #e8f5e8; padding: 15px; margin: 10px 0; border-radius: 5px; }
        .error { background: #ffebee; padding: 15px; margin: 10px 0; border-radius: 5px; }
        .info { background: #e3f2fd; padding: 15px; margin: 10px 0; border-radius: 5px; }
    </style>
</head>
<body>
    <h1>📝 Добавление тестовых заявок</h1>";

// Данные для подключения
$host = 'localhost';
$dbname = 'ca991909_crm';
$username = 'ca991909_crm';
$password = '!Mazay199';

try {
    $dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";
    $pdo = new PDO($dsn, $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<div class='success'>✅ Подключение к БД установлено</div>";
    
    // Генерируем уникальные номера заявок
    $application_numbers = [
        'APP-' . date('Ymd') . '-001',
        'APP-' . date('Ymd') . '-002', 
        'APP-' . date('Ymd') . '-003',
        'APP-' . date('Ymd') . '-004',
        'APP-' . date('Ymd') . '-005'
    ];
    
    // Тестовые заявки
    $applications = [
        [
            $application_numbers[0], 1, 1, 1, 
            'Москва, аэропорт Шереметьево', 
            'Москва, отель Ритц-Карлтон',
            'Джон Смит',
            '+79161234567',
            date('Y-m-d H:i:s', strtotime('+2 hours')),
            'new',
            2500.00,
            'Встреча с табличкой'
        ],
        [
            $application_numbers[1], 2, 2, 2,
            'Москва, вокзал Ленинградский',
            'Москва, бизнес-центр Сити',
            'Анна Петрова', 
            '+79167654321',
            date('Y-m-d H:i:s', strtotime('+3 hours')),
            'assigned',
            1800.00,
            'Срочная поездка'
        ],
        [
            $application_numbers[2], 1, 3, 3,
            'Москва, отель Метрополь',
            'Москва, аэропорт Домодедово',
            'Михаил Иванов',
            '+79169876543',
            date('Y-m-d H:i:s', strtotime('+5 hours')),
            'in_progress',
            2200.00,
            'Групповой трансфер'
        ],
        [
            $application_numbers[3], 3, 1, 4,
            'Москва, Красная площадь',
            'Москва, район Арбат',
            'Сара Джонсон',
            '+79161112233',
            date('Y-m-d H:i:s', strtotime('+1 day')),
            'new',
            1500.00,
            'Экскурсионная поездка'
        ],
        [
            $application_numbers[4], 2, 2, 1,
            'Москва, гостиница Украина',
            'Москва, Киевский вокзал',
            'Петр Васильев',
            '+79164445566',
            date('Y-m-d H:i:s', strtotime('+6 hours')),
            'completed',
            1900.00,
            'Деловая встреча'
        ]
    ];
    
    $added_count = 0;
    
    foreach ($applications as $app) {
        $stmt = $pdo->prepare("INSERT INTO applications (
            application_number, company_id, driver_id, vehicle_id, 
            pickup_address, destination_address, passenger_name, passenger_phone,
            scheduled_date, status, price, notes
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
        try {
            $stmt->execute($app);
            $added_count++;
            echo "<div class='success'>✅ Добавлена заявка: $app[0]</div>";
        } catch (PDOException $e) {
            echo "<div class='error'>❌ Ошибка добавления заявки $app[0]: " . $e->getMessage() . "</div>";
        }
    }
    
    // Показываем итоги
    echo "<div class='info'>
            <h3>📊 Итоги добавления заявок:</h3>
            <p><strong>Успешно добавлено:</strong> $added_count заявок</p>
        </div>";
    
    // Показываем общую статистику
    $tables = ['companies', 'drivers', 'vehicles', 'applications'];
    echo "<div class='info'><h3>📈 Общая статистика базы данных:</h3>";
    foreach ($tables as $table) {
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM $table");
        $count = $stmt->fetch()['count'];
        echo "<p><strong>$table:</strong> $count записей</p>";
    }
    echo "</div>";
    
    echo "<div class='success'>
            <h3>🎉 База данных CRM готова к работе!</h3>
            <p><a href='/fixed-test-db.php'>🔍 Посмотреть все данные</a></p>
            <p><a href='/test-db.php'>🧪 Протестировать основное подключение</a></p>
        </div>";
    
} catch (PDOException $e) {
    echo "<div class='error'>❌ Ошибка: " . $e->getMessage() . "</div>";
}

echo "</body></html>";
?>