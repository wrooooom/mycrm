# API Documentation - CRM.PROFTRANSFER

## База
- Все API находятся в папке `/api/`
- Формат ответа: JSON
- Требуется авторизация через сессии

## Applications API (`/api/applications.php`)

### GET - Получение списка заявок
```
GET /api/applications.php?action=getAll
```

Параметры:
- `page` - номер страницы (по умолчанию 1)
- `limit` - количество на странице (по умолчанию 20, макс 100)
- `status` - фильтр по статусу (new, assigned, in_progress, completed, cancelled)
- `payment_status` - фильтр по статусу оплаты (pending, paid, refunded)
- `date_from` - дата начала (YYYY-MM-DD)
- `date_to` - дата окончания (YYYY-MM-DD)
- `search` - поиск по номеру, имени, телефону
- `sort_by` - сортировка (trip_date, status, order_amount, created_at, application_number)
- `sort_order` - порядок (ASC, DESC)

Ответ:
```json
{
  "success": true,
  "data": [...],
  "pagination": {
    "current_page": 1,
    "total_pages": 5,
    "total_records": 100,
    "per_page": 20,
    "has_next": true,
    "has_prev": false
  },
  "filters": {...}
}
```

### POST - Создание заявки
```
POST /api/applications.php?action=create
Content-Type: application/json

{
  "customer_name": "Иванов И.И.",
  "customer_phone": "+79991234567",
  "trip_date": "2025-01-25 14:30:00",
  "service_type": "airport_arrival",
  "tariff": "comfort",
  "order_amount": 2500,
  "routes": [
    {"address": "Аэропорт Шереметьево"},
    {"address": "ул. Тверская, д. 15"}
  ],
  "passengers": [
    {"name": "Иванов И.И.", "phone": "+79991234567"}
  ]
}
```

### PUT - Обновление статуса
```
POST /api/applications.php?action=updateStatus
Content-Type: application/json

{
  "application_id": 123,
  "status": "in_progress"
}
```

### POST - Назначение водителя
```
POST /api/applications.php?action=assignDriver
Content-Type: application/json

{
  "application_id": 123,
  "driver_id": 5
}
```

### POST - Назначение автомобиля
```
POST /api/applications.php?action=assignVehicle
Content-Type: application/json

{
  "application_id": 123,
  "vehicle_id": 8
}
```

## Notifications API (`/api/notifications.php`)

### GET - Все уведомления
```
GET /api/notifications.php?action=getAll
```

Параметры:
- `limit` - количество (по умолчанию 50, макс 100)
- `offset` - смещение (по умолчанию 0)

Ответ:
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "user_id": 3,
      "type": "assignment",
      "title": "Новый заказ",
      "message": "Вам назначен заказ #A2025010001",
      "related_type": "application",
      "related_id": 123,
      "is_read": false,
      "created_at": "2025-01-11 10:30:00"
    }
  ],
  "total": 10,
  "unread": 3
}
```

### GET - Непрочитанные уведомления
```
GET /api/notifications.php?action=getUnread
```

### GET - Количество непрочитанных
```
GET /api/notifications.php?action=getCount
```

### POST - Создать уведомление
```
POST /api/notifications.php
Content-Type: application/json

{
  "user_id": 3,
  "type": "info",
  "title": "Уведомление",
  "message": "Текст сообщения",
  "related_type": "application",
  "related_id": 123
}
```

### POST - Отметить как прочитанное
```
POST /api/notifications.php?action=markAsRead
Content-Type: application/json

{
  "id": 1
}
```

### POST - Отметить все как прочитанные
```
POST /api/notifications.php?action=markAllAsRead
```

### DELETE - Удалить уведомление
```
DELETE /api/notifications.php
Content-Type: application/json

{
  "id": 1
}
```

## Payments API (`/api/payments.php`)

### GET - Все платежи
```
GET /api/payments.php
```

Параметры:
- `limit` - количество (по умолчанию 50, макс 100)
- `offset` - смещение
- `status` - фильтр по статусу (pending, completed, refunded, failed)

### GET - Платежи по заявке
```
GET /api/payments.php?action=getByApplication&application_id=123
```

### POST - Создать платеж
```
POST /api/payments.php
Content-Type: application/json

{
  "application_id": 123,
  "amount": 2500.00,
  "status": "completed",
  "method": "card",
  "payment_date": "2025-01-11 14:30:00",
  "notes": "Оплачено картой"
}
```

### PUT - Обновить платеж
```
PUT /api/payments.php
Content-Type: application/json

{
  "id": 5,
  "amount": 2500.00,
  "status": "completed",
  "method": "card",
  "payment_date": "2025-01-11 14:30:00",
  "notes": "Обновлено"
}
```

### DELETE - Удалить платеж
```
DELETE /api/payments.php
Content-Type: application/json

