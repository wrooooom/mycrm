<div class="container-fluid h-100">
    <div class="row h-100">
        <nav id="sidebarMenu" class="col-md-3 col-lg-2 d-md-block bg-light sidebar collapse">
            <div class="position-sticky pt-3">
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'index.php') ? 'active' : ''; ?>" href="/index.php">
                            <i class="bi bi-speedometer2 me-2"></i>
                            Главная
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'applications.php') ? 'active' : ''; ?>" href="/applications.php">
                            <i class="bi bi-clipboard-check me-2"></i>
                            Заявки
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'drivers.php') ? 'active' : ''; ?>" href="/drivers.php">
                            <i class="bi bi-person-badge me-2"></i>
                            Водители
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'vehicles.php') ? 'active' : ''; ?>" href="/vehicles.php">
                            <i class="bi bi-truck me-2"></i>
                            Транспорт
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'companies.php') ? 'active' : ''; ?>" href="/companies.php">
                            <i class="bi bi-building me-2"></i>
                            Компании
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'tracking.php') ? 'active' : ''; ?>" href="/tracking.php">
                            <i class="bi bi-map me-2"></i>
                            Трекинг
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'analytics.php') ? 'active' : ''; ?>" href="/analytics.php">
                            <i class="bi bi-graph-up me-2"></i>
                            Аналитика
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'settings.php') ? 'active' : ''; ?>" href="/settings.php">
                            <i class="bi bi-gear me-2"></i>
                            Настройки
                        </a>
                    </li>
                </ul>
                
                <!-- Блок пользователя -->
                <div class="mt-4 p-3 border-top">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-user-circle me-2 text-primary"></i>
                        <div>
                            <small class="fw-bold"><?php echo htmlspecialchars($_SESSION['full_name'] ?? $_SESSION['username']); ?></small>
                            <br>
                            <small class="text-muted">
                                <?php 
                                if (isAdmin()) echo 'Администратор';
                                elseif (isDispatcher()) echo 'Диспетчер';
                                elseif (isManager()) echo 'Менеджер';
                                else echo 'Пользователь';
                                ?>
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </nav>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="row h-100">
                <!-- Основное рабочее пространство -->
                <div class="col-lg-8 pt-3">