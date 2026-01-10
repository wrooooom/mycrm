<!-- Application Modal -->
<div class="modal fade" id="applicationModal" tabindex="-1" aria-labelledby="applicationModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="applicationModalLabel">Создание заказа</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="applicationForm">
                    <input type="hidden" id="app_id" name="app_id">
                    
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <h6 class="section-title">Основная информация</h6>
                            <div class="mb-2">
                                <label class="form-label">Статус <span class="required-star">*</span></label>
                                <select name="status" class="form-select" required>
                                    <option value="new">Не обработана</option>
                                    <option value="confirmed">Принята</option>
                                    <option value="inwork">В работе</option>
                                    <option value="completed">Выполнена</option>
                                    <option value="cancel_penalty">Отмена со штрафом</option>
                                    <option value="cancelled">Отменена</option>
                                </select>
                            </div>
                            <div class="mb-2">
                                <label class="form-label">Город <span class="required-star">*</span></label>
                                <input type="text" name="city" class="form-control" required>
                            </div>
                            <div class="mb-2">
                                <label class="form-label">Страна</label>
                                <select name="country" class="form-select">
                                    <option value="ru">Россия</option>
                                    <option value="by">Беларусь</option>
                                    <option value="other">Другая</option>
                                </select>
                            </div>
                            <div class="mb-2">
                                <label class="form-label">Дата и время поездки <span class="required-star">*</span></label>
                                <input type="datetime-local" name="trip_date" class="form-control" required>
                            </div>
                        </div>
                        
                        <div class="col-md-3">
                            <h6 class="section-title">Услуга</h6>
                            <div class="mb-2">
                                <label class="form-label">Тип услуги <span class="required-star">*</span></label>
                                <select name="service_type" class="form-select" required>
                                    <option value="">Выберите...</option>
                                    <option value="rent">Аренда</option>
                                    <option value="transfer">Трансфер</option>
                                    <option value="airport_arrival">Трансфер из аэропорта</option>
                                    <option value="airport_departure">Трансфер в аэропорт</option>
                                    <option value="city_transfer">Трансфер город</option>
                                    <option value="train_station">Трансфер ж/д вокзал</option>
                                    <option value="remote_area">Отдаленный район</option>
                                    <option value="other">Иное</option>
                                </select>
                            </div>
                            <div class="mb-2">
                                <label class="form-label">Тип тарифа <span class="required-star">*</span></label>
                                <select name="tariff" class="form-select" required>
                                    <option value="">Выберите...</option>
                                    <option value="standard">Стандарт</option>
                                    <option value="comfort">Комфорт</option>
                                    <option value="business">Бизнес</option>
                                    <option value="premium">Представительский</option>
                                    <option value="crossover">Кроссовер</option>
                                    <option value="minivan5">Минивэн-5</option>
                                    <option value="minivan6">Минивэн-6</option>
                                    <option value="microbus8">Микроавтобус-8</option>
                                    <option value="microbus10">Микроавтобус-10</option>
                                    <option value="microbus14">Микроавтобус-14</option>
                                    <option value="microbus16">Микроавтобус-16</option>
                                    <option value="microbus18">Микроавтобус-18</option>
                                    <option value="microbus24">Микроавтобус-24</option>
                                    <option value="bus35">Автобус-35</option>
                                    <option value="bus44">Автобус-44</option>
                                    <option value="bus50">Автобус-50</option>
                                    <option value="other">Иное</option>
                                </select>
                            </div>
                            <div class="mb-2">
                                <label class="form-label">Часы аренды</label>
                                <input type="number" name="rent_hours" class="form-control" value="0">
                            </div>
                            <div class="mb-2">
                                <label class="form-label">Часов для отмены</label>
                                <input type="number" name="cancellation_hours" class="form-control" value="0">
                            </div>
                        </div>

                        <div class="col-md-3">
                            <h6 class="section-title">Заказчик</h6>
                            <div class="mb-2">
                                <label class="form-label">ФИО заказчика <span class="required-star">*</span></label>
                                <input type="text" name="customer_name" class="form-control" required>
                            </div>
                            <div class="mb-2">
                                <label class="form-label">Телефон <span class="required-star">*</span></label>
                                <input type="text" name="customer_phone" class="form-control phone-mask" required>
                            </div>
                            <div class="mb-2">
                                <label class="form-label">Email</label>
                                <input type="email" name="customer_email" class="form-control">
                            </div>
                            <div class="mb-2">
                                <label class="form-label">Компания заказчик</label>
                                <select name="customer_company_id" class="form-select">
                                    <option value="">Не указана</option>
                                    <?php
                                    $companies = $pdo->query("SELECT id, name FROM companies WHERE is_customer = 1")->fetchAll();
                                    foreach ($companies as $c) echo "<option value='{$c['id']}'>{$c['name']}</option>";
                                    ?>
                                </select>
                            </div>
                        </div>

                        <div class="col-md-3">
                            <h6 class="section-title">Финансы и Компания</h6>
                            <div class="mb-2">
                                <label class="form-label">Исполнитель</label>
                                <select name="executor_company_id" class="form-select">
                                    <option value="">Не указана</option>
                                    <?php
                                    $executors = $pdo->query("SELECT id, name FROM companies WHERE is_customer = 0")->fetchAll();
                                    foreach ($executors as $c) echo "<option value='{$c['id']}'>{$c['name']}</option>";
                                    ?>
                                </select>
                            </div>
                            <div class="mb-2">
                                <label class="form-label">Стоимость заказа</label>
                                <input type="number" step="0.01" name="order_amount" class="form-control" value="0">
                            </div>
                            <div class="mb-2">
                                <label class="form-label">Стоимость исполнителя</label>
                                <input type="number" step="0.01" name="executor_amount" class="form-control" value="0">
                            </div>
                            <div class="mb-2">
                                <label class="form-label">Доп. услуги</label>
                                <input type="number" step="0.01" name="additional_services_amount" class="form-control" value="0">
                            </div>
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h6 class="section-title">Маршрут <button type="button" class="btn btn-xs btn-outline-primary float-end" onclick="Modals.addRoutePoint()">Добавить точку</button></h6>
                            <div id="routesContainer">
                                <!-- Points added via JS -->
                            </div>
                        </div>
                        <div class="col-md-6">
                            <h6 class="section-title">Пассажиры <button type="button" class="btn btn-xs btn-outline-primary float-end" onclick="Modals.addPassengerRow()">Добавить пассажира</button></h6>
                            <div id="passengersContainer">
                                <!-- Passenger rows added via JS -->
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4">
                            <h6 class="section-title">Доп. информация</h6>
                            <div class="mb-2">
                                <label class="form-label">Рейс</label>
                                <input type="text" name="flight_number" class="form-control">
                            </div>
                            <div class="mb-2">
                                <label class="form-label">Текст таблички</label>
                                <input type="text" name="sign_text" class="form-control">
                            </div>
                        </div>
                        <div class="col-md-8">
                            <h6 class="section-title">Комментарии</h6>
                            <div class="row">
                                <div class="col-md-4">
                                    <label class="form-label">Примечание</label>
                                    <textarea name="notes" class="form-control" rows="3"></textarea>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Комментарий менеджера (видит водитель)</label>
                                    <textarea name="manager_comment" class="form-control" rows="3"></textarea>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Внутренний комментарий</label>
                                    <textarea name="internal_comment" class="form-control" rows="3"></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                <button type="button" class="btn btn-primary" id="btnSaveApplication" onclick="ApplicationsManager.saveApplication()">Сохранить</button>
            </div>
        </div>
    </div>
</div>

<!-- Assign Driver Modal -->
<div class="modal fade" id="assignDriverModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Назначение водителя</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="assign_driver_app_id">
                <div class="table-responsive">
                    <table class="table table-hover" id="driversTable">
                        <thead>
                            <tr>
                                <th>ФИО</th>
                                <th>Статус</th>
                                <th>Рейтинг</th>
                                <th>Город</th>
                                <th>Заказов</th>
                                <th>Действие</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Assign Vehicle Modal -->
<div class="modal fade" id="assignVehicleModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Назначение автомобиля</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="assign_vehicle_app_id">
                <div class="table-responsive">
                    <table class="table table-hover" id="vehiclesTable">
                        <thead>
                            <tr>
                                <th>Марка/Модель</th>
                                <th>Класс</th>
                                <th>Гос. номер</th>
                                <th>Статус</th>
                                <th>Действие</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
