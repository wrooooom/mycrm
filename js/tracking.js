// Моковые данные для трекинга
const mockTracking = [
    {
        id: 1,
        driver_id: 1,
        vehicle_id: 1,
        latitude: 55.7558,
        longitude: 37.6173,
        city: "Москва",
        district: "Центральный",
        status: "on_order",
        current_order_id: 10001,
        location_address: "ул. Тверская, д. 15",
        last_update: new Date().toISOString().replace('T', ' ').substr(0, 19),
        driver_name: "Сидоров Алексей Петрович",
        vehicle_info: "Toyota Camry (A123BC777)",
        order_number: "A2025010001"
    },
    {
        id: 2,
        driver_id: 3,
        vehicle_id: 3,
        latitude: 55.7517,
        longitude: 37.6178,
        city: "Москва",
        district: "Западный",
        status: "free",
        current_order_id: null,
        location_address: "Кутузовский проспект",
        last_update: new Date(Date.now() - 5 * 60000).toISOString().replace('T', ' ').substr(0, 19),
        driver_name: "Иванов Сергей Владимирович",
        vehicle_info: "Mercedes-Benz E-Class (C789FG777)",
        order_number: ""
    }
];

// Яндекс карта
let map = null;
let placemarks = [];

// Инициализация карты
function initMap() {
    if (map) return;

    if (typeof ymaps !== 'undefined') {
        ymaps.ready(() => {
            map = new ymaps.Map('map', {
                center: [55.7558, 37.6173],
                zoom: 10,
                controls: ['zoomControl', 'fullscreenControl']
            });

            // Добавляем метки водителей
            updateMapMarkers();
            
            addActivity(`${currentUser.name} открыл карту трекинга`);
        });
    } else {
        console.error('Yandex Maps API не загружен');
        showEnhancedNotification('Ошибка загрузки карты', 'error');
    }
}

// Обновление меток на карте
function updateMapMarkers() {
    if (!map) return;

    // Удаляем старые метки
    placemarks.forEach(placemark => {
        map.geoObjects.remove(placemark);
    });
    placemarks = [];

    // Добавляем новые метки
    mockTracking.forEach(track => {
        let preset, iconColor;
        
        switch(track.status) {
            case 'on_order':
                preset = 'islands#redAutoCircleIcon';
                iconColor = 'red';
                break;
            case 'free':
                preset = 'islands#greenAutoCircleIcon';
                iconColor = 'green';
                break;
            case 'break':
                preset = 'islands#blueAutoCircleIcon';
                iconColor = 'blue';
                break;
            default:
                preset = 'islands#grayAutoCircleIcon';
                iconColor = 'gray';
        }

        const placemark = new ymaps.Placemark(
            [track.latitude, track.longitude],
            {
                balloonContent: `
                    <div style="padding: 10px;">
                        <strong>${track.driver_name}</strong><br>
                        ${track.vehicle_info}<br>
                        ${track.city}, ${track.district}<br>
                        Статус: <span style="color: ${iconColor}; font-weight: bold;">${getTrackingStatusText(track.status)}</span><br>
                        ${track.order_number ? 'Заказ: ' + track.order_number : 'Свободен'}<br>
                        ${track.location_address}<br>
                        Обновлено: ${formatTimeAgo(track.last_update)}
                    </div>
                `
            },
            {
                preset: preset,
                iconColor: iconColor
            }
        );

        map.geoObjects.add(placemark);
        placemarks.push(placemark);
    });
}

// Форматирование времени "сколько времени назад"
function formatTimeAgo(timestamp) {
    const now = new Date();
    const time = new Date(timestamp);
    const diffMs = now - time;
    const diffMins = Math.floor(diffMs / 60000);
    const diffHours = Math.floor(diffMs / 3600000);
    
    if (diffMins < 1) return 'только что';
    if (diffMins < 60) return `${diffMins} мин назад`;
    if (diffHours < 24) return `${diffHours} ч назад`;
    
    return time.toLocaleString('ru-RU');
}

// Получение текста статуса трекинга
function getTrackingStatusText(status) {
    const statusMap = {
        'free': 'Свободен',
        'on_order': 'На заказе',
        'break': 'На перерыве',
        'offline': 'Не в сети'
    };
    return statusMap[status] || status;
}

// Загрузка данных трекинга
async function loadTracking() {
    try {
        const result = await apiRequest('tracking', 'GET');
        return result.data;
    } catch (error) {
        console.log('Используем моковые данные для трекинга');
        return {
            tracking: mockTracking,
            last_update: new Date().toISOString()
        };
    }
}

