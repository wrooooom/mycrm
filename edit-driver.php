<?php
require_once 'config.php';
require_once 'auth.php';
requireLogin(); // Требуем авторизацию

// Для административных страниц (companies.php, analytics.php) используйте:
// requireAdmin();
?>
<?php
/**
 * Редактирование водителя
 */

session_start();
require_once 'config/database.php';

$driver_id = $_GET['id'] ?? 0;

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
    
} catch (Exception $e) {
    header('Location: drivers.php?error=' . urlencode($e->getMessage()));
    exit;
}

// Обработка формы
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $data = [
            'full_name' => $_POST['full_name'],
            'phone' => $_POST['phone'],
            'email' => $_POST['email'],
            'license_number' => $_POST['license_number'],
            'license_expiry' => $_POST['license_expiry'],
            'status' => $_POST['status'],
            'id' => $driver_id
        ];
        
        $sql = "UPDATE drivers SET 
                full_name = ?, phone = ?, email = ?, 
                license_number = ?, license_expiry = ?, status = ?
                WHERE id = ?";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute(array_values($data));
        
        header('Location: drivers.php?success=Водитель успешно обновлен');
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
    <title>Редактировать водителя - CRM ProfTransfer</title>
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
                <a href="drivers.php" class="btn">← Назад к водителям</a>
            </div>
        </div>
    </div>

    <div class="container">
        <div class="form-container">
            <h2 style="margin-bottom: 25px; color: #2c3e50;">✏️ Редактировать водителя</h2>
            
            <?php if (isset($error)): ?>
                <div class="error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="form-group">
                    <label>ФИО водителя *</label>
                    <input type="text" name="full_name" class="form-control" value="<?php echo htmlspecialchars($driver['full_name']); ?>" required>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Телефон *</label>
                        <input type="tel" name="phone" class="form-control" value="<?php echo htmlspecialchars($driver['phone']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($driver['email']); ?>">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Номер прав *</label>
                        <input type="text" name="license_number" class="form-control" value="<?php echo htmlspecialchars($driver['license_number']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Действительны до</label>
                        <input type="date" name="license_expiry" class="form-control" value="<?php echo $driver['license_expiry']; ?>">
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Статус</label>
                    <select name="status" class="form-control" required>
                        <option value="active" <?php echo $driver['status'] === 'active' ? 'selected' : ''; ?>>Активен</option>
                        <option value="inactive" <?php echo $driver['status'] === 'inactive' ? 'selected' : ''; ?>>Неактивен</option>
                        <option value="on_leave" <?php echo $driver['status'] === 'on_leave' ? 'selected' : ''; ?>>В отпуске</option>
                    </select>
                </div>
                
                <div style="display: flex; gap: 10px; margin-top: 30px;">
                    <a href="drivers.php" class="btn">Отмена</a>
                    <button type="submit" class="btn btn-success">Сохранить изменения</button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>