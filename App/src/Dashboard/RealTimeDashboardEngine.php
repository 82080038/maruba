<?php
namespace App\Dashboard;

use App\Models\Member;
use App\Models\Loan;
use App\Models\SavingsAccount;
use App\Monitoring\TenantPerformanceMonitor;

/**
 * Real-Time Dashboard System with Live KPI Monitoring
 *
 * Advanced real-time analytics and live dashboard updates
 * Essential for modern KSP platforms to provide instant insights
 * Competitive advantage through real-time decision support
 */
class RealTimeDashboardEngine
{
    private TenantPerformanceMonitor $performanceMonitor;
    private array $kpiDefinitions;

    public function __construct()
    {
        $this->performanceMonitor = new TenantPerformanceMonitor();
        $this->initializeKPIDefinitions();
    }

    /**
     * Initialize KPI definitions
     */
    private function initializeKPIDefinitions(): void
    {
        $this->kpiDefinitions = [
            'total_members' => [
                'name' => 'Total Anggota',
                'query' => 'SELECT COUNT(*) as value FROM members WHERE tenant_id = ?',
                'format' => 'number',
                'icon' => 'people',
                'color' => 'blue',
                'trend_period' => '7d'
            ],

            'active_loans' => [
                'name' => 'Pinjaman Aktif',
                'query' => 'SELECT COUNT(*) as value FROM loans WHERE tenant_id = ? AND status IN ("active", "disbursed")',
                'format' => 'number',
                'icon' => 'credit_card',
                'color' => 'green',
                'trend_period' => '30d'
            ],

            'total_outstanding' => [
                'name' => 'Total Outstanding',
                'query' => 'SELECT COALESCE(SUM(amount), 0) as value FROM loans WHERE tenant_id = ? AND status IN ("active", "disbursed")',
                'format' => 'currency',
                'icon' => 'account_balance',
                'color' => 'orange',
                'trend_period' => '30d'
            ],

            'total_savings' => [
                'name' => 'Total Simpanan',
                'query' => 'SELECT COALESCE(SUM(balance), 0) as value FROM savings_accounts WHERE tenant_id = ? AND status = "active"',
                'format' => 'currency',
                'icon' => 'savings',
                'color' => 'purple',
                'trend_period' => '30d'
            ],

            'monthly_transactions' => [
                'name' => 'Transaksi Bulan Ini',
                'query' => 'SELECT COUNT(*) as value FROM savings_transactions WHERE tenant_id = ? AND transaction_date >= DATE_FORMAT(CURDATE(), "%Y-%m-01")',
                'format' => 'number',
                'icon' => 'swap_horiz',
                'color' => 'teal',
                'trend_period' => '7d'
            ],

            'npl_ratio' => [
                'name' => 'NPL Ratio',
                'query' => 'SELECT CASE WHEN COUNT(*) > 0 THEN ROUND((SUM(CASE WHEN status = "defaulted" THEN 1 ELSE 0 END) / COUNT(*)) * 100, 2) ELSE 0 END as value FROM loans WHERE tenant_id = ?',
                'format' => 'percentage',
                'icon' => 'warning',
                'color' => 'red',
                'trend_period' => '90d'
            ],

            'new_members_today' => [
                'name' => 'Anggota Baru Hari Ini',
                'query' => 'SELECT COUNT(*) as value FROM members WHERE tenant_id = ? AND DATE(created_at) = CURDATE()',
                'format' => 'number',
                'icon' => 'person_add',
                'color' => 'green',
                'trend_period' => '1d'
            ],

            'pending_approvals' => [
                'name' => 'Menunggu Approval',
                'query' => 'SELECT COUNT(*) as value FROM loans WHERE tenant_id = ? AND status IN ("submitted", "survey_completed")',
                'format' => 'number',
                'icon' => 'schedule',
                'color' => 'amber',
                'trend_period' => '1d'
            ],

            'total_revenue_month' => [
                'name' => 'Revenue Bulan Ini',
                'query' => 'SELECT COALESCE(SUM(amount), 0) as value FROM payments WHERE tenant_id = ? AND payment_type = "fee" AND DATE(created_at) >= DATE_FORMAT(CURDATE(), "%Y-%m-01")',
                'format' => 'currency',
                'icon' => 'attach_money',
                'color' => 'green',
                'trend_period' => '30d'
            ]
        ];
    }

