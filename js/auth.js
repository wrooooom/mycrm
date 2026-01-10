// js/auth.js - система аутентификации CRM Proftransfer

class AuthSystem {
    constructor() {
        this.currentUser = null;
        this.isLoggedIn = false;
        this.apiBase = window.location.origin + '/api';
    }

    // Функция входа в систему
    async login(email, password) {
        try {
            const response = await fetch(this.apiBase + '/auth.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    email: email,
                    password: password
                })
            });

            const data = await response.json();

            if (data.success) {
                this.currentUser = data.user;
                this.isLoggedIn = true;
                
                // Сохраняем в localStorage
                localStorage.setItem('currentUser', JSON.stringify(this.currentUser));
                localStorage.setItem('isLoggedIn', 'true');
                
                this.showNotification('Успешный вход!', 'success');
                this.showMainApp();
                
                return true;
            } else {
                this.showNotification(data.message || 'Ошибка авторизации', 'error');
                return false;
            }
        } catch (error) {
            console.error('Login error:', error);
            
            // Fallback к тестовой авторизации если API недоступно
            if (await this.fallbackAuth(email, password)) {
                return true;
            }
            
            this.showNotification('Ошибка соединения с сервером', 'error');
            return false;
        }
    }

    // Резервная авторизация если API недоступно
    async fallbackAuth(email, password) {
        const testUsers = {
            'admin@proftransfer.ru': { 
                id: 1, 
                name: 'Администратор', 
                role: 'admin',
                phone: '+79990000001',
                company_id: 1
            },
            'manager@proftransfer.ru': { 
                id: 2, 
                name: 'Менеджер', 
                role: 'manager',
                phone: '+79990000002',
                company_id: 1
            },
            'driver@proftransfer.ru': { 
                id: 3, 
                name: 'Водитель Петров', 
                role: 'driver',
                phone: '+79990000003',
                company_id: 1
            },
            'client@proftransfer.ru': { 
                id: 4, 
                name: 'Клиент Иванов', 
                role: 'client',
                phone: '+79990000004',
                company_id: 2
            }
        };

        if (testUsers[email] && password === 'admin123') {
            this.currentUser = testUsers[email];
            this.isLoggedIn = true;
            
            localStorage.setItem('currentUser', JSON.stringify(this.currentUser));
            localStorage.setItem('isLoggedIn', 'true');
            
            this.showNotification('Успешный вход (тестовый режим)', 'success');
            this.showMainApp();
            
            return true;
        }
        return false;
    }

    // Функция выхода
    logout() {
        this.currentUser = null;
        this.isLoggedIn = false;
        localStorage.removeItem('currentUser');
        localStorage.removeItem('isLoggedIn');
        
        this.showAuthPage();
        this.showNotification('Вы вышли из системы', 'success');
    }

    // Проверяем, авторизован ли пользователь
    checkAuth() {
        const isLoggedIn = localStorage.getItem('isLoggedIn');
        const userData = localStorage.getItem('currentUser');
        
        if (isLoggedIn === 'true' && userData) {
            this.currentUser = JSON.parse(userData);
            this.isLoggedIn = true;
            this.showMainApp();
            return true;
        }
        
        this.showAuthPage();
        return false;
    }

    // Показываем страницу авторизации
    showAuthPage() {
        const authPage = document.getElementById('auth-page');
        const app = document.getElementById('app');
        
        if (authPage) authPage.style.display = 'flex';
        if (app) app.style.display = 'none';
    }

    // Показываем основное приложение
    showMainApp() {
        const authPage = document.getElementById('auth-page');
        const app = document.getElementById('app');
        
        if (authPage) authPage.style.display = 'none';
        if (app) app.style.display = 'block';
        
        // Обновляем информацию о пользователе
        this.updateUserInfo();
        
        // Показываем раздел заявок по умолчанию
        if (typeof showSection === 'function') {
            showSection('applications');
        }
        
        // Загружаем данные для dashboard
        this.loadDashboardData();
    }

    // Обновляем информацию о пользователе в интерфейсе
    updateUserInfo() {
        if (this.currentUser) {
            const userNameElement = document.getElementById('user-name');
            const userRoleElement = document.getElementById('user-role-badge');
            const userAvatarElement = document.getElementById('user-avatar');
            
            if (userNameElement) {
                userNameElement.textContent = this.currentUser.name;
            }
            if (userRoleElement) {
                userRoleElement.textContent = this.getRoleName(this.currentUser.role);
                userRoleElement.className = `role-badge role-${this.currentUser.role}`;
            }
            if (userAvatarElement) {
                const initials = this.getInitials(this.currentUser.name);
                userAvatarElement.textContent = initials;
            }
        }
    }

    // Получаем инициалы для аватара
    getInitials(name) {
        return name.split(' ').map(word => word[0]).join('').toUpperCase();
    }

    // Получаем русское название роли
    getRoleName(role) {
        const roles = {
            'admin': 'Администратор',
            'manager': 'Менеджер', 
            'driver': 'Водитель',
            'client': 'Клиент'
        };
        return roles[role] || role;
    }

    // Загружаем данные для dashboard
    async loadDashboardData() {
        // Здесь будет загрузка статистики и данных
        console.log('Loading dashboard data for user:', this.currentUser);
    }

    // Показываем уведомления
    showNotification(message, type = 'success') {
        // Удаляем предыдущие уведомления
        const existingNotifications = document.querySelectorAll('.notification');
        existingNotifications.forEach(notification => {
            if (notification.parentNode) {
                notification.parentNode.removeChild(notification);
            }
        });

        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px 20px;
            border-radius: 5px;
            color: white;
            z-index: 10000;
            min-width: 300px;
            max-width: 500px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            animation: slideIn 0.3s ease-out;
        `;
        
        if (type === 'success') {
            notification.style.background = '#4CAF50';
        } else if (type === 'error') {
            notification.style.background = '#f44336';
        } else if (type === 'warning') {
            notification.style.background = '#ff9800';
        } else {
            notification.style.background = '#2196F3';
        }
        
        notification.innerHTML = `
            <div style="display: flex; align-items: center; justify-content: space-between;">
                <span>${message}</span>
                <button onclick="this.parentElement.parentElement.remove()" 
                        style="background: none; border: none; color: white; cursor: pointer; font-size: 18px; margin-left: 10px;">
                    ×
                </button>
            </div>
        `;
        
        document.body.appendChild(notification);
        
        // Автоматическое удаление через 4 секунды
        setTimeout(() => {
            if (notification.parentNode) {
                notification.parentNode.removeChild(notification);
            }
        }, 4000);
    }

    // Проверяем права доступа
    hasPermission(requiredRole) {
        if (!this.currentUser) return false;
        
        const roleHierarchy = {
            'client': 0,
            'driver': 1,
            'manager': 2,
            'admin': 3
        };
        
        const userLevel = roleHierarchy[this.currentUser.role] || 0;
        const requiredLevel = roleHierarchy[requiredRole] || 0;
        
        return userLevel >= requiredLevel;
    }
}

// Добавляем CSS для анимации
const style = document.createElement('style');
style.textContent = `
    @keyframes slideIn {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
    
    .role-badge {
        padding: 4px 8px;
        border-radius: 12px;
        font-size: 12px;
        font-weight: 500;
    }
    
    .role-admin { background: #f44336; color: white; }
    .role-manager { background: #2196F3; color: white; }
    .role-driver { background: #4CAF50; color: white; }
    .role-client { background: #FF9800; color: white; }
`;
document.head.appendChild(style);

// Создаем глобальный объект аутентификации
window.authSystem = new AuthSystem();

// Функция для входа (вызывается из HTML)
async function login() {
    const email = document.getElementById('login-email').value;
    const password = document.getElementById('login-password').value;
    
    if (!email || !password) {
        authSystem.showNotification('Заполните все поля', 'error');
        return;
    }
    
    // Показываем индикатор загрузки
    const loginBtn = document.querySelector('#auth-page button[type="submit"]');
    const originalText = loginBtn.textContent;
    loginBtn.textContent = 'Вход...';
    loginBtn.disabled = true;
    
    await authSystem.login(email, password);
    
    // Восстанавливаем кнопку
    loginBtn.textContent = originalText;
    loginBtn.disabled = false;
}

// Функция для выхода
function logout() {
    authSystem.logout();
}

// Обработка нажатия Enter в форме авторизации
document.addEventListener('DOMContentLoaded', function() {
    const passwordField = document.getElementById('login-password');
    if (passwordField) {
        passwordField.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                login();
            }
        });
    }
    
    // Проверяем авторизацию при загрузке страницы
    authSystem.checkAuth();
});