<?php

class ACL {
    public const ROLE_ADMIN = 'admin';
    public const ROLE_MANAGER = 'manager';
    public const ROLE_DRIVER = 'driver';
    public const ROLE_CLIENT = 'client';

    /**
     * Можно ли просматривать список заказов
     */
    public static function canViewApplications($role) {
        return true;
    }

    /**
     * Можно ли создавать заказ
     */
    public static function canCreateApplication($role) {
        return in_array($role, [self::ROLE_ADMIN, self::ROLE_MANAGER]);
    }

    /**
     * Можно ли редактировать заказ
     */
    public static function canEditApplication($role, $status = null) {
        if (!in_array($role, [self::ROLE_ADMIN, self::ROLE_MANAGER])) {
            return false;
        }
        
        // Статус "Выполнена" - только чтение
        if ($status === 'completed') {
            return false;
        }
        
        return true;
    }

    /**
     * Можно ли изменять статус заказа
     */
    public static function canUpdateStatus($role, $application = null) {
        if (in_array($role, [self::ROLE_ADMIN, self::ROLE_MANAGER])) {
            return true;
        }
        
        if ($role === self::ROLE_DRIVER) {
            // Водитель может менять статус только СВОЕГО заказа
            return $application && isset($_SESSION['user_id']) && $application['driver_user_id'] == $_SESSION['user_id'];
        }
        
        return false;
    }

    /**
     * Можно ли назначать водителя
     */
    public static function canAssignDriver($role) {
        return in_array($role, [self::ROLE_ADMIN, self::ROLE_MANAGER]);
    }

    /**
     * Можно ли назначать машину
     */
    public static function canAssignVehicle($role) {
        return in_array($role, [self::ROLE_ADMIN, self::ROLE_MANAGER]);
    }

    /**
     * Можно ли видеть финансовую аналитику
     */
    public static function canViewFinancialData($role) {
        return $role === self::ROLE_ADMIN;
    }

    /**
     * Можно ли видеть комментарии менеджера
     */
    public static function canViewManagerComments($role) {
        return in_array($role, [self::ROLE_ADMIN, self::ROLE_MANAGER, self::ROLE_DRIVER]);
    }

    /**
     * Можно ли видеть внутренние комментарии
     */
    public static function canViewInternalComments($role) {
        return in_array($role, [self::ROLE_ADMIN, self::ROLE_MANAGER]);
    }

    /**
     * Можно ли изменять роли пользователей
     */
    public static function canChangeUserRoles($role) {
        return $role === self::ROLE_ADMIN;
    }

    /**
     * Можно ли видеть историю действий
     */
    public static function canViewActivityLog($role) {
        return in_array($role, [self::ROLE_ADMIN, self::ROLE_MANAGER]);
    }

    /**
     * Можно ли управлять пользователями
     */
    public static function canManageUsers($role) {
        return in_array($role, [self::ROLE_ADMIN, self::ROLE_MANAGER]);
    }

    /**
     * Можно ли управлять водителями
     */
    public static function canManageDrivers($role) {
        return in_array($role, [self::ROLE_ADMIN, self::ROLE_MANAGER]);
    }

    /**
     * Можно ли управлять автомобилями
     */
    public static function canManageVehicles($role) {
        return in_array($role, [self::ROLE_ADMIN, self::ROLE_MANAGER]);
    }
}
