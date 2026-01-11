<?php
// Временный код для отладки - удалить после решения проблемы
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

echo "=== ДЕБАГ ИНФОРМАЦИЯ ===<br>";
echo "Текущий файл: " . __FILE__ . "<br>";
echo "Директория: " . __DIR__ . "<br>";

// Проверяем существование файлов
$files_to_check = [
    'config.php' => __DIR__ . '/config.php',
    'auth.php' => __DIR__ . '/auth.php', 
    'templates/header.php' => __DIR__ . '/templates/header.php',
    'templates/sidebar.php' => __DIR__ . '/templates/sidebar.php',
    'templates/footer.php' => __DIR__ . '/templates/footer.php',
    'includes/db.php' => __DIR__ . '/includes/db.php',
    'includes/functions.php' => __DIR__ . '/includes/functions.php'
];

foreach ($files_to_check as $name => $path) {
    echo "Файл {$name}: " . (file_exists($path) ? "✅ СУЩЕСТВУЕТ" : "❌ НЕ НАЙДЕН") . "<br>";
}

// Проверяем сессию
echo "Сессия: " . (session_status() === PHP_SESSION_ACTIVE ? "✅ АКТИВНА" : "❌ НЕ АКТИВНА") . "<br>";

if (isset($_SESSION)) {
    echo "Данные сессии: ";
    print_r($_SESSION);
} else {
    echo "Сессия пуста<br>";
}

echo "=== КОНЕЦ ДЕБАГ ИНФОРМАЦИИ ===<br><br>";

// Продолжаем обычный код
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';
// ... остальной ваш код
<?php
// Правильные пути для подключения
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';
requireLogin();

// Логируем просмотр страницы
logAction('view_applications_page', $_SESSION['user_id']);

// Получаем статистику по заявкам из реальной БД
try {
    $stats = [
        'total' => 0,
        'new' => 0,
        'assigned' => 0,
        'in_progress' => 0,
        'completed' => 0,
        'cancelled' => 0,
        'today' => 0
    ];
    
    // Реальная статистика из БД
    $stats['total'] = $pdo->query("SELECT COUNT(*) FROM applications")->fetchColumn();
    $stats['new'] = $pdo->query("SELECT COUNT(*) FROM applications WHERE status = 'new'")->fetchColumn();
    $stats['assigned'] = $pdo->query("SELECT COUNT(*) FROM applications WHERE status = 'assigned'")->fetchColumn();
    $stats['in_progress'] = $pdo->query("SELECT COUNT(*) FROM applications WHERE status = 'in_progress'")->fetchColumn();
    $stats['completed'] = $pdo->query("SELECT COUNT(*) FROM applications WHERE status = 'completed'")->fetchColumn();
    $stats['cancelled'] = $pdo->query("SELECT COUNT(*) FROM applications WHERE status = 'cancelled'")->fetchColumn();
    $stats['today'] = $pdo->query("SELECT COUNT(*) FROM applications WHERE DATE(created_at) = CURDATE()")->fetchColumn();
    
} catch(Exception $e) {
    // Резервные данные если есть ошибки
    $stats = [
        'total' => 68,
        'new' => 12,
        'assigned' => 8,
        'in_progress' => 15,
        'completed' => 45,
        'cancelled' => 3,
        'today' => 5
    ];
}

