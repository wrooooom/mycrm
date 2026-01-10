// Утилитарные функции для CRM системы

// Данные для выпадающих списков
const citiesData = {
    ru: [
        "Москва", "Санкт-Петербург", "Новосибирск", "Екатеринбург", "Казань",
        "Нижний Новгород", "Челябинск", "Самара", "Омск", "Ростов-на-Дону",
        "Уфа", "Красноярск", "Воронеж", "Пермь", "Волгоград", "Краснодар",
        "Саратов", "Тюмень", "Тольятти", "Ижевск", "Барнаул", "Ульяновск",
        "Иркутск", "Хабаровск", "Ярославль", "Владивосток", "Махачкала",
        "Томск", "Оренбург", "Кемерово", "Новокузнецк", "Рязань", "Астрахань",
        "Пенза", "Липецк", "Киров", "Чебоксары", "Тула", "Калининград"
    ],
    by: [
        "Минск", "Гомель", "Могилёв", "Витебск", "Гродно", "Брест", "Барановичи",
        "Борисов", "Пинск", "Орша", "Мозырь", "Солигорск", "Новополоцк", "Лида"
    ]
};

// Марки и модели автомобилей
const carBrands = {
    "Audi": ["A3", "A4", "A5", "A6", "A7", "A8", "Q2", "Q3", "Q5", "Q7", "Q8"],
    "BMW": ["1 Series", "2 Series", "3 Series", "4 Series", "5 Series", "6 Series", "7 Series", "8 Series", "X1", "X2", "X3", "X4", "X5", "X6", "X7"],
    "BYD": ["Han", "Tang", "Song", "Yuan", "Qin", "E2", "E3", "Dolphin", "Seal", "Atto 3"],
    "Cadillac": ["CT4", "CT5", "CT6", "XT4", "XT5", "XT6", "Escalade"],
    "Chery": ["Tiggo 4", "Tiggo 7", "Tiggo 8", "Arrizo 6", "Arrizo 8"],
    "Chevrolet": ["Camaro", "Corvette", "Malibu", "Trax", "Trailblazer", "Equinox", "Blazer", "Traverse", "Tahoe", "Suburban"],
    "Ford": ["Focus", "Mondeo", "Mustang", "Kuga", "Escape", "Explorer", "Expedition", "Ranger", "F-150", "Transit"],
    "Geely": ["Atlas", "Coolray", "Tugella", "Emgrand 7", "Emgrand 8", "Monjaro", "Okavango"],
    "Genesis": ["G70", "G80", "G90", "GV70", "GV80"],
    "Haval": ["F7", "H6", "Jolion", "Dargo", "M6"],
    "Honda": ["Accord", "Civic", "CR-V", "HR-V", "Pilot", "Odyssey"],
    "Hongqi": ["H5", "H7", "H9", "E-HS9", "HS5", "HS7"],
    "Hyundai": ["Elantra", "Sonata", "Solaris", "Creta", "Tucson", "Santa Fe", "Palisade", "Kona", "Bayon"],
    "Kia": ["Rio", "Cerato", "Optima", "Stinger", "Soul", "Seltos", "Sportage", "Sorento", "Telluride", "Carnival"],
    "Lada": ["Granta", "Vesta", "Largus", "Niva", "XRAY"],
    "Land Rover": ["Defender", "Discovery", "Discovery Sport", "Range Rover", "Range Rover Sport", "Range Rover Velar", "Range Rover Evoque"],
    "Lexus": ["ES", "GS", "IS", "LS", "LC", "UX", "NX", "RX", "GX", "LX"],
    "Mazda": ["2", "3", "6", "CX-3", "CX-30", "CX-5", "CX-9", "MX-5"],
    "Mercedes-Benz": ["A-Class", "B-Class", "C-Class", "E-Class", "S-Class", "CLA", "CLS", "GLA", "GLB", "GLC", "GLE", "GLS", "G-Class", "V-Class"],
    "Mitsubishi": ["Outlander", "Eclipse Cross", "Pajero Sport"],
    "Nissan": ["Almera", "Altima", "Qashqai", "X-Trail", "Murano", "Pathfinder", "Leaf"],
    "Peugeot": ["208", "308", "508", "2008", "3008", "5008"],
    "Porsche": ["911", "Panamera", "Macan", "Cayenne", "Taycan"],
    "Renault": ["Arkana", "Koleos", "Megane", "Talisman", "Kangoo", "Trafic", "Master"],
    "Skoda": ["Fabia", "Rapid", "Octavia", "Superb", "Kamiq", "Karoq", "Kodiaq", "Enyaq"],
    "Subaru": ["Impreza", "Legacy", "WRX", "Forester", "Outback", "XV", "Ascent"],
    "Toyota": ["Camry", "Corolla", "Yaris", "Prius", "RAV4", "Highlander", "Land Cruiser", "Prado", "Hilux", "C-HR", "Venza", "Sienna", "Alphard"],
    "Volkswagen": ["Polo", "Jetta", "Golf", "Passat", "Arteon", "T-Cross", "T-Roc", "Tiguan", "Touareg", "ID.3", "ID.4", "ID.6", "Caddy", "Transporter"],
    "Volvo": ["S60", "S90", "V60", "V90", "XC40", "XC60", "XC90"],
    "ВАЗ": ["Granta", "Vesta", "Largus", "XRAY", "Niva"],
    "ГАЗ": ["Газель NEXT", "ГАЗон NEXT"]
};

