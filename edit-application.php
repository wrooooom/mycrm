<?php
/**
 * Страница редактирования заявки
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';
requireLogin();

// Проверяем права доступа
if (!hasAccess('applications')) {
    header("Location: index.php");
    exit();
}

// Получаем ID заявки из URL
$applicationId = intval($_GET['id'] ?? 0);

if (!$applicationId) {
    header("Location: applications.php");
    exit();
}

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    // Получаем данные заявки
    $query = "SELECT a.*, 
                     d.first_name as driver_first_name, 
                     d.last_name as driver_last_name,
                     d.phone as driver_phone,
                     v.brand as vehicle_brand, 
                     v.model as vehicle_model,
                     v.license_plate as vehicle_plate,
                     c.name as customer_company_name,
                     ec.name as executor_company_name,
                     u.username as creator_name
              FROM applications a
              LEFT JOIN drivers d ON a.driver_id = d.id
              LEFT JOIN vehicles v ON a.vehicle_id = v.id
              LEFT JOIN companies c ON a.customer_company_id = c.id
              LEFT JOIN companies ec ON a.executor_company_id = ec.id
              LEFT JOIN users u ON a.created_by = u.id
              WHERE a.id = :app_id";
    
    $stmt = $conn->prepare($query);
    $stmt->execute([':app_id' => $applicationId]);
    $application = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$application) {
        header("Location: applications.php");
        exit();
    }
    
    // Получаем маршруты заявки
    $routeQuery = "SELECT * FROM application_routes WHERE application_id = :app_id ORDER BY point_order";
    $routeStmt = $conn->prepare($routeQuery);
    $routeStmt->execute([':app_id' => $applicationId]);
    $routes = $routeStmt->fetchAll(PDO::FETCH_ASSOC);
    $application['routes'] = $routes;
    
    // Получаем пассажиров заявки
    $passengerQuery = "SELECT * FROM application_passengers WHERE application_id = :app_id";
    $passengerStmt = $conn->prepare($passengerQuery);
    $passengerStmt->execute([':app_id' => $applicationId]);
    $passengers = $passengerStmt->fetchAll(PDO::FETCH_ASSOC);
    $application['passengers'] = $passengers;
    
    // Получаем список доступных водителей
    $driversQuery = "SELECT id, first_name, last_name, phone FROM drivers WHERE status = 'work' ORDER BY first_name, last_name";
    $driversStmt = $conn->prepare($driversQuery);
    $driversStmt->execute();
    $availableDrivers = $driversStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Получаем список доступных автомобилей
    $vehiclesQuery = "SELECT id, brand, model, license_plate, class FROM vehicles WHERE status = 'working' ORDER BY brand, model";
    $vehiclesStmt = $conn->prepare($vehiclesQuery);
    $vehiclesStmt->execute();
    $availableVehicles = $vehiclesStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Получаем список компаний
    $companiesQuery = "SELECT id, name FROM companies WHERE status = 'active' ORDER BY name";
    $companiesStmt = $conn->prepare($companiesQuery);
    $companiesStmt->execute();
    $companies = $companiesStmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    error_log("Error loading application: " . $e->getMessage());
    header("Location: applications.php");
    exit();
}

// Обработка формы обновления
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $conn->beginTransaction();
        
        // Обновляем основные данные заявки
        $updateQuery = "UPDATE applications SET
                        status = :status,
                        pickup_time = :pickup_time,
                        delivery_time = :delivery_time,
                        driver_id = :driver_id,
                        vehicle_id = :vehicle_id,
                        customer_name = :customer_name,
                        customer_phone = :customer_phone,
                        order_amount = :order_amount,
                        payment_status = :payment_status,
                        manager_comment = :manager_comment,
                        notes = :notes,
                        updated_at = CURRENT_TIMESTAMP
                        WHERE id = :app_id";
        
        $stmt = $conn->prepare($updateQuery);
        $stmt->execute([
            ':status' => $_POST['status'],
            ':pickup_time' => !empty($_POST['pickup_time']) ? date('Y-m-d H:i:s', strtotime($_POST['pickup_time'])) : null,
            ':delivery_time' => !empty($_POST['delivery_time']) ? date('Y-m-d H:i:s', strtotime($_POST['delivery_time'])) : null,
            ':driver_id' => !empty($_POST['driver_id']) ? intval($_POST['driver_id']) : null,
            ':vehicle_id' => !empty($_POST['vehicle_id']) ? intval($_POST['vehicle_id']) : null,
            ':customer_name' => trim($_POST['customer_name']),
            ':customer_phone' => trim($_POST['customer_phone']),
            ':order_amount' => floatval($_POST['order_amount'] ?? 0),
            ':payment_status' => $_POST['payment_status'],
            ':manager_comment' => $_POST['manager_comment'] ?? null,
            ':notes' => $_POST['notes'] ?? null,
            ':app_id' => $applicationId
        ]);
        
        // Обновляем маршруты
        // Сначала удаляем старые маршруты
        $deleteRoutesQuery = "DELETE FROM application_routes WHERE application_id = :app_id";
        $deleteRoutesStmt = $conn->prepare($deleteRoutesQuery);
        $deleteRoutesStmt->execute([':app_id' => $applicationId]);
        
        // Добавляем новые маршруты
        if (!empty($_POST['routes']) && is_array($_POST['routes'])) {
            foreach ($_POST['routes'] as $index => $routeAddress) {
                if (!empty(trim($routeAddress))) {
                    $routeQuery = "INSERT INTO application_routes (application_id, point_order, city, country, address) 
                                  VALUES (:app_id, :order, :city, :country, :address)";
                    $routeStmt = $conn->prepare($routeQuery);
                    $routeStmt->execute([
                        ':app_id' => $applicationId,
                        ':order' => $index,
                        ':city' => $application['city'] ?? 'Москва',
                        ':country' => $application['country'] ?? 'ru',
                        ':address' => trim($routeAddress)
                    ]);
                }
            }
        }
        
        // Обновляем пассажиров
        // Сначала удаляем старых пассажиров
        $deletePassengersQuery = "DELETE FROM application_passengers WHERE application_id = :app_id";
        $deletePassengersStmt = $conn->prepare($deletePassengersQuery);
        $deletePassengersStmt->execute([':app_id' => $applicationId]);
        
        // Добавляем новых пассажиров
        if (!empty($_POST['passengers']) && is_array($_POST['passengers'])) {
            foreach ($_POST['passengers'] as $passenger) {
                if (!empty(trim($passenger['name'] ?? ''))) {
                    $passengerQuery = "INSERT INTO application_passengers (application_id, name, phone) 
                                      VALUES (:app_id, :name, :phone)";
                    $passengerStmt = $conn->prepare($passengerQuery);
                    $passengerStmt->execute([
                        ':app_id' => $applicationId,
                        ':name' => trim($passenger['name']),
                        ':phone' => $passenger['phone'] ?? null
                    ]);
                }
            }
        }
        
        $conn->commit();
        
        // Логируем действие
        logAction("edit_application", $_SESSION['user_id'], "Отредактирована заявка {$application['application_number']}");
        
        header("Location: applications.php?updated=1");
        exit();
        
    } catch (Exception $e) {
        if (isset($conn)) {
            $conn->rollBack();
        }
        $error = "Ошибка при обновлении заявки: " . $e->getMessage();
    }
}

// Функция логирования (добавляем если не существует)
if (!function_exists('logAction')) {
    function logAction($action, $userId, $description = null) {
        try {
            $database = new Database();
            $conn = $database->getConnection();
            
            $logQuery = "INSERT INTO activity_log (user_id, action, ip_address, user_agent) 
                        VALUES (:user_id, :action, :ip, :user_agent)";
            $logStmt = $conn->prepare($logQuery);
            $logStmt->execute([
                ':user_id' => $userId,
                ':action' => $description ?? $action,
                ':ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                ':user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
            ]);
        } catch (Exception $e) {
            error_log("Logging error: " . $e->getMessage());
        }
    }
}

$pageTitle = "Редактирование заявки #" . $application['application_number'];
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <?php require __DIR__ . '/templates/header.php'; ?>
    
    <div class="container-fluid">
        <div class="row">
            <?php require __DIR__ . '/templates/sidebar.php'; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">
                        <i class="fas fa-edit"></i>
                        Редактирование заявки #<?php echo htmlspecialchars($application['application_number']); ?>
                    </h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <a href="applications.php" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left"></i> Назад к списку
                            </a>
                        </div>
                    </div>
                </div>

                <?php if (isset($error)): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle"></i>
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="">
                    <div class="row">
                        <!-- Основная информация -->
                        <div class="col-lg-8">
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h5><i class="fas fa-info-circle"></i> Основная информация</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="customer_name" class="form-label">Имя клиента *</label>
                                            <input type="text" class="form-control" id="customer_name" name="customer_name" 
                                                   value="<?php echo htmlspecialchars($application['customer_name']); ?>" required>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="customer_phone" class="form-label">Телефон клиента *</label>
                                            <input type="text" class="form-control" id="customer_phone" name="customer_phone" 
                                                   value="<?php echo htmlspecialchars($application['customer_phone']); ?>" required>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-4 mb-3">
                                            <label for="status" class="form-label">Статус</label>
                                            <select class="form-select" id="status" name="status">
                                                <option value="new" <?php echo $application['status'] === 'new' ? 'selected' : ''; ?>>Новая</option>
                                                <option value="assigned" <?php echo $application['status'] === 'assigned' ? 'selected' : ''; ?>>Назначена</option>
                                                <option value="in_progress" <?php echo $application['status'] === 'in_progress' ? 'selected' : ''; ?>>В работе</option>
                                                <option value="completed" <?php echo $application['status'] === 'completed' ? 'selected' : ''; ?>>Завершена</option>
                                                <option value="cancelled" <?php echo $application['status'] === 'cancelled' ? 'selected' : ''; ?>>Отменена</option>
                                            </select>
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label for="payment_status" class="form-label">Статус оплаты</label>
                                            <select class="form-select" id="payment_status" name="payment_status">
                                                <option value="pending" <?php echo $application['payment_status'] === 'pending' ? 'selected' : ''; ?>>Ожидает</option>
                                                <option value="paid" <?php echo $application['payment_status'] === 'paid' ? 'selected' : ''; ?>>Оплачена</option>
                                                <option value="refunded" <?php echo $application['payment_status'] === 'refunded' ? 'selected' : ''; ?>>Возврат</option>
                                            </select>
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label for="order_amount" class="form-label">Сумма заказа</label>
                                            <input type="number" class="form-control" id="order_amount" name="order_amount" 
                                                   value="<?php echo $application['order_amount']; ?>" step="0.01">
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="pickup_time" class="form-label">Время посадки</label>
                                            <input type="datetime-local" class="form-control" id="pickup_time" name="pickup_time" 
                                                   value="<?php echo $application['pickup_time'] ? date('Y-m-d\TH:i', strtotime($application['pickup_time'])) : ''; ?>">
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="delivery_time" class="form-label">Время доставки</label>
                                            <input type="datetime-local" class="form-control" id="delivery_time" name="delivery_time" 
                                                   value="<?php echo $application['delivery_time'] ? date('Y-m-d\TH:i', strtotime($application['delivery_time'])) : ''; ?>">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Назначение водителя и автомобиля -->
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h5><i class="fas fa-users-cog"></i> Назначение водителя и автомобиля</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="driver_id" class="form-label">Водитель</label>
                                            <select class="form-select" id="driver_id" name="driver_id">
                                                <option value="">Не назначен</option>
                                                <?php foreach ($availableDrivers as $driver): ?>
                                                    <option value="<?php echo $driver['id']; ?>" 
                                                            <?php echo $application['driver_id'] == $driver['id'] ? 'selected' : ''; ?>>
                                                        <?php echo htmlspecialchars($driver['first_name'] . ' ' . $driver['last_name']); ?>
                                                        (<?php echo htmlspecialchars($driver['phone']); ?>)
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="vehicle_id" class="form-label">Автомобиль</label>
                                            <select class="form-select" id="vehicle_id" name="vehicle_id">
                                                <option value="">Не назначен</option>
                                                <?php foreach ($availableVehicles as $vehicle): ?>
                                                    <option value="<?php echo $vehicle['id']; ?>" 
                                                            <?php echo $application['vehicle_id'] == $vehicle['id'] ? 'selected' : ''; ?>>
                                                        <?php echo htmlspecialchars($vehicle['brand'] . ' ' . $vehicle['model']); ?>
                                                        (<?php echo htmlspecialchars($vehicle['license_plate']); ?>)
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Маршруты -->
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h5><i class="fas fa-route"></i> Маршрут</h5>
                                </div>
                                <div class="card-body">
                                    <div id="routes-container">
                                        <?php if (!empty($application['routes'])): ?>
                                            <?php foreach ($application['routes'] as $index => $route): ?>
                                                <div class="route-item mb-3">
                                                    <label class="form-label">Точка <?php echo $index + 1; ?></label>
                                                    <div class="input-group">
                                                        <input type="text" class="form-control" name="routes[]" 
                                                               value="<?php echo htmlspecialchars($route['address']); ?>" 
                                                               placeholder="Введите адрес">
                                                        <?php if ($index > 0): ?>
                                                            <button type="button" class="btn btn-outline-danger remove-route">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <div class="route-item mb-3">
                                                <label class="form-label">Точка 1</label>
                                                <div class="input-group">
                                                    <input type="text" class="form-control" name="routes[]" placeholder="Введите адрес">
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <button type="button" class="btn btn-outline-primary btn-sm" id="add-route">
                                        <i class="fas fa-plus"></i> Добавить точку
                                    </button>
                                </div>
                            </div>
                            
                            <!-- Пассажиры -->
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h5><i class="fas fa-user-friends"></i> Пассажиры</h5>
                                </div>
                                <div class="card-body">
                                    <div id="passengers-container">
                                        <?php if (!empty($application['passengers'])): ?>
                                            <?php foreach ($application['passengers'] as $index => $passenger): ?>
                                                <div class="passenger-item mb-3">
                                                    <div class="row">
                                                        <div class="col-md-6">
                                                            <input type="text" class="form-control" name="passengers[<?php echo $index; ?>][name]" 
                                                                   value="<?php echo htmlspecialchars($passenger['name']); ?>" 
                                                                   placeholder="Имя пассажира">
                                                        </div>
                                                        <div class="col-md-5">
                                                            <input type="text" class="form-control" name="passengers[<?php echo $index; ?>][phone]" 
                                                                   value="<?php echo htmlspecialchars($passenger['phone'] ?? ''); ?>" 
                                                                   placeholder="Телефон">
                                                        </div>
                                                        <div class="col-md-1">
                                                            <button type="button" class="btn btn-outline-danger remove-passenger">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <div class="passenger-item mb-3">
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <input type="text" class="form-control" name="passengers[0][name]" placeholder="Имя пассажира">
                                                    </div>
                                                    <div class="col-md-5">
                                                        <input type="text" class="form-control" name="passengers[0][phone]" placeholder="Телефон">
                                                    </div>
                                                    <div class="col-md-1">
                                                        <button type="button" class="btn btn-outline-danger remove-passenger">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <button type="button" class="btn btn-outline-primary btn-sm" id="add-passenger">
                                        <i class="fas fa-plus"></i> Добавить пассажира
                                    </button>
                                </div>
                            </div>
                            
                            <!-- Комментарии -->
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h5><i class="fas fa-comment"></i> Комментарии</h5>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <label for="manager_comment" class="form-label">Комментарий менеджера</label>
                                        <textarea class="form-control" id="manager_comment" name="manager_comment" rows="3"><?php echo htmlspecialchars($application['manager_comment'] ?? ''); ?></textarea>
                                    </div>
                                    <div class="mb-3">
                                        <label for="notes" class="form-label">Примечания</label>
                                        <textarea class="form-control" id="notes" name="notes" rows="3"><?php echo htmlspecialchars($application['notes'] ?? ''); ?></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Боковая панель с информацией -->
                        <div class="col-lg-4">
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h5><i class="fas fa-info"></i> Информация о заявке</h5>
                                </div>
                                <div class="card-body">
                                    <p><strong>Номер заявки:</strong> <?php echo htmlspecialchars($application['application_number']); ?></p>
                                    <p><strong>Создана:</strong> <?php echo date('d.m.Y H:i', strtotime($application['created_at'])); ?></p>
                                    <p><strong>Создатель:</strong> <?php echo htmlspecialchars($application['creator_name'] ?? 'Неизвестно'); ?></p>
                                    <p><strong>Тип услуги:</strong> <?php echo htmlspecialchars($application['service_type']); ?></p>
                                    <p><strong>Тариф:</strong> <?php echo htmlspecialchars($application['tariff']); ?></p>
                                    <p><strong>Дата поездки:</strong> <?php echo date('d.m.Y H:i', strtotime($application['trip_date'])); ?></p>
                                </div>
                            </div>
                            
                            <div class="card">
                                <div class="card-body">
                                    <button type="submit" class="btn btn-primary w-100">
                                        <i class="fas fa-save"></i> Сохранить изменения
                                    </button>
                                    <a href="applications.php" class="btn btn-outline-secondary w-100 mt-2">
                                        <i class="fas fa-times"></i> Отмена
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Добавление/удаление маршрутов
        document.getElementById('add-route').addEventListener('click', function() {
            const container = document.getElementById('routes-container');
            const index = container.children.length;
            const routeHtml = `
                <div class="route-item mb-3">
                    <label class="form-label">Точка ${index + 1}</label>
                    <div class="input-group">
                        <input type="text" class="form-control" name="routes[]" placeholder="Введите адрес">
                        <button type="button" class="btn btn-outline-danger remove-route">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            `;
            container.insertAdjacentHTML('beforeend', routeHtml);
        });

        document.addEventListener('click', function(e) {
            if (e.target.closest('.remove-route')) {
                e.target.closest('.route-item').remove();
                // Обновляем номера точек
                document.querySelectorAll('.route-item').forEach((item, index) => {
                    item.querySelector('label').textContent = `Точка ${index + 1}`;
                });
            }
        });

        // Добавление/удаление пассажиров
        document.getElementById('add-passenger').addEventListener('click', function() {
            const container = document.getElementById('passengers-container');
            const index = container.children.length;
            const passengerHtml = `
                <div class="passenger-item mb-3">
                    <div class="row">
                        <div class="col-md-6">
                            <input type="text" class="form-control" name="passengers[${index}][name]" placeholder="Имя пассажира">
                        </div>
                        <div class="col-md-5">
                            <input type="text" class="form-control" name="passengers[${index}][phone]" placeholder="Телефон">
                        </div>
                        <div class="col-md-1">
                            <button type="button" class="btn btn-outline-danger remove-passenger">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>
            `;
            container.insertAdjacentHTML('beforeend', passengerHtml);
        });

        document.addEventListener('click', function(e) {
            if (e.target.closest('.remove-passenger')) {
                e.target.closest('.passenger-item').remove();
            }
        });
    </script>
</body>
</html>