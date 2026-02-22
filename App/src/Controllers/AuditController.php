<?php
namespace App\Controllers;
use App\Helpers\AuthHelper;

class AuditController
{
    public function index(): void
    {
        require_login();
        AuthHelper::requirePermission('audit_logs', 'view');
        $title = 'Audit Log';
        $pdo = \App\Database::getConnection();

        // Get current tenant ID for filtering
        $tenantId = $this->getCurrentTenantId();

        if ($tenantId === null) {
            // System admin - can see all tenant audit logs
            $stmt = $pdo->prepare('
                SELECT a.*, u.name AS user_name, t.name as tenant_name
                FROM audit_logs a
                LEFT JOIN users u ON a.user_id = u.id
                LEFT JOIN tenants t ON a.tenant_id = t.id
                ORDER BY a.created_at DESC
                LIMIT 200
            ');
            $stmt->execute();
        } else {
            // Tenant user - only see their tenant audit logs
            $stmt = $pdo->prepare('
                SELECT a.*, u.name AS user_name
                FROM audit_logs a
                LEFT JOIN users u ON a.user_id = u.id
                WHERE a.tenant_id = ?
                ORDER BY a.created_at DESC
                LIMIT 200
            ');
            $stmt->execute([$tenantId]);
        }

        $logs = $stmt->fetchAll();
        include view_path('audit/index');
    }

    /**
     * Get current tenant ID for data isolation
     */
    private function getCurrentTenantId(): ?int
    {
        // Check if user is system admin (tenant_id = NULL)
        $currentUser = current_user();
        if (!$currentUser) {
            return null;
        }

        // Get user details including tenant_id
        $pdo = \App\Database::getConnection();
        $stmt = $pdo->prepare('SELECT tenant_id FROM users WHERE id = ?');
        $stmt->execute([$currentUser['id']]);
        $user = $stmt->fetch();

        return $user ? $user['tenant_id'] : null;
    }
}
