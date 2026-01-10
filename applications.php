<?php
// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include required files
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/includes/ACL.php';

// Require login
requireLogin();

// Log page view
logAction('view_applications_page', $_SESSION['user_id'] ?? null);

// Get current user data
$currentUser = getUserData();
$role = $currentUser['role'] ?? 'guest';

// Get statistics based on user role
$stats = [
    'total' => 0,
    'new' => 0,
    'confirmed' => 0,
    'inwork' => 0,
    'completed' => 0,
    'cancelled' => 0,
    'today' => 0
];

try {
    require_once __DIR__ . '/includes/db.php';

    // Get ACL filter for applications
    $aclFilter = ACL::getAccessibleApplications($currentUser['id'], $role, $pdo);

    if (ACL::canViewAllApplications($role)) {
        // Admin/Manager/Dispatcher see all
        $stats['total'] = $pdo->query("SELECT COUNT(*) FROM applications")->fetchColumn();
        $stats['new'] = $pdo->query("SELECT COUNT(*) FROM applications WHERE status = 'new'")->fetchColumn();
        $stats['confirmed'] = $pdo->query("SELECT COUNT(*) FROM applications WHERE status = 'confirmed'")->fetchColumn();
        $stats['inwork'] = $pdo->query("SELECT COUNT(*) FROM applications WHERE status = 'inwork'")->fetchColumn();
        $stats['completed'] = $pdo->query("SELECT COUNT(*) FROM applications WHERE status = 'completed'")->fetchColumn();
        $stats['cancelled'] = $pdo->query("SELECT COUNT(*) FROM applications WHERE status = 'cancelled'")->fetchColumn();
        $stats['today'] = $pdo->query("SELECT COUNT(*) FROM applications WHERE DATE(created_at) = CURDATE()")->fetchColumn();
    } else {
        // Driver/Client see only their applications
        $whereClause = $aclFilter ? " WHERE " . str_replace(':user_id', $currentUser['id'], $aclFilter) : '';
        $stats['total'] = $pdo->query("SELECT COUNT(*) FROM applications$whereClause")->fetchColumn();
        $stats['new'] = $pdo->query("SELECT COUNT(*) FROM applications$whereClause AND status = 'new'")->fetchColumn();
        $stats['confirmed'] = $pdo->query("SELECT COUNT(*) FROM applications$whereClause AND status = 'confirmed'")->fetchColumn();
        $stats['inwork'] = $pdo->query("SELECT COUNT(*) FROM applications$whereClause AND status = 'inwork'")->fetchColumn();
        $stats['completed'] = $pdo->query("SELECT COUNT(*) FROM applications$whereClause AND status = 'completed'")->fetchColumn();
        $stats['cancelled'] = $pdo->query("SELECT COUNT(*) FROM applications$whereClause AND status = 'cancelled'")->fetchColumn();
        $stats['today'] = $pdo->query("SELECT COUNT(*) FROM applications$whereClause AND DATE(created_at) = CURDATE()")->fetchColumn();
    }
} catch(Exception $e) {
    error_log("Stats error: " . $e->getMessage());
}

// Page settings
$page_title = "Заказы";
$additional_css = '<link rel="stylesheet" href="/css/modals.css">';
$additional_js = '<script src="/js/modals.js"></script><script src="/js/applications-manager.js"></script>';

