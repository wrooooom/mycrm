<?php
require_once 'config.php';
require_once 'auth.php';
requireLogin();

// Получаем последние действия для правой колонки
try {
    $activity_log = $pdo->query("
        SELECT al.*, u.username, u.full_name 
        FROM activity_log al 
        LEFT JOIN users u ON al.user_id = u.id 
        ORDER BY al.created_at DESC 
        LIMIT 50
    ")->fetchAll(PDO::FETCH_ASSOC);
} catch(Exception $e) {
    $activity_log = [];
}

// Получаем статистику для dashboard
try {
    $stats = [
        'total_applications' => $pdo->query("SELECT COUNT(*) FROM applications")->fetchColumn(),
        'new_applications' => $pdo->query("SELECT COUNT(*) FROM applications WHERE status = 'new'")->fetchColumn(),
        'total_drivers' => $pdo->query("SELECT COUNT(*) FROM drivers WHERE status = 'work'")->fetchColumn(),
        'active_drivers' => $pdo->query("SELECT COUNT(*) FROM drivers WHERE status = 'work'")->fetchColumn(),
        'total_vehicles' => $pdo->query("SELECT COUNT(*) FROM vehicles WHERE status = 'working'")->fetchColumn(),
        'available_vehicles' => $pdo->query("SELECT COUNT(*) FROM vehicles WHERE status = 'working'")->fetchColumn(),
    ];
} catch(Exception $e) {
    $stats = [
        'total_applications' => 0,
        'new_applications' => 0,
        'total_drivers' => 0,
        'active_drivers' => 0,
        'total_vehicles' => 0,
        'available_vehicles' => 0,
    ];
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Главная - CRM.PROFTRANSFER</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f8f9fa;
            color: #333;
            font-size: 13px;
            line-height: 1.4;
            display: flex;
            height: 100vh;
            overflow: hidden;
        }

        /* Левая колонка - 15% */
        .sidebar {
            width: 15%;
            background: #2c3e50;
            color: white;
            display: flex;
            flex-direction: column;
            min-width: 250px;
        }

        .logo-section {
            padding: 20px;
            border-bottom: 1px solid #34495e;
            text-align: center;
        }

        .logo {
            font-size: 18px;
            font-weight: 700;
            color: #3498db;
        }

        .logo i {
            margin-right: 8px;
        }

        .calendar-section {
            padding: 15px;
            border-bottom: 1px solid #34495e;
        }

        .calendar-widget {
            background: #34495e;
            border-radius: 8px;
            padding: 12px;
        }

        .calendar-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }

        .calendar-nav {
            background: none;
            border: none;
            color: #bdc3c7;
            cursor: pointer;
            padding: 5px;
        }

        .calendar-title {
            font-weight: 600;
            color: #ecf0f1;
            font-size: 12px;
        }

        .calendar-grid {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 3px;
            margin-bottom: 8px;
        }

        .calendar-weekday {
            text-align: center;
            font-size: 10px;
            color: #bdc3c7;
            font-weight: 600;
            padding: 3px 0;
        }

        .calendar-day {
            text-align: center;
            padding: 4px 0;
            font-size: 11px;
            cursor: pointer;
            border-radius: 3px;
            transition: all 0.2s ease;
        }

        .calendar-day:hover {
            background: #3498db;
        }

        .calendar-day.today {
            background: #e74c3c;
            color: white;
        }

        .calendar-day.has-events::after {
            content: '';
            display: block;
            width: 3px;
            height: 3px;
            background: #2ecc71;
            border-radius: 50%;
            margin: 1px auto 0;
        }

        .calendar-day.other-month {
            color: #7f8c8d;
        }

        .navigation-section {
            flex: 1;
            padding: 15px 0;
            overflow-y: auto;
        }

        .nav-item {
            display: flex;
            align-items: center;
            padding: 12px 20px;
            color: #bdc3c7;
            text-decoration: none;
            transition: all 0.2s ease;
            border-left: 3px solid transparent;
        }

        .nav-item:hover {
            background: #34495e;
            color: #ecf0f1;
            border-left-color: #3498db;
        }

        .nav-item.active {
            background: #34495e;
            color: #3498db;
            border-left-color: #3498db;
        }

        .nav-item i {
            width: 20px;
            margin-right: 10px;
            font-size: 14px;
        }

        .user-section {
            padding: 15px 20px;
            border-top: 1px solid #34495e;
            background: #34495e;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .user-avatar {
            width: 32px;
            height: 32px;
            background: #3498db;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
        }

        .user-details {
            flex: 1;
        }

        .user-name {
            font-weight: 600;
            color: #ecf0f1;
            font-size: 12px;
        }

        .user-role {
            color: #bdc3c7;
            font-size: 10px;
        }

        .logout-btn {
            background: none;
            border: none;
            color: #bdc3c7;
            cursor: pointer;
            padding: 5px;
        }

        /* Центральная колонка - 70% */
        .main-content {
            width: 70%;
            background: #f8f9fa;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        .content-header {
            background: white;
            padding: 15px 25px;
            border-bottom: 1px solid #e0e0e0;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        .page-title {
            color: #2c3e50;
            font-size: 20px;
            font-weight: 600;
        }

        .page-subtitle {
            color: #7f8c8d;
            font-size: 12px;
            margin-top: 5px;
        }

        .content-body {
            flex: 1;
            padding: 20px;
            overflow-y: auto;
        }

        /* Правая колонка - 15% */
        .activity-sidebar {
            width: 15%;
            background: white;
            border-left: 1px solid #e0e0e0;
            display: flex;
            flex-direction: column;
            min-width: 280px;
        }

        .activity-header {
            padding: 15px 20px;
            border-bottom: 1px solid #e0e0e0;
            background: #f8f9fa;
        }

        .activity-title {
            color: #2c3e50;
            font-size: 14px;
            font-weight: 600;
        }

        .activity-list {
            flex: 1;
            overflow-y: auto;
            padding: 10px;
        }

        .activity-item {
            padding: 10px;
            margin-bottom: 8px;
            background: #f8f9fa;
            border-radius: 6px;
            border-left: 3px solid #3498db;
        }

        .activity-time {
            font-size: 10px;
            color: #7f8c8d;
            margin-bottom: 3px;
        }

        .activity-user {
            font-weight: 600;
            color: #2c3e50;
            font-size: 11px;
            margin-bottom: 2px;
        }

        .activity-action {
            color: #34495e;
            font-size: 11px;
            line-height: 1.3;
        }

        /* Стили для контента */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }

        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            border-left: 4px solid #3498db;
        }

        .stat-card.success {
            border-left-color: #2ecc71;
        }

        .stat-card.warning {
            border-left-color: #f39c12;
        }

        .stat-card.danger {
            border-left-color: #e74c3c;
        }

        .stat-number {
            font-size: 24px;
            font-weight: 700;
            color: #2c3e50;
            margin: 5px 0;
        }

        .stat-card.success .stat-number {
            color: #2ecc71;
        }

        .stat-card.warning .stat-number {
            color: #f39c12;
        }

        .stat-card.danger .stat-number {
            color: #e74c3c;
        }

        .stat-description {
            color: #7f8c8d;
            font-size: 12px;
        }

        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 10px;
            margin-bottom: 20px;
        }

        .action-btn {
            background: white;
            border: 1px solid #e0e0e0;
            padding: 15px;
            border-radius: 6px;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s ease;
            text-decoration: none;
            color: #2c3e50;
        }

        .action-btn:hover {
            border-color: #3498db;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }

        .action-btn i {
            font-size: 24px;
            color: #3498db;
            margin-bottom: 8px;
        }

        .action-title {
            font-weight: 600;
            font-size: 12px;
        }

        .recent-section {
            background: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .section-title {
            color: #2c3e50;
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #e0e0e0;
        }

        /* Адаптивность */
        @media (max-width: 1200px) {
            .sidebar {
                width: 200px;
            }
            .activity-sidebar {
                width: 250px;
            }
            .main-content {
                width: calc(100% - 450px);
            }
        }
    </style>
</head>
<body>
    <!-- Левая колонка - 15% -->
    <div class="sidebar">
        <!-- Логотип -->
        <div class="logo-section">
            <div class="logo">
                <i class="fas fa-tachometer-alt"></i>CRM.PROFTRANSFER
            </div>
        </div>

        <!-- Календарь -->
        <div class="calendar-section">
            <div class="calendar-widget">
                <div class="calendar-header">
                    <button class="calendar-nav" onclick="changeMonth(-1)"><i class="fas fa-chevron-left"></i></button>
                    <div class="calendar-title" id="calendarTitle"></div>
                    <button class="calendar-nav" onclick="changeMonth(1)"><i class="fas fa-chevron-right"></i></button>
                </div>
                <div class="calendar-grid" id="calendarDays">
                    <!-- Заполнится JavaScript -->
                </div>
            </div>
        </div>

        <!-- Навигация -->
        <div class="navigation-section">
            <a href="index.php" class="nav-item active">
                <i class="fas fa-home"></i>Рабочий стол
            </a>
            <a href="applications.php" class="nav-item">
                <i class="fas fa-file-alt"></i>Заявки
            </a>
            <a href="drivers.php" class="nav-item">
                <i class="fas fa-users"></i>Водители
            </a>
            <a href="vehicles.php" class="nav-item">
                <i class="fas fa-truck"></i>Автомобили
            </a>
            <a href="billing.php" class="nav-item">
                <i class="fas fa-money-bill"></i>Счёт
            </a>
            <a href="users.php" class="nav-item">
                <i class="fas fa-user-cog"></i>Пользователи
            </a>
            <a href="analytics.php" class="nav-item">
                <i class="fas fa-chart-bar"></i>Аналитика
            </a>
            <a href="tracking.php" class="nav-item">
                <i class="fas fa-map-marker-alt"></i>Трекинг
            </a>
        </div>

        <!-- Пользователь -->
        <div class="user-section">
            <div class="user-info">
                <div class="user-avatar">
                    <?php 
                    $initials = '';
                    if (isset($_SESSION['full_name'])) {
                        $names = explode(' ', $_SESSION['full_name']);
                        $initials = substr($names[0], 0, 1) . (isset($names[1]) ? substr($names[1], 0, 1) : '');
                    } else {
                        $initials = substr($_SESSION['username'], 0, 2);
                    }
                    echo strtoupper($initials);
                    ?>
                </div>
                <div class="user-details">
                    <div class="user-name"><?php echo htmlspecialchars($_SESSION['full_name'] ?? $_SESSION['username']); ?></div>
                    <div class="user-role"><?php echo getRoleName($_SESSION['user_role'] ?? ''); ?></div>
                </div>
                <a href="logout.php" class="logout-btn" title="Выйти">
                    <i class="fas fa-sign-out-alt"></i>
                </a>
            </div>
        </div>
    </div>

    <!-- Центральная колонка - 70% -->
    <div class="main-content">
        <div class="content-header">
            <h1 class="page-title">Рабочий стол</h1>
            <div class="page-subtitle">Обзор системы и быстрый доступ</div>
        </div>

        <div class="content-body">
            <!-- Статистика -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-number"><?php echo $stats['total_applications']; ?></div>
                    <div class="stat-description">Всего заявок</div>
                </div>
                <div class="stat-card warning">
                    <div class="stat-number"><?php echo $stats['new_applications']; ?></div>
                    <div class="stat-description">Новых заявок</div>
                </div>
                <div class="stat-card success">
                    <div class="stat-number"><?php echo $stats['active_drivers']; ?></div>
                    <div class="stat-description">Активных водителей</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $stats['available_vehicles']; ?></div>
                    <div class="stat-description">Доступных авто</div>
                </div>
            </div>

            <!-- Быстрые действия -->
            <div class="quick-actions">
                <a href="applications.php?action=create" class="action-btn">
                    <i class="fas fa-plus-circle"></i>
                    <div class="action-title">Создать заявку</div>
                </a>
                <a href="drivers.php?action=create" class="action-btn">
                    <i class="fas fa-user-plus"></i>
                    <div class="action-title">Добавить водителя</div>
                </a>
                <a href="vehicles.php?action=create" class="action-btn">
                    <i class="fas fa-truck-moving"></i>
                    <div class="action-title">Добавить авто</div>
                </a>
                <a href="tracking.php" class="action-btn">
                    <i class="fas fa-map-marked-alt"></i>
                    <div class="action-title">Отслеживание</div>
                </a>
                <a href="analytics.php" class="action-btn">
                    <i class="fas fa-chart-line"></i>
                    <div class="action-title">Аналитика</div>
                </a>
                <a href="billing.php" class="action-btn">
                    <i class="fas fa-file-invoice-dollar"></i>
                    <div class="action-title">Финансы</div>
                </a>
            </div>

            <!-- Последние заявки -->
            <div class="recent-section">
                <h3 class="section-title">Последние заявки</h3>
                <div style="color: #7f8c8d; text-align: center; padding: 20px;">
                    <i class="fas fa-inbox" style="font-size: 48px; margin-bottom: 10px;"></i>
                    <div>Здесь будут отображаться последние заявки</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Правая колонка - 15% -->
    <div class="activity-sidebar">
        <div class="activity-header">
            <h3 class="activity-title">История действий</h3>
        </div>
        <div class="activity-list">
            <?php if (empty($activity_log)): ?>
                <div style="color: #7f8c8d; text-align: center; padding: 20px; font-size: 12px;">
                    <i class="fas fa-history" style="font-size: 24px; margin-bottom: 10px;"></i>
                    <div>История действий появится здесь</div>
                </div>
            <?php else: ?>
                <?php foreach ($activity_log as $activity): ?>
                <div class="activity-item">
                    <div class="activity-time">
                        <?php echo date('H:i', strtotime($activity['created_at'])); ?>
                    </div>
                    <div class="activity-user">
                        <?php echo htmlspecialchars($activity['full_name'] ?? $activity['username'] ?? 'Система'); ?>
                    </div>
                    <div class="activity-action">
                        <?php echo htmlspecialchars($activity['action']); ?>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Календарь
        let currentDate = new Date();
        let currentMonth = currentDate.getMonth();
        let currentYear = currentDate.getFullYear();

        function initCalendar() {
            updateCalendar();
        }

        function updateCalendar() {
            const monthNames = [
                "Январь", "Февраль", "Март", "Апрель", "Май", "Июнь",
                "Июль", "Август", "Сентябрь", "Октябрь", "Ноябрь", "Декабрь"
            ];
            
            document.getElementById('calendarTitle').textContent = 
                `${monthNames[currentMonth]} ${currentYear}`;
            
            const firstDay = new Date(currentYear, currentMonth, 1);
            const lastDay = new Date(currentYear, currentMonth + 1, 0);
            const startingDay = firstDay.getDay() === 0 ? 6 : firstDay.getDay() - 1;
            
            let calendarHTML = '';
            
            // Дни недели
            const weekdays = ['Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб', 'Вс'];
            weekdays.forEach(day => {
                calendarHTML += `<div class="calendar-weekday">${day}</div>`;
            });
            
            // Пустые ячейки перед первым днем
            for (let i = 0; i < startingDay; i++) {
                const prevMonthDay = new Date(currentYear, currentMonth, -i);
                calendarHTML += `<div class="calendar-day other-month">${prevMonthDay.getDate()}</div>`;
            }
            
            // Дни текущего месяца
            const today = new Date();
            for (let day = 1; day <= lastDay.getDate(); day++) {
                const isToday = today.getDate() === day && 
                               today.getMonth() === currentMonth && 
                               today.getFullYear() === currentYear;
                const hasEvents = Math.random() > 0.7; // Заглушка
                
                let dayClass = 'calendar-day';
                if (isToday) dayClass += ' today';
                if (hasEvents) dayClass += ' has-events';
                
                calendarHTML += `<div class="${dayClass}" onclick="selectDate(${day})">${day}</div>`;
            }
            
            // Пустые ячейки после последнего дня
            const totalCells = 42; // 6 строк * 7 дней
            const remainingCells = totalCells - (startingDay + lastDay.getDate());
            for (let i = 1; i <= remainingCells; i++) {
                calendarHTML += `<div class="calendar-day other-month">${i}</div>`;
            }
            
            document.getElementById('calendarDays').innerHTML = calendarHTML;
        }

        function changeMonth(direction) {
            currentMonth += direction;
            if (currentMonth > 11) {
                currentMonth = 0;
                currentYear++;
            } else if (currentMonth < 0) {
                currentMonth = 11;
                currentYear--;
            }
            updateCalendar();
        }

        function selectDate(day) {
            const selectedDate = new Date(currentYear, currentMonth, day);
            alert(`Выбрана дата: ${selectedDate.toLocaleDateString('ru-RU')}`);
            // Здесь можно добавить загрузку событий на выбранную дату
        }

        // Инициализация при загрузке
        document.addEventListener('DOMContentLoaded', function() {
            initCalendar();
        });
    </script>
</body>
</html>