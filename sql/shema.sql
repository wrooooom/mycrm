-- База данных CRM для службы трансфера
CREATE DATABASE IF NOT EXISTS crm_proftransfer CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE crm_proftransfer;

-- Таблица пользователей
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    role ENUM('admin', 'manager', 'driver', 'client') NOT NULL,
    company_id INT,
    status ENUM('active', 'blocked') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_role (role),
    INDEX idx_company (company_id)
);

-- Таблица компаний
CREATE TABLE companies (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    inn VARCHAR(20),
    phone VARCHAR(20),
    dispatcher_name VARCHAR(255),
    parent_company_id INT,
    user_limit INT DEFAULT 0,
    city VARCHAR(100),
    country ENUM('ru', 'by', 'other') DEFAULT 'ru',
    is_customer BOOLEAN DEFAULT false,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_name (name),
    INDEX idx_status (status)
);

-- Таблица водителей
CREATE TABLE drivers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    company_id INT NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    middle_name VARCHAR(100),
    phone VARCHAR(20),
    phone_secondary VARCHAR(20),
    email VARCHAR(255),
    provider_driver_id VARCHAR(100),
    idriver_login VARCHAR(100),
    idriver_password VARCHAR(255),
    city VARCHAR(100),
    country ENUM('ru', 'by', 'other') DEFAULT 'ru',
    district VARCHAR(100),
    comments TEXT,
    passport_series_number VARCHAR(20),
    passport_issued_by TEXT,
    passport_issue_date DATE,
    passport_registration_address TEXT,
    schedule ENUM('day', 'night') DEFAULT 'day',
    status ENUM('work', 'dayoff', 'vacation', 'repair') DEFAULT 'work',
    photo_url VARCHAR(500),
    rating DECIMAL(3,2) DEFAULT 5.0,
    total_earnings DECIMAL(10,2) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (company_id) REFERENCES companies(id),
    INDEX idx_company (company_id),
    INDEX idx_status (status),
    INDEX idx_city (city)
);

-- Таблица автомобилей
CREATE TABLE vehicles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_id INT NOT NULL,
    brand VARCHAR(100) NOT NULL,
    model VARCHAR(100) NOT NULL,
    class ENUM('standard', 'comfort', 'business', 'premium', 'crossover', 
               'minivan5', 'minivan6', 'microbus8', 'microbus10', 'microbus14',
               'microbus16', 'microbus18', 'microbus24', 'bus35', 'bus44', 'bus50', 'other') NOT NULL,
    provider_vehicle_id VARCHAR(100),
    salon_type ENUM('alcantara', 'velour', 'vinyl', 'artificial_leather', 'carpet',
                   'leather', 'leather_wood', 'leather_chrome', 'combined', 'fabric'),
    salon_color ENUM('white', 'brown', 'light', 'gray', 'dark', 'black'),
    body_color ENUM('beige', 'white', 'green', 'gold', 'brown', 'red', 'orange',
                   'silver', 'gray_brown', 'gray', 'blue', 'purple', 'black'),
    license_plate VARCHAR(20),
    year INT,
    passenger_seats INT,
    status ENUM('working', 'broken', 'repair') DEFAULT 'working',
    mileage INT DEFAULT 0,
    photo_url VARCHAR(500),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (company_id) REFERENCES companies(id),
    INDEX idx_company (company_id),
    INDEX idx_brand (brand),
    INDEX idx_status (status),
    INDEX idx_class (class)
);

-- Связь водителей и автомобилей
CREATE TABLE driver_vehicles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    driver_id INT NOT NULL,
    vehicle_id INT NOT NULL,
    is_active BOOLEAN DEFAULT true,
    assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (driver_id) REFERENCES drivers(id) ON DELETE CASCADE,
    FOREIGN KEY (vehicle_id) REFERENCES vehicles(id) ON DELETE CASCADE,
    INDEX idx_driver (driver_id),
    INDEX idx_vehicle (vehicle_id),
    UNIQUE KEY unique_active_driver_vehicle (driver_id, vehicle_id, is_active)
);

