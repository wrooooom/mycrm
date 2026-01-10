                </div> <!-- Закрываем col-lg-8 -->

                <!-- Панель с календарем и историей (займет 1/3 ширины) -->
                <div class="col-lg-4 border-start pt-3">
                    <div class="mb-4">
                        <h5>Календарь</h5>
                        <div id="calendar"></div>
                    </div>                </div>

                <!-- Боковая панель с календарем и историей -->
                <div class="col-lg-4 border-start pt-3">
                    <div class="mb-4">
                        <h5><i class="bi bi-calendar me-2"></i>Календарь</h5>
                        <div id="calendar" class="border rounded p-3 bg-light"></div>
                    </div>
                    <div>
                        <h5><i class="bi bi-clock-history me-2"></i>История действий</h5>
                        <div class="list-group list-group-flush small">
                            <?php
                            // Функция для получения последних действий
                            function getRecentActivity($pdo, $limit = 5) {
                                try {
                                    $stmt = $pdo->prepare("
                                        SELECT al.*, u.username 
                                        FROM activity_log al 
                                        LEFT JOIN users u ON al.user_id = u.id 
                                        ORDER BY al.created_at DESC 
                                        LIMIT ?
                                    ");
                                    $stmt->execute([$limit]);
                                    return $stmt->fetchAll(PDO::FETCH_ASSOC);
                                } catch(Exception $e) {
                                    return [];
                                }
                            }
                            
                            $history = getRecentActivity($pdo);
                            if ($history) {
                                foreach ($history as $item) {
                                    echo '<div class="list-group-item list-group-item-action">';
                                    echo '<div class="d-flex w-100 justify-content-between">';
                                    echo '<h6 class="mb-1">' . htmlspecialchars($item['action']) . '</h6>';
                                    echo '<small>' . date('H:i', strtotime($item['created_at'])) . '</small>';
                                    echo '</div>';
                                    echo '<p class="mb-1">' . ($item['username'] ? 'Пользователь: ' . htmlspecialchars($item['username']) : 'Система') . '</p>';
                                    echo '</div>';
                                }
                            } else {
                                echo '<div class="list-group-item">';
                                echo '<p class="text-muted mb-0">Нет недавних действий</p>';
                                echo '</div>';
                            }
                            ?>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const today = new Date();
        const month = today.toLocaleString('ru-RU', { month: 'long' });
        const year = today.getFullYear();
        const daysInMonth = new Date(today.getFullYear(), today.getMonth() + 1, 0).getDate();

        let calendarHTML = `<h6 class="text-center text-primary">${month} ${year}</h6>`;
        calendarHTML += `<div class="d-grid gap-1" style="grid-template-columns: repeat(7, 1fr);">`;
        
        const weekdays = ['Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб', 'Вс'];
        weekdays.forEach(day => {
            calendarHTML += `<small class="text-center text-muted fw-bold">${day}</small>`;
        });

        for (let i = 1; i <= daysInMonth; i++) {
            const isToday = i === today.getDate();
            const classToday = isToday ? 'bg-primary text-white rounded' : '';
            calendarHTML += `<span class="text-center p-1 ${classToday}">${i}</span>`;
        }

        calendarHTML += `</div>`;
        document.getElementById('calendar').innerHTML = calendarHTML;
    });
</script>

<!-- Дополнительные скрипты для конкретных страниц -->
<?php if (isset($additional_scripts)) echo $additional_scripts; ?>

</body>
</html>
                    <div>
                        <h5>История действий</h5>
                        <div class="list-group list-group-flush small">
                            <?php
                            // Пример вывода истории. Позже мы это оживим.
                            $history = getRecentActivity($pdo); // Эту функцию нужно будет создать в functions.php
                            if ($history) {
                                foreach ($history as $item) {
                                    echo '<div class="list-group-item list-group-item-action">';
                                    echo '<div class="d-flex w-100 justify-content-between">';
                                    echo '<h6 class="mb-1">' . htmlspecialchars($item['action']) . '</h6>';
                                    echo '<small>' . $item['time_ago'] . '</small>';
                                    echo '</div>';
                                    echo '<p class="mb-1">' . htmlspecialchars($item['details']) . '</p>';
                                    echo '</div>';
                                }
                            } else {
                                echo '<p class="text-muted">Нет recent activity.</p>';
                            }
                            ?>
                        </div>
                    </div>
                </div> <!-- Закрываем col-lg-4 -->
            </div> <!-- Закрываем row -->
        </main> <!-- Закрываем main -->
    </div> <!-- Закрываем row -->
</div> <!-- Закрываем container-fluid -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="/js/script.js"></script>
<!-- Скрипт для календаря -->
<script>
    // Простой календарь. Позже можно подключить полноценный, например, FullCalendar.
    document.addEventListener('DOMContentLoaded', function() {
        const today = new Date();
        const month = today.toLocaleString('ru-RU', { month: 'long' });
        const year = today.getFullYear();
        const daysInMonth = new Date(today.getFullYear(), today.getMonth() + 1, 0).getDate();

        let calendarHTML = `<h6 class="text-center">${month} ${year}</h6>`;
        calendarHTML += `<div class="d-grid gap-1" style="grid-template-columns: repeat(7, 1fr);">`;
        
        // Добавляем дни недели
        const weekdays = ['Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб', 'Вс'];
        weekdays.forEach(day => {
            calendarHTML += `<small class="text-center text-muted">${day}</small>`;
        });

        // Добавляем числа
        for (let i = 1; i <= daysInMonth; i++) {
            const isToday = i === today.getDate();
            const classToday = isToday ? 'bg-primary text-white rounded' : '';
            calendarHTML += `<span class="text-center p-1 ${classToday}">${i}</span>`;
        }

        calendarHTML += `</div>`;
        document.getElementById('calendar').innerHTML = calendarHTML;
    });
</script>
</body>
</html>