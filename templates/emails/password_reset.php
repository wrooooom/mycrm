<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #FF9800; color: white; padding: 20px; text-align: center; }
        .content { padding: 20px; background: #f9f9f9; }
        .button { display: inline-block; padding: 10px 20px; background: #FF9800; color: white; text-decoration: none; border-radius: 5px; }
        .warning { background: #fff3cd; border-left: 4px solid #ffc107; padding: 10px; margin: 10px 0; }
        .footer { padding: 20px; text-align: center; font-size: 12px; color: #666; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Восстановление пароля</h1>
        </div>
        <div class="content">
            <p>Здравствуйте, <?= htmlspecialchars($user['name'] ?? 'Пользователь') ?>!</p>
            
            <p>Мы получили запрос на восстановление пароля для вашего аккаунта.</p>
            
            <p style="text-align: center;">
                <a href="<?= htmlspecialchars($resetLink ?? '#') ?>" class="button">Сбросить пароль</a>
            </p>
            
            <div class="warning">
                <p><strong>Важно:</strong> Эта ссылка действительна в течение 1 часа.</p>
            </div>
            
            <p>Если вы не запрашивали восстановление пароля, просто проигнорируйте это письмо.</p>
        </div>
        <div class="footer">
            <p>&copy; <?= date('Y') ?> CRM.PROFTRANSFER. Все права защищены.</p>
        </div>
    </div>
</body>
</html>
