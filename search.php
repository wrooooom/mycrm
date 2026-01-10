<?php
require_once 'config.php';
require_once 'auth.php';
requireLogin(); // Требуем авторизацию

// Для административных страниц (companies.php, analytics.php) используйте:
// requireAdmin();
?>
<?php
/**
 * Универсальный поиск по CRM
 */

session_start();
require_once 'config/database.php';

$search_query = $_GET['q'] ?? '';
$results = [];

if (!empty($search_query)) {
    try {
        $pdo = connectDatabase();
        
        // Поиск по заявкам
        $applications = $pdo->prepare("
            SELECT a.*, c.name as company_name, d.full_name as driver_name
            FROM applications a
            LEFT JOIN companies c ON a.company_id = c.id
            LEFT JOIN drivers d ON a.driver_id = d.id
            WHERE a.application_number LIKE ? OR a.passenger_name LIKE ? OR a.passenger_phone LIKE ?
            ORDER BY a.created_at DESC
            LIMIT 10
        ");
        $applications->execute(["%$search_query%", "%$search_query%", "%$search_query%"]);
        $results['applications'] = $applications->fetchAll();
        
        // Поиск по водителям
        $drivers = $pdo->prepare("
            SELECT * FROM drivers 
            WHERE full_name LIKE ? OR phone LIKE ? OR email LIKE ? OR license_number LIKE ?
            ORDER BY created_at DESC
            LIMIT 10
        ");
        $drivers->execute(["%$search_query%", "%$search_query%", "%$search_query%", "%$search_query%"]);
        $results['drivers'] = $drivers->fetchAll();
        
        // Поиск по транспорту
        $vehicles = $pdo->prepare("
            SELECT * FROM vehicles 
            WHERE model LIKE ? OR license_plate LIKE ?
            ORDER BY created_at DESC
            LIMIT 10
        ");
        $vehicles->execute(["%$search_query%", "%$search_query%"]);
        $results['vehicles'] = $vehicles->fetchAll();
        
        // Поиск по компаниям
        $companies = $pdo->prepare("
            SELECT * FROM companies 
            WHERE name LIKE ? OR contact_person LIKE ? OR phone LIKE ? OR email LIKE ?
            ORDER BY created_at DESC
            LIMIT 10
        ");
        $companies->execute(["%$search_query%", "%$search_query%", "%$search_query%", "%$search_query%"]);
        $results['companies'] = $companies->fetchAll();
        
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Поиск - CRM ProfTransfer</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            background: #f5f5f5;
            color: #333;
        }
        .header {
            background: white;
            padding: 1rem 2rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 1200px;
            margin: 0 auto;
        }
        .logo { display: flex; align-items: center; gap: 12px; }
        .logo h1 { color: #2c3e50; font-size: 1.8rem; }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }
        .search-form {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        .search-input {
            display: flex;
            gap: 10px;
        }
        .search-input input {
            flex: 1;
            padding: 12px 15px;
            border: 2px solid #3498db;
            border-radius: 8px;
            font-size: 16px;
        }
        .search-input button {
            background: #3498db;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
        }
        .results-section {
            margin-bottom: 30px;
        }
        .section-title {
            color: #2c3e50;
            margin-bottom: 15px;
            font-size: 1.3rem;
        }
        .results-grid {
            display: grid;
            gap: 15px;
        }
        .result-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            border-left: 4px solid #3498db;
        }
        .result-title {
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 5px;
        }
        .result-details {
            color: #7f8c8d;
            font-size: 0.9rem;
        }
        .result-type {
            background: #e3f2fd;
            color: #1976d2;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 0.8rem;
            display: inline-block;
            margin-bottom: 8px;
        }
        .no-results {
            text-align: center;
            padding: 40px;
            color: #7f8c8d;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .btn {
            background: #3498db;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 4px;
            text-decoration: none;
            display: inline-block;
            font-size: 12px;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-content">
            <div class="logo">
                <div style="font-size: 2rem;">🚗</div>
                <h1>CRM ProfTransfer</h1>
            </div>
            <div>
                <a href="index.php" class="btn">📊 Дашборд</a>
            </div>
        </div>
    </div>

    <div class="container">
        <div class="search-form">
            <h2 style="margin-bottom: 20px; color: #2c3e50;">🔍 Поиск по CRM системе</h2>
            <form method="GET" class="search-input">
                <input type="text" name="q" value="<?php echo htmlspecialchars($search_query); ?>" 
                       placeholder="Введите номер заявки, ФИО, телефон, госномер..." required>
                <button type="submit">Найти</button>
            </form>
        </div>

        <?php if (!empty($search_query)): ?>
            <div style="margin-bottom: 20px; color: #7f8c8d;">
                Результаты поиска для: "<strong><?php echo htmlspecialchars($search_query); ?></strong>"
            </div>

            <?php if (empty(array_filter($results))): ?>
                <div class="no-results">
                    <h3>😔 Ничего не найдено</h3>
                    <p>Попробуйте изменить поисковый запрос</p>
                </div>
            <?php else: ?>
                <!-- Заявки -->
                <?php if (!empty($results['applications'])): ?>
                    <div class="results-section">
                        <h3 class="section-title">📝 Заявки (<?php echo count($results['applications']); ?>)</h3>
                        <div class="results-grid">
                            <?php foreach ($results['applications'] as $app): ?>
                                <div class="result-card">
                                    <span class="result-type">Заявка</span>
                                    <div class="result-title"><?php echo htmlspecialchars($app['application_number']); ?></div>
                                    <div class="result-details">
                                        <strong>Пассажир:</strong> <?php echo htmlspecialchars($app['passenger_name']); ?><br>
                                        <strong>Телефон:</strong> <?php echo htmlspecialchars($app['passenger_phone']); ?><br>
                                        <strong>Статус:</strong> <?php echo $app['status']; ?>
                                    </div>
                                    <a href="applications.php" class="btn">Перейти к заявке</a>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Водители -->
                <?php if (!empty($results['drivers'])): ?>
                    <div class="results-section">
                        <h3 class="section-title">👨‍💼 Водители (<?php echo count($results['drivers']); ?>)</h3>
                        <div class="results-grid">
                            <?php foreach ($results['drivers'] as $driver): ?>
                                <div class="result-card">
                                    <span class="result-type">Водитель</span>
                                    <div class="result-title"><?php echo htmlspecialchars($driver['full_name']); ?></div>
                                    <div class="result-details">
                                        <strong>Телефон:</strong> <?php echo htmlspecialchars($driver['phone']); ?><br>
                                        <strong>Права:</strong> <?php echo htmlspecialchars($driver['license_number']); ?>
                                    </div>
                                    <a href="drivers.php" class="btn">Перейти к водителю</a>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Транспорт -->
                <?php if (!empty($results['vehicles'])): ?>
                    <div class="results-section">
                        <h3 class="section-title">🚗 Транспорт (<?php echo count($results['vehicles']); ?>)</h3>
                        <div class="results-grid">
                            <?php foreach ($results['vehicles'] as $vehicle): ?>
                                <div class="result-card">
                                    <span class="result-type">Транспорт</span>
                                    <div class="result-title"><?php echo htmlspecialchars($vehicle['model']); ?></div>
                                    <div class="result-details">
                                        <strong>Госномер:</strong> <?php echo htmlspecialchars($vehicle['license_plate']); ?><br>
                                        <strong>Тип:</strong> <?php echo $vehicle['vehicle_type']; ?>
                                    </div>
                                    <a href="vehicles.php" class="btn">Перейти к транспорту</a>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Компании -->
                <?php if (!empty($results['companies'])): ?>
                    <div class="results-section">
                        <h3 class="section-title">🏢 Компании (<?php echo count($results['companies']); ?>)</h3>
                        <div class="results-grid">
                            <?php foreach ($results['companies'] as $company): ?>
                                <div class="result-card">
                                    <span class="result-type">Компания</span>
                                    <div class="result-title"><?php echo htmlspecialchars($company['name']); ?></div>
                                    <div class="result-details">
                                        <strong>Контакт:</strong> <?php echo htmlspecialchars($company['contact_person']); ?><br>
                                        <strong>Телефон:</strong> <?php echo htmlspecialchars($company['phone']); ?>
                                    </div>
                                    <a href="companies.php" class="btn">Перейти к компании</a>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</body>
</html>