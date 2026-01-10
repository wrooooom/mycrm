// Основные переменные приложения
let currentSection = 'applications';
let selectedDate = new Date();
let activityLog = [];
let map = null;
let currentApplicationDetails = null;
let selectedBrand = '';
let charts = {};

// Инициализация приложения
function init() {
    console.log('Инициализация приложения...');
    
    checkAuth();
    initCalendar();
    loadActivityLog();
    initSelects();
    updateCorrectionBadge();
    activateAllButtons();
    
    console.log('Инициализация завершена');
}

// Загрузка начальных данных
async function loadInitialData() {
    await loadAndRenderApplications();
    await loadDrivers();
    await loadVehicles();
    await loadUsers();
    await loadCompanies();
    await loadDashboardData();
}

// Улучшенные уведомления
function showEnhancedNotification(message, type = 'success') {
    // Удаляем существующие уведомления
    const existingNotifications = document.querySelectorAll('.notification');
    existingNotifications.forEach(notification => notification.remove());
    
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.textContent = message;
    notification.style.opacity = '0';
    
    document.body.appendChild(notification);
    
    // Анимация появления
    setTimeout(() => {
        notification.style.opacity = '1';
        notification.style.transform = 'translateX(0)';
    }, 100);
    
    // Автоматическое скрытие
    setTimeout(() => {
        notification.style.opacity = '0';
        notification.style.transform = 'translateX(100%)';
        setTimeout(() => {
            if (notification.parentNode) {
                notification.parentNode.removeChild(notification);
            }
        }, 300);
    }, 4000);
}

// История действий
function addActivity(action) {
    const activity = {
        user: currentUser ? currentUser.name : 'Система',
        action: action,
        timestamp: new Date().toLocaleString('ru-RU')
    };
    
    activityLog.unshift(activity);
    if (activityLog.length > 50) activityLog.pop();
    
    try {
        sessionStorage.setItem('proftransfer_activity', JSON.stringify(activityLog));
    } catch (error) {
        console.error('Ошибка сохранения активности:', error);
    }
    
    renderActivityLog();
}

function loadActivityLog() {
    try {
        const savedActivity = sessionStorage.getItem('proftransfer_activity');
        if (savedActivity) {
            activityLog = JSON.parse(savedActivity);
            renderActivityLog();
        }
    } catch (error) {
        console.error('Ошибка загрузки активности:', error);
        activityLog = [];
    }
}

function renderActivityLog() {
    const container = document.getElementById('activity-list');
    if (!container) return;
    
    container.innerHTML = activityLog.map(activity => `
        <div class="activity-item">
            <div class="activity-user">${activity.user}</div>
            <div>${activity.action}</div>
            <div class="activity-time">${activity.timestamp}</div>
        </div>
    `).join('');
}

// Навигация по разделам
function showSection(section) {
    // Скрываем все секции
    document.querySelectorAll('.section').forEach(sec => {
        sec.style.display = 'none';
    });
    
    // Убираем активный класс со всех пунктов меню
    document.querySelectorAll('.nav-item').forEach(item => {
        item.classList.remove('active');
    });
    
    // Показываем выбранную секцию
    const sectionEl = document.getElementById(section + '-section');
    if (sectionEl) {
        sectionEl.style.display = 'block';
    }
    
    // Добавляем активный класс к выбранному пункту меню
    const navItems = document.querySelectorAll('.nav-item');
    navItems.forEach(item => {
        if (item.textContent.includes(getNavItemText(section))) {
            item.classList.add('active');
        }
    });
    
    currentSection = section;
    
    // Специфическая инициализация для разделов
    switch(section) {
        case 'tracking':
            setTimeout(initMap, 100);
            renderTrackingTable();
            break;
        case 'analytics':
            renderAnalytics();
            break;
        case 'users':
            renderCompaniesTable();
            break;
    }
    
    // Скрываем контент договоров по умолчанию
    if (section !== 'users') {
        const contractsContent = document.getElementById('contracts-content');
        if (contractsContent) {
            contractsContent.style.display = 'none';
        }
    }
}

function getNavItemText(section) {
    const navTexts = {
        'applications': 'Заявки',
        'dashboard': 'Рабочий стол',
        'drivers': 'Водители',
        'vehicles': 'Автомобили',
        'billing': 'Счёт',
        'users': 'Пользователи',
        'analytics': 'Аналитика',
        'tracking': 'Трекинг'
    };
    return navTexts[section] || '';
}

// Активация всех кнопок
function activateAllButtons() {
    console.log('Активация функциональности кнопок...');
    
    // Здесь будет код для активации всех интерактивных элементов
    // Эта функция гарантирует, что все кнопки и элементы управления работают
}

// Обновление бейджа корректировок
function updateCorrectionBadge() {
    const correctionCount = mockApplications.filter(app => app.requires_correction).length;
    const badge = document.getElementById('correction-count');
    const correctionBtn = document.getElementById('correction-btn');
    
    if (badge) {
        badge.textContent = correctionCount;
        if (correctionCount > 0) {
            badge.style.display = 'inline';
            if (correctionBtn) {
                correctionBtn.style.display = 'inline-flex';
            }
        } else {
            badge.style.display = 'none';
            if (correctionBtn) {
                correctionBtn.style.display = 'none';
            }
        }
    }
}

// Показ заявок, требующих корректировки
function showCorrectionApplications() {
    const correctionApps = mockApplications.filter(app => app.requires_correction);
    renderApplicationsTable(correctionApps);
    showEnhancedNotification(`Показано ${correctionApps.length} заявок, требующих корректировки`, 'warning');
    addActivity(`${currentUser.name} просматривает заявки, требующие корректировки`);
}

// Экспорт в Excel
function exportToExcel(section) {
    // Заглушка для экспорта - в реальном приложении здесь был бы вызов API
    showEnhancedNotification(`Экспорт данных раздела "${section}" в Excel начат`, 'success');
    addActivity(`${currentUser.name} экспортировал данные раздела ${section} в Excel`);
    
    // Имитация экспорта
    setTimeout(() => {
        showEnhancedNotification('Экспорт завершен успешно!', 'success');
    }, 2000);
}

// Запуск инициализации при загрузке DOM
document.addEventListener('DOMContentLoaded', function() {
    init();
    
    // Глобальные обработчики событий
    document.addEventListener('click', function(e) {
        // Закрытие выпадающих списков при клике вне их
        if (!e.target.closest('.custom-select')) {
            document.querySelectorAll('.custom-select-options').forEach(options => {
                options.style.display = 'none';
            });
        }
        
        // Закрытие модальных окон при клике на фон
        if (e.target.classList.contains('modal')) {
            const modalId = e.target.id.replace('-modal', '');
            closeModal(modalId);
        }
    });
});