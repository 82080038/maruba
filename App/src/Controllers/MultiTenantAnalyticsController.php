<?php
namespace App\Controllers;

use App\Models\MultiTenantAnalytics;
use App\Models\Tenant;
use App\Models\TenantFeatureUsage;
use App\Models\TenantBilling;
use App\Helpers\AuthHelper;

class MultiTenantAnalyticsController
{
    /**
     * Show multi-tenant analytics dashboard
     */
    public function index(): void
    {
        require_login();
        AuthHelper::requirePermission('analytics', 'view');

        $analyticsModel = new MultiTenantAnalytics();
        $overview = $analyticsModel->getSystemOverview();
        $systemHealth = $analyticsModel->getSystemHealth();
        $topPerformers = $analyticsModel->getTopPerformingTenants(10);

        include view_path('analytics/index');
    }

    /**
     * Show tenant comparison dashboard
     */
    public function tenantComparison(): void
    {
        require_login();
        AuthHelper::requirePermission('analytics', 'view');

        $analyticsModel = new MultiTenantAnalytics();
        $tenants = $analyticsModel->getTenantComparison(50);

        include view_path('analytics/tenant_comparison');
    }

    /**
     * Show subscription analytics
     */
    public function subscriptionAnalytics(): void
    {
        require_login();
        AuthHelper::requirePermission('analytics', 'view');

        $analyticsModel = new MultiTenantAnalytics();
        $overview = $analyticsModel->getSystemOverview();

        // Get detailed subscription data
        $tenantModel = new Tenant();
        $tenants = $tenantModel->all(['created_at' => 'DESC']);

        $subscriptionData = [
            'by_plan' => [],
            'by_status' => [],
            'expiring_soon' => [],
            'recent_changes' => []
        ];

        foreach ($tenants as $tenant) {
            $plan = $tenant['subscription_plan'] ?? 'none';
            if (!isset($subscriptionData['by_plan'][$plan])) {
                $subscriptionData['by_plan'][$plan] = 0;
            }
            $subscriptionData['by_plan'][$plan]++;

            $status = $tenant['status'];
            if (!isset($subscriptionData['by_status'][$status])) {
                $subscriptionData['by_status'][$status] = 0;
            }
            $subscriptionData['by_status'][$status]++;

            // Check if subscription expires soon
            if ($tenant['subscription_ends_at']) {
                $endDate = strtotime($tenant['subscription_ends_at']);
                $now = time();
                $daysUntilExpiry = ($endDate - $now) / (60 * 60 * 24);

                if ($daysUntilExpiry >= 0 && $daysUntilExpiry <= 30) {
                    $subscriptionData['expiring_soon'][] = [
                        'name' => $tenant['name'],
                        'plan' => $plan,
                        'days_left' => round($daysUntilExpiry),
                        'end_date' => $tenant['subscription_ends_at']
                    ];
                }
            }
        }

        include view_path('analytics/subscription');
    }

    /**
     * Show usage analytics
     */
    public function usageAnalytics(): void
    {
        require_login();
        AuthHelper::requirePermission('analytics', 'view');

        $analyticsModel = new MultiTenantAnalytics();
        $overview = $analyticsModel->getSystemOverview();
        $usageStats = $overview['usage_stats'];

        include view_path('analytics/usage');
    }

    /**
     * Show financial analytics
     */
    public function financialAnalytics(): void
    {
        require_login();
        AuthHelper::requirePermission('analytics', 'view');

        $analyticsModel = new MultiTenantAnalytics();
        $overview = $analyticsModel->getSystemOverview();
        $financialStats = $overview['financial_stats'];

        // Get revenue trends (last 12 months)
        $revenueTrends = $this->getRevenueTrends();

        include view_path('analytics/financial');
    }

    /**
     * Show system health dashboard
     */
    public function systemHealth(): void
    {
        require_login();
        AuthHelper::requirePermission('analytics', 'view');

        $analyticsModel = new MultiTenantAnalytics();
        $systemHealth = $analyticsModel->getSystemHealth();

        include view_path('analytics/system_health');
    }

