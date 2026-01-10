// Моковые данные для аналитики
const mockAnalytics = {
    general: {
        total_applications: 85,
        new_applications: 12,
        inwork_applications: 15,
        completed_applications: 45,
        cancelled_applications: 5,
        avg_cost: 1250,
        monthly_growth: 15
    },
    drivers: {
        total_drivers: 45,
        active_drivers: 38,
        vacation_drivers: 5,
        avg_rating: 4.7,
        total_earnings: 210000,
        avg_driver_order: 1150
    },
    vehicles: {
        total_vehicles: 32,
        working_vehicles: 28,
        repair_vehicles: 4,
        avg_mileage: 85600,
        total_costs: 156000,
        popular_class: "comfort"
    },
    users: {
        total_users: 172,
        admin_users: 3,
        manager_users: 8,
        driver_users: 45,
        client_users: 116,
        active_today: 89
    },
    companies: {
        total_companies: 15,
        active_companies: 12,
        corporate_clients: 8,
        avg_company_check: 3850,
        total_company_revenue: 1560000,
        best_client: "ООО Газпром трансфер"
    },
    finance: {
        total_revenue: 890000,
        total_expenses: 356000,
        total_profit: 534000,
        total_taxes: 106800,
        net_profit: 427200,
        profitability: 48.0
    }
};

// Загрузка аналитики
async function loadAnalytics(type, period = 'month') {
    try {
        const result = await apiRequest('analytics', 'GET', null, { type, period });
        return result.data;
    } catch (error) {
        console.log('Используем моковые данные для аналитики');
        return mockAnalytics[type] || {};
    }
}

// Рендер аналитики
async function renderAnalytics() {
    await renderAnalyticsForTab('general');
}

// Рендер аналитики для конкретной вкладки
async function renderAnalyticsForTab(tabName) {
    try {
        const analyticsData = await loadAnalytics(tabName);
        
        // Уничтожаем старые диаграммы если они есть
        if (charts[tabName]) {
            charts[tabName].forEach(chart => chart.destroy());
        }
        charts[tabName] = [];
        
        switch(tabName) {
            case 'general':
                createGeneralCharts(analyticsData);
                updateGeneralAnalytics(analyticsData);
                break;
            case 'drivers':
                createDriversCharts(analyticsData);
                updateDriversAnalytics(analyticsData);
                break;
            case 'vehicles':
                createVehiclesCharts(analyticsData);
                updateVehiclesAnalytics(analyticsData);
                break;
            case 'users':
                createUsersCharts(analyticsData);
                updateUsersAnalytics(analyticsData);
                break;
            case 'companies':
                createCompaniesCharts(analyticsData);
                updateCompaniesAnalytics(analyticsData);
                break;
            case 'finance':
                createFinanceCharts(analyticsData);
                updateFinanceAnalytics(analyticsData);
                break;
        }
        
        addActivity(`${currentUser.name} просматривает аналитику: ${tabName}`);
        
    } catch (error) {
        console.error('Ошибка рендера аналитики:', error);
        showEnhancedNotification('Ошибка загрузки аналитики', 'error');
    }
}

