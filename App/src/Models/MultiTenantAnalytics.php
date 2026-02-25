<?php
namespace App\Models;

class MultiTenantAnalytics extends Model
{
    /**
     * Get system-wide analytics overview
     */
    public function getSystemOverview(): array
    {
        // Get tenant statistics
        $tenantStats = $this->getTenantStatistics();

        // Get subscription statistics
        $subscriptionStats = $this->getSubscriptionStatistics();

        // Get usage statistics
        $usageStats = $this->getUsageStatistics();

        // Get financial statistics
        $financialStats = $this->getFinancialStatistics();

        // Get performance metrics
        $performanceStats = $this->getPerformanceMetrics();

        return [
            'tenant_stats' => $tenantStats,
            'subscription_stats' => $subscriptionStats,
            'usage_stats' => $usageStats,
            'financial_stats' => $financialStats,
            'performance_stats' => $performanceStats,
            'generated_at' => date('Y-m-d H:i:s')
        ];
    }

    /**
     * Get tenant statistics
     */
    private function getTenantStatistics(): array
    {
        $stmt = $this->db->prepare("
            SELECT
                COUNT(*) as total_tenants,
                COUNT(CASE WHEN status = 'active' THEN 1 END) as active_tenants,
                COUNT(CASE WHEN status = 'inactive' THEN 1 END) as inactive_tenants,
                COUNT(CASE WHEN status = 'suspended' THEN 1 END) as suspended_tenants,
                COUNT(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 END) as new_tenants_30d,
                COUNT(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 END) as new_tenants_7d
            FROM tenants
        ");
        $stmt->execute();
        $stats = $stmt->fetch();

        // Get most popular subscription plans
        $stmt = $this->db->prepare("
            SELECT subscription_plan, COUNT(*) as count
            FROM tenants
            WHERE status = 'active'
            GROUP BY subscription_plan
            ORDER BY count DESC
            LIMIT 5
        ");
        $stmt->execute();
        $planStats = $stmt->fetchAll();

        return array_merge($stats, ['popular_plans' => $planStats]);
    }

    /**
     * Get subscription statistics
     */
    private function getSubscriptionStatistics(): array
    {
        $stmt = $this->db->prepare("
            SELECT
                COUNT(CASE WHEN subscription_ends_at < NOW() THEN 1 END) as expired_subscriptions,
                COUNT(CASE WHEN subscription_ends_at BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 30 DAY) THEN 1 END) as expiring_soon,
                COUNT(CASE WHEN trial_ends_at < NOW() THEN 1 END) as expired_trials,
                COUNT(CASE WHEN trial_ends_at BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 7 DAY) THEN 1 END) as trials_expiring_soon,
                AVG(CASE WHEN billing_cycle = 'monthly' THEN 1 ELSE 12 END) as avg_billing_cycle_months
            FROM tenants
            WHERE status = 'active'
        ");
        $stmt->execute();
        $stats = $stmt->fetch();

        // Get subscription changes this month
        $stmt = $this->db->prepare("
            SELECT
                COUNT(CASE WHEN action = 'upgrade' THEN 1 END) as upgrades_this_month,
                COUNT(CASE WHEN action = 'downgrade' THEN 1 END) as downgrades_this_month,
                COUNT(CASE WHEN action = 'cancel' THEN 1 END) as cancellations_this_month
            FROM tenant_audit_logs
            WHERE action LIKE 'subscription_%'
            AND created_at >= DATE_FORMAT(NOW(), '%Y-%m-01')
        ");
        $stmt->execute();
        $changeStats = $stmt->fetch();

        return array_merge($stats, $changeStats);
    }

    /**
     * Get usage statistics
     */
    private function getUsageStatistics(): array
    {
        // Get current month usage
        $currentPeriod = $this->getCurrentPeriod();

        $stmt = $this->db->prepare("
            SELECT
                COUNT(DISTINCT tenant_id) as active_tenants_with_usage,
                SUM(usage_count) as total_usage_this_month,
                AVG(usage_count) as avg_usage_per_tenant,
                MAX(usage_count) as max_usage_by_tenant
            FROM tenant_feature_usage
            WHERE period_start = ? AND period_end = ?
        ");
        $stmt->execute([$currentPeriod['start'], $currentPeriod['end']]);
        $monthlyStats = $stmt->fetch();

        // Get feature usage breakdown
        $stmt = $this->db->prepare("
            SELECT
                feature_name,
                SUM(usage_count) as total_usage,
                COUNT(DISTINCT tenant_id) as tenants_using,
                AVG(usage_count) as avg_usage
            FROM tenant_feature_usage
            WHERE period_start = ? AND period_end = ?
            GROUP BY feature_name
            ORDER BY total_usage DESC
        ");
        $stmt->execute([$currentPeriod['start'], $currentPeriod['end']]);
        $featureStats = $stmt->fetchAll();

        return [
            'monthly_stats' => $monthlyStats,
            'feature_breakdown' => $featureStats
        ];
    }

    /**
     * Get financial statistics
     */
    private function getFinancialStatistics(): array
    {
        // Get billing statistics
        $stmt = $this->db->prepare("
            SELECT
                SUM(amount) as total_billed_this_month,
                AVG(amount) as avg_billing_per_tenant,
                COUNT(*) as total_invoices,
                COUNT(CASE WHEN status = 'paid' THEN 1 END) as paid_invoices,
                COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_invoices,
                COUNT(CASE WHEN status = 'overdue' THEN 1 END) as overdue_invoices
            FROM tenant_billings
            WHERE billing_period_start >= DATE_FORMAT(NOW(), '%Y-%m-01')
        ");
        $stmt->execute();
        $billingStats = $stmt->fetch();

        // Get revenue by plan
        $stmt = $this->db->prepare("
            SELECT
                t.subscription_plan,
                SUM(tb.amount) as total_revenue,
                COUNT(DISTINCT t.id) as tenant_count,
                AVG(tb.amount) as avg_revenue_per_tenant
            FROM tenants t
            LEFT JOIN tenant_billings tb ON t.id = tb.tenant_id
            WHERE tb.status = 'paid'
            AND tb.billing_period_start >= DATE_FORMAT(NOW(), '%Y-%m-01')
            GROUP BY t.subscription_plan
            ORDER BY total_revenue DESC
        ");
        $stmt->execute();
        $revenueByPlan = $stmt->fetchAll();

        return [
            'billing_stats' => $billingStats,
            'revenue_by_plan' => $revenueByPlan
        ];
    }

    /**
     * Get performance metrics
     */
    private function getPerformanceMetrics(): array
    {
        // Get system uptime (placeholder - would need monitoring system)
        $uptimeStats = [
            'system_uptime_percentage' => 99.9,
            'average_response_time' => 150, // ms
            'error_rate' => 0.01 // percentage
        ];

        // Get tenant performance metrics
        $stmt = $this->db->prepare("
            SELECT
                AVG(profile_completion_percentage) as avg_profile_completion,
                COUNT(CASE WHEN logo_path IS NOT NULL THEN 1 END) as tenants_with_logo,
                COUNT(CASE WHEN branding_settings IS NOT NULL THEN 1 END) as tenants_with_branding,
                COUNT(CASE WHEN theme_settings IS NOT NULL THEN 1 END) as tenants_with_custom_theme
            FROM tenants
            WHERE status = 'active'
        ");
        $stmt->execute();
        $tenantPerformance = $stmt->fetch();

        return [
            'system_performance' => $uptimeStats,
            'tenant_performance' => $tenantPerformance
        ];
    }

    /**
     * Get tenant comparison data
     */
    public function getTenantComparison(int $limit = 20): array
    {
        $stmt = $this->db->prepare("
            SELECT
                t.id, t.name, t.slug, t.subscription_plan, t.status,
                t.created_at, t.last_profile_update,
                COALESCE(t.profile_completion_percentage, 0) as profile_completion,
                COUNT(DISTINCT u.id) as user_count,
                COALESCE(SUM(tfu.usage_count), 0) as total_usage_this_month
            FROM tenants t
            LEFT JOIN users u ON u.tenant_id = t.id
            LEFT JOIN tenant_feature_usage tfu ON tfu.tenant_id = t.id
                AND tfu.period_start = DATE_FORMAT(NOW(), '%Y-%m-01')
                AND tfu.period_end = DATE_FORMAT(LAST_DAY(NOW()), '%Y-%m-%d')
            GROUP BY t.id, t.name, t.slug, t.subscription_plan, t.status, t.created_at, t.last_profile_update, t.profile_completion_percentage
            ORDER BY t.created_at DESC
            LIMIT ?
        ");
        $stmt->execute([$limit]);
        $tenants = $stmt->fetchAll();

        return $tenants;
    }

    /**
     * Get top performing tenants
     */
    public function getTopPerformingTenants(int $limit = 10): array
    {
        $stmt = $this->db->prepare("
            SELECT
                t.id, t.name, t.slug,
                COALESCE(t.profile_completion_percentage, 0) as profile_completion,
                COUNT(DISTINCT u.id) as user_count,
                COALESCE(SUM(tfu.usage_count), 0) as total_usage,
                t.created_at
            FROM tenants t
            LEFT JOIN users u ON u.tenant_id = t.id
            LEFT JOIN tenant_feature_usage tfu ON tfu.tenant_id = t.id
                AND tfu.period_start = DATE_FORMAT(NOW(), '%Y-%m-01')
                AND tfu.period_end = DATE_FORMAT(LAST_DAY(NOW()), '%Y-%m-%d')
            WHERE t.status = 'active'
            GROUP BY t.id, t.name, t.slug, t.profile_completion_percentage, t.created_at
            ORDER BY (
                COALESCE(t.profile_completion_percentage, 0) +
                LEAST(COUNT(DISTINCT u.id), 10) * 10 +
                LEAST(COALESCE(SUM(tfu.usage_count), 0), 1000) * 0.1
            ) DESC
            LIMIT ?
        ");
        $stmt->execute([$limit]);
        $tenants = $stmt->fetchAll();

        return $tenants;
    }

    /**
     * Get system health metrics
     */
    public function getSystemHealth(): array
    {
        // Database connection health
        $dbHealthy = true;
        try {
            $this->db->query('SELECT 1');
        } catch (\Exception $e) {
            $dbHealthy = false;
        }

        // File system health (check if storage is writable)
        $storageHealthy = is_writable(__DIR__ . '/../../storage');

        // Memory usage
        $memoryUsage = memory_get_peak_usage(true);
        $memoryLimit = $this->getMemoryLimitBytes();

        // Disk usage
        $diskTotal = disk_total_space('/');
        $diskFree = disk_free_space('/');
        $diskUsage = $diskTotal > 0 ? (($diskTotal - $diskFree) / $diskTotal) * 100 : 0;

        return [
            'database' => [
                'healthy' => $dbHealthy,
                'connections' => 1, // Simplified
                'query_time_avg' => 0.05 // Placeholder
            ],
            'storage' => [
                'healthy' => $storageHealthy,
                'writable' => $storageHealthy
            ],
            'memory' => [
                'usage' => $memoryUsage,
                'limit' => $memoryLimit,
                'percentage' => $memoryLimit > 0 ? ($memoryUsage / $memoryLimit) * 100 : 0
            ],
            'disk' => [
                'total' => $diskTotal,
                'free' => $diskFree,
                'usage_percentage' => $diskUsage
            ],
            'overall_health' => ($dbHealthy && $storageHealthy) ? 'healthy' : 'warning'
        ];
    }

    /**
     * Generate comprehensive system report
     */
    public function generateSystemReport(string $format = 'json'): string
    {
        $reportData = [
            'generated_at' => date('Y-m-d H:i:s'),
            'period' => [
                'start' => date('Y-m-01'),
                'end' => date('Y-m-t')
            ],
            'system_overview' => $this->getSystemOverview(),
            'system_health' => $this->getSystemHealth(),
            'tenant_comparison' => $this->getTenantComparison(50),
            'top_performers' => $this->getTopPerformingTenants(20)
        ];

        if ($format === 'json') {
            return json_encode($reportData, JSON_PRETTY_PRINT);
        }

        // For other formats, could implement CSV, PDF, etc.
        return json_encode($reportData);
    }

    /**
     * Get current billing period
     */
    private function getCurrentPeriod(): array
    {
        $now = new \DateTime();
        $start = new \DateTime($now->format('Y-m-01'));
        $end = new \DateTime($now->format('Y-m-t'));

        return [
            'start' => $start->format('Y-m-d'),
            'end' => $end->format('Y-m-d')
        ];
    }

    /**
     * Convert memory limit to bytes
     */
    private function getMemoryLimitBytes(): int
    {
        $limit = ini_get('memory_limit');
        if (preg_match('/^(\d+)(.)$/', $limit, $matches)) {
            $value = (int)$matches[1];
            $unit = $matches[2];

            switch (strtoupper($unit)) {
                case 'G':
                    $value *= 1024 * 1024 * 1024;
                    break;
                case 'M':
                    $value *= 1024 * 1024;
                    break;
                case 'K':
                    $value *= 1024;
                    break;
            }

            return $value;
        }

        return 134217728; // 128MB default
    }
}
