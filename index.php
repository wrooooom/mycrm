<?php
require_once 'config.php';
require_once 'auth.php';
require_once 'includes/ACL.php';
requireLogin();

$user_role = $_SESSION['user_role'];
$user_id = $_SESSION['user_id'];

// Check if user has access to desktop (everyone logged in has for now, but let's be consistent)
if (!isLoggedIn()) {
    http_response_code(403);
    die('Доступ запрещен');
}

$page_title = "Рабочий стол";

// Additional assets
$additional_css = '<link rel="stylesheet" href="/css/modals.css">';
$additional_scripts = '
<script src="/js/modals.js"></script>
<script src="/js/applications-manager.js"></script>
';

// Fetch KPI data
try {
    $today = date('Y-m-d');
    $stats = [
        'total_today' => $pdo->query("SELECT COUNT(*) FROM applications WHERE DATE(trip_date) = '$today'")->fetchColumn(),
        'new' => $pdo->query("SELECT COUNT(*) FROM applications WHERE status = 'new'")->fetchColumn(),
        'inwork' => $pdo->query("SELECT COUNT(*) FROM applications WHERE status = 'inwork'")->fetchColumn(),
        'completed' => $pdo->query("SELECT COUNT(*) FROM applications WHERE status = 'completed'")->fetchColumn(),
        'cancelled' => $pdo->query("SELECT COUNT(*) FROM applications WHERE status = 'cancelled'")->fetchColumn(),
        'avg_cost' => $pdo->query("SELECT AVG(order_amount) FROM applications")->fetchColumn() ?: 0,
        'avg_margin' => $pdo->query("SELECT AVG(order_amount - executor_amount) FROM applications")->fetchColumn() ?: 0,
        'active_drivers' => $pdo->query("SELECT COUNT(*) FROM drivers WHERE status = 'work'")->fetchColumn(),
    ];
} catch (Exception $e) {
    $stats = array_fill_keys(['total_today', 'new', 'inwork', 'completed', 'cancelled', 'avg_cost', 'avg_margin', 'active_drivers'], 0);
}

// Fetch Activity Log for Right Sidebar
$activity_log = [];
if (ACL::canViewActivityLog($user_role)) {
    try {
        $activity_log = $pdo->query("SELECT al.*, u.name as user_name FROM activity_log al LEFT JOIN users u ON al.user_id = u.id ORDER BY al.created_at DESC LIMIT 20")->fetchAll();
    } catch (Exception $e) {}
}

