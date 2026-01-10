/**
 * Modals Management for CRM PROFTRANSFER
 */

const Modals = {
    // Open application creation modal
    openApplicationCreate: function() {
        const modal = new bootstrap.Modal(document.getElementById('applicationModal'));
        document.getElementById('applicationForm').reset();
        document.getElementById('applicationModalLabel').innerText = 'Создание нового заказа';
        document.getElementById('app_id').value = '';
        
        // Clear routes and passengers except the defaults
        const routesContainer = document.getElementById('routesContainer');
        routesContainer.innerHTML = '';
        this.addRoutePoint();
        this.addRoutePoint();
        
        const passengersContainer = document.getElementById('passengersContainer');
        passengersContainer.innerHTML = '';
        this.addPassengerRow();

        this.applyPhoneMask(document.querySelector('#applicationForm .phone-mask'));
        
        modal.show();
    },

    // Open application edit modal
    openApplicationEdit: function(appId) {
        fetch(`/api/applications.php?action=getById&id=${appId}`)
            .then(response => response.json())
            .then(res => {
                if (res.success) {
                    const app = res.data;
                    const modal = new bootstrap.Modal(document.getElementById('applicationModal'));
                    document.getElementById('applicationModalLabel').innerText = `Редактирование заказа ${app.application_number}`;
                    
                    // Fill form fields
                    const form = document.getElementById('applicationForm');
                    form.app_id.value = app.id;
                    form.status.value = app.status;
                    form.city.value = app.city;
                    form.country.value = app.country;
                    form.trip_date.value = app.trip_date.replace(' ', 'T').substring(0, 16);
                    form.service_type.value = app.service_type;
                    form.tariff.value = app.tariff;
                    form.cancellation_hours.value = app.cancellation_hours;
                    form.customer_name.value = app.customer_name;
                    form.customer_phone.value = app.customer_phone;
                    form.additional_services_amount.value = app.additional_services_amount;
                    form.flight_number.value = app.flight_number;
                    form.sign_text.value = app.sign_text;
                    form.notes.value = app.notes;
                    form.manager_comment.value = app.manager_comment;
                    if (form.internal_comment) form.internal_comment.value = app.internal_comment;
                    form.customer_company_id.value = app.customer_company_id || '';
                    form.executor_company_id.value = app.executor_company_id || '';
                    form.order_amount.value = app.order_amount;
                    form.executor_amount.value = app.executor_amount;

                    // Fill routes
                    const routesContainer = document.getElementById('routesContainer');
                    routesContainer.innerHTML = '';
                    if (app.routes && app.routes.length > 0) {
                        app.routes.forEach(route => this.addRoutePoint(route.address));
                    } else {
                        this.addRoutePoint();
                        this.addRoutePoint();
                    }

                    // Fill passengers
                    const passengersContainer = document.getElementById('passengersContainer');
                    passengersContainer.innerHTML = '';
                    if (app.passengers && app.passengers.length > 0) {
                        app.passengers.forEach(p => this.addPassengerRow(p.name, p.phone));
                    } else {
                        this.addPassengerRow();
                    }

                    // Handle readonly state based on status and role
                    this.applyAclToForm(app.status);

                    this.applyPhoneMask(document.querySelector('#applicationForm .phone-mask'));

                    modal.show();
                } else {
                    alert('Ошибка при получении данных заказа: ' + res.message);
                }
            });
    },

    applyAclToForm: function(status) {
        const form = document.getElementById('applicationForm');
        const isCompleted = status === 'completed';
        const isConfirmed = status === 'confirmed' || status === 'confirmed'; // "Принята"
        
        const inputs = form.querySelectorAll('input, select, textarea');
        
        if (isCompleted) {
            inputs.forEach(input => input.disabled = true);
            document.getElementById('btnSaveApplication').style.display = 'none';
        } else if (isConfirmed) {
            // "Принята" - только комментарии (as per ticket)
            // But ticket says: "админ и менеджер могут редактировать только комментарии"
            inputs.forEach(input => {
                if (!['manager_comment', 'internal_comment'].includes(input.name)) {
                    input.disabled = true;
                } else {
                    input.disabled = false;
                }
            });
            document.getElementById('btnSaveApplication').style.display = 'inline-block';
        } else {
            inputs.forEach(input => input.disabled = false);
            document.getElementById('btnSaveApplication').style.display = 'inline-block';
        }
    },

    addRoutePoint: function(address = '') {
        const container = document.getElementById('routesContainer');
        const pointCount = container.children.length;
        const letter = String.fromCharCode(65 + pointCount); // A, B, C...
        
        const div = document.createElement('div');
        div.className = 'route-point d-flex align-items-center';
        div.innerHTML = `
            <span class="fw-bold me-2">${letter}</span>
            <input type="text" name="routes[]" class="form-control" placeholder="Адрес" value="${address}" required>
            ${pointCount > 1 ? '<i class="fas fa-times btn-remove ms-2" onclick="this.parentElement.remove()"></i>' : ''}
        `;
        container.appendChild(div);
    },

    addPassengerRow: function(name = '', phone = '') {
        const container = document.getElementById('passengersContainer');
        const div = document.createElement('div');
        div.className = 'passenger-row d-flex align-items-center';
        div.innerHTML = `
            <input type="text" name="passenger_names[]" class="form-control me-2" placeholder="ФИО" value="${name}">
            <input type="text" name="passenger_phones[]" class="form-control me-2 phone-mask" placeholder="Телефон" value="${phone}">
            <i class="fas fa-times btn-remove" onclick="this.parentElement.remove()"></i>
        `;
        container.appendChild(div);
        
        // Re-apply mask if available
        if (window.applyPhoneMask) {
            window.applyPhoneMask(div.querySelector('.phone-mask'));
        }
    },

    openDriverAssign: function(appId) {
        document.getElementById('assign_driver_app_id').value = appId;
        const modal = new bootstrap.Modal(document.getElementById('assignDriverModal'));
        this.loadDrivers();
        modal.show();
    },

    loadDrivers: function() {
        fetch('/api/drivers.php?action=getAll')
            .then(response => response.json())
            .then(res => {
                if (res.success) {
                    const tbody = document.querySelector('#driversTable tbody');
                    tbody.innerHTML = '';
                    res.data.forEach(driver => {
                        const tr = document.createElement('tr');
                        tr.innerHTML = `
                            <td>${driver.first_name} ${driver.last_name}</td>
                            <td><span class="badge bg-${this.getDriverStatusColor(driver.status)}">${this.getDriverStatusText(driver.status)}</span></td>
                            <td>${driver.rating}</td>
                            <td>${driver.city}</td>
                            <td>${driver.active_orders_count || 0}</td>
                            <td>
                                <button class="btn btn-sm btn-primary" onclick="ApplicationsManager.assignDriver(${driver.id})">Назначить</button>
                            </td>
                        `;
                        tbody.appendChild(tr);
                    });
                }
            });
    },

    openVehicleAssign: function(appId) {
        document.getElementById('assign_vehicle_app_id').value = appId;
        const modal = new bootstrap.Modal(document.getElementById('assignVehicleModal'));
        this.loadVehicles();
        modal.show();
    },

    loadVehicles: function() {
        fetch('/api/vehicles.php?action=getAll')
            .then(response => response.json())
            .then(res => {
                if (res.success) {
                    const tbody = document.querySelector('#vehiclesTable tbody');
                    tbody.innerHTML = '';
                    res.data.filter(v => v.status !== 'broken').forEach(vehicle => {
                        const tr = document.createElement('tr');
                        tr.innerHTML = `
                            <td>${vehicle.brand} ${vehicle.model}</td>
                            <td>${vehicle.class}</td>
                            <td>${vehicle.license_plate}</td>
                            <td><span class="badge bg-${vehicle.status === 'working' ? 'success' : 'warning'}">${vehicle.status}</span></td>
                            <td>
                                <button class="btn btn-sm btn-primary" onclick="ApplicationsManager.assignVehicle(${vehicle.id})">Назначить</button>
                            </td>
                        `;
                        tbody.appendChild(tr);
                    });
                }
            });
    },

    getDriverStatusColor: function(status) {
        const colors = {
            'work': 'success',
            'dayoff': 'secondary',
            'vacation': 'info',
            'repair': 'warning'
        };
        return colors[status] || 'dark';
    },

    getDriverStatusText: function(status) {
        const texts = {
            'work': 'В работе',
            'dayoff': 'Выходной',
            'vacation': 'Отпуск',
            'repair': 'Резонт'
        };
        return texts[status] || status;
    },

    applyPhoneMask: function(input) {
        if (!input) return;
        input.addEventListener('input', function(e) {
            let x = e.target.value.replace(/\D/g, '').match(/(\d{0,1})(\d{0,3})(\d{0,3})(\d{0,2})(\d{0,2})/);
            if (!x[2]) {
                e.target.value = x[1] === '7' || x[1] === '8' ? '+7 ' : x[1];
                return;
            }
            e.target.value = !x[3] ? '+7 (' + x[2] : '+7 (' + x[2] + ') ' + x[3] + (x[4] ? '-' + x[4] : '') + (x[5] ? '-' + x[5] : '');
        });
    }
};

window.Modals = Modals;
window.applyPhoneMask = Modals.applyPhoneMask;
