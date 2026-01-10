// js/applications.js - ПОЛНАЯ РАБОЧАЯ ВЕРСИЯ
class ApplicationsSystem {
    constructor() {
        this.applications = [];
        this.drivers = [];
        this.vehicles = [];
        this.selectedApplications = new Set();
    }

    async init() {
        console.log('🚀 Инициализация системы заявок...');
        await this.loadApplications();
        this.setupEventListeners();
        this.renderApplicationsTable();
    }

    async loadApplications() {
        try {
            console.log('📥 Загрузка заявок...');
            const response = await fetch('/api/applications.php?action=getAll');
            const result = await response.json();
            
            if (result.success) {
                this.applications = result.data;
                console.log('✅ Загружено заявок:', this.applications.length);
            } else {
                console.error('❌ Ошибка загрузки:', result.message);
                this.applications = [];
            }
            
            this.renderApplicationsTable();
        } catch (error) {
            console.error('❌ Ошибка загрузки заявок:', error);
            this.applications = [];
            this.renderApplicationsTable();
        }
    }

    setupEventListeners() {
        console.log('🔧 Настройка обработчиков...');
        
        // Обработчик создания заявки
        document.addEventListener('click', (e) => {
            if (e.target.closest('[data-action="create-application"]')) {
                console.log('🎯 Нажата кнопка создания заявки');
                this.openCreateModal();
            }
        });

        // Обработчики для кнопок в таблице
        document.addEventListener('click', (e) => {
            if (e.target.closest('.assign-driver-btn')) {
                const appId = e.target.closest('.assign-driver-btn').dataset.appId;
                console.log('👨‍💼 Назначение водителя для заявки:', appId);
                this.openAssignDriverModal(parseInt(appId));
            }
            
            if (e.target.closest('.assign-vehicle-btn')) {
                const appId = e.target.closest('.assign-vehicle-btn').dataset.appId;
                console.log('🚗 Назначение авто для заявки:', appId);
                this.openAssignVehicleModal(parseInt(appId));
            }
            
            if (e.target.closest('.view-application-btn')) {
                const appId = e.target.closest('.view-application-btn').dataset.appId;
                console.log('👁️ Просмотр заявки:', appId);
                this.viewApplication(parseInt(appId));
            }
        });
    }

