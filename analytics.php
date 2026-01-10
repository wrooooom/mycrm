<?php
require_once 'config.php';
require_once 'auth.php';
requireAdmin(); // Требуем права администратора
?>
<?php
/**
 * Страница аналитики и отчетов
 */

session_start();
require_once 'config/database.php';

try {
    $pdo = connectDatabase();
    
    // Статистика по заявкам
    $applications_stats = $pdo->query("
        SELECT 
            COUNT(*) as total,
            SUM(price) as total_revenue,
            AVG(price) as avg_price,
            COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed,
            COUNT(CASE WHEN status = 'new' THEN 1 END) as new,
            COUNT(CASE WHEN status = 'in_progress' THEN 1 END) as in_progress
        FROM applications
    ")->fetch();
    
    // Статистика по дням (последние 7 дней)
    $daily_stats = $pdo->query("
        SELECT 
            DATE(created_at) as date,
            COUNT(*) as applications,
            SUM(price) as revenue
        FROM applications 
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        GROUP BY DATE(created_at)
        ORDER BY date DESC
    ")->fetchAll();
    
    // Топ компаний по доходу
    $top_companies = $pdo->query("
        SELECT 
            c.name,
            COUNT(a.id) as applications,
            SUM(a.price) as revenue
        FROM companies c
        LEFT JOIN applications a ON c.id = a.company_id
        GROUP BY c.id
        ORDER BY revenue DESC
        LIMIT 5
    ")->fetchAll();
    
    // Топ водителей по заявкам
    $top_drivers = $pdo->query("
        SELECT 
            d.full_name,
            COUNT(a.id) as applications,
            SUM(a.price) as revenue
        FROM drivers d
        LEFT JOIN applications a ON d.id = a.driver_id
        WHERE a.status = 'completed'
        GROUP BY d.id
        ORDER BY applications DESC
        LIMIT 5
    ")->fetchAll();
    
} catch (Exception $e) {
    $error = $e->getMessage();
    $applications_stats = $daily_stats = $top_companies = $top_drivers = [];
}

?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Аналитика - CRM ProfTransfer</title>
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
            max-width: 1400px;
            margin: 0 auto;
        }
        
        .logo { display: flex; align-items: center; gap: 12px; }
        .logo h1 { color: #2c3e50; font-size: 1.8rem; }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        .page-header {
            margin-bottom: 30px;
        }
        
        .stats-overview {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
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
        
        .revenue { color: #27ae60; }
        .applications { color: #3498db; }
        
        .analytics-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 25px;
            margin-bottom: 40px;
        }
        
        @media (max-width: 1024px) {
            .analytics-grid {
                grid-template-columns: 1fr;
            }
        }
        
        .chart-container {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .chart-title {
            color: #2c3e50;
            margin-bottom: 20px;
            font-size: 1.2rem;
            border-bottom: 2px solid #f8f9fa;
            padding-bottom: 10px;
        }
        
        .top-list {
            list-style: none;
        }
        
        .top-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid #f8f9fa;
        }
        
        .top-item:last-child {
            border-bottom: none;
        }
        
        .item-name {
            font-weight: 500;
            color: #2c3e50;
        }
        
        .item-stats {
            color: #7f8c8d;
            font-size: 0.9rem;
        }
        
        .daily-stats {
            display: grid;
            gap: 10px;
        }
        
        .day-stat {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 15px;
            background: #f8f9fa;
            border-radius: 8px;
        }
        
        .day-date {
            font-weight: 500;
            color: #2c3e50;
        }
        
        .day-numbers {
            text-align: right;
        }
        
        .day-applications {
            color: #3498db;
            font-weight: 500;
        }
        
        .day-revenue {
            color: #27ae60;
            font-size: 0.9rem;
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
                <a href="applications.php" class="btn">📝 Заявки</a>
                <a href="drivers.php" class="btn">👨‍💼 Водители</a>
                <a href="vehicles.php" class="btn">🚗 Транспорт</a>
                <a href="companies.php" class="btn">🏢 Компании</a>
            </div>
        </div>
    </div>

    <div class="container">
        <div class="page-header">
            <h2>📈 Аналитика и отчеты</h2>
            <p>Общая статистика и ключевые показатели эффективности</p>
        </div>

        <?php if (isset($error)): ?>
            <div style="background: #ffebee; color: #d32f2f; padding: 15px; border-radius: 5px; margin-bottom: 20px;">
                Ошибка: <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <!-- Основная статистика -->
        <div class="stats-overview">
            <div class="stat-card">
                <span class="stat-number applications"><?php echo $applications_stats['total'] ?? 0; ?></span>
                <div class="stat-label">Всего заявок</div>
            </div>
            <div class="stat-card">
                <span class="stat-number revenue"><?php echo number_format($applications_stats['total_revenue'] ?? 0, 0, '', ' '); ?> ₽</span>
                <div class="stat-label">Общий доход</div>
            </div>
            <div class="stat-card">
                <span class="stat-number"><?php echo number_format($applications_stats['avg_price'] ?? 0, 0, '', ' '); ?> ₽</span>
                <div class="stat-label">Средний чек</div>
            </div>
            <div class="stat-card">
                <span class="stat-number"><?php echo $applications_stats['completed'] ?? 0; ?></span>
                <div class="stat-label">Завершено заявок</div>
            </div>
        </div>

        <!-- Аналитика -->
        <div class="analytics-grid">
            <!-- Статистика по дням -->
            <div class="chart-container">
                <h3 class="chart-title">📅 Статистика за последние 7 дней</h3>
                <div class="daily-stats">
                    <?php if (!empty($daily_stats)): ?>
                        <?php foreach ($daily_stats as $day): ?>
                            <div class="day-stat">
                                <div class="day-date">
                                    <?php echo date('d.m.Y', strtotime($day['date'])); ?>
                                </div>
                                <div class="day-numbers">
                                    <div class="day-applications"><?php echo $day['applications']; ?> заявок</div>
                                    <div class="day-revenue"><?php echo number_format($day['revenue'] ?? 0, 0, '', ' '); ?> ₽</div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p style="text-align: center; color: #7f8c8d; padding: 20px;">Нет данных за последние 7 дней</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Топ компаний -->
            <div class="chart-container">
                <h3 class="chart-title">🏆 Топ компаний по доходу</h3>
                <ul class="top-list">
                    <?php if (!empty($top_companies)): ?>
                        <?php foreach ($top_companies as $company): ?>
                            <li class="top-item">
                                <span class="item-name"><?php echo htmlspecialchars($company['name']); ?></span>
                                <span class="item-stats">
                                    <?php echo number_format($company['revenue'] ?? 0, 0, '', ' '); ?> ₽
                                    <br><small><?php echo $company['applications']; ?> заявок</small>
                                </span>
                            </li>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p style="text-align: center; color: #7f8c8d; padding: 20px;">Нет данных о компаниях</p>
                    <?php endif; ?>
                </ul>
            </div>

            <!-- Топ водителей -->
            <div class="chart-container">
                <h3 class="chart-title">👑 Топ водителей по заявкам</h3>
                <ul class="top-list">
                    <?php if (!empty($top_drivers)): ?>
                        <?php foreach ($top_drivers as $driver): ?>
                            <li class="top-item">
                                <span class="item-name"><?php echo htmlspecialchars($driver['full_name']); ?></span>
                                <span class="item-stats">
                                    <?php echo $driver['applications']; ?> заявок
                                    <br><small><?php echo number_format($driver['revenue'] ?? 0, 0, '', ' '); ?> ₽</small>
                                </span>
                            </li>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p style="text-align: center; color: #7f8c8d; padding: 20px;">Нет данных о водителях</p>
                    <?php endif; ?>
                </ul>
            </div>

            <!-- Статусы заявок -->
            <div class="chart-container">
                <h3 class="chart-title">📊 Статусы заявок</h3>
                <ul class="top-list">
                    <li class="top-item">
                        <span class="item-name">🆕 Новые</span>
                        <span class="item-stats"><?php echo $applications_stats['new'] ?? 0; ?> заявок</span>
                    </li>
                    <li class="top-item">
                        <span class="item-name">⚡ В работе</span>
                        <span class="item-stats"><?php echo $applications_stats['in_progress'] ?? 0; ?> заявок</span>
                    </li>
                    <li class="top-item">
                        <span class="item-name">✅ Завершено</span>
                        <span class="item-stats"><?php echo $applications_stats['completed'] ?? 0; ?> заявок</span>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</body>
</html>