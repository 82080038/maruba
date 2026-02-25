<?php
namespace App\Helpers;

class AuthHelper
{
    public static function can(string $module, string $action): bool
    {
        $role = user_role();
        if (!$role) return false;
        $pdo = \App\Database::getConnection();
        $stmt = $pdo->prepare('SELECT permissions FROM roles WHERE name = ?');
        $stmt->execute([$role]);
        $row = $stmt->fetch();
        if (!$row || empty($row['permissions'])) return false;
        $perms = json_decode($row['permissions'], true);
        return isset($perms[$module]) && in_array($action, $perms[$module]);
    }

    public static function requirePermission(string $module, string $action): void
    {
        if (!self::can($module, $action)) {
            http_response_code(403);
            echo 'Akses ditolak.';
        }
    }

    public static function getPermissions(): array
    {
        $role = user_role();
        if (!$role) return [];
        $pdo = \App\Database::getConnection();
        $stmt = $pdo->prepare('SELECT permissions FROM roles WHERE name = ?');
        $stmt->execute([$role]);
        $row = $stmt->fetch();
        if (!$row || empty($row['permissions'])) return [];
        return json_decode($row['permissions'], true) ?: [];
    }
}
