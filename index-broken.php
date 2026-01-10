<?p<?php
/**
 * Главная страница CRM ProfTransfer
 * Обновленная версия с правильными ссылками
 */

// Базовые настройки
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Временный пользователь
$_SESSION['user_id'] = 1;
$_SESSION['user_name'] = 'Администратор';

// Подключаемся к базе данных
try {
    require_once 'config/database.php';
    $pdo = connectDatabase();
    
    // Получаем статистику
    $applications_count = $pdo->query("SELECT COUNT(*) FROM applications")->fetchColumn();
    $drivers_count = $pdo->query("SELECT COUNT(*) FROM drivers WHERE status = 'active'")->fetchColumn();
    $vehicles_count = $pdo->query("SELECT COUNT(*) FROM vehicles WHERE status = 'active'")->fetchColumn();
    $companies_count = $pdo->query("SELECT COUNT(*) FROM companies")->fetchColumn();
    
    // Последние заявки
    $recent_apps = $pdo->query("
        SELECT a.*, c.name as company_name, d.full_name as driver_name 
        FROM applications a 
        LEFT JOIN companies c ON a.company_id = c.id 
        LEFT JOIN drivers d ON a.driver_id = d.id 
        ORDER BY a.created_at DESC 
        LIMIT 5
    ")->fetchAll();
    
} catch (Exception $e) {
    // Если ошибка БД, используем заглушки
    $applications_count = $drivers_count = $vehicles_count = $companies_count = 0;
    $recent_apps = [];
    $db_error = $e->getMessage();
}

?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CRM ProfTransfer - Управление транспортом</title>
    <style>
        * { 
            margin: 0; 
            padding: 0; 
            box-sizing: border-box; 
        }
        
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            color: #333;
        }
        
        .header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 1rem 2rem;
            box-shadow: 0 2px 20px rgba(0,0,0,0.1);
            border-bottom: 1px solid rgba(255,255,255,0.2);
        }
        
        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .logo {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .logo h1 {
            color: #2c3e50;
            font-size: 1.8rem;
            font-weight: 700;
        }
        
        .user-info {
            color: #2c3e50;
            font-weight: 500;
        }
        
        .container {
            display: flex;
            max-width: 1200px;
            margin: 20px auto;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            min-height: 80vh;
        }
        
        .sidebar {
            width: 280px;
            background: linear-gradient(180deg, #2c3e50 0%, #3498db 100%);
            padding: 30px 0;
        }
        
        .sidebar-nav {
            list-style: none;
        }
        
        .nav-item {
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        
        .nav-link {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 18px 25px;
            color: #ecf0f1;
            text-decoration: none;
            transition: all 0.3s ease;
            font-weight: 500;
        }
        
        .nav-link:hover {
            background: rgba(255,255,255,0.15);
            padding-left: 30px;
        }
        
        .nav-icon {
            font-size: 1.3rem;
            width: 25px;
            text-align: center;
        }
        
        .main-content {
            flex: 1;
            padding: 40px;
            background: #f8f9fa;
        }
        
        .welcome-message {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            margin-bottom: 30px;
            border-left: 4px solid #3498db;
        }
        
        .welcome-message h2 {
            color: #2c3e50;
            margin-bottom: 10px;
            font-size: 1.8rem;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }
        
        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            text-align: center;
            border-left: 4px solid #3498db;
            transition: transform 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        .stat-number {
            font-size: 2.5rem;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 10px;
            display: block;
        }
        
        .stat-label {
            color: #7f8c8d;
            font-size: 1rem;
        }
        
        .recent-section {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        
        .section-title {
            color: #2c3e50;
            margin-bottom: 20px;
            font-size: 1.4rem;
            border-bottom: 2px solid #f8f9fa;
            padding-bottom: 10px;
        }
        
        .applications-list {
            display: grid;
            gap: 15px;
        }
        
        .app-card {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            border-left: 4px solid #3498db;
            transition: all 0.3s ease;
        }
        
        .app-card:hover {
            background: #e9ecef;
            transform: translateX(5px);
        }
        
        .app-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        
        .app-number {
            font-weight: bold;
            color: #2c3e50;
            font-size: 1.1rem;
        }
        
        .app-status {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        
        .status-new { background: #e3f2fd; color: #1976d2; }
        .status-assigned { background: #fff3e0; color: #f57c00; }
        .status-in_progress { background: #e8f5e8; color: #388e3c; }
        .status-completed { background: #f3e5f5; color: #7b1fa2; }
        
        .app-details {
            color: #6c757d;
            font-size: 0.9rem;
        }
        
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 30px;
        }
        
        .action-btn {
            background: linear-gradient(135deg, #3498db, #2980b9);
            color: white;
            border: none;
            padding: 15px 20px;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            text-align: center;
            text-decoration: none;
            display: block;
        }
        
        .action-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(52, 152, 219, 0.4);
        }
        
        .db-error {
            background: #ffebee;
            color: #d32f2f;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #d32f2f;
        }

        @media (max-width: 768px) {
            .container {
                flex-direction: column;
                margin: 10px;
            }
            
            .sidebar {
                width: 100%;
            }
            
            .stats-grid {
                grid-template-columns: 1fr 1fr;
            }
            
            .quick-actions {
                grid-template-columns: 1fr;
            }
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
            <div class="user-info">
                👋 Добро пожаловать, <?php echo $_SESSION['user_name']; ?>
            </div>
        </div>
    </div>

    <div class="container">
        <!-- Sidebar -->
        <div class="sidebar">
            <ul class="sidebar-nav">
                <li class="nav-item">
                    <a href="index.php" class="nav-link">
                        <span class="nav-icon">📊</span>
                        <span>Дашборд</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="applications.php" class="nav-link">
                        <span class="nav-icon">📝</span>
                        <span>Управление заявками</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="fixed-test-db.php" class="nav-link">
                        <span class="nav-icon">👨‍💼</span>
                        <span>Водители</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="fixed-test-db.php" class="nav-link">
                        <span class="nav-icon">🚗</span>
                        <span>Транспорт</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="fixed-test-db.php" class="nav-link">
                        <span class="nav-icon">🏢</span>
                        <span>Компании</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="fixed-test-db.php" class="nav-link">
                        <span class="nav-icon">📈</span>
                        <span>Аналитика</span>
                    </a>
                </li>
            </ul>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <?php if (isset($db_error)): ?>
                <div class="db-error">
                    <strong>Ошибка базы данных:</strong> <?php echo $db_error; ?>
                </div>
            <?php endif; ?>

            <div class="welcome-message">
                <h2>Добро пожаловать в CRM ProfTransfer! 🎉</h2>
                <p>Система управления транспортными заявками и автопарком. Всего в системе: 
                   <strong><?php echo $applications_count; ?> заявок</strong>, 
                   <strong><?php echo $drivers_count; ?> водителей</strong>, 
                   <strong><?php echo $vehicles_count; ?> единиц транспорта</strong>.</p>
            </div>

            <div class="stats-grid">
                <div class="stat-card">
                    <span class="stat-number"><?php echo $applications_count; ?></span>
                    <div class="stat-label">Всего заявок</div>
                </div>
                <div class="stat-card">
                    <span class="stat-number"><?php echo $drivers_count; ?></span>
                    <div class="stat-label">Активных водителей</div>
                </div>
                <div class="stat-card">
                    <span class="stat-number"><?php echo $vehicles_count; ?></span>
                    <div class="stat-label">Единиц транспорта</div>
                </div>
                <div class="stat-card">
                    <span class="stat-number"><?php echo $companies_count; ?></span>
                    <div class="stat-label">Компаний-клиентов</div>
                </div>
            </div>

            <div class="recent-section">
                <h3 class="section-title">📋 Последние заявки</h3>
                <div class="applications-list">
                    <?php if (!empty($recent_apps)): ?>
                        <?php foreach ($recent_apps as $app): ?>
                            <div class="app-card">
                                <div class="app-header">
                                    <div class="app-number"><?php echo htmlspecialchars($app['application_number']); ?></div>
                                    <div class="app-status status-<?php echo $app['status']; ?>">
                                        <?php 
                                        $statusText = [
                                            'new' => 'Новая',
                                            'assigned' => 'Назначена', 
                                            'in_progress' => 'В работе',
                                            'completed' => 'Завершена',
                                            'cancelled' => 'Отменена'
                                        ];
                                        echo $statusText[$app['status']] ?? $app['status'];
                                        ?>
                                    </div>
                                </div>
                                <div class="app-details">
                                    <strong>Пассажир:</strong> <?php echo htmlspecialchars($app['passenger_name']); ?><br>
                                    <strong>Маршрут:</strong> <?php echo htmlspecialchars(substr($app['pickup_address'], 0, 30) . '... → ' . substr($app['destination_address'], 0, 30) . '...'); ?><br>
                                    <strong>Водитель:</strong> <?php echo htmlspecialchars($app['driver_name'] ?? 'Не назначен'); ?><br>
                                    <strong>Дата:</strong> <?php echo date('d.m.Y H:i', strtotime($app['scheduled_date'])); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p style="text-align: center; color: #6c757d; padding: 20px;">Заявок пока нет</p>
                    <?php endif; ?>
                </div>
            </div>

            <div class="quick-actions">
                <a href="applications.php" class="action-btn">📝 Управление заявками</a>
                <a href="fixed-test-db.php" class="action-btn">👨‍💼 Управление водителями</a>
                <a href="fixed-test-db.php" class="action-btn">🚗 Управление транспортом</a>
                <a href="fixed-test-db.php" class="action-btn">📊 Полная аналитика</a>
            </div>
        </div>
    </div>

    <script>
        // Простые анимации
        document.addEventListener('DOMContentLoaded', function() {
            // Анимация появления карточек
            const cards = document.querySelectorAll('.stat-card, .app-card');
            cards.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';
                
                setTimeout(() => {
                    card.style.transition = 'all 0.5s ease';
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, index * 100);
            });
        });
    </script>
</body>
</html>hp
/**
 * Улучшенная версия CRM ProfTransfer с реальными данными из БД
 */

// Базовые настройки
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Временный пользователь
$_SESSION['user_id'] = 1;
$_SESSION['user_name'] = 'Администратор';

// Подключаемся к базе данных
try {
    require_once 'config/database.php';
    $pdo = connectDatabase();
    
    // Получаем статистику
    $applications_count = $pdo->query("SELECT COUNT(*) FROM applications")->fetchColumn();
    $drivers_count = $pdo->query("SELECT COUNT(*) FROM drivers WHERE status = 'active'")->fetchColumn();
    $vehicles_count = $pdo->query("SELECT COUNT(*) FROM vehicles WHERE status = 'active'")->fetchColumn();
    $companies_count = $pdo->query("SELECT COUNT(*) FROM companies")->fetchColumn();
    
    // Последние заявки
    $recent_apps = $pdo->query("
        SELECT a.*, c.name as company_name, d.full_name as driver_name 
        FROM applications a 
        LEFT JOIN companies c ON a.company_id = c.id 
        LEFT JOIN drivers d ON a.driver_id = d.id 
        ORDER BY a.created_at DESC 
        LIMIT 5
    ")->fetchAll();
    
} catch (Exception $e) {
    // Если ошибка БД, используем заглушки
    $applications_count = $drivers_count = $vehicles_count = $companies_count = 0;
    $recent_apps = [];
    $db_error = $e->getMessage();
}

?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CRM ProfTransfer - Управление транспортом</title>
    <style>
        * { 
            margin: 0; 
            padding: 0; 
            box-sizing: border-box; 
        }
        
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            color: #333;
        }
        
        .header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 1rem 2rem;
            box-shadow: 0 2px 20px rgba(0,0,0,0.1);
            border-bottom: 1px solid rgba(255,255,255,0.2);
        }
        
        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .logo {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .logo h1 {
            color: #2c3e50;
            font-size: 1.8rem;
            font-weight: 700;
        }
        
        .user-info {
            color: #2c3e50;
            font-weight: 500;
        }
        
        .container {
            display: flex;
            max-width: 1200px;
            margin: 20px auto;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            min-height: 80vh;
        }
        
        .sidebar {
            width: 280px;
            background: linear-gradient(180deg, #2c3e50 0%, #3498db 100%);
            padding: 30px 0;
        }
        
        .sidebar-nav {
            list-style: none;
        }
        
        .nav-item {
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        
        .nav-link {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 18px 25px;
            color: #ecf0f1;
            text-decoration: none;
            transition: all 0.3s ease;
            font-weight: 500;
        }
        
        .nav-link:hover {
            background: rgba(255,255,255,0.15);
            padding-left: 30px;
        }
        
        .nav-icon {
            font-size: 1.3rem;
            width: 25px;
            text-align: center;
        }
        
        .main-content {
            flex: 1;
            padding: 40px;
            background: #f8f9fa;
        }
        
        .welcome-message {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            margin-bottom: 30px;
            border-left: 4px solid #3498db;
        }
        
        .welcome-message h2 {
            color: #2c3e50;
            margin-bottom: 10px;
            font-size: 1.8rem;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }
        
        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            text-align: center;
            border-left: 4px solid #3498db;
            transition: transform 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        .stat-number {
            font-size: 2.5rem;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 10px;
            display: block;
        }
        
        .stat-label {
            color: #7f8c8d;
            font-size: 1rem;
        }
        
        .recent-section {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        
        .section-title {
            color: #2c3e50;
            margin-bottom: 20px;
            font-size: 1.4rem;
            border-bottom: 2px solid #f8f9fa;
            padding-bottom: 10px;
        }
        
        .applications-list {
            display: grid;
            gap: 15px;
        }
        
        .app-card {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            border-left: 4px solid #3498db;
            transition: all 0.3s ease;
        }
        
        .app-card:hover {
            background: #e9ecef;
            transform: translateX(5px);
        }
        
        .app-header {
            display: flex;
            justify-content: between;
            align-items: center;
            margin-bottom: 10px;
        }
        
        .app-number {
            font-weight: bold;
            color: #2c3e50;
            font-size: 1.1rem;
        }
        
        .app-status {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        
        .status-new { background: #e3f2fd; color: #1976d2; }
        .status-assigned { background: #fff3e0; color: #f57c00; }
        .status-in_progress { background: #e8f5e8; color: #388e3c; }
        .status-completed { background: #f3e5f5; color: #7b1fa2; }
        
        .app-details {
            color: #6c757d;
            font-size: 0.9rem;
        }
        
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 30px;
        }
        
        .action-btn {
            background: linear-gradient(135deg, #3498db, #2980b9);
            color: white;
            border: none;
            padding: 15px 20px;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            text-align: center;
            text-decoration: none;
            display: block;
        }
        
        .action-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(52, 152, 219, 0.4);
        }
        
        .db-error {
            background: #ffebee;
            color: #d32f2f;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #d32f2f;
        }

        @media (max-width: 768px) {
            .container {
                flex-direction: column;
                margin: 10px;
            }
            
            .sidebar {
                width: 100%;
            }
            
            .stats-grid {
                grid-template-columns: 1fr 1fr;
            }
            
            .quick-actions {
                grid-template-columns: 1fr;
            }
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
            <div class="user-info">
                👋 Добро пожаловать, <?php echo $_SESSION['user_name']; ?>
            </div>
        </div>
    </div>

    <div class="container">
        <!-- Sidebar -->
        <div class="sidebar">
            <ul class="sidebar-nav">
                <li class="nav-item">
                    <a href="index.php" class="nav-link">
                        <span class="nav-icon">📊</span>
                        <span>Дашборд</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="fixed-test-db.php" class="nav-link">
                        <span class="nav-icon">📝</span>
                        <span>Все заявки</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="fixed-test-db.php" class="nav-link">
                        <span class="nav-icon">👨‍💼</span>
                        <span>Водители</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="fixed-test-db.php" class="nav-link">
                        <span class="nav-icon">🚗</span>
                        <span>Транспорт</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="fixed-test-db.php" class="nav-link">
                        <span class="nav-icon">🏢</span>
                        <span>Компании</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="fixed-test-db.php" class="nav-link">
                        <span class="nav-icon">📈</span>
                        <span>Аналитика</span>
                    </a>
                </li>
            </ul>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <?php if (isset($db_error)): ?>
                <div class="db-error">
                    <strong>Ошибка базы данных:</strong> <?php echo $db_error; ?>
                </div>
            <?php endif; ?>

            <div class="welcome-message">
                <h2>Добро пожаловать в CRM ProfTransfer! 🎉</h2>
                <p>Система управления транспортными заявками и автопарком. Всего в системе: 
                   <strong><?php echo $applications_count; ?> заявок</strong>, 
                   <strong><?php echo $drivers_count; ?> водителей</strong>, 
                   <strong><?php echo $vehicles_count; ?> единиц транспорта</strong>.</p>
            </div>

            <div class="stats-grid">
                <div class="stat-card">
                    <span class="stat-number"><?php echo $applications_count; ?></span>
                    <div class="stat-label">Всего заявок</div>
                </div>
                <div class="stat-card">
                    <span class="stat-number"><?php echo $drivers_count; ?></span>
                    <div class="stat-label">Активных водителей</div>
                </div>
                <div class="stat-card">
                    <span class="stat-number"><?php echo $vehicles_count; ?></span>
                    <div class="stat-label">Единиц транспорта</div>
                </div>
                <div class="stat-card">
                    <span class="stat-number"><?php echo $companies_count; ?></span>
                    <div class="stat-label">Компаний-клиентов</div>
                </div>
            </div>

            <div class="recent-section">
                <h3 class="section-title">📋 Последние заявки</h3>
                <div class="applications-list">
                    <?php if (!empty($recent_apps)): ?>
                        <?php foreach ($recent_apps as $app): ?>
                            <div class="app-card">
                                <div class="app-header">
                                    <div class="app-number"><?php echo htmlspecialchars($app['application_number']); ?></div>
                                    <div class="app-status status-<?php echo $app['status']; ?>">
                                        <?php 
                                        $statusText = [
                                            'new' => 'Новая',
                                            'assigned' => 'Назначена', 
                                            'in_progress' => 'В работе',
                                            'completed' => 'Завершена',
                                            'cancelled' => 'Отменена'
                                        ];
                                        echo $statusText[$app['status']] ?? $app['status'];
                                        ?>
                                    </div>
                                </div>
                                <div class="app-details">
                                    <strong>Пассажир:</strong> <?php echo htmlspecialchars($app['passenger_name']); ?><br>
                                    <strong>Маршрут:</strong> <?php echo htmlspecialchars(substr($app['pickup_address'], 0, 30) . '... → ' . substr($app['destination_address'], 0, 30) . '...'); ?><br>
                                    <strong>Водитель:</strong> <?php echo htmlspecialchars($app['driver_name'] ?? 'Не назначен'); ?><br>
                                    <strong>Дата:</strong> <?php echo date('d.m.Y H:i', strtotime($app['scheduled_date'])); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p style="text-align: center; color: #6c757d; padding: 20px;">Заявок пока нет</p>
                    <?php endif; ?>
                </div>
            </div>

            <div class="quick-actions">
                <a href="fixed-test-db.php" class="action-btn">📝 Создать заявку</a>
                <a href="fixed-test-db.php" class="action-btn">👨‍💼 Управление водителями</a>
                <a href="fixed-test-db.php" class="action-btn">🚗 Управление транспортом</a>
                <a href="fixed-test-db.php" class="action-btn">📊 Полная аналитика</a>
            </div>
        </div>
    </div>

    <script>
        // Простые анимации
        document.addEventListener('DOMContentLoaded', function() {
            // Анимация появления карточек
            const cards = document.querySelectorAll('.stat-card, .app-card');
            cards.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';
                
                setTimeout(() => {
                    card.style.transition = 'all 0.5s ease';
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, index * 100);
            });
        });
    </script>
</body>
</html>