// Рендер таблицы трекинга
function renderTrackingTable(trackingData = []) {
    const tbody = document.getElementById('tracking-table-body');
    if (!tbody) return;
    
    if (trackingData.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="9" style="text-align: center; padding: 40px; color: var(--text-light);">
                    📍 Данные трекинга не найдены
                </td>
            </tr>
        `;
        return;
    }
    
    tbody.innerHTML = trackingData.map(track => `
        <tr>
            <td><strong>${track.driver_name}</strong></td>
            <td>${track.vehicle_info}</td>
            <td>${track.city}</td>
            <td>${track.district}</td>
            <td>
                <span class="status ${getTrackingStatusClass(track.status)}">
                    ${getTrackingStatusText(track.status)}
                </span>
            </td>
            <td>${track.order_number || ''}</td>
            <td>${track.location_address}</td>
            <td>${formatTimeAgo(track.last_update)}</td>
            <td>
                <button class="action-icon" onclick="contactDriver(${track.driver_id})" title="Связаться">📞</button>
                <button class="action-icon" onclick="showOnMap(${track.id})" title="На карте">🗺️</button>
                <button class="action-icon" onclick="showDriverRoute(${track.driver_id})" title="Маршрут">🛣️</button>
            </td>
        </tr>
    `).join('');
}

// Получение класса статуса для трекинга
function getTrackingStatusClass(status) {
    const classMap = {
        'on_order': 'status-inwork',
        'free': 'status-confirmed',
        'break': 'status-new',
        'offline': 'status-cancelled'
    };
    return classMap[status] || 'status-new';
}

// Обновление трекинга
async function updateTracking() {
    showEnhancedNotification('Обновление позиций...', 'success');
    
    try {
        // Имитация обновления данных
        mockTracking.forEach(track => {
            // Немного меняем координаты для демонстрации
            track.latitude += (Math.random() - 0.5) * 0.01;
            track.longitude += (Math.random() - 0.5) * 0.01;
            track.last_update = new Date().toISOString().replace('T', ' ').substr(0, 19);
        });
        
        // Обновляем карту и таблицу
        updateMapMarkers();
        renderTrackingTable(mockTracking);
        
        showEnhancedNotification('Позиции обновлены!', 'success');
        addActivity(`${currentUser.name} обновил позиции на карте`);
        
    } catch (error) {
        showEnhancedNotification('Ошибка обновления трекинга', 'error');
    }
}

// Связь с водителем
function contactDriver(driverId) {
    const driver = mockTracking.find(t => t.driver_id === driverId);
    if (driver) {
        showEnhancedNotification(`Связываемся с водителем ${driver.driver_name}...`, 'success');
        
        // Имитация звонка
        setTimeout(() => {
            showEnhancedNotification(`Соединение с ${driver.driver_name} установлено`, 'success');
        }, 1500);
        
        addActivity(`${currentUser.name} связывается с водителем ${driver.driver_name}`);
    }
}

// Показать на карте
function showOnMap(trackId) {
    const track = mockTracking.find(t => t.id === trackId);
    if (track && map) {
        map.setCenter([track.latitude, track.longitude], 15);
        
        // Открываем балун метки
        const placemark = placemarks.find(p => 
            p.geometry.getCoordinates()[0] === track.latitude && 
            p.geometry.getCoordinates()[1] === track.longitude
        );
        
        if (placemark) {
            placemark.balloon.open();
        }
        
        showEnhancedNotification(`Показываем на карте: ${track.driver_name}`, 'success');
        addActivity(`${currentUser.name} просматривает позицию водителя ${track.driver_name} на карте`);
    }
}

// Показать маршрут водителя
function showDriverRoute(driverId) {
    const track = mockTracking.find(t => t.driver_id === driverId);
    if (track) {
        showEnhancedNotification(`Построение маршрута для ${track.driver_name}...`, 'success');
        
        // В реальном приложении здесь было бы построение маршрута
        setTimeout(() => {
            showEnhancedNotification(`Маршрут для ${track.driver_name} построен`, 'success');
        }, 1000);
        
        addActivity(`${currentUser.name} просматривает маршрут водителя ${track.driver_name}`);
    }
}

// Загрузка и рендер трекинга
async function loadAndRenderTracking() {
    try {
        const data = await loadTracking();
        renderTrackingTable(data.tracking);
        
        // Если карта уже инициализирована, обновляем метки
        if (map) {
            updateMapMarkers();
        }
    } catch (error) {
        showEnhancedNotification('Ошибка загрузки данных трекинга', 'error');
    }
}

// Автоматическое обновление трекинга каждые 30 секунд
let trackingInterval = null;

function startAutoTracking() {
    if (trackingInterval) {
        clearInterval(trackingInterval);
    }
    
    trackingInterval = setInterval(() => {
        if (currentSection === 'tracking') {
            updateTracking();
        }
    }, 30000);
}

function stopAutoTracking() {
    if (trackingInterval) {
        clearInterval(trackingInterval);
        trackingInterval = null;
    }
}

// Запуск автообновления при переходе в раздел трекинга
function startTrackingAutoUpdate() {
    startAutoTracking();
}

// Остановка автообновления при уходе из раздела трекинга
function stopTrackingAutoUpdate() {
    stopAutoTracking();
}