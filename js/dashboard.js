// Функции для рабочего стола

// Моковые данные для дашборда
const mockDashboard = {
    drivers_rating: [
        {
            name: "Сидоров Алексей Петрович",
            orders: 45,
            avg_order: 1250,
            total: 56250
        },
        {
            name: "Козлов Дмитрий Иванович",
            orders: 38,
            avg_order: 1100,
            total: 41800
        },
        {
            name: "Иванов Сергей Владимирович",
            orders: 52,
            avg_order: 1400,
            total: 72800
        }
    ],
    widgets: [
        {
            id: 1,
            type: 'stats',
            title: 'Общая статистика',
            data: {
                total_orders: 85,
                completed_orders: 45,
                revenue: 125000,
                active_drivers: 38
            }
        },
        {
            id: 2,
            type: 'chart',
            title: 'Заявки по статусам',
            data: {
                labels: ['Новые', 'В работе', 'Завершены'],
                values: [12, 15, 45]
            }
        }
    ]
};

// Загрузка данных дашборда
async function loadDashboardData() {
    try {
        // В реальном приложении здесь был бы API запрос
        return mockDashboard;
    } catch (error) {
        console.log('Используем моковые данные для дашборда');
        return mockDashboard;
    }
}

// Рендер таблицы рейтинга водителей
function renderDashboardTable(drivers = []) {
    const tbody = document.getElementById('drivers-rating-table-body');
    if (!tbody) return;
    
    if (drivers.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="4" style="text-align: center; padding: 40px; color: var(--text-light);">
                    📊 Данные для отображения отсутствуют
                </td>
            </tr>
        `;
        return;
    }
    
    tbody.innerHTML = drivers.map(driver => `
        <tr>
            <td><strong>${driver.name}</strong></td>
            <td>${driver.orders}</td>
            <td>${driver.avg_order.toLocaleString()} ₽</td>
            <td>${driver.total.toLocaleString()} ₽</td>
        </tr>
    `).join('');
}

// Добавление виджета
function addWidget() {
    const widgetsContainer = document.getElementById('widgets-container');
    const widgetId = Date.now();
    
    const widget = document.createElement('div');
    widget.className = 'widget';
    widget.innerHTML = `
        <div class="widget-header">
            <div class="widget-title">Виджет ${widgetsContainer.children.length + 1}</div>
            <button class="action-icon" onclick="removeWidget(${widgetId})" title="Удалить">🗑️</button>
        </div>
        <div>
            <p>Это пример виджета. Здесь может отображаться различная статистика и графики.</p>
            <div style="margin-top: 10px; padding: 10px; background: #f5f5f5; border-radius: 4px;">
                <small>Данные обновлены: ${new Date().toLocaleTimeString('ru-RU')}</small>
            </div>
        </div>
    `;
    widget.id = `widget-${widgetId}`;
    
    widgetsContainer.appendChild(widget);
    addActivity(`${currentUser.name} добавил виджет на рабочий стол`);
    showEnhancedNotification('Виджет добавлен', 'success');
}

// Удаление виджета
function removeWidget(widgetId) {
    const widget = document.getElementById(`widget-${widgetId}`);
    if (widget) {
        widget.remove();
        addActivity(`${currentUser.name} удалил виджет с рабочего стола`);
        showEnhancedNotification('Виджет удален', 'success');
    }
}

// Изменение раскладки
function changeLayout(layout) {
    const widgetsContainer = document.getElementById('widgets-container');
    if (!widgetsContainer) return;
    
    switch(layout) {
        case 'full':
            widgetsContainer.style.gridTemplateColumns = '1fr';
            break;
        case '50-50':
            widgetsContainer.style.gridTemplateColumns = '1fr 1fr';
            break;
        case '25-25-25-25':
            widgetsContainer.style.gridTemplateColumns = '1fr 1fr 1fr 1fr';
            break;
        case '30-70':
            widgetsContainer.style.gridTemplateColumns = '30% 70%';
            break;
        case '30-40-30':
            widgetsContainer.style.gridTemplateColumns = '30% 40% 30%';
            break;
        case '70-30':
            widgetsContainer.style.gridTemplateColumns = '70% 30%';
            break;
    }
    
    addActivity(`${currentUser.name} изменил раскладку рабочего стола на ${layout}`);
    showEnhancedNotification('Раскладка изменена', 'success');
}

// Применение фильтров дашборда
function applyDashboardFilters() {
    const dateFrom = document.getElementById('dashboard-date-from').value;
    const dateTo = document.getElementById('dashboard-date-to').value;
    const legalFilter = document.getElementById('dashboard-legal-filter').value;
    
    // Здесь должна быть логика применения фильтров
    showEnhancedNotification('Фильтры применены к дашборду', 'success');
    addActivity(`${currentUser.name} применил фильтры на рабочем столе`);
}

// Поиск на дашборде
function performDashboardSearch() {
    const search1 = document.getElementById('dashboard-search-1').value;
    const search2 = document.getElementById('dashboard-search-2').value;
    
    if (!search1 && !search2) {
        showEnhancedNotification('Введите критерии поиска', 'warning');
        return;
    }
    
    // Здесь должна быть логика поиска
    showEnhancedNotification('Поиск выполнен', 'success');
    addActivity(`${currentUser.name} выполнил поиск на рабочем столе`);
}

// Обновление дашборда
function refreshDashboard() {
    showEnhancedNotification('Обновление данных дашборда...', 'success');
    
    // Имитация обновления данных
    setTimeout(() => {
        loadAndRenderDashboard();
        showEnhancedNotification('Дашборд обновлен', 'success');
        addActivity(`${currentUser.name} обновил рабочий стол`);
    }, 1000);
}

// Загрузка и рендер дашборда
async function loadAndRenderDashboard() {
    try {
        const data = await loadDashboardData();
        renderDashboardTable(data.drivers_rating);
        
        // Здесь может быть дополнительная логика рендера виджетов
    } catch (error) {
        showEnhancedNotification('Ошибка загрузки дашборда', 'error');
    }
}

// Создание стандартных виджетов
function createDefaultWidgets() {
    const widgetsContainer = document.getElementById('widgets-container');
    if (!widgetsContainer) return;
    
    // Виджет статистики
    const statsWidget = document.createElement('div');
    statsWidget.className = 'widget';
    statsWidget.innerHTML = `
        <div class="widget-header">
            <div class="widget-title">📊 Быстрая статистика</div>
        </div>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
            <div style="text-align: center; padding: 10px; background: #e3f2fd; border-radius: 6px;">
                <div style="font-size: 24px; font-weight: bold; color: #1976d2;">85</div>
                <div style="font-size: 12px; color: #666;">Всего заявок</div>
            </div>
            <div style="text-align: center; padding: 10px; background: #e8f5e8; border-radius: 6px;">
                <div style="font-size: 24px; font-weight: bold; color: #2e7d32;">45</div>
                <div style="font-size: 12px; color: #666;">Завершено</div>
            </div>
            <div style="text-align: center; padding: 10px; background: #fff8e1; border-radius: 6px;">
                <div style="font-size: 24px; font-weight: bold; color: #ff8f00;">38</div>
                <div style="font-size: 12px; color: #666;">Активных водителей</div>
            </div>
            <div style="text-align: center; padding: 10px; background: #f3e5f5; border-radius: 6px;">
                <div style="font-size: 24px; font-weight: bold; color: #7b1fa2;">125K</div>
                <div style="font-size: 12px; color: #666;">Выручка (руб)</div>
            </div>
        </div>
    `;
    
    // Виджет последних действий
    const activityWidget = document.createElement('div');
    activityWidget.className = 'widget';
    activityWidget.innerHTML = `
        <div class="widget-header">
            <div class="widget-title">📝 Последние действия</div>
        </div>
        <div style="max-height: 200px; overflow-y: auto;">
            ${activityLog.slice(0, 5).map(activity => `
                <div style="padding: 8px; border-bottom: 1px solid #f0f0f0; font-size: 12px;">
                    <div style="font-weight: 600;">${activity.user}</div>
                    <div>${activity.action}</div>
                    <div style="color: #666; font-size: 10px;">${activity.timestamp}</div>
                </div>
            `).join('')}
        </div>
    `;
    
    widgetsContainer.appendChild(statsWidget);
    widgetsContainer.appendChild(activityWidget);
}