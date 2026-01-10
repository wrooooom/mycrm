<?php
require_once 'config.php';
require_once 'auth.php';
requireLogin(); // Требуем авторизацию

// Для административных страниц (companies.php, analytics.php) используйте:
// requireAdmin();
?>
<?php
/**
 * Редактирование транспортного средства
 */

session_start();
require_once 'config/database.php';

$vehicle_id = $_GET['id'] ?? 0;

if (!$vehicle_id) {
    header('Location: vehicles.php');
    exit;
}

try {
    $pdo = connectDatabase();
    
    // Получаем данные транспорта
    $stmt = $pdo->prepare("SELECT * FROM vehicles WHERE id = ?");
    $stmt->execute([$vehicle_id]);
    $vehicle = $stmt->fetch();
    
    if (!$vehicle) {
        header('Location: vehicles.php?error=Транспорт не найден');
        exit;
    }
    
} catch (Exception $e) {
    header('Location: vehicles.php?error=' . urlencode($e->getMessage()));
    exit;
}

// Обработка формы
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $data = [
            'model' => $_POST['model'],
            'license_plate' => $_POST['license_plate'],
            'vehicle_type' => $_POST['vehicle_type'],
            'capacity' => $_POST['capacity'] ?: null,
            'year' => $_POST['year'] ?: null,
            'status' => $_POST['status'],
            'id' => $vehicle_id
        ];
        
        $sql = "UPDATE vehicles SET 
                model = ?, license_plate = ?, vehicle_type = ?, 
                capacity = ?, year = ?, status = ?
                WHERE id = ?";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute(array_values($data));
        
        header('Location: vehicles.php?success=Транспорт успешно обновлен');
        exit;
        
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
    <title>Редактировать транспорт - CRM ProfTransfer</title>
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
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
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
        .error { color: #e74c3c; margin-bottom: 15px; padding: 10px; background: #ffebee; border-radius: 5px; }
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
                <a href="vehicles.php" class="btn">← Назад к транспорту</a>
            </div>
        </div>
    </div>

    <div class="container">
        <div class="form-container">
            <h2 style="margin-bottom: 25px; color: #2c3e50;">✏️ Редактировать транспорт</h2>
            
            <?php if (isset($error)): ?>
                <div class="error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="form-group">
                    <label>Модель транспортного средства *</label>
                    <input type="text" name="model" class="form-control" value="<?php echo htmlspecialchars($vehicle['model']); ?>" required>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Госномер *</label>
                        <input type="text" name="license_plate" class="form-control" value="<?php echo htmlspecialchars($vehicle['license_plate']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Тип транспорта *</label>
                        <select name="vehicle_type" class="form-control" required>
                            <option value="car" <?php echo $vehicle['vehicle_type'] === 'car' ? 'selected' : ''; ?>>Легковой автомобиль</option>
                            <option value="minivan" <?php echo $vehicle['vehicle_type'] === 'minivan' ? 'selected' : ''; ?>>Минивэн</option>
                            <option value="bus" <?php echo $vehicle['vehicle_type'] === 'bus' ? 'selected' : ''; ?>>Автобус</option>
                            <option value="truck" <?php echo $vehicle['vehicle_type'] === 'truck' ? 'selected' : ''; ?>>Грузовой</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Вместимость (человек)</label>
                        <input type="number" name="capacity" class="form-control" min="1" max="50" value="<?php echo $vehicle['capacity']; ?>">
                    </div>
                    <div class="form-group">
                        <label>Год выпуска</label>
                        <input type="number" name="year" class="form-control" min="2000" max="2025" value="<?php echo $vehicle['year']; ?>">
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Статус</label>
                    <select name="status" class="form-control" required>
                        <option value="active" <?php echo $vehicle['status'] === 'active' ? 'selected' : ''; ?>>Активен</option>
                        <option value="maintenance" <?php echo $vehicle['status'] === 'maintenance' ? 'selected' : ''; ?>>На обслуживании</option>
                        <option value="inactive" <?php echo $vehicle['status'] === 'inactive' ? 'selected' : ''; ?>>Неактивен</option>
                    </select>
                </div>
                
                <div style="display: flex; gap: 10px; margin-top: 30px;">
                    <a href="vehicles.php" class="btn">Отмена</a>
                    <button type="submit" class="btn btn-success">Сохранить изменения</button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>