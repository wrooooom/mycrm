<?php
/**
 * Страница управления заказами (applications)
 */

require_once 'config.php';
require_once 'auth.php';

// Проверяем авторизацию
requireLogin();

// Логируем просмотр страницы
logAction('view_applications_page', $_SESSION['user_id']);

// Получаем статистику по заявкам
try {
    $database = new Database();
    $conn = $database->getConnection();
    
    $stats = [
        'total' => 0,
        'new' => 0,
        'assigned' => 0,
        'in_progress' => 0,
        'completed' => 0,
        'cancelled' => 0,
        'today' => 0
    ];
    
    // Общая статистика
    $stmt = $conn->query("SELECT COUNT(*) FROM applications");
    $stats['total'] = $stmt->fetchColumn();
    
    $stmt = $conn->query("SELECT COUNT(*) FROM applications WHERE status = 'new'");
    $stats['new'] = $stmt->fetchColumn();
    
    $stmt = $conn->query("SELECT COUNT(*) FROM applications WHERE status = 'assigned'");
    $stats['assigned'] = $stmt->fetchColumn();
    
    $stmt = $conn->query("SELECT COUNT(*) FROM applications WHERE status = 'in_progress'");
    $stats['in_progress'] = $stmt->fetchColumn();
    
    $stmt = $conn->query("SELECT COUNT(*) FROM applications WHERE status = 'completed'");
    $stats['completed'] = $stmt->fetchColumn();
    
    $stmt = $conn->query("SELECT COUNT(*) FROM applications WHERE status = 'cancelled'");
    $stats['cancelled'] = $stmt->fetchColumn();
    
    $stmt = $conn->query("SELECT COUNT(*) FROM applications WHERE DATE(created_at) = CURDATE()");
    $stats['today'] = $stmt->fetchColumn();
    
} catch (Exception $e) {
    // Резервные данные если есть ошибки
    $stats = [
        'total' => 0,
        'new' => 0,
        'assigned' => 0,
        'in_progress' => 0,
        'completed' => 0,
        'cancelled' => 0,
        'today' => 0
    ];
}

$page_title = "Управление заказами";
include 'templates/header.php';
?>

<div class="container-fluid">
    <!-- Статистические карточки -->
    <div class="row mb-4">
        <div class="col-md-2">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4><?= $stats['total'] ?></h4>
                            <small>Всего заказов</small>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-shopping-cart fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4><?= $stats['new'] ?></h4>
                            <small>Новые</small>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-plus-circle fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4><?= $stats['assigned'] ?></h4>
                            <small>Назначенные</small>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-user-check fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card bg-secondary text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4><?= $stats['in_progress'] ?></h4>
                            <small>В работе</small>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-clock fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4><?= $stats['completed'] ?></h4>
                            <small>Завершенные</small>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-check-circle fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card bg-danger text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4><?= $stats['cancelled'] ?></h4>
                            <small>Отмененные</small>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-times-circle fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Панель управления -->
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">
                        <i class="fas fa-list me-2"></i>Список заказов
                    </h4>
                    <div>
                        <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#createApplicationModal">
                            <i class="fas fa-plus"></i> Создать заказ
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Фильтры -->
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-body py-2">
                                    <div class="row">
                                        <div class="col-md-3">
                                            <label for="statusFilter" class="form-label mb-1">Статус</label>
                                            <select id="statusFilter" class="form-control form-control-sm">
                                                <option value="">Все статусы</option>
                                                <option value="new">Новые</option>
                                                <option value="assigned">Назначенные</option>
                                                <option value="in_progress">В работе</option>
                                                <option value="completed">Завершенные</option>
                                                <option value="cancelled">Отмененные</option>
                                            </select>
                                        </div>
                                        <div class="col-md-3">
                                            <label for="dateFrom" class="form-label mb-1">Дата от</label>
                                            <input type="date" id="dateFrom" class="form-control form-control-sm">
                                        </div>
                                        <div class="col-md-3">
                                            <label for="dateTo" class="form-label mb-1">Дата до</label>
                                            <input type="date" id="dateTo" class="form-control form-control-sm">
                                        </div>
                                        <div class="col-md-3">
                                            <label for="searchInput" class="form-label mb-1">Поиск</label>
                                            <div class="input-group input-group-sm">
                                                <input type="text" id="searchInput" class="form-control" placeholder="Номер заказа, клиент...">
                                                <button class="btn btn-outline-secondary" type="button" id="searchBtn">
                                                    <i class="fas fa-search"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row mt-2">
                                        <div class="col-md-12">
                                            <button type="button" class="btn btn-primary btn-sm" id="applyFilters">
                                                <i class="fas fa-filter"></i> Применить фильтры
                                            </button>
                                            <button type="button" class="btn btn-secondary btn-sm" id="resetFilters">
                                                <i class="fas fa-undo"></i> Сбросить
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Таблица заказов -->
                    <div class="table-responsive">
                        <table id="applicationsTable" class="table table-striped table-hover">
                            <thead class="table-dark">
                                <tr>
                                    <th>№ заказа</th>
                                    <th>Дата</th>
                                    <th>Клиент</th>
                                    <th>Маршрут</th>
                                    <th>Статус</th>
                                    <th>Водитель</th>
                                    <th>Автомобиль</th>
                                    <th>Сумма</th>
                                    <th>Действия</th>
                                </tr>
                            </thead>
                            <tbody id="applicationsTableBody">
                                <!-- Данные будут загружены через AJAX -->
                            </tbody>
                        </table>
                    </div>

                    <!-- Пагинация -->
                    <div class="d-flex justify-content-between align-items-center mt-3">
                        <div>
                            <span id="paginationInfo">Показано 0 из 0 записей</span>
                        </div>
                        <nav>
                            <ul class="pagination pagination-sm mb-0" id="pagination">
                                <!-- Пагинация будет добавлена через JavaScript -->
                            </ul>
                        </nav>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Модальное окно создания заказа -->
