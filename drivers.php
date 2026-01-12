<?php
require_once 'config.php';
require_once 'auth.php';
requireLogin();

// Логируем просмотр страницы
logAction('view_drivers_page', $_SESSION['user_id']);

// Получаем статистику по водителям
try {
    $stats = [
        'total' => 0,
        'active' => 0,
        'on_trip' => 0,
        'with_license' => 0,
        'on_vacation' => 0,
        'with_expired_docs' => 0
    ];
    
    // Здесь будет реальная статистика из БД
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM drivers WHERE is_active = 1");
    $stats['total'] = $stmt->fetchColumn();
    
    // Для демонстрации используем фиктивные данные
    $stats['active'] = $stats['total'];
    $stats['on_trip'] = ceil($stats['total'] * 0.3);
    $stats['with_license'] = $stats['total'];
    $stats['on_vacation'] = 2;
    $stats['with_expired_docs'] = 1;
    
} catch(Exception $e) {
    // Используем демо данные если БД недоступна
    $stats = [
        'total' => 24,
        'active' => 18, 
        'on_trip' => 6,
        'with_license' => 22,
        'on_vacation' => 2,
        'with_expired_docs' => 1
    ];
}

// Обработка действий с водителями
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $driver_id = $_POST['driver_id'] ?? 0;
    
    switch($action) {
        case 'add_driver':
            // Логика добавления водителя
            logAction('attempt_add_driver', $_SESSION['user_id']);
            break;
        case 'export_drivers':
            // Логика экспорта
            logAction('export_drivers_list', $_SESSION['user_id']);
            break;
        case 'delete_driver':
            // Логика удаления водителя
            logAction('delete_driver', $_SESSION['user_id']);
            break;
        case 'update_status':
            // Логика обновления статуса
            logAction('update_driver_status', $_SESSION['user_id']);
            break;
    }
}

// Получаем список водителей для таблицы
try {
    $drivers_query = "SELECT * FROM drivers WHERE is_active = 1 ORDER BY created_at DESC LIMIT 50";
    $drivers = $pdo->query($drivers_query)->fetchAll(PDO::FETCH_ASSOC);
} catch(Exception $e) {
    $drivers = [];
}

