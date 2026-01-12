<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #2196F3; color: white; padding: 20px; text-align: center; }
        .content { padding: 20px; background: #f9f9f9; }
        .info-box { background: white; padding: 15px; margin: 10px 0; border-left: 4px solid #2196F3; }
        .button { display: inline-block; padding: 10px 20px; background: #2196F3; color: white; text-decoration: none; border-radius: 5px; }
        .footer { padding: 20px; text-align: center; font-size: 12px; color: #666; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Новая заявка назначена</h1>
        </div>
        <div class="content">
            <p>Здравствуйте, <?= htmlspecialchars($driver['first_name'] ?? '') ?> <?= htmlspecialchars($driver['last_name'] ?? '') ?>!</p>
            
            <p>Вам назначена новая заявка:</p>
            
            <div class="info-box">
                <p><strong>Номер заявки:</strong> <?= htmlspecialchars($application['application_number'] ?? '') ?></p>
                <p><strong>Клиент:</strong> <?= htmlspecialchars($application['customer_name'] ?? '') ?></p>
                <p><strong>Телефон:</strong> <?= htmlspecialchars($application['customer_phone'] ?? '') ?></p>
                <p><strong>Дата поездки:</strong> <?= htmlspecialchars($application['trip_date'] ?? '') ?></p>
                <p><strong>Маршрут:</strong> <?= htmlspecialchars($application['route_from'] ?? '') ?> → <?= htmlspecialchars($application['route_to'] ?? '') ?></p>
            </div>
            
            <p style="text-align: center;">
                <a href="<?= getenv('APP_URL') ?>/applications.php?id=<?= $application['id'] ?? '' ?>" class="button">Посмотреть детали</a>
            </p>
            
            <p>Пожалуйста, свяжитесь с клиентом и подтвердите детали поездки.</p>
        </div>
        <div class="footer">
            <p>&copy; <?= date('Y') ?> CRM.PROFTRANSFER. Все права защищены.</p>
        </div>
    </div>
</body>
</html>