    openCreateModal() {
        console.log('📝 Открытие модального окна создания заявки');
        
        const modalHTML = `
        <div class="modal show" id="create-application-modal" style="display: block; background: rgba(0,0,0,0.5); position: fixed; top: 0; left: 0; right: 0; bottom: 0; z-index: 1000;">
            <div class="modal-content" style="max-width: 700px; margin: 50px auto; background: white; padding: 20px; border-radius: 8px; max-height: 90vh; overflow-y: auto;">
                <div class="modal-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 1px solid #eee; padding-bottom: 15px;">
                    <h2 style="margin: 0; color: #333;">➕ Создать новую заявку</h2>
                    <button onclick="applicationsSystem.closeModal('create-application-modal')" style="background: none; border: none; font-size: 24px; cursor: pointer; color: #666;">×</button>
                </div>
                
                <div class="modal-body">
                    <div class="form-section" style="margin-bottom: 20px;">
                        <h3 style="color: #333; margin-bottom: 15px;">👤 Данные клиента</h3>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                            <div>
                                <label style="display: block; margin-bottom: 5px; font-weight: bold; color: #333;">ФИО заказчика *</label>
                                <input type="text" id="customer-name" class="form-control" placeholder="Иванов Иван Иванович" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px;">
                            </div>
                            <div>
                                <label style="display: block; margin-bottom: 5px; font-weight: bold; color: #333;">Телефон *</label>
                                <input type="tel" id="customer-phone" class="form-control" placeholder="+79991234567" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px;">
                            </div>
                        </div>
                    </div>

                    <div class="form-section" style="margin-bottom: 20px;">
                        <h3 style="color: #333; margin-bottom: 15px;">🚗 Детали поездки</h3>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                            <div>
                                <label style="display: block; margin-bottom: 5px; font-weight: bold; color: #333;">Дата и время *</label>
                                <input type="datetime-local" id="trip-date" class="form-control" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px;">
                            </div>
                            <div>
                                <label style="display: block; margin-bottom: 5px; font-weight: bold; color: #333;">Тип услуги *</label>
                                <select id="service-type" class="form-control" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px;">
                                    <option value="">Выберите тип</option>
                                    <option value="transfer">Трансфер</option>
                                    <option value="city_transfer">Городской трансфер</option>
                                    <option value="airport_arrival">Встреча в аэропорту</option>
                                    <option value="airport_departure">Проводы в аэропорт</option>
                                    <option value="rent">Аренда</option>
                                    <option value="train_station">Вокзал</option>
                                </select>
                            </div>
                            <div>
                                <label style="display: block; margin-bottom: 5px; font-weight: bold; color: #333;">Класс авто *</label>
                                <select id="vehicle-class" class="form-control" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px;">
                                    <option value="">Выберите класс</option>
                                    <option value="standard">Стандарт</option>
                                    <option value="comfort">Комфорт</option>
                                    <option value="business">Бизнес</option>
                                    <option value="premium">Премиум</option>
                                    <option value="minivan5">Минивэн (5 мест)</option>
                                    <option value="minivan6">Минивэн (6 мест)</option>
                                </select>
                            </div>
                            <div>
                                <label style="display: block; margin-bottom: 5px; font-weight: bold; color: #333;">Стоимость *</label>
                                <input type="number" id="order-amount" class="form-control" placeholder="2500" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px;">
                            </div>
                        </div>
                    </div>

                    <div class="form-section" style="margin-bottom: 20px;">
                        <h3 style="color: #333; margin-bottom: 15px;">📍 Маршрут</h3>
                        <div style="display: grid; gap: 10px; margin-bottom: 10px;">
                            <div>
                                <label style="display: block; margin-bottom: 5px; font-weight: bold; color: #333;">Точка А (Откуда) *</label>
                                <input type="text" id="route-from" class="form-control" placeholder="Аэропорт Шереметьево, терминал B" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px;">
                            </div>
                            <div>
                                <label style="display: block; margin-bottom: 5px; font-weight: bold; color: #333;">Точка Б (Куда) *</label>
                                <input type="text" id="route-to" class="form-control" placeholder="ул. Тверская, д. 15" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px;">
                            </div>
                        </div>
                        <div id="additional-routes-container"></div>
                        <button type="button" onclick="applicationsSystem.addRoutePoint()" style="padding: 8px 15px; background: #6c757d; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 14px;">
                            ➕ Добавить точку маршрута
                        </button>
                    </div>

                    <div class="form-section" style="margin-bottom: 20px;">
                        <h3 style="color: #333; margin-bottom: 15px;">👥 Пассажиры</h3>
                        <div id="passengers-container">
                            <div class="passenger-item" style="display: flex; gap: 10px; margin-bottom: 10px;">
                                <input type="text" class="passenger-name" placeholder="ФИО пассажира" style="flex: 1; padding: 10px; border: 1px solid #ddd; border-radius: 4px;">
                                <input type="tel" class="passenger-phone" placeholder="Телефон" style="flex: 1; padding: 10px; border: 1px solid #ddd; border-radius: 4px;">
                                <button type="button" onclick="applicationsSystem.removePassenger(this)" style="padding: 10px; background: #dc3545; color: white; border: none; border-radius: 4px; cursor: pointer;">🗑️</button>
                            </div>
                        </div>
                        <button type="button" onclick="applicationsSystem.addPassenger()" style="padding: 8px 15px; background: #6c757d; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 14px;">
                            ➕ Добавить пассажира
                        </button>
                    </div>

                    <div class="form-section">
                        <h3 style="color: #333; margin-bottom: 15px;">💬 Комментарий</h3>
                        <textarea id="driver-comment" class="form-control" placeholder="Особые пожелания, детали поездки..." style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px; min-height: 80px;"></textarea>
                    </div>
                </div>

                <div class="modal-footer" style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 25px; border-top: 1px solid #eee; padding-top: 20px;">
                    <button onclick="applicationsSystem.closeModal('create-application-modal')" style="padding: 12px 25px; border: 1px solid #ddd; background: white; border-radius: 4px; cursor: pointer; font-size: 14px;">❌ Отмена</button>
                    <button onclick="applicationsSystem.createApplication()" style="padding: 12px 25px; background: #28a745; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 14px; font-weight: bold;">✅ Создать заявку</button>
                </div>
            </div>
        </div>
        `;

        document.body.insertAdjacentHTML('beforeend', modalHTML);
        
        // Установка завтрашней даты по умолчанию
        const tomorrow = new Date();
        tomorrow.setDate(tomorrow.getDate() + 1);
        tomorrow.setHours(10, 0, 0, 0);
        document.getElementById('trip-date').value = tomorrow.toISOString().slice(0, 16);
        
        console.log('✅ Модальное окно создания заявки открыто');
    }

