<?php
/**
 * Страница отчётов
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';
requireLogin();

$page_title = 'Отчёты';

// Проверяем права доступа
if (!in_array($_SESSION['user_role'], ['admin', 'manager', 'dispatcher'])) {
    header("Location: index.php");
    exit();
}

// Обработка экспорта в CSV
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    exportToCSV();
    exit();
}

function exportToCSV() {
    global $pdo;
    
    $reportType = $_GET['report_type'] ?? 'applications';
    $dateFrom = $_GET['date_from'] ?? date('Y-m-01');
    $dateTo = $_GET['date_to'] ?? date('Y-m-t');
    
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="report_' . $reportType . '_' . date('Y-m-d') . '.csv"');
    
    $output = fopen('php://output', 'w');
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM
    
    switch ($reportType) {
        case 'applications':
            fputcsv($output, ['Номер', 'Дата', 'Клиент', 'Статус', 'Сумма', 'Водитель']);
            $stmt = $pdo->prepare("
                SELECT a.application_number, a.trip_date, a.customer_name, a.status, 
                       a.order_amount, CONCAT(d.first_name, ' ', d.last_name) as driver_name
                FROM applications a
                LEFT JOIN drivers d ON a.driver_id = d.id
                WHERE DATE(a.trip_date) BETWEEN ? AND ?
                ORDER BY a.trip_date DESC
            ");
            $stmt->execute([$dateFrom, $dateTo]);
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                fputcsv($output, $row);
            }
            break;
            
        case 'drivers':
            fputcsv($output, ['ID', 'Имя', 'Телефон', 'Статус', 'Заказов', 'Доход']);
            $stmt = $pdo->query("
                SELECT d.id, CONCAT(d.first_name, ' ', d.last_name) as name, d.phone, d.status,
                       COUNT(a.id) as orders_count,
                       COALESCE(SUM(a.order_amount), 0) as total_income
                FROM drivers d
                LEFT JOIN applications a ON d.id = a.driver_id
                GROUP BY d.id
            ");
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                fputcsv($output, $row);
            }
            break;
            
        case 'payments':
            fputcsv($output, ['ID', 'Заявка', 'Сумма', 'Статус', 'Метод', 'Дата']);
            $stmt = $pdo->prepare("
                SELECT p.id, a.application_number, p.amount, p.status, p.method, p.created_at
                FROM payments p
                LEFT JOIN applications a ON p.application_id = a.id
                WHERE DATE(p.created_at) BETWEEN ? AND ?
                ORDER BY p.created_at DESC
            ");
            $stmt->execute([$dateFrom, $dateTo]);
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                fputcsv($output, $row);
            }
            break;
    }
    
    fclose($output);
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - CRM.PROFTRANSFER</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="/css/style.css" rel="stylesheet">
</head>
<body>
    <?php include __DIR__ . '/templates/header.php'; ?>
    
    <div class="container-fluid">
        <div class="row">
            <?php include __DIR__ . '/templates/sidebar.php'; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2"><i class="fas fa-file-alt me-2"></i><?php echo $page_title; ?></h1>
                </div>
                
                <!-- Фильтры отчётов -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-filter me-2"></i>Параметры отчёта</h5>
                    </div>
                    <div class="card-body">
                        <form method="get" action="">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label for="report_type" class="form-label">Тип отчёта</label>
                                    <select class="form-select" id="report_type" name="report_type">
                                        <option value="applications">По заявкам</option>
                                        <option value="drivers">По водителям</option>
                                        <option value="vehicles">По автомобилям</option>
                                        <option value="payments">По платежам</option>
                                        <option value="revenue">По доходам</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label for="date_from" class="form-label">Дата с</label>
                                    <input type="date" class="form-control" id="date_from" name="date_from" 
                                           value="<?php echo date('Y-m-01'); ?>">
                                </div>
                                <div class="col-md-3">
                                    <label for="date_to" class="form-label">Дата по</label>
                                    <input type="date" class="form-control" id="date_to" name="date_to" 
                                           value="<?php echo date('Y-m-t'); ?>">
                                </div>
                                <div class="col-md-2 d-flex align-items-end">
                                    <button type="submit" class="btn btn-primary w-100">
                                        <i class="fas fa-search me-1"></i>Показать
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Результаты отчёта -->
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="fas fa-chart-bar me-2"></i>Результаты</h5>
                        <div>
                            <button class="btn btn-success btn-sm" onclick="exportReport('csv')">
                                <i class="fas fa-file-csv me-1"></i>Экспорт CSV
                            </button>
                            <button class="btn btn-danger btn-sm" onclick="exportReport('pdf')">
                                <i class="fas fa-file-pdf me-1"></i>Экспорт PDF
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div id="report-content">
                            <?php
                            $reportType = $_GET['report_type'] ?? 'applications';
                            $dateFrom = $_GET['date_from'] ?? date('Y-m-01');
                            $dateTo = $_GET['date_to'] ?? date('Y-m-t');
                            
                            switch ($reportType) {
                                case 'applications':
                                    renderApplicationsReport($pdo, $dateFrom, $dateTo);
                                    break;
                                case 'drivers':
                                    renderDriversReport($pdo);
                                    break;
                                case 'payments':
                                    renderPaymentsReport($pdo, $dateFrom, $dateTo);
                                    break;
                                default:
                                    echo '<p class="text-muted">Выберите тип отчёта и нажмите "Показать"</p>';
                            }
                            ?>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function exportReport(format) {
            const reportType = document.getElementById('report_type').value;
            const dateFrom = document.getElementById('date_from').value;
            const dateTo = document.getElementById('date_to').value;
            window.location.href = `reports.php?export=${format}&report_type=${reportType}&date_from=${dateFrom}&date_to=${dateTo}`;
        }
    </script>
</body>
</html>

<?php
function renderApplicationsReport($pdo, $dateFrom, $dateTo) {
    $stmt = $pdo->prepare("
        SELECT a.application_number, a.trip_date, a.customer_name, a.status, 
               a.order_amount, CONCAT(d.first_name, ' ', d.last_name) as driver_name,
               v.brand, v.model
        FROM applications a
        LEFT JOIN drivers d ON a.driver_id = d.id
        LEFT JOIN vehicles v ON a.vehicle_id = v.id
        WHERE DATE(a.trip_date) BETWEEN ? AND ?
        ORDER BY a.trip_date DESC
    ");
    $stmt->execute([$dateFrom, $dateTo]);
    $applications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $totalAmount = array_sum(array_column($applications, 'order_amount'));
    
    echo '<div class="table-responsive">';
    echo '<table class="table table-striped">';
    echo '<thead><tr><th>Номер</th><th>Дата</th><th>Клиент</th><th>Статус</th><th>Сумма</th><th>Водитель</th><th>Авто</th></tr></thead>';
    echo '<tbody>';
    foreach ($applications as $app) {
        echo '<tr>';
        echo '<td>' . htmlspecialchars($app['application_number']) . '</td>';
        echo '<td>' . date('d.m.Y H:i', strtotime($app['trip_date'])) . '</td>';
        echo '<td>' . htmlspecialchars($app['customer_name']) . '</td>';
        echo '<td>' . htmlspecialchars($app['status']) . '</td>';
        echo '<td>' . number_format($app['order_amount'], 2) . ' ₽</td>';
        echo '<td>' . htmlspecialchars($app['driver_name'] ?? '-') . '</td>';
        echo '<td>' . htmlspecialchars(($app['brand'] ?? '') . ' ' . ($app['model'] ?? '-')) . '</td>';
        echo '</tr>';
    }
    echo '</tbody>';
    echo '<tfoot><tr><th colspan="4">Итого:</th><th>' . number_format($totalAmount, 2) . ' ₽</th><th colspan="2"></th></tr></tfoot>';
    echo '</table>';
    echo '</div>';
    echo '<p class="mt-3"><strong>Всего заказов:</strong> ' . count($applications) . '</p>';
}

function renderDriversReport($pdo) {
    $stmt = $pdo->query("
        SELECT d.id, CONCAT(d.first_name, ' ', d.last_name) as name, d.phone, d.status,
               COUNT(a.id) as orders_count,
               COALESCE(SUM(CASE WHEN a.status = 'completed' THEN a.order_amount ELSE 0 END), 0) as total_income
        FROM drivers d
        LEFT JOIN applications a ON d.id = a.driver_id
        GROUP BY d.id
        ORDER BY total_income DESC
    ");
    $drivers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo '<div class="table-responsive">';
    echo '<table class="table table-striped">';
    echo '<thead><tr><th>ID</th><th>Имя</th><th>Телефон</th><th>Статус</th><th>Заказов</th><th>Доход</th></tr></thead>';
    echo '<tbody>';
    foreach ($drivers as $driver) {
        echo '<tr>';
        echo '<td>' . $driver['id'] . '</td>';
        echo '<td>' . htmlspecialchars($driver['name']) . '</td>';
        echo '<td>' . htmlspecialchars($driver['phone']) . '</td>';
        echo '<td>' . htmlspecialchars($driver['status']) . '</td>';
        echo '<td>' . $driver['orders_count'] . '</td>';
        echo '<td>' . number_format($driver['total_income'], 2) . ' ₽</td>';
        echo '</tr>';
    }
    echo '</tbody>';
    echo '</table>';
    echo '</div>';
}

function renderPaymentsReport($pdo, $dateFrom, $dateTo) {
    $stmt = $pdo->prepare("
        SELECT p.id, a.application_number, p.amount, p.status, p.method, p.created_at
        FROM payments p
        LEFT JOIN applications a ON p.application_id = a.id
        WHERE DATE(p.created_at) BETWEEN ? AND ?
        ORDER BY p.created_at DESC
    ");
    $stmt->execute([$dateFrom, $dateTo]);
    $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $totalAmount = array_sum(array_column($payments, 'amount'));
    
    echo '<div class="table-responsive">';
    echo '<table class="table table-striped">';
    echo '<thead><tr><th>ID</th><th>Заявка</th><th>Сумма</th><th>Статус</th><th>Метод</th><th>Дата</th></tr></thead>';
    echo '<tbody>';
    foreach ($payments as $payment) {
        echo '<tr>';
        echo '<td>' . $payment['id'] . '</td>';
        echo '<td>' . htmlspecialchars($payment['application_number']) . '</td>';
        echo '<td>' . number_format($payment['amount'], 2) . ' ₽</td>';
        echo '<td>' . htmlspecialchars($payment['status']) . '</td>';
        echo '<td>' . htmlspecialchars($payment['method']) . '</td>';
        echo '<td>' . date('d.m.Y H:i', strtotime($payment['created_at'])) . '</td>';
        echo '</tr>';
    }
    echo '</tbody>';
    echo '<tfoot><tr><th colspan="2">Итого:</th><th>' . number_format($totalAmount, 2) . ' ₽</th><th colspan="3"></th></tr></tfoot>';
    echo '</table>';
    echo '</div>';
}
?>
