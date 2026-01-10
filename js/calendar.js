// Функции для работы с календарем

let selectedDate = new Date();

// Инициализация календаря
function initCalendar() {
    const now = new Date();
    const currentMonth = now.getMonth();
    const currentYear = now.getFullYear();
    
    // Заполняем месяцы
    const monthSelect = document.getElementById('calendar-month');
    const months = ['Январь', 'Февраль', 'Март', 'Апрель', 'Май', 'Июнь', 
                   'Июль', 'Август', 'Сентябрь', 'Октябрь', 'Ноябрь', 'Декабрь'];
    
    monthSelect.innerHTML = months.map((month, index) => 
        `<option value="${index}" ${index === currentMonth ? 'selected' : ''}>${month}</option>`
    ).join('');
    
    // Заполняем годы
    const yearSelect = document.getElementById('calendar-year');
    const years = [];
    for (let year = currentYear - 5; year <= currentYear + 5; year++) {
        years.push(year);
    }
    
    yearSelect.innerHTML = years.map(year => 
        `<option value="${year}" ${year === currentYear ? 'selected' : ''}>${year}</option>`
    ).join('');
    
    renderCalendar(currentMonth, currentYear);
}

// Рендер календаря
function renderCalendar(month, year) {
    const calendarGrid = document.getElementById('calendar-grid');
    if (!calendarGrid) return;

    // Первый день месяца
    const firstDay = new Date(year, month, 1);
    // Последний день месяца
    const lastDay = new Date(year, month + 1, 0);
    // Первый день календаря (может быть из предыдущего месяца)
    const startDate = new Date(firstDay);
    startDate.setDate(startDate.getDate() - firstDay.getDay());
    
    calendarGrid.innerHTML = '';
    
    // Заголовки дней недели
    const daysOfWeek = ['Вс', 'Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб'];
    daysOfWeek.forEach(day => {
        const dayHeader = document.createElement('div');
        dayHeader.className = 'calendar-day-header';
        dayHeader.textContent = day;
        dayHeader.style.fontWeight = '600';
        dayHeader.style.fontSize = '12px';
        dayHeader.style.textAlign = 'center';
        dayHeader.style.padding = '5px';
        calendarGrid.appendChild(dayHeader);
    });
    
    // Дни календаря
    for (let i = 0; i < 42; i++) {
        const currentDateObj = new Date(startDate);
        currentDateObj.setDate(startDate.getDate() + i);
        
        const dayElement = document.createElement('div');
        dayElement.className = 'calendar-day';
        
        if (currentDateObj.getMonth() !== month) {
            dayElement.classList.add('other-month');
        }
        
        if (currentDateObj.toDateString() === selectedDate.toDateString()) {
            dayElement.classList.add('active');
        }
        
        // Проверяем есть ли заявки на эту дату
        const hasOrders = checkOrdersForDate(currentDateObj);
        
        if (hasOrders) {
            dayElement.classList.add('has-orders');
        }
        
        dayElement.textContent = currentDateObj.getDate();
        dayElement.onclick = () => selectDate(currentDateObj);
        
        calendarGrid.appendChild(dayElement);
    }
}

// Проверка заявок на дату
function checkOrdersForDate(date) {
    const dateStr = date.toISOString().split('T')[0];
    return mockApplications.some(app => {
        const appDate = new Date(app.trip_date).toISOString().split('T')[0];
        return appDate === dateStr;
    });
}

// Выбор даты
function selectDate(date) {
    selectedDate = date;
    renderCalendar(date.getMonth(), date.getFullYear());
    
    // Обновляем фильтры на дашборде
    const dateFrom = document.getElementById('date-from');
    const dateTo = document.getElementById('date-to');
    
    if (dateFrom) dateFrom.value = date.toISOString().split('T')[0];
    if (dateTo) dateTo.value = date.toISOString().split('T')[0];
    
    // Показываем заявки на выбранную дату
    showApplicationsForDate(date);
    
    addActivity(`${currentUser.name} выбрал дату: ${date.toLocaleDateString('ru-RU')}`);
    showEnhancedNotification(`Выбрана дата: ${date.toLocaleDateString('ru-RU')}`, 'success');
}

// Показать заявки на дату
function showApplicationsForDate(date) {
    const dateStr = date.toISOString().split('T')[0];
    const applicationsForDate = mockApplications.filter(app => {
        const appDate = new Date(app.trip_date).toISOString().split('T')[0];
        return appDate === dateStr;
    });
    
    if (applicationsForDate.length > 0) {
        // Показываем секцию заявок
        showSection('applications');
        
        // Обновляем таблицу заявок
        renderApplicationsTable(applicationsForDate);
        
        // Показываем уведомление
        showEnhancedNotification(`Найдено ${applicationsForDate.length} заявок на ${date.toLocaleDateString('ru-RU')}`, 'success');
    } else {
        showEnhancedNotification(`На ${date.toLocaleDateString('ru-RU')} заявок не найдено`, 'error');
    }
}

// Изменение месяца
function changeCalendarMonth() {
    const month = parseInt(document.getElementById('calendar-month').value);
    const year = parseInt(document.getElementById('calendar-year').value);
    renderCalendar(month, year);
}

// Изменение года
function changeCalendarYear() {
    changeCalendarMonth();
}

// Получение текущей выбранной даты
function getSelectedDate() {
    return selectedDate;
}

// Установка даты
function setDate(date) {
    selectedDate = new Date(date);
    renderCalendar(selectedDate.getMonth(), selectedDate.getFullYear());
}

// Получение заявок на период
function getApplicationsForPeriod(startDate, endDate) {
    return mockApplications.filter(app => {
        const appDate = new Date(app.trip_date);
        return appDate >= startDate && appDate <= endDate;
    });
}