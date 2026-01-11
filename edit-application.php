<?php
/**
 * Страница редактирования заявки
 */

require_once 'config.php';
require_once 'auth.php';

// Проверяем авторизацию
requireLogin();

$application_id = $_GET['id'] ?? $_POST['id'] ?? null;

if (!$application_id) {
    header('Location: applications.php');
    exit();
}

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    // Получаем данные заявки
    $query = "SELECT a.*, 
                     d.first_name as driver_first_name, 
                     d.last_name as driver_last_name,
                     v.brand as vehicle_brand, 
                     v.model as vehicle_model,
                     v.license_plate as vehicle_plate,
                     c.name as customer_company_name
              FROM applications a
              LEFT JOIN drivers d ON a.driver_id = d.id
              LEFT JOIN vehicles v ON a.vehicle_id = v.id
              LEFT JOIN companies c ON a.customer_company_id = c.id
              WHERE a.id = :id";
    
    $stmt = $conn->prepare($query);
    $stmt->execute([':id' => $application_id]);
    $application = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$application) {
        $_SESSION['error_message'] = 'Заявка не найдена';
        header('Location: applications.php');
        exit();
    }
    
    // Получаем маршруты
    $routeQuery = "SELECT * FROM application_routes WHERE application_id = :app_id ORDER BY point_order";
    $routeStmt = $conn->prepare($routeQuery);
    $routeStmt->execute([':app_id' => $application_id]);
    $routes = $routeStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Получаем пассажиров
    $passengerQuery = "SELECT * FROM application_passengers WHERE application_id = :app_id";
    $passengerStmt = $conn->prepare($passengerQuery);
    $passengerStmt->execute([':app_id' => $application_id]);
    $passengers = $passengerStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Получаем водителей для выпадающего списка
    $driversQuery = "SELECT id, first_name, last_name FROM drivers WHERE status = 'work' ORDER BY first_name, last_name";
    $driversStmt = $conn->prepare($driversQuery);
    $driversStmt->execute();
    $drivers = $driversStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Получаем автомобили для выпадающего списка
    $vehiclesQuery = "SELECT id, brand, model, license_plate FROM vehicles WHERE status = 'working' ORDER BY brand, model";
    $vehiclesStmt = $conn->prepare($vehiclesQuery);
    $vehiclesStmt->execute();
    $vehicles = $vehiclesStmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $_SESSION['error_message'] = 'Ошибка загрузки данных: ' . $e->getMessage();
    header('Location: applications.php');
    exit();
}

