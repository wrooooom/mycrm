                <!-- Основной контент страницы размещается здесь -->
            </main>
        </div>
    </div>

    <!-- Футер -->
    <footer class="footer mt-auto py-3 bg-light border-top">
        <div class="container-fluid">
            <span class="text-muted">
                © 2025 CRM.PROFTRANSFER - Система управления трансферными услугами
            </span>
        </div>
    </footer>

    <!-- JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    
    <!-- Дополнительные скрипты для конкретных страниц -->
    <?php if (isset($additional_js)) echo $additional_js; ?>
    
    <!-- Общие скрипты -->
    <script>
        // Автоматическое скрытие уведомлений
        document.addEventListener('DOMContentLoaded', function() {
            const alerts = document.querySelectorAll('.alert:not(.alert-permanent)');
            alerts.forEach(function(alert) {
                setTimeout(function() {
                    const bsAlert = new bootstrap.Alert(alert);
                    bsAlert.close();
                }, 5000);
            });
        });

        // Подтверждение действий
        function confirmAction(message, callback) {
            if (confirm(message)) {
                callback();
            }
        }

        // AJAX запросы с обработкой ошибок
        function ajaxRequest(url, data, callback) {
            $.ajax({
                url: url,
                type: 'POST',
                data: data,
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        showAlert(response.message, 'success');
                        if (callback) callback(response);
                    } else {
                        showAlert('Ошибка: ' + response.message, 'danger');
                    }
                },
                error: function(xhr, status, error) {
                    showAlert('Ошибка связи с сервером', 'danger');
                    console.error('AJAX Error:', error);
                }
            });
        }

        // Показ уведомлений
        function showAlert(message, type) {
            const alertHtml = `
                <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                    ${message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            `;
            
            // Удаляем старые уведомления
            $('.alert').remove();
            
            // Добавляем новое уведомление
            $('.container-fluid').prepend(alertHtml);
            
            // Прокручиваем к верху
            $('html, body').animate({ scrollTop: 0 }, 'fast');
        }

        // Подтверждение удаления
        function confirmDelete(itemName, callback) {
            if (confirm(`Вы уверены, что хотите удалить ${itemName}?`)) {
                callback();
            }
        }

        // Форматирование даты
        function formatDate(dateString) {
            const date = new Date(dateString);
            return date.toLocaleDateString('ru-RU', {
                year: 'numeric',
                month: '2-digit',
                day: '2-digit',
                hour: '2-digit',
                minute: '2-digit'
            });
        }

        // Форматирование суммы
        function formatAmount(amount) {
            return new Intl.NumberFormat('ru-RU', {
                style: 'currency',
                currency: 'RUB',
                minimumFractionDigits: 0
            }).format(amount);
        }

        // Валидация формы
        function validateForm(formId) {
            const form = document.getElementById(formId);
            const requiredFields = form.querySelectorAll('[required]');
            let isValid = true;

            requiredFields.forEach(function(field) {
                if (!field.value.trim()) {
                    field.classList.add('is-invalid');
                    isValid = false;
                } else {
                    field.classList.remove('is-invalid');
                }
            });

            return isValid;
        }

        // Очистка валидации при вводе
        $(document).on('input', '.is-invalid', function() {
            $(this).removeClass('is-invalid');
        });
    </script>
</body>
</html>