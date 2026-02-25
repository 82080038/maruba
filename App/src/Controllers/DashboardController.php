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

        // Get user role for role-specific dashboard
        $userRole = user_role();
        
        // Route to role-specific dashboard
        switch ($userRole) {
            case 'kasir':
                $this->kasirDashboard();
                break;
            case 'teller':
                $this->tellerDashboard();
                break;
            case 'surveyor':
                $this->surveyorDashboard();
                break;
            case 'collector':
                $this->collectorDashboard();
                break;
            case 'manajer':
                $this->manajerDashboard();
                break;
            case 'akuntansi':
                $this->akuntansiDashboard();
                break;
            case 'creator':
                $this->creatorDashboard();
                break;
            case 'admin':
            default:
                if ($isTenant) {
                    $this->tenantDashboard();
                } else {
                    $this->adminDashboard();
                }
                break;
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
        $tenantId = $tenantInfo['id'] ?? null;

        if (!$tenantId) {
            // Fallback to admin dashboard if no tenant context
            $this->adminDashboard();
            return;
        }

        // Tenant-specific metrics with tenant filtering
        $stmt = $pdo->prepare("SELECT COALESCE(SUM(amount),0) AS total FROM loans WHERE tenant_id = ? AND status IN ('approved','disbursed')");
        $stmt->execute([$tenantId]);
        $outstanding = (float)$stmt->fetch()['total'];

        // Active members for this tenant
        $stmt = $pdo->prepare("SELECT COUNT(*) AS cnt FROM members WHERE tenant_id = ? AND status='active'");
        $stmt->execute([$tenantId]);
        $activeMembers = (int)$stmt->fetch()['cnt'];

        // Running loans for this tenant
        $stmt = $pdo->prepare("SELECT COUNT(*) AS cnt FROM loans WHERE tenant_id = ? AND status IN ('draft','survey','review','approved','disbursed')");
        $stmt->execute([$tenantId]);
        $runningLoans = (int)$stmt->fetch()['cnt'];

        // NPL calculation for this tenant
        $stmt = $pdo->prepare("SELECT SUM(status='default') AS npl, COUNT(*) AS total FROM loans WHERE tenant_id = ?");
        $stmt->execute([$tenantId]);
        $row = $stmt->fetch();
        $nplPct = ($row['total'] ?? 0) > 0 ? round(($row['npl'] / $row['total']) * 100, 1) : 0;

        $metrics = [
            ['label' => 'Outstanding', 'value' => $outstanding, 'type' => 'currency'],
            ['label' => 'Anggota Aktif', 'value' => $activeMembers, 'type' => 'number'],
            ['label' => 'Pinjaman Berjalan', 'value' => $runningLoans, 'type' => 'number'],
            ['label' => 'NPL', 'value' => $nplPct, 'type' => 'percent'],
        ];

        // Tenant-specific audit logs
        $stmt = $pdo->prepare("SELECT a.action, a.entity, a.entity_id, a.created_at, u.name AS user_name FROM audit_logs a LEFT JOIN users u ON a.user_id = u.id WHERE a.tenant_id = ? ORDER BY a.created_at DESC LIMIT 5");
        $stmt->execute([$tenantId]);
        $activities = $stmt->fetchAll();

        // Server-rendered tenant dashboard view
        include view_path('dashboard/tenant');
    }

    private function kasirDashboard(): void
    {
        $title = 'Dashboard Kasir';
        $pdo = Database::getConnection();

        // Get tenant context
        $tenantInfo = \App\Middleware\TenantMiddleware::getTenantInfo();
        $tenantId = $tenantInfo['id'] ?? null;
        $tenantFilter = $tenantId ? "WHERE tenant_id = ?" : "";
        $tenantParam = $tenantId ? [$tenantId] : [];

        // Cash flow hari ini
        $stmt = $pdo->prepare("SELECT COALESCE(SUM(amount),0) AS total FROM repayments WHERE DATE(paid_date) = CURDATE() $tenantFilter");
        $stmt->execute($tenantParam);
        $cashToday = (float)$stmt->fetch()['total'];

        // Transaksi pending
        $stmt = $pdo->prepare("SELECT COUNT(*) AS cnt FROM repayments WHERE status = 'due' $tenantFilter");
        $stmt->execute($tenantParam);
        $pendingTransactions = (int)$stmt->fetch()['cnt'];

        // Payment gateway status (simulated)
        $paymentGatewayStatus = 'Online'; // Could be from config

        // Reconciliation summary
        $stmt = $pdo->prepare("SELECT COUNT(*) AS cnt FROM repayments WHERE DATE(paid_date) = CURDATE() AND status = 'paid' $tenantFilter");
        $stmt->execute($tenantParam);
        $reconciledToday = (int)$stmt->fetch()['cnt'];

        $metrics = [
            ['label' => 'Cash Flow Hari Ini', 'value' => $cashToday, 'type' => 'currency'],
            ['label' => 'Transaksi Pending', 'value' => $pendingTransactions, 'type' => 'number'],
            ['label' => 'Payment Gateway', 'value' => $paymentGatewayStatus, 'type' => 'status'],
            ['label' => 'Reconciled Today', 'value' => $reconciledToday, 'type' => 'number'],
        ];

        // Recent transactions
        $stmt = $pdo->prepare("SELECT r.*, m.name as member_name, l.amount as loan_amount FROM repayments r LEFT JOIN members m ON r.member_id = m.id LEFT JOIN loans l ON r.loan_id = l.id WHERE r.paid_date IS NOT NULL $tenantFilter ORDER BY r.paid_date DESC LIMIT 5");
        $stmt->execute($tenantParam);
        $recentTransactions = $stmt->fetchAll();

        include view_path('dashboard/kasir');
    }

    private function tellerDashboard(): void
    {
        $title = 'Dashboard Teller';
        $pdo = Database::getConnection();

        // Get tenant context
        $tenantInfo = \App\Middleware\TenantMiddleware::getTenantInfo();
        $tenantId = $tenantInfo['id'] ?? null;
        $tenantFilter = $tenantId ? "WHERE tenant_id = ?" : "";
        $tenantParam = $tenantId ? [$tenantId] : [];

        // Tabungan balances
        $stmt = $pdo->prepare("SELECT COALESCE(SUM(balance),0) AS total FROM savings_accounts $tenantFilter");
        $stmt->execute($tenantParam);
        $totalSavings = (float)$stmt->fetch()['total'];

        // Member registrations today
        $stmt = $pdo->prepare("SELECT COUNT(*) AS cnt FROM members WHERE DATE(created_at) = CURDATE() $tenantFilter");
        $stmt->execute($tenantParam);
        $registrationsToday = (int)$stmt->fetch()['cnt'];

        // Deposit/withdrawal trends
        $stmt = $pdo->prepare("SELECT COALESCE(SUM(CASE WHEN type = 'deposit' THEN amount ELSE 0 END),0) AS deposits, COALESCE(SUM(CASE WHEN type = 'withdraw' THEN amount ELSE 0 END),0) AS withdrawals FROM savings_transactions WHERE DATE(created_at) = CURDATE() $tenantFilter");
        $stmt->execute($tenantParam);
        $trends = $stmt->fetch();
        $depositsToday = (float)$trends['deposits'];
        $withdrawalsToday = (float)$trends['withdrawals'];

        // Service queue status (simulated)
        $queueStatus = '3 customers waiting';

        $metrics = [
            ['label' => 'Total Tabungan', 'value' => $totalSavings, 'type' => 'currency'],
            ['label' => 'Registrasi Hari Ini', 'value' => $registrationsToday, 'type' => 'number'],
            ['label' => 'Deposit Hari Ini', 'value' => $depositsToday, 'type' => 'currency'],
            ['label' => 'Queue Status', 'value' => $queueStatus, 'type' => 'status'],
        ];

        // Recent members
        $stmt = $pdo->prepare("SELECT * FROM members $tenantFilter ORDER BY created_at DESC LIMIT 5");
        $stmt->execute($tenantParam);
        $recentMembers = $stmt->fetchAll();

        include view_path('dashboard/teller');
    }

    private function surveyorDashboard(): void
    {
        $title = 'Dashboard Surveyor';
        $pdo = Database::getConnection();

        // Get tenant context
        $tenantInfo = \App\Middleware\TenantMiddleware::getTenantInfo();
        $tenantId = $tenantInfo['id'] ?? null;
        $tenantFilter = $tenantId ? "WHERE tenant_id = ?" : "";
        $tenantParam = $tenantId ? [$tenantId] : [];

        // Survey assignments
        $stmt = $pdo->prepare("SELECT COUNT(*) AS cnt FROM surveys WHERE surveyor_id = ? AND result IS NULL");
        $currentUser = current_user();
        $stmt->execute([$currentUser['id']]);
        $pendingSurveys = (int)$stmt->fetch()['cnt'];

        // Completion rates
        $stmt = $pdo->prepare("SELECT COUNT(*) AS total, SUM(CASE WHEN result IS NOT NULL THEN 1 ELSE 0 END) AS completed FROM surveys WHERE surveyor_id = ?");
        $stmt->execute([$currentUser['id']]);
        $surveyStats = $stmt->fetch();
        $completionRate = $surveyStats['total'] > 0 ? round(($surveyStats['completed'] / $surveyStats['total']) * 100, 1) : 0;

        // Geographic coverage (simulated)
        $coverage = '5 of 8 areas covered';

        // Scoring summary
        $stmt = $pdo->prepare("SELECT AVG(score) as avg_score FROM surveys WHERE surveyor_id = ? AND result IS NOT NULL");
        $stmt->execute([$currentUser['id']]);
        $avgScore = round((float)$stmt->fetch()['avg_score'], 1);

        $metrics = [
            ['label' => 'Survei Pending', 'value' => $pendingSurveys, 'type' => 'number'],
            ['label' => 'Completion Rate', 'value' => $completionRate, 'type' => 'percent'],
            ['label' => 'Coverage', 'value' => $coverage, 'type' => 'status'],
            ['label' => 'Avg Score', 'value' => $avgScore, 'type' => 'number'],
        ];

        // Recent surveys
        $stmt = $pdo->prepare("SELECT s.*, m.name as member_name FROM surveys s LEFT JOIN loans l ON s.loan_id = l.id LEFT JOIN members m ON l.member_id = m.id WHERE s.surveyor_id = ? ORDER BY s.created_at DESC LIMIT 5");
        $stmt->execute([$currentUser['id']]);
        $recentSurveys = $stmt->fetchAll();

        include view_path('dashboard/surveyor');
    }

    private function collectorDashboard(): void
    {
        $title = 'Dashboard Collector';
        $pdo = Database::getConnection();

        // Get tenant context
        $tenantInfo = \App\Middleware\TenantMiddleware::getTenantInfo();
        $tenantId = $tenantInfo['id'] ?? null;
        $tenantFilter = $tenantId ? "WHERE tenant_id = ?" : "";
        $tenantParam = $tenantId ? [$tenantId] : [];

        // Collection targets
        $stmt = $pdo->prepare("SELECT COALESCE(SUM(amount_due),0) AS target FROM repayments WHERE status = 'due' AND due_date <= CURDATE() $tenantFilter");
        $stmt->execute($tenantParam);
        $collectionTarget = (float)$stmt->fetch()['target'];

        // Overdue accounts
        $stmt = $pdo->prepare("SELECT COUNT(*) AS cnt FROM repayments WHERE status = 'due' AND due_date < CURDATE() $tenantFilter");
        $stmt->execute($tenantParam);
        $overdueAccounts = (int)$stmt->fetch()['cnt'];

        // Payment success rates
        $stmt = $pdo->prepare("SELECT COUNT(*) AS total, SUM(CASE WHEN status = 'paid' THEN 1 ELSE 0 END) AS paid FROM repayments WHERE due_date <= CURDATE() $tenantFilter");
        $stmt->execute($tenantParam);
        $paymentStats = $stmt->fetch();
        $successRate = $paymentStats['total'] > 0 ? round(($paymentStats['paid'] / $paymentStats['total']) * 100, 1) : 0;

        // Route optimization (simulated)
        $routeStatus = '12 collections scheduled today';

        $metrics = [
            ['label' => 'Collection Target', 'value' => $collectionTarget, 'type' => 'currency'],
            ['label' => 'Overdue Accounts', 'value' => $overdueAccounts, 'type' => 'number'],
            ['label' => 'Success Rate', 'value' => $successRate, 'type' => 'percent'],
            ['label' => 'Route Status', 'value' => $routeStatus, 'type' => 'status'],
        ];

        // Collection list
        $stmt = $pdo->prepare("SELECT r.*, m.name as member_name, m.phone FROM repayments r LEFT JOIN loans l ON r.loan_id = l.id LEFT JOIN members m ON l.member_id = m.id WHERE r.status = 'due' AND r.due_date <= CURDATE() $tenantFilter ORDER BY r.due_date ASC LIMIT 5");
        $stmt->execute($tenantParam);
        $collectionList = $stmt->fetchAll();

        include view_path('dashboard/collector');
    }

    private function manajerDashboard(): void
    {
        $title = 'Dashboard Manajer';
        $pdo = Database::getConnection();

        // Get tenant context
        $tenantInfo = \App\Middleware\TenantMiddleware::getTenantInfo();
        $tenantId = $tenantInfo['id'] ?? null;
        $tenantFilter = $tenantId ? "WHERE tenant_id = ?" : "";
        $tenantParam = $tenantId ? [$tenantId] : [];

        // Portfolio performance
        $stmt = $pdo->prepare("SELECT COALESCE(SUM(amount),0) AS total, COALESCE(SUM(CASE WHEN status = 'default' THEN amount ELSE 0 END),0) AS bad FROM loans $tenantFilter");
        $stmt->execute($tenantParam);
        $portfolio = $stmt->fetch();
        $portfolioTotal = (float)$portfolio['total'];
        $portfolioBad = (float)$portfolio['bad'];
        $portfolioHealth = $portfolioTotal > 0 ? round((($portfolioTotal - $portfolioBad) / $portfolioTotal) * 100, 1) : 100;

        // Risk assessment
        $stmt = $pdo->prepare("SELECT COUNT(*) AS cnt FROM loans WHERE status = 'survey' $tenantFilter");
        $stmt->execute($tenantParam);
        $riskAssessments = (int)$stmt->fetch()['cnt'];

        // Approval queues
        $stmt = $pdo->prepare("SELECT COUNT(*) AS cnt FROM loans WHERE status IN ('review','approved') $tenantFilter");
        $stmt->execute($tenantParam);
        $approvalQueue = (int)$stmt->fetch()['cnt'];

        // Team productivity
        $stmt = $pdo->prepare("SELECT COUNT(*) AS cnt FROM users WHERE status = 'active' $tenantFilter");
        $stmt->execute($tenantParam);
        $teamSize = (int)$stmt->fetch()['cnt'];

        $metrics = [
            ['label' => 'Portfolio Health', 'value' => $portfolioHealth, 'type' => 'percent'],
            ['label' => 'Risk Assessments', 'value' => $riskAssessments, 'type' => 'number'],
            ['label' => 'Approval Queue', 'value' => $approvalQueue, 'type' => 'number'],
            ['label' => 'Team Size', 'value' => $teamSize, 'type' => 'number'],
        ];

        // Recent activities
        $stmt = $pdo->prepare("SELECT a.action, a.entity, a.entity_id, a.created_at, u.name AS user_name FROM audit_logs a LEFT JOIN users u ON a.user_id = u.id $tenantFilter ORDER BY a.created_at DESC LIMIT 5");
        $stmt->execute($tenantParam);
        $activities = $stmt->fetchAll();

        include view_path('dashboard/manajer');
    }

    private function akuntansiDashboard(): void
    {
        $title = 'Dashboard Akuntansi';
        $pdo = Database::getConnection();

        // Get tenant context
        $tenantInfo = \App\Middleware\TenantMiddleware::getTenantInfo();
        $tenantId = $tenantInfo['id'] ?? null;
        $tenantFilter = $tenantId ? "WHERE tenant_id = ?" : "";
        $tenantParam = $tenantId ? [$tenantId] : [];

        // Journal entries pending
        $stmt = $pdo->prepare("SELECT COUNT(*) AS cnt FROM journal_entries WHERE status = 'pending' $tenantFilter");
        $stmt->execute($tenantParam);
        $pendingEntries = (int)$stmt->fetch()['cnt'];

        // Trial balance status
        $stmt = $pdo->prepare("SELECT COUNT(*) AS cnt FROM chart_of_accounts $tenantFilter");
        $stmt->execute($tenantParam);
        $accountCount = (int)$stmt->fetch()['cnt'];

        // Tax compliance (simulated)
        $taxStatus = 'Compliant';

        // Audit findings
        $stmt = $pdo->prepare("SELECT COUNT(*) AS cnt FROM audit_logs WHERE action LIKE '%error%' OR action LIKE '%violation%' $tenantFilter");
        $stmt->execute($tenantParam);
        $auditFindings = (int)$stmt->fetch()['cnt'];

        $metrics = [
            ['label' => 'Pending Entries', 'value' => $pendingEntries, 'type' => 'number'],
            ['label' => 'Chart Accounts', 'value' => $accountCount, 'type' => 'number'],
            ['label' => 'Tax Status', 'value' => $taxStatus, 'type' => 'status'],
            ['label' => 'Audit Findings', 'value' => $auditFindings, 'type' => 'number'],
        ];

        // Recent journal entries
        $stmt = $pdo->prepare("SELECT * FROM journal_entries $tenantFilter ORDER BY created_at DESC LIMIT 5");
        $stmt->execute($tenantParam);
        $recentEntries = $stmt->fetchAll();

        include view_path('dashboard/akuntansi');
    }

    private function creatorDashboard(): void
    {
        $title = 'Dashboard Creator';
        $pdo = Database::getConnection();

        // System health
        $systemHealth = 'All Systems Operational';

        // Tenant statistics
        $stmt = $pdo->prepare("SELECT COUNT(*) AS cnt FROM tenants WHERE status = 'active'");
        $stmt->execute();
        $activeTenants = (int)$stmt->fetch()['cnt'];

        // Performance metrics
        $stmt = $pdo->prepare("SELECT COUNT(*) AS cnt FROM users WHERE status = 'active'");
        $stmt->execute();
        $activeUsers = (int)$stmt->fetch()['cnt'];

        // Security alerts
        $stmt = $pdo->prepare("SELECT COUNT(*) AS cnt FROM audit_logs WHERE action LIKE '%failed%' AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)");
        $stmt->execute();
        $securityAlerts = (int)$stmt->fetch()['cnt'];

        $metrics = [
            ['label' => 'System Health', 'value' => $systemHealth, 'type' => 'status'],
            ['label' => 'Active Tenants', 'value' => $activeTenants, 'type' => 'number'],
            ['label' => 'Active Users', 'value' => $activeUsers, 'type' => 'number'],
            ['label' => 'Security Alerts', 'value' => $securityAlerts, 'type' => 'number'],
        ];

        // System logs
        $stmt = $pdo->prepare("SELECT * FROM audit_logs ORDER BY created_at DESC LIMIT 5");
        $stmt->execute();
        $systemLogs = $stmt->fetchAll();

        include view_path('dashboard/creator');
    }
}
