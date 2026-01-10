<?php
require_once 'config.php';
require_once 'auth.php';
requireAdmin(); // Требуем права администратора
?>
<?php
/**
 * Страница управления компаниями-клиентами
 * Обновленная версия с функциями редактирования и удаления
 */

session_start();
require_once 'config/database.php';

// Обработка сообщений об успехе/ошибке
$success = $_GET['success'] ?? '';
$error = $_GET['error'] ?? '';

try {
    $pdo = connectDatabase();
    
    // Получаем все компании
    $companies = $pdo->query("
        SELECT c.*, 
               COUNT(a.id) as applications_count,
               SUM(a.price) as total_revenue
        FROM companies c
        LEFT JOIN applications a ON c.id = a.company_id
        GROUP BY c.id
        ORDER BY c.created_at DESC
    ")->fetchAll();
    
} catch (Exception $e) {
    $error = $e->getMessage();
    $companies = [];
}

?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Управление компаниями - CRM ProfTransfer</title>
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
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }
        
        .btn {
            background: #3498db;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 6px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            font-size: 14px;
        }
        
        .btn:hover { background: #2980b9; }
        .btn-success { background: #27ae60; }
        .btn-success:hover { background: #219a52; }
        
        .companies-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
            gap: 25px;
            margin-bottom: 40px;
        }
        
        .company-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            overflow: hidden;
            transition: transform 0.3s ease;
        }
        
        .company-card:hover {
            transform: translateY(-5px);
        }
        
        .company-header {
            background: linear-gradient(135deg, #9b59b6, #8e44ad);
            color: white;
            padding: 20px;
        }
        
        .company-name {
            font-size: 1.4rem;
            font-weight: 600;
            margin-bottom: 10px;
        }
        
        .company-contacts {
            display: flex;
            flex-direction: column;
            gap: 5px;
            font-size: 0.9rem;
            opacity: 0.9;
        }
        
        .company-body {
            padding: 20px;
        }
        
        .company-info {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-bottom: 15px;
        }
        
        .info-item {
            display: flex;
            flex-direction: column;
        }
        
        .info-label {
            font-size: 0.8rem;
            color: #7f8c8d;
            margin-bottom: 3px;
        }
        
        .info-value {
            font-weight: 500;
            color: #2c3e50;
        }
        
        .revenue {
            color: #27ae60;
            font-weight: bold;
        }
        
        .company-address {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin: 15px 0;
            font-size: 0.9rem;
            color: #6c757d;
        }
        
        .company-actions {
            display: flex;
            gap: 8px;
            margin-top: 15px;
            flex-wrap: wrap;
        }
        
        .btn-sm {
            padding: 8px 12px;
            font-size: 11px;
            border-radius: 4px;
            text-align: center;
            text-decoration: none;
            flex: 1;
            min-width: 80px;
        }
        
        .btn-edit { 
            background: #f39c12; 
            color: white;
            border: none;
            cursor: pointer;
        }
        .btn-edit:hover { background: #e67e22; }
        
        .btn-contacts { 
            background: #3498db; 
            color: white;
            border: none;
            cursor: pointer;
        }
        .btn-contacts:hover { background: #2980b9; }
        
        .btn-delete { 
            background: #e74c3c; 
            color: white;
            border: none;
            cursor: pointer;
        }
        .btn-delete:hover { background: #c0392b; }
        
        .btn-view { 
            background: #2ecc71; 
            color: white;
            border: none;
            cursor: pointer;
        }
        .btn-view:hover { background: #27ae60; }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #7f8c8d;
            background: white;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .stats-overview {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
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
        
        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid;
        }
        
        .alert-success {
            background: #e8f5e8;
            color: #388e3c;
            border-color: #388e3c;
        }
        
        .alert-error {
            background: #ffebee;
            color: #d32f2f;
            border-color: #d32f2f;
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
            </div>
        </div>
    </div>

    <div class="container">
        <div class="page-header">
            <h2>🏢 Управление компаниями</h2>
            <a href="add-company.php" class="btn btn-success">+ Добавить компанию</a>
        </div>

        <!-- Сообщения об успехе/ошибке -->
        <?php if ($success): ?>
            <div class="alert alert-success">
                ✅ <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-error">
                ❌ <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <!-- Статистика -->
        <div class="stats-overview">
            <?php
            $total_revenue = array_sum(array_column($companies, 'total_revenue'));
            $total_applications = array_sum(array_column($companies, 'applications_count'));
            $companies_with_applications = array_filter($companies, fn($c) => $c['applications_count'] > 0);
            ?>
            <div class="stat-card">
                <span class="stat-number"><?php echo count($companies); ?></span>
                <div class="stat-label">Всего компаний</div>
            </div>
            <div class="stat-card">
                <span class="stat-number"><?php echo count($companies_with_applications); ?></span>
                <div class="stat-label">Активных</div>
            </div>
            <div class="stat-card">
                <span class="stat-number"><?php echo $total_applications; ?></span>
                <div class="stat-label">Всего заявок</div>
            </div>
            <div class="stat-card">
                <span class="stat-number"><?php echo number_format($total_revenue, 0, '', ' '); ?> ₽</span>
                <div class="stat-label">Общий доход</div>
            </div>
        </div>

        <!-- Список компаний -->
        <div class="companies-grid">
            <?php if (!empty($companies)): ?>
                <?php foreach ($companies as $company): ?>
                    <div class="company-card">
                        <div class="company-header">
                            <div class="company-name"><?php echo htmlspecialchars($company['name']); ?></div>
                            <div class="company-contacts">
                                <?php if ($company['contact_person']): ?>
                                    <span>👤 <?php echo htmlspecialchars($company['contact_person']); ?></span>
                                <?php endif; ?>
                                <?php if ($company['phone']): ?>
                                    <span>📞 <?php echo htmlspecialchars($company['phone']); ?></span>
                                <?php endif; ?>
                                <?php if ($company['email']): ?>
                                    <span>📧 <?php echo htmlspecialchars($company['email']); ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="company-body">
                            <div class="company-info">
                                <div class="info-item">
                                    <span class="info-label">Заявок</span>
                                    <span class="info-value"><?php echo $company['applications_count']; ?></span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Общий доход</span>
                                    <span class="info-value revenue"><?php echo number_format($company['total_revenue'] ?? 0, 0, '', ' '); ?> ₽</span>
                                </div>
                            </div>
                            
                            <?php if ($company['address']): ?>
                                <div class="company-address">
                                    <strong>📍 Адрес:</strong><br>
                                    <?php echo htmlspecialchars($company['address']); ?>
                                </div>
                            <?php endif; ?>
                            
                            <div class="company-actions">
                                <a href="edit-company.php?id=<?php echo $company['id']; ?>" class="btn-sm btn-edit">✏️ Редакт.</a>
                                <a href="company-contacts.php?id=<?php echo $company['id']; ?>" class="btn-sm btn-contacts">📞 Контакты</a>
                                <a href="company-applications.php?company_id=<?php echo $company['id']; ?>" class="btn-sm btn-view">📋 Заявки</a>
                                <a href="delete-company.php?id=<?php echo $company['id']; ?>" class="btn-sm btn-delete" 
                                   onclick="return confirm('Вы уверены что хотите удалить компанию <?php echo htmlspecialchars($company['name']); ?>?')">
                                   🗑️ Удалить
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-state">
                    <h3>🏢 Компаний пока нет</h3>
                    <p>Добавьте первую компанию-клиента для начала работы</p>
                    <a href="add-company.php" class="btn btn-success" style="margin-top: 15px;">+ Добавить компанию</a>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Быстрые действия -->
        <div style="text-align: center; margin-top: 40px; padding-top: 30px; border-top: 1px solid #eee;">
            <h3 style="margin-bottom: 20px; color: #2c3e50;">⚡ Быстрые действия</h3>
            <div style="display: flex; gap: 15px; justify-content: center; flex-wrap: wrap;">
                <a href="add-company.php" class="btn btn-success">🏢 Добавить компанию</a>
                <a href="applications.php" class="btn">📝 Создать заявку</a>
                <a href="analytics.php" class="btn">📈 Статистика</a>
                <a href="export-companies.php" class="btn">📊 Экспорт данных</a>
            </div>
        </div>
    </div>

    <script>
        // Анимация появления карточек
        document.addEventListener('DOMContentLoaded', function() {
            const cards = document.querySelectorAll('.company-card');
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