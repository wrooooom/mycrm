<?php
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
 * Защита страниц - требует авторизации
 */
function requireLogin() {
    if (!isLoggedIn()) {
        $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
        logAction('attempt_access_without_login');
        header("Location: /login.php");
        exit();
    }
}

/**
 * Логирование действий
 */
function logAction($action, $user_id = null) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("INSERT INTO activity_log (user_id, action, ip_address, user_agent) VALUES (?, ?, ?, ?)");
        $stmt->execute([
            $user_id ?? ($_SESSION['user_id'] ?? null),
            $action,
            $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
        ]);
    } catch(Exception $e) {
        // Игнорируем ошибки логирования
    }
}

/**
 * Получение статуса заявки в текстовом формате
 */
function getStatusText($status) {
    $statuses = [
        'new' => 'Новая',
        'assigned' => 'Назначена',
        'in_progress' => 'В работе',
        'completed' => 'Завершена',
        'cancelled' => 'Отменена'
    ];
    return $statuses[$status] ?? 'Неизвестно';
}

/**
 * Получение цвета для бейджа статуса
 */
function getStatusBadgeColor($status) {
    $colors = [
        'new' => 'success',
        'assigned' => 'warning',
        'in_progress' => 'primary',
        'completed' => 'secondary',
        'cancelled' => 'danger'
    ];
    return $colors[$status] ?? 'dark';
}

/**
 * Получение последних действий для сайдбара
 */
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
?>