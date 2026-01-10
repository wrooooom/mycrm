// js/working-applications.js - РАБОЧАЯ СИСТЕМА ЗАЯВОК
class WorkingApplicationsSystem {
    constructor() {
        this.applications = [];
        this.init();
    }

    async init() {
        console.log('🔄 Загрузка заявок...');
        await this.loadApplications();
        this.setupEventListeners();
        this.renderApplicationsTable();
    }

    async loadApplications() {
        try {
            // Пробуем загрузить из API
            const response = await fetch('/api/applications.php?action=getAll');
            if (response.ok) {
                const result = await response.json();
                if (result.success) {
                    this.applications = result.data;
                    console.log('✅ Заявки загружены из API:', this.applications.length);
                } else {
                    throw new Error(result.message);
                }
            } else {
                throw new Error('API недоступен');
            }
        } catch (error) {
            console.log('⚠️ Используем локальное хранилище:', error.message);
            // Используем localStorage как запасной вариант
            const saved = localStorage.getItem('proftransfer_applications');
            this.applications = saved ? JSON.parse(saved) : [];
        }
    }

    setupEventListeners() {
        // Обработчик для кнопки создания заявки
        document.addEventListener('click', (e) => {
            if (e.target.closest('[data-action="create-application"]')) {
                this.openCreateModal();
            }
        });
    }

    openCreateModal() {
        const modalHTML = `
        <div class="modal show" id="working-create-modal" style="display: block; background: rgba(0,0,0,0.5);">
            <div class="modal-content" style="max-width: 600px; margin: 50px auto; background: white; padding: 20px; border-radius: 8px;">
                <div class="modal-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                    <h2 style="margin: 0;">➕ Создать заявку</h2>
                    <button onclick="workingApps.closeModal()" style="background: none; border: none; font-size: 20px; cursor: pointer;">×</button>
                </div>
                <div class="modal-body">
                    <div style="margin-bottom: 15px;">
                        <label style="display: block; margin-bottom: 5px; font-weight: bold;">ФИО заказчика *</label>
                        <input type="text" id="working-customer-name" placeholder="Иванов Иван Иванович" 
                               style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px;">
                    </div>
                    <div style="margin-bottom: 15px;">
                        <label style="display: block; margin-bottom: 5px; font-weight: bold;">Телефон *</label>
                        <input type="tel" id="working-customer-phone" placeholder="+79991234567" 
                               style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px;">
                    </div>
                    <div style="margin-bottom: 15px;">
                        <label style="display: block; margin-bottom: 5px; font-weight: bold;">Дата и время</label>
                        <input type="datetime-local" id="working-trip-date" 
                               style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px;">
                    </div>
                    <div style="margin-bottom: 15px;">
                        <label style="display: block; margin-bottom: 5px; font-weight: bold;">Маршрут</label>
                        <input type="text" id="working-route-from" placeholder="Откуда" 
                               style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; margin-bottom: 5px;">
                        <input type="text" id="working-route-to" placeholder="Куда" 
                               style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px;">
                    </div>
                    <div style="margin-bottom: 15px;">
                        <label style="display: block; margin-bottom: 5px; font-weight: bold;">Стоимость</label>
                        <input type="number" id="working-order-amount" placeholder="2500" 
                               style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px;">
                    </div>
                </div>
                <div class="modal-footer" style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px;">
                    <button onclick="workingApps.closeModal()" style="padding: 10px 20px; border: 1px solid #ddd; background: white; border-radius: 4px; cursor: pointer;">Отмена</button>
                    <button onclick="workingApps.createApplication()" style="padding: 10px 20px; background: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer;">✅ Создать</button>
                </div>
            </div>
        </div>
        `;
        
        document.body.insertAdjacentHTML('beforeend', modalHTML);
        
        // Установка завтрашней даты
        const tomorrow = new Date();
        tomorrow.setDate(tomorrow.getDate() + 1);
        tomorrow.setHours(10, 0, 0, 0);
        document.getElementById('working-trip-date').value = tomorrow.toISOString().slice(0, 16);
    }

    closeModal() {
        const modal = document.getElementById('working-create-modal');
        if (modal) modal.remove();
    }

    async createApplication() {
        const customerName = document.getElementById('working-customer-name').value.trim();
        const customerPhone = document.getElementById('working-customer-phone').value.trim();
        const tripDate = document.getElementById('working-trip-date').value;
        const routeFrom = document.getElementById('working-route-from').value.trim();
        const routeTo = document.getElementById('working-route-to').value.trim();
        const orderAmount = document.getElementById('working-order-amount').value;

        if (!customerName || !customerPhone) {
            alert('❌ Заполните ФИО и телефон!');
            return;
        }

        try {
            // Пробуем сохранить через API
            const response = await fetch('/api/applications.php?action=create', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    customer_name: customerName,
                    customer_phone: customerPhone,
                    trip_date: tripDate,
                    routes: [routeFrom, routeTo],
                    order_amount: orderAmount || 0,
                    status: 'new'
                })
            });

            if (response.ok) {
                const result = await response.json();
                if (result.success) {
                    // Добавляем заявку в список
                    const newApp = {
                        id: result.id,
                        application_number: result.application_number,
                        customer_name: customerName,
                        customer_phone: customerPhone,
                        trip_date: tripDate,
                        routes: [{address: routeFrom}, {address: routeTo}],
                        order_amount: orderAmount,
                        status: 'new'
                    };
                    
                    this.applications.unshift(newApp);
                    this.renderApplicationsTable();
                    this.closeModal();
                    alert(`✅ Заявка создана! Номер: ${result.application_number}`);
                    return;
                }
            }
            
            // Если API не сработало - сохраняем локально
            throw new Error('API недоступно');
            
        } catch (error) {
            console.log('💾 Сохраняем локально:', error.message);
            
            // Локальное сохранение
            const newApp = {
                id: Date.now(),
                application_number: 'LOCAL' + Date.now(),
                customer_name: customerName,
                customer_phone: customerPhone,
                trip_date: tripDate,
                routes: [{address: routeFrom}, {address: routeTo}],
                order_amount: orderAmount,
                status: 'new',
                created_at: new Date().toISOString()
            };
            
            this.applications.unshift(newApp);
            localStorage.setItem('proftransfer_applications', JSON.stringify(this.applications));
            this.renderApplicationsTable();
            this.closeModal();
            alert('✅ Заявка создана (локально)!');
        }
    }

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
                <td><input type="checkbox"></td>
                <td><strong>${app.application_number}</strong></td>
                <td>${this.formatDate(app.trip_date)}</td>
                <td>
                    ${app.routes && app.routes.length >= 2 ? 
                      `${app.routes[0].address} → ${app.routes[app.routes.length-1].address}` : 
                      'Маршрут не указан'}
                </td>
                <td>Не назначен</td>
                <td><span class="status status-${app.status}">${this.getStatusText(app.status)}</span></td>
                <td>
                    <div class="application-preview">
                        <span class="application-preview-item">${app.customer_name}</span>
                        <span class="application-preview-item">${app.customer_phone}</span>
                    </div>
                </td>
                <td>
                    <button class="action-icon" title="Редактировать">✏️</button>
                </td>
            </tr>
        `).join('');
    }

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
}

// Создаем глобальный объект
window.workingApps = new WorkingApplicationsSystem();

// Запускаем когда DOM готов
document.addEventListener('DOMContentLoaded', () => {
    workingApps.init();
});