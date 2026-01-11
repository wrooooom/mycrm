-- Миграция: Добавление недостающих полей в таблицу applications
-- Дата: 2025-01-11
-- Описание: Добавляет поля для расширения функциональности управления заказами

-- Добавляем недостающие поля в таблицу applications (только если они не существуют)
ALTER TABLE applications 
ADD COLUMN IF NOT EXISTS payment_status ENUM('pending', 'paid', 'refunded') DEFAULT 'pending' AFTER status,
ADD COLUMN IF NOT EXISTS pickup_time DATETIME NULL AFTER trip_date,
ADD COLUMN IF NOT EXISTS delivery_time DATETIME NULL AFTER pickup_time;

-- Обновляем enum статус для приведения к стандарту (безопасное изменение)
ALTER TABLE applications MODIFY COLUMN status ENUM('new', 'assigned', 'in_progress', 'completed', 'cancelled') DEFAULT 'new';

-- Добавляем индексы для новых полей (только если они не существуют)
CREATE INDEX IF NOT EXISTS idx_applications_payment_status ON applications(payment_status);
CREATE INDEX IF NOT EXISTS idx_applications_pickup_time ON applications(pickup_time);
CREATE INDEX IF NOT EXISTS idx_applications_delivery_time ON applications(delivery_time);

-- Обновляем существующие записи для соответствия новому формату статуса
UPDATE applications SET status = CASE 
    WHEN status = 'confirmed' THEN 'assigned'
    WHEN status = 'inwork' THEN 'in_progress' 
    ELSE status 
END;

-- Добавляем комментарии к полям (если MySQL версия поддерживает)
-- ALTER TABLE applications 
-- MODIFY COLUMN status ENUM('new', 'assigned', 'in_progress', 'completed', 'cancelled') DEFAULT 'new' COMMENT 'Статус заказа',
-- MODIFY COLUMN payment_status ENUM('pending', 'paid', 'refunded') DEFAULT 'pending' COMMENT 'Статус оплаты',
-- MODIFY COLUMN pickup_time DATETIME NULL COMMENT 'Время посадки пассажиров',
-- MODIFY COLUMN delivery_time DATETIME NULL COMMENT 'Время доставки';

-- Создание представления для удобства работы с заказами
CREATE OR REPLACE VIEW applications_detailed AS
SELECT 
    a.*,
    d.first_name as driver_first_name,
    d.last_name as driver_last_name,
    d.phone as driver_phone,
    v.brand as vehicle_brand,
    v.model as vehicle_model,
    v.license_plate as vehicle_plate,
    c.name as customer_company_name,
    ec.name as executor_company_name,
    u.name as creator_name
FROM applications a
LEFT JOIN drivers d ON a.driver_id = d.id
LEFT JOIN vehicles v ON a.vehicle_id = v.id
LEFT JOIN companies c ON a.customer_company_id = c.id
LEFT JOIN companies ec ON a.executor_company_id = ec.id
LEFT JOIN users u ON a.created_by = u.id;