-- Обновление схемы для соответствия новым требованиям
-- Дата: 2025-01-11

USE crm_proftransfer;

-- Проверяем и обновляем статусы заявок
ALTER TABLE applications 
MODIFY COLUMN status ENUM('new', 'assigned', 'in_progress', 'completed', 'cancelled') DEFAULT 'new';

-- Добавляем недостающие поля если их нет
SET @sql = IF(
    (SELECT COUNT(*) FROM information_schema.COLUMNS 
     WHERE TABLE_SCHEMA = 'crm_proftransfer' 
     AND TABLE_NAME = 'applications' 
     AND COLUMN_NAME = 'payment_status') = 0,
    'ALTER TABLE applications ADD COLUMN payment_status ENUM(\'pending\', \'paid\', \'refunded\', \'cancelled\') DEFAULT \'pending\' AFTER status',
    'SELECT \'payment_status column already exists\' as message'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = IF(
    (SELECT COUNT(*) FROM information_schema.COLUMNS 
     WHERE TABLE_SCHEMA = 'crm_proftransfer' 
     AND TABLE_NAME = 'applications' 
     AND COLUMN_NAME = 'pickup_time') = 0,
    'ALTER TABLE applications ADD COLUMN pickup_time DATETIME NULL AFTER trip_date',
    'SELECT \'pickup_time column already exists\' as message'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = IF(
    (SELECT COUNT(*) FROM information_schema.COLUMNS 
     WHERE TABLE_SCHEMA = 'crm_proftransfer' 
     AND TABLE_NAME = 'applications' 
     AND COLUMN_NAME = 'delivery_time') = 0,
    'ALTER TABLE applications ADD COLUMN delivery_time DATETIME NULL AFTER pickup_time',
    'SELECT \'delivery_time column already exists\' as message'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Обновляем существующие данные
UPDATE applications SET 
    pickup_time = COALESCE(pickup_time, trip_date),
    delivery_time = COALESCE(delivery_time, DATE_ADD(trip_date, INTERVAL 2 HOUR))
WHERE pickup_time IS NULL OR delivery_time IS NULL;

-- Обновляем статусы для соответствия новой схеме
UPDATE applications SET status = CASE 
    WHEN status = 'confirmed' THEN 'assigned'
    WHEN status = 'inwork' THEN 'in_progress'
    ELSE status
END
WHERE status IN ('confirmed', 'inwork');

-- Создаем индексы для оптимизации запросов
CREATE INDEX IF NOT EXISTS idx_applications_status ON applications(status);
CREATE INDEX IF NOT EXISTS idx_applications_trip_date ON applications(trip_date);
CREATE INDEX IF NOT EXISTS idx_applications_payment_status ON applications(payment_status);
CREATE INDEX IF NOT EXISTS idx_applications_pickup_time ON applications(pickup_time);
CREATE INDEX IF NOT EXISTS idx_applications_delivery_time ON applications(delivery_time);

-- Добавляем комментарии к полям
ALTER TABLE applications 
    MODIFY COLUMN status ENUM('new', 'assigned', 'in_progress', 'completed', 'cancelled') DEFAULT 'new' COMMENT 'Статус заявки: new-новая, assigned-назначена, in_progress-в работе, completed-завершена, cancelled-отменена',
    MODIFY COLUMN payment_status ENUM('pending', 'paid', 'refunded', 'cancelled') DEFAULT 'pending' COMMENT 'Статус оплаты: pending-ожидает, paid-оплачена, refunded-возвращена, cancelled-отменена',
    MODIFY COLUMN pickup_time DATETIME NULL COMMENT 'Время подачи автомобиля',
    MODIFY COLUMN delivery_time DATETIME NULL COMMENT 'Время доставки/завершения поездки';

SELECT 'Database schema updated successfully!' as message;