// Обновление общей аналитики
function updateGeneralAnalytics(data) {
    // Обновляем summary карточки
    document.getElementById('total-applications').textContent = data.total_applications || 0;
    document.getElementById('new-applications').textContent = data.new_applications || 0;
    document.getElementById('inwork-applications').textContent = data.inwork_applications || 0;
    document.getElementById('completed-applications').textContent = data.completed_applications || 0;
    document.getElementById('cancelled-applications').textContent = data.cancelled_applications || 0;
    document.getElementById('avg-cost').textContent = (data.avg_cost || 0).toLocaleString() + ' ₽';
    
    // Обновляем таблицу
    const tbody = document.getElementById('general-analytics-body');
    if (tbody) {
        tbody.innerHTML = `
            <tr>
                <td>Всего заявок</td>
                <td>${data.total_applications || 0}</td>
                <td>+${Math.floor((data.total_applications || 0) * 0.15)} (15%)</td>
                <td>100%</td>
            </tr>
            <tr>
                <td>Новые заявки</td>
                <td>${data.new_applications || 0}</td>
                <td>+${Math.floor((data.new_applications || 0) * 0.1)} (10%)</td>
                <td>${(((data.new_applications || 0) / (data.total_applications || 1)) * 100).toFixed(1)}%</td>
            </tr>
            <tr>
                <td>Заявки в работе</td>
                <td>${data.inwork_applications || 0}</td>
                <td>+${Math.floor((data.inwork_applications || 0) * 0.2)} (20%)</td>
                <td>${(((data.inwork_applications || 0) / (data.total_applications || 1)) * 100).toFixed(1)}%</td>
            </tr>
            <tr>
                <td>Завершенные заявки</td>
                <td>${data.completed_applications || 0}</td>
                <td>+${Math.floor((data.completed_applications || 0) * 0.25)} (25%)</td>
                <td>${(((data.completed_applications || 0) / (data.total_applications || 1)) * 100).toFixed(1)}%</td>
            </tr>
            <tr>
                <td>Отмененные заявки</td>
                <td>${data.cancelled_applications || 0}</td>
                <td>${Math.floor((data.cancelled_applications || 0) * 0.05)} (5%)</td>
                <td>${(((data.cancelled_applications || 0) / (data.total_applications || 1)) * 100).toFixed(1)}%</td>
            </tr>
        `;
    }
}

// Обновление аналитики по водителям
function updateDriversAnalytics(data) {
    document.getElementById('total-drivers').textContent = data.total_drivers || 0;
    document.getElementById('active-drivers').textContent = data.active_drivers || 0;
    document.getElementById('vacation-drivers').textContent = data.vacation_drivers || 0;
    document.getElementById('avg-rating').textContent = (data.avg_rating || 0).toFixed(1);
    document.getElementById('total-earnings').textContent = (data.total_earnings || 0).toLocaleString() + ' ₽';
    document.getElementById('avg-driver-order').textContent = (data.avg_driver_order || 0).toLocaleString() + ' ₽';
    
    const tbody = document.getElementById('drivers-analytics-body');
    if (tbody) {
        // Используем моковые данные водителей для таблицы
        tbody.innerHTML = mockDrivers.map(driver => `
            <tr>
                <td>${driver.last_name} ${driver.first_name}</td>
                <td>${Math.floor(Math.random() * 50) + 10}</td>
                <td>${driver.rating}</td>
                <td>${driver.total_earnings.toLocaleString()} ₽</td>
                <td>${getDriverStatusText(driver.status)}</td>
            </tr>
        `).join('');
    }
}

// Обновление аналитики по автомобилям
function updateVehiclesAnalytics(data) {
    document.getElementById('total-vehicles').textContent = data.total_vehicles || 0;
    document.getElementById('working-vehicles').textContent = data.working_vehicles || 0;
    document.getElementById('repair-vehicles').textContent = data.repair_vehicles || 0;
    document.getElementById('avg-mileage').textContent = (data.avg_mileage || 0).toLocaleString() + ' км';
    document.getElementById('total-costs').textContent = (data.total_costs || 0).toLocaleString() + ' ₽';
    document.getElementById('popular-class').textContent = getVehicleClassText(data.popular_class) || '-';
    
    const tbody = document.getElementById('vehicles-analytics-body');
    if (tbody) {
        tbody.innerHTML = mockVehicles.map(vehicle => `
            <tr>
                <td>${vehicle.brand} ${vehicle.model}</td>
                <td>${getVehicleClassText(vehicle.class)}</td>
                <td>${vehicle.mileage.toLocaleString()} км</td>
                <td>${getVehicleStatusText(vehicle.status)}</td>
                <td>${Math.floor(Math.random() * 15000) + 5000} ₽</td>
            </tr>
        `).join('');
    }
}