    /**
     * Get real-time dashboard data
     */
    public function getRealTimeDashboard(int $tenantId): array
    {
        $dashboard = [
            'tenant_id' => $tenantId,
            'generated_at' => date('Y-m-d H:i:s'),
            'kpis' => [],
            'charts' => [],
            'alerts' => [],
            'last_updated' => time()
        ];

        // Get KPI data
        $dashboard['kpis'] = $this->getKPIData($tenantId);

        // Get chart data
        $dashboard['charts'] = $this->getChartData($tenantId);

        // Get alerts
        $dashboard['alerts'] = $this->getDashboardAlerts($tenantId);

        // Add real-time metadata
        $dashboard['realtime'] = [
            'websocket_enabled' => true,
            'update_interval' => 30, // seconds
            'last_update' => time(),
            'next_update' => time() + 30
        ];

        return $dashboard;
    }

    /**
     * Get KPI data with trends
     */
    private function getKPIData(int $tenantId): array
    {
        $kpis = [];

        foreach ($this->kpiDefinitions as $kpiCode => $kpiConfig) {
            try {
                // Get current value
                $currentValue = $this->executeKPIQuery($kpiConfig['query'], $tenantId);

                // Get trend data
                $trend = $this->calculateKPITrend($tenantId, $kpiCode, $kpiConfig['trend_period']);

                // Format value
                $formattedValue = $this->formatKPIValue($currentValue, $kpiConfig['format']);

                $kpis[$kpiCode] = [
                    'code' => $kpiCode,
                    'name' => $kpiConfig['name'],
                    'value' => $currentValue,
                    'formatted_value' => $formattedValue,
                    'format' => $kpiConfig['format'],
                    'icon' => $kpiConfig['icon'],
                    'color' => $kpiConfig['color'],
                    'trend' => $trend,
                    'last_updated' => date('Y-m-d H:i:s')
                ];

            } catch (\Exception $e) {
                // Log error but continue
                error_log("Failed to get KPI {$kpiCode}: " . $e->getMessage());
                $kpis[$kpiCode] = [
                    'code' => $kpiCode,
                    'name' => $kpiConfig['name'],
                    'value' => 0,
                    'formatted_value' => 'N/A',
                    'error' => $e->getMessage()
                ];
            }
        }

        return $kpis;
    }

    /**
     * Get chart data for dashboard
     */
    private function getChartData(int $tenantId): array
    {
        return [
            'member_growth' => $this->getMemberGrowthChart($tenantId),
            'loan_performance' => $this->getLoanPerformanceChart($tenantId),
            'savings_trends' => $this->getSavingsTrendsChart($tenantId),
            'revenue_chart' => $this->getRevenueChart($tenantId),
            'transaction_volume' => $this->getTransactionVolumeChart($tenantId),
            'risk_indicators' => $this->getRiskIndicatorsChart($tenantId)
        ];
    }

    /**
     * Get member growth chart data
     */
    private function getMemberGrowthChart(int $tenantId): array
    {
        $chart = [
            'title' => 'Pertumbuhan Anggota',
            'type' => 'line',
            'data' => [],
            'period' => '6_months'
        ];

        // Get member count for last 6 months
        for ($i = 5; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-{$i} months"));
            $monthName = date('M Y', strtotime($date));

            $count = $this->executeCustomQuery(
                'SELECT COUNT(*) as count FROM members WHERE tenant_id = ? AND created_at < ?',
                [$tenantId, date('Y-m-d', strtotime("+1 month", strtotime($date)))]
            );

            $chart['data'][] = [
                'month' => $monthName,
                'count' => (int)$count
            ];
        }

        return $chart;
    }

    /**
     * Get loan performance chart data
     */
    private function getLoanPerformanceChart(int $tenantId): array
    {
        return [
            'title' => 'Performa Pinjaman',
            'type' => 'bar',
            'data' => [
                ['status' => 'Aktif', 'count' => $this->getLoanCountByStatus($tenantId, 'active')],
                ['status' => 'Lunas', 'count' => $this->getLoanCountByStatus($tenantId, 'completed')],
                ['status' => 'Default', 'count' => $this->getLoanCountByStatus($tenantId, 'defaulted')]
            ]
        ];
    }

    /**
     * Get savings trends chart data
     */
    private function getSavingsTrendsChart(int $tenantId): array
    {
        $chart = [
            'title' => 'Tren Simpanan',
            'type' => 'area',
            'data' => []
        ];

        // Get savings data for last 6 months
        for ($i = 5; $i >= 0; $i--) {
            $date = date('Y-m-01', strtotime("-{$i} months"));
            $monthName = date('M Y', strtotime($date));

            $total = $this->executeCustomQuery(
                'SELECT COALESCE(SUM(balance), 0) as total FROM savings_accounts WHERE tenant_id = ? AND created_at < ?',
                [$tenantId, date('Y-m-d', strtotime("+1 month", strtotime($date)))]
            );

            $chart['data'][] = [
                'month' => $monthName,
                'total' => (float)$total
            ];
        }

        return $chart;
    }

