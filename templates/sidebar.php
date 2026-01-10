<nav id="sidebarMenu" class="col-md-3 col-lg-2 d-md-block bg-light sidebar collapse shadow-sm">
    <div class="position-sticky pt-3 h-100 d-flex flex-column">
        <ul class="nav flex-column mb-auto">
            <li class="nav-item">
                <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'index.php') ? 'active fw-bold text-primary' : ''; ?>" href="/index.php">
                    <i class="fas fa-home me-2"></i> РАБОЧИЙ СТОЛ
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'applications.php') ? 'active fw-bold text-primary' : ''; ?>" href="/applications.php">
                    <i class="fas fa-file-alt me-2"></i> ЗАКАЗЫ
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'drivers.php') ? 'active fw-bold text-primary' : ''; ?>" href="/drivers.php">
                    <i class="fas fa-users me-2"></i> ВОДИТЕЛИ
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'vehicles.php') ? 'active fw-bold text-primary' : ''; ?>" href="/vehicles.php">
                    <i class="fas fa-car me-2"></i> АВТОМОБИЛИ
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'users.php') ? 'active fw-bold text-primary' : ''; ?>" href="/users.php">
                    <i class="fas fa-user-cog me-2"></i> ПОЛЬЗОВАТЕЛИ
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'billing.php') ? 'active fw-bold text-primary' : ''; ?>" href="/billing.php">
                    <i class="fas fa-file-invoice-dollar me-2"></i> СЧЕТА
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'analytics.php') ? 'active fw-bold text-primary' : ''; ?>" href="/analytics.php">
                    <i class="fas fa-chart-line me-2"></i> АНАЛИТИКА
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'tracking.php') ? 'active fw-bold text-primary' : ''; ?>" href="/tracking.php">
                    <i class="fas fa-map-marker-alt me-2"></i> ТРЕКИНГ
                </a>
            </li>
        </ul>

        <!-- Calendar Widget -->
        <div class="px-3 mb-4 mt-4">
            <div id="calendar" class="border rounded p-2 bg-white small"></div>
        </div>
        
        <!-- User Info -->
        <div class="p-3 border-top mt-auto bg-white">
            <div class="d-flex align-items-center">
                <div class="flex-shrink-0">
                    <i class="fas fa-user-circle fa-2x text-secondary"></i>
                </div>
                <div class="flex-grow-1 ms-2 overflow-hidden">
                    <div class="text-truncate fw-bold small"><?php echo htmlspecialchars($_SESSION['full_name'] ?? $_SESSION['username']); ?></div>
                    <div class="small text-muted text-uppercase" style="font-size: 0.6rem;"><?php echo htmlspecialchars($_SESSION['user_role']); ?></div>
                </div>
            </div>
        </div>
    </div>
</nav>
