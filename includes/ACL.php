<?php
/**
 * ACL (Access Control List) Class
 * Manages role-based access control for the CRM system
 */
class ACL {
    /**
     * Check if user can view applications
     */
    public static function canViewApplications($role) {
        return in_array($role, ['admin', 'manager', 'dispatcher', 'driver', 'client']);
    }

    /**
     * Check if user can create applications
     */
    public static function canCreateApplication($role) {
        return in_array($role, ['admin', 'manager', 'dispatcher']);
    }

    /**
     * Check if user can edit application
     */
    public static function canEditApplication($role, $application_status = null) {
        // Admin and manager can edit
        if (in_array($role, ['admin', 'manager', 'dispatcher'])) {
            // If application is already completed, no edits allowed
            if ($application_status === 'completed') {
                return false;
            }
            return true;
        }

        // Driver can only change status
        if ($role === 'driver' && $application_status !== 'completed') {
            return true; // Limited to status changes
        }

        return false;
    }

    /**
     * Check if user can delete application
     */
    public static function canDeleteApplication($role, $application_status = null) {
        // Only admin and manager can delete
        if (!in_array($role, ['admin', 'manager'])) {
            return false;
        }

        // Cannot delete completed applications
        if ($application_status === 'completed') {
            return false;
        }

        return true;
    }

    /**
     * Check if user can assign driver
     */
    public static function canAssignDriver($role) {
        return in_array($role, ['admin', 'manager', 'dispatcher']);
    }

    /**
     * Check if user can assign vehicle
     */
    public static function canAssignVehicle($role) {
        return in_array($role, ['admin', 'manager', 'dispatcher']);
    }

    /**
     * Check if user can update application status
     */
    public static function canUpdateStatus($role, $current_status, $new_status) {
        // Admin and manager can always update status
        if (in_array($role, ['admin', 'manager', 'dispatcher'])) {
            return true;
        }

        // Driver can only move forward in workflow
        if ($role === 'driver') {
            $statusFlow = [
                'new' => ['confirmed', 'cancelled'],
                'confirmed' => ['inwork', 'cancelled'],
                'inwork' => ['completed', 'cancelled']
            ];

            return isset($statusFlow[$current_status]) && in_array($new_status, $statusFlow[$current_status]);
        }

        return false;
    }

    /**
     * Check if user can view financial data
     */
    public static function canViewFinancialData($role) {
        return in_array($role, ['admin']);
    }

    /**
     * Check if user can view all applications
     */
    public static function canViewAllApplications($role) {
        return in_array($role, ['admin', 'manager', 'dispatcher']);
    }

    /**
     * Check if user can view all comments
     */
    public static function canViewAllComments($role) {
        return in_array($role, ['admin', 'manager']);
    }

    /**
     * Check if user can view internal comments
     */
    public static function canViewInternalComments($role) {
        return in_array($role, ['admin', 'manager']);
    }

    /**
     * Check if user can view manager comments
     */
    public static function canViewManagerComments($role) {
        return in_array($role, ['admin', 'manager', 'driver']);
    }

    /**
     * Check if user can add comments
     */
    public static function canAddComment($role) {
        return in_array($role, ['admin', 'manager', 'driver']);
    }

    /**
     * Check if user can view drivers
     */
    public static function canViewDrivers($role) {
        return in_array($role, ['admin', 'manager', 'dispatcher']);
    }

    /**
     * Check if user can edit drivers
     */
    public static function canEditDrivers($role) {
        return in_array($role, ['admin', 'manager', 'dispatcher']);
    }

    /**
     * Check if user can view vehicles
     */
    public static function canViewVehicles($role) {
        return in_array($role, ['admin', 'manager', 'dispatcher']);
    }

    /**
     * Check if user can edit vehicles
     */
    public static function canEditVehicles($role) {
        return in_array($role, ['admin', 'manager', 'dispatcher']);
    }

    /**
     * Check if user can view users
     */
    public static function canViewUsers($role) {
        return in_array($role, ['admin', 'manager', 'dispatcher']);
    }

    /**
     * Check if user can edit users
     */
    public static function canEditUsers($role) {
        return in_array($role, ['admin']);
    }

    /**
     * Check if user can change user roles
     */
    public static function canChangeUserRoles($role) {
        return $role === 'admin';
    }

    /**
     * Check if user can view companies
     */
    public static function canViewCompanies($role) {
        return in_array($role, ['admin', 'manager', 'dispatcher']);
    }

    /**
     * Check if user can edit companies
     */
    public static function canEditCompanies($role) {
        return in_array($role, ['admin', 'manager', 'dispatcher']);
    }

    /**
     * Check if user can view analytics
     */
    public static function canViewAnalytics($role) {
        return in_array($role, ['admin', 'manager']);
    }

    /**
     * Check if user can view financial analytics
     */
    public static function canViewFinancialAnalytics($role) {
        return $role === 'admin';
    }

    /**
     * Check if user can view activity log
     */
    public static function canViewActivityLog($role) {
        return in_array($role, ['admin', 'manager', 'dispatcher']);
    }

    /**
     * Get accessible applications for a user
     */
    public static function getAccessibleApplications($user_id, $role, $pdo) {
        if (in_array($role, ['admin', 'manager', 'dispatcher'])) {
            // Can view all applications
            return null; // No filter needed
        }

        if ($role === 'driver') {
            // Can only view applications assigned to them
            return "a.driver_id IN (SELECT id FROM drivers WHERE user_id = :user_id)";
        }

        if ($role === 'client') {
            // Can only view their own applications or applications for their company
            return "(a.created_by = :user_id OR a.customer_company_id IN (SELECT company_id FROM users WHERE id = :user_id))";
        }

        return "1=0"; // No access by default
    }

    /**
     * Send 403 Forbidden response if user doesn't have permission
     */
    public static function requirePermission($has_permission, $message = 'У вас нет прав для выполнения этого действия') {
        if (!$has_permission) {
            http_response_code(403);
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => $message
            ]);
            exit();
        }
    }

    /**
     * Get user role from session
     */
    public static function getUserRole() {
        return $_SESSION['user_role'] ?? 'guest';
    }

    /**
     * Get user ID from session
     */
    public static function getUserId() {
        return $_SESSION['user_id'] ?? null;
    }

    /**
     * Check if user is logged in
     */
    public static function isLoggedIn() {
        return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
    }
}
?>