// Получение текста класса автомобиля
function getVehicleClassText(vehicleClass) {
    const classMap = {
        'standard': 'Стандарт',
        'comfort': 'Комфорт',
        'business': 'Бизнес',
        'premium': 'Премиум',
        'crossover': 'Кроссовер',
        'minivan5': 'Минивэн 5',
        'minivan6': 'Минивэн 6',
        'microbus8': 'Микроавтобус 8',
        'microbus10': 'Микроавтобус 10',
        'microbus14': 'Микроавтобус 14',
        'microbus16': 'Микроавтобус 16',
        'microbus18': 'Микроавтобус 18',
        'microbus24': 'Микроавтобус 24',
        'bus35': 'Автобус 35',
        'bus44': 'Автобус 44',
        'bus50': 'Автобус 50',
        'other': 'Иное'
    };
    return classMap[vehicleClass] || vehicleClass;
}

// Обновление аналитики по пользователям
function updateUsersAnalytics(data) {
    document.getElementById('total-users').textContent = data.total_users || 0;
    document.getElementById('admin-users').textContent = data.admin_users || 0;
    document.getElementById('manager-users').textContent = data.manager_users || 0;
    document.getElementById('driver-users').textContent = data.driver_users || 0;
    document.getElementById('client-users').textContent = data.client_users || 0;
    document.getElementById('active-today').textContent = data.active_today || 0;
    
    const tbody = document.getElementById('users-analytics-body');
    if (tbody) {
        const roles = [
            { role: 'Администраторы', count: data.admin_users || 0, active: data.admin_users || 0, blocked: 0 },
            { role: 'Менеджеры', count: data.manager_users || 0, active: data.manager_users || 0, blocked: 0 },
            { role: 'Водители', count: data.driver_users || 0, active: (data.driver_users || 0) - 2, blocked: 2 },
            { role: 'Клиенты', count: data.client_users || 0, active: (data.client_users || 0) - 5, blocked: 5 }
        ];
        
        tbody.innerHTML = roles.map(role => `
            <tr>
                <td>${role.role}</td>
                <td>${role.count}</td>
                <td>${role.active}</td>
                <td>${role.blocked}</td>
                <td>${((role.count / (data.total_users || 1)) * 100).toFixed(1)}%</td>
            </tr>
        `).join('');
    }
}

// Обновление аналитики по компаниям
function updateCompaniesAnalytics(data) {
    document.getElementById('total-companies').textContent = data.total_companies || 0;
    document.getElementById('active-companies').textContent = data.active_companies || 0;
    document.getElementById('corporate-clients').textContent = data.corporate_clients || 0;
    document.getElementById('avg-company-check').textContent = (data.avg_company_check || 0).toLocaleString() + ' ₽';
    document.getElementById('total-company-revenue').textContent = (data.total_company_revenue || 0).toLocaleString() + ' ₽';
    document.getElementById('best-client').textContent = data.best_client || '-';
    
    const tbody = document.getElementById('companies-analytics-body');
    if (tbody) {
        // Используем моковые данные компаний
        const mockCompaniesData = [
            { name: "ООО ТрансферСервис", orders: 120, total_amount: 450000, avg_check: 3750, status: "active" },
            { name: "ИП Козлов", orders: 45, total_amount: 156000, avg_check: 3467, status: "active" },
            { name: "ООО Газпром трансфер", orders: 200, total_amount: 890000, avg_check: 4450, status: "active" },
            { name: "АО РЖД Логистика", orders: 85, total_amount: 320000, avg_check: 3765, status: "active" }
        ];
        
        tbody.innerHTML = mockCompaniesData.map(company => `
            <tr>
                <td>${company.name}</td>
                <td>${company.orders}</td>
                <td>${company.total_amount.toLocaleString()} ₽</td>
                <td>${company.avg_check.toLocaleString()} ₽</td>
                <td>${company.status === 'active' ? 'Активна' : 'Неактивна'}</td>
            </tr>
        `).join('');
    }
}