<div class="modal fade" id="createApplicationModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Создание нового заказа</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="createApplicationForm">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="customer_name" class="form-label">Имя клиента *</label>
                                <input type="text" class="form-control" id="customer_name" name="customer_name" required>
                            </div>
                            <div class="mb-3">
                                <label for="customer_phone" class="form-label">Телефон *</label>
                                <input type="text" class="form-control" id="customer_phone" name="customer_phone" required>
                            </div>
                            <div class="mb-3">
                                <label for="service_type" class="form-label">Тип услуги *</label>
                                <select class="form-control" id="service_type" name="service_type" required>
                                    <option value="">Выберите...</option>
                                    <option value="transfer">Трансфер</option>
                                    <option value="airport_arrival">Встреча в аэропорту</option>
                                    <option value="airport_departure">Трансфер в аэропорт</option>
                                    <option value="city_transfer">Городской трансфер</option>
                                    <option value="train_station">Вокзал</option>
                                    <option value="rent">Аренда</option>
                                    <option value="other">Другое</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="tariff" class="form-label">Тариф</label>
                                <select class="form-control" id="tariff" name="tariff">
                                    <option value="comfort">Комфорт</option>
                                    <option value="business">Бизнес</option>
                                    <option value="premium">Премиум</option>
                                    <option value="standard">Стандарт</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="trip_date" class="form-label">Дата и время *</label>
                                <input type="datetime-local" class="form-control" id="trip_date" name="trip_date" required>
                            </div>
                            <div class="mb-3">
                                <label for="pickup_time" class="form-label">Время подачи</label>
                                <input type="datetime-local" class="form-control" id="pickup_time" name="pickup_time">
                            </div>
                            <div class="mb-3">
                                <label for="delivery_time" class="form-label">Время доставки</label>
                                <input type="datetime-local" class="form-control" id="delivery_time" name="delivery_time">
                            </div>
                            <div class="mb-3">
                                <label for="order_amount" class="form-label">Сумма</label>
                                <input type="number" class="form-control" id="order_amount" name="order_amount" step="0.01">
                            </div>
                        </div>
                    </div>
                    
                    <!-- Маршруты -->
                    <div class="mb-3">
                        <label class="form-label">Маршрут</label>
                        <div id="routes-container">
                            <div class="route-item mb-2">
                                <input type="text" class="form-control mb-2" name="routes[]" placeholder="Откуда (адрес)">
                                <input type="text" class="form-control mb-2" name="routes[]" placeholder="Куда (адрес)">
                            </div>
                        </div>
                    </div>
                    
                    <!-- Пассажиры -->
                    <div class="mb-3">
                        <label class="form-label">Пассажиры</label>
                        <div id="passengers-container">
                            <div class="passenger-item mb-2">
                                <div class="row">
                                    <div class="col-md-6">
                                        <input type="text" class="form-control" name="passengers[0][name]" placeholder="Имя">
                                    </div>
                                    <div class="col-md-6">
                                        <input type="text" class="form-control" name="passengers[0][phone]" placeholder="Телефон">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="notes" class="form-label">Примечания</label>
                        <textarea class="form-control" id="notes" name="notes" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                    <button type="submit" class="btn btn-primary">Создать заказ</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script>