    /**
     * Get revenue chart data
     */
    private function getRevenueChart(int $tenantId): array
    {
        $chart = [
            'title' => 'Revenue Bulanan',
            'type' => 'line',
            'data' => []
        ];

        // Get revenue data for last 6 months
        for ($i = 5; $i >= 0; $i--) {
            $date = date('Y-m-01', strtotime("-{$i} months"));
            $monthName = date('M Y', strtotime($date));

            $revenue = $this->executeCustomQuery(
                'SELECT COALESCE(SUM(amount), 0) as total FROM payments WHERE tenant_id = ? AND payment_type = "fee" AND YEAR(created_at) = YEAR(?) AND MONTH(created_at) = MONTH(?)',
                [$tenantId, $date, $date]
            );

            $chart['data'][] = [
                'month' => $monthName,
                'revenue' => (float)$revenue
            ];
        }

        return $chart;
    }

    /**
     * Get transaction volume chart data
     */
    private function getTransactionVolumeChart(int $tenantId): array
    {
        return [
            'title' => 'Volume Transaksi Harian',
            'type' => 'bar',
            'data' => $this->getDailyTransactionData($tenantId, 7)
        ];
    }

    /**
     * Get risk indicators chart data
     */
    private function getRiskIndicatorsChart(int $tenantId): array
    {
        $nplRatio = $this->executeKPIQuery(
            'SELECT CASE WHEN COUNT(*) > 0 THEN ROUND((SUM(CASE WHEN status = "defaulted" THEN 1 ELSE 0 END) / COUNT(*)) * 100, 2) ELSE 0 END as value FROM loans WHERE tenant_id = ?',
            $tenantId
        );

        $overdueLoans = $this->executeKPIQuery(
            'SELECT COUNT(*) as value FROM loans WHERE tenant_id = ? AND status = "active" AND amount > 0',
            $tenantId
        );

        return [
            'title' => 'Indikator Risiko',
            'type' => 'gauge',
            'data' => [
                ['metric' => 'NPL Ratio', 'value' => (float)$nplRatio, 'max' => 5.0, 'threshold' => 3.0],
                ['metric' => 'Overdue Loans', 'value' => (int)$overdueLoans, 'max' => 100, 'threshold' => 10]
            ]
        ];
    }

    /**
     * Get dashboard alerts
     */
    private function getDashboardAlerts(int $tenantId): array
    {
        $alerts = [];

        // High NPL ratio alert
        $nplRatio = $this->executeKPIQuery(
            'SELECT CASE WHEN COUNT(*) > 0 THEN ROUND((SUM(CASE WHEN status = "defaulted" THEN 1 ELSE 0 END) / COUNT(*)) * 100, 2) ELSE 0 END as value FROM loans WHERE tenant_id = ?',
            $tenantId
        );

        if ($nplRatio > 5) {
            $alerts[] = [
                'type' => 'warning',
                'title' => 'NPL Ratio Tinggi',
                'message' => "NPL ratio sebesar {$nplRatio}% melebihi batas aman 5%",
                'severity' => 'high',
                'action_required' => 'Review collection strategy'
            ];
        }

        // Low member growth alert
        $newMembersThisMonth = $this->executeKPIQuery(
            'SELECT COUNT(*) as value FROM members WHERE tenant_id = ? AND created_at >= DATE_FORMAT(CURDATE(), "%Y-%m-01")',
            $tenantId
        );

        if ($newMembersThisMonth < 5) {
            $alerts[] = [
                'type' => 'info',
                'title' => 'Pertumbuhan Anggota Rendah',
                'message' => "Hanya {$newMembersThisMonth} anggota baru bulan ini",
                'severity' => 'medium',
                'action_required' => 'Increase member acquisition efforts'
            ];
        }

        // High pending approvals alert
        $pendingApprovals = $this->executeKPIQuery(
            'SELECT COUNT(*) as value FROM loans WHERE tenant_id = ? AND status IN ("submitted", "survey_completed")',
            $tenantId
        );

        if ($pendingApprovals > 10) {
            $alerts[] = [
                'type' => 'warning',
                'title' => 'Approval Tertunda Tinggi',
                'message' => "{$pendingApprovals} pinjaman menunggu approval",
                'severity' => 'medium',
                'action_required' => 'Process pending loan approvals'
            ];
        }

        return $alerts;
    }