-- Таблица заявок
CREATE TABLE applications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    application_number VARCHAR(50) UNIQUE NOT NULL,
    status ENUM('new', 'confirmed', 'inwork', 'completed', 'cancelled') DEFAULT 'new',
    city VARCHAR(100),
    country ENUM('ru', 'by', 'other') DEFAULT 'ru',
    trip_date DATETIME NOT NULL,
    service_type ENUM('rent', 'transfer', 'city_transfer', 'airport_arrival', 
                     'airport_departure', 'train_station', 'remote_area', 'other') NOT NULL,
    tariff ENUM('standard', 'comfort', 'crossover', 'business', 'premium', 'other',
               'minivan5', 'minivan6', 'microbus8', 'microbus10', 'microbus14',
               'microbus16', 'microbus18', 'microbus24', 'bus35', 'bus44', 'bus50') NOT NULL,
    cancellation_hours INT DEFAULT 0,
    customer_name VARCHAR(255) NOT NULL,
    customer_phone VARCHAR(20) NOT NULL,
    additional_services_amount DECIMAL(10,2) DEFAULT 0,
    flight_number VARCHAR(50),
    sign_text VARCHAR(255),
    manager_comment TEXT,
    toll_roads_amount DECIMAL(10,2) DEFAULT 0,
    vehicle_class VARCHAR(50),
    notes TEXT,
    requires_correction BOOLEAN DEFAULT false,
    correction_reason TEXT,
    
    -- Юридические лица
    order_amount DECIMAL(10,2) DEFAULT 0,
    customer_company_id INT,
    executor_company_id INT,
    executor_amount DECIMAL(10,2) DEFAULT 0,
    
    -- Системные поля
    created_by INT NOT NULL,
    driver_id INT,
    vehicle_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (created_by) REFERENCES users(id),
    FOREIGN KEY (driver_id) REFERENCES drivers(id),
    FOREIGN KEY (vehicle_id) REFERENCES vehicles(id),
    FOREIGN KEY (customer_company_id) REFERENCES companies(id),
    FOREIGN KEY (executor_company_id) REFERENCES companies(id),
    INDEX idx_status (status),
    INDEX idx_trip_date (trip_date),
    INDEX idx_driver (driver_id),
    INDEX idx_created_by (created_by),
    INDEX idx_customer_phone (customer_phone),
    INDEX idx_application_number (application_number)
);

-- Таблица маршрутов заявок
CREATE TABLE application_routes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    application_id INT NOT NULL,
    point_order INT NOT NULL,
    city VARCHAR(100),
    country ENUM('ru', 'by', 'other') DEFAULT 'ru',
    address TEXT NOT NULL,
    FOREIGN KEY (application_id) REFERENCES applications(id) ON DELETE CASCADE,
    INDEX idx_application (application_id),
    INDEX idx_point_order (point_order)
);

-- Таблица пассажиров
CREATE TABLE application_passengers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    application_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    FOREIGN KEY (application_id) REFERENCES applications(id) ON DELETE CASCADE,
    INDEX idx_application (application_id)
);

-- Таблица файлов заявок
CREATE TABLE application_files (
    id INT AUTO_INCREMENT PRIMARY KEY,
    application_id INT NOT NULL,
    filename VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    file_size INT,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (application_id) REFERENCES applications(id) ON DELETE CASCADE,
    INDEX idx_application (application_id)
);

-- Таблица комментариев к заявкам
CREATE TABLE application_comments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    application_id INT NOT NULL,
    user_id INT NOT NULL,
    comment TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (application_id) REFERENCES applications(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id),
    INDEX idx_application (application_id),
    INDEX idx_created_at (created_at)
);

-- Таблица истории действий
CREATE TABLE activity_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    action VARCHAR(500) NOT NULL,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    INDEX idx_user (user_id),
    INDEX idx_created_at (created_at)
);

