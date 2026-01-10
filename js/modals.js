/**
 * Modal Management System for CRM PROFTRANSFER
 * Handles creation, editing, and management of application modals
 */

class ModalManager {
    constructor() {
        this.currentApplication = null;
        this.currentUser = null;
        this.files = [];
        this.maxFiles = 10;
    }

    /**
     * Initialize modal manager
     */
    async init() {
        await this.loadCurrentUser();
        this.setupEventListeners();
        this.setupFileUpload();
    }

    /**
     * Load current user data
     */
    async loadCurrentUser() {
        try {
            const response = await fetch('/api/auth.php?action=me', {
                credentials: 'same-origin'
            });
            const result = await response.json();
            if (result.success) {
                this.currentUser = result.data;
            }
        } catch (error) {
            console.error('Failed to load user:', error);
        }
    }

    /**
     * Setup global event listeners
     */
    setupEventListeners() {
        // Close modal on backdrop click
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('modal')) {
                this.closeModal(e.target.id);
            }
        });

        // Close modal on Escape key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                const activeModal = document.querySelector('.modal.show');
                if (activeModal) {
                    this.closeModal(activeModal.id);
                }
            }
        });

        // Setup form submissions
        document.querySelectorAll('[data-action="create-application"]').forEach(btn => {
            btn.addEventListener('click', () => this.openCreateModal());
        });
    }

    /**
     * Open create application modal
     */
    async openCreateModal() {
        const modal = document.getElementById('createApplicationModal');
        if (!modal) return;

        this.resetForm('createApplicationForm');
        this.files = [];

        // Load companies for dropdowns
        await this.loadCompanies();

        modal.classList.add('show');
        document.body.style.overflow = 'hidden';
    }

    /**
     * Open edit application modal
     */
    async openEditModal(applicationId) {
        const modal = document.getElementById('editApplicationModal');
        if (!modal) return;

        try {
            const response = await fetch(`/api/applications.php?action=getById&id=${applicationId}`);
            const result = await response.json();

            if (result.success) {
                this.currentApplication = result.data;
                this.populateEditForm(result.data);
                this.files = result.data.files || [];

                await this.loadCompanies();

                modal.classList.add('show');
                document.body.style.overflow = 'hidden';
            } else {
                this.showNotification('error', result.message);
            }
        } catch (error) {
            console.error('Failed to load application:', error);
            this.showNotification('error', 'Ошибка загрузки заявки');
        }
    }

    /**
     * Open assign driver modal
     */
    async openAssignDriverModal(applicationId) {
        const modal = document.getElementById('assignDriverModal');
        if (!modal) return;

        this.currentApplication = { id: applicationId };

        await this.loadDrivers();

        modal.classList.add('show');
        document.body.style.overflow = 'hidden';
    }

    /**
     * Open assign vehicle modal
     */
    async openAssignVehicleModal(applicationId) {
        const modal = document.getElementById('assignVehicleModal');
        if (!modal) return;

        this.currentApplication = { id: applicationId };

        await this.loadVehicles();

        modal.classList.add('show');
        document.body.style.overflow = 'hidden';
    }

    /**
     * Close modal
     */
    closeModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.remove('show');
            document.body.style.overflow = '';
            this.currentApplication = null;
        }
    }

    /**
     * Reset form
     */
    resetForm(formId) {
        const form = document.getElementById(formId);
        if (form) {
            form.reset();
            form.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));

            // Clear dynamic fields
            const routePoints = form.querySelectorAll('.route-point:not(:first-child):not(:nth-child(2))');
            routePoints.forEach(point => point.remove());

            const passengersBody = form.querySelector('#passengersTableBody');
            if (passengersBody) {
                passengersBody.innerHTML = `
                    <tr>
                        <td><input type="text" class="form-control passenger-name" name="passengers[0][name]" required></td>
                        <td><input type="tel" class="form-control passenger-phone" name="passengers[0][phone]"></td>
                        <td><button type="button" class="btn btn-sm btn-danger" onclick="modalManager.removePassenger(this)">Удалить</button></td>
                    </tr>
                `;
            }
        }
    }

    /**
     * Populate edit form with application data
     */
    populateEditForm(data) {
        const form = document.getElementById('editApplicationForm');
        if (!form) return;

        // Populate basic fields
        Object.keys(data).forEach(key => {
            const input = form.querySelector(`[name="${key}"]`);
            if (input) {
                if (input.type === 'checkbox') {
                    input.checked = data[key];
                } else {
                    input.value = data[key] || '';
                }
            }
        });

        // Populate routes
        if (data.routes && data.routes.length > 0) {
            const routeContainer = form.querySelector('.route-points');
            if (routeContainer) {
                routeContainer.innerHTML = '';
                const labels = ['Точка А', 'Точка Б', 'Точка В', 'Точка Г', 'Точка Д'];
                data.routes.forEach((route, index) => {
                    const routeHtml = `
                        <div class="route-point">
                            <span class="route-point-label">${labels[index] || `Точка ${String.fromCharCode(65 + index)}`}</span>
                            <input type="text" class="form-control route-address" name="routes[${index}][address]" value="${route.address || ''}" required>
                            ${index >= 2 ? `<button type="button" class="btn btn-sm btn-danger" onclick="modalManager.removeRoutePoint(this)">Удалить</button>` : ''}
                        </div>
                    `;
                    routeContainer.insertAdjacentHTML('beforeend', routeHtml);
                });
            }
        }

        // Populate passengers
        if (data.passengers && data.passengers.length > 0) {
            const passengersBody = form.querySelector('#passengersTableBody');
            if (passengersBody) {
                passengersBody.innerHTML = '';
                data.passengers.forEach((passenger, index) => {
                    const row = `
                        <tr>
                            <td><input type="text" class="form-control passenger-name" name="passengers[${index}][name]" value="${passenger.name || ''}" required></td>
                            <td><input type="tel" class="form-control passenger-phone" name="passengers[${index}][phone]" value="${passenger.phone || ''}"></td>
                            <td><button type="button" class="btn btn-sm btn-danger" onclick="modalManager.removePassenger(this)">Удалить</button></td>
                        </tr>
                    `;
                    passengersBody.insertAdjacentHTML('beforeend', row);
                });
            }
        }

        // Hide financial fields if not admin
        if (!this.canViewFinancial()) {
            form.querySelectorAll('.financial-field').forEach(el => {
                el.closest('.form-group')?.style.setProperty('display', 'none');
            });
        }

        // Hide internal comment if not admin/manager
        if (!this.canViewInternalComments()) {
            form.querySelector('[name="internal_comment"]')?.closest('.form-group')?.style.setProperty('display', 'none');
        }
    }

    /**
     * Setup file upload functionality
     */
    setupFileUpload() {
        const fileInput = document.getElementById('fileInput');
        const fileArea = document.getElementById('fileUploadArea');
        const fileList = document.getElementById('fileList');

        if (!fileInput || !fileArea) return;

        // Drag and drop
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            fileArea.addEventListener(eventName, (e) => {
                e.preventDefault();
                e.stopPropagation();
            }, false);
        });

        ['dragenter', 'dragover'].forEach(eventName => {
            fileArea.addEventListener(eventName, () => fileArea.classList.add('dragover'), false);
        });

        ['dragleave', 'drop'].forEach(eventName => {
            fileArea.addEventListener(eventName, () => fileArea.classList.remove('dragover'), false);
        });

        fileArea.addEventListener('drop', (e) => {
            const files = e.dataTransfer.files;
            this.handleFiles(files);
        }, false);

        fileInput.addEventListener('change', (e) => {
            this.handleFiles(e.target.files);
        });

        fileArea.addEventListener('click', () => {
            fileInput.click();
        });
    }

    /**
     * Handle selected files
     */
    handleFiles(files) {
        if (this.files.length + files.length > this.maxFiles) {
            this.showNotification('error', `Максимум ${this.maxFiles} файлов`);
            return;
        }

        Array.from(files).forEach(file => {
            if (file.size > 10 * 1024 * 1024) {
                this.showNotification('error', `Файл ${file.name} слишком большой (максимум 10 МБ)`);
                return;
            }

            this.files.push(file);
        });

        this.renderFileList();
    }

    /**
     * Render file list
     */
    renderFileList() {
        const fileList = document.getElementById('fileList');
        if (!fileList) return;

        fileList.innerHTML = this.files.map((file, index) => `
            <div class="file-item">
                <div class="file-info">
                    <span class="file-name">${file.name}</span>
                    <span class="file-size">${this.formatFileSize(file.size)}</span>
                </div>
                <button type="button" class="file-remove" onclick="modalManager.removeFile(${index})">✕</button>
            </div>
        `).join('');
    }

    /**
     * Remove file from list
     */
    removeFile(index) {
        this.files.splice(index, 1);
        this.renderFileList();
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
     * Add route point
     */
    addRoutePoint(container) {
        const points = container.querySelectorAll('.route-point');
        const nextLabel = `Точка ${String.fromCharCode(65 + points.length)}`;
        const index = points.length;

        const pointHtml = `
            <div class="route-point">
                <span class="route-point-label">${nextLabel}</span>
                <input type="text" class="form-control route-address" name="routes[${index}][address]" required>
                <button type="button" class="btn btn-sm btn-danger" onclick="modalManager.removeRoutePoint(this)">Удалить</button>
            </div>
        `;

        container.insertAdjacentHTML('beforeend', pointHtml);
    }

    /**
     * Remove route point
     */
    removeRoutePoint(button) {
        button.closest('.route-point').remove();
    }

    /**
     * Add passenger
     */
    addPassenger(tbody) {
        const rows = tbody.querySelectorAll('tr');
        const index = rows.length;

        const row = `
            <tr>
                <td><input type="text" class="form-control passenger-name" name="passengers[${index}][name]" required></td>
                <td><input type="tel" class="form-control passenger-phone" name="passengers[${index}][phone]"></td>
                <td><button type="button" class="btn btn-sm btn-danger" onclick="modalManager.removePassenger(this)">Удалить</button></td>
            </tr>
        `;

        tbody.insertAdjacentHTML('beforeend', row);
    }

    /**
     * Remove passenger
     */
    removePassenger(button) {
        const tbody = button.closest('tbody');
        const rows = tbody.querySelectorAll('tr');

        if (rows.length > 1) {
            button.closest('tr').remove();
        } else {
            this.showNotification('error', 'Должен быть хотя бы один пассажир');
        }
    }

    /**
     * Load companies for dropdowns
     */
    async loadCompanies() {
        try {
            const response = await fetch('/api/companies.php?action=getAll');
            const result = await response.json();

            if (result.success && result.data) {
                const customerSelect = document.getElementById('customerCompany');
                const executorSelect = document.getElementById('executorCompany');

                if (customerSelect) {
                    customerSelect.innerHTML = '<option value="">Выберите компанию</option>';
                    result.data.filter(c => c.is_customer).forEach(company => {
                        customerSelect.innerHTML += `<option value="${company.id}">${company.name}</option>`;
                    });
                }

                if (executorSelect) {
                    executorSelect.innerHTML = '<option value="">Выберите компанию</option>';
                    result.data.filter(c => !c.is_customer).forEach(company => {
                        executorSelect.innerHTML += `<option value="${company.id}">${company.name}</option>`;
                    });
                }
            }
        } catch (error) {
            console.error('Failed to load companies:', error);
        }
    }

    /**
     * Load drivers for assignment modal
     */
    async loadDrivers(filters = {}) {
        try {
            let url = '/api/drivers.php?action=getAll';
            const params = new URLSearchParams(filters);
            if (params.toString()) {
                url += '&' + params.toString();
            }

            const response = await fetch(url);
            const result = await response.json();

            if (result.success && result.data) {
                this.renderDriversTable(result.data);
            }
        } catch (error) {
            console.error('Failed to load drivers:', error);
            this.showNotification('error', 'Ошибка загрузки водителей');
        }
    }

    /**
     * Render drivers table
     */
    renderDriversTable(drivers) {
        const tbody = document.getElementById('driversTableBody');
        if (!tbody) return;

        tbody.innerHTML = drivers.map(driver => `
            <tr>
                <td>${driver.first_name} ${driver.last_name}</td>
                <td><span class="status-badge status-${driver.status}">${this.getStatusText(driver.status)}</span></td>
                <td>${driver.rating || 'Н/Д'}</td>
                <td>${driver.city || 'Н/Д'}</td>
                <td>${driver.current_orders || 0}</td>
                <td>
                    <button type="button" class="btn btn-sm btn-primary" onclick="modalManager.assignDriver(${driver.id})">
                        Назначить
                    </button>
                </td>
            </tr>
        `).join('');
    }

    /**
     * Load vehicles for assignment modal
     */
    async loadVehicles(filters = {}) {
        try {
            let url = '/api/vehicles.php?action=getAll';
            const params = new URLSearchParams(filters);
            if (params.toString()) {
                url += '&' + params.toString();
            }

            const response = await fetch(url);
            const result = await response.json();

            if (result.success && result.data) {
                this.renderVehiclesTable(result.data);
            }
        } catch (error) {
            console.error('Failed to load vehicles:', error);
            this.showNotification('error', 'Ошибка загрузки автомобилей');
        }
    }

    /**
     * Render vehicles table
     */
    renderVehiclesTable(vehicles) {
        const tbody = document.getElementById('vehiclesTableBody');
        if (!tbody) return;

        tbody.innerHTML = vehicles.map(vehicle => `
            <tr>
                <td>${vehicle.brand}</td>
                <td>${vehicle.model}</td>
                <td>${this.getTariffText(vehicle.class)}</td>
                <td>${vehicle.license_plate || 'Н/Д'}</td>
                <td><span class="status-badge status-${vehicle.status}">${this.getStatusText(vehicle.status)}</span></td>
                <td>
                    <button type="button" class="btn btn-sm btn-primary" onclick="modalManager.assignVehicle(${vehicle.id})">
                        Назначить
                    </button>
                </td>
            </tr>
        `).join('');
    }

    /**
     * Assign driver to application
     */
    async assignDriver(driverId) {
        if (!this.currentApplication || !this.currentApplication.id) {
            this.showNotification('error', 'Не выбрана заявка');
            return;
        }

        try {
            const response = await fetch('/api/applications.php?action=assignDriver', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    application_id: this.currentApplication.id,
                    driver_id: driverId
                })
            });

            const result = await response.json();

            if (result.success) {
                this.showNotification('success', result.message);
                this.closeModal('assignDriverModal');
                this.refreshApplicationsTable();
            } else {
                this.showNotification('error', result.message);
            }
        } catch (error) {
            console.error('Failed to assign driver:', error);
            this.showNotification('error', 'Ошибка назначения водителя');
        }
    }

    /**
     * Assign vehicle to application
     */
    async assignVehicle(vehicleId) {
        if (!this.currentApplication || !this.currentApplication.id) {
            this.showNotification('error', 'Не выбрана заявка');
            return;
        }

        try {
            const response = await fetch('/api/applications.php?action=assignVehicle', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    application_id: this.currentApplication.id,
                    vehicle_id: vehicleId
                })
            });

            const result = await response.json();

            if (result.success) {
                this.showNotification('success', result.message);
                this.closeModal('assignVehicleModal');
                this.refreshApplicationsTable();
            } else {
                this.showNotification('error', result.message);
            }
        } catch (error) {
            console.error('Failed to assign vehicle:', error);
            this.showNotification('error', 'Ошибка назначения автомобиля');
        }
    }

    /**
     * Create application
     */
    async createApplication(formData) {
        try {
            const response = await fetch('/api/applications.php?action=create', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(formData)
            });

            const result = await response.json();

            if (result.success) {
                this.showNotification('success', result.message);
                this.closeModal('createApplicationModal');
                this.refreshApplicationsTable();

                // Upload files if any
                if (this.files.length > 0) {
                    await this.uploadFiles(result.application_id);
                }
            } else {
                this.showNotification('error', result.message);
            }
        } catch (error) {
            console.error('Failed to create application:', error);
            this.showNotification('error', 'Ошибка создания заявки');
        }
    }

    /**
     * Update application
     */
    async updateApplication(formData) {
        try {
            const response = await fetch('/api/applications.php?action=update', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(formData)
            });

            const result = await response.json();

            if (result.success) {
                this.showNotification('success', result.message);
                this.closeModal('editApplicationModal');
                this.refreshApplicationsTable();
            } else {
                this.showNotification('error', result.message);
            }
        } catch (error) {
            console.error('Failed to update application:', error);
            this.showNotification('error', 'Ошибка обновления заявки');
        }
    }

    /**
     * Upload files for application
     */
    async uploadFiles(applicationId) {
        for (const file of this.files) {
            const formData = new FormData();
            formData.append('file', file);
            formData.append('application_id', applicationId);

            try {
                await fetch('/api/applications.php?action=uploadFile', {
                    method: 'POST',
                    body: formData
                });
            } catch (error) {
                console.error('Failed to upload file:', error);
            }
        }
    }

    /**
     * Delete application
     */
    async deleteApplication(applicationId) {
        if (!confirm('Вы уверены, что хотите удалить эту заявку?')) {
            return;
        }

        try {
            const response = await fetch(`/api/applications.php?action=delete&id=${applicationId}`);
            const result = await response.json();

            if (result.success) {
                this.showNotification('success', result.message);
                this.refreshApplicationsTable();
            } else {
                this.showNotification('error', result.message);
            }
        } catch (error) {
            console.error('Failed to delete application:', error);
            this.showNotification('error', 'Ошибка удаления заявки');
        }
    }

    /**
     * Update application status
     */
    async updateStatus(applicationId, newStatus) {
        try {
            const response = await fetch('/api/applications.php?action=updateStatus', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    application_id: applicationId,
                    status: newStatus
                })
            });

            const result = await response.json();

            if (result.success) {
                this.showNotification('success', result.message);
                this.refreshApplicationsTable();
            } else {
                this.showNotification('error', result.message);
            }
        } catch (error) {
            console.error('Failed to update status:', error);
            this.showNotification('error', 'Ошибка изменения статуса');
        }
    }

    /**
     * Validate form
     */
    validateForm(formId) {
        const form = document.getElementById(formId);
        if (!form) return true;

        let isValid = true;
        const requiredFields = form.querySelectorAll('[required]');

        requiredFields.forEach(field => {
            if (!field.value.trim()) {
                field.classList.add('is-invalid');
                isValid = false;
            } else {
                field.classList.remove('is-invalid');
            }

            // Validate phone
            if (field.type === 'tel' && field.value) {
                const phoneRegex = /^[\+]?[(]?[0-9]{3}[)]?[-\s\.]?[0-9]{3}[-\s\.]?[0-9]{4,6}$/;
                if (!phoneRegex.test(field.value)) {
                    field.classList.add('is-invalid');
                    isValid = false;
                }
            }

            // Validate date
            if (field.type === 'datetime-local' && field.value) {
                const date = new Date(field.value);
                if (date < new Date()) {
                    field.classList.add('is-invalid');
                    isValid = false;
                }
            }

            // Validate numeric
            if (field.type === 'number' && field.value) {
                if (parseFloat(field.value) < 0) {
                    field.classList.add('is-invalid');
                    isValid = false;
                }
            }
        });

        // Validate routes (at least 2 points)
        const routes = form.querySelectorAll('.route-address');
        const filledRoutes = Array.from(routes).filter(r => r.value.trim());
        if (filledRoutes.length < 2) {
            routes.forEach(r => r.classList.add('is-invalid'));
            isValid = false;
        } else {
            routes.forEach(r => r.classList.remove('is-invalid'));
        }

        return isValid;
    }

    /**
     * Collect form data
     */
    collectFormData(formId) {
        const form = document.getElementById(formId);
        if (!form) return null;

        const formData = new FormData(form);
        const data = {};

        formData.forEach((value, key) => {
            // Handle arrays
            if (key.includes('[')) {
                const parts = key.match(/([^\[]+)\[(\d+)\]\[([^\]]+)\]/);
                if (parts) {
                    const [, arrayName, index, field] = parts;
                    if (!data[arrayName]) data[arrayName] = [];
                    if (!data[arrayName][index]) data[arrayName][index] = {};
                    data[arrayName][index][field] = value;
                }
            } else {
                data[key] = value;
            }
        });

        return data;
    }

    /**
     * Refresh applications table
     */
    refreshApplicationsTable() {
        if (window.applicationsManager && window.applicationsManager.loadApplications) {
            window.applicationsManager.loadApplications();
        }
    }

    /**
     * Show notification
     */
    showNotification(type, message) {
        // Implementation depends on your notification system
        console.log(`[${type}] ${message}`);

        // You can integrate with your existing notification system
        if (window.showNotification) {
            window.showNotification(type, message);
        }
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
            'cancelled': 'Отменена',
            'work': 'В работе',
            'dayoff': 'Выходной',
            'vacation': 'Отпуск',
            'repair': 'Ремонт',
            'working': 'На ходу',
            'broken': 'Сломана'
        };
        return statuses[status] || status;
    }

    /**
     * Get tariff text
     */
    getTariffText(tariff) {
        const tariffs = {
            'standard': 'Стандарт',
            'comfort': 'Комфорт',
            'business': 'Бизнес',
            'premium': 'Представительский',
            'crossover': 'Кроссовер',
            'minivan5': 'Минивэн-5',
            'minivan6': 'Минивэн-6',
            'microbus8': 'Микроавтобус-8',
            'microbus10': 'Микроавтобус-10',
            'microbus14': 'Микроавтобус-14',
            'microbus16': 'Микроавтобус-16',
            'microbus18': 'Микроавтобус-18',
            'microbus24': 'Микроавтобус-24',
            'bus35': 'Автобус-35',
            'bus44': 'Автобус-44',
            'bus50': 'Автобус-50',
            'other': 'Иное'
        };
        return tariffs[tariff] || tariff;
    }

    /**
     * Check if user can view financial data
     */
    canViewFinancial() {
        return this.currentUser && this.currentUser.role === 'admin';
    }

    /**
     * Check if user can view internal comments
     */
    canViewInternalComments() {
        return this.currentUser && ['admin', 'manager'].includes(this.currentUser.role);
    }
}

// Initialize modal manager
const modalManager = new ModalManager();

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    modalManager.init();
});

// Make available globally
window.modalManager = modalManager;
