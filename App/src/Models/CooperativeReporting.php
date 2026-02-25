<?php
namespace App\Models;

class CooperativeReporting extends Model
{
    protected string $table = 'cooperative_reports';
    protected array $fillable = [
        'tenant_id', 'report_type', 'report_period', 'report_year',
        'data', 'generated_at', 'generated_by'
    ];
    protected array $casts = [
        'tenant_id' => 'int',
        'report_year' => 'int',
        'data' => 'array',
        'generated_at' => 'datetime',
        'generated_by' => 'int',
        'created_at' => 'datetime'
    ];

    /**
     * Generate monthly activity report for a cooperative
     */
    public function generateMonthlyReport(int $tenantId, int $month, int $year, int $generatedBy): int
    {
        $tenantModel = new Tenant();
        $tenant = $tenantModel->find($tenantId);

        if (!$tenant) {
            throw new \Exception('Tenant not found');
        }

        $reportData = $this->collectMonthlyData($tenant, $month, $year);

        return $this->create([
            'tenant_id' => $tenantId,
            'report_type' => 'monthly_activity',
            'report_period' => $month,
            'report_year' => $year,
            'data' => json_encode($reportData),
            'generated_at' => date('Y-m-d H:i:s'),
            'generated_by' => $generatedBy
        ]);
    }

    /**
     * Generate annual report for a cooperative
     */
    public function generateAnnualReport(int $tenantId, int $year, int $generatedBy): int
    {
        $tenantModel = new Tenant();
        $tenant = $tenantModel->find($tenantId);

        if (!$tenant) {
            throw new \Exception('Tenant not found');
        }

        $reportData = $this->collectAnnualData($tenant, $year);

        return $this->create([
            'tenant_id' => $tenantId,
            'report_type' => 'annual_report',
            'report_period' => null,
            'report_year' => $year,
            'data' => json_encode($reportData),
            'generated_at' => date('Y-m-d H:i:s'),
            'generated_by' => $generatedBy
        ]);
    }