    addRoutePoint() {
        const container = document.getElementById('additional-routes-container');
        const pointNumber = container.children.length + 3; // A=1, B=2, C=3...
        const pointLetter = String.fromCharCode(64 + pointNumber); // A, B, C...
        
        const routeHTML = `
            <div class="route-point" style="display: flex; align-items: center; gap: 10px; margin-bottom: 10px;">
                <label style="min-width: 80px; font-weight: bold; color: #333;">Точка ${pointLetter}</label>
                <input type="text" class="route-address" placeholder="Адрес точки ${pointLetter}" style="flex: 1; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px;">
                <button type="button" onclick="applicationsSystem.removeRoutePoint(this)" style="padding: 10px; background: #dc3545; color: white; border: none; border-radius: 4px; cursor: pointer;">🗑️</button>
            </div>
        `;
        
        container.insertAdjacentHTML('beforeend', routeHTML);
    }

    removeRoutePoint(button) {
        button.closest('.route-point').remove();
    }

    addPassenger() {
        const container = document.getElementById('passengers-container');
        
        const passengerHTML = `
            <div class="passenger-item" style="display: flex; gap: 10px; margin-bottom: 10px;">
                <input type="text" class="passenger-name" placeholder="ФИО пассажира" style="flex: 1; padding: 10px; border: 1px solid #ddd; border-radius: 4px;">
                <input type="tel" class="passenger-phone" placeholder="Телефон" style="flex: 1; padding: 10px; border: 1px solid #ddd; border-radius: 4px;">
                <button type="button" onclick="applicationsSystem.removePassenger(this)" style="padding: 10px; background: #dc3545; color: white; border: none; border-radius: 4px; cursor: pointer;">🗑️</button>
            </div>
        `;
        
        container.insertAdjacentHTML('beforeend', passengerHTML);
    }

    removePassenger(button) {
        button.closest('.passenger-item').remove();
    }

