<?php
namespace App\Monitoring;

use App\Models\Tenant;
use App\Database;

/**
 * Multi-Tenant Performance Monitoring System
 *
 * Monitors performance metrics across all tenants
 * Provides insights into system health and usage patterns
 */
class TenantPerformanceMonitor
{
    private Tenant $tenantModel;
    private array $metrics = [];

    public function __construct()
    {
        $this->tenantModel = new Tenant();
    }

    /**
     * Collect performance metrics for all active tenants
     */
    public function collectMetrics(): array
    {
        $activeTenants = $this->tenantModel->findWhere(['status' => 'active']);
        $metrics = [];

        foreach ($activeTenants as $tenant) {
            try {
                $tenantMetrics = $this->collectTenantMetrics($tenant);
                $metrics[$tenant['id']] = $tenantMetrics;

                // Store metrics in database for historical tracking
                $this->storeMetrics($tenant['id'], $tenantMetrics);

            } catch (\Exception $e) {
                error_log("Failed to collect metrics for tenant {$tenant['name']}: " . $e->getMessage());
                $metrics[$tenant['id']] = [
                    'error' => $e->getMessage(),
                    'collected_at' => date('Y-m-d H:i:s')
                ];
            }
        }

        // Collect system-wide metrics
        $metrics['system'] = $this->collectSystemMetrics();

        return $metrics;
    }

    /**
     * Collect metrics for specific tenant
     */
    private function collectTenantMetrics(array $tenant): array
    {
        $metrics = [
            'tenant_id' => $tenant['id'],
            'tenant_name' => $tenant['name'],
            'collected_at' => date('Y-m-d H:i:s'),
            'period' => '5min' // Collection interval
        ];

        try {
            // Get tenant database connection
            $tenantDb = $this->tenantModel->getTenantDatabaseById($tenant['id']);

            if (!$tenantDb) {
                throw new \Exception("Tenant database not accessible");
            }

            // Database performance metrics
            $metrics['database'] = $this->collectDatabaseMetrics($tenantDb);

            // Application performance metrics
            $metrics['application'] = $this->collectApplicationMetrics($tenantDb);

            // Business metrics
            $metrics['business'] = $this->collectBusinessMetrics($tenantDb);

            // Resource usage
            $metrics['resources'] = $this->collectResourceMetrics();

        } catch (\Exception $e) {
            $metrics['error'] = $e->getMessage();
        }

        return $metrics;
    }

