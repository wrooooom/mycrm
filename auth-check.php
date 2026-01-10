<?php
/**
 * Проверка авторизации пользователя
 */

session_start();

// Если пользователь не авторизован - редирект на страницу входа
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Функция для проверки прав доступа
function checkPermission($required_role) {
    $user_role = $_SESSION['role'] ?? 'dispatcher';
    $roles_hierarchy = [
        'dispatcher' => 1,
        'manager' => 2,
        'admin' => 3
    ];
    
    return ($roles_hierarchy[$user_role] >= $roles_hierarchy[$required_role]);
}

// Функция для получения информации о текущем пользователе
function getCurrentUser() {
    return [
        'id' => $_SESSION['user_id'],
        'username' => $_SESSION['username'],
        'full_name' => $_SESSION['full_name'],
        'role' => $_SESSION['role'],
        'email' => $_SESSION['email']
    ];
}

// Функция для проверки является ли пользователь администратором
function isAdmin() {
    return ($_SESSION['role'] === 'admin');
}

// Функция для проверки является ли пользователь менеджером или выше
function isManager() {
    return in_array($_SESSION['role'], ['manager', 'admin']);
}
?>