    /**
     * Collect monthly activity data
     */
    private function collectMonthlyData(array $tenant, int $month, int $year): array
    {
        $tenantDb = $this->getTenantDatabase($tenant['slug']);

        $startDate = sprintf('%04d-%02d-01', $year, $month);
        $endDate = date('Y-m-t', strtotime($startDate));

        try {
            // Member statistics
            $stmt = $tenantDb->prepare("
                SELECT
                    COUNT(*) as total_members,
                    COUNT(CASE WHEN status = 'active' THEN 1 END) as active_members,
                    COUNT(CASE WHEN created_at BETWEEN ? AND ? THEN 1 END) as new_members
                FROM members
            ");
            $stmt->execute([$startDate, $endDate]);
            $memberStats = $stmt->fetch();

            // Loan statistics
            $stmt = $tenantDb->prepare("
                SELECT
                    COUNT(*) as total_loans,
                    COUNT(CASE WHEN status = 'disbursed' THEN 1 END) as disbursed_loans,
                    COUNT(CASE WHEN created_at BETWEEN ? AND ? THEN 1 END) as new_loans,
                    SUM(CASE WHEN status = 'disbursed' THEN amount ELSE 0 END) as total_disbursed
                FROM loans
            ");
            $stmt->execute([$startDate, $endDate]);
            $loanStats = $stmt->fetch();

            // Savings statistics
            $stmt = $tenantDb->prepare("
                SELECT
                    COUNT(DISTINCT member_id) as active_savers,
                    COUNT(CASE WHEN type = 'pokok' THEN 1 END) as pokok_accounts,
                    COUNT(CASE WHEN type = 'wajib' THEN 1 END) as wajib_accounts,
                    COUNT(CASE WHEN type = 'sukarela' THEN 1 END) as sukarela_accounts,
                    SUM(balance) as total_savings_balance
                FROM savings_accounts
                WHERE status = 'active'
            ");
            $stmt->execute();
            $savingsStats = $stmt->fetch();

            // Transaction statistics
            $stmt = $tenantDb->prepare("
                SELECT
                    COUNT(CASE WHEN type = 'deposit' THEN 1 END) as deposit_transactions,
                    COUNT(CASE WHEN type = 'withdrawal' THEN 1 END) as withdrawal_transactions,
                    SUM(CASE WHEN type = 'deposit' THEN amount ELSE 0 END) as total_deposits,
                    SUM(CASE WHEN type = 'withdrawal' THEN amount ELSE 0 END) as total_withdrawals
                FROM savings_transactions
                WHERE transaction_date BETWEEN ? AND ?
            ");
            $stmt->execute([$startDate, $endDate]);
            $transactionStats = $stmt->fetch();

            // Repayment statistics
            $stmt = $tenantDb->prepare("
                SELECT
                    COUNT(*) as total_repayments,
                    COUNT(CASE WHEN status = 'paid' THEN 1 END) as paid_repayments,
                    SUM(amount_paid) as total_collected
                FROM repayments
                WHERE paid_date BETWEEN ? AND ?
            ");
            $stmt->execute([$startDate, $endDate]);
            $repaymentStats = $stmt->fetch();

            return [
                'period' => [
                    'month' => $month,
                    'year' => $year,
                    'start_date' => $startDate,
                    'end_date' => $endDate
                ],
                'members' => $memberStats,
                'loans' => $loanStats,
                'savings' => $savingsStats,
                'transactions' => $transactionStats,
                'repayments' => $repaymentStats,
                'summary' => [
                    'total_revenue' => ($repaymentStats['total_collected'] ?? 0),
                    'total_assets' => ($savingsStats['total_savings_balance'] ?? 0) + ($loanStats['total_disbursed'] ?? 0),
                    'growth_rate' => $this->calculateGrowthRate($tenant, $month, $year)
                ]
            ];

        } catch (\Exception $e) {
            return ['error' => 'Failed to collect monthly data: ' . $e->getMessage()];
        }
    }

    /**
     * Collect annual data
     */
    private function collectAnnualData(array $tenant, int $year): array
    {
        $monthlyReports = [];

        // Collect data for each month
        for ($month = 1; $month <= 12; $month++) {
            $monthlyReports[$month] = $this->collectMonthlyData($tenant, $month, $year);
        }

        // Aggregate annual data
        $annualSummary = [
            'total_members' => 0,
            'new_members' => 0,
            'total_loans' => 0,
            'disbursed_loans' => 0,
            'total_disbursed' => 0,
            'total_savings_balance' => 0,
            'total_deposits' => 0,
            'total_withdrawals' => 0,
            'total_collections' => 0,
            'total_revenue' => 0
        ];

        foreach ($monthlyReports as $monthData) {
            if (isset($monthData['members'])) {
                $annualSummary['total_members'] = max($annualSummary['total_members'], $monthData['members']['total_members'] ?? 0);
                $annualSummary['new_members'] += $monthData['members']['new_members'] ?? 0;
            }

            if (isset($monthData['loans'])) {
                $annualSummary['total_loans'] += $monthData['loans']['new_loans'] ?? 0;
                $annualSummary['disbursed_loans'] += $monthData['loans']['disbursed_loans'] ?? 0;
                $annualSummary['total_disbursed'] += $monthData['loans']['total_disbursed'] ?? 0;
            }

            if (isset($monthData['savings'])) {
                $annualSummary['total_savings_balance'] = $monthData['savings']['total_savings_balance'] ?? 0;
            }

            if (isset($monthData['transactions'])) {
                $annualSummary['total_deposits'] += $monthData['transactions']['total_deposits'] ?? 0;
                $annualSummary['total_withdrawals'] += $monthData['transactions']['total_withdrawals'] ?? 0;
            }

            if (isset($monthData['repayments'])) {
                $annualSummary['total_collections'] += $monthData['repayments']['total_collected'] ?? 0;
            }

            if (isset($monthData['summary'])) {
                $annualSummary['total_revenue'] += $monthData['summary']['total_revenue'] ?? 0;
            }
        }

        return [
            'year' => $year,
            'monthly_reports' => $monthlyReports,
            'annual_summary' => $annualSummary,
            'performance_metrics' => [
                'membership_growth' => $this->calculateMembershipGrowth($tenant, $year),
                'loan_portfolio_growth' => $this->calculatePortfolioGrowth($tenant, $year),
                'savings_growth' => $this->calculateSavingsGrowth($tenant, $year),
                'profitability' => $this->calculateProfitability($tenant, $year)
            ]
        ];
    }

    /**
     * Calculate growth rate
     */
    private function calculateGrowthRate(array $tenant, int $month, int $year): float
    {
        // Compare with previous month
        $prevMonth = $month - 1;
        $prevYear = $year;

        if ($prevMonth < 1) {
            $prevMonth = 12;
            $prevYear--;
        }

        $prevData = $this->collectMonthlyData($tenant, $prevMonth, $prevYear);
        $currentData = $this->collectMonthlyData($tenant, $month, $year);

        $prevRevenue = $prevData['summary']['total_revenue'] ?? 0;
        $currentRevenue = $currentData['summary']['total_revenue'] ?? 0;

        if ($prevRevenue == 0) {
            return $currentRevenue > 0 ? 100.0 : 0.0;
        }

        return round((($currentRevenue - $prevRevenue) / $prevRevenue) * 100, 2);
    }

    /**
     * Calculate membership growth
     */
    private function calculateMembershipGrowth(array $tenant, int $year): float
    {
        $tenantDb = $this->getTenantDatabase($tenant['slug']);

        $stmt = $tenantDb->prepare("
            SELECT
                COUNT(CASE WHEN YEAR(created_at) = ? THEN 1 END) as current_year,
                COUNT(CASE WHEN YEAR(created_at) = ? THEN 1 END) as previous_year
            FROM members
        ");
        $stmt->execute([$year, $year - 1]);
        $result = $stmt->fetch();

        $current = $result['current_year'] ?? 0;
        $previous = $result['previous_year'] ?? 0;

        if ($previous == 0) {
            return $current > 0 ? 100.0 : 0.0;
        }

        return round((($current - $previous) / $previous) * 100, 2);
    }

    /**
     * Calculate portfolio growth
     */
    private function calculatePortfolioGrowth(array $tenant, int $year): float
    {
        $tenantDb = $this->getTenantDatabase($tenant['slug']);

        $stmt = $tenantDb->prepare("
            SELECT
                SUM(CASE WHEN YEAR(created_at) = ? AND status = 'disbursed' THEN amount ELSE 0 END) as current_year,
                SUM(CASE WHEN YEAR(created_at) = ? AND status = 'disbursed' THEN amount ELSE 0 END) as previous_year
            FROM loans
        ");
        $stmt->execute([$year, $year - 1]);
        $result = $stmt->fetch();

        $current = $result['current_year'] ?? 0;
        $previous = $result['previous_year'] ?? 0;

        if ($previous == 0) {
            return $current > 0 ? 100.0 : 0.0;
        }

        return round((($current - $previous) / $previous) * 100, 2);
    }

    /**
     * Calculate savings growth
     */
    private function calculateSavingsGrowth(array $tenant, int $year): float
    {
        // For simplicity, compare end-of-year balances
        // In a real implementation, this would track monthly balances
        return 0.0; // Placeholder
    }

    /**
     * Calculate profitability
     */
    private function calculateProfitability(array $tenant, int $year): float
    {
        $tenantDb = $this->getTenantDatabase($tenant['slug']);

        // Simple profitability calculation: interest income - operating expenses
        $stmt = $tenantDb->prepare("
            SELECT
                SUM(CASE WHEN status = 'paid' THEN amount_paid ELSE 0 END) as total_collections
            FROM repayments
            WHERE YEAR(paid_date) = ?
        ");
        $stmt->execute([$year]);
        $result = $stmt->fetch();

        $revenue = $result['total_collections'] ?? 0;
        $expenses = $revenue * 0.3; // Assume 30% operating expenses

        $profit = $revenue - $expenses;
        $roi = $revenue > 0 ? ($profit / $revenue) * 100 : 0;

        return round($roi, 2);
    }

    /**
     * Get tenant database connection
     */
    private function getTenantDatabase(string $slug): \PDO
    {
        $tenantDbName = "tenant_{$slug}";
        $host = $_ENV['DB_HOST'] ?? 'localhost';
        $port = $_ENV['DB_PORT'] ?? '3306';
        $user = $_ENV['DB_USER'] ?? 'root';
        $pass = $_ENV['DB_PASS'] ?? '';

        $dsn = "mysql:host=$host;port=$port;dbname=$tenantDbName;charset=utf8mb4";
        $options = [
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
            \PDO::ATTR_EMULATE_PREPARES => false,
        ];

        return new \PDO($dsn, $user, $pass, $options);
    }

    /**
     * Get reports by tenant
     */
    public function getByTenant(int $tenantId, string $type = null): array
    {
        $conditions = ['tenant_id' => $tenantId];
        if ($type) {
            $conditions['report_type'] = $type;
        }

        return $this->findWhere($conditions, ['created_at' => 'DESC']);
    }

    /**
     * Get report by ID with data decoded
     */
    public function findWithData(int $id): ?array
    {
        $report = $this->find($id);
        if (!$report) {
            return null;
        }

        $report['data'] = json_decode($report['data'] ?? '{}', true);
        return $report;
    }
}