    /**
     * Collect database performance metrics
     */
    private function collectDatabaseMetrics(\PDO $db): array
    {
        $metrics = [
            'connections_active' => 0,
            'query_count' => 0,
            'slow_queries' => 0,
            'table_sizes' => []
        ];

        try {
            // Count active connections (approximate)
            $stmt = $db->query("SHOW PROCESSLIST");
            $processes = $stmt->fetchAll();
            $metrics['connections_active'] = count($processes);

            // Get table sizes
            $stmt = $db->query("
                SELECT
                    table_name,
                    round(((data_length + index_length) / 1024 / 1024), 2) as size_mb
                FROM information_schema.TABLES
                WHERE table_schema = DATABASE()
                ORDER BY (data_length + index_length) DESC
                LIMIT 10
            ");
            $metrics['table_sizes'] = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            // Count recent queries (if general log is enabled)
            // This would require additional setup

        } catch (\Exception $e) {
            $metrics['error'] = $e->getMessage();
        }

        return $metrics;
    }

    /**
     * Collect application performance metrics
     */
    private function collectApplicationMetrics(\PDO $db): array
    {
        $metrics = [
            'active_users' => 0,
            'concurrent_sessions' => 0,
            'api_calls_today' => 0,
            'error_rate' => 0,
            'response_time_avg' => 0
        ];

        try {
            // Active users (logged in within last hour)
            $stmt = $db->prepare("
                SELECT COUNT(*) as count FROM users
                WHERE last_login > DATE_SUB(NOW(), INTERVAL 1 HOUR)
            ");
            $stmt->execute();
            $metrics['active_users'] = (int)$stmt->fetch()['count'];

            // API calls today
            $stmt = $db->prepare("
                SELECT COUNT(*) as count FROM audit_logs
                WHERE DATE(created_at) = CURDATE()
            ");
            $stmt->execute();
            $metrics['api_calls_today'] = (int)$stmt->fetch()['count'];

            // Error rate (failed operations)
            $stmt = $db->prepare("
                SELECT
                    COUNT(*) as total,
                    SUM(CASE WHEN action LIKE '%error%' OR action LIKE '%fail%' THEN 1 ELSE 0 END) as errors
                FROM audit_logs
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
            ");
            $stmt->execute();
            $result = $stmt->fetch();
            $total = (int)$result['total'];
            $errors = (int)$result['errors'];
            $metrics['error_rate'] = $total > 0 ? round(($errors / $total) * 100, 2) : 0;

        } catch (\Exception $e) {
            $metrics['error'] = $e->getMessage();
        }

        return $metrics;
    }

    /**
     * Collect business performance metrics
     */
    private function collectBusinessMetrics(\PDO $db): array
    {
        $metrics = [
            'total_members' => 0,
            'active_loans' => 0,
            'total_outstanding' => 0,
            'npl_ratio' => 0,
            'monthly_transactions' => 0
        ];

        try {
            // Total members
            $stmt = $db->query("SELECT COUNT(*) as count FROM members WHERE status = 'active'");
            $metrics['total_members'] = (int)$stmt->fetch()['count'];

            // Active loans
            $stmt = $db->query("
                SELECT COUNT(*) as count FROM loans
                WHERE status IN ('approved', 'disbursed', 'active')
            ");
            $metrics['active_loans'] = (int)$stmt->fetch()['count'];

            // Total outstanding
            $stmt = $db->query("
                SELECT COALESCE(SUM(amount), 0) as total
                FROM loans
                WHERE status IN ('approved', 'disbursed', 'active')
            ");
            $metrics['total_outstanding'] = (float)$stmt->fetch()['total'];

            // NPL ratio
            $stmt = $db->query("
                SELECT
                    COUNT(*) as total_loans,
                    SUM(CASE WHEN status = 'defaulted' THEN 1 ELSE 0 END) as npl_count
                FROM loans
            ");
            $result = $stmt->fetch();
            $totalLoans = (int)$result['total_loans'];
            $nplCount = (int)$result['npl_count'];
            $metrics['npl_ratio'] = $totalLoans > 0 ? round(($nplCount / $totalLoans) * 100, 2) : 0;

            // Monthly transactions (last 30 days)
            $stmt = $db->query("
                SELECT COUNT(*) as count FROM savings_transactions
                WHERE transaction_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
            ");
            $metrics['monthly_transactions'] = (int)$stmt->fetch()['count'];

        } catch (\Exception $e) {
            $metrics['error'] = $e->getMessage();
        }

        return $metrics;
    }

    /**
     * Collect system resource metrics
     */
    private function collectResourceMetrics(): array
    {
        $metrics = [
            'cpu_usage' => 0,
            'memory_usage' => 0,
            'disk_usage' => 0,
            'response_time' => 0
        ];

        // CPU usage (simplified)
        if (function_exists('sys_getloadavg')) {
            $load = sys_getloadavg();
            $metrics['cpu_usage'] = round($load[0] * 100, 2); // Convert to percentage
        }

        // Memory usage
        $memoryUsage = memory_get_peak_usage(true);
        $metrics['memory_usage'] = round($memoryUsage / 1024 / 1024, 2); // MB

        // Disk usage
        $diskFree = disk_free_space('/');
        $diskTotal = disk_total_space('/');
        $diskUsed = $diskTotal - $diskFree;
        $metrics['disk_usage'] = round(($diskUsed / $diskTotal) * 100, 2); // Percentage

        // Average response time (from recent requests)
        $metrics['response_time'] = $this->calculateAverageResponseTime();

        return $metrics;
    }

    /**
     * Collect system-wide metrics
     */
    private function collectSystemMetrics(): array
    {
        $mainDb = Database::getConnection();

        $metrics = [
            'total_tenants' => 0,
            'active_tenants' => 0,
            'total_users' => 0,
            'system_uptime' => 0,
            'total_revenue' => 0
        ];

        try {
            // Tenant statistics
            $stmt = $mainDb->query("SELECT COUNT(*) as count FROM tenants");
            $metrics['total_tenants'] = (int)$stmt->fetch()['count'];

            $stmt = $mainDb->query("SELECT COUNT(*) as count FROM tenants WHERE status = 'active'");
            $metrics['active_tenants'] = (int)$stmt->fetch()['count'];

            // Total users across all tenants
            $stmt = $mainDb->query("SELECT COUNT(*) as count FROM users");
            $metrics['total_users'] = (int)$stmt->fetch()['count'];

            // System uptime (simplified)
            $metrics['system_uptime'] = round($_SERVER['REQUEST_TIME'] / 3600, 2); // Hours

            // Total revenue (from billings)
            $stmt = $mainDb->query("
                SELECT COALESCE(SUM(amount), 0) as total
                FROM tenant_billings
                WHERE status = 'paid'
            ");
            $metrics['total_revenue'] = (float)$stmt->fetch()['total'];

        } catch (\Exception $e) {
            $metrics['error'] = $e->getMessage();
        }

        return $metrics;
    }

    /**
     * Calculate average response time
     */
    private function calculateAverageResponseTime(): float
    {
        // This would require additional logging infrastructure
        // For now, return a placeholder
        return 0.0;
    }

    /**
     * Store metrics in database for historical tracking
     */
    private function storeMetrics(int $tenantId, array $metrics): void
    {
        $mainDb = Database::getConnection();

        $stmt = $mainDb->prepare("
            INSERT INTO tenant_performance_metrics
            (tenant_id, metrics_data, collected_at, period)
            VALUES (?, ?, ?, ?)
        ");

        $stmt->execute([
            $tenantId,
            json_encode($metrics),
            $metrics['collected_at'],
            $metrics['period'] ?? '5min'
        ]);
    }

    /**
     * Get performance metrics for specific tenant
     */
    public function getTenantMetrics(int $tenantId, string $period = '24h'): array
    {
        $mainDb = Database::getConnection();

        $timeCondition = match($period) {
            '1h' => 'collected_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)',
            '24h' => 'collected_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)',
            '7d' => 'collected_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)',
            '30d' => 'collected_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)',
            default => 'collected_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)'
        };

        $stmt = $mainDb->prepare("
            SELECT * FROM tenant_performance_metrics
            WHERE tenant_id = ? AND {$timeCondition}
            ORDER BY collected_at DESC
            LIMIT 100
        ");

        $stmt->execute([$tenantId]);
        $results = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        // Parse JSON data
        foreach ($results as &$result) {
            $result['metrics_data'] = json_decode($result['metrics_data'], true);
        }

        return $results;
    }

    /**
     * Get system-wide performance dashboard
     */
    public function getSystemDashboard(): array
    {
        $metrics = $this->collectMetrics();

        $dashboard = [
            'summary' => [
                'total_tenants' => $metrics['system']['total_tenants'] ?? 0,
                'active_tenants' => $metrics['system']['active_tenants'] ?? 0,
                'total_users' => $metrics['system']['total_users'] ?? 0,
                'system_health' => $this->calculateSystemHealth($metrics)
            ],
            'tenant_health' => [],
            'alerts' => []
        ];

        // Analyze tenant health
        foreach ($metrics as $tenantId => $tenantMetrics) {
            if ($tenantId === 'system') continue;

            $health = $this->analyzeTenantHealth($tenantMetrics);
            $dashboard['tenant_health'][$tenantId] = $health;

            if ($health['status'] !== 'healthy') {
                $dashboard['alerts'][] = [
                    'type' => 'tenant_health',
                    'tenant_id' => $tenantId,
                    'severity' => $health['status'],
                    'message' => $health['issues'][0] ?? 'Performance issues detected'
                ];
            }
        }

        return $dashboard;
    }

    /**
     * Calculate system health score
     */
    private function calculateSystemHealth(array $metrics): array
    {
        $system = $metrics['system'] ?? [];

        $health = [
            'score' => 100,
            'status' => 'healthy',
            'issues' => []
        ];

        // Check tenant activity
        $activeRatio = $system['total_tenants'] > 0 ?
            ($system['active_tenants'] / $system['total_tenants']) * 100 : 100;

        if ($activeRatio < 80) {
            $health['score'] -= 20;
            $health['issues'][] = 'Low tenant activity ratio';
        }

        // Check for errors in metrics collection
        $errorCount = 0;
        foreach ($metrics as $tenantMetrics) {
            if (isset($tenantMetrics['error'])) {
                $errorCount++;
            }
        }

        if ($errorCount > 0) {
            $health['score'] -= ($errorCount * 5);
            $health['issues'][] = "{$errorCount} tenants have metric collection errors";
        }

        // Determine status
        if ($health['score'] < 50) {
            $health['status'] = 'critical';
        } elseif ($health['score'] < 75) {
            $health['status'] = 'warning';
        }

        return $health;
    }

    /**
     * Analyze individual tenant health
     */
    private function analyzeTenantHealth(array $tenantMetrics): array
    {
        $health = [
            'status' => 'healthy',
            'score' => 100,
            'issues' => []
        ];

        // Check for errors
        if (isset($tenantMetrics['error'])) {
            $health['status'] = 'error';
            $health['score'] = 0;
            $health['issues'][] = $tenantMetrics['error'];
            return $health;
        }

        $app = $tenantMetrics['application'] ?? [];
        $business = $tenantMetrics['business'] ?? [];

        // Check error rate
        if (($app['error_rate'] ?? 0) > 10) {
            $health['score'] -= 30;
            $health['issues'][] = 'High error rate: ' . $app['error_rate'] . '%';
        }

        // Check NPL ratio
        if (($business['npl_ratio'] ?? 0) > 15) {
            $health['score'] -= 25;
            $health['issues'][] = 'High NPL ratio: ' . $business['npl_ratio'] . '%';
        }

        // Check activity
        if (($business['monthly_transactions'] ?? 0) < 10) {
            $health['score'] -= 15;
            $health['issues'][] = 'Low transaction activity';
        }

        // Determine status
        if ($health['score'] < 30) {
            $health['status'] = 'critical';
        } elseif ($health['score'] < 60) {
            $health['status'] = 'warning';
        } elseif ($health['score'] < 80) {
            $health['status'] = 'caution';
        }

        return $health;
    }

    /**
     * Get performance alerts
     */
    public function getPerformanceAlerts(): array
    {
        $alerts = [];
        $metrics = $this->collectMetrics();

        foreach ($metrics as $tenantId => $tenantMetrics) {
            if ($tenantId === 'system') continue;

            if (isset($tenantMetrics['error'])) {
                $alerts[] = [
                    'type' => 'metric_collection_failed',
                    'tenant_id' => $tenantId,
                    'severity' => 'high',
                    'message' => 'Failed to collect performance metrics',
                    'details' => $tenantMetrics['error']
                ];
            }

            $app = $tenantMetrics['application'] ?? [];
            if (($app['error_rate'] ?? 0) > 20) {
                $alerts[] = [
                    'type' => 'high_error_rate',
                    'tenant_id' => $tenantId,
                    'severity' => 'critical',
                    'message' => 'Error rate exceeds 20%',
                    'details' => 'Current error rate: ' . $app['error_rate'] . '%'
                ];
            }
        }

        return $alerts;
    }
}

/**
 * Performance Monitoring Middleware
 */
class PerformanceMonitoringMiddleware
{
    private TenantPerformanceMonitor $monitor;
    private array $requestMetrics = [];

    public function __construct()
    {
        $this->monitor = new TenantPerformanceMonitor();
    }

    /**
     * Start performance monitoring for request
     */
    public function startRequest(): void
    {
        $this->requestMetrics = [
            'start_time' => microtime(true),
            'start_memory' => memory_get_usage(),
            'tenant_id' => \App\Middleware\TenantContextManager::getCurrentTenantId(),
            'request_uri' => $_SERVER['REQUEST_URI'] ?? '',
            'request_method' => $_SERVER['REQUEST_METHOD'] ?? '',
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? ''
        ];
    }

    /**
     * End performance monitoring for request
     */
    public function endRequest(): void
    {
        $this->requestMetrics['end_time'] = microtime(true);
        $this->requestMetrics['end_memory'] = memory_get_usage();

        // Calculate metrics
        $duration = $this->requestMetrics['end_time'] - $this->requestMetrics['start_time'];
        $memoryUsed = $this->requestMetrics['end_memory'] - $this->requestMetrics['start_memory'];

        $this->requestMetrics['duration_ms'] = round($duration * 1000, 2);
        $this->requestMetrics['memory_used_mb'] = round($memoryUsed / 1024 / 1024, 2);

        // Log slow requests
        if ($duration > 2.0) { // More than 2 seconds
            error_log("SLOW_REQUEST: " . json_encode($this->requestMetrics));
        }

        // Store performance data (optional - for detailed monitoring)
        $this->storeRequestMetrics();
    }

    /**
     * Store request performance metrics
     */
    private function storeRequestMetrics(): void
    {
        // Optional: Store detailed request metrics in database
        // This would be useful for detailed performance analysis
    }

    /**
     * Get current performance monitor
     */
    public function getMonitor(): TenantPerformanceMonitor
    {
        return $this->monitor;
    }

    /**
     * Get request performance summary
     */
    public function getRequestSummary(): array
    {
        return $this->requestMetrics;
    }
}

// =========================================
// PERFORMANCE MONITORING TABLES SETUP
// =========================================

/*
-- Create performance monitoring tables:

CREATE TABLE tenant_performance_metrics (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT NOT NULL,
    metrics_data JSON NOT NULL,
    collected_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    period VARCHAR(10) DEFAULT '5min',
    INDEX idx_tenant_collected (tenant_id, collected_at),
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
);

CREATE TABLE request_performance_logs (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT NULL,
    request_uri VARCHAR(500) NOT NULL,
    request_method VARCHAR(10) NOT NULL,
    duration_ms DECIMAL(8,2) NOT NULL,
    memory_used_mb DECIMAL(8,2) NOT NULL,
    response_code INT DEFAULT 200,
    ip_address VARCHAR(45) NOT NULL,
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_tenant_created (tenant_id, created_at),
    INDEX idx_duration (duration_ms)
);

-- Automated cleanup (optional):
-- Delete old metrics after 90 days
CREATE EVENT cleanup_old_metrics
ON SCHEDULE EVERY 1 DAY
DO
    DELETE FROM tenant_performance_metrics
    WHERE collected_at < DATE_SUB(NOW(), INTERVAL 90 DAY);

CREATE EVENT cleanup_old_request_logs
ON SCHEDULE EVERY 1 DAY
DO
    DELETE FROM request_performance_logs
    WHERE created_at < DATE_SUB(NOW(), INTERVAL 30 DAY);
*/

?>