// Обновление финансовой аналитики
function updateFinanceAnalytics(data) {
    document.getElementById('total-revenue').textContent = (data.total_revenue || 0).toLocaleString() + ' ₽';
    document.getElementById('total-expenses').textContent = (data.total_expenses || 0).toLocaleString() + ' ₽';
    document.getElementById('total-profit').textContent = (data.total_profit || 0).toLocaleString() + ' ₽';
    document.getElementById('total-taxes').textContent = (data.total_taxes || 0).toLocaleString() + ' ₽';
    document.getElementById('net-profit').textContent = (data.net_profit || 0).toLocaleString() + ' ₽';
    document.getElementById('profitability').textContent = (data.profitability || 0).toFixed(1) + '%';
    
    const tbody = document.getElementById('finance-analytics-body');
    if (tbody) {
        const currentMonth = {
            revenue: data.total_revenue || 0,
            expenses: data.total_expenses || 0,
            profit: data.total_profit || 0,
            taxes: data.total_taxes || 0,
            netProfit: data.net_profit || 0
        };
        
        const lastMonth = {
            revenue: Math.round((data.total_revenue || 0) * 0.85),
            expenses: Math.round((data.total_expenses || 0) * 0.9),
            profit: Math.round((data.total_profit || 0) * 0.8),
            taxes: Math.round((data.total_taxes || 0) * 0.8),
            netProfit: Math.round((data.net_profit || 0) * 0.8)
        };
        
        const nextMonth = {
            revenue: Math.round((data.total_revenue || 0) * 1.15),
            expenses: Math.round((data.total_expenses || 0) * 1.1),
            profit: Math.round((data.total_profit || 0) * 1.2),
            taxes: Math.round((data.total_taxes || 0) * 1.2),
            netProfit: Math.round((data.net_profit || 0) * 1.2)
        };
        
        tbody.innerHTML = `
            <tr>
                <td>Выручка</td>
                <td>${currentMonth.revenue.toLocaleString()} ₽</td>
                <td>${lastMonth.revenue.toLocaleString()} ₽</td>
                <td style="color: ${currentMonth.revenue >= lastMonth.revenue ? 'var(--success)' : 'var(--danger)'}">
                    ${currentMonth.revenue >= lastMonth.revenue ? '+' : ''}${(currentMonth.revenue - lastMonth.revenue).toLocaleString()} ₽
                </td>
                <td>${nextMonth.revenue.toLocaleString()} ₽</td>
            </tr>
            <tr>
                <td>Расходы</td>
                <td>${currentMonth.expenses.toLocaleString()} ₽</td>
                <td>${lastMonth.expenses.toLocaleString()} ₽</td>
                <td style="color: ${currentMonth.expenses <= lastMonth.expenses ? 'var(--success)' : 'var(--danger)'}">
                    ${currentMonth.expenses <= lastMonth.expenses ? '+' : ''}${(currentMonth.expenses - lastMonth.expenses).toLocaleString()} ₽
                </td>
                <td>${nextMonth.expenses.toLocaleString()} ₽</td>
            </tr>
            <tr>
                <td>Прибыль</td>
                <td>${currentMonth.profit.toLocaleString()} ₽</td>
                <td>${lastMonth.profit.toLocaleString()} ₽</td>
                <td style="color: ${currentMonth.profit >= lastMonth.profit ? 'var(--success)' : 'var(--danger)'}">
                    ${currentMonth.profit >= lastMonth.profit ? '+' : ''}${(currentMonth.profit - lastMonth.profit).toLocaleString()} ₽
                </td>
                <td>${nextMonth.profit.toLocaleString()} ₽</td>
            </tr>
            <tr>
                <td>Налоги</td>
                <td>${currentMonth.taxes.toLocaleString()} ₽</td>
                <td>${lastMonth.taxes.toLocaleString()} ₽</td>
                <td style="color: ${currentMonth.taxes >= lastMonth.taxes ? 'var(--success)' : 'var(--danger)'}">
                    ${currentMonth.taxes >= lastMonth.taxes ? '+' : ''}${(currentMonth.taxes - lastMonth.taxes).toLocaleString()} ₽
                </td>
                <td>${nextMonth.taxes.toLocaleString()} ₽</td>
            </tr>
            <tr>
                <td>Чистая прибыль</td>
                <td>${currentMonth.netProfit.toLocaleString()} ₽</td>
                <td>${lastMonth.netProfit.toLocaleString()} ₽</td>
                <td style="color: ${currentMonth.netProfit >= lastMonth.netProfit ? 'var(--success)' : 'var(--danger)'}">
                    ${currentMonth.netProfit >= lastMonth.netProfit ? '+' : ''}${(currentMonth.netProfit - lastMonth.netProfit).toLocaleString()} ₽
                </td>
                <td>${nextMonth.netProfit.toLocaleString()} ₽</td>
            </tr>
        `;
    }
}

