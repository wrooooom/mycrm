<?php
/**
 * Notifications helpers
 */

if (!function_exists('sendNotification')) {
    function sendNotification($pdo, $userId, $type, $message, $isRead = 0) {
        try {
            $stmt = $pdo->prepare("INSERT INTO notifications (user_id, type, message, is_read) VALUES (?, ?, ?, ?)");
            $stmt->execute([$userId, $type, $message, $isRead ? 1 : 0]);
            return true;
        } catch (Exception $e) {
            error_log('sendNotification error: ' . $e->getMessage());
            return false;
        }
    }
}

if (!function_exists('notifyDriverAssignment')) {
    function notifyDriverAssignment($pdo, $driverId, $applicationNumber) {
        try {
            $stmt = $pdo->prepare('SELECT user_id, first_name, last_name FROM drivers WHERE id = ?');
            $stmt->execute([$driverId]);
            $driver = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$driver || empty($driver['user_id'])) {
                return false;
            }

            $name = trim(($driver['first_name'] ?? '') . ' ' . ($driver['last_name'] ?? ''));
            $message = "{$name}, вам назначен заказ #{$applicationNumber}";

            return sendNotification($pdo, intval($driver['user_id']), 'assignment', $message);
        } catch (Exception $e) {
            error_log('notifyDriverAssignment error: ' . $e->getMessage());
            return false;
        }
    }
}

if (!function_exists('notifyDispatchersStatusChange')) {
    function notifyDispatchersStatusChange($pdo, $applicationNumber, $oldStatus, $newStatus, $companyId = null) {
        try {
            $roles = ['admin', 'dispatcher', 'manager'];
            $placeholders = implode(',', array_fill(0, count($roles), '?'));

            $sql = "SELECT id FROM users WHERE role IN ($placeholders)";
            $params = $roles;

            if ($companyId !== null) {
                $sql .= ' AND (company_id = ? OR company_id IS NULL)';
                $params[] = $companyId;
            }

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($users as $u) {
                sendNotification(
                    $pdo,
                    intval($u['id']),
                    'status_change',
                    "Статус заказа #{$applicationNumber} изменен: {$oldStatus} → {$newStatus}"
                );
            }

            return true;
        } catch (Exception $e) {
            error_log('notifyDispatchersStatusChange error: ' . $e->getMessage());
            return false;
        }
    }
}
