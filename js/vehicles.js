// js/vehicles.js - ПОЛНАЯ РАБОЧАЯ ВЕРСИЯ
class VehiclesSystem {
    constructor() {
        this.vehicles = [];
        this.currentVehicle = null;
        this.filters = {};
    }

    async init() {
        console.log('🚗 Инициализация системы автомобилей...');
        await this.loadVehicles();
        this.setupEventListeners();
        this.renderVehiclesTable();
    }

    async loadVehicles(filters = {}) {
        try {
            console.log('📥 Загрузка автомобилей...');
            
            const params = new URLSearchParams();
            if (filters.status) params.append('status', filters.status);
            if (filters.class) params.append('class', filters.class);
            if (filters.brand) params.append('brand', filters.brand);
            
            const response = await fetch(`/api/vehicles.php?${params.toString()}`);
            const result = await response.json();
            
            if (result.success) {
                this.vehicles = result.data.vehicles || [];
                console.log('✅ Загружено автомобилей:', this.vehicles.length);
            } else {
                console.error('❌ Ошибка загрузки:', result.message);
                this.vehicles = [];
            }
            
            this.renderVehiclesTable();
        } catch (error) {
            console.error('❌ Ошибка загрузки автомобилей:', error);
            this.vehicles = [];
            this.renderVehiclesTable();
        }
    }

    setupEventListeners() {
        console.log('🔧 Настройка обработчиков автомобилей...');
        
        // Обработчик создания автомобиля
        document.addEventListener('click', (e) => {
            if (e.target.closest('[data-action="create-vehicle"]')) {
                console.log('🎯 Нажата кнопка создания автомобиля');
                this.openCreateModal();
            }
        });

        // Обработчики фильтров
        document.addEventListener('change', (e) => {
            if (e.target.id === 'vehicle-status-filter') {
                this.filters.status = e.target.value;
                this.loadVehicles(this.filters);
            }
            if (e.target.id === 'vehicle-class-filter') {
                this.filters.class = e.target.value;
                this.loadVehicles(this.filters);
            }
            if (e.target.id === 'vehicle-brand-filter') {
                this.filters.brand = e.target.value;
                this.loadVehicles(this.filters);
            }
        });
    }

