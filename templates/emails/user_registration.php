<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #4CAF50; color: white; padding: 20px; text-align: center; }
        .content { padding: 20px; background: #f9f9f9; }
        .button { display: inline-block; padding: 10px 20px; background: #4CAF50; color: white; text-decoration: none; border-radius: 5px; }
        .footer { padding: 20px; text-align: center; font-size: 12px; color: #666; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Добро пожаловать в CRM.PROFTRANSFER</h1>
        </div>
        <div class="content">
            <p>Здравствуйте, <?= htmlspecialchars($user['name'] ?? 'Пользователь') ?>!</p>
            
            <p>Ваш аккаунт успешно создан в системе CRM.PROFTRANSFER.</p>
            
            <p><strong>Данные для входа:</strong></p>
            <ul>
                <li>Email: <?= htmlspecialchars($user['email'] ?? '') ?></li>
                <li>Роль: <?= htmlspecialchars($user['role'] ?? '') ?></li>
            </ul>
            
            <p>Для входа в систему используйте ссылку:</p>
            <p style="text-align: center;">
                <a href="<?= getenv('APP_URL') ?>" class="button">Войти в систему</a>
            </p>
            
            <p>Если у вас есть вопросы, свяжитесь с нашей поддержкой.</p>
        </div>
        <div class="footer">
            <p>&copy; <?= date('Y') ?> CRM.PROFTRANSFER. Все права защищены.</p>
        </div>
    </div>
</body>
</html>