// Обработка формы
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $conn->beginTransaction();
        
        // Обновляем основную заявку
        $updateQuery = "UPDATE applications SET 
                        status = :status,
                        city = :city,
                        country = :country,
                        trip_date = :trip_date,
                        pickup_time = :pickup_time,
                        delivery_time = :delivery_time,
                        service_type = :service_type,
                        tariff = :tariff,
                        customer_name = :customer_name,
                        customer_phone = :customer_phone,
                        order_amount = :order_amount,
                        flight_number = :flight_number,
                        manager_comment = :manager_comment,
                        notes = :notes,
                        driver_id = :driver_id,
                        vehicle_id = :vehicle_id,
                        payment_status = :payment_status,
                        updated_at = CURRENT_TIMESTAMP
                      WHERE id = :id";
        
        $updateStmt = $conn->prepare($updateQuery);
        $updateStmt->execute([
            ':status' => $_POST['status'],
            ':city' => $_POST['city'],
            ':country' => $_POST['country'],
            ':trip_date' => date('Y-m-d H:i:s', strtotime($_POST['trip_date'])),
            ':pickup_time' => !empty($_POST['pickup_time']) ? date('Y-m-d H:i:s', strtotime($_POST['pickup_time'])) : null,
            ':delivery_time' => !empty($_POST['delivery_time']) ? date('Y-m-d H:i:s', strtotime($_POST['delivery_time'])) : null,
            ':service_type' => $_POST['service_type'],
            ':tariff' => $_POST['tariff'],
            ':customer_name' => $_POST['customer_name'],
            ':customer_phone' => $_POST['customer_phone'],
            ':order_amount' => floatval($_POST['order_amount']),
            ':flight_number' => $_POST['flight_number'],
            ':manager_comment' => $_POST['manager_comment'],
            ':notes' => $_POST['notes'],
            ':driver_id' => !empty($_POST['driver_id']) ? $_POST['driver_id'] : null,
            ':vehicle_id' => !empty($_POST['vehicle_id']) ? $_POST['vehicle_id'] : null,
            ':payment_status' => $_POST['payment_status'],
            ':id' => $application_id
        ]);
        
        // Удаляем старые маршруты
        $deleteRoutesQuery = "DELETE FROM application_routes WHERE application_id = :app_id";
        $deleteRoutesStmt = $conn->prepare($deleteRoutesQuery);
        $deleteRoutesStmt->execute([':app_id' => $application_id]);
        
        // Добавляем новые маршруты
        if (!empty($_POST['routes'])) {
            foreach ($_POST['routes'] as $index => $route) {
                if (!empty(trim($route))) {
                    $routeQuery = "INSERT INTO application_routes (application_id, point_order, city, country, address) 
                                  VALUES (:app_id, :order, :city, :country, :address)";
                    $routeStmt = $conn->prepare($routeQuery);
                    $routeStmt->execute([
                        ':app_id' => $application_id,
                        ':order' => $index,
                        ':city' => $_POST['city'],
                        ':country' => $_POST['country'],
                        ':address' => trim($route)
                    ]);
                }
            }
        }
        
        // Удаляем старых пассажиров
        $deletePassengersQuery = "DELETE FROM application_passengers WHERE application_id = :app_id";
        $deletePassengersStmt = $conn->prepare($deletePassengersQuery);
        $deletePassengersStmt->execute([':app_id' => $application_id]);
        
        // Добавляем новых пассажиров
        if (!empty($_POST['passengers'])) {
            foreach ($_POST['passengers'] as $passenger) {
                if (!empty(trim($passenger['name']))) {
                    $passengerQuery = "INSERT INTO application_passengers (application_id, name, phone) 
                                      VALUES (:app_id, :name, :phone)";
                    $passengerStmt = $conn->prepare($passengerQuery);
                    $passengerStmt->execute([
                        ':app_id' => $application_id,
                        ':name' => trim($passenger['name']),
                        ':phone' => $passenger['phone'] ?? null
                    ]);
                }
            }
        }
        
        $conn->commit();
        
        // Логируем действие
        logAction("Обновлена заявка {$application['application_number']}");
        
        $_SESSION['success_message'] = 'Заявка успешно обновлена';
        header('Location: applications.php');
        exit();
        
    } catch (Exception $e) {
        $conn->rollBack();
        $error_message = 'Ошибка обновления заявки: ' . $e->getMessage();
    }
}

// Статусы заявок
$statuses = [
    'new' => 'Новая',
    'assigned' => 'Назначенная',
    'in_progress' => 'В работе',
    'completed' => 'Завершена',
    'cancelled' => 'Отменена'
];

// Статусы оплаты
$paymentStatuses = [
    'pending' => 'Ожидает оплаты',
    'paid' => 'Оплачена',
    'refunded' => 'Возвращена',
    'cancelled' => 'Отменена'
];

// Типы услуг
$serviceTypes = [
    'rent' => 'Аренда',
    'transfer' => 'Трансфер',
    'city_transfer' => 'Городской трансфер',
    'airport_arrival' => 'Встреча в аэропорту',
    'airport_departure' => 'Трансфер в аэропорт',
    'train_station' => 'Вокзал',
    'remote_area' => 'Удаленный район',
    'other' => 'Другое'
];

// Тарифы
$tariffs = [
    'standard' => 'Стандарт',
    'comfort' => 'Комфорт',
    'business' => 'Бизнес',
    'premium' => 'Премиум',
    'crossover' => 'Кроссовер',
    'minivan5' => 'Минивэн 5 мест',
    'minivan6' => 'Минивэн 6 мест',
    'microbus8' => 'Микроавтобус 8 мест',
    'microbus10' => 'Микроавтобус 10 мест',
    'microbus14' => 'Микроавтобус 14 мест',
    'microbus16' => 'Микроавтобус 16 мест',
    'microbus18' => 'Микроавтобус 18 мест',
    'microbus24' => 'Микроавтобус 24 места',
    'bus35' => 'Автобус 35 мест',
    'bus44' => 'Автобус 44 места',
    'bus50' => 'Автобус 50 мест'
];