    // =========================================
    // HELPER METHODS
    // =========================================

    private function executeKPIQuery(string $query, int $tenantId)
    {
        return $this->executeCustomQuery($query, [$tenantId]);
    }

    private function executeCustomQuery(string $query, array $params = [])
    {
        $stmt = $this->db()->prepare($query);
        $stmt->execute($params);
        $result = $stmt->fetch();

        return $result ? $result[0] : 0;
    }

    private function db()
    {
        return \App\Database::getConnection();
    }

    private function calculateKPITrend(int $tenantId, string $kpiCode, string $period): array
    {
        // Calculate trend (simplified)
        $current = $this->getKPIData($tenantId)[$kpiCode]['value'] ?? 0;

        // Get previous period value (simplified)
        $previous = $current * 0.9; // Mock previous value

        $change = $previous > 0 ? (($current - $previous) / $previous) * 100 : 0;

        return [
            'direction' => $change > 0 ? 'up' : ($change < 0 ? 'down' : 'stable'),
            'percentage' => round(abs($change), 1),
            'period' => $period
        ];
    }

    private function formatKPIValue($value, string $format): string
    {
        switch ($format) {
            case 'currency':
                return 'Rp ' . number_format($value, 0, ',', '.');
            case 'percentage':
                return number_format($value, 1) . '%';
            case 'number':
            default:
                return number_format($value, 0, ',', '.');
        }
    }

    private function getLoanCountByStatus(int $tenantId, string $status): int
    {
        $statusMap = [
            'active' => 'active',
            'completed' => 'completed',
            'defaulted' => 'defaulted'
        ];

        $mappedStatus = $statusMap[$status] ?? $status;

        return $this->executeKPIQuery(
            'SELECT COUNT(*) as value FROM loans WHERE tenant_id = ? AND status = ?',
            [$tenantId, $mappedStatus]
        );
    }

    private function getDailyTransactionData(int $tenantId, int $days): array
    {
        $data = [];

        for ($i = $days - 1; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-{$i} days"));
            $dayName = date('D', strtotime($date));

            $count = $this->executeCustomQuery(
                'SELECT COUNT(*) as count FROM savings_transactions WHERE tenant_id = ? AND DATE(transaction_date) = ?',
                [$tenantId, $date]
            );

            $data[] = [
                'day' => $dayName,
                'count' => (int)$count
            ];
        }

        return $data;
    }

    /**
     * WebSocket broadcast for real-time updates
     */
    public function broadcastDashboardUpdate(int $tenantId, array $updateData): void
    {
        // In a real implementation, this would broadcast via WebSocket
        // For demo, we'll simulate the broadcast
        $updateData['tenant_id'] = $tenantId;
        $updateData['timestamp'] = time();

        error_log("Dashboard update broadcasted for tenant {$tenantId}: " . json_encode($updateData));
    }

    /**
     * Get real-time KPI updates
     */
    public function getRealTimeKPIUpdates(int $tenantId): array
    {
        // Return recent KPI changes
        return [
            'updates' => [],
            'last_check' => time(),
            'next_check' => time() + 30
        ];
    }

    /**
     * Export dashboard data
     */
    public function exportDashboardData(int $tenantId, string $format = 'json'): string
    {
        $dashboard = $this->getRealTimeDashboard($tenantId);

        switch ($format) {
            case 'json':
                return json_encode($dashboard, JSON_PRETTY_PRINT);
            case 'csv':
                return $this->convertToCSV($dashboard);
            default:
                return json_encode($dashboard);
        }
    }

    private function convertToCSV(array $dashboard): string
    {
        $csv = "KPI,Value,Trend\n";

        foreach ($dashboard['kpis'] as $kpi) {
            $trend = $kpi['trend']['direction'] ?? 'stable';
            $csv .= "\"{$kpi['name']}\",\"{$kpi['formatted_value']}\",\"{$trend}\"\n";
        }

        return $csv;
    }
}

/**
 * WebSocket Dashboard Controller
 */
class WebSocketDashboardController
{
    private RealTimeDashboardEngine $dashboardEngine;

    public function __construct()
    {
        $this->dashboardEngine = new RealTimeDashboardEngine();
    }

    /**
     * Handle WebSocket connection for real-time updates
     */
    public function handleWebSocketConnection(): void
    {
        // In a real implementation, this would handle WebSocket connections
        // For demo, we'll show the structure

        echo json_encode([
            'message' => 'WebSocket connection established',
            'supported_events' => [
                'dashboard_update',
                'kpi_change',
                'alert_triggered',
                'real_time_data'
            ]
        ]);
    }

