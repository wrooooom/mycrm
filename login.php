<?php
require_once 'config.php';
require_once 'auth.php';

// Если пользователь уже авторизован, перенаправляем на главную
if (isLoggedIn()) {
    header("Location: index.php");
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    
    if (!empty($username) && !empty($password)) {
        try {
            // Ищем пользователя в БД - проверяем оба поля: password и password_hash
            $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? AND is_active = 1");
            $stmt->execute([$username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user) {
                // Пробуем проверить пароль в поле password (новое)
                $passwordValid = false;
                $passwordField = '';
                
                if (!empty($user['password']) && password_verify($password, $user['password'])) {
                    $passwordValid = true;
                    $passwordField = 'password';
                } 
                // Если в password не подошло, пробуем password_hash (старое поле)
                elseif (!empty($user['password_hash']) && password_verify($password, $user['password_hash'])) {
                    $passwordValid = true;
                    $passwordField = 'password_hash';
                }
                
                if ($passwordValid) {
                    // Успешная авторизация
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['full_name'] = $user['full_name'];
                    $_SESSION['user_role'] = $user['role'];
                    $_SESSION['email'] = $user['email'];
                    $_SESSION['last_login'] = $user['last_login'];
                    
                    // Обновляем время последнего входа
                    $updateStmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
                    $updateStmt->execute([$user['id']]);
                    
                    // Если использовали старый password_hash, обновляем на новое поле
                    if ($passwordField === 'password_hash') {
                        $newHash = password_hash($password, PASSWORD_DEFAULT);
                        $updatePassStmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                        $updatePassStmt->execute([$newHash, $user['id']]);
                    }
                    
                    header("Location: index.php");
                    exit();
                } else {
                    $error = 'Неверный пароль';
                }
            } else {
                $error = 'Пользователь не найден или заблокирован';
            }
        } catch(PDOException $e) {
            $error = 'Ошибка при авторизации: ' . $e->getMessage();
        }
    } else {
        $error = 'Пожалуйста, заполните все поля';
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Вход в систему - Транспортная CRM</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .login-container {
            background: rgba(255, 255, 255, 0.95);
            padding: 3rem;
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 450px;
            backdrop-filter: blur(10px);
        }

        .logo {
            text-align: center;
            margin-bottom: 2rem;
        }

        .logo h1 {
            color: #4a5568;
            font-size: 1.8rem;
            margin-bottom: 0.5rem;
        }

        .logo p {
            color: #718096;
            font-size: 1rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: #4a5568;
            font-weight: 600;
        }

        .form-group input {
            width: 100%;
            padding: 1rem;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .form-group input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .btn {
            width: 100%;
            padding: 1rem;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn:hover {
            background: #5a6fd8;
            transform: translateY(-2px);
        }

        .error {
            background: #fed7d7;
            color: #c53030;
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 1.5rem;
            border-left: 4px solid #c53030;
        }

        .success {
            background: #c6f6d5;
            color: #2d7d32;
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 1.5rem;
            border-left: 4px solid #2d7d32;
        }

        .test-data {
            background: #edf2f7;
            padding: 1rem;
            border-radius: 10px;
            margin-top: 1.5rem;
            font-size: 0.9rem;
            color: #4a5568;
        }

        .test-data strong {
            color: #667eea;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="logo">
            <h1><i class="fas fa-tachometer-alt"></i> Транспортная CRM</h1>
            <p>Система управления перевозками</p>
        </div>
        
        <?php if (!empty($error)): ?>
            <div class="error">
                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_GET['success']) && $_GET['success'] == '1'): ?>
            <div class="success">
                <i class="fas fa-check-circle"></i> База данных успешно восстановлена! Теперь вы можете войти в систему.
            </div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <label for="username"><i class="fas fa-user"></i> Имя пользователя:</label>
                <input type="text" id="username" name="username" required placeholder="Введите ваш логин" value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : 'admin'; ?>">
            </div>
            
            <div class="form-group">
                <label for="password"><i class="fas fa-lock"></i> Пароль:</label>
                <input type="password" id="password" name="password" required placeholder="Введите ваш пароль" value="admin123">
            </div>
            
            <button type="submit" class="btn">
                <i class="fas fa-sign-in-alt"></i> Войти в систему
            </button>
        </form>
        
        <div class="test-data">
            <strong><i class="fas fa-info-circle"></i> Данные для входа:</strong><br>
            Логин: <strong>admin</strong><br>
            Пароль: <strong>admin123</strong>
        </div>
    </div>
</body>
</html>