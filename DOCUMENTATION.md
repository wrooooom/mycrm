# Документация по системе управления заказами

## Выполненные задачи ЭТАПА 2

### ✅ 1. Исправление и оптимизация API заказов
- **Файл**: `api/applications.php`
- **Функции**:
  - ACL система для фильтрации по ролям (admin, dispatcher, manager, driver)
  - Фильтрация по статусу заказа (new, assigned, in_progress, completed, cancelled)
  - Фильтрация по статусу оплаты (pending, paid, refunded)
  - Пагинация (20 записей на страницу)
  - Сортировка по дате, статусу, цене
  - Поиск по номеру заказа, клиенту, телефону
  - Валидация входных данных при создании/редактировании заказа

### ✅ 2. Расширение таблицы applications
- **Файл миграции**: `sql/migrate_add_application_fields.sql`
- **Добавленные поля**:
  - `payment_status` ENUM('pending', 'paid', 'refunded')
  - `pickup_time` DATETIME
  - `delivery_time` DATETIME
- **Обновлено**: enum для поля `status` (new, assigned, in_progress, completed, cancelled)
- **Создано представление**: `applications_detailed` для удобства работы

### ✅ 3. Улучшение UI заказов (applications.php)
- **Функции**:
  - Фильтры по статусу и статусу оплаты
  - Поиск по номеру заказа, клиенту, телефону
  - Фильтрация по дате
  - Быстрые действия (Quick Actions):
    - Назначение водителя
    - Назначение транспорта
    - Изменение статуса
  - Отображение водителя и машины в таблице
  - Пагинация результатов
  - Статистические карточки

### ✅ 4. Создание и редактирование заказов
- **Создание**: Модальное окно в applications.php
- **Редактирование**: Отдельная страница `edit-application.php`
- **Функции**:
  - Форма содержит: пассажиров, маршруты, время, примечания
  - Выбор водителя и транспорта при создании
  - Валидация на фронте и бэке
  - Динамическое добавление маршрутов и пассажиров

### ✅ 5. Логирование и аудит
- **Функция**: `logAction()` в auth.php
- **Логируется**:
  - Создание заказа
  - Назначение водителя
  - Назначение автомобиля
  - Изменение статуса
  - Просмотр списка заказов
  - Редактирование заказа
- **Таблица**: activity_log

### ✅ 6. Очистка кода
- **Удалены отладочные файлы**:
  - applications_debug.php
  - applications_fixed.php
  - applications_new.php
  - applications_working.php
- **Добавлены комментарии** к основным функциям
- **Исправлены** require/include пути

## Использование API

### Получение списка заказов
```
GET /api/applications.php?action=getAll
```

**Параметры**:
- `page` - номер страницы (по умолчанию 1)
- `limit` - количество записей на страницу (по умолчанию 20)
- `status` - фильтр по статусу (new, assigned, in_progress, completed, cancelled)
- `payment_status` - фильтр по статусу оплаты (pending, paid, refunded)
- `date_from` - дата начала (YYYY-MM-DD)
- `date_to` - дата окончания (YYYY-MM-DD)
- `search` - поисковая строка
- `sort_by` - поле сортировки (trip_date, status, order_amount, created_at, application_number)
- `sort_order` - порядок сортировки (ASC, DESC)

### Создание заказа
```
POST /api/applications.php?action=create
```

**Тело запроса**:
```json
{
  "customer_name": "Иванов Иван",
  "customer_phone": "+79991234567",
  "trip_date": "2025-01-26 14:30:00",
  "order_amount": 2500,
  "service_type": "transfer",
  "tariff": "comfort",
  "routes": [
    "Аэропорт Шереметьево, терминал B",
    "ул. Тверская, д. 15"
  ],
  "passengers": [
    {
      "name": "Иванов Иван",
      "phone": "+79991234567"
    }
  ],
  "notes": "Встретить с табличкой"
}
```

### Изменение статуса заказа
```
POST /api/applications.php?action=updateStatus
```

**Тело запроса**:
```json
{
  "application_id": 1,
  "status": "assigned"
}
```

### Назначение водителя
```
POST /api/applications.php?action=assignDriver
```

**Тело запроса**:
```json
{
  "application_id": 1,
  "driver_id": 123
}
```

### Назначение автомобиля
```
POST /api/applications.php?action=assignVehicle
```

**Тело запроса**:
```json
{
  "application_id": 1,
  "vehicle_id": 456
}
```

## Права доступа (ACL)

### Администратор (admin)
- Полный доступ ко всем заказам
- Может создавать, редактировать, удалять
- Может назначать водителей и автомобили

### Диспетчер (dispatcher)
- Видит заказы своей компании
- Может создавать и редактировать заказы
- Может назначать водителей и автомобили

### Менеджер (manager)
- Видит заказы своей компании
- Может создавать и редактировать заказы
- Может назначать водителей и автомобили

### Водитель (driver)
- Видит только свои заказы
- Может изменять статус только своих заказов

## Структура статусов заказов

1. **new** - Новый заказ
2. **assigned** - Назначен водитель/автомобиль
3. **in_progress** - В работе (в пути)
4. **completed** - Завершен
5. **cancelled** - Отменен

## Структура статусов оплаты

1. **pending** - Ожидает оплаты
2. **paid** - Оплачен
3. **refunded** - Возврат

## База данных

### Новые поля в таблице applications:
- `payment_status` ENUM('pending', 'paid', 'refunded') DEFAULT 'pending'
- `pickup_time` DATETIME NULL
- `delivery_time` DATETIME NULL

### Созданные индексы:
- `idx_applications_payment_status`
- `idx_applications_pickup_time`
- `idx_applications_delivery_time`

### Созданное представление:
- `applications_detailed` - для удобства работы с связанными данными

## Файлы проекта

- `api/applications.php` - основной API для заказов
- `applications.php` - главная страница управления заказами
- `edit-application.php` - страница редактирования заказа
- `sql/migrate_add_application_fields.sql` - миграция для БД
- `auth.php` - обновленные функции авторизации и логирования

## Тестирование

Для тестирования API используйте:
- Postman или аналогичный инструмент
- Сначала авторизуйтесь в системе
- Используйте cookies сессии для аутентификации

## Миграция базы данных

Для применения изменений в БД выполните:
```sql
SOURCE /path/to/sql/migrate_add_application_fields.sql;
```