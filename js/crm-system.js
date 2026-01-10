// js/crm-system.js - ИСПРАВЛЕННАЯ ВЕРСИЯ
class CRMApplicationSystem {
    constructor() {
        this.applications = [];
        this.drivers = [];
        this.vehicles = [];
        this.currentApplication = null;
    }

    async init() {
        console.log('🚀 Запуск CRM системы...');
        await this.loadApplications();
        this.setupGlobalEventListeners(); // ИЗМЕНИЛ НАЗВАНИЕ
        this.renderApplicationsTable();
        this.updateStats();
    }

    // ЗАГРУЗКА ДАННЫХ
    async loadApplications() {
        try {
            const response = await fetch('/api/applications.php?action=getAll');
            if (response.ok) {
                const result = await response.json();
                if (result.success) {
                    this.applications = result.data;
                    console.log('✅ Загружено заявок:', this.applications.length);
                }
            }
        } catch (error) {
            console.log('⚠️ Используем тестовые данные');
            this.applications = this.getMockApplications();
        }
    }

    // ГЛОБАЛЬНЫЕ ОБРАБОТЧИКИ - ДЕЛЕГИРОВАНИЕ СОБЫТИЙ
    setupGlobalEventListeners() {
        // Обработчик для ВСЕХ кнопок создания заявки
        document.addEventListener('click', (e) => {
            const createBtn = e.target.closest('[data-action="create-application"]');
            if (createBtn) {
                this.openCreateModal();
                return;
            }

            // Обработчик для ВСЕХ кнопок назначения водителя
            const driverBtn = e.target.closest('.assign-driver-btn');
            if (driverBtn) {
                const appId = driverBtn.dataset.appId;
                this.openAssignDriverModal(parseInt(appId));
                return;
            }

            // Обработчик для ВСЕХ кнопок назначения авто
            const vehicleBtn = e.target.closest('.assign-vehicle-btn');
            if (vehicleBtn) {
                const appId = vehicleBtn.dataset.appId;
                this.openAssignVehicleModal(parseInt(appId));
                return;
            }

            // Обработчик для кнопки добавления точки маршрута
            const addRouteBtn = e.target.closest('[onclick*="addRoutePoint"]');
            if (addRouteBtn) {
                this.addRoutePoint();
                return;
            }

            // Обработчик для кнопок удаления точки маршрута
            const removeRouteBtn = e.target.closest('[onclick*="remove"]');
            if (removeRouteBtn && removeRouteBtn.closest('.route-point')) {
                removeRouteBtn.closest('.route-point').remove();
                return;
            }
        });
    }