include 'templates/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h4>Редактирование заявки <?= htmlspecialchars($application['application_number']) ?></h4>
                    <a href="applications.php" class="btn btn-secondary btn-sm">
                        <i class="fas fa-arrow-left"></i> Назад к списку
                    </a>
                </div>
                <div class="card-body">
                    <?php if (isset($error_message)): ?>
                        <div class="alert alert-danger"><?= htmlspecialchars($error_message) ?></div>
                    <?php endif; ?>
                    
                    <form method="POST" id="editApplicationForm">
                        <div class="row">
                            <!-- Основная информация -->
                            <div class="col-md-6">
                                <h5>Основная информация</h5>
                                
                                <div class="form-group">
                                    <label for="status">Статус</label>
                                    <select name="status" id="status" class="form-control" required>
                                        <?php foreach ($statuses as $value => $label): ?>
                                            <option value="<?= $value ?>" <?= $application['status'] === $value ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($label) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <label for="payment_status">Статус оплаты</label>
                                    <select name="payment_status" id="payment_status" class="form-control" required>
                                        <?php foreach ($paymentStatuses as $value => $label): ?>
                                            <option value="<?= $value ?>" <?= $application['payment_status'] === $value ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($label) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <label for="customer_name">Имя клиента</label>
                                    <input type="text" name="customer_name" id="customer_name" 
                                           class="form-control" value="<?= htmlspecialchars($application['customer_name']) ?>" required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="customer_phone">Телефон клиента</label>
                                    <input type="text" name="customer_phone" id="customer_phone" 
                                           class="form-control" value="<?= htmlspecialchars($application['customer_phone']) ?>" required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="city">Город</label>
                                    <input type="text" name="city" id="city" 
                                           class="form-control" value="<?= htmlspecialchars($application['city']) ?>">
                                </div>
                                
                                <div class="form-group">
                                    <label for="trip_date">Дата и время поездки</label>
                                    <input type="datetime-local" name="trip_date" id="trip_date" 
                                           class="form-control" 
                                           value="<?= date('Y-m-d\TH:i', strtotime($application['trip_date'])) ?>" required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="pickup_time">Время подачи</label>
                                    <input type="datetime-local" name="pickup_time" id="pickup_time" 
                                           class="form-control" 
                                           value="<?= $application['pickup_time'] ? date('Y-m-d\TH:i', strtotime($application['pickup_time'])) : '' ?>">
                                </div>
                                
                                <div class="form-group">
                                    <label for="delivery_time">Время доставки</label>
                                    <input type="datetime-local" name="delivery_time" id="delivery_time" 
                                           class="form-control" 
                                           value="<?= $application['delivery_time'] ? date('Y-m-d\TH:i', strtotime($application['delivery_time'])) : '' ?>">
                                </div>
                            </div>
                            
                            <!-- Детали поездки -->
                            <div class="col-md-6">
                                <h5>Детали поездки</h5>
                                
                                <div class="form-group">
                                    <label for="service_type">Тип услуги</label>
                                    <select name="service_type" id="service_type" class="form-control" required>
                                        <?php foreach ($serviceTypes as $value => $label): ?>
                                            <option value="<?= $value ?>" <?= $application['service_type'] === $value ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($label) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <label for="tariff">Тариф</label>
                                    <select name="tariff" id="tariff" class="form-control" required>
                                        <?php foreach ($tariffs as $value => $label): ?>
                                            <option value="<?= $value ?>" <?= $application['tariff'] === $value ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($label) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <label for="order_amount">Сумма заказа</label>
                                    <input type="number" name="order_amount" id="order_amount" 
                                           class="form-control" step="0.01" 
                                           value="<?= $application['order_amount'] ?>">
                                </div>
                                
                                <div class="form-group">
                                    <label for="flight_number">Номер рейса</label>
                                    <input type="text" name="flight_number" id="flight_number" 
                                           class="form-control" value="<?= htmlspecialchars($application['flight_number']) ?>">
                                </div>
                                
                                <div class="form-group">
                                    <label for="driver_id">Водитель</label>
                                    <select name="driver_id" id="driver_id" class="form-control">
                                        <option value="">Не назначен</option>
                                        <?php foreach ($drivers as $driver): ?>
                                            <option value="<?= $driver['id'] ?>" <?= $application['driver_id'] == $driver['id'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($driver['first_name'] . ' ' . $driver['last_name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <label for="vehicle_id">Автомобиль</label>
                                    <select name="vehicle_id" id="vehicle_id" class="form-control">
                                        <option value="">Не назначен</option>
                                        <?php foreach ($vehicles as $vehicle): ?>
                                            <option value="<?= $vehicle['id'] ?>" <?= $application['vehicle_id'] == $vehicle['id'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($vehicle['brand'] . ' ' . $vehicle['model'] . ' (' . $vehicle['license_plate'] . ')') ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Маршруты -->
                        <div class="row mt-3">
                            <div class="col-md-12">
                                <h5>Маршрут</h5>
                                <div id="routes-container">
                                    <?php foreach ($routes as $index => $route): ?>
                                        <div class="route-item mb-2">
                                            <div class="input-group">
                                                <span class="input-group-text"><?= $index + 1 ?></span>
                                                <input type="text" name="routes[]" class="form-control" 
                                                       placeholder="Адрес" value="<?= htmlspecialchars($route['address']) ?>">
                                                <button type="button" class="btn btn-danger remove-route" <?= count($routes) <= 1 ? 'style="display: none;"' : '' ?>>
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <button type="button" id="add-route" class="btn btn-success btn-sm">
                                    <i class="fas fa-plus"></i> Добавить точку
                                </button>
                            </div>
                        </div>
                        
                        <!-- Пассажиры -->
                        <div class="row mt-3">
                            <div class="col-md-12">
                                <h5>Пассажиры</h5>
                                <div id="passengers-container">
                                    <?php foreach ($passengers as $index => $passenger): ?>
                                        <div class="passenger-item mb-2">
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <input type="text" name="passengers[<?= $index ?>][name]" class="form-control" 
                                                           placeholder="Имя" value="<?= htmlspecialchars($passenger['name']) ?>">
                                                </div>
                                                <div class="col-md-5">
                                                    <input type="text" name="passengers[<?= $index ?>][phone]" class="form-control" 
                                                           placeholder="Телефон" value="<?= htmlspecialchars($passenger['phone']) ?>">
                                                </div>
                                                <div class="col-md-1">
                                                    <button type="button" class="btn btn-danger remove-passenger" <?= count($passengers) <= 1 ? 'style="display: none;"' : '' ?>>
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <button type="button" id="add-passenger" class="btn btn-success btn-sm">
                                    <i class="fas fa-plus"></i> Добавить пассажира
                                </button>
                            </div>
                        </div>
                        
                        <!-- Комментарии -->
                        <div class="row mt-3">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="manager_comment">Комментарий менеджера</label>
                                    <textarea name="manager_comment" id="manager_comment" class="form-control" rows="3"><?= htmlspecialchars($application['manager_comment']) ?></textarea>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="notes">Примечания</label>
                                    <textarea name="notes" id="notes" class="form-control" rows="3"><?= htmlspecialchars($application['notes']) ?></textarea>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row mt-4">
                            <div class="col-md-12">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Сохранить изменения
                                </button>
                                <a href="applications.php" class="btn btn-secondary">
                                    <i class="fas fa-times"></i> Отмена
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Добавление маршрутов
    $('#add-route').click(function() {
        const index = $('#routes-container .route-item').length;
        const routeHtml = `
            <div class="route-item mb-2">
                <div class="input-group">
                    <span class="input-group-text">${index + 1}</span>
                    <input type="text" name="routes[]" class="form-control" placeholder="Адрес">
                    <button type="button" class="btn btn-danger remove-route">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
        `;
        $('#routes-container').append(routeHtml);
    });
    
    // Удаление маршрутов
    $(document).on('click', '.remove-route', function() {
        if ($('#routes-container .route-item').length > 1) {
            $(this).closest('.route-item').remove();
            // Пересчитываем номера
            $('#routes-container .route-item').each(function(index) {
                $(this).find('.input-group-text').text(index + 1);
            });
        }
    });
    
    // Добавление пассажиров
    $('#add-passenger').click(function() {
        const index = $('#passengers-container .passenger-item').length;
        const passengerHtml = `
            <div class="passenger-item mb-2">
                <div class="row">
                    <div class="col-md-6">
                        <input type="text" name="passengers[${index}][name]" class="form-control" placeholder="Имя">
                    </div>
                    <div class="col-md-5">
                        <input type="text" name="passengers[${index}][phone]" class="form-control" placeholder="Телефон">
                    </div>
                    <div class="col-md-1">
                        <button type="button" class="btn btn-danger remove-passenger">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
            </div>
        `;
        $('#passengers-container').append(passengerHtml);
    });
    
    // Удаление пассажиров
    $(document).on('click', '.remove-passenger', function() {
        if ($('#passengers-container .passenger-item').length > 1) {
            $(this).closest('.passenger-item').remove();
        }
    });
});
</script>

<?php include 'templates/footer.php'; ?>