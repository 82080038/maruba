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
        $stmt = $pdo->prepare('
            SELECT a.*, u.name AS user_name
            FROM audit_logs a
            LEFT JOIN users u ON a.user_id = u.id
            ORDER BY a.created_at DESC
            LIMIT 200
        ');
        $stmt->execute();
        $logs = $stmt->fetchAll();
        include view_path('audit/index');
    }
}
