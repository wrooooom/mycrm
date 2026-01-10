/**
 * Applications Management Logic for CRM PROFTRANSFER
 */

const ApplicationsManager = {
    init: function() {
        const form = document.getElementById('applicationForm');
        if (form) {
            form.addEventListener('submit', (e) => {
                e.preventDefault();
                this.saveApplication();
            });
        }
        
        // Initial load if on applications page
        if (document.getElementById('applicationsTable')) {
            this.loadApplications();
        }
    },

    loadApplications: function(filters = {}) {
        let url = '/api/applications.php?action=getAll';
        Object.keys(filters).forEach(key => {
            if (filters[key]) url += `&${key}=${encodeURIComponent(filters[key])}`;
        });

        fetch(url)
            .then(response => response.json())
            .then(res => {
                if (res.success) {
                    this.renderApplicationsTable(res.data);
                }
            });
    },

    renderApplicationsTable: function(applications) {
        const tbody = document.querySelector('#applicationsTable tbody');
        if (!tbody) return;
        
        tbody.innerHTML = '';
        applications.forEach(app => {
            const tr = document.createElement('tr');
            tr.className = 'clickable-row';
            tr.onclick = (e) => {
                if (e.target.tagName !== 'BUTTON' && !e.target.closest('button')) {
                    Modals.openApplicationEdit(app.id);
                }
            };
            
            const route = app.routes && app.routes.length >= 2 
                ? `${app.routes[0].address} → ${app.routes[app.routes.length-1].address}` 
                : (app.city || 'Не указан');

            tr.innerHTML = `
                <td>${app.application_number}</td>
                <td><span class="badge bg-${this.getStatusColor(app.status)}">${this.getStatusText(app.status)}</span></td>
                <td>${app.customer_name}<br><small class="text-muted">${app.customer_phone}</small></td>
                <td>${route}</td>
                <td>${this.formatDate(app.trip_date)}</td>
                <td>${app.driver_first_name ? app.driver_first_name + ' ' + app.driver_last_name : '<button class="btn btn-sm btn-outline-primary" onclick="Modals.openDriverAssign('+app.id+')">Назначить</button>'}</td>
                <td>${app.vehicle_brand ? app.vehicle_brand + ' ' + app.vehicle_model : '<button class="btn btn-sm btn-outline-primary" onclick="Modals.openVehicleAssign('+app.id+')">Назначить</button>'}</td>
                <td>${app.order_amount}</td>
                <td>
                    <div class="btn-group">
                        <button class="btn btn-sm btn-light" onclick="Modals.openApplicationEdit(${app.id})"><i class="fas fa-edit"></i></button>
                        <button class="btn btn-sm btn-light text-danger" onclick="ApplicationsManager.deleteApplication(${app.id})"><i class="fas fa-trash"></i></button>
                    </div>
                </td>
            `;
            tbody.appendChild(tr);
        });
    },

    saveApplication: function() {
        const form = document.getElementById('applicationForm');
        const formData = new FormData(form);
        const data = {};
        formData.forEach((value, key) => {
            if (key.endsWith('[]')) {
                const cleanKey = key.slice(0, -2);
                if (!data[cleanKey]) data[cleanKey] = [];
                data[cleanKey].push(value);
            } else {
                data[key] = value;
            }
        });

        // Add passengers
        const passenger_names = formData.getAll('passenger_names[]');
        const passenger_phones = formData.getAll('passenger_phones[]');
        data.passengers = passenger_names.map((name, i) => ({
            name: name,
            phone: passenger_phones[i]
        })).filter(p => p.name);

        const action = data.app_id ? 'update' : 'create';
        
        fetch(`/api/applications.php?action=${action}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(data)
        })
        .then(response => response.json())
        .then(res => {
            if (res.success) {
                alert(res.message);
                bootstrap.Modal.getInstance(document.getElementById('applicationModal')).hide();
                this.loadApplications();
            } else {
                alert('Ошибка: ' + res.message);
            }
        })
        .catch(err => {
            console.error(err);
            alert('Произошла системная ошибка при сохранении');
        });
    },

    assignDriver: function(driverId) {
        const appId = document.getElementById('assign_driver_app_id').value;
        fetch('/api/applications.php?action=assignDriver', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ application_id: appId, driver_id: driverId })
        })
        .then(response => response.json())
        .then(res => {
            if (res.success) {
                bootstrap.Modal.getInstance(document.getElementById('assignDriverModal')).hide();
                this.loadApplications();
            } else {
                alert(res.message);
            }
        });
    },

    assignVehicle: function(vehicleId) {
        const appId = document.getElementById('assign_vehicle_app_id').value;
        fetch('/api/applications.php?action=assignVehicle', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ application_id: appId, vehicle_id: vehicleId })
        })
        .then(response => response.json())
        .then(res => {
            if (res.success) {
                bootstrap.Modal.getInstance(document.getElementById('assignVehicleModal')).hide();
                this.loadApplications();
            } else {
                alert(res.message);
            }
        });
    },

    updateStatus: function(appId, newStatus) {
        fetch('/api/applications.php?action=updateStatus', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ application_id: appId, status: newStatus })
        })
        .then(response => response.json())
        .then(res => {
            if (res.success) {
                this.loadApplications();
            } else {
                alert(res.message);
            }
        });
    },

    deleteApplication: function(appId) {
        if (!confirm('Вы уверены, что хотите удалить этот заказ?')) return;
        
        fetch(`/api/applications.php?action=delete&id=${appId}`, { method: 'POST' })
            .then(response => response.json())
            .then(res => {
                if (res.success) {
                    this.loadApplications();
                } else {
                    alert(res.message);
                }
            });
    },

    getStatusColor: function(status) {
        const colors = {
            'new': 'success',
            'confirmed': 'info',
            'inwork': 'primary',
            'completed': 'secondary',
            'cancelled': 'danger',
            'cancel_penalty': 'warning'
        };
        return colors[status] || 'dark';
    },

    getStatusText: function(status) {
        const texts = {
            'new': 'Не обработана',
            'confirmed': 'Принята',
            'inwork': 'В работе',
            'completed': 'Выполнена',
            'cancelled': 'Отменена',
            'cancel_penalty': 'Отмена со штрафом'
        };
        return texts[status] || status;
    },

    formatDate: function(dateStr) {
        if (!dateStr) return '';
        const d = new Date(dateStr);
        return d.toLocaleString('ru-RU', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' });
    }
};

document.addEventListener('DOMContentLoaded', () => {
    ApplicationsManager.init();
});

window.ApplicationsManager = ApplicationsManager;
