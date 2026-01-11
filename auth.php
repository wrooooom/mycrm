<?php
require_once 'config.php';

/**
 * auth.php - session helpers and RBAC
 *
 * Some templates include includes/functions.php which may define similar helpers.
 * All functions here are guarded with function_exists() to avoid redeclaration.
 */

if (!function_exists('isLoggedIn')) {
    function isLoggedIn() {
        return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
    }
}

if (!function_exists('isAdmin')) {
    function isAdmin() {
        return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
    }
}

if (!function_exists('isDispatcher')) {
    function isDispatcher() {
        return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'dispatcher';
    }
}

if (!function_exists('isManager')) {
    function isManager() {
        return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'manager';
    }
}

if (!function_exists('getUserData')) {
    function getUserData() {
        if (isLoggedIn()) {
            return [
                'id' => $_SESSION['user_id'],
                'username' => $_SESSION['username'] ?? '',
                'full_name' => $_SESSION['full_name'] ?? '',
                'role' => $_SESSION['user_role'] ?? '',
                'email' => $_SESSION['email'] ?? '',
                'last_login' => $_SESSION['last_login'] ?? ''
            ];
        }
        return null;
    }
}

if (!function_exists('requireLogin')) {
    function requireLogin() {
        if (!isLoggedIn()) {
            $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
            if (function_exists('logAction')) {
                logAction('attempt_access_without_login');
            }
            header("Location: login.php");
            exit();
        }
    }
}

if (!function_exists('requireAdmin')) {
    function requireAdmin() {
        requireLogin();
        if (!isAdmin()) {
            if (function_exists('logAction')) {
                logAction('attempt_access_admin_without_permission', $_SESSION['user_id'] ?? null);
            }
            header("Location: index.php");
            exit();
        }
    }
}

if (!function_exists('requireDispatcher')) {
    function requireDispatcher() {
        requireLogin();
        if (!isAdmin() && !isDispatcher()) {
            if (function_exists('logAction')) {
                logAction('attempt_access_dispatcher_without_permission', $_SESSION['user_id'] ?? null);
            }
            header("Location: index.php");
            exit();
        }
    }
}

if (!function_exists('requireManager')) {
    function requireManager() {
        requireLogin();
        if (!isAdmin() && !isManager()) {
            if (function_exists('logAction')) {
                logAction('attempt_access_manager_without_permission', $_SESSION['user_id'] ?? null);
            }
            header("Location: index.php");
            exit();
        }
    }
}

if (!function_exists('getRoleName')) {
    function getRoleName($role) {
        $roles = [
            'admin' => 'Администратор',
            'dispatcher' => 'Диспетчер',
            'manager' => 'Менеджер',
            'driver' => 'Водитель'
        ];
        return $roles[$role] ?? $role;
    }
}

if (!function_exists('hasAccess')) {
    function hasAccess($module) {
        $userRole = $_SESSION['user_role'] ?? '';

        $accessMatrix = [
            'admin' => ['applications', 'drivers', 'vehicles', 'companies', 'analytics', 'settings', 'reports'],
            'dispatcher' => ['applications', 'drivers', 'vehicles', 'companies', 'reports'],
            'manager' => ['applications', 'drivers', 'vehicles', 'reports'],
            'driver' => ['applications']
        ];

        return in_array($module, $accessMatrix[$userRole] ?? []);
    }
}
