// core.js - базовые функции для CRM.PROFTRANSFER
(function(window, document) {
    'use strict';
    
    console.log('🚗 CRM.PROFTRANSFER Core loading...');
    
    const Core = {
        version: '1.0.0',
        debug: true,
        apiBase: '/api/',
        
        // Логирование
        log: function(...args) {
            if (this.debug && console && console.log) {
                console.log('%cCRM.Core:', 'color: #1a365d; font-weight: bold;', ...args);
            }
        },
        
        error: function(...args) {
            if (console && console.error) {
                console.error('%cCRM.Core:', 'color: #e53e3e; font-weight: bold;', ...args);
            }
        },
        
        // Утилиты для работы с API
        api: {
            // Получить данные
            get: function(endpoint, params = {}) {
                return this.request('GET', endpoint, null, params);
            },
            
            // Отправить данные
            post: function(endpoint, data = {}) {
                return this.request('POST', endpoint, data);
            },
            
            // Обновить данные
            put: function(endpoint, data = {}) {
                return this.request('PUT', endpoint, data);
            },
            
            // Удалить данные
            delete: function(endpoint) {
                return this.request('DELETE', endpoint);
            },
            
            // Базовый запрос
            request: function(method, endpoint, data = null, params = {}) {
                return new Promise((resolve, reject) => {
                    try {
                        // В реальном приложении здесь будет fetch/axios запрос
                        // Сейчас используем мок-данные для демонстрации
                        setTimeout(() => {
                            const mockData = this.getMockData(method, endpoint, data);
                            if (mockData) {
                                resolve(mockData);
                            } else {
                                reject(new Error(`API endpoint ${endpoint} not implemented`));
                            }
                        }, 300);
                    } catch (error) {
                        reject(error);
                    }
                });
            },
            
            // Мок-данные для демонстрации
            getMockData: function(method, endpoint, data) {
                this.log(`API ${method} ${endpoint}`, data);
                
                const mockResponses = {
                    'GET:/api/applications': {
                        success: true,
                        data: window.mockData?.applications || [],
                        total: window.mockData?.applications?.length || 0
                    },
                    'GET:/api/drivers': {
                        success: true,
                        data: window.mockData?.drivers || [],
                        total: window.mockData?.drivers?.length || 0
                    },
                    'GET:/api/vehicles': {
                        success: true,
                        data: window.mockData?.vehicles || [],
                        total: window.mockData?.vehicles?.length || 0
                    },
                    'GET:/api/users': {
                        success: true,
                        data: window.mockData?.users || [],
                        total: window.mockData?.users?.length || 0
                    },
                    'POST:/api/applications': {
                        success: true,
                        message: 'Заявка успешно создана',
                        id: Date.now()
                    },
                    'POST:/api/drivers': {
                        success: true,
                        message: 'Водитель успешно добавлен',
                        id: Date.now()
                    },
                    'POST:/api/vehicles': {
                        success: true,
                        message: 'Автомобиль успешно добавлен',
                        id: Date.now()
                    }
                };
                
                const key = `${method}:${endpoint}`;
                return mockResponses[key] || null;
            }
        },
        
        // Утилиты для работы с DOM
        dom: {
            // Найти элемент
            find: function(selector) {
                return document.querySelector(selector);
            },
            
            // Найти все элементы
            findAll: function(selector) {
                return document.querySelectorAll(selector);
            },
            
            // Показать элемент
            show: function(element) {
                if (typeof element === 'string') {
                    element = this.find(element);
                }
                if (element) element.style.display = 'block';
            },
            
            // Скрыть элемент
            hide: function(element) {
                if (typeof element === 'string') {
                    element = this.find(element);
                }
                if (element) element.style.display = 'none';
            },
            
            // Добавить класс
            addClass: function(element, className) {
                if (typeof element === 'string') {
                    element = this.find(element);
                }
                if (element) element.classList.add(className);
            },
            
            // Удалить класс
            removeClass: function(element, className) {
                if (typeof element === 'string') {
                    element = this.find(element);
                }
                if (element) element.classList.remove(className);
            },
            
            // Переключить класс
            toggleClass: function(element, className) {
                if (typeof element === 'string') {
                    element = this.find(element);
                }
                if (element) element.classList.toggle(className);
            }
        },
        
        // Утилиты для работы с датами
        date: {
            // Форматирование даты
            format: function(date, format = 'ru-RU') {
                const d = new Date(date);
                return d.toLocaleDateString(format);
            },
            
            // Форматирование даты и времени
            formatDateTime: function(date, format = 'ru-RU') {
                const d = new Date(date);
                return d.toLocaleString(format);
            },
            
            // Добавить дни к дате
            addDays: function(date, days) {
                const d = new Date(date);
                d.setDate(d.getDate() + days);
                return d;
            },
            
            // Разница между датами в днях
            diffInDays: function(date1, date2) {
                const d1 = new Date(date1);
                const d2 = new Date(date2);
                const diffTime = Math.abs(d2 - d1);
                return Math.ceil(diffTime / (1000 * 60 * 60 * 24));
            }
        },
        
        // Утилиты для работы с формами
        form: {
            // Сериализовать форму в объект
            serialize: function(formElement) {
                const formData = new FormData(formElement);
                const data = {};
                for (let [key, value] of formData.entries()) {
                    data[key] = value;
                }
                return data;
            },
            
            // Валидация email
            validateEmail: function(email) {
                const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                return re.test(email);
            },
            
            // Валидация телефона
            validatePhone: function(phone) {
                const re = /^[\+]?[0-9\-\s\(\)]{10,}$/;
                return re.test(phone);
            },
            
            // Очистить форму
            clear: function(formElement) {
                formElement.reset();
            }
        },
        
        // Утилиты для работы с localStorage
        storage: {
            // Получить данные
            get: function(key, defaultValue = null) {
                try {
                    const item = localStorage.getItem(key);
                    return item ? JSON.parse(item) : defaultValue;
                } catch (error) {
                    console.error('Storage get error:', error);
                    return defaultValue;
                }
            },
            
            // Сохранить данные
            set: function(key, value) {
                try {
                    localStorage.setItem(key, JSON.stringify(value));
                    return true;
                } catch (error) {
                    console.error('Storage set error:', error);
                    return false;
                }
            },
            
            // Удалить данные
            remove: function(key) {
                try {
                    localStorage.removeItem(key);
                    return true;
                } catch (error) {
                    console.error('Storage remove error:', error);
                    return false;
                }
            },
            
            // Очистить все данные
            clear: function() {
                try {
                    localStorage.clear();
                    return true;
                } catch (error) {
                    console.error('Storage clear error:', error);
                    return false;
                }
            }
        },
        
        // Уведомления
        notify: {
            // Показать уведомление
            show: function(message, type = 'info', duration = 3000) {
                const notification = document.createElement('div');
                notification.className = `core-notification core-notification-${type}`;
                notification.innerHTML = `
                    <div class="core-notification-content">
                        <span class="core-notification-message">${message}</span>
                        <button class="core-notification-close" onclick="this.parentElement.parentElement.remove()">×</button>
                    </div>
                `;
                
                // Стили для уведомления
                Object.assign(notification.style, {
                    position: 'fixed',
                    top: '20px',
                    right: '20px',
                    background: this.getBackgroundColor(type),
                    color: 'white',
                    padding: '12px 16px',
                    borderRadius: '4px',
                    boxShadow: '0 4px 12px rgba(0,0,0,0.15)',
                    zIndex: '10000',
                    minWidth: '300px',
                    maxWidth: '500px',
                    animation: 'coreNotificationSlideIn 0.3s ease-out'
                });
                
                // Добавляем стили анимации если их еще нет
                if (!document.querySelector('#core-notification-styles')) {
                    const style = document.createElement('style');
                    style.id = 'core-notification-styles';
                    style.textContent = `
                        @keyframes coreNotificationSlideIn {
                            from { transform: translateX(100%); opacity: 0; }
                            to { transform: translateX(0); opacity: 1; }
                        }
                        .core-notification-close {
                            background: none;
                            border: none;
                            color: white;
                            font-size: 18px;
                            cursor: pointer;
                            margin-left: 10px;
                        }
                        .core-notification-content {
                            display: flex;
                            align-items: center;
                            justify-content: space-between;
                        }
                    `;
                    document.head.appendChild(style);
                }
                
                document.body.appendChild(notification);
                
                // Автоматическое закрытие
                if (duration > 0) {
                    setTimeout(() => {
                        if (notification.parentElement) {
                            notification.remove();
                        }
                    }, duration);
                }
                
                return notification;
            },
            
            // Получить цвет фона по типу уведомления
            getBackgroundColor: function(type) {
                const colors = {
                    info: '#1a365d',
                    success: '#38a169',
                    warning: '#d69e2e',
                    error: '#e53e3e'
                };
                return colors[type] || colors.info;
            },
            
            // Быстрые методы для разных типов уведомлений
            info: function(message, duration) {
                return this.show(message, 'info', duration);
            },
            
            success: function(message, duration) {
                return this.show(message, 'success', duration);
            },
            
            warning: function(message, duration) {
                return this.show(message, 'warning', duration);
            },
            
            error: function(message, duration) {
                return this.show(message, 'error', duration);
            }
        },
        
        // Модальные окна
        modal: {
            // Открыть модальное окно
            open: function(modalId) {
                const modal = document.getElementById(modalId);
                if (modal) {
                    modal.style.display = 'flex';
                    setTimeout(() => {
                        modal.classList.add('show');
                    }, 10);
                    
                    // Блокируем прокрутку фона
                    document.body.style.overflow = 'hidden';
                }
            },
            
            // Закрыть модальное окно
            close: function(modalId) {
                const modal = document.getElementById(modalId);
                if (modal) {
                    modal.classList.remove('show');
                    setTimeout(() => {
                        modal.style.display = 'none';
                        document.body.style.overflow = '';
                    }, 300);
                }
            },
            
            // Закрыть все модальные окна
            closeAll: function() {
                document.querySelectorAll('.modal').forEach(modal => {
                    modal.classList.remove('show');
                    setTimeout(() => {
                        modal.style.display = 'none';
                    }, 300);
                });
                document.body.style.overflow = '';
            }
        },
        
        // Инициализация
        init: function() {
            this.log('Core initialized successfully');
            
            // Закрытие модальных окон по клику вне области
            document.addEventListener('click', (e) => {
                if (e.target.classList.contains('modal')) {
                    this.modal.closeAll();
                }
            });
            
            // Закрытие модальных окон по ESC
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape') {
                    this.modal.closeAll();
                }
            });
            
            // Инициализация всех выпадающих списков
            this.initSelects();
        },
        
        // Инициализация кастомных селектов
        initSelects: function() {
            document.addEventListener('click', (e) => {
                // Закрытие всех выпадающих списков при клике вне их
                if (!e.target.closest('.custom-select')) {
                    document.querySelectorAll('.custom-select-options').forEach(options => {
                        options.style.display = 'none';
                    });
                }
            });
        },
        
        // Загрузка данных приложения
        loadAppData: function() {
            return Promise.all([
                this.api.get('/api/applications'),
                this.api.get('/api/drivers'),
                this.api.get('/api/vehicles'),
                this.api.get('/api/users')
            ]);
        }
    };
    
    // Экспортируем Core в глобальную область видимости
    window.Core = Core;
    
    // Автоматическая инициализация при загрузке DOM
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => Core.init());
    } else {
        Core.init();
    }
    
    console.log('🚗 CRM.PROFTRANSFER Core loaded successfully');
    
})(window, document);