// Include header
include __DIR__ . '/templates/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-md-2 sidebar">
            <?php include __DIR__ . '/templates/sidebar.php'; ?>
        </div>

        <!-- Main Content -->
        <div class="col-md-10 main-content">
            <!-- Page Header -->
            <div class="page-header">
                <div class="page-title">
                    <h2>📋 Заказы</h2>
                    <p>Управление заказами и заявками</p>
                </div>
                <?php if (ACL::canCreateApplication($role)): ?>
                    <button type="button" class="btn btn-primary" data-action="create-application">
                        <i class="fas fa-plus"></i> Создать заказ
                    </button>
                <?php endif; ?>
            </div>

            <!-- Statistics Cards -->
            <div class="stats-row">
                <div class="stat-card">
                    <div class="stat-icon stat-icon-primary">
                        <i class="fas fa-list"></i>
                    </div>
                    <div class="stat-info">
                        <div class="stat-label">Всего заказов</div>
                        <div class="stat-value"><?php echo $stats['total']; ?></div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon stat-icon-success">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="stat-info">
                        <div class="stat-label">Новых</div>
                        <div class="stat-value"><?php echo $stats['new']; ?></div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon stat-icon-warning">
                        <i class="fas fa-spinner"></i>
                    </div>
                    <div class="stat-info">
                        <div class="stat-label">В работе</div>
                        <div class="stat-value"><?php echo $stats['confirmed'] + $stats['inwork']; ?></div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon stat-icon-info">
                        <i class="fas fa-check"></i>
                    </div>
                    <div class="stat-info">
                        <div class="stat-label">Завершённых</div>
                        <div class="stat-value"><?php echo $stats['completed']; ?></div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon stat-icon-danger">
                        <i class="fas fa-times"></i>
                    </div>
                    <div class="stat-info">
                        <div class="stat-label">Отменённых</div>
                        <div class="stat-value"><?php echo $stats['cancelled']; ?></div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon stat-icon-secondary">
                        <i class="fas fa-calendar-day"></i>
                    </div>
                    <div class="stat-info">
                        <div class="stat-label">Сегодня</div>
                        <div class="stat-value"><?php echo $stats['today']; ?></div>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="filters-panel">
                <div class="filter-group">
                    <div class="filter-item">
                        <label><i class="fas fa-search"></i> Поиск</label>
                        <input type="text" id="searchInput" class="form-control" placeholder="Поиск по заказу...">
                    </div>
                    <div class="filter-item">
                        <label><i class="fas fa-flag"></i> Статус</label>
                        <select id="statusFilter" class="form-control">
                            <option value="">Все статусы</option>
                            <option value="new">Новая</option>
                            <option value="confirmed">Принята</option>
                            <option value="inwork">В работе</option>
                            <option value="completed">Завершена</option>
                            <option value="cancelled">Отменена</option>
                        </select>
                    </div>
                    <div class="filter-item">
                        <label><i class="fas fa-calendar"></i> Дата</label>
                        <input type="date" id="dateFilter" class="form-control">
                    </div>
                    <?php if (ACL::canViewAllApplications($role)): ?>
                    <div class="filter-item">
                        <label><i class="fas fa-user"></i> Водитель</label>
                        <select id="driverFilter" class="form-control">
                            <option value="">Все водители</option>
                        </select>
                    </div>
                    <?php endif; ?>
                    <div class="filter-item">
                        <button id="resetFilters" class="btn btn-secondary">
                            <i class="fas fa-undo"></i> Сбросить
                        </button>
                    </div>
                </div>
            </div>

            <!-- Applications Table -->
            <div class="table-panel">
                <div class="table-header">
                    <h3><i class="fas fa-table"></i> Список заказов</h3>
                </div>
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Номер</th>
                                <th>Статус</th>
                                <th>Заказчик</th>
                                <th>Маршрут</th>
                                <th>Дата/Время</th>
                                <th>Водитель</th>
                                <th>Автомобиль</th>
                                <th>Стоимость</th>
                                <th>Действия</th>
                            </tr>
                        </thead>
                        <tbody id="applicationsTableBody">
                            <tr>
                                <td colspan="10" class="text-center py-4">
                                    <div class="spinner-border" role="status">
                                        <span class="visually-hidden">Загрузка...</span>
                                    </div>
                                    <p>Загрузка заказов...</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="pagination-panel">
                    <div id="paginationInfo" class="pagination-info">Страница 1 из 1</div>
                    <div class="pagination-buttons">
                        <button id="prevPage" class="btn btn-secondary btn-sm" disabled>
                            <i class="fas fa-chevron-left"></i> Назад
                        </button>
                        <button id="nextPage" class="btn btn-secondary btn-sm" disabled>
                            Далее <i class="fas fa-chevron-right"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Create Application Modal -->