    async createApplication() {
        console.log('🎯 Начало создания заявки...');
        
        // Получаем основные данные из формы
        const customerName = document.getElementById('customer-name').value.trim();
        const customerPhone = document.getElementById('customer-phone').value.trim();
        const tripDate = document.getElementById('trip-date').value;
        const serviceType = document.getElementById('service-type').value;
        const vehicleClass = document.getElementById('vehicle-class').value;
        const orderAmount = document.getElementById('order-amount').value;
        const routeFrom = document.getElementById('route-from').value.trim();
        const routeTo = document.getElementById('route-to').value.trim();
        const driverComment = document.getElementById('driver-comment').value.trim();

        // Получаем дополнительные точки маршрута
        const additionalRoutes = [];
        const routePoints = document.querySelectorAll('.route-address');
        routePoints.forEach(input => {
            if (input.value.trim()) {
                additionalRoutes.push(input.value.trim());
            }
        });

        // Получаем пассажиров
        const passengers = [];
        const passengerItems = document.querySelectorAll('.passenger-item');
        passengerItems.forEach(item => {
            const name = item.querySelector('.passenger-name').value.trim();
            const phone = item.querySelector('.passenger-phone').value.trim();
            if (name) {
                passengers.push({ name, phone });
            }
        });

        console.log('📋 Данные формы:', {
            customerName, customerPhone, tripDate, serviceType, vehicleClass, orderAmount, 
            routeFrom, routeTo, additionalRoutes, passengers
        });

        // ВАЛИДАЦИЯ
        if (!customerName || !customerPhone || !tripDate || !serviceType || !vehicleClass || !orderAmount || !routeFrom || !routeTo) {
            this.showNotification('❌ Заполните все обязательные поля!', 'error');
            return;
        }

        if (orderAmount <= 0) {
            this.showNotification('❌ Введите корректную стоимость!', 'error');
            return;
        }

        try {
            console.log('📤 Отправка данных на сервер...');
            
            // Формируем все маршруты
            const allRoutes = [routeFrom, routeTo, ...additionalRoutes].filter(route => route);
            
            const requestData = {
                customer_name: customerName,
                customer_phone: customerPhone,
                trip_date: tripDate,
                service_type: serviceType,
                vehicle_class: vehicleClass,
                order_amount: parseFloat(orderAmount),
                routes: allRoutes,
                passengers: passengers,
                driver_comment: driverComment,
                status: 'new',
                created_by: window.authSystem?.currentUser?.id || 1
            };

            const response = await fetch('/api/applications.php?action=create', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(requestData)
            });

            const result = await response.json();
            console.log('📥 Ответ сервера:', result);

            if (result.success) {
                console.log('✅ Заявка успешно создана, номер:', result.application_number);
                this.closeModal('create-application-modal');
                await this.loadApplications();
                this.showNotification(`✅ Заявка успешно создана! Номер: ${result.application_number}`, 'success');
            } else {
                console.error('❌ Ошибка сервера:', result.message);
                this.showNotification('❌ Ошибка: ' + result.message, 'error');
            }

        } catch (error) {
            console.error('❌ Ошибка сети:', error);
            this.showNotification('❌ Ошибка сети при создании заявки', 'error');
        }
    }

    closeModal(modalId) {
        console.log('🔒 Закрытие модального окна:', modalId);
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.remove();
            console.log('✅ Модальное окно закрыто');
        }
    }

    openAssignDriverModal(applicationId) {
        console.log('👨‍💼 Открытие модального окна назначения водителя для заявки:', applicationId);
        
        const application = this.applications.find(app => app.id === applicationId);
        if (!application) {
            console.error('❌ Заявка не найдена:', applicationId);
            return;
        }

        const modalHTML = `
        <div class="modal show" id="assign-driver-modal" style="display: block; background: rgba(0,0,0,0.5); position: fixed; top: 0; left: 0; right: 0; bottom: 0; z-index: 1000;">
            <div class="modal-content" style="max-width: 500px; margin: 50px auto; background: white; padding: 20px; border-radius: 8px;">
                <div class="modal-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 1px solid #eee; padding-bottom: 15px;">
                    <h2 style="margin: 0; color: #333;">👨‍💼 Назначить водителя</h2>
                    <button onclick="applicationsSystem.closeModal('assign-driver-modal')" style="background: none; border: none; font-size: 24px; cursor: pointer; color: #666;">×</button>
                </div>
                
                <div class="modal-body">
                    <div style="margin-bottom: 15px;">
                        <p><strong>Заявка:</strong> ${application.application_number}</p>
                        <p><strong>Клиент:</strong> ${application.customer_name}</p>
                        <p><strong>Маршрут:</strong> ${application.routes && application.routes.length >= 2 ? 
                            application.routes[0].address + ' → ' + application.routes[application.routes.length-1].address : 
                            'Не указан'}</p>
                    </div>
                    
                    <div style="margin-bottom: 15px;">
                        <label style="display: block; margin-bottom: 5px; font-weight: bold; color: #333;">Выберите водителя:</label>
                        <select id="driver-select" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px;">
                            <option value="">-- Выберите водителя --</option>
                            <option value="1">Иванов Алексей (Комфорт)</option>
                            <option value="2">Петров Дмитрий (Бизнес)</option>
                            <option value="3">Сидоров Михаил (Премиум)</option>
                        </select>
                    </div>
                </div>

                <div class="modal-footer" style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px; border-top: 1px solid #eee; padding-top: 20px;">
                    <button onclick="applicationsSystem.closeModal('assign-driver-modal')" style="padding: 10px 20px; border: 1px solid #ddd; background: white; border-radius: 4px; cursor: pointer;">❌ Отмена</button>
                    <button onclick="applicationsSystem.assignDriver(${applicationId})" style="padding: 10px 20px; background: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer;">✅ Назначить</button>
                </div>
            </div>
        </div>
        `;

        document.body.insertAdjacentHTML('beforeend', modalHTML);
        console.log('✅ Модальное окно назначения водителя открыто');
    }

    async assignDriver(applicationId) {
        console.log('🎯 Начало назначения водителя для заявки:', applicationId);
        
        const driverSelect = document.getElementById('driver-select');
        const driverId = driverSelect.value;

        if (!driverId) {
            this.showNotification('❌ Выберите водителя!', 'error');
            console.error('❌ Водитель не выбран');
            return;
        }

        try {
            console.log('📤 Отправка данных о назначении водителя...');
            
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
                console.log('✅ Водитель успешно назначен');
                this.closeModal('assign-driver-modal');
                await this.loadApplications(); // Перезагружаем список
                this.showNotification('✅ Водитель успешно назначен на заявку!', 'success');
            } else {
                console.error('❌ Ошибка сервера:', result.message);
                this.showNotification('❌ Ошибка: ' + result.message, 'error');
            }

        } catch (error) {
            console.error('❌ Ошибка сети:', error);
            this.showNotification('❌ Ошибка сети при назначении водителя', 'error');
        }
    }

    openAssignVehicleModal(applicationId) {
        console.log('🚗 Открытие модального окна назначения авто для заявки:', applicationId);
        
        const application = this.applications.find(app => app.id === applicationId);
        if (!application) {
            console.error('❌ Заявка не найдена:', applicationId);
            return;
        }

        const modalHTML = `
        <div class="modal show" id="assign-vehicle-modal" style="display: block; background: rgba(0,0,0,0.5); position: fixed; top: 0; left: 0; right: 0; bottom: 0; z-index: 1000;">
            <div class="modal-content" style="max-width: 500px; margin: 50px auto; background: white; padding: 20px; border-radius: 8px;">
                <div class="modal-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 1px solid #eee; padding-bottom: 15px;">
                    <h2 style="margin: 0; color: #333;">🚗 Назначить автомобиль</h2>
                    <button onclick="applicationsSystem.closeModal('assign-vehicle-modal')" style="background: none; border: none; font-size: 24px; cursor: pointer; color: #666;">×</button>
                </div>
                
                <div class="modal-body">
                    <div style="margin-bottom: 15px;">
                        <p><strong>Заявка:</strong> ${application.application_number}</p>
                        <p><strong>Класс авто:</strong> ${application.tariff || application.vehicle_class}</p>
                        <p><strong>Время:</strong> ${this.formatDate(application.trip_date)}</p>
                    </div>
                    
                    <div style="margin-bottom: 15px;">
                        <label style="display: block; margin-bottom: 5px; font-weight: bold; color: #333;">Выберите автомобиль:</label>
                        <select id="vehicle-select" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px;">
                            <option value="">-- Выберите автомобиль --</option>
                            <option value="1">Toyota Camry (A123BC777) - Комфорт</option>
                            <option value="2">Mercedes E-Class (B456DE777) - Бизнес</option>
                            <option value="3">BMW 7-series (C789FG777) - Премиум</option>
                        </select>
                    </div>
                </div>

                <div class="modal-footer" style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px; border-top: 1px solid #eee; padding-top: 20px;">
                    <button onclick="applicationsSystem.closeModal('assign-vehicle-modal')" style="padding: 10px 20px; border: 1px solid #ddd; background: white; border-radius: 4px; cursor: pointer;">❌ Отмена</button>
                    <button onclick="applicationsSystem.assignVehicle(${applicationId})" style="padding: 10px 20px; background: #28a745; color: white; border: none; border-radius: 4px; cursor: pointer;">✅ Назначить</button>
                </div>
            </div>
        </div>
        `;

        document.body.insertAdjacentHTML('beforeend', modalHTML);
        console.log('✅ Модальное окно назначения авто открыто');
    }

    async assignVehicle(applicationId) {
        console.log('🎯 Начало назначения авто для заявки:', applicationId);
        
        const vehicleSelect = document.getElementById('vehicle-select');
        const vehicleId = vehicleSelect.value;

        if (!vehicleId) {
            this.showNotification('❌ Выберите автомобиль!', 'error');
            console.error('❌ Автомобиль не выбран');
            return;
        }

        try {
            console.log('📤 Отправка данных о назначении авто...');
            
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
                console.log('✅ Автомобиль успешно назначен');
                this.closeModal('assign-vehicle-modal');
                await this.loadApplications(); // Перезагружаем список
                this.showNotification('✅ Автомобиль успешно назначен на заявку!', 'success');
            } else {
                console.error('❌ Ошибка сервера:', result.message);
                this.showNotification('❌ Ошибка: ' + result.message, 'error');
            }

        } catch (error) {
            console.error('❌ Ошибка сети:', error);
            this.showNotification('❌ Ошибка сети при назначении автомобиля', 'error');
        }
    }

    viewApplication(applicationId) {
        console.log('👁️ Просмотр заявки:', applicationId);
        
        const application = this.applications.find(app => app.id === applicationId);
        if (!application) {
            console.error('❌ Заявка не найдена:', applicationId);
            return;
        }

        const modalHTML = `
        <div class="modal show" id="view-application-modal" style="display: block; background: rgba(0,0,0,0.5); position: fixed; top: 0; left: 0; right: 0; bottom: 0; z-index: 1000;">
            <div class="modal-content" style="max-width: 600px; margin: 50px auto; background: white; padding: 20px; border-radius: 8px;">
                <div class="modal-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 1px solid #eee; padding-bottom: 15px;">
                    <h2 style="margin: 0; color: #333;">📋 Заявка ${application.application_number}</h2>
                    <button onclick="applicationsSystem.closeModal('view-application-modal')" style="background: none; border: none; font-size: 24px; cursor: pointer; color: #666;">×</button>
                </div>
                
                <div class="modal-body">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 20px;">
                        <div>
                            <h4 style="margin: 0 0 10px 0; color: #333;">👤 Клиент</h4>
                            <p style="margin: 5px 0;"><strong>ФИО:</strong> ${application.customer_name}</p>
                            <p style="margin: 5px 0;"><strong>Телефон:</strong> ${application.customer_phone}</p>
                        </div>
                        <div>
                            <h4 style="margin: 0 0 10px 0; color: #333;">🚗 Поездка</h4>
                            <p style="margin: 5px 0;"><strong>Дата/время:</strong> ${this.formatDate(application.trip_date)}</p>
                            <p style="margin: 5px 0;"><strong>Тип услуги:</strong> ${application.service_type}</p>
                            <p style="margin: 5px 0;"><strong>Класс авто:</strong> ${application.tariff || application.vehicle_class}</p>
                        </div>
                    </div>

                    <div style="margin-bottom: 20px;">
                        <h4 style="margin: 0 0 10px 0; color: #333;">📍 Маршрут</h4>
                        ${application.routes && application.routes.length >= 2 ? 
                            `<p style="margin: 5px 0;"><strong>Откуда:</strong> ${application.routes[0].address}</p>
                             <p style="margin: 5px 0;"><strong>Куда:</strong> ${application.routes[application.routes.length-1].address}</p>` :
                            '<p style="margin: 5px 0; color: #666;">Маршрут не указан</p>'
                        }
                    </div>

                    <div style="margin-bottom: 20px;">
                        <h4 style="margin: 0 0 10px 0; color: #333;">💰 Стоимость</h4>
                        <p style="margin: 5px 0; font-size: 18px; font-weight: bold; color: #28a745;">${application.order_amount || 0} ₽</p>
                    </div>

                    <div>
                        <h4 style="margin: 0 0 10px 0; color: #333;">📊 Статус</h4>
                        <span class="status status-${application.status}" style="padding: 5px 10px; border-radius: 4px; font-size: 12px; font-weight: bold;">
                            ${this.getStatusText(application.status)}
                        </span>
                    </div>
                </div>

                <div class="modal-footer" style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 25px; border-top: 1px solid #eee; padding-top: 20px;">
                    <button onclick="applicationsSystem.closeModal('view-application-modal')" style="padding: 10px 20px; border: 1px solid #ddd; background: white; border-radius: 4px; cursor: pointer;">❌ Закрыть</button>
                </div>
            </div>
        </div>
        `;

        document.body.insertAdjacentHTML('beforeend', modalHTML);
        console.log('✅ Модальное окно просмотра заявки открыто');
    }

    renderApplicationsTable() {
        const tbody = document.getElementById('applications-table-body');
        if (!tbody) {
            console.error('❌ Не найдено tbody для таблицы заявок');
            return;
        }

        console.log('📊 Отрисовка таблицы заявок, количество:', this.applications.length);

        if (this.applications.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="7" style="text-align: center; padding: 40px; color: #666;">
                        <div style="font-size: 48px; margin-bottom: 10px;">📋</div>
                        <div style="font-size: 16px; margin-bottom: 15px;">Нет заявок</div>
                        <button class="btn btn-primary" data-action="create-application" 
                                style="padding: 10px 20px; background: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer;">
                            ➕ Создать первую заявку
                        </button>
                    </td>
                </tr>
            `;
            return;
        }

        tbody.innerHTML = this.applications.map(app => `
            <tr>
                <td>
                    <div class="checkbox-container">
                        <input type="checkbox" class="checkbox" onchange="applicationsSystem.toggleApplicationSelection(${app.id})">
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
                        <span class="application-preview-item">${app.tariff || app.vehicle_class}</span>
                        <span class="application-preview-item">${app.service_type}</span>
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

    toggleApplicationSelection(id) {
        if (this.selectedApplications.has(id)) {
            this.selectedApplications.delete(id);
        } else {
            this.selectedApplications.add(id);
        }
        console.log('🔘 Выбранные заявки:', this.selectedApplications);
    }

    formatDate(dateString) {
        if (!dateString) return '-';
        try {
            const date = new Date(dateString);
            return date.toLocaleDateString('ru-RU') + '<br><small>' + 
                   date.toLocaleTimeString('ru-RU', {hour: '2-digit', minute: '2-digit'}) + '</small>';
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
            'cancelled': 'Отменена',
            'assigned': 'Назначена'
        };
        return statuses[status] || status;
    }

    showNotification(message, type = 'success') {
        console.log('🔔 Уведомление:', message, type);
        
        // Создаем красивое уведомление
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px 20px;
            border-radius: 8px;
            color: white;
            z-index: 10000;
            min-width: 300px;
            max-width: 500px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            animation: slideInRight 0.3s ease-out;
            font-family: Arial, sans-serif;
        `;
        
        if (type === 'success') {
            notification.style.background = 'linear-gradient(135deg, #4CAF50, #45a049)';
        } else if (type === 'error') {
            notification.style.background = 'linear-gradient(135deg, #f44336, #da190b)';
        } else if (type === 'warning') {
            notification.style.background = 'linear-gradient(135deg, #ff9800, #e68900)';
        } else {
            notification.style.background = 'linear-gradient(135deg, #2196F3, #0b7dda)';
        }
        
        notification.innerHTML = `
            <div style="display: flex; align-items: center; justify-content: space-between;">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <span style="font-size: 18px;">
                        ${type === 'success' ? '✅' : type === 'error' ? '❌' : type === 'warning' ? '⚠️' : '💡'}
                    </span>
                    <span>${message}</span>
                </div>
                <button onclick="this.parentElement.parentElement.remove()" 
                        style="background: none; border: none; color: white; cursor: pointer; font-size: 18px; margin-left: 10px; padding: 0; width: 24px; height: 24px; display: flex; align-items: center; justify-content: center;">
                    ×
                </button>
            </div>
        `;
        
        // Добавляем стили для анимации
        if (!document.querySelector('#notification-styles')) {
            const style = document.createElement('style');
            style.id = 'notification-styles';
            style.textContent = `
                @keyframes slideInRight {
                    from {
                        transform: translateX(100%);
                        opacity: 0;
                    }
                    to {
                        transform: translateX(0);
                        opacity: 1;
                    }
                }
                
                .notification {
                    transition: all 0.3s ease;
                }
            `;
            document.head.appendChild(style);
        }
        
        document.body.appendChild(notification);
        
        // Автоматическое удаление через 5 секунд
        setTimeout(() => {
            if (notification.parentNode) {
                notification.style.animation = 'slideInRight 0.3s ease-out reverse';
                setTimeout(() => {
                    if (notification.parentNode) {
                        notification.parentNode.removeChild(notification);
                    }
                }, 300);
            }
        }, 5000);
    }
}

// Создаем глобальный объект системы заявок
window.applicationsSystem = new ApplicationsSystem();

// Инициализация при загрузке страницы
document.addEventListener('DOMContentLoaded', function() {
    console.log('🏁 DOM загружен, запуск CRM...');
    if (typeof applicationsSystem !== 'undefined') {
        applicationsSystem.init();
    } else {
        console.error('❌ applicationsSystem не определен');
    }
});