-- Таблица трекинга водителей
CREATE TABLE driver_tracking (
    id INT AUTO_INCREMENT PRIMARY KEY,
    driver_id INT NOT NULL,
    vehicle_id INT,
    latitude DECIMAL(10,8),
    longitude DECIMAL(11,8),
    city VARCHAR(100),
    district VARCHAR(100),
    status ENUM('free', 'on_order', 'break', 'offline') DEFAULT 'free',
    current_order_id INT,
    location_address TEXT,
    last_update TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (driver_id) REFERENCES drivers(id),
    FOREIGN KEY (vehicle_id) REFERENCES vehicles(id),
    FOREIGN KEY (current_order_id) REFERENCES applications(id),
    INDEX idx_driver (driver_id),
    INDEX idx_status (status),
    INDEX idx_last_update (last_update),
    INDEX idx_location (latitude, longitude)
);

-- Вставка тестовых данных
INSERT INTO companies (name, inn, phone, dispatcher_name, user_limit, city, country, is_customer) VALUES
('ООО ТрансферСервис', '1234567890', '+79990000001', 'Иванов Иван', 50, 'Москва', 'ru', false),
('ИП Козлов', '0987654321', '+79990000002', 'Козлов Дмитрий', 5, 'Санкт-Петербург', 'ru', true),
('ООО Газпром трансфер', '1122334455', '+79990000003', 'Петров Петр', 100, 'Москва', 'ru', true),
('АО РЖД Логистика', '5566778899', '+79990000004', 'Сидоров Алексей', 30, 'Москва', 'ru', true);