    openCreateModal(vehicleData = null) {
        console.log('📝 Открытие модального окна автомобиля');
        this.currentVehicle = vehicleData;
        
        const isEdit = !!vehicleData;
        const modalTitle = isEdit ? '✏️ Редактировать автомобиль' : '🚗 Добавить автомобиль';
        const buttonText = isEdit ? '💾 Сохранить изменения' : '✅ Добавить автомобиль';

        const modalHTML = `
        <div class="modal show" id="vehicle-modal" style="display: block; background: rgba(0,0,0,0.5); position: fixed; top: 0; left: 0; right: 0; bottom: 0; z-index: 1000;">
            <div class="modal-content" style="max-width: 800px; margin: 20px auto; background: white; padding: 20px; border-radius: 8px; max-height: 95vh; overflow-y: auto;">
                <div class="modal-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 1px solid #eee; padding-bottom: 15px;">
                    <h2 style="margin: 0; color: #333;">${modalTitle}</h2>
                    <button onclick="vehiclesSystem.closeModal()" style="background: none; border: none; font-size: 24px; cursor: pointer; color: #666;">×</button>
                </div>
                
                <div class="modal-body">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                        <!-- Левая колонка - Основная информация -->
                        <div>
                            <h3 style="color: #333; margin-bottom: 15px;">🚗 Основная информация</h3>
                            
                            <div style="display: grid; gap: 15px;">
                                <div>
                                    <label style="display: block; margin-bottom: 5px; font-weight: bold; color: #333;">Марка *</label>
                                    <input type="text" id="vehicle-brand" value="${vehicleData?.brand || ''}" 
                                           style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px;" 
                                           placeholder="Toyota">
                                </div>
                                
                                <div>
                                    <label style="display: block; margin-bottom: 5px; font-weight: bold; color: #333;">Модель *</label>
                                    <input type="text" id="vehicle-model" value="${vehicleData?.model || ''}" 
                                           style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px;" 
                                           placeholder="Camry">
                                </div>
                                
                                <div>
                                    <label style="display: block; margin-bottom: 5px; font-weight: bold; color: #333;">Класс *</label>
                                    <select id="vehicle-class" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px;">
                                        <option value="">Выберите класс</option>
                                        <option value="standard" ${vehicleData?.class === 'standard' ? 'selected' : ''}>Стандарт</option>
                                        <option value="comfort" ${vehicleData?.class === 'comfort' ? 'selected' : ''}>Комфорт</option>
                                        <option value="business" ${vehicleData?.class === 'business' ? 'selected' : ''}>Бизнес</option>
                                        <option value="premium" ${vehicleData?.class === 'premium' ? 'selected' : ''}>Премиум</option>
                                        <option value="minivan5" ${vehicleData?.class === 'minivan5' ? 'selected' : ''}>Минивэн (5 мест)</option>
                                        <option value="minivan6" ${vehicleData?.class === 'minivan6' ? 'selected' : ''}>Минивэн (6 мест)</option>
                                        <option value="microbus8" ${vehicleData?.class === 'microbus8' ? 'selected' : ''}>Микроавтобус (8 мест)</option>
                                        <option value="microbus10" ${vehicleData?.class === 'microbus10' ? 'selected' : ''}>Микроавтобус (10 мест)</option>
                                    </select>
                                </div>
                                
                                <div>
                                    <label style="display: block; margin-bottom: 5px; font-weight: bold; color: #333;">Гос. номер *</label>
                                    <input type="text" id="vehicle-license-plate" value="${vehicleData?.license_plate || ''}" 
                                           style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px;" 
                                           placeholder="A123BC777">
                                </div>
                                
                                <div>
                                    <label style="display: block; margin-bottom: 5px; font-weight: bold; color: #333;">Год выпуска</label>
                                    <input type="number" id="vehicle-year" value="${vehicleData?.year || ''}" 
                                           style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px;" 
                                           placeholder="2023" min="2000" max="2030">
                                </div>
                                
                                <div>
                                    <label style="display: block; margin-bottom: 5px; font-weight: bold; color: #333;">Пробег (км)</label>
                                    <input type="number" id="vehicle-mileage" value="${vehicleData?.mileage || '0'}" 
                                           style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px;" 
                                           placeholder="0" min="0">
                                </div>
                            </div>
                        </div>

                        <!-- Правая колонка - Дополнительная информация -->
                        <div>
                            <h3 style="color: #333; margin-bottom: 15px;">🎨 Внешний вид и статус</h3>
                            
                            <div style="display: grid; gap: 15px;">
                                <div>
                                    <label style="display: block; margin-bottom: 5px; font-weight: bold; color: #333;">Количество мест</label>
                                    <input type="number" id="vehicle-passenger-seats" value="${vehicleData?.passenger_seats || '4'}" 
                                           style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px;" 
                                           placeholder="4" min="2" max="50">
                                </div>
                                
                                <div>
                                    <label style="display: block; margin-bottom: 5px; font-weight: bold; color: #333;">Тип салона</label>
                                    <select id="vehicle-salon-type" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px;">
                                        <option value="">Не указан</option>
                                        <option value="leather" ${vehicleData?.salon_type === 'leather' ? 'selected' : ''}>Кожа</option>
                                        <option value="alcantara" ${vehicleData?.salon_type === 'alcantara' ? 'selected' : ''}>Алькантара</option>
                                        <option value="velour" ${vehicleData?.salon_type === 'velour' ? 'selected' : ''}>Велюр</option>
                                        <option value="fabric" ${vehicleData?.salon_type === 'fabric' ? 'selected' : ''}>Ткань</option>
                                    </select>
                                </div>
                                
                                <div>
                                    <label style="display: block; margin-bottom: 5px; font-weight: bold; color: #333;">Цвет салона</label>
                                    <select id="vehicle-salon-color" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px;">
                                        <option value="">Не указан</option>
                                        <option value="black" ${vehicleData?.salon_color === 'black' ? 'selected' : ''}>Черный</option>
                                        <option value="brown" ${vehicleData?.salon_color === 'brown' ? 'selected' : ''}>Коричневый</option>
                                        <option value="beige" ${vehicleData?.salon_color === 'beige' ? 'selected' : ''}>Бежевый</option>
                                        <option value="gray" ${vehicleData?.salon_color === 'gray' ? 'selected' : ''}>Серый</option>
                                    </select>
                                </div>
                                
                                <div>
                                    <label style="display: block; margin-bottom: 5px; font-weight: bold; color: #333;">Цвет кузова</label>
                                    <select id="vehicle-body-color" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px;">
                                        <option value="">Не указан</option>
                                        <option value="black" ${vehicleData?.body_color === 'black' ? 'selected' : ''}>Черный</option>
                                        <option value="white" ${vehicleData?.body_color === 'white' ? 'selected' : ''}>Белый</option>
                                        <option value="silver" ${vehicleData?.body_color === 'silver' ? 'selected' : ''}>Серебристый</option>
                                        <option value="gray" ${vehicleData?.body_color === 'gray' ? 'selected' : ''}>Серый</option>
                                        <option value="blue" ${vehicleData?.body_color === 'blue' ? 'selected' : ''}>Синий</option>
                                        <option value="red" ${vehicleData?.body_color === 'red' ? 'selected' : ''}>Красный</option>
                                    </select>
                                </div>
                                
                                <div>
                                    <label style="display: block; margin-bottom: 5px; font-weight: bold; color: #333;">Статус</label>
                                    <select id="vehicle-status" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px;">
                                        <option value="working" ${vehicleData?.status === 'working' ? 'selected' : ''}>На ходу</option>
                                        <option value="repair" ${vehicleData?.status === 'repair' ? 'selected' : ''}>В ремонте</option>
                                        <option value="broken" ${vehicleData?.status === 'broken' ? 'selected' : ''}>Битый</option>
                                    </select>
                                </div>

                                <div>
                                    <label style="display: block; margin-bottom: 5px; font-weight: bold; color: #333;">Фотография</label>
                                    <input type="file" id="vehicle-photo" accept="image/*" 
                                           style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px;"
                                           onchange="vehiclesSystem.previewPhoto(this)">
                                    <div id="vehicle-photo-preview" style="margin-top: 10px; display: ${vehicleData?.photo_url ? 'block' : 'none'}">
                                        ${vehicleData?.photo_url ? 
                                            `<img src="${vehicleData.photo_url}" style="max-width: 150px; max-height: 150px; border-radius: 4px;">` : 
                                            ''
                                        }
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer" style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 25px; border-top: 1px solid #eee; padding-top: 20px;">
                    <button onclick="vehiclesSystem.closeModal()" style="padding: 12px 25px; border: 1px solid #ddd; background: white; border-radius: 4px; cursor: pointer; font-size: 14px;">❌ Отмена</button>
                    <button onclick="vehiclesSystem.${isEdit ? 'updateVehicle' : 'createVehicle'}()" 
                            style="padding: 12px 25px; background: #28a745; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 14px; font-weight: bold;">
                        ${buttonText}
                    </button>
                </div>
            </div>
        </div>
        `;

        document.body.insertAdjacentHTML('beforeend', modalHTML);
        console.log('✅ Модальное окно автомобиля открыто');
    }