include 'templates/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include 'templates/sidebar.php'; ?>

        <main class="col-md-9 ms-sm-auto col-lg-8 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Рабочий стол</h1>
            </div>

            <!-- KPI Cards -->
            <div class="row row-cols-1 row-cols-md-4 g-2 mb-4">
                <div class="col">
                    <div class="card bg-primary text-white h-100">
                        <div class="card-body py-2">
                            <h6 class="card-title mb-0 small">Заказов сегодня</h6>
                            <h4 class="mb-0"><?php echo $stats['total_today']; ?></h4>
                        </div>
                    </div>
                </div>
                <div class="col">
                    <div class="card bg-success text-white h-100">
                        <div class="card-body py-2">
                            <h6 class="card-title mb-0 small">Новых</h6>
                            <h4 class="mb-0"><?php echo $stats['new']; ?></h4>
                        </div>
                    </div>
                </div>
                <div class="col">
                    <div class="card bg-info text-white h-100">
                        <div class="card-body py-2">
                            <h6 class="card-title mb-0 small">В процессе</h6>
                            <h4 class="mb-0"><?php echo $stats['inwork']; ?></h4>
                        </div>
                    </div>
                </div>
                <div class="col">
                    <div class="card bg-secondary text-white h-100">
                        <div class="card-body py-2">
                            <h6 class="card-title mb-0 small">Завершено</h6>
                            <h4 class="mb-0"><?php echo $stats['completed']; ?></h4>
                        </div>
                    </div>
                </div>
                <div class="col">
                    <div class="card bg-danger text-white h-100">
                        <div class="card-body py-2">
                            <h6 class="card-title mb-0 small">Отменено</h6>
                            <h4 class="mb-0"><?php echo $stats['cancelled']; ?></h4>
                        </div>
                    </div>
                </div>
                <div class="col">
                    <div class="card bg-light h-100">
                        <div class="card-body py-2">
                            <h6 class="card-title mb-0 small">Ср. стоимость</h6>
                            <h4 class="mb-0"><?php echo number_format($stats['avg_cost'], 0, '.', ' '); ?></h4>
                        </div>
                    </div>
                </div>
                <div class="col">
                    <div class="card bg-light h-100">
                        <div class="card-body py-2">
                            <h6 class="card-title mb-0 small">Ср. маржа</h6>
                            <h4 class="mb-0"><?php echo number_format($stats['avg_margin'], 0, '.', ' '); ?></h4>
                        </div>
                    </div>
                </div>
                <div class="col">
                    <div class="card bg-warning text-dark h-100">
                        <div class="card-body py-2">
                            <h6 class="card-title mb-0 small">Активных водителей</h6>
                            <h4 class="mb-0"><?php echo $stats['active_drivers']; ?></h4>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="row g-2 mb-4">
                <div class="col">
                    <button class="btn btn-outline-primary w-100 py-3" onclick="Modals.openApplicationCreate()">
                        <i class="fas fa-plus d-block mb-1"></i> Создать заказ
                    </button>
                </div>
                <?php if (ACL::canManageDrivers($user_role)): ?>
                <div class="col">
                    <a href="add-driver.php" class="btn btn-outline-success w-100 py-3">
                        <i class="fas fa-user-plus d-block mb-1"></i> Добавить водителя
                    </a>
                </div>
                <?php endif; ?>
                <?php if (ACL::canManageVehicles($user_role)): ?>
                <div class="col">
                    <a href="add-vehicle.php" class="btn btn-outline-info w-100 py-3">
                        <i class="fas fa-car d-block mb-1"></i> Добавить машину
                    </a>
                </div>
                <?php endif; ?>
                <div class="col">
                    <a href="analytics.php" class="btn btn-outline-secondary w-100 py-3">
                        <i class="fas fa-chart-line d-block mb-1"></i> Посмотреть отчёт
                    </a>
                </div>
            </div>

            <!-- Recent Applications or other main content -->
            <div class="card">
                <div class="card-header">Ближайшие поездки</div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm table-hover mb-0" id="applicationsTable">
                            <thead>
                                <tr>
                                    <th>Номер</th>
                                    <th>Статус</th>
                                    <th>Заказчик</th>
                                    <th>Маршрут</th>
                                    <th>Дата/Время</th>
                                    <th>Действия</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Loaded via AJAX -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>

        <!-- Right Sidebar: Activity Log -->
        <?php if (ACL::canViewActivityLog($user_role)): ?>
        <aside class="col-md-3 col-lg-2 bg-light border-start overflow-auto" style="height: calc(100vh - 48px);">
            <div class="p-3">
                <h6 class="border-bottom pb-2 mb-3">История действий</h6>
                <div class="activity-log-list">
                    <?php foreach ($activity_log as $log): ?>
                    <div class="mb-3 border-bottom pb-2 small">
                        <div class="d-flex justify-content-between">
                            <span class="fw-bold"><?php echo htmlspecialchars($log['user_name'] ?: 'Система'); ?></span>
                            <span class="text-muted"><?php echo date('H:i', strtotime($log['created_at'])); ?></span>
                        </div>
                        <div class="text-wrap"><?php echo htmlspecialchars($log['action']); ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </aside>
        <?php endif; ?>
    </div>
</div>

<?php 
include 'templates/modals.php';
include 'templates/footer.php'; 
?>