{
  "id": 5
}
```

## Drivers API (`/api/drivers.php`)

### GET - Список водителей
```
GET /api/drivers.php?action=getAll
```

Параметры:
- `status` - фильтр по статусу (work, dayoff, vacation, repair)
- `city` - фильтр по городу

### POST - Создать водителя
```
POST /api/drivers.php?action=create
Content-Type: application/json

{
  "first_name": "Иван",
  "last_name": "Иванов",
  "phone": "+79991234567",
  "email": "ivan@example.com",
  "city": "Москва",
  "status": "work",
  "company_id": 1
}
```

### PUT - Обновить водителя
```
POST /api/drivers.php?action=update
Content-Type: application/json

{
  "id": 5,
  "first_name": "Иван",
  "last_name": "Иванов",
  "status": "work"
}
```

### DELETE - Удалить водителя
```
POST /api/drivers.php?action=delete
Content-Type: application/json

{
  "id": 5
}
```

## Vehicles API (`/api/vehicles.php`)

### GET - Список автомобилей
```
GET /api/vehicles.php?action=getAll
```

### POST - Создать автомобиль
```
POST /api/vehicles.php?action=create
Content-Type: application/json

{
  "brand": "Toyota",
  "model": "Camry",
  "class": "comfort",
  "license_plate": "A123BC777",
  "year": 2022,
  "passenger_seats": 4,
  "status": "working",
  "company_id": 1
}
```

## Analytics API (`/api/analytics.php`)

### GET - Статистика
```
GET /api/analytics.php
```

Возвращает общую статистику по заявкам, водителям, доходам.

## Tracking API (`/api/tracking.php`)

### GET - Текущие позиции
```
GET /api/tracking.php
```

Возвращает текущие позиции водителей с координатами.

## Коды ответов

- `200` - успешно
- `201` - создано
- `400` - неверные данные
- `401` - требуется авторизация
- `403` - доступ запрещен
- `404` - не найдено
- `405` - метод не поддерживается
- `500` - внутренняя ошибка сервера

## Аутентификация

API использует сессии PHP. Перед использованием API необходимо авторизоваться через `/login.php`.

## Примеры использования

### JavaScript (fetch)
```javascript
// Получение списка заявок
fetch('/api/applications.php?action=getAll&page=1&limit=20')
  .then(response => response.json())
  .then(data => console.log(data));

// Создание заявки
fetch('/api/applications.php?action=create', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json'
  },
  body: JSON.stringify({
    customer_name: 'Иванов И.И.',
    customer_phone: '+79991234567',
    trip_date: '2025-01-25 14:30:00',
    service_type: 'airport_arrival',
    tariff: 'comfort',
    order_amount: 2500
  })
})
.then(response => response.json())
.then(data => console.log(data));
```

### PHP (cURL)
```php
// Получение списка уведомлений
$ch = curl_init('http://example.com/api/notifications.php?action=getAll');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
$response = curl_exec($ch);
curl_close($ch);
$data = json_decode($response, true);
```

## Функции для интеграции

### includes/functions.php

```php
// Отправка уведомления
sendNotification($pdo, $userId, $type, $title, $message, $relatedType, $relatedId);

// Уведомление водителю при назначении
notifyDriverAssignment($pdo, $driverId, $applicationNumber);

// Уведомление диспетчерам при изменении статуса
notifyStatusChange($pdo, $applicationId, $oldStatus, $newStatus);
```

## База данных

### Таблицы ЭТАП 3

#### notifications
- `id` - INT PRIMARY KEY
- `user_id` - INT (FK to users)
- `type` - VARCHAR(50)
- `title` - VARCHAR(255)
- `message` - TEXT
- `related_type` - VARCHAR(50)
- `related_id` - INT
- `is_read` - TINYINT(1)
- `created_at` - TIMESTAMP

#### payments
- `id` - INT PRIMARY KEY
- `application_id` - INT (FK to applications)
- `user_id` - INT (FK to users)
- `amount` - DECIMAL(10,2)
- `status` - VARCHAR(30)
- `method` - VARCHAR(30)
- `payment_date` - DATETIME
- `notes` - TEXT
- `created_at` - TIMESTAMP
- `updated_at` - TIMESTAMP

#### vehicle_maintenance
- `id` - INT PRIMARY KEY
- `vehicle_id` - INT (FK to vehicles)
- `maintenance_type` - VARCHAR(50)
- `description` - TEXT
- `cost` - DECIMAL(10,2)
- `mileage` - INT
- `maintenance_date` - DATE
- `next_maintenance_date` - DATE
- `performed_by` - VARCHAR(255)
- `notes` - TEXT
- `created_at` - TIMESTAMP

#### Координаты в applications
- `pickup_lat` - DECIMAL(10,8)
- `pickup_lon` - DECIMAL(11,8)
- `delivery_lat` - DECIMAL(10,8)
- `delivery_lon` - DECIMAL(11,8)