    previewPhoto(input) {
        const preview = document.getElementById('vehicle-photo-preview');
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                preview.innerHTML = `<img src="${e.target.result}" style="max-width: 150px; max-height: 150px; border-radius: 4px;">`;
                preview.style.display = 'block';
            }
            reader.readAsDataURL(input.files[0]);
        }
    }

    async createVehicle() {
        console.log('🎯 Создание нового автомобиля...');
        
        // Собираем данные
        const vehicleData = {
            brand: document.getElementById('vehicle-brand').value.trim(),
            model: document.getElementById('vehicle-model').value.trim(),
            class: document.getElementById('vehicle-class').value,
            license_plate: document.getElementById('vehicle-license-plate').value.trim(),
            year: document.getElementById('vehicle-year').value ? parseInt(document.getElementById('vehicle-year').value) : null,
            passenger_seats: document.getElementById('vehicle-passenger-seats').value ? parseInt(document.getElementById('vehicle-passenger-seats').value) : 4,
            mileage: document.getElementById('vehicle-mileage').value ? parseInt(document.getElementById('vehicle-mileage').value) : 0,
            salon_type: document.getElementById('vehicle-salon-type').value,
            salon_color: document.getElementById('vehicle-salon-color').value,
            body_color: document.getElementById('vehicle-body-color').value,
            status: document.getElementById('vehicle-status').value,
            created_by: window.authSystem?.currentUser?.id || 1
        };

        // Валидация
        if (!vehicleData.brand || !vehicleData.model || !vehicleData.class || !vehicleData.license_plate) {
            this.showNotification('❌ Заполните обязательные поля: Марка, Модель, Класс, Гос. номер', 'error');
            return;
        }

        // Обработка фото
        const photoInput = document.getElementById('vehicle-photo');
        if (photoInput.files && photoInput.files[0]) {
            const base64Photo = await this.fileToBase64(photoInput.files[0]);
            vehicleData.photo_base64 = base64Photo;
        }

        try {
            console.log('📤 Отправка данных автомобиля...');
            
            const response = await fetch('/api/vehicles.php?action=create', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(vehicleData)
            });

            const result = await response.json();
            console.log('📥 Ответ сервера:', result);

            if (result.success) {
                this.showNotification('✅ Автомобиль успешно создан!', 'success');
                this.closeModal();
                await this.loadVehicles();
            } else {
                this.showNotification('❌ Ошибка: ' + result.message, 'error');
            }

        } catch (error) {
            console.error('❌ Ошибка сети:', error);
            this.showNotification('❌ Ошибка сети при создании автомобиля', 'error');
        }
    }

    async updateVehicle() {
        if (!this.currentVehicle) return;
        
        console.log('🎯 Обновление автомобиля:', this.currentVehicle.id);
        
        // Собираем данные
        const vehicleData = {
            id: this.currentVehicle.id,
            brand: document.getElementById('vehicle-brand').value.trim(),
            model: document.getElementById('vehicle-model').value.trim(),
            class: document.getElementById('vehicle-class').value,
            license_plate: document.getElementById('vehicle-license-plate').value.trim(),
            year: document.getElementById('vehicle-year').value ? parseInt(document.getElementById('vehicle-year').value) : null,
            passenger_seats: document.getElementById('vehicle-passenger-seats').value ? parseInt(document.getElementById('vehicle-passenger-seats').value) : 4,
            mileage: document.getElementById('vehicle-mileage').value ? parseInt(document.getElementById('vehicle-mileage').value) : 0,
            salon_type: document.getElementById('vehicle-salon-type').value,
            salon_color: document.getElementById('vehicle-salon-color').value,
            body_color: document.getElementById('vehicle-body-color').value,
            status: document.getElementById('vehicle-status').value,
            updated_by: window.authSystem?.currentUser?.id || 1
        };

        // Валидация
        if (!vehicleData.brand || !vehicleData.model || !vehicleData.class || !vehicleData.license_plate) {
            this.showNotification('❌ Заполните обязательные поля: Марка, Модель, Класс, Гос. номер', 'error');
            return;
        }

        // Обработка фото
        const photoInput = document.getElementById('vehicle-photo');
        if (photoInput.files && photoInput.files[0]) {
            const base64Photo = await this.fileToBase64(photoInput.files[0]);
            vehicleData.photo_base64 = base64Photo;
        }

        try {
            console.log('📤 Отправка данных для обновления...');
            
            const response = await fetch('/api/vehicles.php?action=update', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(vehicleData)
            });

            const result = await response.json();
            console.log('📥 Ответ сервера:', result);

            if (result.success) {
                this.showNotification('✅ Автомобиль успешно обновлен!', 'success');
                this.closeModal();
                await this.loadVehicles();
            } else {
                this.showNotification('❌ Ошибка: ' + result.message, 'error');
            }

        } catch (error) {
            console.error('❌ Ошибка сети:', error);
            this.showNotification('❌ Ошибка сети при обновлении автомобиля', 'error');
        }
    }

    async deleteVehicle(vehicleId) {
        if (!confirm('Вы уверены, что хотите удалить этот автомобиль?')) {
            return;
        }

        try {
            console.log('🗑️ Удаление автомобиля:', vehicleId);
            
            const response = await fetch('/api/vehicles.php?action=delete', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    id: vehicleId,
                    deleted_by: window.authSystem?.currentUser?.id || 1
                })
            });

            const result = await response.json();
            console.log('📥 Ответ сервера:', result);

            if (result.success) {
                this.showNotification('✅ Автомобиль успешно удален!', 'success');
                await this.loadVehicles();
            } else {
                this.showNotification('❌ Ошибка: ' + result.message, 'error');
            }

        } catch (error) {
            console.error('❌ Ошибка сети:', error);
            this.showNotification('❌ Ошибка сети при удалении автомобиля', 'error');
        }
    }

    fileToBase64(file) {
        return new Promise((resolve, reject) => {
            const reader = new FileReader();
            reader.readAsDataURL(file);
            reader.onload = () => resolve(reader.result);
            reader.onerror = error => reject(error);
        });
    }

    closeModal() {
        const modal = document.getElementById('vehicle-modal');
        if (modal) {
            modal.remove();
            this.currentVehicle = null;
            console.log('✅ Модальное окно автомобиля закрыто');
        }
    }

    renderVehiclesTable() {
        const tbody = document.getElementById('vehicles-table-body');
        if (!tbody) {
            console.error('❌ Не найдено tbody для таблицы автомобилей');
            return;
        }

        console.log('📊 Отрисовка таблицы автомобилей, количество:', this.vehicles.length);

        if (this.vehicles.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="7" style="text-align: center; padding: 40px; color: #666;">
                        <div style="font-size: 48px; margin-bottom: 10px;">🚗</div>
                        <div style="font-size: 16px; margin-bottom: 15px;">Автомобили не найдены</div>
                        <button class="btn btn-primary" data-action="create-vehicle" 
                                style="padding: 10px 20px; background: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer;">
                            🚗 Добавить первый автомобиль
                        </button>
                    </td>
                </tr>
            `;
            return;
        }

        tbody.innerHTML = this.vehicles.map(vehicle => `
            <tr>
                <td>
                    <div style="display: flex; align-items: center; gap: 10px;">
                        ${vehicle.photo_url ? 
                            `<img src="${vehicle.photo_url}" style="width: 50px; height: 40px; border-radius: 4px; object-fit: cover;">` :
                            `<div style="width: 50px; height: 40px; border-radius: 4px; background: #6c757d; display: flex; align-items: center; justify-content: center; color: white; font-size: 12px;">
                                📷
                            </div>`
                        }
                        <div>
                            <strong>${vehicle.brand} ${vehicle.model}</strong>
                            <div style="font-size: 12px; color: #666;">${vehicle.license_plate}</div>
                        </div>
                    </div>
                </td>
                <td>${this.getVehicleClassText(vehicle.class)}</td>
                <td>${vehicle.year || 'Не указан'}</td>
                <td>
                    ${vehicle.driver_first_name ? 
                        `${vehicle.driver_first_name} ${vehicle.driver_last_name}` : 
                        '<span style="color: #666;">Не назначен</span>'
                    }
                </td>
                <td>
                    <span class="status status-${this.getVehicleStatusClass(vehicle.status)}">
                        ${this.getVehicleStatusText(vehicle.status)}
                    </span>
                </td>
                <td>${vehicle.mileage ? `${vehicle.mileage.toLocaleString()} км` : '0 км'}</td>
                <td>
                    <div style="display: flex; gap: 5px; flex-wrap: wrap;">
                        <button class="btn btn-small" onclick="vehiclesSystem.openCreateModal(${JSON.stringify(vehicle).replace(/"/g, '&quot;')})" 
                                style="padding: 5px 10px; background: #007bff; color: white; border: none; border-radius: 3px; cursor: pointer; font-size: 12px;">
                            ✏️
                        </button>
                        <button class="btn btn-small" onclick="vehiclesSystem.viewVehicleDetails(${vehicle.id})" 
                                style="padding: 5px 10px; background: #6c757d; color: white; border: none; border-radius: 3px; cursor: pointer; font-size: 12px;">
                            👁️
                        </button>
                        ${window.authSystem?.currentUser?.role === 'admin' ? 
                            `<button class="btn btn-small" onclick="vehiclesSystem.deleteVehicle(${vehicle.id})" 
                                    style="padding: 5px 10px; background: #dc3545; color: white; border: none; border-radius: 3px; cursor: pointer; font-size: 12px;">
                                🗑️
                            </button>` : 
                            ''
                        }
                    </div>
                </td>
            </tr>
        `).join('');

        console.log('✅ Таблица автомобилей отрисована');
    }

    getVehicleStatusText(status) {
        const statuses = {
            'working': 'На ходу',
            'broken': 'Битый',
            'repair': 'В ремонте'
        };
        return statuses[status] || status;
    }

    getVehicleStatusClass(status) {
        const classMap = {
            'working': 'inwork',
            'broken': 'cancelled',
            'repair': 'confirmed'
        };
        return classMap[status] || 'new';
    }

    getVehicleClassText(vehicleClass) {
        const classes = {
            'standard': 'Стандарт',
            'comfort': 'Комфорт',
            'business': 'Бизнес',
            'premium': 'Премиум',
            'minivan5': 'Минивэн (5 мест)',
            'minivan6': 'Минивэн (6 мест)',
            'microbus8': 'Микроавтобус (8 мест)',
            'microbus10': 'Микроавтобус (10 мест)'
        };
        return classes[vehicleClass] || vehicleClass;
    }

    viewVehicleDetails(vehicleId) {
        const vehicle = this.vehicles.find(v => v.id == vehicleId);
        if (!vehicle) {
            this.showNotification('❌ Автомобиль не найден', 'error');
            return;
        }

        const modalHTML = `
        <div class="modal show" id="view-vehicle-modal" style="display: block; background: rgba(0,0,0,0.5); position: fixed; top: 0; left: 0; right: 0; bottom: 0; z-index: 1000;">
            <div class="modal-content" style="max-width: 600px; margin: 50px auto; background: white; padding: 20px; border-radius: 8px;">
                <div class="modal-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 1px solid #eee; padding-bottom: 15px;">
                    <h2 style="margin: 0; color: #333;">🚗 ${vehicle.brand} ${vehicle.model}</h2>
                    <button onclick="this.closest('.modal').remove()" style="background: none; border: none; font-size: 24px; cursor: pointer; color: #666;">×</button>
                </div>
                
                <div class="modal-body">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 20px;">
                        <div>
                            <h4 style="margin: 0 0 10px 0; color: #333;">📋 Основные данные</h4>
                            <p style="margin: 5px 0;"><strong>Гос. номер:</strong> ${vehicle.license_plate}</p>
                            <p style="margin: 5px 0;"><strong>Класс:</strong> ${this.getVehicleClassText(vehicle.class)}</p>
                            <p style="margin: 5px 0;"><strong>Год:</strong> ${vehicle.year || 'Не указан'}</p>
                            <p style="margin: 5px 0;"><strong>Пробег:</strong> ${vehicle.mileage ? `${vehicle.mileage.toLocaleString()} км` : '0 км'}</p>
                        </div>
                        <div>
                            <h4 style="margin: 0 0 10px 0; color: #333;">🎨 Внешний вид</h4>
                            <p style="margin: 5px 0;"><strong>Цвет кузова:</strong> ${vehicle.body_color || 'Не указан'}</p>
                            <p style="margin: 5px 0;"><strong>Тип салона:</strong> ${vehicle.salon_type || 'Не указан'}</p>
                            <p style="margin: 5px 0;"><strong>Цвет салона:</strong> ${vehicle.salon_color || 'Не указан'}</p>
                            <p style="margin: 5px 0;"><strong>Мест:</strong> ${vehicle.passenger_seats || 4}</p>
                        </div>
                    </div>

                    ${vehicle.driver_first_name ? `
                    <div style="margin-bottom: 20px; padding: 15px; background: #f8f9fa; border-radius: 4px;">
                        <h4 style="margin: 0 0 10px 0; color: #333;">👨‍💼 Закрепленный водитель</h4>
                        <p style="margin: 5px 0;"><strong>Водитель:</strong> ${vehicle.driver_first_name} ${vehicle.driver_last_name}</p>
                    </div>
                    ` : ''}

                    <div style="margin-bottom: 20px;">
                        <h4 style="margin: 0 0 10px 0; color: #333;">📊 Статус</h4>
                        <span class="status status-${this.getVehicleStatusClass(vehicle.status)}">
                            ${this.getVehicleStatusText(vehicle.status)}
                        </span>
                    </div>

                    ${vehicle.photo_url ? `
                    <div>
                        <h4 style="margin: 0 0 10px 0; color: #333;">📷 Фотография</h4>
                        <img src="${vehicle.photo_url}" style="max-width: 100%; max-height: 200px; border-radius: 4px;">
                    </div>
                    ` : ''}
                </div>

                <div class="modal-footer" style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 25px; border-top: 1px solid #eee; padding-top: 20px;">
                    <button onclick="this.closest('.modal').remove()" style="padding: 10px 20px; border: 1px solid #ddd; background: white; border-radius: 4px; cursor: pointer;">❌ Закрыть</button>
                </div>
            </div>
        </div>
        `;

        document.body.insertAdjacentHTML('beforeend', modalHTML);
    }

    showNotification(message, type = 'success') {
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
        } else {
            notification.style.background = 'linear-gradient(135deg, #2196F3, #0b7dda)';
        }
        
        notification.innerHTML = `
            <div style="display: flex; align-items: center; justify-content: space-between;">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <span style="font-size: 18px;">
                        ${type === 'success' ? '✅' : '❌'}
                    </span>
                    <span>${message}</span>
                </div>
                <button onclick="this.parentElement.parentElement.remove()" 
                        style="background: none; border: none; color: white; cursor: pointer; font-size: 18px; margin-left: 10px; padding: 0; width: 24px; height: 24px; display: flex; align-items: center; justify-content: center;">
                    ×
                </button>
            </div>
        `;
        
        document.body.appendChild(notification);
        
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

// Создаем глобальный объект системы автомобилей
window.vehiclesSystem = new VehiclesSystem();

// Инициализация при загрузке страницы
document.addEventListener('DOMContentLoaded', function() {
    console.log('🏁 DOM загружен, запуск системы автомобилей...');
    if (typeof vehiclesSystem !== 'undefined') {
        vehiclesSystem.init();
    } else {
        console.error('❌ vehiclesSystem не определен');
    }
});