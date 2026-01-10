<?php
require_once 'config.php';
require_once 'auth.php';
requireLogin(); // Требуем авторизацию

// Для административных страниц (companies.php, analytics.php) используйте:
// requireAdmin();
?>
<?php
/**
 * Назначение транспорта водителю
 */

session_start();
require_once 'config/database.php';

$driver_id = $_GET['driver_id'] ?? 0;

if (!$driver_id) {
    header('Location: drivers.php');
    exit;
}

try {
    $pdo = connectDatabase();
    
    // Получаем данные водителя
    $stmt = $pdo->prepare("SELECT * FROM drivers WHERE id = ?");
    $stmt->execute([$driver_id]);
    $driver = $stmt->fetch();
    
    if (!$driver) {
        header('Location: drivers.php?error=Водитель не найден');
        exit;
    }
    
    // Получаем доступный транспорт
    $vehicles = $pdo->query("
        SELECT v.* 
        FROM vehicles v 
        WHERE v.status = 'active' 
        AND v.id NOT IN (
            SELECT vehicle_id FROM driver_vehicles WHERE is_active = 1
        )
        ORDER BY v.model
    ")->fetchAll();
    
    // Получаем текущий закрепленный транспорт
    $current_vehicle = $pdo->prepare("
        SELECT v.* 
        FROM driver_vehicles dv 
        JOIN vehicles v ON dv.vehicle_id = v.id 
        WHERE dv.driver_id = ? AND dv.is_active = 1
    ");
    $current_vehicle->execute([$driver_id]);
    $current_vehicle = $current_vehicle->fetch();
    
} catch (Exception $e) {
    header('Location: drivers.php?error=' . urlencode($e->getMessage()));
    exit;
}

// Обработка назначения транспорта
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $vehicle_id = $_POST['vehicle_id'];
        
        if ($vehicle_id) {
            // Снимаем текущий транспорт
            $pdo->prepare("UPDATE driver_vehicles SET is_active = 0 WHERE driver_id = ?")->execute([$driver_id]);
            
            // Назначаем новый транспорт
            $sql = "INSERT INTO driver_vehicles (driver_id, vehicle_id, assigned_date, is_active) 
                    VALUES (?, ?, CURDATE(), 1) 
                    ON DUPLICATE KEY UPDATE is_active = 1, assigned_date = CURDATE()";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$driver_id, $vehicle_id]);
            
            header('Location: drivers.php?success=Транспорт успешно назначен водителю');
            exit;
        }
        
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
    <title>Назначить транспорт - CRM ProfTransfer</title>
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
            max-width: 800px;
            margin: 0 auto;
        }
        .logo { display: flex; align-items: center; gap: 12px; }
        .logo h1 { color: #2c3e50; font-size: 1.8rem; }
        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 0 20px;
        }
        .form-container {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .driver-info {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 25px;
            border-left: 4px solid #3498db;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
            color: #2c3e50;
        }
        .form-control {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
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
            margin-right: 10px;
        }
        .btn:hover { background: #2980b9; }
        .btn-success { background: #27ae60; }
        .btn-success:hover { background: #219a52; }
        .vehicle-card {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 10px;
            border: 2px solid transparent;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .vehicle-card:hover {
            border-color: #3498db;
            background: #e3f2fd;
        }
        .vehicle-card.selected {
            border-color: #27ae60;
            background: #e8f5e8;
        }
        .vehicle-info {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .vehicle-model {
            font-weight: 600;
            color: #2c3e50;
        }
        .vehicle-details {
            color: #7f8c8d;
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
                <a href="drivers.php" class="btn">← Назад к водителям</a>
            </div>
        </div>
    </div>

    <div class="container">
        <div class="form-container">
            <h2 style="margin-bottom: 25px; color: #2c3e50;">🚗 Назначить транспорт водителю</h2>
            
            <div class="driver-info">
                <h3 style="margin-bottom: 10px; color: #2c3e50;"><?php echo htmlspecialchars($driver['full_name']); ?></h3>
                <p style="color: #7f8c8d; margin: 0;">
                    📞 <?php echo htmlspecialchars($driver['phone']); ?> | 
                    📧 <?php echo htmlspecialchars($driver['email']); ?>
                </p>
                <?php if ($current_vehicle): ?>
                    <div style="margin-top: 10px; padding: 10px; background: #fff3e0; border-radius: 5px;">
                        <strong>Текущий транспорт:</strong> 
                        <?php echo htmlspecialchars($current_vehicle['model']); ?> 
                        (<?php echo htmlspecialchars($current_vehicle['license_plate']); ?>)
                    </div>
                <?php endif; ?>
            </div>
            
            <form method="POST" id="assignForm">
                <div class="form-group">
                    <label>Выберите транспорт для назначения:</label>
                    
                    <?php if (empty($vehicles)): ?>
                        <div style="padding: 20px; text-align: center; background: #ffebee; border-radius: 5px; color: #d32f2f;">
                            ❌ Нет доступного транспорта для назначения
                        </div>
                    <?php else: ?>
                        <div style="max-height: 300px; overflow-y: auto;">
                            <?php foreach ($vehicles as $vehicle): ?>
                                <div class="vehicle-card" onclick="selectVehicle(<?php echo $vehicle['id']; ?>)">
                                    <div class="vehicle-info">
                                        <div>
                                            <div class="vehicle-model"><?php echo htmlspecialchars($vehicle['model']); ?></div>
                                            <div class="vehicle-details">
                                                🚙 <?php echo htmlspecialchars($vehicle['license_plate']); ?> | 
                                                💺 <?php echo $vehicle['capacity'] ? $vehicle['capacity'] . ' мест' : 'Вместимость не указана'; ?> | 
                                                📅 <?php echo $vehicle['year'] ?? 'Год не указан'; ?>
                                            </div>
                                        </div>
                                        <input type="radio" name="vehicle_id" value="<?php echo $vehicle['id']; ?>" style="display: none;">
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div style="display: flex; gap: 10px; margin-top: 30px;">
                    <a href="drivers.php" class="btn">Отмена</a>
                    <?php if (!empty($vehicles)): ?>
                        <button type="submit" class="btn btn-success">Назначить транспорт</button>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <script>
        function selectVehicle(vehicleId) {
            // Снимаем выделение со всех карточек
            document.querySelectorAll('.vehicle-card').forEach(card => {
                card.classList.remove('selected');
            });
            
            // Выделяем выбранную карточку
            const selectedCard = document.querySelector(`.vehicle-card input[value="${vehicleId}"]`).closest('.vehicle-card');
            selectedCard.classList.add('selected');
            
            // Устанавливаем значение в радиокнопку
            document.querySelectorAll('input[name="vehicle_id"]').forEach(radio => {
                radio.checked = false;
            });
            document.querySelector(`input[value="${vehicleId}"]`).checked = true;
        }
        
        // Выбираем первый транспорт по умолчанию
        document.addEventListener('DOMContentLoaded', function() {
            const firstVehicle = document.querySelector('.vehicle-card');
            if (firstVehicle) {
                const vehicleId = firstVehicle.querySelector('input').value;
                selectVehicle(vehicleId);
            }
        });
    </script>
</body>
</html>