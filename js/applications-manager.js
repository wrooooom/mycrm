/**
 * Applications Manager for CRM PROFTRANSFER
 * Manages the applications table and related operations
 */

class ApplicationsManager {
    constructor() {
        this.applications = [];
        this.filters = {
            status: '',
            date: '',
            driver_id: '',
            search: ''
        };
        this.pagination = {
            page: 1,
            limit: 20,
            total: 0
        };
    }

    /**
     * Initialize applications manager
     */
    async init() {
        await this.loadApplications();
        this.setupEventListeners();
        this.startAutoRefresh();
    }

    /**
     * Setup event listeners
     */
    setupEventListeners() {
        // Search input
        const searchInput = document.getElementById('searchInput');
        if (searchInput) {
            let debounceTimer;
            searchInput.addEventListener('input', () => {
                clearTimeout(debounceTimer);
                debounceTimer = setTimeout(() => {
                    this.filters.search = searchInput.value;
                    this.pagination.page = 1;
                    this.loadApplications();
                }, 500);
            });
        }

        // Status filter
        const statusFilter = document.getElementById('statusFilter');
        if (statusFilter) {
            statusFilter.addEventListener('change', () => {
                this.filters.status = statusFilter.value;
                this.pagination.page = 1;
                this.loadApplications();
            });
        }

        // Date filter
        const dateFilter = document.getElementById('dateFilter');
        if (dateFilter) {
            dateFilter.addEventListener('change', () => {
                this.filters.date = dateFilter.value;
                this.pagination.page = 1;
                this.loadApplications();
            });
        }

        // Driver filter
        const driverFilter = document.getElementById('driverFilter');
        if (driverFilter) {
            driverFilter.addEventListener('change', () => {
                this.filters.driver_id = driverFilter.value;
                this.pagination.page = 1;
                this.loadApplications();
            });
        }

        // Reset filters
        const resetButton = document.getElementById('resetFilters');
        if (resetButton) {
            resetButton.addEventListener('click', () => this.resetFilters());
        }

        // Pagination
        const prevPage = document.getElementById('prevPage');
        const nextPage = document.getElementById('nextPage');
        if (prevPage) {
            prevPage.addEventListener('click', () => this.changePage(-1));
        }
        if (nextPage) {
            nextPage.addEventListener('click', () => this.changePage(1));
        }
    }

    /**
     * Load applications from API
     */
    async loadApplications() {
        try {
            const params = new URLSearchParams({
                action: 'getAll',
                limit: this.pagination.limit,
                offset: (this.pagination.page - 1) * this.pagination.limit,
                ...this.filters
            });

            const response = await fetch(`/api/applications.php?${params}`);
            const result = await response.json();

            if (result.success) {
                this.applications = result.data;
                this.pagination.total = result.data.length;
                this.renderApplications();
                this.renderPagination();
            } else {
                this.showError('Ошибка загрузки заявок: ' + result.message);
            }
        } catch (error) {
            console.error('Failed to load applications:', error);
            this.showError('Ошибка загрузки заявок');
        }
    }