    /**
     * Generate system report
     */
    public function generateReport(): void
    {
        require_login();
        AuthHelper::requirePermission('analytics', 'export');

        $format = $_GET['format'] ?? 'json';
        $analyticsModel = new MultiTenantAnalytics();

        $reportData = $analyticsModel->generateSystemReport($format);

        // Set appropriate headers based on format
        if ($format === 'json') {
            header('Content-Type: application/json');
            header('Content-Disposition: attachment; filename="system_report_' . date('Y-m-d') . '.json"');
        } else {
            header('Content-Type: text/plain');
            header('Content-Disposition: attachment; filename="system_report_' . date('Y-m-d') . '.txt"');
        }

        echo $reportData;
    }

    // ===== API ENDPOINTS =====

    /**
     * Get system overview API
     */
    public function getOverviewApi(): void
    {
        require_login();

        $analyticsModel = new MultiTenantAnalytics();
        $overview = $analyticsModel->getSystemOverview();

        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'overview' => $overview]);
    }

    /**
     * Get tenant comparison API
     */
    public function getTenantComparisonApi(): void
    {
        require_login();

        $limit = (int)($_GET['limit'] ?? 20);
        $analyticsModel = new MultiTenantAnalytics();
        $tenants = $analyticsModel->getTenantComparison($limit);

        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'tenants' => $tenants]);
    }

    /**
     * Get usage analytics API
     */
    public function getUsageAnalyticsApi(): void
    {
        require_login();

        $analyticsModel = new MultiTenantAnalytics();
        $overview = $analyticsModel->getSystemOverview();

        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'usage' => $overview['usage_stats']]);
    }

    /**
     * Get financial analytics API
     */
    public function getFinancialAnalyticsApi(): void
    {
        require_login();

        $analyticsModel = new MultiTenantAnalytics();
        $overview = $analyticsModel->getSystemOverview();

        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'financial' => $overview['financial_stats']]);
    }

    /**
     * Get system health API
     */
    public function getSystemHealthApi(): void
    {
        require_login();

        $analyticsModel = new MultiTenantAnalytics();
        $health = $analyticsModel->getSystemHealth();

        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'health' => $health]);
    }

    /**
     * Get real-time metrics API
     */
    public function getRealTimeMetricsApi(): void
    {
        require_login();

        $metrics = [
            'active_tenants' => $this->getActiveTenantCount(),
            'total_users' => $this->getTotalUserCount(),
            'system_load' => $this->getSystemLoad(),
            'db_connections' => $this->getDbConnectionCount(),
            'timestamp' => time()
        ];

        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'metrics' => $metrics]);
    }

    // ===== PRIVATE METHODS =====

    /**
     * Get revenue trends for the last 12 months
     */
    private function getRevenueTrends(): array
    {
        $trends = [];

        for ($i = 11; $i >= 0; $i--) {
            $date = date('Y-m', strtotime("-{$i} months"));
            $year = date('Y', strtotime($date . '-01'));
            $month = date('m', strtotime($date . '-01'));

            $stmt = $this->db->prepare("
                SELECT SUM(amount) as revenue
                FROM tenant_billings
                WHERE status = 'paid'
                AND YEAR(billing_period_start) = ?
                AND MONTH(billing_period_start) = ?
            ");
            $stmt->execute([$year, $month]);
            $result = $stmt->fetch();

            $trends[] = [
                'month' => date('M Y', strtotime($date . '-01')),
                'revenue' => (float)($result['revenue'] ?? 0)
            ];
        }

        return $trends;
    }

    /**
     * Get active tenant count
     */
    private function getActiveTenantCount(): int
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM tenants WHERE status = 'active'");
        $stmt->execute();
        $result = $stmt->fetch();
        return (int)$result['count'];
    }

    /**
     * Get total user count across all tenants
     */
    private function getTotalUserCount(): int
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM users");
        $stmt->execute();
        $result = $stmt->fetch();
        return (int)$result['count'];
    }

    /**
     * Get system load (simplified)
     */
    private function getSystemLoad(): float
    {
        if (function_exists('sys_getloadavg')) {
            $load = sys_getloadavg();
            return round($load[0], 2);
        }
        return 0.0;
    }

    /**
     * Get database connection count (simplified)
     */
    private function getDbConnectionCount(): int
    {
        // This is a simplified count - in production you'd use monitoring tools
        return 1;
    }
}