// Создание диаграмм для общей аналитики
function createGeneralCharts(data) {
    // Общая статистика - круговая диаграмма
    const generalCtx = document.getElementById('generalChart');
    if (generalCtx) {
        const generalChart = new Chart(generalCtx, {
            type: 'doughnut',
            data: {
                labels: ['Новые', 'Подтвержденные', 'В работе', 'Завершенные', 'Отмененные'],
                datasets: [{
                    data: [
                        data.new_applications || 0,
                        8, // Подтвержденные
                        data.inwork_applications || 0,
                        data.completed_applications || 0,
                        data.cancelled_applications || 0
                    ],
                    backgroundColor: [
                        '#1976d2', '#ff8f00', '#2e7d32', '#7b1fa2', '#c62828'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    title: {
                        display: true,
                        text: 'Распределение заявок по статусам'
                    },
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
        charts.general.push(generalChart);
    }
    
    // Эффективность - линейная диаграмма
    const efficiencyCtx = document.getElementById('efficiencyChart');
    if (efficiencyCtx) {
        const efficiencyChart = new Chart(efficiencyCtx, {
            type: 'line',
            data: {
                labels: ['Янв', 'Фев', 'Мар', 'Апр', 'Май', 'Июн'],
                datasets: [{
                    label: 'Завершенные заявки',
                    data: [65, 59, 80, 81, 56, 72],
                    borderColor: '#1976d2',
                    backgroundColor: 'rgba(25, 118, 210, 0.1)',
                    fill: true,
                    tension: 0.4
                }, {
                    label: 'Новые заявки',
                    data: [28, 48, 40, 19, 86, 27],
                    borderColor: '#ff8f00',
                    backgroundColor: 'rgba(255, 143, 0, 0.1)',
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    title: {
                        display: true,
                        text: 'Динамика заявок по месяцам'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
        charts.general.push(efficiencyChart);
    }
}

// Создание диаграмм для водителей
function createDriversCharts(data) {
    const driversCtx = document.getElementById('driversChart');
    if (driversCtx) {
        const driversChart = new Chart(driversCtx, {
            type: 'bar',
            data: {
                labels: ['Сидоров А.П.', 'Козлов Д.И.', 'Иванов С.В.', 'Петров А.Н.'],
                datasets: [{
                    label: 'Количество заказов',
                    data: [45, 38, 52, 29],
                    backgroundColor: '#1976d2'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    title: {
                        display: true,
                        text: 'Заказы по водителям'
                    }
                }
            }
        });
        charts.drivers.push(driversChart);
    }
    
    const driversFinanceCtx = document.getElementById('driversFinanceChart');
    if (driversFinanceCtx) {
        const driversFinanceChart = new Chart(driversFinanceCtx, {
            type: 'pie',
            data: {
                labels: ['Зарплата', 'Премии', 'Налоги', 'Штрафы'],
                datasets: [{
                    data: [65, 15, 15, 5],
                    backgroundColor: ['#1976d2', '#2e7d32', '#ff8f00', '#c62828']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    title: {
                        display: true,
                        text: 'Распределение выплат водителям'
                    },
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
        charts.drivers.push(driversFinanceChart);
    }
}

// Создание диаграмм для автомобилей
function createVehiclesCharts(data) {
    const vehiclesCtx = document.getElementById('vehiclesChart');
    if (vehiclesCtx) {
        const vehiclesChart = new Chart(vehiclesCtx, {
            type: 'doughnut',
            data: {
                labels: ['Toyota', 'Hyundai', 'Mercedes', 'BYD', 'Другие'],
                datasets: [{
                    data: [35, 25, 20, 15, 5],
                    backgroundColor: ['#1976d2', '#2e7d32', '#ff8f00', '#c62828', '#7b1fa2']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    title: {
                        display: true,
                        text: 'Распределение автомобилей по маркам'
                    },
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
        charts.vehicles.push(vehiclesChart);
    }
    
    const vehiclesCostsCtx = document.getElementById('vehiclesCostsChart');
    if (vehiclesCostsCtx) {
        const vehiclesCostsChart = new Chart(vehiclesCostsCtx, {
            type: 'line',
            data: {
                labels: ['Янв', 'Фев', 'Мар', 'Апр', 'Май', 'Июн'],
                datasets: [{
                    label: 'Топливо',
                    data: [12000, 19000, 15000, 25000, 22000, 30000],
                    borderColor: '#1976d2',
                    backgroundColor: 'rgba(25, 118, 210, 0.1)',
                    fill: true,
                    tension: 0.4
                }, {
                    label: 'ТО и ремонт',
                    data: [8000, 12000, 10000, 15000, 18000, 20000],
                    borderColor: '#2e7d32',
                    backgroundColor: 'rgba(46, 125, 50, 0.1)',
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    title: {
                        display: true,
                        text: 'Динамика затрат на автомобили'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
        charts.vehicles.push(vehiclesCostsChart);
    }
}

// Создание диаграмм для пользователей
function createUsersCharts(data) {
    const usersCtx = document.getElementById('usersChart');
    if (usersCtx) {
        const usersChart = new Chart(usersCtx, {
            type: 'bar',
            data: {
                labels: ['Администраторы', 'Менеджеры', 'Водители', 'Клиенты'],
                datasets: [{
                    label: 'Количество пользователей',
                    data: [
                        data.admin_users || 0,
                        data.manager_users || 0,
                        data.driver_users || 0,
                        data.client_users || 0
                    ],
                    backgroundColor: '#1976d2'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    title: {
                        display: true,
                        text: 'Пользователи по ролям'
                    }
                }
            }
        });
        charts.users.push(usersChart);
    }
    
    const usersActivityCtx = document.getElementById('usersActivityChart');
    if (usersActivityCtx) {
        const usersActivityChart = new Chart(usersActivityCtx, {
            type: 'line',
            data: {
                labels: ['Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб', 'Вс'],
                datasets: [{
                    label: 'Активные сессии',
                    data: [65, 59, 80, 81, 56, 55, 40],
                    borderColor: '#1976d2',
                    backgroundColor: 'rgba(25, 118, 210, 0.1)',
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    title: {
                        display: true,
                        text: 'Активность пользователей по дням недели'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
        charts.users.push(usersActivityChart);
    }
}

// Создание диаграмм для компаний
function createCompaniesCharts(data) {
    const companiesCtx = document.getElementById('companiesChart');
    if (companiesCtx) {
        const companiesChart = new Chart(companiesCtx, {
            type: 'pie',
            data: {
                labels: ['ООО ТрансферСервис', 'ИП Козлов', 'ООО Газпром трансфер', 'АО РЖД Логистика'],
                datasets: [{
                    data: [40, 25, 20, 15],
                    backgroundColor: ['#1976d2', '#2e7d32', '#ff8f00', '#c62828']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    title: {
                        display: true,
                        text: 'Распределение по компаниям'
                    },
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
        charts.companies.push(companiesChart);
    }
    
    const businessCtx = document.getElementById('businessChart');
    if (businessCtx) {
        const businessChart = new Chart(businessCtx, {
            type: 'radar',
            data: {
                labels: ['Выручка', 'Прибыль', 'Клиенты', 'Заказы', 'Удовлетворенность'],
                datasets: [{
                    label: 'Текущий месяц',
                    data: [85, 75, 90, 80, 95],
                    borderColor: '#1976d2',
                    backgroundColor: 'rgba(25, 118, 210, 0.2)',
                    fill: true
                }, {
                    label: 'Прошлый месяц',
                    data: [70, 65, 75, 70, 85],
                    borderColor: '#2e7d32',
                    backgroundColor: 'rgba(46, 125, 50, 0.2)',
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    title: {
                        display: true,
                        text: 'Бизнес-показатели'
                    }
                },
                scales: {
                    r: {
                        beginAtZero: true,
                        max: 100
                    }
                }
            }
        });
        charts.companies.push(businessChart);
    }
}

// Создание финансовых диаграмм
function createFinanceCharts(data) {
    const financeCtx = document.getElementById('financeChart');
    if (financeCtx) {
        const financeChart = new Chart(financeCtx, {
            type: 'bar',
            data: {
                labels: ['Выручка', 'Расходы', 'Прибыль', 'Налоги', 'Чистая прибыль'],
                datasets: [{
                    label: 'Текущий месяц',
                    data: [
                        data.total_revenue || 0,
                        data.total_expenses || 0,
                        data.total_profit || 0,
                        data.total_taxes || 0,
                        data.net_profit || 0
                    ],
                    backgroundColor: '#1976d2'
                }, {
                    label: 'Прошлый месяц',
                    data: [
                        Math.round((data.total_revenue || 0) * 0.85),
                        Math.round((data.total_expenses || 0) * 0.9),
                        Math.round((data.total_profit || 0) * 0.8),
                        Math.round((data.total_taxes || 0) * 0.8),
                        Math.round((data.net_profit || 0) * 0.8)
                    ],
                    backgroundColor: '#2e7d32'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    title: {
                        display: true,
                        text: 'Финансовые показатели'
                    }
                }
            }
        });
        charts.finance.push(financeChart);
    }
    
    const monthlyFinanceCtx = document.getElementById('monthlyFinanceChart');
    if (monthlyFinanceCtx) {
        const monthlyFinanceChart = new Chart(monthlyFinanceCtx, {
            type: 'line',
            data: {
                labels: ['Янв', 'Фев', 'Мар', 'Апр', 'Май', 'Июн'],
                datasets: [{
                    label: 'Выручка',
                    data: [650000, 720000, 780000, 820000, 890000, 950000],
                    borderColor: '#1976d2',
                    backgroundColor: 'rgba(25, 118, 210, 0.1)',
                    fill: true,
                    tension: 0.4
                }, {
                    label: 'Расходы',
                    data: [320000, 340000, 350000, 345000, 356000, 380000],
                    borderColor: '#c62828',
                    backgroundColor: 'rgba(198, 40, 40, 0.1)',
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    title: {
                        display: true,
                        text: 'Финансы по месяцам'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
        charts.finance.push(monthlyFinanceChart);
    }
}

// Экспорт аналитики в Excel
function exportAnalyticsToExcel() {
    showEnhancedNotification('Подготовка экспорта аналитики в Excel...', 'success');
    
    // Имитация экспорта
    setTimeout(() => {
        showEnhancedNotification('Аналитика успешно экспортирована в Excel!', 'success');
        addActivity(`${currentUser.name} экспортировал аналитику в Excel`);
    }, 2000);
}