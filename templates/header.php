<?php
// Правильные пути для подключения
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

// Проверяем авторизацию
if (!isset($_SESSION['user_id'])) {
    header("Location: /login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CRM.PROFTRANSFER - <?php echo $page_title ?? 'Панель управления'; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="/css/style.css" rel="stylesheet">
    <link rel="stylesheet" href="https://api-maps.yandex.ru/2.1/?apikey=09481e5b-e974-4f24-888d-7c9ee559fc6a&lang=ru_RU" type="text/css">
    <!-- Дополнительные стили для конкретных страниц -->
    <?php if (isset($additional_css)) echo $additional_css; ?>
</head>
<body class="d-flex flex-column vh-100">
    <header class="navbar navbar-dark bg-dark flex-md-nowrap p-0 shadow">
        <a class="navbar-brand col-md-3 col-lg-2 me-0 px-3 fs-6" href="/index.php">
            <i class="fas fa-tachometer-alt me-2"></i>CRM.PROFTRANSFER
        </a>
        <div class="navbar-nav">
            <div class="nav-item text-nowrap">
                <a class="nav-link px-3" href="/logout.php">
                    Выйти (<?php echo htmlspecialchars($_SESSION['full_name'] ?? $_SESSION['username']); ?>)
                </a>
            </div>
        </div>
    </header>
