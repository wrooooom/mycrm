<?php
/**
 * Страница управления заказами (заявками)
 * Включает фильтрацию, пагинацию, быстрые действия
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';
requireLogin();

// Проверяем права доступа
if (!hasAccess('applications')) {
    header("Location: index.php");
    exit();
}

// Функция логирования
function logAction($action, $userId, $description = null) {
    try {
        $database = new Database();
        $conn = $database->getConnection();
        
        $logQuery = "INSERT INTO activity_log (user_id, action, ip_address, user_agent) 
                    VALUES (:user_id, :action, :ip, :user_agent)";
        $logStmt = $conn->prepare($logQuery);
        $logStmt->execute([
            ':user_id' => $userId,
            ':action' => $description ?? $action,
            ':ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            ':user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
        ]);
    } catch (Exception $e) {
        error_log("Logging error: " . $e->getMessage());
    }
}

// Логируем просмотр страницы
logAction('view_applications_page', $_SESSION['user_id']);

// Получаем параметры фильтрации из URL
$currentPage = max(1, intval($_GET['page'] ?? 1));
$limit = 20;
$statusFilter = $_GET['status'] ?? '';
$paymentStatusFilter = $_GET['payment_status'] ?? '';
$searchFilter = $_GET['search'] ?? '';
$dateFrom = $_GET['date_from'] ?? '';
$dateTo = $_GET['date_to'] ?? '';
$sortBy = $_GET['sort_by'] ?? 'trip_date';
$sortOrder = $_GET['sort_order'] ?? 'DESC';

// Формируем URL для пагинации
function buildQueryUrl($params) {
    $url = 'applications.php?';
    $queryParams = [];
    foreach ($params as $key => $value) {
        if (!empty($value)) {
            $queryParams[] = urlencode($key) . '=' . urlencode($value);
        }
    }
    return $url . implode('&', $queryParams);
}

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    // Получаем статистику по заявкам
    $userContext = [
        'user_id' => $_SESSION['user_id'],
        'role' => $_SESSION['user_role'],
        'company_id' => $_SESSION['company_id'] ?? null
    ];
    
    // Базовый запрос для статистики с учетом ролей
    $statsQuery = "SELECT COUNT(*) as total FROM applications a WHERE 1=1";
    $statsParams = [];
    
    if ($userContext['role'] === 'dispatcher' || $userContext['role'] === 'manager') {
        $statsQuery .= " AND (a.executor_company_id = :company_id OR a.executor_company_id IS NULL)";
        $statsParams[':company_id'] = $userContext['company_id'];
    } elseif ($userContext['role'] === 'driver') {
        $statsQuery .= " AND a.driver_id = (SELECT id FROM drivers WHERE user_id = :user_id)";
        $statsParams[':user_id'] = $userContext['user_id'];
    }
    
    // Общая статистика
    $totalStmt = $conn->prepare($statsQuery);
    $totalStmt->execute($statsParams);
    $stats['total'] = $totalStmt->fetchColumn();
    
    // Статистика по статусам
    foreach (['new', 'assigned', 'in_progress', 'completed', 'cancelled'] as $status) {
        $stmt = $conn->prepare($statsQuery . " AND a.status = :status");
        $params = $statsParams;
        $params[':status'] = $status;
        $stmt->execute($params);
        $stats[$status] = $stmt->fetchColumn();
    }
    
    // Статистика за сегодня
    $todayStmt = $conn->prepare($statsQuery . " AND DATE(a.created_at) = CURDATE()");
    $todayStmt->execute($statsParams);
    $stats['today'] = $todayStmt->fetchColumn();
    
    // Формируем основной запрос с фильтрацией
    $whereClause = "WHERE 1=1";
    $params = [];
    
    // ACL фильтрация
    if ($userContext['role'] === 'dispatcher' || $userContext['role'] === 'manager') {
        $whereClause .= " AND (a.executor_company_id = :company_id OR a.executor_company_id IS NULL)";
        $params[':company_id'] = $userContext['company_id'];
    } elseif ($userContext['role'] === 'driver') {
        $whereClause .= " AND a.driver_id = (SELECT id FROM drivers WHERE user_id = :user_id)";
        $params[':user_id'] = $userContext['user_id'];
    }
    
    // Фильтры
    if (!empty($statusFilter)) {
        $whereClause .= " AND a.status = :status";
        $params[':status'] = $statusFilter;
    }
    
    if (!empty($paymentStatusFilter)) {
        $whereClause .= " AND a.payment_status = :payment_status";
        $params[':payment_status'] = $paymentStatusFilter;
    }
    
    if (!empty($dateFrom)) {
        $whereClause .= " AND DATE(a.trip_date) >= :date_from";
        $params[':date_from'] = $dateFrom;
    }
    
    if (!empty($dateTo)) {
        $whereClause .= " AND DATE(a.trip_date) <= :date_to";
        $params[':date_to'] = $dateTo;
    }
    
    if (!empty($searchFilter)) {
        $whereClause .= " AND (a.application_number LIKE :search OR a.customer_name LIKE :search OR a.customer_phone LIKE :search)";
        $params[':search'] = "%{$searchFilter}%";
    }
    
    // Подсчет общего количества записей
    $countQuery = "SELECT COUNT(*) FROM applications a {$whereClause}";
    $countStmt = $conn->prepare($countQuery);
    $countStmt->execute($params);
    $totalRecords = $countStmt->fetchColumn();
    $totalPages = ceil($totalRecords / $limit);
    $offset = ($currentPage - 1) * $limit;
    
    // Основной запрос
    $allowedSortFields = ['trip_date', 'status', 'order_amount', 'created_at', 'application_number'];
    if (!in_array($sortBy, $allowedSortFields)) {
        $sortBy = 'trip_date';
    }
    if (!in_array($sortOrder, ['ASC', 'DESC'])) {
        $sortOrder = 'DESC';
    }
    
    $query = "SELECT a.*, 
                     d.first_name as driver_first_name, 
                     d.last_name as driver_last_name,
                     d.phone as driver_phone,
                     v.brand as vehicle_brand, 
                     v.model as vehicle_model,
                     v.license_plate as vehicle_plate,
                     c.name as customer_company_name,
                     ec.name as executor_company_name,
                     u.name as creator_name
              FROM applications a
              LEFT JOIN drivers d ON a.driver_id = d.id
              LEFT JOIN vehicles v ON a.vehicle_id = v.id
              LEFT JOIN companies c ON a.customer_company_id = c.id
              LEFT JOIN companies ec ON a.executor_company_id = ec.id
              LEFT JOIN users u ON a.created_by = u.id
              {$whereClause}
              ORDER BY a.{$sortBy} {$sortOrder}
              LIMIT :limit OFFSET :offset";
    
    $stmt = $conn->prepare($query);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $applications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Получаем маршруты и пассажиров для каждой заявки
    foreach ($applications as &$app) {
        // Маршруты
        $routeQuery = "SELECT * FROM application_routes WHERE application_id = :app_id ORDER BY point_order";
        $routeStmt = $conn->prepare($routeQuery);
        $routeStmt->execute([':app_id' => $app['id']]);
        $app['routes'] = $routeStmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Пассажиры
        $passengerQuery = "SELECT * FROM application_passengers WHERE application_id = :app_id";
        $passengerStmt = $conn->prepare($passengerQuery);
        $passengerStmt->execute([':app_id' => $app['id']]);
        $app['passengers'] = $passengerStmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Формируем читаемые поля
        if ($app['driver_first_name'] && $app['driver_last_name']) {
            $app['driver_name'] = $app['driver_first_name'] . ' ' . $app['driver_last_name'];
        } else {
            $app['driver_name'] = null;
        }
        
        if ($app['vehicle_brand'] && $app['vehicle_model']) {
            $app['vehicle_name'] = $app['vehicle_brand'] . ' ' . $app['vehicle_model'];
        } else {
            $app['vehicle_name'] = null;
        }
    }
    
} catch (Exception $e) {
    error_log("Applications page error: " . $e->getMessage());
    $applications = [];
    $stats = [
        'total' => 0,
        'new' => 0,
        'assigned' => 0,
        'in_progress' => 0,
        'completed' => 0,
        'cancelled' => 0,
        'today' => 0
    ];
    $totalPages = 1;
}

// Функция для получения названия статуса
function getStatusName($status) {
    $statuses = [
        'new' => 'Новая',
        'assigned' => 'Назначена',
        'in_progress' => 'В работе',
        'completed' => 'Завершена',
        'cancelled' => 'Отменена'
    ];
    return $statuses[$status] ?? $status;
}

// Функция для получения названия статуса оплаты
function getPaymentStatusName($status) {
    $statuses = [
        'pending' => 'Ожидает',
        'paid' => 'Оплачена',
        'refunded' => 'Возврат'
    ];
    return $statuses[$status] ?? $status;
}

// Функция для получения цвета статуса
function getStatusBadgeClass($status) {
    $classes = [
        'new' => 'bg-primary',
        'assigned' => 'bg-info',
        'in_progress' => 'bg-warning',
        'completed' => 'bg-success',
        'cancelled' => 'bg-danger'
    ];
    return $classes[$status] ?? 'bg-secondary';
}

$pageTitle = "Управление заказами";
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        .status-badge {
            font-size: 0.75rem;
        }
        .quick-actions {
            opacity: 0;
            transition: opacity 0.2s;
        }
        .table-row:hover .quick-actions {
            opacity: 1;
        }
        .filter-card {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
        }
        .stats-card {
            border-left: 4px solid;
        }
        .stats-card.total { border-left-color: #6c757d; }
        .stats-card.new { border-left-color: #0d6efd; }
        .stats-card.assigned { border-left-color: #0dcaf0; }
        .stats-card.in_progress { border-left-color: #ffc107; }
        .stats-card.completed { border-left-color: #198754; }
        .stats-card.cancelled { border-left-color: #dc3545; }
        .pagination {
            margin: 0;
        }
    </style>
</head>
<body>
    <?php require __DIR__ . '/templates/header.php'; ?>
    
    <div class="container-fluid">
        <div class="row">
            <?php require __DIR__ . '/templates/sidebar.php'; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">
                        <i class="fas fa-clipboard-list"></i>
                        Управление заказами
                    </h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createApplicationModal">
                                <i class="fas fa-plus"></i> Создать заказ
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Статистические карточки -->
                <div class="row mb-4">
                    <div class="col-md-2">
                        <div class="card stats-card total">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h6 class="card-title">Всего</h6>
                                        <h3 class="mb-0"><?php echo $stats['total']; ?></h3>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-list fa-2x text-muted"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="card stats-card new">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h6 class="card-title">Новые</h6>
                                        <h3 class="mb-0"><?php echo $stats['new']; ?></h3>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-plus-circle fa-2x text-primary"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="card stats-card assigned">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h6 class="card-title">Назначены</h6>
                                        <h3 class="mb-0"><?php echo $stats['assigned']; ?></h3>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-user-check fa-2x text-info"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="card stats-card in_progress">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h6 class="card-title">В работе</h6>
                                        <h3 class="mb-0"><?php echo $stats['in_progress']; ?></h3>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-cog fa-spin fa-2x text-warning"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="card stats-card completed">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h6 class="card-title">Завершены</h6>
                                        <h3 class="mb-0"><?php echo $stats['completed']; ?></h3>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-check-circle fa-2x text-success"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="card stats-card cancelled">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h6 class="card-title">Сегодня</h6>
                                        <h3 class="mb-0"><?php echo $stats['today']; ?></h3>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-calendar-day fa-2x text-muted"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Фильтры -->
                <div class="card filter-card mb-4">
                    <div class="card-body">
                        <form method="GET" action="" class="row g-3">
                            <div class="col-md-3">
                                <label for="status" class="form-label">Статус</label>
                                <select class="form-select" id="status" name="status">
                                    <option value="">Все статусы</option>
                                    <option value="new" <?php echo $statusFilter === 'new' ? 'selected' : ''; ?>>Новые</option>
                                    <option value="assigned" <?php echo $statusFilter === 'assigned' ? 'selected' : ''; ?>>Назначены</option>
                                    <option value="in_progress" <?php echo $statusFilter === 'in_progress' ? 'selected' : ''; ?>>В работе</option>
                                    <option value="completed" <?php echo $statusFilter === 'completed' ? 'selected' : ''; ?>>Завершены</option>
                                    <option value="cancelled" <?php echo $statusFilter === 'cancelled' ? 'selected' : ''; ?>>Отменены</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="payment_status" class="form-label">Статус оплаты</label>
                                <select class="form-select" id="payment_status" name="payment_status">
                                    <option value="">Все</option>
                                    <option value="pending" <?php echo $paymentStatusFilter === 'pending' ? 'selected' : ''; ?>>Ожидает</option>
                                    <option value="paid" <?php echo $paymentStatusFilter === 'paid' ? 'selected' : ''; ?>>Оплачена</option>
                                    <option value="refunded" <?php echo $paymentStatusFilter === 'refunded' ? 'selected' : ''; ?>>Возврат</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="search" class="form-label">Поиск</label>
                                <input type="text" class="form-control" id="search" name="search" 
                                       value="<?php echo htmlspecialchars($searchFilter); ?>" 
                                       placeholder="Номер, клиент, телефон">
                            </div>
                            <div class="col-md-3">
                                <label for="date_from" class="form-label">Дата с</label>
                                <input type="date" class="form-control" id="date_from" name="date_from" 
                                       value="<?php echo htmlspecialchars($dateFrom); ?>">
                            </div>
                            <div class="col-md-3">
                                <label for="date_to" class="form-label">Дата по</label>
                                <input type="date" class="form-control" id="date_to" name="date_to" 
                                       value="<?php echo htmlspecialchars($dateTo); ?>">
                            </div>
                            <div class="col-md-3">
                                <label for="sort_by" class="form-label">Сортировка</label>
                                <select class="form-select" id="sort_by" name="sort_by">
                                    <option value="trip_date" <?php echo $sortBy === 'trip_date' ? 'selected' : ''; ?>>По дате</option>
                                    <option value="status" <?php echo $sortBy === 'status' ? 'selected' : ''; ?>>По статусу</option>
                                    <option value="order_amount" <?php echo $sortBy === 'order_amount' ? 'selected' : ''; ?>>По сумме</option>
                                    <option value="created_at" <?php echo $sortBy === 'created_at' ? 'selected' : ''; ?>>По созданию</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="sort_order" class="form-label">Порядок</label>
                                <select class="form-select" id="sort_order" name="sort_order">
                                    <option value="DESC" <?php echo $sortOrder === 'DESC' ? 'selected' : ''; ?>>По убыванию</option>
                                    <option value="ASC" <?php echo $sortOrder === 'ASC' ? 'selected' : ''; ?>>По возрастанию</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">&nbsp;</label>
                                <div class="d-grid">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-filter"></i> Применить
                                    </button>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">&nbsp;</label>
                                <div class="d-grid">
                                    <a href="applications.php" class="btn btn-outline-secondary">
                                        <i class="fas fa-times"></i> Очистить
                                    </a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Таблица заказов -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            Заказы 
                            <span class="badge bg-secondary"><?php echo $totalRecords; ?></span>
                        </h5>
                    </div>
                    <div class="card-body p-0">
                        <?php if (empty($applications)): ?>
                            <div class="text-center p-4">
                                <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                <p class="text-muted">Заказы не найдены</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Номер</th>
                                            <th>Клиент</th>
                                            <th>Маршрут</th>
                                            <th>Дата/Время</th>
                                            <th>Водитель</th>
                                            <th>Автомобиль</th>
                                            <th>Статус</th>
                                            <th>Сумма</th>
                                            <th>Действия</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($applications as $app): ?>
                                            <tr class="table-row">
                                                <td>
                                                    <strong><?php echo htmlspecialchars($app['application_number']); ?></strong>
                                                    <br>
                                                    <small class="text-muted">
                                                        <?php echo date('d.m.Y H:i', strtotime($app['created_at'])); ?>
                                                    </small>
                                                </td>
                                                <td>
                                                    <div>
                                                        <strong><?php echo htmlspecialchars($app['customer_name']); ?></strong>
                                                        <br>
                                                        <small class="text-muted"><?php echo htmlspecialchars($app['customer_phone']); ?></small>
                                                    </div>
                                                </td>
                                                <td>
                                                    <?php if (!empty($app['routes'])): ?>
                                                        <div>
                                                            <small>
                                                                <strong>От:</strong> <?php echo htmlspecialchars(substr($app['routes'][0]['address'], 0, 50)) . '...'; ?>
                                                                <br>
                                                                <?php if (count($app['routes']) > 1): ?>
                                                                    <strong>До:</strong> <?php echo htmlspecialchars(substr($app['routes'][count($app['routes'])-1]['address'], 0, 50)) . '...'; ?>
                                                                <?php endif; ?>
                                                            </small>
                                                        </div>
                                                    <?php else: ?>
                                                        <span class="text-muted">Маршрут не указан</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php echo date('d.m.Y', strtotime($app['trip_date'])); ?>
                                                    <br>
                                                    <small class="text-muted"><?php echo date('H:i', strtotime($app['trip_date'])); ?></small>
                                                </td>
                                                <td>
                                                    <?php if ($app['driver_name']): ?>
                                                        <div>
                                                            <strong><?php echo htmlspecialchars($app['driver_name']); ?></strong>
                                                            <br>
                                                            <small class="text-muted"><?php echo htmlspecialchars($app['driver_phone'] ?? ''); ?></small>
                                                        </div>
                                                    <?php else: ?>
                                                        <span class="text-muted">Не назначен</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if ($app['vehicle_name']): ?>
                                                        <div>
                                                            <strong><?php echo htmlspecialchars($app['vehicle_name']); ?></strong>
                                                            <br>
                                                            <small class="text-muted"><?php echo htmlspecialchars($app['vehicle_plate']); ?></small>
                                                        </div>
                                                    <?php else: ?>
                                                        <span class="text-muted">Не назначен</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <span class="badge <?php echo getStatusBadgeClass($app['status']); ?> status-badge">
                                                        <?php echo getStatusName($app['status']); ?>
                                                    </span>
                                                    <br>
                                                    <small class="text-muted">
                                                        <?php echo getPaymentStatusName($app['payment_status']); ?>
                                                    </small>
                                                </td>
                                                <td>
                                                    <strong><?php echo number_format($app['order_amount'], 0, '.', ' '); ?> ₽</strong>
                                                </td>
                                                <td>
                                                    <div class="quick-actions">
                                                        <div class="btn-group btn-group-sm">
                                                            <a href="edit-application.php?id=<?php echo $app['id']; ?>" 
                                                               class="btn btn-outline-primary" title="Редактировать">
                                                                <i class="fas fa-edit"></i>
                                                            </a>
                                                            <?php if (in_array($_SESSION['user_role'], ['admin', 'dispatcher', 'manager'])): ?>
                                                                <button type="button" class="btn btn-outline-info" 
                                                                        onclick="quickAction('assignDriver', <?php echo $app['id']; ?>)" 
                                                                        title="Назначить водителя">
                                                                    <i class="fas fa-user-plus"></i>
                                                                </button>
                                                                <button type="button" class="btn btn-outline-success" 
                                                                        onclick="quickAction('assignVehicle', <?php echo $app['id']; ?>)" 
                                                                        title="Назначить автомобиль">
                                                                    <i class="fas fa-car"></i>
                                                                </button>
                                                                <div class="btn-group">
                                                                    <button type="button" class="btn btn-outline-warning dropdown-toggle" 
                                                                            data-bs-toggle="dropdown" title="Изменить статус">
                                                                        <i class="fas fa-cog"></i>
                                                                    </button>
                                                                    <ul class="dropdown-menu">
                                                                        <li><a class="dropdown-item" href="#" onclick="changeStatus(<?php echo $app['id']; ?>, 'assigned')">Назначена</a></li>
                                                                        <li><a class="dropdown-item" href="#" onclick="changeStatus(<?php echo $app['id']; ?>, 'in_progress')">В работе</a></li>
                                                                        <li><a class="dropdown-item" href="#" onclick="changeStatus(<?php echo $app['id']; ?>, 'completed')">Завершена</a></li>
                                                                        <li><hr class="dropdown-divider"></li>
                                                                        <li><a class="dropdown-item text-danger" href="#" onclick="changeStatus(<?php echo $app['id']; ?>, 'cancelled')">Отменена</a></li>
                                                                    </ul>
                                                                </div>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Пагинация -->
                <?php if ($totalPages > 1): ?>
                    <nav aria-label="Навигация по страницам" class="mt-4">
                        <ul class="pagination justify-content-center">
                            <?php if ($currentPage > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="<?php echo buildQueryUrl(array_merge($_GET, ['page' => $currentPage - 1])); ?>">
                                        <i class="fas fa-chevron-left"></i>
                                    </a>
                                </li>
                            <?php endif; ?>
                            
                            <?php
                            $startPage = max(1, $currentPage - 2);
                            $endPage = min($totalPages, $currentPage + 2);
                            
                            for ($i = $startPage; $i <= $endPage; $i++):
                            ?>
                                <li class="page-item <?php echo $i === $currentPage ? 'active' : ''; ?>">
                                    <a class="page-link" href="<?php echo buildQueryUrl(array_merge($_GET, ['page' => $i])); ?>">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                            <?php endfor; ?>
                            
                            <?php if ($currentPage < $totalPages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="<?php echo buildQueryUrl(array_merge($_GET, ['page' => $currentPage + 1])); ?>">
                                        <i class="fas fa-chevron-right"></i>
                                    </a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                <?php endif; ?>
            </main>
        </div>
    </div>

    <!-- Модальное окно создания заказа -->
    <div class="modal fade" id="createApplicationModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-plus"></i> Создание нового заказа
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="createApplicationForm">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="customer_name" class="form-label">Имя клиента *</label>
                                <input type="text" class="form-control" id="customer_name" name="customer_name" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="customer_phone" class="form-label">Телефон клиента *</label>
                                <input type="text" class="form-control" id="customer_phone" name="customer_phone" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="trip_date" class="form-label">Дата и время поездки *</label>
                                <input type="datetime-local" class="form-control" id="trip_date" name="trip_date" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="order_amount" class="form-label">Сумма заказа</label>
                                <input type="number" class="form-control" id="order_amount" name="order_amount" step="0.01">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="service_type" class="form-label">Тип услуги</label>
                                <select class="form-select" id="service_type" name="service_type">
                                    <option value="transfer">Трансфер</option>
                                    <option value="airport_arrival">Аэропорт (прилет)</option>
                                    <option value="airport_departure">Аэропорт (вылет)</option>
                                    <option value="city_transfer">Городской трансфер</option>
                                    <option value="train_station">Вокзал</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="tariff" class="form-label">Тариф</label>
                                <select class="form-select" id="tariff" name="tariff">
                                    <option value="standard">Стандарт</option>
                                    <option value="comfort">Комфорт</option>
                                    <option value="business">Бизнес</option>
                                    <option value="premium">Премиум</option>
                                    <option value="minivan5">Минивэн (5 мест)</option>
                                </select>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="route_from" class="form-label">Откуда</label>
                            <input type="text" class="form-control" id="route_from" name="route_from" placeholder="Адрес или место посадки">
                        </div>
                        <div class="mb-3">
                            <label for="route_to" class="form-label">Куда</label>
                            <input type="text" class="form-control" id="route_to" name="route_to" placeholder="Адрес или место назначения">
                        </div>
                        <div class="mb-3">
                            <label for="notes" class="form-label">Примечания</label>
                            <textarea class="form-control" id="notes" name="notes" rows="3" placeholder="Дополнительная информация"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Создать заказ
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Создание нового заказа
        document.getElementById('createApplicationForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const data = {
                customer_name: formData.get('customer_name'),
                customer_phone: formData.get('customer_phone'),
                trip_date: formData.get('trip_date'),
                order_amount: formData.get('order_amount'),
                service_type: formData.get('service_type'),
                tariff: formData.get('tariff'),
                routes: [
                    formData.get('route_from'),
                    formData.get('route_to')
                ].filter(route => route.trim() !== ''),
                notes: formData.get('notes')
            };
            
            fetch('api/applications.php?action=create', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    alert('Заказ успешно создан!');
                    location.reload();
                } else {
                    alert('Ошибка: ' + result.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Произошла ошибка при создании заказа');
            });
        });

        // Быстрые действия
        function quickAction(action, applicationId) {
            if (action === 'assignDriver') {
                const driverId = prompt('Введите ID водителя:');
                if (driverId) {
                    changeDriver(applicationId, driverId);
                }
            } else if (action === 'assignVehicle') {
                const vehicleId = prompt('Введите ID автомобиля:');
                if (vehicleId) {
                    changeVehicle(applicationId, vehicleId);
                }
            }
        }

        function changeDriver(applicationId, driverId) {
            fetch('api/applications.php?action=assignDriver', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    application_id: applicationId,
                    driver_id: driverId
                })
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    alert('Водитель успешно назначен!');
                    location.reload();
                } else {
                    alert('Ошибка: ' + result.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Произошла ошибка при назначении водителя');
            });
        }

        function changeVehicle(applicationId, vehicleId) {
            fetch('api/applications.php?action=assignVehicle', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    application_id: applicationId,
                    vehicle_id: vehicleId
                })
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    alert('Автомобиль успешно назначен!');
                    location.reload();
                } else {
                    alert('Ошибка: ' + result.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Произошла ошибка при назначении автомобиля');
            });
        }

        function changeStatus(applicationId, status) {
            if (!confirm('Вы уверены, что хотите изменить статус заказа?')) {
                return;
            }

            fetch('api/applications.php?action=updateStatus', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    application_id: applicationId,
                    status: status
                })
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    alert('Статус заказа успешно изменен!');
                    location.reload();
                } else {
                    alert('Ошибка: ' + result.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Произошла ошибка при изменении статуса');
            });
        }
    </script>
</body>
</html>