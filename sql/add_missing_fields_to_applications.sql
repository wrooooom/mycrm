-- Миграция для добавления недостающих полей в таблицу applications
-- Дата создания: 2025-01-11

USE crm_proftransfer;

-- Добавляем недостающие поля в таблицу applications
ALTER TABLE applications 
ADD COLUMN payment_status ENUM('pending', 'paid', 'refunded', 'cancelled') DEFAULT 'pending' AFTER status,
ADD COLUMN pickup_time DATETIME NULL AFTER trip_date,
ADD COLUMN delivery_time DATETIME NULL AFTER pickup_time,
ADD COLUMN driver_id INT NOT NULL DEFAULT 0 AFTER created_by,
ADD COLUMN vehicle_id INT NOT NULL DEFAULT 0 AFTER driver_id;

-- Обновляем существующие записи, чтобы они имели правильные статусы
-- Сопоставляем старые статусы с новыми
UPDATE applications SET status = CASE 
    WHEN status = 'new' THEN 'new'
    WHEN status = 'confirmed' THEN 'assigned'
    WHEN status = 'inwork' THEN 'in_progress'
    WHEN status = 'completed' THEN 'completed'
    WHEN status = 'cancelled' THEN 'cancelled'
    ELSE 'new'
END;

-- Добавляем индексы для новых полей
CREATE INDEX idx_applications_payment_status ON applications(payment_status);
CREATE INDEX idx_applications_pickup_time ON applications(pickup_time);
CREATE INDEX idx_applications_delivery_time ON applications(delivery_time);

-- Обновляем существующие записи с примерными данными
UPDATE applications SET pickup_time = trip_date WHERE pickup_time IS NULL;
UPDATE applications SET delivery_time = DATE_ADD(trip_date, INTERVAL 2 HOUR) WHERE delivery_time IS NULL;