<?php
require_once 'config.php';
require_once 'auth.php';
requireLogin(); // Требуем авторизацию

// Для административных страниц (companies.php, analytics.php) используйте:
// requireAdmin();
?>
<?php
/**
 * Страница управления транспортными средствами
 * Обновленная версия с функциями редактирования и удаления
 */

session_start();
require_once 'config/database.php';

// Обработка сообщений об успехе/ошибке
$success = $_GET['success'] ?? '';
$error = $_GET['error'] ?? '';

try {
    $pdo = connectDatabase();
    
    // Получаем весь транспорт
    $vehicles = $pdo->query("
        SELECT v.*, 
               COUNT(a.id) as applications_count,
               GROUP_CONCAT(DISTINCT d.full_name SEPARATOR ', ') as assigned_drivers,
               dv.assigned_date
        FROM vehicles v
        LEFT JOIN applications a ON v.id = a.vehicle_id
        LEFT JOIN driver_vehicles dv ON v.id = dv.vehicle_id AND dv.is_active = 1
        LEFT JOIN drivers d ON dv.driver_id = d.id
        GROUP BY v.id
        ORDER BY v.created_at DESC
    ")->fetchAll();
    
} catch (Exception $e) {
    $error = $e->getMessage();
    $vehicles = [];
}

?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Управление транспортом - CRM ProfTransfer</title>
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
        
        .vehicles-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 25px;
            margin-bottom: 40px;
        }
        
        .vehicle-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            overflow: hidden;
            transition: transform 0.3s ease;
        }
        
        .vehicle-card:hover {
            transform: translateY(-5px);
        }
        
        .vehicle-header {
            background: linear-gradient(135deg, #e74c3c, #c0392b);
            color: white;
            padding: 20px;
        }
        
        .vehicle-model {
            font-size: 1.4rem;
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        .vehicle-plate {
            font-size: 1.1rem;
            opacity: 0.9;
            background: rgba(255,255,255,0.2);
            padding: 5px 10px;
            border-radius: 4px;
            display: inline-block;
        }
        
        .vehicle-body {
            padding: 20px;
        }
        
        .vehicle-info {
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
        
        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
            text-align: center;
            display: inline-block;
        }
        
        .status-active { background: #e8f5e8; color: #388e3c; }
        .status-maintenance { background: #fff3e0; color: #f57c00; }
        .status-inactive { background: #ffebee; color: #d32f2f; }
        
        .type-badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 10px;
            font-weight: 500;
            text-transform: uppercase;
        }
        
        .type-car { background: #e3f2fd; color: #1976d2; }
        .type-minivan { background: #f3e5f5; color: #7b1fa2; }
        .type-bus { background: #e8f5e8; color: #388e3c; }
        .type-truck { background: #fff3e0; color: #f57c00; }
        
        .vehicle-actions {
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
        
        .btn-assign { 
            background: #9b59b6; 
            color: white;
            border: none;
            cursor: pointer;
        }
        .btn-assign:hover { background: #8e44ad; }
        
        .btn-delete { 
            background: #e74c3c; 
            color: white;
            border: none;
            cursor: pointer;
        }
        .btn-delete:hover { background: #c0392b; }
        
        .btn-view { 
            background: #3498db; 
            color: white;
            border: none;
            cursor: pointer;
        }
        .btn-view:hover { background: #2980b9; }
        
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
        
        .driver-assignment {
            background: #f8f9fa;
            padding: 12px;
            border-radius: 6px;
            margin-top: 10px;
            font-size: 0.85rem;
        }
        
        .assignment-date {
            color: #7f8c8d;
            font-size: 0.8rem;
            margin-top: 5px;
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
            </div>
        </div>
    </div>

    <div class="container">
        <div class="page-header">
            <h2>🚗 Управление транспортом</h2>
            <a href="add-vehicle.php" class="btn btn-success">+ Добавить транспорт</a>
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
            $active_vehicles = array_filter($vehicles, fn($v) => $v['status'] === 'active');
            $maintenance_vehicles = array_filter($vehicles, fn($v) => $v['status'] === 'maintenance');
            $inactive_vehicles = array_filter($vehicles, fn($v) => $v['status'] === 'inactive');
            $total_applications = array_sum(array_column($vehicles, 'applications_count'));
            ?>
            <div class="stat-card">
                <span class="stat-number"><?php echo count($vehicles); ?></span>
                <div class="stat-label">Всего единиц</div>
            </div>
            <div class="stat-card">
                <span class="stat-number"><?php echo count($active_vehicles); ?></span>
                <div class="stat-label">Активных</div>
            </div>
            <div class="stat-card">
                <span class="stat-number"><?php echo count($maintenance_vehicles); ?></span>
                <div class="stat-label">На обслуживании</div>
            </div>
            <div class="stat-card">
                <span class="stat-number"><?php echo $total_applications; ?></span>
                <div class="stat-label">Выполнено заявок</div>
            </div>
        </div>

        <!-- Список транспорта -->
        <div class="vehicles-grid">
            <?php if (!empty($vehicles)): ?>
                <?php foreach ($vehicles as $vehicle): ?>
                    <div class="vehicle-card">
                        <div class="vehicle-header">
                            <div class="vehicle-model"><?php echo htmlspecialchars($vehicle['model']); ?></div>
                            <div class="vehicle-plate"><?php echo htmlspecialchars($vehicle['license_plate']); ?></div>
                        </div>
                        
                        <div class="vehicle-body">
                            <div class="vehicle-info">
                                <div class="info-item">
                                    <span class="info-label">Тип</span>
                                    <span class="type-badge type-<?php echo $vehicle['vehicle_type']; ?>">
                                        <?php 
                                        $typeLabels = [
                                            'car' => 'Легковой',
                                            'minivan' => 'Минивэн',
                                            'bus' => 'Автобус',
                                            'truck' => 'Грузовой'
                                        ];
                                        echo $typeLabels[$vehicle['vehicle_type']] ?? $vehicle['vehicle_type'];
                                        ?>
                                    </span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Статус</span>
                                    <span class="status-badge status-<?php echo $vehicle['status']; ?>">
                                        <?php 
                                        $statusLabels = [
                                            'active' => 'Активен',
                                            'maintenance' => 'Обслуживание',
                                            'inactive' => 'Неактивен'
                                        ];
                                        echo $statusLabels[$vehicle['status']] ?? $vehicle['status'];
                                        ?>
                                    </span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Вместимость</span>
                                    <span class="info-value"><?php echo $vehicle['capacity'] ? $vehicle['capacity'] . ' чел.' : 'Не указана'; ?></span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Год</span>
                                    <span class="info-value"><?php echo $vehicle['year'] ?? 'Не указан'; ?></span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Заявок</span>
                                    <span class="info-value"><?php echo $vehicle['applications_count']; ?></span>
                                </div>
                            </div>
                            
                            <?php if ($vehicle['assigned_drivers']): ?>
                                <div class="driver-assignment">
                                    <strong>👨‍💼 Закрепленные водители:</strong><br>
                                    <?php echo htmlspecialchars($vehicle['assigned_drivers']); ?>
                                    <?php if ($vehicle['assigned_date']): ?>
                                        <div class="assignment-date">
                                            Назначен: <?php echo date('d.m.Y', strtotime($vehicle['assigned_date'])); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php else: ?>
                                <div style="background: #fff3e0; padding: 10px; border-radius: 6px; margin-top: 10px; font-size: 0.85rem;">
                                    ⚠️ Водитель не назначен
                                </div>
                            <?php endif; ?>
                            
                            <div class="vehicle-actions">
                                <a href="edit-vehicle.php?id=<?php echo $vehicle['id']; ?>" class="btn-sm btn-edit">✏️ Редакт.</a>
                                <a href="assign-driver.php?vehicle_id=<?php echo $vehicle['id']; ?>" class="btn-sm btn-assign">👨‍💼 Водитель</a>
                                <a href="vehicle-applications.php?vehicle_id=<?php echo $vehicle['id']; ?>" class="btn-sm btn-view">📋 Заявки</a>
                                <a href="delete-vehicle.php?id=<?php echo $vehicle['id']; ?>" class="btn-sm btn-delete" 
                                   onclick="return confirm('Вы уверены что хотите удалить транспорт <?php echo htmlspecialchars($vehicle['model']); ?>?')">
                                   🗑️ Удалить
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-state">
                    <h3>🚗 Транспорта пока нет</h3>
                    <p>Добавьте первую единицу транспорта для начала работы</p>
                    <a href="add-vehicle.php" class="btn btn-success" style="margin-top: 15px;">+ Добавить транспорт</a>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Быстрые действия -->
        <div style="text-align: center; margin-top: 40px; padding-top: 30px; border-top: 1px solid #eee;">
            <h3 style="margin-bottom: 20px; color: #2c3e50;">⚡ Быстрые действия</h3>
            <div style="display: flex; gap: 15px; justify-content: center; flex-wrap: wrap;">
                <a href="add-vehicle.php" class="btn btn-success">🚗 Добавить транспорт</a>
                <a href="drivers.php" class="btn">👨‍💼 Управление водителями</a>
                <a href="applications.php" class="btn">📝 Создать заявку</a>
                <a href="analytics.php" class="btn">📈 Статистика</a>
            </div>
        </div>
    </div>

    <script>
        // Анимация появления карточек
        document.addEventListener('DOMContentLoaded', function() {
            const cards = document.querySelectorAll('.vehicle-card');
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