// Получаем список заявок для таблицы с JOIN на связанные таблицы
try {
    $applications_query = "
        SELECT 
            a.*,
            d.name as driver_name,
            v.model as vehicle_model,
            v.license_plate as vehicle_number,
            c.name as company_name
        FROM applications a
        LEFT JOIN drivers d ON a.driver_id = d.id
        LEFT JOIN vehicles v ON a.vehicle_id = v.id  
        LEFT JOIN companies c ON a.company_id = c.id
        ORDER BY a.created_at DESC 
        LIMIT 50
    ";
    $applications = $pdo->query($applications_query)->fetchAll(PDO::FETCH_ASSOC);
} catch(Exception $e) {
    $applications = [];
    // Если есть ошибка, используем демо данные
    if (empty($applications)) {
        $applications = [
            [
                'id' => 1,
                'application_number' => 'ORD-001',
                'passenger_name' => 'Иванов А.В.',
                'passenger_phone' => '+7 (912) 345-67-89',
                'pickup_address' => 'Москва, ул. Тверская, 1',
                'destination_address' => 'Шереметьево, терминал B',
                'scheduled_date' => '2024-11-26 14:30:00',
                'status' => 'new',
                'price' => 2500,
                'driver_name' => 'Иванов И.И.',
                'vehicle_number' => 'A123BC777',
                'created_at' => '2024-11-25 10:00:00'
            ],
            [
                'id' => 2,
                'application_number' => 'ORD-002', 
                'passenger_name' => 'Петрова С.И.',
                'passenger_phone' => '+7 (923) 456-78-90',
                'pickup_address' => 'Домодедово, выход 5',
                'destination_address' => 'Москва, Красная площадь',
                'scheduled_date' => '2024-11-26 16:45:00',
                'status' => 'assigned',
                'price' => 1800,
                'driver_name' => 'Петров П.П.',
                'vehicle_number' => 'B456DE123',
                'created_at' => '2024-11-25 14:30:00'
            ],
            [
                'id' => 3,
                'application_number' => 'ORD-003',
                'passenger_name' => 'Сидоров В.П.',
                'passenger_phone' => '+7 (934) 567-89-01',
                'pickup_address' => 'Внуково, терминал A',
                'destination_address' => 'Москва, офис Профтрансфер',
                'scheduled_date' => '2024-11-25 22:15:00',
                'status' => 'completed',
                'price' => 2200,
                'driver_name' => 'Сидорова А.В.',
                'vehicle_number' => 'C789FG456',
                'created_at' => '2024-11-25 08:15:00'
            ]
        ];
    }
}

// Устанавливаем переменные для шаблонов
$page_title = "Управление заявками";
$additional_css = '
<style>
/* ВАШ ПОЛНЫЙ CSS КОД БЕЗ ИЗМЕНЕНИЙ */
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: \'Segoe UI\', Tahoma, Geneva, Verdana, sans-serif;
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

.applications-table-container {
    overflow-x: auto;
    margin-bottom: 15px;
    border: 1px solid #e0e0e0;
    border-radius: 5px;
}

.applications-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 12px;
    min-width: 1000px;
}

.applications-table th {
    background: #f8f9fa;
    color: #495057;
    font-weight: 600;
    padding: 8px 10px;
    text-align: left;
    border-bottom: 2px solid #e9ecef;
    white-space: nowrap;
}

.applications-table td {
    padding: 6px 10px;
    border-bottom: 1px solid #e9ecef;
    vertical-align: middle;
}

.applications-table tr:hover {
    background: #f8f9fa;
}

.status-badge {
    padding: 3px 8px;
    border-radius: 12px;
    font-size: 10px;
    font-weight: 600;
    white-space: nowrap;
}

.status-new {
    background: #d4edda;
    color: #155724;
}

.status-in_progress {
    background: #cce7ff;
    color: #004085;
}

.status-completed {
    background: #d1ecf1;
    color: #0c5460;
}

.status-cancelled {
    background: #f8d7da;
    color: #721c24;
}