    // МОДАЛЬНОЕ ОКНО СОЗДАНИЯ ЗАЯВКИ - ИСПРАВЛЕННАЯ ВЕРСИЯ
    openCreateModal() {
        console.log('🎯 Открытие модального окна создания заявки');
        
        const modalHTML = `
        <div class="modal show" id="crm-create-modal" style="display: block; background: rgba(0,0,0,0.5); position: fixed; top: 0; left: 0; right: 0; bottom: 0; z-index: 1000;">
            <div class="modal-content" style="max-width: 700px; margin: 50px auto; background: white; padding: 20px; border-radius: 8px;">
                <div class="modal-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                    <h2 style="margin: 0;">➕ Создать заявку</h2>
                    <button onclick="crmSystem.closeModal()" style="background: none; border: none; font-size: 20px; cursor: pointer;">×</button>
                </div>
                <div class="modal-body">
                    <div style="margin-bottom: 15px;">
                        <label style="display: block; margin-bottom: 5px; font-weight: bold;">ФИО заказчика *</label>
                        <input type="text" id="crm-customer-name" class="form-control" placeholder="Иванов Иван Иванович" 
                               style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px;">
                    </div>
                    
                    <div style="margin-bottom: 15px;">
                        <label style="display: block; margin-bottom: 5px; font-weight: bold;">Телефон *</label>
                        <input type="tel" id="crm-customer-phone" class="form-control" placeholder="+79991234567" 
                               style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px;">
                    </div>
                    
                    <div style="margin-bottom: 15px;">
                        <label style="display: block; margin-bottom: 5px; font-weight: bold;">Дата и время *</label>
                        <input type="datetime-local" id="crm-trip-date" class="form-control" 
                               style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px;">
                    </div>

                    <div style="margin-bottom: 15px;">
                        <label style="display: block; margin-bottom: 5px; font-weight: bold;">Маршрут *</label>
                        <div id="crm-route-points">
                            <div class="route-point" style="margin-bottom: 10px;">
                                <input type="text" class="crm-route-address" placeholder="Адрес отправления" required
                                       style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px;">
                            </div>
                            <div class="route-point" style="margin-bottom: 10px;">
                                <input type="text" class="crm-route-address" placeholder="Адрес назначения" required
                                       style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px;">
                            </div>
                        </div>
                        <button type="button" class="btn btn-outline" onclick="crmSystem.addRoutePoint()" 
                                style="margin-top: 10px; padding: 8px 12px; font-size: 12px;">
                            ➕ Добавить точку маршрута
                        </button>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px;">
                        <div>
                            <label style="display: block; margin-bottom: 5px; font-weight: bold;">Класс авто</label>
                            <select id="crm-vehicle-class" class="form-control" 
                                    style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px;">
                                <option value="comfort">Комфорт</option>
                                <option value="business">Бизнес</option>
                                <option value="premium">Премиум</option>
                            </select>
                        </div>
                        <div>
                            <label style="display: block; margin-bottom: 5px; font-weight: bold;">Стоимость *</label>
                            <input type="number" id="crm-order-amount" class="form-control" placeholder="2500" required
                                   style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px;">
                        </div>
                    </div>

                    <div style="margin-bottom: 15px;">
                        <label style="display: block; margin-bottom: 5px; font-weight: bold;">Комментарий для водителя</label>
                        <textarea id="crm-driver-comment" class="form-control" rows="3" placeholder="Особые пожелания..."
                                  style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px;"></textarea>
                    </div>
                </div>
                <div class="modal-footer" style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px;">
                    <button onclick="crmSystem.closeModal()" 
                            style="padding: 10px 20px; border: 1px solid #ddd; background: white; border-radius: 4px; cursor: pointer;">
                        Отмена
                    </button>
                    <button onclick="crmSystem.createApplication()" 
                            style="padding: 10px 20px; background: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer;">
                        ✅ Создать заявку
                    </button>
                </div>
            </div>
        </div>
        `;

        document.body.insertAdjacentHTML('beforeend', modalHTML);
        
        // Установка завтрашней даты
        const tomorrow = new Date();
        tomorrow.setDate(tomorrow.getDate() + 1);
        tomorrow.setHours(10, 0, 0, 0);
        document.getElementById('crm-trip-date').value = tomorrow.toISOString().slice(0, 16);
        
        console.log('✅ Модальное окно создания заявки открыто');
    }

    // ДОБАВЛЕНИЕ ТОЧКИ МАРШРУТА - ИСПРАВЛЕННАЯ ВЕРСИЯ
    addRoutePoint() {
        console.log('📍 Добавление точки маршрута');
        const routePoints = document.getElementById('crm-route-points');
        const pointCount = routePoints.children.length;
        
        const pointHTML = `
        <div class="route-point" style="margin-bottom: 10px; display: flex; gap: 10px; align-items: center;">
            <input type="text" class="crm-route-address" placeholder="Промежуточная точка ${pointCount}" required
                   style="flex: 1; padding: 10px; border: 1px solid #ddd; border-radius: 4px;">
            <button type="button" onclick="this.parentElement.remove()" 
                    style="padding: 8px 12px; background: #dc3545; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 12px;">
                🗑️ Удалить
            </button>
        </div>
        `;

        routePoints.insertAdjacentHTML('beforeend', pointHTML);
        console.log('✅ Точка маршрута добавлена');
    }

