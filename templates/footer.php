            </div> <!-- Close row from sidebar/main layout -->
        </main>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<!-- Shared Calendar and History Logic -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const calendarEl = document.getElementById('calendar');
        if (calendarEl) {
            const today = new Date();
            const month = today.toLocaleString('ru-RU', { month: 'long' });
            const year = today.getFullYear();
            const daysInMonth = new Date(today.getFullYear(), today.getMonth() + 1, 0).getDate();

            let calendarHTML = `<h6 class="text-center text-primary">${month} ${year}</h6>`;
            calendarHTML += `<div class="d-grid gap-1" style="grid-template-columns: repeat(7, 1fr);">`;
            
            const weekdays = ['Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб', 'Вс'];
            weekdays.forEach(day => {
                calendarHTML += `<small class="text-center text-muted fw-bold" style="font-size: 0.7rem;">${day}</small>`;
            });

            for (let i = 1; i <= daysInMonth; i++) {
                const isToday = i === today.getDate();
                const classToday = isToday ? 'bg-primary text-white rounded' : '';
                calendarHTML += `<span class="text-center p-1 ${classToday}" style="font-size: 0.8rem; cursor: pointer;">${i}</span>`;
            }

            calendarHTML += `</div>`;
            calendarEl.innerHTML = calendarHTML;
        }
    });
</script>

<?php if (isset($additional_scripts)) echo $additional_scripts; ?>

</body>
</html>