// Если нет водителей в БД, используем демо данные
if (empty($drivers)) {
    $drivers = [
        [
            'id' => 1,
            'full_name' => 'Иванов Иван Иванович',
            'phone' => '+7 (912) 345-67-89',
            'status' => 'active',
            'license_categories' => 'B, C, CE',
            'vehicle_number' => 'A123BC777',
            'last_trip' => '2024-11-25',
            'experience' => '5 лет'
        ],
        [
            'id' => 2,
            'full_name' => 'Петров Петр Петрович',
            'phone' => '+7 (923) 456-78-90',
            'status' => 'on_trip',
            'license_categories' => 'C, CE',
            'vehicle_number' => 'B456DE123',
            'last_trip' => '2024-11-26',
            'experience' => '3 года'
        ],
        [
            'id' => 3,
            'full_name' => 'Сидорова Анна Владимировна',
            'phone' => '+7 (934) 567-89-01',
            'status' => 'active',
            'license_categories' => 'B, D',
            'vehicle_number' => 'C789FG456',
            'last_trip' => '2024-11-24',
            'experience' => '7 лет'
        ]
    ];
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Водители - CRM.PROFTRANSFER</title>
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
            min-height: 100vh;
            color: #333;
            font-size: 13px;
            line-height: 1.4;
        }

        .header {
            background: #ffffff;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            position: sticky;
            top: 0;
            z-index: 1000;
            border-bottom: 1px solid #e0e0e0;
        }

        .container {
            max-width: 1800px;
            margin: 0 auto;
            padding: 0 12px;
        }

        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 0;
            min-height: 50px;
        }

        .logo h1 {
            color: #2c5aa0;
            font-size: 16px;
            font-weight: 700;
            white-space: nowrap;
        }

        .nav-list {
            display: flex;
            list-style: none;
            gap: 4px;
            flex-wrap: wrap;
        }

        .nav-list a {
            text-decoration: none;
            color: #555;
            font-weight: 500;
            padding: 6px 10px;
            border-radius: 4px;
            font-size: 12px;
            transition: all 0.2s ease;
            white-space: nowrap;
        }

        .nav-list a:hover {
            background: #e9ecef;
            color: #2c5aa0;
        }

        .nav-list a.active {
            background: #2c5aa0;
            color: white;
        }

        .header-actions {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 6px;
            background: #f8f9fa;
            padding: 4px 10px;
            border-radius: 15px;
            border: 1px solid #e9ecef;
            font-size: 12px;
        }

        .user-info i {
            color: #2c5aa0;
        }

        .user-info span {
            font-weight: 600;
            color: #495057;
        }

        .role-badge {
            background: #2c5aa0;
            color: white;
            padding: 2px 6px;
            border-radius: 10px;
            font-size: 10px;
            font-weight: 600;
        }

        .btn {
            padding: 6px 12px;
            border: none;
            border-radius: 4px;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            font-size: 12px;
            white-space: nowrap;
        }

        .btn-primary {
            background: #2c5aa0;
            color: white;
            border: 1px solid #2c5aa0;
        }

        .btn-primary:hover {
            background: #1e4a8a;
            border-color: #1e4a8a;
            transform: translateY(-1px);
        }

        .btn-success {
            background: #28a745;
            color: white;
            border: 1px solid #28a745;
        }

        .btn-success:hover {
            background: #218838;
            border-color: #1e7e34;
        }

        .btn-warning {
            background: #ffc107;
            color: #212529;
            border: 1px solid #ffc107;
        }

        .btn-warning:hover {
            background: #e0a800;
            border-color: #d39e00;
        }

        .btn-danger {
            background: #dc3545;
            color: white;
            border: 1px solid #dc3545;
        }

        .btn-danger:hover {
            background: #c82333;
            border-color: #bd2130;
        }

        .btn-outline {
            background: transparent;
            color: #6c757d;
            border: 1px solid #6c757d;
        }

        .btn-outline:hover {
            background: #6c757d;
            color: white;
        }

        .btn-sm {
            padding: 4px 8px;
            font-size: 11px;
        }

        .btn-xs {
            padding: 2px 6px;
            font-size: 10px;
        }

        .main {
            padding: 12px 0;
        }

        .content-card {
            background: white;
            border-radius: 6px;
            padding: 15px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            border: 1px solid #e0e0e0;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #e9ecef;
        }

        .page-title {
            color: #2c5aa0;
            font-size: 18px;
            font-weight: 600;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 8px;
            margin-bottom: 15px;
        }

        .stat-card {
            background: #f8f9fa;
            padding: 10px;
            border-radius: 5px;
            border-left: 3px solid #2c5aa0;
            transition: all 0.2s ease;
            cursor: pointer;
        }

        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .stat-card.warning {
            border-left-color: #ffc107;
        }

        .stat-card.danger {
            border-left-color: #dc3545;
        }

        .stat-card.success {
            border-left-color: #28a745;
        }

        .stat-card.info {
            border-left-color: #17a2b8;
        }

        .stat-card h3 {
            color: #495057;
            margin-bottom: 3px;
            font-size: 11px;
            font-weight: 600;
        }

        .stat-number {
            font-size: 20px;
            font-weight: bold;
            margin: 3px 0;
        }

        .stat-card .stat-number {
            color: #2c5aa0;
        }

        .stat-card.warning .stat-number {
            color: #ffc107;
        }

        .stat-card.danger .stat-number {
            color: #dc3545;
        }

        .stat-card.success .stat-number {
            color: #28a745;
        }

        .stat-card.info .stat-number {
            color: #17a2b8;
        }

        .stat-description {
            color: #6c757d;
            font-size: 10px;
        }

        .action-buttons {
            display: flex;
            gap: 6px;
            margin-bottom: 15px;
            flex-wrap: wrap;
        }

        .filters-section {
            background: #f8f9fa;
            padding: 12px;
            border-radius: 5px;
            margin-bottom: 15px;
            border: 1px solid #e9ecef;
        }

        .filters-section h3 {
            color: #495057;
            margin-bottom: 8px;
            font-size: 13px;
            font-weight: 600;
        }

        .filter-row {
            display: flex;
            gap: 6px;
            flex-wrap: wrap;
        }

        .filter-input {
            padding: 5px 8px;
            border: 1px solid #ced4da;
            border-radius: 4px;
            flex: 1;
            min-width: 140px;
            font-size: 12px;
            background: white;
        }

        .filter-input:focus {
            outline: none;
            border-color: #2c5aa0;
            box-shadow: 0 0 0 2px rgba(44, 90, 160, 0.1);
        }

        .drivers-table-container {
            overflow-x: auto;
            margin-bottom: 15px;
            border: 1px solid #e0e0e0;
            border-radius: 5px;
        }

        .drivers-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 12px;
            min-width: 800px;
        }

        .drivers-table th {
            background: #f8f9fa;
            color: #495057;
            font-weight: 600;
            padding: 8px 10px;
            text-align: left;
            border-bottom: 2px solid #e9ecef;
            white-space: nowrap;
        }

        .drivers-table td {
            padding: 6px 10px;
            border-bottom: 1px solid #e9ecef;
            vertical-align: middle;
        }

        .drivers-table tr:hover {
            background: #f8f9fa;
        }

        .status-badge {
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 10px;
            font-weight: 600;
            white-space: nowrap;
        }

        .status-active {
            background: #d4edda;
            color: #155724;
        }

        .status-on-trip {
            background: #cce7ff;
            color: #004085;
        }

        .status-vacation {
            background: #fff3cd;
            color: #856404;
        }

        .status-sick {
            background: #f8d7da;
            color: #721c24;
        }

        .status-inactive {
            background: #e2e3e5;
            color: #383d41;
        }

        .table-actions {
            display: flex;
            gap: 3px;
            flex-wrap: nowrap;
        }

        .pagination {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 12px;
            padding-top: 10px;
            border-top: 1px solid #e9ecef;
        }

        .pagination-info {
            color: #6c757d;
            font-size: 11px;
        }

        .pagination-controls {
            display: flex;
            gap: 3px;
        }

        .info-box {
            background: #e7f3ff;
            padding: 12px;
            border-radius: 5px;
            margin-top: 15px;
            border-left: 3px solid #2c5aa0;
        }

        .info-box h3 {
            color: #2c5aa0;
            margin-bottom: 5px;
            font-size: 13px;
            font-weight: 600;
        }

        .info-box p {
            color: #495057;
            line-height: 1.4;
            font-size: 11px;
            margin-bottom: 3px;
        }

        /* Модальные окна */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 10000;
            align-items: center;
            justify-content: center;
            padding: 10px;
        }

        .modal-content {
            background: white;
            padding: 20px;
            border-radius: 6px;
            width: 100%;
            max-width: 700px;
            max-height: 95vh;
            overflow-y: auto;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #e9ecef;
        }

        .modal-title {
            color: #2c5aa0;
            font-size: 16px;
            font-weight: 600;
        }

        .modal-close {
            background: none;
            border: none;
            font-size: 20px;
            cursor: pointer;
            color: #6c757d;
            padding: 0;
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .modal-close:hover {
            color: #495057;
        }

        .form-section {
            margin-bottom: 15px;
        }

        .form-section-title {
            color: #495057;
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 8px;
            padding-bottom: 5px;
            border-bottom: 1px solid #e9ecef;
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px;
            margin-bottom: 10px;
        }

        .form-group {
            margin-bottom: 8px;
        }

        .form-label {
            display: block;
            margin-bottom: 3px;
            color: #495057;
            font-weight: 600;
            font-size: 11px;
        }

        .form-label.required::after {
            content: " *";
            color: #dc3545;
        }

        .form-input {
            width: 100%;
            padding: 6px 8px;
            border: 1px solid #ced4da;
            border-radius: 4px;
            font-size: 12px;
            background: white;
            transition: border-color 0.2s ease;
        }

        .form-input:focus {
            outline: none;
            border-color: #2c5aa0;
            box-shadow: 0 0 0 2px rgba(44, 90, 160, 0.1);
        }

        .form-select {
            width: 100%;
            padding: 6px 8px;
            border: 1px solid #ced4da;
            border-radius: 4px;
            font-size: 12px;
            background: white;
            cursor: pointer;
        }

        .form-textarea {
            width: 100%;
            padding: 6px 8px;
            border: 1px solid #ced4da;
            border-radius: 4px;
            font-size: 12px;
            resize: vertical;
            min-height: 60px;
            font-family: inherit;
        }

        .form-checkbox-group {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .form-checkbox {
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .form-checkbox input[type="checkbox"] {
            margin: 0;
        }

        .form-actions {
            display: flex;
            gap: 8px;
            justify-content: flex-end;
            margin-top: 20px;
            padding-top: 15px;
            border-top: 1px solid #e9ecef;
        }

        .file-upload {
            border: 1px dashed #ced4da;
            border-radius: 4px;
            padding: 15px;
            text-align: center;
            background: #f8f9fa;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .file-upload:hover {
            border-color: #2c5aa0;
            background: #e7f3ff;
        }

        .file-upload i {
            font-size: 24px;
            color: #6c757d;
            margin-bottom: 5px;
        }

        .file-upload-text {
            color: #6c757d;
            font-size: 11px;
        }

        /* Адаптивность */
        @media (max-width: 768px) {
            .header-content {
                flex-direction: column;
                gap: 8px;
            }

            .nav-list {
                justify-content: center;
            }

            .page-header {
                flex-direction: column;
                gap: 8px;
                align-items: flex-start;
            }

            .action-buttons {
                width: 100%;
                justify-content: flex-start;
            }

            .filter-row {
                flex-direction: column;
            }

            .form-grid {
                grid-template-columns: 1fr;
            }

            .modal-content {
                margin: 10px;
                padding: 15px;
            }
        }

        /* Анимации */
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .modal-content {
            animation: slideIn 0.2s ease;
        }

        /* Уведомления */
        .notification {
            position: fixed;
            top: 60px;
            right: 20px;
            padding: 10px 15px;
            border-radius: 4px;
            color: white;
            font-weight: 600;
            z-index: 10001;
            animation: slideIn 0.3s ease;
            max-width: 300px;
            font-size: 12px;
        }

        .notification.success {
            background: #28a745;
        }

        .notification.error {
            background: #dc3545;
        }

        .notification.info {
            background: #17a2b8;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="container">
            <div class="header-content">
                <div class="logo">
                    <h1><i class="fas fa-tachometer-alt"></i> CRM.PROFTRANSFER</h1>
                </div>
                
                <nav class="nav">
                    <ul class="nav-list">
                        <li><a href="index.php"><i class="fas fa-home"></i> Главная</a></li>
                        <li><a href="applications.php"><i class="fas fa-file-alt"></i> Заявки</a></li>
                        <li><a href="drivers.php" class="active"><i class="fas fa-users"></i> Водители</a></li>
                        <li><a href="vehicles.php"><i class="fas fa-truck"></i> Транспорт</a></li>
                        <li><a href="companies.php"><i class="fas fa-building"></i> Компании</a></li>
                        <li><a href="tracking.php"><i class="fas fa-map-marker-alt"></i> Трекинг</a></li>
                        <li><a href="analytics.php"><i class="fas fa-chart-bar"></i> Аналитика</a></li>
                    </ul>
                </nav>
                
                <div class="header-actions">
                    <div class="user-info">
                        <i class="fas fa-user-circle"></i>
                        <span><?php echo htmlspecialchars($_SESSION['full_name'] ?? $_SESSION['username']); ?></span>
                        <?php if (isAdmin()): ?>
                            <span class="role-badge"><i class="fas fa-crown"></i> Администратор</span>
                        <?php elseif (isManager()): ?>
                            <span class="role-badge"><i class="fas fa-headset"></i> Диспетчер</span>
                        <?php elseif (isManager()): ?>
                            <span class="role-badge"><i class="fas fa-user-tie"></i> Менеджер</span>
                        <?php endif; ?>
                    </div>
                    <a href="logout.php" class="btn btn-outline">
                        <i class="fas fa-sign-out-alt"></i> Выйти
                    </a>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="main">
        <div class="container">
            <div class="content-card">
                <div class="page-header">
                    <h1 class="page-title"><i class="fas fa-users"></i> Управление водителями</h1>
                    <div class="action-buttons">
                        <button class="btn btn-primary" onclick="showAddDriverModal()">
                            <i class="fas fa-user-plus"></i> Добавить водителя
                        </button>
                        <button class="btn btn-success" onclick="exportDrivers()">
                            <i class="fas fa-file-export"></i> Экспорт списка
                        </button>
                        <button class="btn btn-warning" onclick="refreshData()">
                            <i class="fas fa-sync-alt"></i> Обновить
                        </button>
                        <?php if (isAdmin()): ?>
                        <button class="btn btn-danger" onclick="showBulkActions()">
                            <i class="fas fa-cog"></i> Массовые действия
                        </button>
                        <?php endif; ?>
                    </div>
                </div>

                <p style="margin-bottom: 15px; color: #6c757d; font-size: 12px;">
                    Управление водительским составом, документами и лицензиями. Всего в системе: <strong><?php echo $stats['total']; ?> водителей</strong>
                </p>
                
                <!-- Статистика -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <h3><i class="fas fa-users"></i> Всего водителей</h3>
                        <div class="stat-number"><?php echo $stats['total']; ?></div>
                        <p class="stat-description">В базе данных</p>
                    </div>
                    
                    <div class="stat-card success">
                        <h3><i class="fas fa-user-check"></i> Активные</h3>
                        <div class="stat-number"><?php echo $stats['active']; ?></div>
                        <p class="stat-description">Доступны для работы</p>
                    </div>
                    
                    <div class="stat-card info">
                        <h3><i class="fas fa-truck"></i> В рейсе</h3>
                        <div class="stat-number"><?php echo $stats['on_trip']; ?></div>
                        <p class="stat-description">Выполняют заказы</p>
                    </div>
                    
                    <div class="stat-card warning">
                        <h3><i class="fas fa-umbrella-beach"></i> В отпуске</h3>
                        <div class="stat-number"><?php echo $stats['on_vacation']; ?></div>
                        <p class="stat-description">Недоступны</p>
                    </div>
                    
                    <div class="stat-card">
                        <h3><i class="fas fa-id-card"></i> С лицензиями</h3>
                        <div class="stat-number"><?php echo $stats['with_license']; ?></div>
                        <p class="stat-description">Действующие права</p>
                    </div>
                    
                    <div class="stat-card danger">
                        <h3><i class="fas fa-exclamation-triangle"></i> Просрочены доки</h3>
                        <div class="stat-number"><?php echo $stats['with_expired_docs']; ?></div>
                        <p class="stat-description">Требуют внимания</p>
                    </div>
                </div>

                <!-- Фильтры и поиск -->
                <div class="filters-section">
                    <h3><i class="fas fa-filter"></i> Фильтры и поиск</h3>
                    <form id="filtersForm" class="filter-row">
                        <input type="text" name="search" placeholder="Поиск по ФИО, телефону..." class="filter-input">
                        <select name="status" class="filter-input">
                            <option value="">Все статусы</option>
                            <option value="active">Активные</option>
                            <option value="on_trip">В рейсе</option>
                            <option value="vacation">Отпуск</option>
                            <option value="sick">На больничном</option>
                            <option value="inactive">Неактивные</option>
                        </select>
                        <select name="license_type" class="filter-input">
                            <option value="">Все категории прав</option>
                            <option value="B">Категория B</option>
                            <option value="C">Категория C</option>
                            <option value="CE">Категория CE</option>
                            <option value="D">Категория D</option>
                            <option value="DE">Категория DE</option>
                        </select>
                        <select name="company" class="filter-input">
                            <option value="">Все компании</option>
                            <option value="1">ООО "ПРОФТРАНСФЕР"</option>
                            <option value="2">ИП Иванов</option>
                        </select>
                        <button type="button" class="btn btn-primary" onclick="applyFilters()">
                            <i class="fas fa-search"></i> Применить
                        </button>
                        <button type="button" class="btn btn-outline" onclick="resetFilters()">
                            <i class="fas fa-times"></i> Сбросить
                        </button>
                    </form>
                </div>

                <!-- Таблица водителей -->
                <div class="drivers-table-container">
                    <table class="drivers-table">
                        <thead>
                            <tr>
                                <th style="width: 30px;">
                                    <input type="checkbox" id="selectAll" onchange="toggleSelectAll(this)">
                                </th>
                                <th>ID</th>
                                <th>ФИО</th>
                                <th>Телефон</th>
                                <th>E-mail</th>
                                <th>Категории прав</th>
                                <th>Статус</th>
                                <th>Автомобиль</th>
                                <th>Последний рейс</th>
                                <th>Стаж</th>
                                <th style="width: 120px;">Действия</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($drivers as $driver): ?>
                            <tr>
                                <td>
                                    <input type="checkbox" class="driver-checkbox" value="<?php echo $driver['id']; ?>">
                                </td>
                                <td>DRV-<?php echo str_pad($driver['id'], 3, '0', STR_PAD_LEFT); ?></td>
                                <td>
                                    <div style="font-weight: 600;"><?php echo htmlspecialchars($driver['full_name']); ?></div>
                                    <div style="font-size: 10px; color: #6c757d;">ID: <?php echo $driver['id']; ?></div>
                                </td>
                                <td><?php echo htmlspecialchars($driver['phone']); ?></td>
                                <td><?php echo htmlspecialchars($driver['email'] ?? 'Не указан'); ?></td>
                                <td>
                                    <div style="display: flex; gap: 2px; flex-wrap: wrap;">
                                        <?php 
                                        $categories = explode(',', $driver['license_categories']);
                                        foreach ($categories as $category): 
                                            $category = trim($category);
                                            if (!empty($category)):
                                        ?>
                                        <span style="background: #e7f3ff; color: #2c5aa0; padding: 1px 4px; border-radius: 3px; font-size: 9px; font-weight: 600;">
                                            <?php echo htmlspecialchars($category); ?>
                                        </span>
                                        <?php endif; endforeach; ?>
                                    </div>
                                </td>
                                <td>
                                    <?php 
                                    $statusClass = 'status-active';
                                    $statusText = 'Активен';
                                    switch($driver['status']) {
                                        case 'on_trip': 
                                            $statusClass = 'status-on-trip'; 
                                            $statusText = 'В рейсе'; 
                                            break;
                                        case 'vacation': 
                                            $statusClass = 'status-vacation'; 
                                            $statusText = 'Отпуск'; 
                                            break;
                                        case 'sick': 
                                            $statusClass = 'status-sick'; 
                                            $statusText = 'Больничный'; 
                                            break;
                                        case 'inactive': 
                                            $statusClass = 'status-inactive'; 
                                            $statusText = 'Неактивен'; 
                                            break;
                                    }
                                    ?>
                                    <span class="status-badge <?php echo $statusClass; ?>"><?php echo $statusText; ?></span>
                                </td>
                                <td>
                                    <?php if (!empty($driver['vehicle_number'])): ?>
                                    <div style="font-weight: 600; color: #2c5aa0;"><?php echo htmlspecialchars($driver['vehicle_number']); ?></div>
                                    <div style="font-size: 10px; color: #6c757d;">Mercedes Sprinter</div>
                                    <?php else: ?>
                                    <span style="color: #6c757d; font-size: 11px;">Не назначен</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($driver['last_trip'])): ?>
                                    <div style="font-weight: 600;"><?php echo date('d.m.Y', strtotime($driver['last_trip'])); ?></div>
                                    <div style="font-size: 10px; color: #6c757d;">Москва - СПб</div>
                                    <?php else: ?>
                                    <span style="color: #6c757d; font-size: 11px;">Нет рейсов</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div style="font-weight: 600;"><?php echo htmlspecialchars($driver['experience']); ?></div>
                                    <div style="font-size: 10px; color: #6c757d;">в компании</div>
                                </td>
                                <td class="table-actions">
                                    <button class="btn btn-primary btn-xs" onclick="editDriver(<?php echo $driver['id']; ?>)" title="Редактировать">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn btn-success btn-xs" onclick="viewDriver(<?php echo $driver['id']; ?>)" title="Просмотреть">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button class="btn btn-warning btn-xs" onclick="manageDocuments(<?php echo $driver['id']; ?>)" title="Документы">
                                        <i class="fas fa-file-alt"></i>
                                    </button>
                                    <?php if (isAdmin()): ?>
                                    <button class="btn btn-danger btn-xs" onclick="deleteDriver(<?php echo $driver['id']; ?>)" title="Удалить">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Пагинация -->
                <div class="pagination">
                    <div class="pagination-info">
                        Показано <?php echo count($drivers); ?> из <?php echo $stats['total']; ?> водителей
                    </div>
                    <div class="pagination-controls">
                        <button class="btn btn-sm" disabled>
                            <i class="fas fa-chevron-left"></i>
                        </button>
                        <button class="btn btn-primary btn-sm">1</button>
                        <button class="btn btn-sm">2</button>
                        <button class="btn btn-sm">3</button>
                        <button class="btn btn-sm">
                            <i class="fas fa-chevron-right"></i>
                        </button>
                    </div>
                </div>

                <!-- Информационный блок -->
                <div class="info-box">
                    <h3><i class="fas fa-info-circle"></i> Информация о разделе</h3>
                    <p>
                        В этом разделе вы можете управлять водительским составом компании. Доступные функции: 
                        добавление новых водителей, редактирование информации, управление документами, 
                        просмотр истории поездок и назначение на рейсы.
                    </p>
                    <p>
                        <strong>Статус системы:</strong> Основной функционал работает. В разработке: массовые операции, импорт данных, 
                        уведомления о сроке действия документов, интеграция с системой трекинга.
                    </p>
                </div>
            </div>
        </div>
    </main>

    <!-- Модальное окно добавления водителя -->
    <div id="addDriverModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title"><i class="fas fa-user-plus"></i> Добавить водителя</h3>
                <button class="modal-close" onclick="closeModal('addDriverModal')">&times;</button>
            </div>
            
            <form id="addDriverForm" onsubmit="handleAddDriver(event)">
                <!-- Основная информация -->
                <div class="form-section">
                    <h4 class="form-section-title">Основная информация</h4>
                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label required">Компания</label>
                            <select class="form-select" name="company_id" required>
                                <option value="">Выберите компанию</option>
                                <option value="1">ООО "ПРОФТРАНСФЕР"</option>
                                <option value="2">ИП Иванов</option>
                                <option value="3">ООО "ТрансЛогист"</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">ID в системе поставщика</label>
                            <input type="text" class="form-input" name="supplier_id" placeholder="ID">
                        </div>
                    </div>
                    
                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label required">Фамилия водителя</label>
                            <input type="text" class="form-input" name="last_name" placeholder="Фамилия" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label required">Имя водителя</label>
                            <input type="text" class="form-input" name="first_name" placeholder="Имя" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Отчество водителя</label>
                            <input type="text" class="form-input" name="middle_name" placeholder="Отчество">
                        </div>
                        <div class="form-group">
                            <label class="form-label required">Телефон водителя</label>
                            <input type="tel" class="form-input" name="phone" placeholder="+7 (___) ___-__-__" required>
                        </div>
                    </div>

                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label">Дополнительный телефон</label>
                            <input type="tel" class="form-input" name="phone_secondary" placeholder="+7 (___) ___-__-__">
                        </div>
                        <div class="form-group">
                            <label class="form-label">E-mail адрес</label>
                            <input type="email" class="form-input" name="email" placeholder="email@example.com">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Логин для iDriver</label>
                            <input type="text" class="form-input" name="idriver_login" placeholder="Логин">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Пароль для iDriver</label>
                            <input type="password" class="form-input" name="idriver_password" placeholder="Пароль">
                        </div>
                    </div>
                </div>

                <!-- Местонахождение -->
                <div class="form-section">
                    <h4 class="form-section-title">Местонахождение</h4>
                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label">Страна</label>
                            <select class="form-select" name="country">
                                <option value="">Выберите страну</option>
                                <option value="RU" selected>Россия</option>
                                <option value="KZ">Казахстан</option>
                                <option value="BY">Беларусь</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Город</label>
                            <select class="form-select" name="city">
                                <option value="">Выберите город</option>
                                <option value="moscow">Москва</option>
                                <option value="spb">Санкт-Петербург</option>
                                <option value="ekb">Екатеринбург</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Регион проживания</label>
                            <input type="text" class="form-input" name="region" placeholder="Микрорайон">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Комментарий к водителю</label>
                        <textarea class="form-textarea" name="comment" placeholder="Комментарий"></textarea>
                    </div>
                </div>

                <!-- Паспортные данные -->
                <div class="form-section">
                    <h4 class="form-section-title">Паспортные данные</h4>
                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label required">Серия и номер паспорта</label>
                            <input type="text" class="form-input" name="passport_number" placeholder="Серия и номер" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label required">Кем выдан</label>
                            <input type="text" class="form-input" name="passport_issued_by" placeholder="Кем выдан" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label required">Дата выдачи</label>
                            <input type="date" class="form-input" name="passport_issue_date" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Адрес регистрации</label>
                        <textarea class="form-textarea" name="registration_address" placeholder="Город, улица, дом, квартира"></textarea>
                    </div>
                </div>

                <!-- Рабочие параметры -->
                <div class="form-section">
                    <h4 class="form-section-title">Рабочие параметры</h4>
                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label">График</label>
                            <select class="form-select" name="schedule">
                                <option value="day">Дневной</option>
                                <option value="night">Ночной</option>
                                <option value="shift">Сменный</option>
                                <option value="flexible">Гибкий</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Статус</label>
                            <select class="form-select" name="status">
                                <option value="active">В работе</option>
                                <option value="vacation">Отпуск</option>
                                <option value="sick">Больничный</option>
                                <option value="inactive">Неактивен</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Категории прав</label>
                            <div class="form-checkbox-group">
                                <label class="form-checkbox">
                                    <input type="checkbox" name="license_categories[]" value="B"> B
                                </label>
                                <label class="form-checkbox">
                                    <input type="checkbox" name="license_categories[]" value="C"> C
                                </label>
                                <label class="form-checkbox">
                                    <input type="checkbox" name="license_categories[]" value="CE"> CE
                                </label>
                                <label class="form-checkbox">
                                    <input type="checkbox" name="license_categories[]" value="D"> D
                                </label>
                                <label class="form-checkbox">
                                    <input type="checkbox" name="license_categories[]" value="DE"> DE
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Автомобиль водителя -->
                <div class="form-section">
                    <h4 class="form-section-title">Автомобиль водителя</h4>
                    <div class="form-group">
                        <div style="background: #f8f9fa; padding: 10px; border-radius: 4px; margin-bottom: 10px;">
                            <div style="color: #6c757d; font-size: 11px; margin-bottom: 5px;">
                                Водитель не имеет закреплённых за ним автомобилей
                            </div>
                            <button type="button" class="btn btn-primary btn-sm" onclick="showAddVehicleModal()">
                                <i class="fas fa-plus"></i> Добавить автомобиль
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Фотографии -->
                <div class="form-section">
                    <h4 class="form-section-title">Фотографии</h4>
                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label">Фотография водителя</label>
                            <div class="file-upload" onclick="document.getElementById('driverPhoto').click()">
                                <i class="fas fa-cloud-upload-alt"></i>
                                <div class="file-upload-text">Нажмите для загрузки фото</div>
                                <input type="file" id="driverPhoto" name="photo" accept="image/*" style="display: none;">
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Дополнительные фотографии</label>
                            <div class="file-upload" onclick="document.getElementById('additionalPhotos').click()">
                                <i class="fas fa-images"></i>
                                <div class="file-upload-text">+ Прикрепить фотографию</div>
                                <input type="file" id="additionalPhotos" name="additional_photos[]" multiple accept="image/*" style="display: none;">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="button" class="btn btn-outline" onclick="closeModal('addDriverModal')">Отмена</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Добавить водителя
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Основные функции управления
        function showAddDriverModal() {
            document.getElementById('addDriverModal').style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }

        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
            document.body.style.overflow = 'auto';
        }

        function toggleSelectAll(checkbox) {
            const checkboxes = document.querySelectorAll('.driver-checkbox');
            checkboxes.forEach(cb => cb.checked = checkbox.checked);
        }

        function applyFilters() {
            const formData = new FormData(document.getElementById('filtersForm'));
            console.log('Применяем фильтры:', Object.fromEntries(formData));
            showNotification('Фильтры применены', 'success');
        }

        function resetFilters() {
            document.getElementById('filtersForm').reset();
            showNotification('Фильтры сброшены', 'info');
        }

        function exportDrivers() {
            if (confirm('Экспортировать список водителей в Excel?')) {
                showNotification('Экспорт начат...', 'info');
                // Логика экспорта
            }
        }

        function refreshData() {
            location.reload();
        }

        function showBulkActions() {
            const selected = document.querySelectorAll('.driver-checkbox:checked');
            if (selected.length === 0) {
                alert('Выберите водителей для массовых действий');
                return;
            }
            alert(`Массовые действия для ${selected.length} водителей`);
        }

        // Функции для работы с водителями
        function editDriver(id) {
            console.log('Редактирование водителя ID:', id);
            showNotification(`Редактирование водителя DRV-${id.toString().padStart(3, '0')}`, 'info');
        }

        function viewDriver(id) {
            console.log('Просмотр водителя ID:', id);
            showNotification(`Просмотр карточки водителя DRV-${id.toString().padStart(3, '0')}`, 'info');
        }

        function manageDocuments(id) {
            console.log('Управление документами водителя ID:', id);
            showNotification(`Управление документами водителя DRV-${id.toString().padStart(3, '0')}`, 'info');
        }

        function deleteDriver(id) {
            if (confirm(`Вы уверены, что хотите удалить водителя DRV-${id.toString().padStart(3, '0')}?`)) {
                console.log('Удаление водителя ID:', id);
                showNotification('Водитель удален', 'success');
            }
        }

        function handleAddDriver(event) {
            event.preventDefault();
            const formData = new FormData(event.target);
            
            // Валидация формы
            const requiredFields = ['company_id', 'last_name', 'first_name', 'phone', 'passport_number', 'passport_issued_by', 'passport_issue_date'];
            let isValid = true;
            
            requiredFields.forEach(field => {
                if (!formData.get(field)) {
                    isValid = false;
                    const input = event.target.querySelector(`[name="${field}"]`);
                    input.style.borderColor = '#dc3545';
                }
            });

            if (!isValid) {
                showNotification('Заполните все обязательные поля', 'error');
                return;
            }

            // Симуляция отправки на сервер
            console.log('Данные для добавления водителя:', Object.fromEntries(formData));
            
            showNotification('Водитель успешно добавлен в систему!', 'success');
            
            setTimeout(() => {
                closeModal('addDriverModal');
                // location.reload();
            }, 1500);
        }

        function showAddVehicleModal() {
            alert('Форма добавления автомобиля будет открыта в отдельном окне');
            // Здесь будет открытие модального окна добавления автомобиля
        }

        // Функция уведомлений
        function showNotification(message, type = 'info') {
            const notification = document.createElement('div');
            notification.className = `notification ${type}`;
            notification.textContent = message;
            document.body.appendChild(notification);
            
            setTimeout(() => {
                notification.style.animation = 'slideIn 0.3s ease reverse';
                setTimeout(() => {
                    document.body.removeChild(notification);
                }, 300);
            }, 3000);
        }

        // Обработчики событий
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('modal')) {
                e.target.style.display = 'none';
                document.body.style.overflow = 'auto';
            }
        });

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                const modals = document.querySelectorAll('.modal');
                modals.forEach(modal => {
                    modal.style.display = 'none';
                    document.body.style.overflow = 'auto';
                });
            }
            
            // Горячие клавиши
            if (e.ctrlKey && e.key === 'n') {
                e.preventDefault();
                showAddDriverModal();
            }
            if (e.ctrlKey && e.key === 'e') {
                e.preventDefault();
                exportDrivers();
            }
        });

        // Маска для телефона
        document.addEventListener('input', function(e) {
            if (e.target.type === 'tel') {
                let value = e.target.value.replace(/\D/g, '');
                if (value.startsWith('7') || value.startsWith('8')) {
                    value = value.substring(1);
                }
                if (value.length > 0) {
                    value = '+7 (' + value;
                    if (value.length > 7) value = value.substring(0, 7) + ') ' + value.substring(7);
                    if (value.length > 12) value = value.substring(0, 12) + '-' + value.substring(12);
                    if (value.length > 15) value = value.substring(0, 15) + '-' + value.substring(15);
                }
                e.target.value = value;
            }
        });

        // Анимация загрузки
        document.addEventListener('DOMContentLoaded', function() {
            const cards = document.querySelectorAll('.stat-card');
            cards.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(10px)';
                
                setTimeout(() => {
                    card.style.transition = 'all 0.3s ease';
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, index * 100);
            });
        });
    </script>
</body>
</html>