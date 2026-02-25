<?php
namespace App\Helpers;

class AuthHelper
{
    /**
     * Check if current user has permission for module/action
     */
    public static function can(string $module, string $action): bool
    {
        // Ensure user is logged in
        if (!function_exists('user_role')) {
            require_once __DIR__ . '/../bootstrap.php';
        }
        
        $role = user_role();
        if (!$role) return false;
        
        try {
            $pdo = \App\Database::getConnection();
            $stmt = $pdo->prepare('SELECT permissions FROM roles WHERE name = ?');
            $stmt->execute([$role]);
            $row = $stmt->fetch();
            
            if (!$row || empty($row['permissions'])) return false;
            
            $perms = json_decode($row['permissions'], true);
            return isset($perms[$module]) && in_array($action, $perms[$module]);
        } catch (\Exception $e) {
            error_log('AuthHelper::can() error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Require permission or deny access
     */
    public static function requirePermission(string $module, string $action): void
    {
        if (!self::can($module, $action)) {
            http_response_code(403);
            echo '<div class="alert alert-danger">Akses ditolak. Anda tidak memiliki izin untuk ' . htmlspecialchars($action) . ' pada ' . htmlspecialchars($module) . '.</div>';
            echo '<a href="' . (function_exists('route_url') ? route_url('dashboard') : '/maruba/index.php/dashboard') . '" class="btn btn-primary">Kembali ke Dashboard</a>';
            exit();
        }
    }

    /**
     * Get all permissions for current user
     */
    public static function getPermissions(): array
    {
        // Ensure user is logged in
        if (!function_exists('user_role')) {
            require_once __DIR__ . '/../bootstrap.php';
        }
        
        $role = user_role();
        if (!$role) return [];
        
        try {
            $pdo = \App\Database::getConnection();
            $stmt = $pdo->prepare('SELECT permissions FROM roles WHERE name = ?');
            $stmt->execute([$role]);
            $row = $stmt->fetch();
            
            if (!$row || empty($row['permissions'])) return [];
            
            return json_decode($row['permissions'], true) ?: [];
        } catch (\Exception $e) {
            error_log('AuthHelper::getPermissions() error: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Check if user has any of the specified roles
     */
    public static function hasRole(array $roles): bool
    {
        if (!function_exists('user_role')) {
            require_once __DIR__ . '/../bootstrap.php';
        }
        
        $userRole = user_role();
        return in_array($userRole, $roles);
    }
    
    /**
     * Check if user is admin (Admin or Creator role)
     */
    public static function isAdmin(): bool
    {
        return self::hasRole(['Admin', 'Creator']);
    }
    
    /**
     * Get current user info safely
     */
    public static function getCurrentUser(): ?array
    {
        if (!function_exists('current_user')) {
            require_once __DIR__ . '/../bootstrap.php';
        }
        
        return current_user();
    }
}
