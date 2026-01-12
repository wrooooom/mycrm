<?php
/**
 * Shared helpers (used by templates/*)
 *
 * Note: auth.php also defines some of these helpers.
 * To avoid "Cannot redeclare" fatals when both files are included,
 * all functions are guarded with function_exists().
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

if (!function_exists('isClient')) {
    function isClient() {
        return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'client';
    }
}

if (!function_exists('isManager')) {
    function isManager() {
        return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'manager';
    }
}

if (!function_exists('requireLogin')) {
    function requireLogin() {
        if (!isLoggedIn()) {
            $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
            if (function_exists('logAction')) {
                logAction('attempt_access_without_login');
            }
            header("Location: /login.php");
            exit();
        }
    }
}

if (!function_exists('logAction')) {
    function logAction($action, $user_id = null, $description = null) {
        global $pdo;
        try {
            $stmt = $pdo->prepare("INSERT INTO activity_log (user_id, action, ip_address, user_agent) VALUES (?, ?, ?, ?)");
            $stmt->execute([
                $user_id ?? ($_SESSION['user_id'] ?? null),
                $description ?? $action,
                $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
            ]);
        } catch (Exception $e) {
            // ignore
        }
    }
}

if (!function_exists('getStatusText')) {
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
}

if (!function_exists('getStatusBadgeColor')) {
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
}

if (!function_exists('getRecentActivity')) {
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
        } catch (Exception $e) {
            return [];
        }
    }
}

require_once __DIR__ . '/notifications.php';
