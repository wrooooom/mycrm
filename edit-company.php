<?php
require_once 'config.php';
require_once 'auth.php';
requireLogin(); // Требуем авторизацию

// Для административных страниц (companies.php, analytics.php) используйте:
// requireAdmin();
?>
<?php
/**
 * Редактирование компании
 */

session_start();
require_once 'config/database.php';

$company_id = $_GET['id'] ?? 0;

if (!$company_id) {
    header('Location: companies.php');
    exit;
}

try {
    $pdo = connectDatabase();
    
    // Получаем данные компании
    $stmt = $pdo->prepare("SELECT * FROM companies WHERE id = ?");
    $stmt->execute([$company_id]);
    $company = $stmt->fetch();
    
    if (!$company) {
        header('Location: companies.php?error=Компания не найдена');
        exit;
    }
    
} catch (Exception $e) {
    header('Location: companies.php?error=' . urlencode($e->getMessage()));
    exit;
}

// Обработка формы
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $data = [
            'name' => $_POST['name'],
            'contact_person' => $_POST['contact_person'],
            'phone' => $_POST['phone'],
            'email' => $_POST['email'],
            'address' => $_POST['address'],
            'id' => $company_id
        ];
        
        $sql = "UPDATE companies SET 
                name = ?, contact_person = ?, phone = ?, 
                email = ?, address = ?
                WHERE id = ?";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute(array_values($data));
        
        header('Location: companies.php?success=Компания успешно обновлена');
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
    <title>Редактировать компанию - CRM ProfTransfer</title>
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
                <a href="companies.php" class="btn">← Назад к компаниям</a>
            </div>
        </div>
    </div>

    <div class="container">
        <div class="form-container">
            <h2 style="margin-bottom: 25px; color: #2c3e50;">✏️ Редактировать компанию</h2>
            
            <?php if (isset($error)): ?>
                <div class="error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="form-group">
                    <label>Название компании *</label>
                    <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($company['name']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label>Контактное лицо *</label>
                    <input type="text" name="contact_person" class="form-control" value="<?php echo htmlspecialchars($company['contact_person']); ?>" required>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Телефон *</label>
                        <input type="tel" name="phone" class="form-control" value="<?php echo htmlspecialchars($company['phone']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($company['email']); ?>">
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Адрес</label>
                    <textarea name="address" class="form-control" rows="3"><?php echo htmlspecialchars($company['address']); ?></textarea>
                </div>
                
                <div style="display: flex; gap: 10px; margin-top: 30px;">
                    <a href="companies.php" class="btn">Отмена</a>
                    <button type="submit" class="btn btn-success">Сохранить изменения</button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>