// Инициализация выпадающих списков
function initSelects() {
    // Заполняем списки городов для всех селекторов
    updateAllCitySelects();
    
    // Заполняем фильтры водителей
    populateDriverFilter();
    
    // Инициализируем кастомные селекты
    initCustomSelects();
    
    // Закрываем выпадающие списки при клике вне их
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.custom-select')) {
            closeAllCustomSelects();
        }
    });
}

// Обновление всех селекторов городов
function updateAllCitySelects() {
    const contexts = ['default', 'search', 'drivers', 'modal', 'route-a', 'route-b', 'driver-modal', 'company'];
    
    contexts.forEach(context => {
        updateCitySelect(context);
    });
}

// Обновление селектора городов для конкретного контекста
function updateCitySelect(context = 'default') {
    let countrySelect, citySelect;
    
    switch(context) {
        case 'search':
            countrySelect = document.getElementById('search-country');
            citySelect = document.getElementById('search-city');
            break;
        case 'drivers':
            countrySelect = document.getElementById('drivers-country-select');
            citySelect = document.getElementById('drivers-city-select');
            break;
        case 'modal':
            countrySelect = document.getElementById('modal-country-select');
            citySelect = document.getElementById('modal-city-select');
            break;
        case 'route-a':
            countrySelect = document.getElementById('route-country-a');
            citySelect = document.getElementById('route-city-a');
            break;
        case 'route-b':
            countrySelect = document.getElementById('route-country-b');
            citySelect = document.getElementById('route-city-b');
            break;
        case 'driver-modal':
            countrySelect = document.getElementById('driver-modal-country');
            citySelect = document.getElementById('driver-modal-city');
            break;
        case 'company':
            countrySelect = document.getElementById('company-country');
            citySelect = document.getElementById('company-city');
            break;
        default:
            countrySelect = document.getElementById('country-select');
            citySelect = document.getElementById('city-select');
    }
    
    if (countrySelect && citySelect) {
        const country = countrySelect.value;
        citySelect.innerHTML = '<option value="">Выберите город</option>';
        
        if (country && citiesData[country]) {
            citiesData[country].forEach(city => {
                const option = document.createElement('option');
                option.value = city;
                option.textContent = city;
                citySelect.appendChild(option);
            });
        }
    }
}

// Обработка изменения страны
function handleCountryChange(context = 'default') {
    let countrySelect, citySelect, cityInputContainer;
    
    switch(context) {
        case 'search':
            countrySelect = document.getElementById('search-country');
            citySelect = document.getElementById('search-city');
            cityInputContainer = document.getElementById('search-city-input-container');
            break;
        case 'drivers':
            countrySelect = document.getElementById('drivers-country-select');
            citySelect = document.getElementById('drivers-city-select');
            cityInputContainer = document.getElementById('drivers-city-input-container');
            break;
        case 'modal':
            countrySelect = document.getElementById('modal-country-select');
            citySelect = document.getElementById('modal-city-select');
            cityInputContainer = document.getElementById('modal-city-input-container');
            break;
        case 'route-a':
            countrySelect = document.getElementById('route-country-a');
            citySelect = document.getElementById('route-city-a');
            cityInputContainer = document.getElementById('route-a-city-input-container');
            break;
        case 'route-b':
            countrySelect = document.getElementById('route-country-b');
            citySelect = document.getElementById('route-city-b');
            cityInputContainer = document.getElementById('route-b-city-input-container');
            break;
        case 'driver-modal':
            countrySelect = document.getElementById('driver-modal-country');
            citySelect = document.getElementById('driver-modal-city');
            cityInputContainer = document.getElementById('driver-modal-city-input-container');
            break;
        case 'company':
            countrySelect = document.getElementById('company-country');
            citySelect = document.getElementById('company-city');
            cityInputContainer = document.getElementById('company-city-input-container');
            break;
        default:
            countrySelect = document.getElementById('country-select');
            citySelect = document.getElementById('city-select');
            cityInputContainer = document.getElementById('city-input-container');
    }
    
    if (countrySelect && citySelect && cityInputContainer) {
        const country = countrySelect.value;
        
        if (country === 'other') {
            citySelect.style.display = 'none';
            cityInputContainer.classList.add('visible');
        } else {
            citySelect.style.display = 'block';
            cityInputContainer.classList.remove('visible');
            updateCitySelect(context);
        }
    }
}

