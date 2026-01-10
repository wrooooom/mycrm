<?php
/**
 * Sidebar навигация
 */
// Стартуем сессию для проверки авторизации
session_start();
$isLoggedIn = isset($_SESSION['user_id']);

// Если не авторизован, редирект на логин
if (!$isLoggedIn) {
    header('Location: login.php');
    exit;
}
?>

<div class="sidebar">
    <nav class="sidebar-nav">
        <ul>
            <li class="nav-item">
                <a href="index.php?page=dashboard" class="nav-link">
                    📊 Дашборд
                </a>
            </li>
            <li class="nav-item">
                <a href="index.php?page=applications" class="nav-link">
                    📝 Заявки
                </a>
            </li>
            <li class="nav-item">
                <a href="index.php?page=drivers" class="nav-link">
                    👨‍💼 Водители
                </a>
            </li>
            <li class="nav-item">
                <a href="index.php?page=vehicles" class="nav-link">
                    🚗 Транспорт
                </a>
            </li>
            <li class="nav-item">
                <a href="index.php?page=companies" class="nav-link">
                    🏢 Компании
                </a>
            </li>
            <li class="nav-item">
                <a href="index.php?page=analytics" class="nav-link">
                    📈 Аналитика
                </a>
            </li>
        </ul>
    </nav>
</div>

<div class="main-content">
    <?php
    // Загружаем контент страницы
    $page = $_GET['page'] ?? 'dashboard';
    $allowed_pages = ['dashboard', 'applications', 'drivers', 'vehicles', 'companies', 'analytics'];
    
    if (in_array($page, $allowed_pages)) {
        include "controllers/{$page}.php";
    } else {
        include "controllers/dashboard.php";
    }
    ?>
</div>

<style>
.sidebar {
    width: 250px;
    background: #2c3e50;
    color: white;
    min-height: 100vh;
}

.sidebar-nav ul {
    list-style: none;
    padding: 0;
    margin: 0;
}

.nav-item {
    border-bottom: 1px solid #34495e;
}

.nav-link {
    display: block;
    padding: 15px 20px;
    color: #ecf0f1;
    text-decoration: none;
    transition: background 0.3s;
}

.nav-link:hover {
    background: #34495e;
}

.main-content {
    flex: 1;
    padding: 20px;
    background: white;
}
</style>