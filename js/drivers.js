// js/drivers.js - ПОЛНАЯ РАБОЧАЯ ВЕРСИЯ
class DriversSystem {
    constructor() {
        this.drivers = [];
        this.currentDriver = null;
        this.filters = {};
    }

    async init() {
        console.log('🚀 Инициализация системы водителей...');
        await this.loadDrivers();
        this.setupEventListeners();
        this.renderDriversTable();
    }

    async loadDrivers(filters = {}) {
        try {
            console.log('📥 Загрузка водителей...');
            
            const params = new URLSearchParams();
            if (filters.status) params.append('status', filters.status);
            if (filters.city) params.append('city', filters.city);
            
            const response = await fetch(`/api/drivers.php?${params.toString()}`);
            const result = await response.json();
            
            if (result.success) {
                this.drivers = result.data.drivers || [];
                console.log('✅ Загружено водителей:', this.drivers.length);
            } else {
                console.error('❌ Ошибка загрузки:', result.message);
                this.drivers = [];
            }
            
            this.renderDriversTable();
        } catch (error) {
            console.error('❌ Ошибка загрузки водителей:', error);
            this.drivers = [];
            this.renderDriversTable();
        }
    }

    setupEventListeners() {
        console.log('🔧 Настройка обработчиков водителей...');
        
        // Обработчик создания водителя
        document.addEventListener('click', (e) => {
            if (e.target.closest('[data-action="create-driver"]')) {
                console.log('🎯 Нажата кнопка создания водителя');
                this.openCreateModal();
            }
        });

        // Обработчики фильтров
        document.addEventListener('change', (e) => {
            if (e.target.id === 'driver-status-filter') {
                this.filters.status = e.target.value;
                this.loadDrivers(this.filters);
            }
            if (e.target.id === 'driver-city-filter') {
                this.filters.city = e.target.value;
                this.loadDrivers(this.filters);
            }
        });
    }