// Заполнение фильтра водителей
function populateDriverFilter() {
    const driverFilter = document.getElementById('driver-filter');
    if (driverFilter && mockDrivers) {
        driverFilter.innerHTML = '<option value="">Все водители</option>' +
            mockDrivers.map(driver => 
                `<option value="${driver.id}">${driver.last_name} ${driver.first_name}</option>`
            ).join('');
    }
}

// Инициализация кастомных селектов
function initCustomSelects() {
    // Инициализация селекта марок автомобилей
    const brandSelects = document.querySelectorAll('#modal-vehicle-brand, #vehicle-search');
    brandSelects.forEach(select => {
        select.addEventListener('input', showBrandOptions);
        select.addEventListener('focus', showBrandOptions);
    });
}

// Показать опции марок
function showBrandOptions(event) {
    const input = event.target;
    const optionsContainer = input.nextElementSibling;
    const value = input.value.toLowerCase();
    
    if (!optionsContainer || !optionsContainer.classList.contains('custom-select-options')) {
        return;
    }
    
    optionsContainer.innerHTML = '';
    optionsContainer.style.display = 'none';
    
    if (value.length > 0) {
        const filteredBrands = Object.keys(carBrands).filter(brand => 
            brand.toLowerCase().includes(value)
        );
        
        filteredBrands.forEach(brand => {
            const option = document.createElement('div');
            option.className = 'custom-select-option';
            option.textContent = brand;
            option.onclick = () => {
                input.value = brand;
                selectedBrand = brand;
                optionsContainer.style.display = 'none';
                
                // Активируем поле модели если это модальное окно
                const modelInput = document.getElementById('modal-vehicle-model');
                if (modelInput && input.id === 'modal-vehicle-brand') {
                    modelInput.disabled = false;
                    modelInput.placeholder = 'Выберите модель';
                    showModelOptions({ target: modelInput });
                }
            };
            optionsContainer.appendChild(option);
        });
        
        if (filteredBrands.length > 0) {
            optionsContainer.style.display = 'block';
        }
    }
}

// Показать опции моделей
function showModelOptions(event) {
    const input = event.target;
    const optionsContainer = input.nextElementSibling;
    const value = input.value.toLowerCase();
    
    if (!optionsContainer || !optionsContainer.classList.contains('custom-select-options') || !selectedBrand) {
        return;
    }
    
    optionsContainer.innerHTML = '';
    optionsContainer.style.display = 'none';
    
    if (selectedBrand && value.length > 0) {
        const models = carBrands[selectedBrand] || [];
        const filteredModels = models.filter(model => 
            model.toLowerCase().includes(value)
        );
        
        filteredModels.forEach(model => {
            const option = document.createElement('div');
            option.className = 'custom-select-option';
            option.textContent = model;
            option.onclick = () => {
                input.value = model;
                optionsContainer.style.display = 'none';
            };
            optionsContainer.appendChild(option);
        });
        
        if (filteredModels.length > 0) {
            optionsContainer.style.display = 'block';
        }
    }
}

// Закрыть все кастомные селекты
function closeAllCustomSelects() {
    document.querySelectorAll('.custom-select-options').forEach(options => {
        options.style.display = 'none';
    });
}

// Переключение сворачиваемых панелей
function toggleCollapsible(panelId) {
    const panel = document.getElementById(panelId);
    const icon = panel.previousElementSibling.querySelector('.collapsible-icon');
    
    if (panel.style.display === 'none' || !panel.style.display) {
        panel.style.display = 'block';
        icon.classList.add('rotated');
    } else {
        panel.style.display = 'none';
        icon.classList.remove('rotated');
    }
}

