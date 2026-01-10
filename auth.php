<?php
require_once 'config.php';

/**
 * Проверка авторизации пользователя
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Проверка роли администратора
 */
function isAdmin() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

/**
 * Проверка роли диспетчера
 */
function isDispatcher() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'dispatcher';
}

/**
 * Проверка роли менеджера
 */
function isManager() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'manager';
}

/**
 * Получение данных текущего пользователя
 */
function getUserData() {
    if (isLoggedIn()) {
        return [
            'id' => $_SESSION['user_id'],
            'username' => $_SESSION['username'],
            'full_name' => $_SESSION['full_name'],
            'role' => $_SESSION['user_role'],
            'email' => $_SESSION['email'] ?? '',
            'last_login' => $_SESSION['last_login'] ?? ''
        ];
    }
    return null;
}

/**
 * Защита страниц - требует авторизации
 */
function requireLogin() {
    if (!isLoggedIn()) {
        $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
        logAction('attempt_access_without_login');
        header("Location: login.php");
        exit();
    }
}

/**
 * Защита административных страниц
 */
function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        logAction('attempt_access_admin_without_permission', $_SESSION['user_id']);
        header("Location: index.php");
        exit();
    }
}

/**
 * Защита страниц для диспетчеров и выше
 */
function requireDispatcher() {
    requireLogin();
    if (!isAdmin() && !isDispatcher()) {
        logAction('attempt_access_dispatcher_without_permission', $_SESSION['user_id']);
        header("Location: index.php");
        exit();
    }
}

/**
 * Защита страниц для менеджеров и выше
 */
function requireManager() {
    requireLogin();
    if (!isAdmin() && !isManager()) {
        logAction('attempt_access_manager_without_permission', $_SESSION['user_id']);
        header("Location: index.php");
        exit();
    }
}

/**
 * Получение названия роли на русском
 */
function getRoleName($role) {
    $roles = [
        'admin' => 'Администратор',
        'dispatcher' => 'Диспетчер', 
        'manager' => 'Менеджер',
        'driver' => 'Водитель'
    ];
    return $roles[$role] ?? $role;
}

/**
 * Проверка прав доступа к модулю
 */
function hasAccess($module) {
    $userRole = $_SESSION['user_role'] ?? '';
    
    $accessMatrix = [
        'admin' => ['applications', 'drivers', 'vehicles', 'companies', 'analytics', 'settings'],
        'dispatcher' => ['applications', 'drivers', 'vehicles', 'companies'],
        'manager' => ['applications', 'drivers', 'vehicles'],
        'driver' => ['applications']
    ];
    
    return in_array($module, $accessMatrix[$userRole] ?? []);
}
?>
