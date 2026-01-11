<?php
/**
 * Боковая панель навигации
 */
?>

<nav id="sidebarMenu" class="col-md-3 col-lg-2 d-md-block bg-light sidebar collapse">
    <div class="position-sticky pt-3">
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : '' ?>" href="index.php">
                    <i class="fas fa-tachometer-alt"></i>
                    Главная
                </a>
            </li>
            
            <?php if (hasAccess('applications')): ?>
            <li class="nav-item">
                <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'applications.php' ? 'active' : '' ?>" href="applications.php">
                    <i class="fas fa-shopping-cart"></i>
                    Заказы
                </a>
            </li>
            <?php endif; ?>
            
            <?php if (hasAccess('drivers')): ?>
            <li class="nav-item">
                <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'drivers.php' ? 'active' : '' ?>" href="drivers.php">
                    <i class="fas fa-users"></i>
                    Водители
                </a>
            </li>
            <?php endif; ?>
            
            <?php if (hasAccess('vehicles')): ?>
            <li class="nav-item">
                <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'vehicles.php' ? 'active' : '' ?>" href="vehicles.php">
                    <i class="fas fa-car"></i>
                    Автомобили
                </a>
            </li>
            <?php endif; ?>
            
            <?php if (hasAccess('companies')): ?>
            <li class="nav-item">
                <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'companies.php' ? 'active' : '' ?>" href="companies.php">
                    <i class="fas fa-building"></i>
                    Компании
                </a>
            </li>
            <?php endif; ?>
            
            <?php if (hasAccess('analytics')): ?>
            <li class="nav-item">
                <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'analytics.php' ? 'active' : '' ?>" href="analytics.php">
                    <i class="fas fa-chart-bar"></i>
                    Аналитика
                </a>
            </li>
            <?php endif; ?>
            
            <li class="nav-item">
                <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'search.php' ? 'active' : '' ?>" href="search.php">
                    <i class="fas fa-search"></i>
                    Поиск
                </a>
            </li>
        </ul>

        <h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1 text-muted">
            <span>Быстрые действия</span>
        </h6>
        <ul class="nav flex-column mb-2">
            <li class="nav-item">
                <a class="nav-link" href="#" onclick="openQuickCreateModal()">
                    <i class="fas fa-plus"></i>
                    Создать заказ
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="assign-vehicle.php">
                    <i class="fas fa-user-plus"></i>
                    Назначить автомобиль
                </a>
            </li>
        </ul>

        <h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1 text-muted">
            <span>Информация</span>
        </h6>
        <ul class="nav flex-column mb-2">
            <li class="nav-item">
                <span class="nav-link text-muted">
                    <i class="fas fa-user"></i>
                    Пользователь: <?= htmlspecialchars($_SESSION['full_name'] ?? $_SESSION['username']) ?>
                </span>
            </li>
            <li class="nav-item">
                <span class="nav-link text-muted">
                    <i class="fas fa-user-shield"></i>
                    Роль: <?= getRoleName($_SESSION['user_role']) ?>
                </span>
            </li>
        </ul>
    </div>
</nav>

<script>
function openQuickCreateModal() {
    // Открыть модальное окно создания заказа
    const modal = new bootstrap.Modal(document.getElementById('createApplicationModal'));
    modal.show();
}
</script>