<div id="createApplicationModal" class="modal modal-xl">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">✨ Создать новый заказ</h5>
                <button type="button" class="btn-close" onclick="modalManager.closeModal('createApplicationModal')">&times;</button>
            </div>
            <div class="modal-body">
                <form id="createApplicationForm" class="modal-form">
                    <!-- Basic Information -->
                    <div class="form-section">
                        <h5 class="form-section-title">📍 Основная информация</h5>
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">Город</label>
                                <select name="city" class="form-control" required>
                                    <option value="">Выберите город</option>
                                    <option value="Москва">Москва</option>
                                    <option value="Санкт-Петербург">Санкт-Петербург</option>
                                    <option value="Казань">Казань</option>
                                    <option value="Екатеринбург">Екатеринбург</option>
                                    <option value="Новосибирск">Новосибирск</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Страна</label>
                                <select name="country" class="form-control" required>
                                    <option value="ru">Россия</option>
                                    <option value="by">Беларусь</option>
                                    <option value="other">Другое</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label required">Дата и время поездки</label>
                                <input type="datetime-local" name="trip_date" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label required">Тип услуги</label>
                                <select name="service_type" class="form-control" required>
                                    <option value="">Значение не указано</option>
                                    <option value="rent">Аренда</option>
                                    <option value="other">Иное</option>
                                    <option value="remote_area">Отдаленный район</option>
                                    <option value="transfer">Трансфер</option>
                                    <option value="airport_departure">Трансфер в аэропорт</option>
                                    <option value="city_transfer">Трансфер город</option>
                                    <option value="airport_arrival">Трансфер из аэропорта</option>
                                    <option value="train_station">Трансфер ж/д вокзал</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label required">Тип тарифа</label>
                                <select name="tariff" class="form-control" required>
                                    <option value="">Значение не указано</option>
                                    <option value="bus35">Автобус-35</option>
                                    <option value="bus44">Автобус-44</option>
                                    <option value="bus50">Автобус-50</option>
                                    <option value="business">Бизнес</option>
                                    <option value="other">Иное</option>
                                    <option value="comfort">Комфорт</option>
                                    <option value="crossover">Кроссовер</option>
                                    <option value="microbus14">Микроавтобус-14</option>
                                    <option value="microbus16">Микроавтобус-16</option>
                                    <option value="microbus18">Микроавтобус-18</option>
                                    <option value="microbus24">Микроавтобус-24</option>
                                    <option value="microbus8">Микроавтобус-8</option>
                                    <option value="microbus10">Микроавтобус 10</option>
                                    <option value="minivan5">Минивэн-5</option>
                                    <option value="minivan6">Минивэн-6</option>
                                    <option value="premium">Представительский</option>
                                    <option value="standard">Стандарт</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">Часы аренды</label>
                                <input type="number" name="rental_hours" class="form-control" min="1">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Кол-во часов для отмены</label>
                                <input type="number" name="cancellation_hours" class="form-control" min="0" value="0">
                            </div>
                        </div>
                    </div>

                    <!-- Customer Information -->
                    <div class="form-section">
                        <h5 class="form-section-title">👤 Информация о заказчике</h5>
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label required">ФИО заказчика</label>
                                <input type="text" name="customer_name" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label required">Телефон заказчика</label>
                                <input type="tel" name="customer_phone" class="form-control" required placeholder="+7 (___) ___-__-__">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Email</label>
                                <input type="email" name="customer_email" class="form-control">
                            </div>
                        </div>
                    </div>

                    <!-- Route -->
                    <div class="form-section">
                        <h5 class="form-section-title">🗺️ Маршрут</h5>
                        <div class="route-points">
                            <div class="route-point">
                                <span class="route-point-label">Точка А</span>
                                <input type="text" name="routes[0][address]" class="form-control route-address" required placeholder="Адрес отправления">
                            </div>
                            <div class="route-point">
                                <span class="route-point-label">Точка Б</span>
                                <input type="text" name="routes[1][address]" class="form-control route-address" required placeholder="Адрес назначения">
                            </div>
                        </div>
                        <button type="button" class="btn btn-secondary btn-sm" onclick="modalManager.addRoutePoint(document.querySelector('.route-points'))">
                            <i class="fas fa-plus"></i> Добавить точку маршрута
                        </button>
                    </div>

                    <!-- Passengers -->
                    <div class="form-section">
                        <h5 class="form-section-title">👥 Пассажиры</h5>
                        <table class="passengers-table">
                            <thead>
                                <tr>
                                    <th>Имя пассажира</th>
                                    <th>Телефон</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody id="passengersTableBody">
                                <tr>
                                    <td><input type="text" class="form-control passenger-name" name="passengers[0][name]" required></td>
                                    <td><input type="tel" class="form-control passenger-phone" name="passengers[0][phone]"></td>
                                    <td></td>
                                </tr>
                            </tbody>
                        </table>
                        <button type="button" class="btn btn-secondary btn-sm" onclick="modalManager.addPassenger(document.querySelector('#passengersTableBody'))">
                            <i class="fas fa-plus"></i> Добавить пассажира
                        </button>
                    </div>

                    <!-- Additional Information -->
                    <div class="form-section">
                        <h5 class="form-section-title">📝 Дополнительная информация</h5>
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">Сумма доп. услуг</label>
                                <input type="number" name="additional_services_amount" class="form-control" min="0" step="0.01" value="0">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Рейс прибытия</label>
                                <input type="text" name="flight_number" class="form-control" placeholder="Номер рейса">
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Текст таблички</label>
                            <input type="text" name="sign_text" class="form-control" placeholder="Текст для встречающей таблички">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Примечание</label>
                            <textarea name="notes" class="form-control" rows="3"></textarea>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Комментарий менеджера (видно водителю)</label>
                            <textarea name="manager_comment" class="form-control" rows="3"></textarea>
                        </div>
                        <?php if (ACL::canViewInternalComments($role)): ?>
                        <div class="form-group">
                            <label class="form-label">Внутренний комментарий (видно только админу и менеджерам)</label>
                            <textarea name="internal_comment" class="form-control" rows="3"></textarea>
                        </div>
                        <?php endif; ?>
                    </div>

                    <!-- Legal Entities -->
                    <div class="form-section">
                        <h5 class="form-section-title">🏢 Юридические лица</h5>
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">Заказчик</label>
                                <select id="customerCompany" name="customer_company_id" class="form-control">
                                    <option value="">Выберите компанию</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Исполнитель</label>
                                <select id="executorCompany" name="executor_company_id" class="form-control">
                                    <option value="">Выберите компанию</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Стоимость заказа</label>
                                <input type="number" name="order_amount" class="form-control financial-field" min="0" step="0.01" value="0">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Стоимость исполнителя</label>
                                <input type="number" name="executor_amount" class="form-control financial-field" min="0" step="0.01" value="0">
                            </div>
                        </div>
                    </div>

                    <!-- Files -->
                    <div class="form-section">
                        <h5 class="form-section-title">📎 Файлы</h5>
                        <div id="fileUploadArea" class="file-upload-area">
                            <i class="fas fa-cloud-upload-alt" style="font-size: 3rem; color: #6c757d; margin-bottom: 1rem;"></i>
                            <p>Перетащите файлы сюда или кликните для выбора</p>
                            <small>Максимум 10 файлов, до 10 МБ каждый</small>
                            <input type="file" id="fileInput" multiple style="display: none;">
                        </div>
                        <div id="fileList" class="file-list"></div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="modalManager.closeModal('createApplicationModal')">Отмена</button>
                <button type="button" class="btn btn-primary" onclick="modalManager.submitCreateForm()">
                    <i class="fas fa-save"></i> Создать заказ
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Edit Application Modal -->
<div id="editApplicationModal" class="modal modal-xl">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">✏️ Редактировать заказ</h5>
                <button type="button" class="btn-close" onclick="modalManager.closeModal('editApplicationModal')">&times;</button>
            </div>
            <div class="modal-body">
                <form id="editApplicationForm" class="modal-form">
                    <!-- Same structure as create form, but with values populated -->
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="modalManager.closeModal('editApplicationModal')">Отмена</button>
                <button type="button" class="btn btn-primary" onclick="modalManager.submitEditForm()">
                    <i class="fas fa-save"></i> Сохранить изменения
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Assign Driver Modal -->
<div id="assignDriverModal" class="modal">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">👤 Назначить водителя</h5>
                <button type="button" class="btn-close" onclick="modalManager.closeModal('assignDriverModal')">&times;</button>
            </div>
            <div class="modal-body">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Статус</label>
                        <select id="driverStatusFilter" class="form-control">
                            <option value="">Все</option>
                            <option value="work">В работе</option>
                            <option value="dayoff">Выходной</option>
                            <option value="vacation">Отпуск</option>
                            <option value="repair">Ремонт</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Город</label>
                        <input type="text" id="driverCityFilter" class="form-control" placeholder="Фильтр по городу">
                    </div>
                </div>
                <table class="selection-table">
                    <thead>
                        <tr>
                            <th>ФИО</th>
                            <th>Статус</th>
                            <th>Рейтинг</th>
                            <th>Город</th>
                            <th>Текущие заказы</th>
                            <th>Действие</th>
                        </tr>
                    </thead>
                    <tbody id="driversTableBody">
                        <tr>
                            <td colspan="6" class="text-center">Загрузка...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="modalManager.closeModal('assignDriverModal')">Отмена</button>
            </div>
        </div>
    </div>
