// Функции для работы с модальными окнами

// Открытие модального окна
function openModal(type, data = null) {
    const modal = document.getElementById(type + '-modal');
    if (modal) {
        modal.style.display = 'flex';
        setTimeout(() => {
            modal.classList.add('show');
        }, 10);
        document.body.style.overflow = 'hidden';
        
        // Заполняем данные если переданы
        if (data) {
            fillModalData(type, data);
        }
        
        // Специфическая инициализация для разных типов модальных окон
        initModal(type);
    }
}

// Закрытие модального окна
function closeModal(type) {
    const modal = document.getElementById(type + '-modal');
    if (modal) {
        modal.classList.remove('show');
        setTimeout(() => {
            modal.style.display = 'none';
            document.body.style.overflow = '';
            
            // Очищаем форму
            clearModalForm(type);
        }, 300);
    }
}

// Заполнение данных в модальное окно
function fillModalData(type, data) {
    switch(type) {
        case 'application':
            fillApplicationModal(data);
            break;
        case 'driver':
            fillDriverModal(data);
            break;
        case 'vehicle':
            fillVehicleModal(data);
            break;
        case 'user':
            fillUserModal(data);
            break;
    }
}

// Заполнение модального окна заявки
function fillApplicationModal(data) {
    document.querySelector('#application-modal [name="status"]').value = data.status || 'new';
    document.querySelector('#application-modal [name="application_number"]').value = data.application_number || '';
    document.querySelector('#application-modal [name="city"]').value = data.city || '';
    document.querySelector('#application-modal [name="trip_date"]').value = data.trip_date ? data.trip_date.split(' ')[0] : '';
    document.querySelector('#application-modal [name="trip_time"]').value = data.trip_date ? data.trip_date.split(' ')[1] : '';
    document.querySelector('#application-modal [name="service_type"]').value = data.service_type || '';
    document.querySelector('#application-modal [name="tariff"]').value = data.tariff || '';
    document.querySelector('#application-modal [name="customer_name"]').value = data.customer_name || '';
    document.querySelector('#application-modal [name="customer_phone"]').value = data.customer_phone || '';
    document.querySelector('#application-modal [name="manager_comment"]').value = data.manager_comment || '';
}

// Заполнение модального окна водителя
function fillDriverModal(data) {
    document.querySelector('#driver-modal [name="first_name"]').value = data.first_name || '';
    document.querySelector('#driver-modal [name="last_name"]').value = data.last_name || '';
    document.querySelector('#driver-modal [name="middle_name"]').value = data.middle_name || '';
    document.querySelector('#driver-modal [name="phone"]').value = data.phone || '';
    document.querySelector('#driver-modal [name="email"]').value = data.email || '';
    document.querySelector('#driver-modal [name="city"]').value = data.city || '';
    document.querySelector('#driver-modal [name="status"]').value = data.status || 'work';
}

// Заполнение модального окна автомобиля
function fillVehicleModal(data) {
    document.querySelector('#vehicle-modal [name="brand"]').value = data.brand || '';
    document.querySelector('#vehicle-modal [name="model"]').value = data.model || '';
    document.querySelector('#vehicle-modal [name="class"]').value = data.class || '';
    document.querySelector('#vehicle-modal [name="license_plate"]').value = data.license_plate || '';
    document.querySelector('#vehicle-modal [name="year"]').value = data.year || '';
    document.querySelector('#vehicle-modal [name="status"]').value = data.status || 'working';
}

// Заполнение модального окна пользователя
function fillUserModal(data) {
    document.querySelector('#user-modal [name="name"]').value = data.name || '';
    document.querySelector('#user-modal [name="email"]').value = data.email || '';
    document.querySelector('#user-modal [name="phone"]').value = data.phone || '';
    document.querySelector('#user-modal [name="role"]').value = data.role || '';
}

// Очистка формы модального окна
function clearModalForm(type) {
    const modal = document.getElementById(type + '-modal');
    if (modal) {
        const inputs = modal.querySelectorAll('input, textarea, select');
        inputs.forEach(input => {
            if (input.type !== 'button' && input.type !== 'submit') {
                input.value = '';
            }
        });
    }
}

// Инициализация модального окна
function initModal(type) {
    switch(type) {
        case 'application':
            initApplicationModal();
            break;
        case 'vehicle':
            initVehicleModal();
            break;
    }
}

// Инициализация модального окна заявки
function initApplicationModal() {
    // Устанавливаем текущую дату по умолчанию
    const now = new Date();
    const dateInput = document.querySelector('#application-modal [name="trip_date"]');
    if (dateInput && !dateInput.value) {
        dateInput.value = now.toISOString().split('T')[0];
    }
    
    // Устанавливаем текущее время + 1 час по умолчанию
    const timeInput = document.querySelector('#application-modal [name="trip_time"]');
    if (timeInput && !timeInput.value) {
        const nextHour = new Date(now.getTime() + 60 * 60 * 1000);
        timeInput.value = nextHour.toTimeString().split(' ')[0].substring(0, 5);
    }
}

// Инициализация модального окна автомобиля
function initVehicleModal() {
    // Активируем автодополнение для марок и моделей
    const brandInput = document.getElementById('modal-vehicle-brand');
    const modelInput = document.getElementById('modal-vehicle-model');
    
    if (brandInput) {
        brandInput.addEventListener('input', showBrandOptions);
    }
    
    if (modelInput) {
        modelInput.addEventListener('input', showModelOptions);
    }
}

// Открытие модального окна заявки
function openApplicationModal(applicationData = null) {
    openModal('application', applicationData);
}

// Открытие модального окна водителя
function openDriverModal(driverData = null) {
    openModal('driver', driverData);
}

// Открытие модального окна автомобиля
function openVehicleModal(vehicleData = null) {
    openModal('vehicle', vehicleData);
}

// Открытие модального окна пользователя
function openUserModal(userData = null) {
    openModal('user', userData);
}

// Закрытие всех модальных окон
function closeAllModals() {
    const modals = document.querySelectorAll('.modal');
    modals.forEach(modal => {
        const modalId = modal.id.replace('-modal', '');
        closeModal(modalId);
    });
}

// Обработчик Escape для закрытия модальных окон
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeAllModals();
    }
});