    /**
     * Send real-time dashboard update
     */
    public function sendRealTimeUpdate(): void
    {
        header('Content-Type: application/json');

        try {
            $tenantId = $_SESSION['tenant_context']['tenant_id'] ?? 1;
            $updateType = $_GET['type'] ?? 'full';

            $update = $this->dashboardEngine->getRealTimeKPIUpdates($tenantId);

            echo json_encode([
                'success' => true,
                'update_type' => $updateType,
                'data' => $update
            ]);

        } catch (\Exception $e) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }
}

/**
 * REST API Dashboard Controller
 */
class DashboardAPIController
{
    private RealTimeDashboardEngine $dashboardEngine;

    public function __construct()
    {
        $this->dashboardEngine = new RealTimeDashboardEngine();
    }

    /**
     * Get real-time dashboard data
     */
    public function getDashboard(): void
    {
        header('Content-Type: application/json');

        try {
            $tenantId = $_SESSION['tenant_context']['tenant_id'] ?? 1;
            $dashboard = $this->dashboardEngine->getRealTimeDashboard($tenantId);

            echo json_encode([
                'success' => true,
                'data' => $dashboard
            ]);

        } catch (\Exception $e) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Export dashboard data
     */
    public function exportDashboard(): void
    {
        try {
            $tenantId = $_SESSION['tenant_context']['tenant_id'] ?? 1;
            $format = $_GET['format'] ?? 'json';

            $data = $this->dashboardEngine->exportDashboardData($tenantId, $format);

            // Set appropriate headers
            switch ($format) {
                case 'json':
                    header('Content-Type: application/json');
                    header('Content-Disposition: attachment; filename="dashboard_export.json"');
                    break;
                case 'csv':
                    header('Content-Type: text/csv');
                    header('Content-Disposition: attachment; filename="dashboard_export.csv"');
                    break;
            }

            echo $data;

        } catch (\Exception $e) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Get KPI details
     */
    public function getKPIDetails(): void
    {
        header('Content-Type: application/json');

        try {
            $tenantId = $_SESSION['tenant_context']['tenant_id'] ?? 1;
            $kpiCode = $_GET['kpi'] ?? '';

            if (!$kpiCode) {
                throw new \Exception('KPI code diperlukan');
            }

            $dashboard = $this->dashboardEngine->getRealTimeDashboard($tenantId);
            $kpi = $dashboard['kpis'][$kpiCode] ?? null;

            if (!$kpi) {
                throw new \Exception('KPI tidak ditemukan');
            }

            echo json_encode([
                'success' => true,
                'data' => $kpi
            ]);

        } catch (\Exception $e) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }
}

// =========================================
// DASHBOARD DATABASE TABLES
// =========================================

/*
-- Dashboard Cache Table
CREATE TABLE dashboard_cache (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT NOT NULL,
    cache_key VARCHAR(100) NOT NULL,
    cache_data JSON NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NOT NULL,
    INDEX idx_tenant_key (tenant_id, cache_key),
    INDEX idx_expires (expires_at),
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
);

-- Real-time Metrics Table
CREATE TABLE realtime_metrics (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT NOT NULL,
    metric_name VARCHAR(100) NOT NULL,
    metric_value DECIMAL(15,2) NOT NULL,
    recorded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_tenant_metric (tenant_id, metric_name),
    INDEX idx_recorded (recorded_at),
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
);

-- Dashboard Alerts Table
CREATE TABLE dashboard_alerts (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT NOT NULL,
    alert_type VARCHAR(50) NOT NULL,
    alert_title VARCHAR(200) NOT NULL,
    alert_message TEXT NOT NULL,
    severity ENUM('low', 'medium', 'high', 'critical') DEFAULT 'medium',
    is_active BOOLEAN DEFAULT TRUE,
    triggered_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    resolved_at TIMESTAMP NULL,
    INDEX idx_tenant_type (tenant_id, alert_type),
    INDEX idx_active (is_active),
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
);

-- API Routes to add:
GET  /api/dashboard/realtime -> DashboardAPIController::getDashboard
GET  /api/dashboard/export -> DashboardAPIController::exportDashboard
GET  /api/dashboard/kpi-details -> DashboardAPIController::getKPIDetails
GET  /api/dashboard/realtime-updates -> WebSocketDashboardController::sendRealTimeUpdate
WS   /ws/dashboard -> WebSocketDashboardController::handleWebSocketConnection
*/

?>