// Назначение водителя
function assignDriver() {
    showEnhancedNotification('Открывается выбор водителя...', 'success');
    
    // В реальном приложении здесь было бы модальное окно выбора водителя
    setTimeout(() => {
        showEnhancedNotification('Водитель успешно назначен!', 'success');
        addActivity(`${currentUser.name} назначил водителя на заявку`);
    }, 1000);
}

// Назначение автомобиля
function assignVehicle() {
    showEnhancedNotification('Открывается выбор автомобиля...', 'success');
    
    // В реальном приложении здесь было бы модальное окно выбора автомобиля
    setTimeout(() => {
        showEnhancedNotification('Автомобиль успешно назначен!', 'success');
        addActivity(`${currentUser.name} назначил автомобиль на заявку`);
    }, 1000);
}

// Форматирование чисел
function formatNumber(number) {
    return new Intl.NumberFormat('ru-RU').format(number);
}

// Форматирование даты
function formatDate(dateString) {
    return new Date(dateString).toLocaleDateString('ru-RU');
}

// Форматирование даты и времени
function formatDateTime(dateString) {
    return new Date(dateString).toLocaleString('ru-RU');
}

// Валидация email
function isValidEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}

// Валидация телефона
function isValidPhone(phone) {
    const phoneRegex = /^(\+7|8)[\s-]?\(?\d{3}\)?[\s-]?\d{3}[\s-]?\d{2}[\s-]?\d{2}$/;
    return phoneRegex.test(phone.replace(/\s/g, ''));
}

// Генерация случайного цвета
function getRandomColor() {
    const colors = ['#1976d2', '#2e7d32', '#ff8f00', '#c62828', '#7b1fa2', '#00838f', '#6a1b9a', '#283593'];
    return colors[Math.floor(Math.random() * colors.length)];
}

// Дебаунс функция
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// Проверка прав доступа
function hasPermission(requiredRole) {
    if (!currentUser) return false;
    
    const roleHierarchy = {
        'admin': 4,
        'manager': 3,
        'driver': 2,
        'client': 1
    };
    
    return roleHierarchy[currentUser.role] >= roleHierarchy[requiredRole];
}

// Экспорт данных в CSV
function exportToCSV(data, filename) {
    if (!data || data.length === 0) return;
    
    const headers = Object.keys(data[0]);
    const csvContent = [
        headers.join(','),
        ...data.map(row => headers.map(header => {
            let cell = row[header] === null || row[header] === undefined ? '' : row[header];
            cell = cell.toString().replace(/"/g, '""');
            return `"${cell}"`;
        }).join(','))
    ].join('\n');
    
    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    const url = URL.createObjectURL(blob);
    
    link.setAttribute('href', url);
    link.setAttribute('download', filename);
    link.style.visibility = 'hidden';
    
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}

// Загрузка файла
function uploadFile(file, onProgress, onComplete) {
    // Имитация загрузки файла
    let progress = 0;
    const interval = setInterval(() => {
        progress += Math.random() * 10;
        if (progress > 100) progress = 100;
        
        onProgress(progress);
        
        if (progress === 100) {
            clearInterval(interval);
            setTimeout(() => {
                onComplete({
                    success: true,
                    filename: file.name,
                    size: file.size,
                    url: URL.createObjectURL(file)
                });
            }, 500);
        }
    }, 100);
}

// Копирование в буфер обмена
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(() => {
        showEnhancedNotification('Скопировано в буфер обмена', 'success');
    }).catch(() => {
        // Fallback для старых браузеров
        const textArea = document.createElement('textarea');
        textArea.value = text;
        document.body.appendChild(textArea);
        textArea.select();
        document.execCommand('copy');
        document.body.removeChild(textArea);
        showEnhancedNotification('Скопировано в буфер обмена', 'success');
    });
}

// Получение иконки для типа файла
function getFileIcon(filename) {
    const ext = filename.split('.').pop().toLowerCase();
    const iconMap = {
        'pdf': '📕',
        'doc': '📘',
        'docx': '📘',
        'xls': '📗',
        'xlsx': '📗',
        'jpg': '🖼️',
        'jpeg': '🖼️',
        'png': '🖼️',
        'zip': '📦',
        'rar': '📦'
    };
    return iconMap[ext] || '📄';
}