    openCreateModal(driverData = null) {
        console.log('📝 Открытие модального окна водителя');
        this.currentDriver = driverData;
        
        const isEdit = !!driverData;
        const modalTitle = isEdit ? '✏️ Редактировать водителя' : '👨‍💼 Добавить водителя';
        const buttonText = isEdit ? '💾 Сохранить изменения' : '✅ Добавить водителя';

        const modalHTML = `
        <div class="modal show" id="driver-modal" style="display: block; background: rgba(0,0,0,0.5); position: fixed; top: 0; left: 0; right: 0; bottom: 0; z-index: 1000;">
            <div class="modal-content" style="max-width: 800px; margin: 20px auto; background: white; padding: 20px; border-radius: 8px; max-height: 95vh; overflow-y: auto;">
                <div class="modal-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 1px solid #eee; padding-bottom: 15px;">
                    <h2 style="margin: 0; color: #333;">${modalTitle}</h2>
                    <button onclick="driversSystem.closeModal()" style="background: none; border: none; font-size: 24px; cursor: pointer; color: #666;">×</button>
                </div>
                
                <div class="modal-body">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                        <!-- Левая колонка - Основная информация -->
                        <div>
                            <h3 style="color: #333; margin-bottom: 15px;">👤 Основная информация</h3>
                            
                            <div style="display: grid; gap: 15px;">
                                <div>
                                    <label style="display: block; margin-bottom: 5px; font-weight: bold; color: #333;">Фамилия *</label>
                                    <input type="text" id="driver-last-name" value="${driverData?.last_name || ''}" 
                                           style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px;" 
                                           placeholder="Иванов">
                                </div>
                                
                                <div>
                                    <label style="display: block; margin-bottom: 5px; font-weight: bold; color: #333;">Имя *</label>
                                    <input type="text" id="driver-first-name" value="${driverData?.first_name || ''}" 
                                           style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px;" 
                                           placeholder="Иван">
                                </div>
                                
                                <div>
                                    <label style="display: block; margin-bottom: 5px; font-weight: bold; color: #333;">Отчество</label>
                                    <input type="text" id="driver-middle-name" value="${driverData?.middle_name || ''}" 
                                           style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px;" 
                                           placeholder="Иванович">
                                </div>
                                
                                <div>
                                    <label style="display: block; margin-bottom: 5px; font-weight: bold; color: #333;">Телефон *</label>
                                    <input type="tel" id="driver-phone" value="${driverData?.phone || ''}" 
                                           style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px;" 
                                           placeholder="+79991234567">
                                </div>
                                
                                <div>
                                    <label style="display: block; margin-bottom: 5px; font-weight: bold; color: #333;">Доп. телефон</label>
                                    <input type="tel" id="driver-phone-secondary" value="${driverData?.phone_secondary || ''}" 
                                           style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px;" 
                                           placeholder="+79991234568">
                                </div>
                                
                                <div>
                                    <label style="display: block; margin-bottom: 5px; font-weight: bold; color: #333;">Email</label>
                                    <input type="email" id="driver-email" value="${driverData?.email || ''}" 
                                           style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px;" 
                                           placeholder="ivanov@example.com">
                                </div>
                            </div>
                        </div>

                        <!-- Правая колонка - Дополнительная информация -->
                        <div>
                            <h3 style="color: #333; margin-bottom: 15px;">📍 Местоположение и статус</h3>
                            
                            <div style="display: grid; gap: 15px;">
                                <div>
                                    <label style="display: block; margin-bottom: 5px; font-weight: bold; color: #333;">Город</label>
                                    <input type="text" id="driver-city" value="${driverData?.city || 'Москва'}" 
                                           style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px;" 
                                           placeholder="Москва">
                                </div>
                                
                                <div>
                                    <label style="display: block; margin-bottom: 5px; font-weight: bold; color: #333;">Район</label>
                                    <input type="text" id="driver-district" value="${driverData?.district || ''}" 
                                           style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px;" 
                                           placeholder="Центральный район">
                                </div>
                                
                                <div>
                                    <label style="display: block; margin-bottom: 5px; font-weight: bold; color: #333;">График работы</label>
                                    <select id="driver-schedule" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px;">
                                        <option value="day" ${driverData?.schedule === 'day' ? 'selected' : ''}>Дневная смена</option>
                                        <option value="night" ${driverData?.schedule === 'night' ? 'selected' : ''}>Ночная смена</option>
                                    </select>
                                </div>
                                
                                <div>
                                    <label style="display: block; margin-bottom: 5px; font-weight: bold; color: #333;">Статус</label>
                                    <select id="driver-status" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px;">
                                        <option value="work" ${driverData?.status === 'work' ? 'selected' : ''}>В работе</option>
                                        <option value="dayoff" ${driverData?.status === 'dayoff' ? 'selected' : ''}>Выходной</option>
                                        <option value="vacation" ${driverData?.status === 'vacation' ? 'selected' : ''}>Отпуск</option>
                                        <option value="repair" ${driverData?.status === 'repair' ? 'selected' : ''}>Ремонт</option>
                                    </select>
                                </div>

                                <div>
                                    <label style="display: block; margin-bottom: 5px; font-weight: bold; color: #333;">Фотография</label>
                                    <input type="file" id="driver-photo" accept="image/*" 
                                           style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px;"
                                           onchange="driversSystem.previewPhoto(this)">
                                    <div id="photo-preview" style="margin-top: 10px; display: ${driverData?.photo_url ? 'block' : 'none'}">
                                        ${driverData?.photo_url ? 
                                            `<img src="${driverData.photo_url}" style="max-width: 150px; max-height: 150px; border-radius: 4px;">` : 
                                            ''
                                        }
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Паспортные данные -->
                    <div style="margin-top: 20px; padding-top: 20px; border-top: 1px solid #eee;">
                        <h3 style="color: #333; margin-bottom: 15px;">📋 Паспортные данные</h3>
                        
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                            <div>
                                <label style="display: block; margin-bottom: 5px; font-weight: bold; color: #333;">Серия и номер</label>
                                <input type="text" id="driver-passport" value="${driverData?.passport_series_number || ''}" 
                                       style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px;" 
                                       placeholder="4510 123456">
                            </div>
                            
                            <div>
                                <label style="display: block; margin-bottom: 5px; font-weight: bold; color: #333;">Дата выдачи</label>
                                <input type="date" id="driver-passport-date" value="${driverData?.passport_issue_date || ''}" 
                                       style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px;">
                            </div>
                            
                            <div style="grid-column: 1 / -1;">
                                <label style="display: block; margin-bottom: 5px; font-weight: bold; color: #333;">Кем выдан</label>
                                <input type="text" id="driver-passport-issued" value="${driverData?.passport_issued_by || ''}" 
                                       style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px;" 
                                       placeholder="ОУФМС России по г. Москве">
                            </div>
                            
                            <div style="grid-column: 1 / -1;">
                                <label style="display: block; margin-bottom: 5px; font-weight: bold; color: #333;">Адрес регистрации</label>
                                <textarea id="driver-passport-address" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px; min-height: 60px;" 
                                          placeholder="г. Москва, ул. Тверская, д. 15, кв. 25">${driverData?.passport_registration_address || ''}</textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Комментарии -->
                    <div style="margin-top: 20px;">
                        <label style="display: block; margin-bottom: 5px; font-weight: bold; color: #333;">💬 Комментарии</label>
                        <textarea id="driver-comments" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px; min-height: 80px;" 
                                  placeholder="Дополнительная информация о водителе...">${driverData?.comments || ''}</textarea>
                    </div>
                </div>

                <div class="modal-footer" style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 25px; border-top: 1px solid #eee; padding-top: 20px;">
                    <button onclick="driversSystem.closeModal()" style="padding: 12px 25px; border: 1px solid #ddd; background: white; border-radius: 4px; cursor: pointer; font-size: 14px;">❌ Отмена</button>
                    <button onclick="driversSystem.${isEdit ? 'updateDriver' : 'createDriver'}()" 
                            style="padding: 12px 25px; background: #28a745; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 14px; font-weight: bold;">
                        ${buttonText}
                    </button>
                </div>
            </div>
        </div>
        `;

        document.body.insertAdjacentHTML('beforeend', modalHTML);
        console.log('✅ Модальное окно водителя открыто');
    }