</div>

<!-- Assign Vehicle Modal -->
<div id="assignVehicleModal" class="modal">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">🚗 Назначить автомобиль</h5>
                <button type="button" class="btn-close" onclick="modalManager.closeModal('assignVehicleModal')">&times;</button>
            </div>
            <div class="modal-body">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Класс</label>
                        <select id="vehicleClassFilter" class="form-control">
                            <option value="">Все</option>
                            <option value="standard">Стандарт</option>
                            <option value="comfort">Комфорт</option>
                            <option value="business">Бизнес</option>
                            <option value="premium">Премиум</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Статус</label>
                        <select id="vehicleStatusFilter" class="form-control">
                            <option value="">Все</option>
                            <option value="working">На ходу</option>
                            <option value="repair">В ремонте</option>
                        </select>
                    </div>
                </div>
                <table class="selection-table">
                    <thead>
                        <tr>
                            <th>Марка</th>
                            <th>Модель</th>
                            <th>Класс</th>
                            <th>Гос. номер</th>
                            <th>Статус</th>
                            <th>Действие</th>
                        </tr>
                    </thead>
                    <tbody id="vehiclesTableBody">
                        <tr>
                            <td colspan="6" class="text-center">Загрузка...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="modalManager.closeModal('assignVehicleModal')">Отмена</button>
            </div>
        </div>
    </div>
</div>

<!-- Application Details Modal -->
<div id="applicationDetailsModal" class="modal modal-xl">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">📄 Детали заказа</h5>
                <button type="button" class="btn-close" onclick="modalManager.closeModal('applicationDetailsModal')">&times;</button>
            </div>
            <div class="modal-body">
                <!-- Content will be populated dynamically -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="modalManager.closeModal('applicationDetailsModal')">Закрыть</button>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/templates/footer.php'; ?>