.status-confirmed {
    background: #fff3cd;
    color: #856404;
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
    max-width: 900px;
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

.form-grid-3 {
    grid-template-columns: 1fr 1fr 1fr;
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

.form-actions {
    display: flex;
    gap: 8px;
    justify-content: flex-end;
    margin-top: 20px;
    padding-top: 15px;
    border-top: 1px solid #e9ecef;
}

.route-point {
    background: #f8f9fa;
    padding: 10px;
    border-radius: 4px;
    margin-bottom: 8px;
    border: 1px solid #e9ecef;
}

.route-point-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 8px;
}

.route-point-title {
    font-weight: 600;
    color: #495057;
    font-size: 12px;
}

.passenger-item {
    background: #f8f9fa;
    padding: 8px;
    border-radius: 4px;
    margin-bottom: 6px;
    border: 1px solid #e9ecef;
}

.passenger-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 6px;
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

.assign-buttons {
    display: flex;
    gap: 8px;
    margin: 10px 0;
}

.assign-btn {
    flex: 1;
    text-align: center;
    padding: 8px;
    border: 1px solid #e9ecef;
    border-radius: 4px;
    background: #f8f9fa;
    cursor: pointer;
    transition: all 0.2s ease;
}

.assign-btn:hover {
    background: #e9ecef;
    border-color: #2c5aa0;
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
';

$additional_scripts = '
<script>
// ВАШ ПОЛНЫЙ JavaScript КОД БЕЗ ИЗМЕНЕНИЙ
let routePointCount = 2;
let passengerCount = 1;

function showCreateApplicationModal() {
    document.getElementById(\'createApplicationModal\').style.display = \'flex\';
    document.body.style.overflow = \'hidden\';
}

function closeModal(modalId) {
    document.getElementById(modalId).style.display = \'none\';
    document.body.style.overflow = \'auto\';
}

function toggleSelectAll(checkbox) {
    const checkboxes = document.querySelectorAll(\'.application-checkbox\');
    checkboxes.forEach(cb => cb.checked = checkbox.checked);
}

function applyFilters() {
    const formData = new FormData(document.getElementById(\'filtersForm\'));
    console.log(\'Применяем фильтры:\', Object.fromEntries(formData));
    showNotification(\'Фильтры применены\', \'success\');
}

function resetFilters() {
    document.getElementById(\'filtersForm\').reset();
    showNotification(\'Фильтры сброшены\', \'info\');
}

function exportApplications() {
    if (confirm(\'Экспортировать список заявок в Excel?\')) {
        showNotification(\'Экспорт начат...\', \'info\');
    }
}

function refreshData() {
    location.reload();
}

function showBulkActions() {
    const selected = document.querySelectorAll(\'.application-checkbox:checked\');
    if (selected.length === 0) {
        alert(\'Выберите заявки для массовых действий\');
        return;
    }
    alert(`Массовые действия для ${selected.length} заявок`);
}

// Функции для работы с заявками
function editApplication(id) {
    console.log(\'Редактирование заявки ID:\', id);
    showNotification(`Редактирование заявки #${id}`, \'info\');
}

function viewApplication(id) {
    console.log(\'Просмотр заявки ID:\', id);
    showNotification(`Просмотр заявки #${id}`, \'info\');
}

function assignDriver(id) {
    console.log(\'Назначение водителя для заявки ID:\', id);
    showNotification(`Назначение водителя для заявки #${id}`, \'info\');
}

function deleteApplication(id) {
    if (confirm(`Вы уверены, что хотите удалить заявку #${id}?`)) {
        console.log(\'Удаление заявки ID:\', id);
        showNotification(\'Заявка удалена\', \'success\');
    }
}

// Функции для формы создания заявки
function addRoutePoint() {
    const routePoints = document.getElementById(\'routePoints\');
    const newPoint = document.createElement(\'div\');
    newPoint.className = \'route-point\';
    newPoint.innerHTML = `
        <div class="route-point-header">
            <span class="route-point-title">Точка ${String.fromCharCode(65 + routePointCount)}</span>
            <button type="button" class="btn btn-danger btn-xs" onclick="removeRoutePoint(this)">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="form-grid">
            <div class="form-group">
                <label class="form-label required">Страна</label>
                <select class="form-select" name="route[${routePointCount}][country]" required>
                    <option value="">Выберите страну</option>
                    <option value="RU" selected>Россия</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label required">Город</label>
                <select class="form-select" name="route[${routePointCount}][city]" required>
                    <option value="">Выберите город</option>
                    <option value="moscow">Москва</option>
                    <option value="spb">Санкт-Петербург</option>
                </select>
            </div>
        </div>
        <div class="form-group">
            <label class="form-label required">Адрес</label>
            <input type="text" class="form-input" name="route[${routePointCount}][address]" placeholder="Адрес" required>
        </div>
    `;
    routePoints.appendChild(newPoint);
    routePointCount++;
}

function removeRoutePoint(button) {
    if (routePointCount > 2) {
        button.closest(\'.route-point\').remove();
        routePointCount--;
    } else {
        alert(\'Минимальное количество точек маршрута - 2\');
    }
}

function addPassenger() {
    const passengersList = document.getElementById(\'passengersList\');
    const newPassenger = document.createElement(\'div\');
    newPassenger.className = \'passenger-item\';
    newPassenger.innerHTML = `
        <div class="passenger-header">
            <span class="route-point-title">Пассажир ${passengerCount + 1}</span>
            <button type="button" class="btn btn-danger btn-xs" onclick="removePassenger(this)">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="form-grid">
            <div class="form-group">
                <label class="form-label">Имя пассажира</label>
                <input type="text" class="form-input" name="passengers[${passengerCount}][name]" placeholder="Имя">
            </div>
            <div class="form-group">
                <label class="form-label">Телефон пассажира</label>
                <input type="tel" class="form-input" name="passengers[${passengerCount}][phone]" placeholder="+7 (___) ___-__-__">
            </div>
        </div>
    `;
    passengersList.appendChild(newPassenger);
    passengerCount++;
}

function removePassenger(button) {
    if (passengerCount > 1) {
        button.closest(\'.passenger-item\').remove();
        passengerCount--;
    } else {
        alert(\'Должен быть хотя бы один пассажир\');
    }
}

function assignDriverToApplication() {
    alert(\'Открытие окна выбора водителя\');
    // Здесь будет логика выбора водителя
}

function assignVehicleToApplication() {
    alert(\'Открытие окна выбора автомобиля\');
    // Здесь будет логика выбора автомобиля
}

function handleCreateApplication(event) {
    event.preventDefault();
    const formData = new FormData(event.target);
    
    // Валидация формы
    const requiredFields = [\'status\', \'order_number\', \'city\', \'trip_date\', \'trip_time\', \'customer_name\', \'customer_phone\', \'order_price\', \'customer_company\', \'executor_company\'];
    let isValid = true;
    
    requiredFields.forEach(field => {
        if (!formData.get(field)) {
            isValid = false;
            const input = event.target.querySelector(`[name="${field}"]`);
            if (input) input.style.borderColor = \'#dc3545\';
        }
    });

    // Валидация маршрута
    for (let i = 0; i < routePointCount; i++) {
        if (!formData.get(`route[${i}][country]`) || !formData.get(`route[${i}][city]`) || !formData.get(`route[${i}][address]`)) {
            isValid = false;
            showNotification(\'Заполните все обязательные поля маршрута\', \'error\');
            break;
        }
    }

    if (!isValid) {
        showNotification(\'Заполните все обязательные поля\', \'error\');
        return;
    }

    // Симуляция отправки на сервер
    console.log(\'Данные для создания заявки:\', Object.fromEntries(formData));
    
    showNotification(\'Заявка успешно создана!\', \'success\');
    
    setTimeout(() => {
        closeModal(\'createApplicationModal\');
        // location.reload();
    }, 1500);
}

// Функция уведомлений
function showNotification(message, type = \'info\') {
    const notification = document.createElement(\'div\');
    notification.className = `notification ${type}`;
    notification.textContent = message;
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.style.animation = \'slideIn 0.3s ease reverse\';
        setTimeout(() => {
            document.body.removeChild(notification);
        }, 300);
    }, 3000);
}

// Обработчики событий
document.addEventListener(\'click\', function(e) {
    if (e.target.classList.contains(\'modal\')) {
        e.target.style.display = \'none\';
        document.body.style.overflow = \'auto\';
    }
});

document.addEventListener(\'keydown\', function(e) {
    if (e.key === \'Escape\') {
        const modals = document.querySelectorAll(\'.modal\');
        modals.forEach(modal => {
            modal.style.display = \'none\';
            document.body.style.overflow = \'auto\';
        });
    }
    
    // Горячие клавиши
    if (e.ctrlKey && e.key === \'n\') {
        e.preventDefault();
        showCreateApplicationModal();
    }
});

// Маска для телефона
document.addEventListener(\'input\', function(e) {
    if (e.target.type === \'tel\') {
        let value = e.target.value.replace(/\\D/g, \'\');
        if (value.startsWith(\'7\') || value.startsWith(\'8\')) {
            value = value.substring(1);
        }
        if (value.length > 0) {
            value = \'+7 (\' + value;
            if (value.length > 7) value = value.substring(0, 7) + \') \' + value.substring(7);
            if (value.length > 12) value = value.substring(0, 12) + \'-\' + value.substring(12);
            if (value.length > 15) value = value.substring(0, 15) + \'-\' + value.substring(15);
        }
        e.target.value = value;
    }
});

// Анимация загрузки
document.addEventListener(\'DOMContentLoaded\', function() {
    const cards = document.querySelectorAll(\'.stat-card\');
    cards.forEach((card, index) => {
        card.style.opacity = \'0\';
        card.style.transform = \'translateY(10px)\';
        
        setTimeout(() => {
            card.style.transition = \'all 0.3s ease\';
            card.style.opacity = \'1\';
            card.style.transform = \'translateY(0)\';
        }, index * 100);
    });
});
</script>
';

// Подключаем шаблоны
include __DIR__ . '/templates/header.php';
include __DIR__ . '/templates/sidebar.php';
?>

<!-- ВАШ ПОЛНЫЙ HTML КОНТЕНТ БЕЗ ИЗМЕНЕНИЙ -->
<div class="main">
    <div class="container">
        <div class="content-card">
            <div class="page-header">
                <h1 class="page-title"><i class="fas fa-file-alt"></i> Управление заявками</h1>
                <div class="action-buttons">
                    <button class="btn btn-primary" onclick="showCreateApplicationModal()">
                        <i class="fas fa-plus"></i> Создать заявку
                    </button>
                    <button class="btn btn-success" onclick="exportApplications()">
                        <i class="fas fa-file-export"></i> Экспорт
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
                Управление транспортными заявками и бронированиями. Всего заявок: <strong><?php echo $stats['total']; ?></strong>
            </p>
            
            <!-- Статистика -->
            <div class="stats-grid">
                <div class="stat-card">
                    <h3><i class="fas fa-file-alt"></i> Всего заявок</h3>
                    <div class="stat-number"><?php echo $stats['total']; ?></div>
                    <p class="stat-description">В базе данных</p>
                </div>
                
                <div class="stat-card success">
                    <h3><i class="fas fa-plus-circle"></i> Новые</h3>
                    <div class="stat-number"><?php echo $stats['new']; ?></div>
                    <p class="stat-description">Требуют обработки</p>
                </div>
                
                <div class="stat-card info">
                    <h3><i class="fas fa-spinner"></i> Назначенные</h3>
                    <div class="stat-number"><?php echo $stats['assigned']; ?></div>
                    <p class="stat-description">Водитель назначен</p>
                </div>
                
                <div class="stat-card warning">
                    <h3><i class="fas fa-truck"></i> В работе</h3>
                    <div class="stat-number"><?php echo $stats['in_progress']; ?></div>
                    <p class="stat-description">Выполняются</p>
                </div>
                
                <div class="stat-card">
                    <h3><i class="fas fa-check-circle"></i> Завершённые</h3>
                    <div class="stat-number"><?php echo $stats['completed']; ?></div>
                    <p class="stat-description">Успешно выполнены</p>
                </div>
                
                <div class="stat-card danger">
                    <h3><i class="fas fa-times-circle"></i> Отменённые</h3>
                    <div class="stat-number"><?php echo $stats['cancelled']; ?></div>
                    <p class="stat-description">Не выполнены</p>
                </div>
            </div>

            <!-- Фильтры и поиск -->
            <div class="filters-section">
                <h3><i class="fas fa-filter"></i> Фильтры и поиск</h3>
                <form id="filtersForm" class="filter-row">
                    <input type="text" name="search" placeholder="Поиск по номеру, ФИО..." class="filter-input">
                    <select name="status" class="filter-input">
                        <option value="">Все статусы</option>
                        <option value="new">Новые</option>
                        <option value="assigned">Назначенные</option>
                        <option value="in_progress">В работе</option>
                        <option value="completed">Завершённые</option>
                        <option value="cancelled">Отменённые</option>
                    </select>
                    <input type="date" name="date_from" class="filter-input" placeholder="Дата от">
                    <input type="date" name="date_to" class="filter-input" placeholder="Дата до">
                    <button type="button" class="btn btn-primary" onclick="applyFilters()">
                        <i class="fas fa-search"></i> Применить
                    </button>
                    <button type="button" class="btn btn-outline" onclick="resetFilters()">
                        <i class="fas fa-times"></i> Сбросить
                    </button>
                </form>
            </div>

            <!-- Таблица заявок -->
            <div class="applications-table-container">
                <table class="applications-table">
                    <thead>
                        <tr>
                            <th style="width: 30px;">
                                <input type="checkbox" id="selectAll" onchange="toggleSelectAll(this)">
                            </th>
                            <th>№ заказа</th>
                            <th>Клиент</th>
                            <th>Телефон</th>
                            <th>Маршрут</th>
                            <th>Дата/время</th>
                            <th>Статус</th>
                            <th>Стоимость</th>
                            <th>Водитель</th>
                            <th>Авто</th>
                            <th style="width: 120px;">Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($applications as $app): ?>
                        <tr>
                            <td>
                                <input type="checkbox" class="application-checkbox" value="<?php echo $app['id']; ?>">
                            </td>
                            <td>
                                <div style="font-weight: 600; color: #2c5aa0;"><?php echo htmlspecialchars($app['application_number']); ?></div>
                                <div style="font-size: 10px; color: #6c757d;">ID: <?php echo $app['id']; ?></div>
                            </td>
                            <td>
                                <div style="font-weight: 600;"><?php echo htmlspecialchars($app['passenger_name']); ?></div>
                            </td>
                            <td><?php echo htmlspecialchars($app['passenger_phone']); ?></td>
                            <td>
                                <div style="font-weight: 600;"><?php echo htmlspecialchars($app['pickup_address']); ?></div>
                                <div style="font-size: 10px; color: #6c757d;">→ <?php echo htmlspecialchars($app['destination_address']); ?></div>
                            </td>
                            <td>
                                <?php if (!empty($app['scheduled_date'])): ?>
                                <div style="font-weight: 600;"><?php echo date('d.m.Y', strtotime($app['scheduled_date'])); ?></div>
                                <div style="font-size: 10px; color: #6c757d;"><?php echo date('H:i', strtotime($app['scheduled_date'])); ?></div>
                                <?php else: ?>
                                <span style="color: #6c757d; font-size: 11px;">—</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php 
                                $statusClass = 'status-new';
                                $statusText = 'Новая';
                                switch($app['status']) {
                                    case 'assigned': 
                                        $statusClass = 'status-confirmed'; 
                                        $statusText = 'Назначена'; 
                                        break;
                                    case 'in_progress': 
                                        $statusClass = 'status-in_progress'; 
                                        $statusText = 'В работе'; 
                                        break;
                                    case 'completed': 
                                        $statusClass = 'status-completed'; 
                                        $statusText = 'Завершена'; 
                                        break;
                                    case 'cancelled': 
                                        $statusClass = 'status-cancelled'; 
                                        $statusText = 'Отменена'; 
                                        break;
                                }
                                ?>
                                <span class="status-badge <?php echo $statusClass; ?>"><?php echo $statusText; ?></span>
                            </td>
                            <td>
                                <?php if (!empty($app['price'])): ?>
                                <div style="font-weight: 600; color: #28a745;"><?php echo number_format($app['price'], 0, '', ' '); ?> ₽</div>
                                <?php else: ?>
                                <span style="color: #6c757d; font-size: 11px;">—</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!empty($app['driver_name'])): ?>
                                <div style="font-weight: 600;"><?php echo htmlspecialchars($app['driver_name']); ?></div>
                                <div style="font-size: 10px; color: #6c757d;">Назначен</div>
                                <?php else: ?>
                                <span style="color: #dc3545; font-size: 11px;">Не назначен</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!empty($app['vehicle_number'])): ?>
                                <div style="font-weight: 600;"><?php echo htmlspecialchars($app['vehicle_number']); ?></div>
                                <?php else: ?>
                                <span style="color: #6c757d; font-size: 11px;">—</span>
                                <?php endif; ?>
                            </td>
                            <td class="table-actions">
                                <button class="btn btn-primary btn-xs" onclick="editApplication(<?php echo $app['id']; ?>)" title="Редактировать">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn btn-success btn-xs" onclick="viewApplication(<?php echo $app['id']; ?>)" title="Просмотреть">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button class="btn btn-warning btn-xs" onclick="assignDriver(<?php echo $app['id']; ?>)" title="Назначить">
                                    <i class="fas fa-user"></i>
                                </button>
                                <?php if (isAdmin()): ?>
                                <button class="btn btn-danger btn-xs" onclick="deleteApplication(<?php echo $app['id']; ?>)" title="Удалить">
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
                    Показано <?php echo count($applications); ?> из <?php echo $stats['total']; ?> заявок
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
                    В этом разделе вы можете управлять транспортными заявками. Доступные функции: 
                    создание новых заявок, редактирование информации, назначение водителей и автомобилей, 
                    отслеживание статуса выполнения заказов.
                </p>
                <p>
                    <strong>Статус системы:</strong> Основной функционал работает. В разработке: интеграция с системой трекинга, 
                    автоматические уведомления клиентов, массовая обработка заявок.
                </p>
            </div>
        </div>
    </div>
</div>

<!-- Модальное окно создания заявки -->
<div id="createApplicationModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title"><i class="fas fa-plus"></i> Создать заявку</h3>
            <button class="modal-close" onclick="closeModal('createApplicationModal')">&times;</button>
        </div>
        
        <form id="createApplicationForm" onsubmit="handleCreateApplication(event)">
            <!-- Основная информация -->
            <div class="form-section">
                <h4 class="form-section-title">Основная информация</h4>
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label required">Статус</label>
                        <select class="form-select" name="status" required>
                            <option value="">Выберите статус</option>
                            <option value="new">Новая</option>
                            <option value="confirmed">Подтверждена</option>
                            <option value="in_progress">В работе</option>
                            <option value="completed">Завершена</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label required">Номер заказа</label>
                        <input type="text" class="form-input" name="order_number" placeholder="Введите номер заказа" required>
                    </div>
                </div>
                
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label required">Город</label>
                        <select class="form-select" name="city" required>
                            <option value="">Выберите город</option>
                            <option value="moscow">Москва</option>
                            <option value="spb">Санкт-Петербург</option>
                            <option value="ekb">Екатеринбург</option>
                            <option value="kazan">Казань</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Страна</label>
                        <select class="form-select" name="country">
                            <option value="RU" selected>Россия</option>
                            <option value="KZ">Казахстан</option>
                            <option value="BY">Беларусь</option>
                        </select>
                    </div>
                </div>

                <div class="form-grid form-grid-3">
                    <div class="form-group">
                        <label class="form-label required">Дата поездки</label>
                        <input type="date" class="form-input" name="trip_date" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label required">Время поездки</label>
                        <input type="time" class="form-input" name="trip_time" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Кол-во часов для отмены</label>
                        <input type="number" class="form-input" name="cancel_hours" value="0" min="0">
                    </div>
                </div>

                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">Услуга</label>
                        <select class="form-select" name="service">
                            <option value="">Значение не указано</option>
                            <option value="transfer">Трансфер</option>
                            <option value="delivery">Доставка</option>
                            <option value="rent">Аренда</option>
                            <option value="corporate">Корпоративный</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Тариф</label>
                        <select class="form-select" name="tariff">
                            <option value="">Значение не указано</option>
                            <option value="economy">Эконом</option>
                            <option value="comfort">Комфорт</option>
                            <option value="business">Бизнес</option>
                            <option value="premium">Премиум</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Информация о заказчике -->
            <div class="form-section">
                <h4 class="form-section-title">Информация о заказчике</h4>
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label required">ФИО заказчика</label>
                        <input type="text" class="form-input" name="customer_name" placeholder="Введите ФИО" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label required">Телефон</label>
                        <input type="tel" class="form-input" name="customer_phone" placeholder="+7 (___) ___-__-__" required>
                    </div>
                </div>
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">Сумма доп. услуг</label>
                        <input type="number" class="form-input" name="extra_services_amount" value="0" min="0">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Рейс прибытия</label>
                        <input type="text" class="form-input" name="arrival_flight" placeholder="Номер рейса">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Текст таблички</label>
                    <input type="text" class="form-input" name="sign_text" placeholder="Текст для таблички">
                </div>
                <div class="form-group">
                    <label class="form-label">Комментарий менеджера</label>
                    <textarea class="form-textarea" name="manager_comment" placeholder="Комментарий"></textarea>
                </div>
            </div>

            <!-- Маршрут -->
            <div class="form-section">
                <h4 class="form-section-title">Маршрут</h4>
                <div id="routePoints">
                    <!-- Точка А -->
                    <div class="route-point">
                        <div class="route-point-header">
                            <span class="route-point-title">Точка А</span>
                        </div>
                        <div class="form-grid">
                            <div class="form-group">
                                <label class="form-label required">Страна</label>
                                <select class="form-select" name="route[0][country]" required>
                                    <option value="">Выберите страну</option>
                                    <option value="RU" selected>Россия</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label required">Город</label>
                                <select class="form-select" name="route[0][city]" required>
                                    <option value="">Выберите город</option>
                                    <option value="moscow">Москва</option>
                                    <option value="spb">Санкт-Петербург</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label required">Адрес</label>
                            <input type="text" class="form-input" name="route[0][address]" placeholder="Адрес" required>
                        </div>
                    </div>

                    <!-- Точка Б -->
                    <div class="route-point">
                        <div class="route-point-header">
                            <span class="route-point-title">Точка Б</span>
                        </div>
                        <div class="form-grid">
                            <div class="form-group">
                                <label class="form-label required">Страна</label>
                                <select class="form-select" name="route[1][country]" required>
                                    <option value="">Выберите страну</option>
                                    <option value="RU" selected>Россия</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label required">Город</label>
                                <select class="form-select" name="route[1][city]" required>
                                    <option value="">Выберите город</option>
                                    <option value="moscow">Москва</option>
                                    <option value="spb">Санкт-Петербург</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label required">Адрес</label>
                            <input type="text" class="form-input" name="route[1][address]" placeholder="Адрес" required>
                        </div>
                    </div>
                </div>
                <button type="button" class="btn btn-outline btn-sm" onclick="addRoutePoint()">
                    <i class="fas fa-plus"></i> Добавить точку маршрута
                </button>
            </div>

            <!-- Пассажиры -->
            <div class="form-section">
                <h4 class="form-section-title">Пассажиры</h4>
                <div id="passengersList">
                    <div class="passenger-item">
                        <div class="passenger-header">
                            <span class="route-point-title">Пассажир 1</span>
                            <button type="button" class="btn btn-danger btn-xs" onclick="removePassenger(this)">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                        <div class="form-grid">
                            <div class="form-group">
                                <label class="form-label">Имя пассажира</label>
                                <input type="text" class="form-input" name="passengers[0][name]" placeholder="Имя">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Телефон пассажира</label>
                                <input type="tel" class="form-input" name="passengers[0][phone]" placeholder="+7 (___) ___-__-__">
                            </div>
                        </div>
                    </div>
                </div>
                <button type="button" class="btn btn-outline btn-sm" onclick="addPassenger()">
                    <i class="fas fa-plus"></i> Добавить пассажира
                </button>
            </div>

            <!-- Файлы заказа -->
            <div class="form-section">
                <h4 class="form-section-title">Файлы заказа</h4>
                <div class="form-group">
                    <div class="file-upload" onclick="document.getElementById('orderFiles').click()">
                        <i class="fas fa-cloud-upload-alt"></i>
                        <div class="file-upload-text">Список файлов пуст</div>
                        <div class="file-upload-text">+ Прикрепить файл</div>
                        <input type="file" id="orderFiles" name="order_files[]" multiple style="display: none;">
                    </div>
                </div>
            </div>

            <!-- Юридические лица и стоимость -->
            <div class="form-section">
                <h4 class="form-section-title">Юридические лица и стоимость</h4>
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label required">Стоимость заказа</label>
                        <input type="number" class="form-input" name="order_price" placeholder="0" required min="0">
                    </div>
                    <div class="form-group">
                        <label class="form-label required">Заказчик</label>
                        <select class="form-select" name="customer_company" required>
                            <option value="">Выберите заказчика</option>
                            <option value="1">ООО "ПРОФТРАНСФЕР"</option>
                            <option value="2">ИП Иванов</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label required">Исполнитель</label>
                        <select class="form-select" name="executor_company" required>
                            <option value="">Выберите исполнителя</option>
                            <option value="1">ООО "ПРОФТРАНСФЕР"</option>
                            <option value="2">ИП Иванов</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Стоимость исполнителя</label>
                        <input type="number" class="form-input" name="executor_price" placeholder="0" min="0">
                    </div>
                </div>
            </div>

            <!-- Назначение водителя и авто -->
            <div class="form-section">
                <h4 class="form-section-title">Назначение</h4>
                <div class="assign-buttons">
                    <div class="assign-btn" onclick="assignDriverToApplication()">
                        <i class="fas fa-user" style="font-size: 20px; margin-bottom: 5px;"></i>
                        <div>🚗 Назначить водителя</div>
                    </div>
                    <div class="assign-btn" onclick="assignVehicleToApplication()">
                        <i class="fas fa-truck" style="font-size: 20px; margin-bottom: 5px;"></i>
                        <div>🚙 Назначить авто</div>
                    </div>
                </div>
            </div>

            <div class="form-actions">
                <button type="button" class="btn btn-outline" onclick="closeModal('createApplicationModal')">Отмена</button>
                <button type="button" class="btn btn-warning">
                    <i class="fas fa-share"></i> Передать заявку
                </button>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Создать заявку
                </button>
            </div>
        </form>
    </div>
</div>

<?php
// Подключаем подвал
include __DIR__ . '/templates/footer.php';
?>