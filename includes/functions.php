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

/**
 * Отправка уведомления пользователю
 */
function sendNotification($pdo, $userId, $type, $title, $message, $relatedType = null, $relatedId = null) {
    try {
        $stmt = $pdo->prepare("
            INSERT INTO notifications (user_id, type, title, message, related_type, related_id, is_read) 
            VALUES (?, ?, ?, ?, ?, ?, 0)
        ");
        $stmt->execute([$userId, $type, $title, $message, $relatedType, $relatedId]);
        return true;
    } catch(Exception $e) {
        error_log("Notification error: " . $e->getMessage());
        return false;
    }
}

/**
 * Отправка уведомления водителю при назначении заказа
 */
function notifyDriverAssignment($pdo, $driverId, $applicationNumber) {
    try {
        // Получаем user_id водителя
        $stmt = $pdo->prepare("SELECT user_id FROM drivers WHERE id = ?");
        $stmt->execute([$driverId]);
        $driver = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($driver && $driver['user_id']) {
            return sendNotification(
                $pdo,
                $driver['user_id'],
                'application_assigned',
                'Новый заказ назначен',
                "Вам назначен заказ #{$applicationNumber}",
                'application',
                $driverId
            );
        }
        return false;
    } catch(Exception $e) {
        error_log("Driver notification error: " . $e->getMessage());
        return false;
    }
}

/**
 * Отправка уведомления диспетчерам при изменении статуса
 */
function notifyStatusChange($pdo, $applicationId, $oldStatus, $newStatus) {
    try {
        // Получаем всех диспетчеров и менеджеров
        $stmt = $pdo->prepare("SELECT id FROM users WHERE role IN ('admin', 'dispatcher', 'manager')");
        $stmt->execute();
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($users as $user) {
            sendNotification(
                $pdo,
                $user['id'],
                'info',
                'Изменение статуса заказа',
                "Статус заказа #{$applicationId} изменен с '{$oldStatus}' на '{$newStatus}'",
                'application',
                $applicationId
            );
        }
        return true;
    } catch(Exception $e) {
        error_log("Status notification error: " . $e->getMessage());
        return false;
    }
}
?>
