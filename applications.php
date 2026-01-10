<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/includes/ACL.php';
requireLogin();

$user_role = $_SESSION['user_role'];

if (!ACL::canViewApplications($user_role)) {
    http_response_code(403);
    die('Доступ запрещен');
}

$page_title = "Заказы";

// Additional assets
$additional_css = '<link rel="stylesheet" href="/css/modals.css">';
$additional_scripts = '
<script src="/js/modals.js"></script>
<script src="/js/applications-manager.js"></script>
';

include __DIR__ . '/templates/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar logic is usually in templates/sidebar.php, but let's see -->
        <?php include __DIR__ . '/templates/sidebar.php'; ?>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Управление заказами</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <?php if (ACL::canCreateApplication($user_role)): ?>
                    <button type="button" class="btn btn-sm btn-primary" onclick="Modals.openApplicationCreate()">
                        <i class="fas fa-plus"></i> Создать заказ
                    </button>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Filters -->
            <div class="card mb-4">
                <div class="card-body">
                    <form id="filterForm" class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">Статус</label>
                            <select name="status" class="form-select form-select-sm">
                                <option value="">Все статусы</option>
                                <option value="new">Не обработана</option>
                                <option value="confirmed">Принята</option>
                                <option value="inwork">В работе</option>
                                <option value="completed">Выполнена</option>
                                <option value="cancelled">Отменена</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Дата</label>
                            <input type="date" name="date" class="form-select form-select-sm">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Поиск</label>
                            <input type="text" name="search" class="form-control form-control-sm" placeholder="Номер, имя, телефон...">
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button type="button" class="btn btn-sm btn-secondary w-100" onclick="ApplicationsManager.loadApplications(Object.fromEntries(new FormData(document.getElementById('filterForm'))))">
                                <i class="fas fa-filter"></i> Фильтр
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Applications Table -->
            <div class="table-responsive">
                <table class="table table-striped table-hover table-sm" id="applicationsTable">
                    <thead>
                        <tr>
                            <th>Номер</th>
                            <th>Статус</th>
                            <th>Заказчик</th>
                            <th>Маршрут</th>
                            <th>Дата/Время</th>
                            <th>Водитель</th>
                            <th>Машина</th>
                            <th>Стоимость</th>
                            <th>Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Loaded via AJAX -->
                    </tbody>
                </table>
            </div>
        </main>
    </div>
</div>

<?php 
include __DIR__ . '/templates/modals.php';
include __DIR__ . '/templates/footer.php'; 
?>