$(document).ready(function() {
    let currentPage = 1;
    let currentFilters = {};
    
    // Загрузка заказов
    function loadApplications(page = 1) {
        currentPage = page;
        const params = {
            action: 'getAll',
            page: page,
            limit: 20,
            ...currentFilters
        };
        
        $.get('api/applications.php', params, function(response) {
            if (response.success) {
                displayApplications(response.data);
                updatePagination(response.pagination);
            } else {
                showAlert('Ошибка загрузки заказов: ' + response.message, 'danger');
            }
        }).fail(function() {
            showAlert('Ошибка связи с сервером', 'danger');
        });
    }
    
    // Отображение заказов в таблице
    function displayApplications(applications) {
        const tbody = $('#applicationsTableBody');
        tbody.empty();
        
        if (applications.length === 0) {
            tbody.html('<tr><td colspan="9" class="text-center">Заказы не найдены</td></tr>');
            return;
        }
        
        applications.forEach(function(app) {
            const row = `
                <tr>
                    <td><strong>${app.application_number}</strong></td>
                    <td>${app.formatted_date}</td>
                    <td>
                        <div>${app.customer_name}</div>
                        <small class="text-muted">${app.customer_phone}</small>
                    </td>
                    <td>
                        ${app.routes.map(route => route.address).join('<br>')}
                    </td>
                    <td>
                        <span class="badge bg-${getStatusColor(app.status)}">${getStatusLabel(app.status)}</span>
                    </td>
                    <td>${app.display_driver}</td>
                    <td>${app.display_vehicle}</td>
                    <td>${app.formatted_amount}</td>
                    <td>
                        <div class="btn-group btn-group-sm">
                            <button type="button" class="btn btn-outline-primary" onclick="editApplication(${app.id})">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button type="button" class="btn btn-outline-info" onclick="viewApplication(${app.id})">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button type="button" class="btn btn-outline-danger" onclick="deleteApplication(${app.id})">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            `;
            tbody.append(row);
        });
    }
    
    // Обновление пагинации
    function updatePagination(pagination) {
        const paginationUl = $('#pagination');
        paginationUl.empty();
        
        // Информация о записях
        $('#paginationInfo').text(
            `Показано ${(pagination.current_page - 1) * pagination.per_page + 1}-${Math.min(pagination.current_page * pagination.per_page, pagination.total_records)} из ${pagination.total_records} записей`
        );
        
        // Предыдущая страница
        if (pagination.has_prev_page) {
            paginationUl.append(`
                <li class="page-item">
                    <a class="page-link" href="#" data-page="${pagination.current_page - 1}">Предыдущая</a>
                </li>
            `);
        }
        
        // Номера страниц
        for (let i = Math.max(1, pagination.current_page - 2); i <= Math.min(pagination.total_pages, pagination.current_page + 2); i++) {
            paginationUl.append(`
                <li class="page-item ${i === pagination.current_page ? 'active' : ''}">
                    <a class="page-link" href="#" data-page="${i}">${i}</a>
                </li>
            `);
        }
        
        // Следующая страница
        if (pagination.has_next_page) {
            paginationUl.append(`
                <li class="page-item">
                    <a class="page-link" href="#" data-page="${pagination.current_page + 1}">Следующая</a>
                </li>
            `);
        }
    }
    
    // Получение цвета статуса
    function getStatusColor(status) {
        const colors = {
            'new': 'warning',
            'assigned': 'info',
            'in_progress': 'secondary',
            'completed': 'success',
            'cancelled': 'danger'
        };
        return colors[status] || 'secondary';
    }
    
    // Получение названия статуса
    function getStatusLabel(status) {
        const labels = {
            'new': 'Новый',
            'assigned': 'Назначен',
            'in_progress': 'В работе',
            'completed': 'Завершен',
            'cancelled': 'Отменен'
        };
        return labels[status] || status;
    }
    
    // Показ уведомлений
    function showAlert(message, type) {
        const alertHtml = `
            <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `;
        $('.container-fluid').prepend(alertHtml);
        
        // Автоматическое скрытие через 5 секунд
        setTimeout(function() {
            $('.alert').fadeOut();
        }, 5000);
    }
    
    // Применение фильтров
    $('#applyFilters').click(function() {
        currentFilters = {
            status: $('#statusFilter').val(),
            date_from: $('#dateFrom').val(),
            date_to: $('#dateTo').val()
        };
        
        const searchText = $('#searchInput').val().trim();
        if (searchText) {
            currentFilters.search = searchText;
        }
        
        loadApplications(1);
    });
    
    // Сброс фильтров
    $('#resetFilters').click(function() {
        $('#statusFilter').val('');
        $('#dateFrom').val('');
        $('#dateTo').val('');
        $('#searchInput').val('');
        currentFilters = {};
        loadApplications(1);
    });
    
    // Поиск по нажатию Enter
    $('#searchInput').keypress(function(e) {
        if (e.which === 13) {
            $('#applyFilters').click();
        }
    });
    
    // Пагинация
    $(document).on('click', '.page-link', function(e) {
        e.preventDefault();
        const page = $(this).data('page');
        if (page) {
            loadApplications(page);
        }
    });
    
    // Создание заказа
    $('#createApplicationForm').submit(function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        formData.append('action', 'create');
        
        $.ajax({
            url: 'api/applications.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    showAlert('Заказ успешно создан!', 'success');
                    $('#createApplicationModal').modal('hide');
                    $('#createApplicationForm')[0].reset();
                    loadApplications(1);
                } else {
                    showAlert('Ошибка: ' + response.message, 'danger');
                }
            },
            error: function() {
                showAlert('Ошибка связи с сервером', 'danger');
            }
        });
    });
    
    // Функции для действий с заказами
    window.editApplication = function(id) {
        window.location.href = 'edit-application.php?id=' + id;
    };
    
    window.viewApplication = function(id) {
        // Здесь можно добавить модальное окно просмотра
        window.location.href = 'edit-application.php?id=' + id;
    };
    
    window.deleteApplication = function(id) {
        if (confirm('Вы уверены, что хотите удалить этот заказ?')) {
            $.ajax({
                url: 'api/applications.php',
                type: 'POST',
                data: { action: 'delete', id: id },
                success: function(response) {
                    if (response.success) {
                        showAlert('Заказ удален', 'success');
                        loadApplications(currentPage);
                    } else {
                        showAlert('Ошибка: ' + response.message, 'danger');
                    }
                },
                error: function() {
                    showAlert('Ошибка связи с сервером', 'danger');
                }
            });
        }
    };
    
    // Добавление точки маршрута
    $('#createApplicationModal').on('click', '.add-route', function() {
        const routeHtml = `
            <div class="route-item mb-2">
                <input type="text" class="form-control mb-2" name="routes[]" placeholder="Промежуточная точка">
            </div>
        `;
        $('#routes-container').append(routeHtml);
    });
    
    // Добавление пассажира
    $('#createApplicationModal').on('click', '.add-passenger', function() {
        const index = $('#passengers-container .passenger-item').length;
        const passengerHtml = `
            <div class="passenger-item mb-2">
                <div class="row">
                    <div class="col-md-6">
                        <input type="text" class="form-control" name="passengers[${index}][name]" placeholder="Имя">
                    </div>
                    <div class="col-md-6">
                        <input type="text" class="form-control" name="passengers[${index}][phone]" placeholder="Телефон">
                    </div>
                </div>
            </div>
        `;
        $('#passengers-container').append(passengerHtml);
    });
    
    // Инициализация - загрузка заказов
    loadApplications();
});
</script>

<?php include 'templates/footer.php'; ?>