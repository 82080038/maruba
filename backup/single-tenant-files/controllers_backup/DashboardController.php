<?php
namespace App\Controllers;
use App\Helpers\AuthHelper;
use App\Database;

class DashboardController
{
    public function index(): void
    {
        require_login();
        AuthHelper::requirePermission('dashboard', 'view');

        // Check if we're in tenant context
        $isTenant = \App\Middleware\TenantMiddleware::hasTenant();

        if ($isTenant) {
            // Load tenant dashboard
            $this->tenantDashboard();
        } else {
            // Load main application dashboard
            $this->adminDashboard();
        }
    }

    private function adminDashboard(): void
    {
        $title = 'Dashboard Admin';
        $pdo = Database::getConnection();

        // Outstanding: jumlah pinjaman berstatus approved/disbursed
        $stmt = $pdo->prepare("SELECT COALESCE(SUM(amount),0) AS total FROM loans WHERE status IN ('approved','disbursed')");
        $stmt->execute();
        $outstanding = (float)$stmt->fetch()['total'];

        // Anggota aktif
        $stmt = $pdo->prepare("SELECT COUNT(*) AS cnt FROM members WHERE status='active'");
        $stmt->execute();
        $activeMembers = (int)$stmt->fetch()['cnt'];

        // Pinjaman berjalan (draft/survey/review/approved/disbursed)
        $stmt = $pdo->prepare("SELECT COUNT(*) AS cnt FROM loans WHERE status IN ('draft','survey','review','approved','disbursed')");
        $stmt->execute();
        $runningLoans = (int)$stmt->fetch()['cnt'];

        // NPL sederhana: jumlah status default / total loans
        $stmt = $pdo->prepare("SELECT SUM(status='default') AS npl, COUNT(*) AS total FROM loans");
        $stmt->execute();
        $row = $stmt->fetch();
        $nplPct = ($row['total'] ?? 0) > 0 ? round(($row['npl'] / $row['total']) * 100, 1) : 0;

        $metrics = [
            ['label' => 'Outstanding', 'value' => $outstanding, 'type' => 'currency'],
            ['label' => 'Anggota Aktif', 'value' => $activeMembers, 'type' => 'number'],
            ['label' => 'Pinjaman Berjalan', 'value' => $runningLoans, 'type' => 'number'],
            ['label' => 'NPL', 'value' => $nplPct, 'type' => 'percent'],
        ];

        // Aktivitas terbaru (audit log)
        $stmt = $pdo->prepare("SELECT a.action, a.entity, a.entity_id, a.created_at, u.name AS user_name FROM audit_logs a LEFT JOIN users u ON a.user_id = u.id ORDER BY a.created_at DESC LIMIT 5");
        $stmt->execute();
        $activities = $stmt->fetchAll();

        // Server-rendered dashboard view
        include view_path('dashboard/index');
    }

    private function tenantDashboard(): void
    {
        $title = 'Dashboard Tenant';
        $pdo = Database::getConnection();

        // Get tenant info
        $tenantInfo = \App\Middleware\TenantMiddleware::getTenantInfo();

        // Aktivitas terbaru dari tenant database (audit log)
        $stmt = $pdo->prepare("SELECT a.action, a.entity, a.entity_id, a.created_at, u.name AS user_name FROM audit_logs a LEFT JOIN users u ON a.user_id = u.id ORDER BY a.created_at DESC LIMIT 5");
        $stmt->execute();
        $activities = $stmt->fetchAll();

        // Server-rendered tenant dashboard view
        include view_path('dashboard/tenant');
    }
}