    /**
     * Render applications table
     */
    renderApplications() {
        const tbody = document.getElementById('applicationsTableBody');
        if (!tbody) return;

        if (this.applications.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="10" class="text-center py-4">
                        <div class="alert alert-info mb-0">
                            Нет заявок для отображения
                        </div>
                    </td>
                </tr>
            `;
            return;
        }

        tbody.innerHTML = this.applications.map(app => this.renderApplicationRow(app)).join('');

        // Add click listeners for row details
        tbody.querySelectorAll('tr[data-application-id]').forEach(row => {
            row.addEventListener('click', (e) => {
                // Don't open details if button was clicked
                if (e.target.closest('button') || e.target.closest('a')) {
                    return;
                }
                this.showApplicationDetails(row.dataset.applicationId);
            });
        });
    }

    /**
     * Render single application row
     */
    renderApplicationRow(app) {
        const statusClass = this.getStatusClass(app.status);
        const statusText = this.getStatusText(app.status);

        const route = this.getRouteText(app.route_count);

        return `
            <tr data-application-id="${app.id}" style="cursor: pointer;">
                <td><strong>${app.application_number}</strong></td>
                <td><span class="status-badge ${statusClass}">${statusText}</span></td>
                <td>
                    <div>${app.customer_name}</div>
                    <small class="text-muted">${app.customer_phone || ''}</small>
                </td>
                <td>${route}</td>
                <td>${this.formatDateTime(app.trip_date)}</td>
                <td>${this.getDriverInfo(app)}</td>
                <td>${this.getVehicleInfo(app)}</td>
                <td>
                    ${app.can_view_financial ? `<strong>${this.formatMoney(app.order_amount)}</strong>` : '---'}
                </td>
                <td>
                    <div class="btn-group" role="group">
                        ${app.can_edit ? `
                            <button type="button" class="btn btn-sm btn-primary"
                                onclick="event.stopPropagation(); modalManager.openEditModal(${app.id})"
                                title="Редактировать">
                                <i class="fas fa-edit"></i>
                            </button>
                        ` : ''}
                        ${app.can_delete ? `
                            <button type="button" class="btn btn-sm btn-danger"
                                onclick="event.stopPropagation(); modalManager.deleteApplication(${app.id})"
                                title="Удалить">
                                <i class="fas fa-trash"></i>
                            </button>
                        ` : ''}
                        ${app.can_assign_driver ? `
                            <button type="button" class="btn btn-sm btn-warning"
                                onclick="event.stopPropagation(); modalManager.openAssignDriverModal(${app.id})"
                                title="Назначить водителя">
                                <i class="fas fa-user"></i>
                            </button>
                        ` : ''}
                        ${app.can_assign_vehicle ? `
                            <button type="button" class="btn btn-sm btn-info"
                                onclick="event.stopPropagation(); modalManager.openAssignVehicleModal(${app.id})"
                                title="Назначить автомобиль">
                                <i class="fas fa-car"></i>
                            </button>
                        ` : ''}
                        <button type="button" class="btn btn-sm btn-secondary"
                            onclick="event.stopPropagation(); this.showStatusMenu(${app.id})"
                            title="Изменить статус">
                            <i class="fas fa-exchange-alt"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `;
    }

    /**
     * Get route text for display
     */
    getRouteText(count) {
        if (count === 1) return '1 точка';
        if (count >= 2 && count <= 4) return `${count} точки`;
        return `${count} точек`;
    }

    /**
     * Get driver info for display
     */
    getDriverInfo(app) {
        if (app.driver_first_name && app.driver_last_name) {
            return `${app.driver_first_name} ${app.driver_last_name}`;
        }
        return '<span class="text-muted">Не назначен</span>';
    }

    /**
     * Get vehicle info for display
     */
    getVehicleInfo(app) {
        if (app.vehicle_brand && app.vehicle_model) {
            return `${app.vehicle_brand} ${app.vehicle_model}<br><small>${app.vehicle_plate || ''}</small>`;
        }
        return '<span class="text-muted">Не назначен</span>';
    }

    /**
     * Render pagination
     */
    renderPagination() {
        const paginationInfo = document.getElementById('paginationInfo');
        const prevPage = document.getElementById('prevPage');
        const nextPage = document.getElementById('nextPage');

        if (paginationInfo) {
            paginationInfo.textContent = `Страница ${this.pagination.page} из ${Math.ceil(this.pagination.total / this.pagination.limit)}`;
        }

        if (prevPage) {
            prevPage.disabled = this.pagination.page === 1;
        }

        if (nextPage) {
            nextPage.disabled = this.pagination.page >= Math.ceil(this.pagination.total / this.pagination.limit);
        }
    }

    /**
     * Change page
     */
    changePage(delta) {
        const newPage = this.pagination.page + delta;
        if (newPage > 0) {
            this.pagination.page = newPage;
            this.loadApplications();
        }
    }

    /**
     * Reset filters
     */
    resetFilters() {
        this.filters = {
            status: '',
            date: '',
            driver_id: '',
            search: ''
        };
        this.pagination.page = 1;

        // Reset form fields
        const searchInput = document.getElementById('searchInput');
        const statusFilter = document.getElementById('statusFilter');
        const dateFilter = document.getElementById('dateFilter');
        const driverFilter = document.getElementById('driverFilter');

        if (searchInput) searchInput.value = '';
        if (statusFilter) statusFilter.value = '';
        if (dateFilter) dateFilter.value = '';
        if (driverFilter) driverFilter.value = '';

        this.loadApplications();
    }

    /**
     * Show application details
     */
    async showApplicationDetails(applicationId) {
        try {
            const response = await fetch(`/api/applications.php?action=getById&id=${applicationId}`);
            const result = await response.json();

            if (result.success) {
                this.renderApplicationDetails(result.data);
            } else {
                this.showError('Ошибка загрузки деталей заявки');
            }
        } catch (error) {
            console.error('Failed to load application details:', error);
            this.showError('Ошибка загрузки деталей заявки');
        }
    }

    /**
     * Render application details modal or panel
     */
    renderApplicationDetails(app) {
        const modal = document.getElementById('applicationDetailsModal');
        if (!modal) return;

        const content = modal.querySelector('.modal-body');
        if (!content) return;

        content.innerHTML = `
            <div class="application-details">
                <h4>${app.application_number}</h4>
                <div class="row mt-3">
                    <div class="col-md-6">
                        <h5>Основная информация</h5>
                        <table class="table table-sm">
                            <tr><td>Статус:</td><td><span class="status-badge status-${app.status}">${this.getStatusText(app.status)}</span></td></tr>
                            <tr><td>Дата поездки:</td><td>${this.formatDateTime(app.trip_date)}</td></tr>
                            <tr><td>Тип услуги:</td><td>${this.getServiceText(app.service_type)}</td></tr>
                            <tr><td>Тариф:</td><td>${modalManager.getTariffText(app.tariff)}</td></tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <h5>Заказчик</h5>
                        <table class="table table-sm">
                            <tr><td>ФИО:</td><td>${app.customer_name}</td></tr>
                            <tr><td>Телефон:</td><td>${app.customer_phone || '---'}</td></tr>
                            <tr><td>Email:</td><td>${app.customer_email || '---'}</td></tr>
                            <tr><td>Компания:</td><td>${app.customer_company_name || '---'}</td></tr>
                        </table>
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col-md-6">
                        <h5>Маршрут</h5>
                        <ul class="list-group">
                            ${(app.routes || []).map((route, i) => `
                                <li class="list-group-item">
                                    <strong>${['Точка А', 'Точка Б', 'Точка В', 'Точка Г', 'Точка Д'][i] || `Точка ${i+1}`}:</strong>
                                    ${route.address}
                                </li>
                            `).join('')}
                        </ul>
                    </div>
                    <div class="col-md-6">
                        <h5>Назначения</h5>
                        <table class="table table-sm">
                            <tr><td>Водитель:</td><td>${this.getDriverInfo(app)}</td></tr>
                            <tr><td>Автомобиль:</td><td>${this.getVehicleInfo(app)}</td></tr>
                        </table>
                    </div>
                </div>
                ${app.show_manager_comment && app.manager_comment ? `
                    <div class="row mt-3">
                        <div class="col-12">
                            <h5>Комментарий менеджера</h5>
                            <div class="alert alert-info">${app.manager_comment}</div>
                        </div>
                    </div>
                ` : ''}
                ${app.show_internal_comment && app.internal_comment ? `
                    <div class="row mt-3">
                        <div class="col-12">
                            <h5>Внутренний комментарий</h5>
                            <div class="alert alert-warning">${app.internal_comment}</div>
                        </div>
                    </div>
                ` : ''}
                ${app.can_view_financial ? `
                    <div class="row mt-3">
                        <div class="col-12">
                            <h5>Финансовая информация</h5>
                            <table class="table table-sm">
                                <tr><td>Стоимость заказа:</td><td><strong>${this.formatMoney(app.order_amount)}</strong></td></tr>
                                <tr><td>Стоимость исполнителя:</td><td>${this.formatMoney(app.executor_amount)}</td></tr>
                                <tr><td>Маржа:</td><td><strong>${this.formatMoney(app.order_amount - app.executor_amount)}</strong></td></tr>
                            </table>
                        </div>
                    </div>
                ` : ''}
                <div class="row mt-3">
                    <div class="col-12">
                        <h5>Пассажиры</h5>
                        <ul class="list-group">
                            ${(app.passengers || []).map(p => `
                                <li class="list-group-item">
                                    ${p.name} ${p.phone ? `(${p.phone})` : ''}
                                </li>
                            `).join('')}
                        </ul>
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col-12">
                        <h5>Дополнительная информация</h5>
                        <table class="table table-sm">
                            ${app.flight_number ? `<tr><td>Рейс:</td><td>${app.flight_number}</td></tr>` : ''}
                            ${app.sign_text ? `<tr><td>Текст таблички:</td><td>${app.sign_text}</td></tr>` : ''}
                            ${app.rental_hours ? `<tr><td>Часы аренды:</td><td>${app.rental_hours}</td></tr>` : ''}
                            ${app.notes ? `<tr><td>Примечание:</td><td>${app.notes}</td></tr>` : ''}
                        </table>
                    </div>
                </div>
                ${app.files && app.files.length > 0 ? `
                    <div class="row mt-3">
                        <div class="col-12">
                            <h5>Файлы</h5>
                            <ul class="list-group">
                                ${app.files.map(f => `
                                    <li class="list-group-item">
                                        <a href="${f.file_path}" target="_blank">${f.filename}</a>
                                        <small>(${this.formatFileSize(f.file_size)})</small>
                                    </li>
                                `).join('')}
                            </ul>
                        </div>
                    </div>
                ` : ''}
            </div>
        `;

        modal.classList.add('show');
        document.body.style.overflow = 'hidden';
    }

    /**
     * Show status change menu
     */
    showStatusMenu(applicationId) {
        const app = this.applications.find(a => a.id === applicationId);
        if (!app) return;

        const statuses = ['new', 'confirmed', 'inwork', 'completed', 'cancelled'];
        const menuHtml = `
            <div class="dropdown-menu show" style="position: fixed; top: ${event.clientY}px; left: ${event.clientX}px;">
                ${statuses.map(status => `
                    <a class="dropdown-item" href="#" onclick="modalManager.updateStatus(${applicationId}, '${status}')">
                        ${this.getStatusText(status)}
                    </a>
                `).join('')}
            </div>
        `;

        document.body.insertAdjacentHTML('beforeend', menuHtml);

        // Close menu on click outside
        setTimeout(() => {
            document.addEventListener('click', function closeMenu(e) {
                if (!e.target.closest('.dropdown-menu')) {
                    document.querySelector('.dropdown-menu')?.remove();
                    document.removeEventListener('click', closeMenu);
                }
            });
        }, 100);
    }

    /**
     * Start auto refresh
     */
    startAutoRefresh() {
        setInterval(() => {
            this.loadApplications();
        }, 60000); // Refresh every minute
    }

    /**
     * Format date time
     */
    formatDateTime(dateStr) {
        if (!dateStr) return '---';
        const date = new Date(dateStr);
        return date.toLocaleString('ru-RU', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    }

    /**
     * Format money
     */
    formatMoney(amount) {
        if (amount === null || amount === undefined) return '---';
        return new Intl.NumberFormat('ru-RU', {
            style: 'currency',
            currency: 'RUB'
        }).format(amount);
    }

    /**
     * Format file size
     */
    formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
    }

    /**
     * Get status class
     */
    getStatusClass(status) {
        const classes = {
            'new': 'status-new',
            'confirmed': 'status-confirmed',
            'inwork': 'status-inwork',
            'completed': 'status-completed',
            'cancelled': 'status-cancelled'
        };
        return classes[status] || '';
    }

    /**
     * Get status text
     */
    getStatusText(status) {
        const statuses = {
            'new': 'Новая',
            'confirmed': 'Принята',
            'inwork': 'В работе',
            'completed': 'Завершена',
            'cancelled': 'Отменена'
        };
        return statuses[status] || status;
    }

    /**
     * Get service text
     */
    getServiceText(service) {
        const services = {
            'rent': 'Аренда',
            'transfer': 'Трансфер',
            'city_transfer': 'Трансфер город',
            'airport_arrival': 'Трансфер из аэропорта',
            'airport_departure': 'Трансфер в аэропорт',
            'train_station': 'Трансфер ж/д вокзал',
            'remote_area': 'Отдаленный район',
            'other': 'Иное'
        };
        return services[service] || service;
    }

    /**
     * Show error message
     */
    showError(message) {
        const alertDiv = document.createElement('div');
        alertDiv.className = 'alert alert-danger';
        alertDiv.textContent = message;
        document.querySelector('.container')?.prepend(alertDiv);

        setTimeout(() => {
            alertDiv.remove();
        }, 5000);
    }
}

// Initialize applications manager
const applicationsManager = new ApplicationsManager();

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    if (document.getElementById('applicationsTableBody')) {
        applicationsManager.init();
    }
});

// Make available globally
window.applicationsManager = applicationsManager;