    // СОЗДАНИЕ ЗАЯВКИ
    async createApplication() {
        console.log('🎯 Начало создания заявки');
        
        // Получаем данные
        const customerName = document.getElementById('crm-customer-name').value.trim();
        const customerPhone = document.getElementById('crm-customer-phone').value.trim();
        const tripDate = document.getElementById('crm-trip-date').value;
        const serviceType = document.getElementById('crm-service-type').value;
        const vehicleClass = document.getElementById('crm-vehicle-class').value;
        const orderAmount = document.getElementById('crm-order-amount').value;
        const driverComment = document.getElementById('crm-driver-comment').value.trim();

        // Получаем маршрут
        const routeInputs = document.querySelectorAll('.crm-route-address');
        const routes = Array.from(routeInputs)
            .map(input => input.value.trim())
            .filter(address => address !== '');

        console.log('📋 Данные формы:', { customerName, customerPhone, tripDate, routes, orderAmount });

        // ПРОВЕРКА
        if (!customerName || !customerPhone || !tripDate || !orderAmount || routes.length < 2) {
            this.showNotification('❌ Заполните все обязательные поля и добавьте минимум 2 точки маршрута!', 'error');
            return;
        }

        try {
            const response = await fetch('/api/applications.php?action=create', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    customer_name: customerName,
                    customer_phone: customerPhone,
                    trip_date: tripDate,
                    service_type: serviceType,
                    vehicle_class: vehicleClass,
                    order_amount: parseFloat(orderAmount),
                    routes: routes,
                    driver_comment: driverComment,
                    status: 'new'
                })
            });

            const result = await response.json();
            console.log('📥 Ответ сервера:', result);

            if (result.success) {
                this.showNotification('✅ Заявка успешно создана!', 'success');
                this.closeModal();
                await this.loadApplications(); // Перезагружаем список
            } else {
                this.showNotification('❌ Ошибка: ' + result.message, 'error');
            }

        } catch (error) {
            console.error('Ошибка создания заявки:', error);
            this.showNotification('❌ Ошибка сети при создании заявки', 'error');
        }
    }

    // МОДАЛЬНОЕ ОКНО НАЗНАЧЕНИЯ ВОДИТЕЛЯ - ИСПРАВЛЕННАЯ ВЕРСИЯ
    openAssignDriverModal(applicationId) {
        console.log('👨‍💼 Открытие окна назначения водителя для заявки:', applicationId);
        
        const application = this.applications.find(app => app.id === applicationId);
        if (!application) {
            console.error('❌ Заявка не найдена');
            return;
        }

        const modalHTML = `
        <div class="modal show" id="assign-driver-modal" style="display: block; background: rgba(0,0,0,0.5); position: fixed; top: 0; left: 0; right: 0; bottom: 0; z-index: 1000;">
            <div class="modal-content" style="max-width: 500px; margin: 50px auto; background: white; padding: 20px; border-radius: 8px;">
                <div class="modal-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                    <h2 style="margin: 0;">👨‍💼 Назначить водителя</h2>
                    <button onclick="crmSystem.closeModal()" style="background: none; border: none; font-size: 20px; cursor: pointer;">×</button>
                </div>
                <div class="modal-body">
                    <div style="margin-bottom: 15px; padding: 15px; background: #f8f9fa; border-radius: 4px;">
                        <p><strong>Заявка:</strong> ${application.application_number}</p>
                        <p><strong>Клиент:</strong> ${application.customer_name}</p>
                        <p><strong>Маршрут:</strong> ${application.routes[0].address} → ${application.routes[application.routes.length-1].address}</p>
                    </div>
                    
                    <div style="margin-bottom: 15px;">
                        <label style="display: block; margin-bottom: 5px; font-weight: bold;">Выберите водителя:</label>
                        <select id="driver-select" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px;">
                            <option value="">-- Выберите водителя --</option>
                            <option value="1">Иванов Алексей (Комфорт)</option>
                            <option value="2">Петров Дмитрий (Бизнес)</option>
                            <option value="3">Сидоров Михаил (Премиум)</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer" style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px;">
                    <button onclick="crmSystem.closeModal()" 
                            style="padding: 10px 20px; border: 1px solid #ddd; background: white; border-radius: 4px; cursor: pointer;">
                        Отмена
                    </button>
                    <button onclick="crmSystem.assignDriver(${applicationId})" 
                            style="padding: 10px 20px; background: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer;">
                        ✅ Назначить
                    </button>
                </div>
            </div>
        </div>
        `;

        document.body.insertAdjacentHTML('beforeend', modalHTML);
        console.log('✅ Окно назначения водителя открыто');
    }

    // МОДАЛЬНОЕ ОКНО НАЗНАЧЕНИЯ АВТО - ИСПРАВЛЕННАЯ ВЕРСИЯ
    openAssignVehicleModal(applicationId) {
        console.log('🚗 Открытие окна назначения авто для заявки:', applicationId);
        
        const application = this.applications.find(app => app.id === applicationId);
        if (!application) {
            console.error('❌ Заявка не найдена');
            return;
        }

        const modalHTML = `
        <div class="modal show" id="assign-vehicle-modal" style="display: block; background: rgba(0,0,0,0.5); position: fixed; top: 0; left: 0; right: 0; bottom: 0; z-index: 1000;">
            <div class="modal-content" style="max-width: 500px; margin: 50px auto; background: white; padding: 20px; border-radius: 8px;">
                <div class="modal-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                    <h2 style="margin: 0;">🚗 Назначить автомобиль</h2>
                    <button onclick="crmSystem.closeModal()" style="background: none; border: none; font-size: 20px; cursor: pointer;">×</button>
                </div>
                <div class="modal-body">
                    <div style="margin-bottom: 15px; padding: 15px; background: #f8f9fa; border-radius: 4px;">
                        <p><strong>Заявка:</strong> ${application.application_number}</p>
                        <p><strong>Класс авто:</strong> ${application.vehicle_class || 'Не указан'}</p>
                        <p><strong>Время:</strong> ${this.formatDate(application.trip_date)}</p>
                    </div>
                    
                    <div style="margin-bottom: 15px;">
                        <label style="display: block; margin-bottom: 5px; font-weight: bold;">Выберите автомобиль:</label>
                        <select id="vehicle-select" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px;">
                            <option value="">-- Выберите автомобиль --</option>
                            <option value="1">Toyota Camry (A123BC777) - Комфорт</option>
                            <option value="2">Mercedes E-Class (B456DE777) - Бизнес</option>
                            <option value="3">BMW 7-series (C789FG777) - Премиум</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer" style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px;">
                    <button onclick="crmSystem.closeModal()" 
                            style="padding: 10px 20px; border: 1px solid #ddd; background: white; border-radius: 4px; cursor: pointer;">
                        Отмена
                    </button>
                    <button onclick="crmSystem.assignVehicle(${applicationId})" 
                            style="padding: 10px 20px; background: #28a745; color: white; border: none; border-radius: 4px; cursor: pointer;">
                        ✅ Назначить
                    </button>
                </div>
            </div>
        </div>
        `;

        document.body.insertAdjacentHTML('beforeend', modalHTML);
        console.log('✅ Окно назначения авто открыто');
    }

    // НАЗНАЧЕНИЕ ВОДИТЕЛЯ
    async assignDriver(applicationId) {
        console.log('🎯 Назначение водителя для заявки:', applicationId);
        
        const driverSelect = document.getElementById('driver-select');
        const driverId = driverSelect.value;

        if (!driverId) {
            this.showNotification('❌ Выберите водителя!', 'error');
            return;
        }

        try {
            const response = await fetch('/api/applications.php?action=assignDriver', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    application_id: applicationId,
                    driver_id: driverId
                })
            });

            const result = await response.json();
            console.log('📥 Ответ сервера:', result);

            if (result.success) {
                this.showNotification('✅ Водитель назначен!', 'success');
                this.closeModal();
                await this.loadApplications();
            } else {
                this.showNotification('❌ Ошибка: ' + result.message, 'error');
            }

        } catch (error) {
            console.error('Ошибка назначения водителя:', error);
            this.showNotification('❌ Ошибка сети', 'error');
        }
    }

    // НАЗНАЧЕНИЕ АВТОМОБИЛЯ
    async assignVehicle(applicationId) {
        console.log('🎯 Назначение авто для заявки:', applicationId);
        
        const vehicleSelect = document.getElementById('vehicle-select');
        const vehicleId = vehicleSelect.value;

        if (!vehicleId) {
            this.showNotification('❌ Выберите автомобиль!', 'error');
            return;
        }

        try {
            const response = await fetch('/api/applications.php?action=assignVehicle', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    application_id: applicationId,
                    vehicle_id: vehicleId
                })
            });

            const result = await response.json();
            console.log('📥 Ответ сервера:', result);

            if (result.success) {
                this.showNotification('✅ Автомобиль назначен!', 'success');
                this.closeModal();
                await this.loadApplications();
            } else {
                this.showNotification('❌ Ошибка: ' + result.message, 'error');
            }

        } catch (error) {
            console.error('Ошибка назначения авто:', error);
            this.showNotification('❌ Ошибка сети', 'error');
        }
    }

    // ЗАКРЫТИЕ МОДАЛЬНОГО ОКНА
    closeModal() {
        const modals = document.querySelectorAll('.modal');
        modals.forEach(modal => modal.remove());
        console.log('🔒 Модальные окна закрыты');
    }

    // ОТОБРАЖЕНИЕ ТАБЛИЦЫ ЗАЯВОК
    renderApplicationsTable() {
        const tbody = document.getElementById('applications-table-body');
        if (!tbody) {
            console.error('❌ Не найдена таблица заявок');
            return;
        }

        if (this.applications.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="8" style="text-align: center; padding: 40px; color: #666;">
                        📋 Нет заявок
                    </td>
                </tr>
            `;
            return;
        }

        tbody.innerHTML = this.applications.map(app => `
            <tr>
                <td>
                    <div class="checkbox-container">
                        <input type="checkbox" class="checkbox">
                    </div>
                </td>
                <td>
                    <strong>${app.application_number}</strong>
                    <div class="application-preview">
                        <span class="application-preview-item">${app.customer_name}</span>
                        <span class="application-preview-item">${app.customer_phone}</span>
                    </div>
                </td>
                <td>${this.formatDate(app.trip_date)}</td>
                <td>
                    <div style="font-weight: 500;">
                        ${app.routes && app.routes.length >= 2 ? 
                          app.routes[0].address + ' → ' + app.routes[app.routes.length-1].address : 
                          'Маршрут не указан'}
                    </div>
                    <div class="application-preview">
                        <span class="application-preview-item">${app.vehicle_class || 'Не указан'}</span>
                        <span class="application-preview-item">${app.service_type || 'Трансфер'}</span>
                    </div>
                </td>
                <td>
                    ${app.driver_id ? `
                        <div style="font-weight: 500;">Водитель #${app.driver_id}</div>
                        <div class="application-preview" style="color: #38a169;">✓ Назначен</div>
                    ` : `
                        <div class="application-preview" style="color: #e53e3e;">⏳ Не назначен</div>
                    `}
                </td>
                <td>
                    <span class="status status-${app.status}">
                        ${this.getStatusText(app.status)}
                    </span>
                </td>
                <td>
                    <div style="font-weight: bold; color: #28a745;">${app.order_amount || 0} ₽</div>
                </td>
                <td>
                    <div style="display: flex; gap: 5px; flex-wrap: wrap;">
                        <button class="btn btn-small assign-driver-btn" data-app-id="${app.id}" 
                                style="padding: 5px 10px; background: #007bff; color: white; border: none; border-radius: 3px; cursor: pointer; font-size: 12px;">
                            👨‍💼
                        </button>
                        <button class="btn btn-small assign-vehicle-btn" data-app-id="${app.id}"
                                style="padding: 5px 10px; background: #28a745; color: white; border: none; border-radius: 3px; cursor: pointer; font-size: 12px;">
                            🚗
                        </button>
                        <button class="btn btn-small view-application-btn" data-app-id="${app.id}"
                                style="padding: 5px 10px; background: #6c757d; color: white; border: none; border-radius: 3px; cursor: pointer; font-size: 12px;">
                            👁️
                        </button>
                    </div>
                </td>
            </tr>
        `).join('');
        
        console.log('✅ Таблица заявок отрисована');
    }

    // ВСПОМОГАТЕЛЬНЫЕ ФУНКЦИИ
    formatDate(dateString) {
        if (!dateString) return '-';
        try {
            const date = new Date(dateString);
            return date.toLocaleDateString('ru-RU') + ' ' + date.toLocaleTimeString('ru-RU', {hour: '2-digit', minute: '2-digit'});
        } catch (e) {
            return dateString;
        }
    }

    getStatusText(status) {
        const statuses = {
            'new': 'Новая',
            'confirmed': 'Подтверждена',
            'inwork': 'В работе',
            'completed': 'Завершена',
            'cancelled': 'Отменена'
        };
        return statuses[status] || status;
    }

    showNotification(message, type = 'success') {
        // Удаляем предыдущие уведомления
        const existingNotifications = document.querySelectorAll('.notification');
        existingNotifications.forEach(notification => notification.remove());

        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        notification.innerHTML = `
            <div style="display: flex; align-items: center; gap: 10px;">
                <span>${message}</span>
                <button onclick="this.parentElement.parentElement.remove()" 
                        style="background: none; border: none; color: white; cursor: pointer; font-size: 16px;">
                    ×
                </button>
            </div>
        `;
        
        document.body.appendChild(notification);
        
        setTimeout(() => {
            if (notification.parentNode) {
                notification.parentNode.removeChild(notification);
            }
        }, 4000);
    }

    updateStats() {
        // Обновление статистики
    }

    getMockApplications() {
        return [
            {
                id: 1,
                application_number: 'TR20250001',
                customer_name: 'Тестовый Клиент',
                customer_phone: '+79990000000',
                trip_date: new Date().toISOString(),
                routes: [{address: 'Адрес А'}, {address: 'Адрес Б'}],
                order_amount: 2500,
                status: 'new',
                vehicle_class: 'comfort',
                service_type: 'transfer'
            }
        ];
    }
}

// Создаем глобальный объект CRM системы
window.crmSystem = new CRMApplicationSystem();

// Запускаем систему при загрузке страницы
document.addEventListener('DOMContentLoaded', function() {
    crmSystem.init();
});