INSERT INTO users (name, email, password, phone, role, company_id, status) VALUES
('Администратор', 'admin@proftransfer.ru', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+79990000001', 'admin', 1, 'active'),
('Менеджер', 'manager@proftransfer.ru', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+79990000002', 'manager', 1, 'active'),
('Водитель Петров', 'driver@proftransfer.ru', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+79990000003', 'driver', 1, 'active'),
('Клиент Иванов', 'client@proftransfer.ru', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+79990000004', 'client', 2, 'active'),
('Диспетчер Сидорова', 'dispatcher@proftransfer.ru', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+79990000005', 'manager', 1, 'active');

INSERT INTO drivers (user_id, company_id, first_name, last_name, middle_name, phone, email, city, country, status, rating, total_earnings) VALUES
(3, 1, 'Алексей', 'Сидоров', 'Петрович', '+79998887766', 'sidorov@proftransfer.ru', 'Москва', 'ru', 'work', 4.8, 56250),
(NULL, 1, 'Дмитрий', 'Козлов', 'Иванович', '+79995554433', 'kozlov@proftransfer.ru', 'Санкт-Петербург', 'ru', 'work', 4.6, 41800),
(NULL, 1, 'Сергей', 'Иванов', 'Владимирович', '+79994443322', 'ivanov@proftransfer.ru', 'Москва', 'ru', 'work', 4.9, 72800),
(NULL, 1, 'Андрей', 'Петров', 'Николаевич', '+79993332211', 'petrov@proftransfer.ru', 'Москва', 'ru', 'vacation', 4.7, 39150),
(NULL, 1, 'Михаил', 'Смирнов', 'Олегович', '+79992221100', 'smirnov@proftransfer.ru', 'Москва', 'ru', 'work', 4.5, 28500);

INSERT INTO vehicles (company_id, brand, model, class, license_plate, year, passenger_seats, status, mileage) VALUES
(1, 'Toyota', 'Camry', 'comfort', 'A123BC777', 2022, 4, 'working', 125000),
(1, 'Hyundai', 'Solaris', 'standard', 'B456DE777', 2021, 4, 'working', 89000),
(1, 'Mercedes-Benz', 'E-Class', 'business', 'C789FG777', 2023, 4, 'working', 67000),
(1, 'BYD', 'Han', 'business', 'D012HI777', 2023, 4, 'repair', 45000),
(1, 'Kia', 'Rio', 'standard', 'E345JK777', 2022, 4, 'working', 78000),
(1, 'Volkswagen', 'Multivan', 'minivan6', 'F678LM777', 2023, 6, 'working', 32000);

INSERT INTO driver_vehicles (driver_id, vehicle_id, is_active) VALUES
(1, 1, true),
(2, 2, true),
(3, 3, true),
(4, 4, true),
(5, 5, true);

INSERT INTO applications (
    application_number, status, city, country, trip_date, service_type, tariff,
    customer_name, customer_phone, order_amount, created_by, driver_id, vehicle_id,
    customer_company_id, executor_company_id, executor_amount
) VALUES
('A2025010001', 'completed', 'Москва', 'ru', '2025-01-25 14:30:00', 'airport_arrival', 'comfort',
 'Иванов Иван Иванович', '+79991234567', 2500, 2, 1, 1, 2, 1, 1800),

('A2025010002', 'confirmed', 'Москва', 'ru', '2025-01-25 09:00:00', 'city_transfer', 'business',
 'Петров Петр Петрович', '+79997654321', 3500, 2, 2, 2, 3, 1, 2200),

('A2025010003', 'new', 'Москва', 'ru', '2025-01-26 16:45:00', 'airport_departure', 'comfort',
 'Сидоров Алексей Сергеевич', '+79994561234', 2800, 2, NULL, NULL, 4, 1, 1900),

('A2025010004', 'inwork', 'Москва', 'ru', '2025-01-26 12:00:00', 'train_station', 'business',
 'Кузнецова Мария Владимировна', '+79993216547', 4200, 2, 3, 3, 3, 1, 2800);

INSERT INTO application_routes (application_id, point_order, city, country, address) VALUES
(1, 0, 'Москва', 'ru', 'Аэропорт Шереметьево, терминал B'),
(1, 1, 'Москва', 'ru', 'ул. Тверская, д. 15'),

(2, 0, 'Москва', 'ru', 'ул. Арбат, д. 25'),
(2, 1, 'Москва', 'ru', 'Аэропорт Домодедово, терминал А'),

(3, 0, 'Москва', 'ru', 'Киевский вокзал, главный вход'),
(3, 1, 'Москва', 'ru', 'Аэропорт Внуково, терминал B'),

(4, 0, 'Москва', 'ru', 'офис Газпром, пр. Мира, д. 120'),
(4, 1, 'Москва', 'ru', 'Ленинградский вокзал');

INSERT INTO application_passengers (application_id, name, phone) VALUES
(1, 'Иванов Иван Иванович', '+79991234567'),
(2, 'Петров Петр Петрович', '+79997654321'),
(2, 'Петрова Анна Сергеевна', '+79997654322'),
(3, 'Сидоров Алексей Сергеевич', '+79994561234'),
(4, 'Кузнецова Мария Владимировна', '+79993216547');

INSERT INTO application_comments (application_id, user_id, comment) VALUES
(1, 2, 'Клиент просит встретить с табличкой с именем'),
(1, 3, 'Прибыл на место, жду пассажира'),
(1, 3, 'Пассажир забран, выезжаем по маршруту'),
(2, 2, 'VIP клиент, особое внимание'),
(4, 2, 'Срочный заказ, требуется быстрая обработка');

INSERT INTO driver_tracking (driver_id, vehicle_id, latitude, longitude, city, district, status, location_address) VALUES
(1, 1, 55.7558, 37.6173, 'Москва', 'Центральный', 'on_order', 'ул. Тверская, д. 15'),
(2, 2, 55.7517, 37.6178, 'Москва', 'Западный', 'free', 'Кутузовский проспект'),
(3, 3, 55.7301, 37.6065, 'Москва', 'Южный', 'on_order', 'Ленинский проспект'),
(5, 5, 55.7635, 37.6254, 'Москва', 'Северный', 'free', 'Савёловский район');

INSERT INTO activity_log (user_id, action, ip_address) VALUES
(1, 'Система инициализирована', '127.0.0.1'),
(2, 'Создана заявка A2025010001', '127.0.0.1'),
(2, 'Создана заявка A2025010002', '127.0.0.1'),
(3, 'Принял заявку A2025010001', '127.0.0.1'),
(2, 'Создана заявка A2025010003', '127.0.0.1'),
(2, 'Создана заявка A2025010004', '127.0.0.1'),
(3, 'Завершил заявку A2025010001', '127.0.0.1');

-- Создание пользователя для базы данных (опционально)
-- CREATE USER 'crm_user'@'localhost' IDENTIFIED BY 'secure_password';
-- GRANT ALL PRIVILEGES ON crm_proftransfer.* TO 'crm_user'@'localhost';
-- FLUSH PRIVILEGES;