    previewPhoto(input) {
        const preview = document.getElementById('photo-preview');
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                preview.innerHTML = `<img src="${e.target.result}" style="max-width: 150px; max-height: 150px; border-radius: 4px;">`;
                preview.style.display = 'block';
            }
            reader.readAsDataURL(input.files[0]);
        }
    }

    async createDriver() {
        console.log('🎯 Создание нового водителя...');
        
        // Собираем данные
        const driverData = {
            first_name: document.getElementById('driver-first-name').value.trim(),
            last_name: document.getElementById('driver-last-name').value.trim(),
            middle_name: document.getElementById('driver-middle-name').value.trim(),
            phone: document.getElementById('driver-phone').value.trim(),
            phone_secondary: document.getElementById('driver-phone-secondary').value.trim(),
            email: document.getElementById('driver-email').value.trim(),
            city: document.getElementById('driver-city').value.trim(),
            district: document.getElementById('driver-district').value.trim(),
            schedule: document.getElementById('driver-schedule').value,
            status: document.getElementById('driver-status').value,
            passport_series_number: document.getElementById('driver-passport').value.trim(),
            passport_issue_date: document.getElementById('driver-passport-date').value,
            passport_issued_by: document.getElementById('driver-passport-issued').value.trim(),
            passport_registration_address: document.getElementById('driver-passport-address').value.trim(),
            comments: document.getElementById('driver-comments').value.trim(),
            created_by: window.authSystem?.currentUser?.id || 1
        };

        // Валидация
        if (!driverData.first_name || !driverData.last_name || !driverData.phone) {
            this.showNotification('❌ Заполните обязательные поля: Фамилия, Имя, Телефон', 'error');
            return;
        }

        // Обработка фото
        const photoInput = document.getElementById('driver-photo');
        if (photoInput.files && photoInput.files[0]) {
            const base64Photo = await this.fileToBase64(photoInput.files[0]);
            driverData.photo_base64 = base64Photo;
        }

        try {
            console.log('📤 Отправка данных водителя...');
            
            const response = await fetch('/api/drivers.php?action=create', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(driverData)
            });

            const result = await response.json();
            console.log('📥 Ответ сервера:', result);

            if (result.success) {
                this.showNotification('✅ Водитель успешно создан!', 'success');
                this.closeModal();
                await this.loadDrivers();
            } else {
                this.showNotification('❌ Ошибка: ' + result.message, 'error');
            }

        } catch (error) {
            console.error('❌ Ошибка сети:', error);
            this.showNotification('❌ Ошибка сети при создании водителя', 'error');
        }
    }

    async updateDriver() {
        if (!this.currentDriver) return;
        
        console.log('🎯 Обновление водителя:', this.currentDriver.id);
        
        // Собираем данные
        const driverData = {
            id: this.currentDriver.id,
            first_name: document.getElementById('driver-first-name').value.trim(),
            last_name: document.getElementById('driver-last-name').value.trim(),
            middle_name: document.getElementById('driver-middle-name').value.trim(),
            phone: document.getElementById('driver-phone').value.trim(),
            phone_secondary: document.getElementById('driver-phone-secondary').value.trim(),
            email: document.getElementById('driver-email').value.trim(),
            city: document.getElementById('driver-city').value.trim(),
            district: document.getElementById('driver-district').value.trim(),
            schedule: document.getElementById('driver-schedule').value,
            status: document.getElementById('driver-status').value,
            passport_series_number: document.getElementById('driver-passport').value.trim(),
            passport_issue_date: document.getElementById('driver-passport-date').value,
            passport_issued_by: document.getElementById('driver-passport-issued').value.trim(),
            passport_registration_address: document.getElementById('driver-passport-address').value.trim(),
            comments: document.getElementById('driver-comments').value.trim(),
            updated_by: window.authSystem?.currentUser?.id || 1
        };

        // Валидация
        if (!driverData.first_name || !driverData.last_name || !driverData.phone) {
            this.showNotification('❌ Заполните обязательные поля: Фамилия, Имя, Телефон', 'error');
            return;
        }

        // Обработка фото
        const photoInput = document.getElementById('driver-photo');
        if (photoInput.files && photoInput.files[0]) {
            const base64Photo = await this.fileToBase64(photoInput.files[0]);
            driverData.photo_base64 = base64Photo;
        }

        try {
            console.log('📤 Отправка данных для обновления...');
            
            const response = await fetch('/api/drivers.php?action=update', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(driverData)
            });

            const result = await response.json();
            console.log('📥 Ответ сервера:', result);

            if (result.success) {
                this.showNotification('✅ Водитель успешно обновлен!', 'success');
                this.closeModal();
                await this.loadDrivers();
            } else {
                this.showNotification('❌ Ошибка: ' + result.message, 'error');
            }

        } catch (error) {
            console.error('❌ Ошибка сети:', error);
            this.showNotification('❌ Ошибка сети при обновлении водителя', 'error');
        }
    }

    async deleteDriver(driverId) {
        if (!confirm('Вы уверены, что хотите удалить этого водителя?')) {
            return;
        }

        try {
            console.log('🗑️ Удаление водителя:', driverId);
            
            const response = await fetch('/api/drivers.php?action=delete', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    id: driverId,
                    deleted_by: window.authSystem?.currentUser?.id || 1
                })
            });

            const result = await response.json();
            console.log('📥 Ответ сервера:', result);

            if (result.success) {
                this.showNotification('✅ Водитель успешно удален!', 'success');
                await this.loadDrivers();
            } else {
                this.showNotification('❌ Ошибка: ' + result.message, 'error');
            }

        } catch (error) {
            console.error('❌ Ошибка сети:', error);
            this.showNotification('❌ Ошибка сети при удалении водителя', 'error');
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
        const modal = document.getElementById('driver-modal');
        if (modal) {
            modal.remove();
            this.currentDriver = null;
            console.log('✅ Модальное окно водителя закрыто');
        }
    }

    renderDriversTable() {
        const tbody = document.getElementById('drivers-table-body');
        if (!tbody) {
            console.error('❌ Не найдено tbody для таблицы водителей');
            return;
        }

        console.log('📊 Отрисовка таблицы водителей, количество:', this.drivers.length);

        if (this.drivers.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="7" style="text-align: center; padding: 40px; color: #666;">
                        <div style="font-size: 48px; margin-bottom: 10px;">👨‍✈️</div>
                        <div style="font-size: 16px; margin-bottom: 15px;">Водители не найдены</div>
                        <button class="btn btn-primary" data-action="create-driver" 
                                style="padding: 10px 20px; background: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer;">
                            👨‍💼 Добавить первого водителя
                        </button>
                    </td>
                </tr>
            `;
            return;
        }

        tbody.innerHTML = this.drivers.map(driver => `
            <tr>
                <td>
                    <div style="display: flex; align-items: center; gap: 10px;">
                        ${driver.photo_url ? 
                            `<img src="${driver.photo_url}" style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover;">` :
                            `<div style="width: 40px; height: 40px; border-radius: 50%; background: #007bff; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold;">
                                ${driver.first_name?.[0] || ''}${driver.last_name?.[0] || ''}
                            </div>`
                        }
                        <div>
                            <strong>${driver.last_name} ${driver.first_name} ${driver.middle_name || ''}</strong>
                            <div style="font-size: 12px; color: #666;">${driver.phone}</div>
                        </div>
                    </div>
                </td>
                <td>${driver.city || 'Не указан'}</td>
                <td>
                    ${driver.vehicle_brand ? 
                        `${driver.vehicle_brand} ${driver.vehicle_model}` : 
                        '<span style="color: #666;">Не назначен</span>'
                    }
                </td>
                <td>
                    <span class="status status-${this.getDriverStatusClass(driver.status)}">
                        ${this.getDriverStatusText(driver.status)}
                    </span>
                </td>
                <td>
                    ${driver.rating ? `⭐ ${driver.rating}` : 'Нет оценок'}
                </td>
                <td>
                    <div style="display: flex; gap: 5px; flex-wrap: wrap;">
                        <button class="btn btn-small" onclick="driversSystem.openCreateModal(${JSON.stringify(driver).replace(/"/g, '&quot;')})" 
                                style="padding: 5px 10px; background: #007bff; color: white; border: none; border-radius: 3px; cursor: pointer; font-size: 12px;">
                            ✏️
                        </button>
                        <button class="btn btn-small" onclick="driversSystem.viewDriverDetails(${driver.id})" 
                                style="padding: 5px 10px; background: #6c757d; color: white; border: none; border-radius: 3px; cursor: pointer; font-size: 12px;">
                            👁️
                        </button>
                        ${window.authSystem?.currentUser?.role === 'admin' ? 
                            `<button class="btn btn-small" onclick="driversSystem.deleteDriver(${driver.id})" 
                                    style="padding: 5px 10px; background: #dc3545; color: white; border: none; border-radius: 3px; cursor: pointer; font-size: 12px;">
                                🗑️
                            </button>` : 
                            ''
                        }
                    </div>
                </td>
            </tr>
        `).join('');

        console.log('✅ Таблица водителей отрисована');
    }

    getDriverStatusText(status) {
        const statuses = {
            'work': 'В работе',
            'dayoff': 'Выходной',
            'vacation': 'Отпуск',
            'repair': 'Ремонт'
        };
        return statuses[status] || status;
    }

    getDriverStatusClass(status) {
        const classMap = {
            'work': 'inwork',
            'dayoff': 'confirmed',
            'vacation': 'new',
            'repair': 'cancelled'
        };
        return classMap[status] || 'new';
    }

    viewDriverDetails(driverId) {
        const driver = this.drivers.find(d => d.id == driverId);
        if (!driver) {
            this.showNotification('❌ Водитель не найден', 'error');
            return;
        }

        const modalHTML = `
        <div class="modal show" id="view-driver-modal" style="display: block; background: rgba(0,0,0,0.5); position: fixed; top: 0; left: 0; right: 0; bottom: 0; z-index: 1000;">
            <div class="modal-content" style="max-width: 600px; margin: 50px auto; background: white; padding: 20px; border-radius: 8px;">
                <div class="modal-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 1px solid #eee; padding-bottom: 15px;">
                    <h2 style="margin: 0; color: #333;">👨‍💼 ${driver.last_name} ${driver.first_name} ${driver.middle_name || ''}</h2>
                    <button onclick="this.closest('.modal').remove()" style="background: none; border: none; font-size: 24px; cursor: pointer; color: #666;">×</button>
                </div>
                
                <div class="modal-body">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 20px;">
                        <div>
                            <h4 style="margin: 0 0 10px 0; color: #333;">📞 Контакты</h4>
                            <p style="margin: 5px 0;"><strong>Телефон:</strong> ${driver.phone}</p>
                            ${driver.phone_secondary ? `<p style="margin: 5px 0;"><strong>Доп. телефон:</strong> ${driver.phone_secondary}</p>` : ''}
                            ${driver.email ? `<p style="margin: 5px 0;"><strong>Email:</strong> ${driver.email}</p>` : ''}
                        </div>
                        <div>
                            <h4 style="margin: 0 0 10px 0; color: #333;">📍 Местоположение</h4>
                            <p style="margin: 5px 0;"><strong>Город:</strong> ${driver.city || 'Не указан'}</p>
                            <p style="margin: 5px 0;"><strong>Район:</strong> ${driver.district || 'Не указан'}</p>
                            <p style="margin: 5px 0;"><strong>Статус:</strong> ${this.getDriverStatusText(driver.status)}</p>
                        </div>
                    </div>

                    ${driver.vehicle_brand ? `
                    <div style="margin-bottom: 20px; padding: 15px; background: #f8f9fa; border-radius: 4px;">
                        <h4 style="margin: 0 0 10px 0; color: #333;">🚗 Закрепленный автомобиль</h4>
                        <p style="margin: 5px 0;"><strong>Автомобиль:</strong> ${driver.vehicle_brand} ${driver.vehicle_model}</p>
                        <p style="margin: 5px 0;"><strong>Номер:</strong> ${driver.vehicle_plate}</p>
                    </div>
                    ` : ''}

                    ${driver.comments ? `
                    <div style="margin-bottom: 20px;">
                        <h4 style="margin: 0 0 10px 0; color: #333;">💬 Комментарий</h4>
                        <p style="margin: 0; padding: 10px; background: #f8f9fa; border-radius: 4px;">${driver.comments}</p>
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
} // <-- ЗАКРЫВАЮЩАЯ ФИГУРНАЯ СКОБКА ДЛЯ КЛАССА DriversSystem

// Создаем глобальный объект системы водителей
window.driversSystem = new DriversSystem();

// Инициализация при загрузке страницы
document.addEventListener('DOMContentLoaded', function() {
    console.log('🏁 DOM загружен, запуск системы водителей...');
    if (typeof driversSystem !== 'undefined') {
        driversSystem.init();
    } else {
        console.error('❌ driversSystem не определен');
    }
});