<?php
namespace App\Models;

class Supervision extends Model
{
    /**
     * Calculate KPI for cooperative management
     */
    public function calculateKPIs(int $tenantId, string $period = 'monthly'): array
    {
        $currentDate = date('Y-m-d');
        $periodStart = $this->getPeriodStart($period);
        $periodEnd = $currentDate;

        $kpis = [
            'portfolio_quality' => $this->calculatePortfolioQuality($tenantId, $periodStart, $periodEnd),
            'financial_performance' => $this->calculateFinancialPerformance($tenantId, $periodStart, $periodEnd),
            'operational_efficiency' => $this->calculateOperationalEfficiency($tenantId, $periodStart, $periodEnd),
            'member_satisfaction' => $this->calculateMemberSatisfaction($tenantId, $periodStart, $periodEnd),
            'regulatory_compliance' => $this->calculateRegulatoryCompliance($tenantId, $periodStart, $periodEnd),
            'risk_management' => $this->calculateRiskManagement($tenantId, $periodStart, $periodEnd)
        ];

        // Calculate overall KPI score
        $weights = [
            'portfolio_quality' => 0.25,
            'financial_performance' => 0.25,
            'operational_efficiency' => 0.20,
            'member_satisfaction' => 0.15,
            'regulatory_compliance' => 0.10,
            'risk_management' => 0.05
        ];

        $overallScore = 0;
        foreach ($kpis as $key => $kpi) {
            $overallScore += ($kpi['score'] ?? 0) * $weights[$key];
        }

        $kpis['overall'] = [
            'score' => round($overallScore, 2),
            'grade' => $this->getKPIGrade($overallScore),
            'period' => $period,
            'calculated_at' => date('Y-m-d H:i:s')
        ];

        return $kpis;
    }

    /**
     * Calculate portfolio quality KPI
     */
    private function calculatePortfolioQuality(int $tenantId, string $startDate, string $endDate): array
    {
        // Get loan statistics
        $loanModel = new Loan();
        $loanStats = $loanModel->getLoanStatistics();

        $totalLoans = $loanStats['total_loans'] ?? 0;
        $activeLoans = $loanStats['active_loans'] ?? 0;
        $defaultedLoans = $loanStats['defaulted_loans'] ?? 0;

        // Calculate NPL ratio
        $nplRatio = $totalLoans > 0 ? ($defaultedLoans / $totalLoans) * 100 : 0;

        // Portfolio quality score (lower NPL = higher score)
        $score = max(0, 100 - ($nplRatio * 2)); // Max 50% NPL reduces score to 0

        return [
            'total_loans' => $totalLoans,
            'active_loans' => $activeLoans,
            'defaulted_loans' => $defaultedLoans,
            'npl_ratio' => round($nplRatio, 2),
            'score' => round($score, 2),
            'target' => '< 5%',
            'status' => $nplRatio <= 5 ? 'good' : ($nplRatio <= 10 ? 'warning' : 'critical')
        ];
    }

    /**
     * Calculate financial performance KPI
     */
    private function calculateFinancialPerformance(int $tenantId, string $startDate, string $endDate): array
    {
        // Get accounting data
        $accountingModel = new Accounting();
        $incomeStatement = $accountingModel->generateIncomeStatement($startDate, $endDate);

        $netIncome = $incomeStatement['net_income'] ?? 0;
        $totalRevenue = $incomeStatement['total_revenue'] ?? 0;

        // Profit margin
        $profitMargin = $totalRevenue > 0 ? ($netIncome / $totalRevenue) * 100 : 0;

        // Financial performance score
        $score = 50; // Base score
        if ($profitMargin >= 20) $score = 100;
        elseif ($profitMargin >= 10) $score = 80;
        elseif ($profitMargin >= 5) $score = 60;
        elseif ($profitMargin >= 0) $score = 40;
        else $score = 20;

        return [
            'net_income' => $netIncome,
            'total_revenue' => $totalRevenue,
            'profit_margin' => round($profitMargin, 2),
            'score' => $score,
            'target' => '> 10%',
            'status' => $profitMargin >= 10 ? 'good' : ($profitMargin >= 5 ? 'warning' : 'critical')
        ];
    }

    /**
     * Calculate operational efficiency KPI
     */
    private function calculateOperationalEfficiency(int $tenantId, string $startDate, string $endDate): array
    {
        // Get operational metrics
        $stmt = $this->db->prepare("
            SELECT
                COUNT(CASE WHEN status IN ('approved', 'disbursed') THEN 1 END) as processed_loans,
                COUNT(CASE WHEN status = 'submitted' THEN 1 END) as pending_loans,
                AVG(TIMESTAMPDIFF(DAY, application_date, disbursement_date)) as avg_processing_days
            FROM loans
            WHERE application_date BETWEEN ? AND ?
        ");
        $stmt->execute([$startDate, $endDate]);
        $metrics = $stmt->fetch();

        $processedLoans = $metrics['processed_loans'] ?? 0;
        $pendingLoans = $metrics['pending_loans'] ?? 0;
        $avgProcessingDays = $metrics['avg_processing_days'] ?? 0;
        $totalLoans = $processedLoans + $pendingLoans;

        // Processing efficiency (lower processing time = higher score)
        $efficiencyScore = 100;
        if ($avgProcessingDays > 14) $efficiencyScore = 60;
        elseif ($avgProcessingDays > 7) $efficiencyScore = 80;
        elseif ($avgProcessingDays > 3) $efficiencyScore = 90;

        // Backlog ratio
        $backlogRatio = $totalLoans > 0 ? ($pendingLoans / $totalLoans) * 100 : 0;

        return [
            'processed_loans' => $processedLoans,
            'pending_loans' => $pendingLoans,
            'avg_processing_days' => round($avgProcessingDays, 1),
            'backlog_ratio' => round($backlogRatio, 2),
            'score' => $efficiencyScore,
            'target' => '< 7 days',
            'status' => $avgProcessingDays <= 7 ? 'good' : ($avgProcessingDays <= 14 ? 'warning' : 'critical')
        ];
    }

    /**
     * Calculate member satisfaction KPI
     */
    private function calculateMemberSatisfaction(int $tenantId, string $startDate, string $endDate): array
    {
        // Get member metrics
        $memberModel = new Member();
        $memberStats = $memberModel->getMemberStatistics();

        $totalMembers = $memberStats['total_members'] ?? 0;
        $activeMembers = $memberStats['active_members'] ?? 0;

        // Member retention rate
        $retentionRate = $totalMembers > 0 ? ($activeMembers / $totalMembers) * 100 : 0;

        // Satisfaction score based on retention and activity
        $score = min(100, $retentionRate + 20); // Add base satisfaction score

        return [
            'total_members' => $totalMembers,
            'active_members' => $activeMembers,
            'retention_rate' => round($retentionRate, 2),
            'score' => round($score, 2),
            'target' => '> 90%',
            'status' => $retentionRate >= 90 ? 'good' : ($retentionRate >= 80 ? 'warning' : 'critical')
        ];
    }

    /**
     * Calculate regulatory compliance KPI
     */
    private function calculateRegulatoryCompliance(int $tenantId, string $startDate, string $endDate): array
    {
        // Get compliance checks
        $stmt = $this->db->prepare("
            SELECT
                COUNT(*) as total_checks,
                COUNT(CASE WHEN status = 'passed' THEN 1 END) as passed_checks,
                COUNT(CASE WHEN status = 'failed' THEN 1 END) as failed_checks
            FROM compliance_checks
            WHERE created_at BETWEEN ? AND ?
        ");
        $stmt->execute([$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
        $compliance = $stmt->fetch();

        $totalChecks = $compliance['total_checks'] ?? 0;
        $passedChecks = $compliance['passed_checks'] ?? 0;
        $failedChecks = $compliance['failed_checks'] ?? 0;

        $complianceRate = $totalChecks > 0 ? ($passedChecks / $totalChecks) * 100 : 100;

        // Compliance score
        $score = round($complianceRate, 2);

        return [
            'total_checks' => $totalChecks,
            'passed_checks' => $passedChecks,
            'failed_checks' => $failedChecks,
            'compliance_rate' => $score,
            'score' => $score,
            'target' => '> 95%',
            'status' => $complianceRate >= 95 ? 'good' : ($complianceRate >= 85 ? 'warning' : 'critical')
        ];
    }

    /**
     * Calculate risk management KPI
     */
    private function calculateRiskManagement(int $tenantId, string $startDate, string $endDate): array
    {
        // Get risk assessments
        $stmt = $this->db->prepare("
            SELECT
                COUNT(*) as total_assessments,
                COUNT(CASE WHEN risk_level = 'low' THEN 1 END) as low_risk,
                COUNT(CASE WHEN risk_level = 'medium' THEN 1 END) as medium_risk,
                COUNT(CASE WHEN risk_level = 'high' THEN 1 END) as high_risk,
                COUNT(CASE WHEN risk_level = 'critical' THEN 1 END) as critical_risk
            FROM risk_assessments
            WHERE assessed_at BETWEEN ? AND ?
        ");
        $stmt->execute([$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
        $risks = $stmt->fetch();

        $totalAssessments = $risks['total_assessments'] ?? 0;
        $criticalRisks = $risks['critical_risk'] ?? 0;
        $highRisks = $risks['high_risk'] ?? 0;

        // Risk score (lower risk = higher score)
        $riskScore = 100;
        if ($criticalRisks > 0) $riskScore = 30;
        elseif ($highRisks > 2) $riskScore = 60;
        elseif ($highRisks > 0) $riskScore = 80;

        return [
            'total_assessments' => $totalAssessments,
            'critical_risks' => $criticalRisks,
            'high_risks' => $highRisks,
            'score' => $riskScore,
            'target' => 'Minimize critical/high risks',
            'status' => $criticalRisks == 0 && $highRisks <= 1 ? 'good' : ($highRisks <= 3 ? 'warning' : 'critical')
        ];
    }

    /**
     * Get period start date
     */
    private function getPeriodStart(string $period): string
    {
        switch ($period) {
            case 'weekly':
                return date('Y-m-d', strtotime('-7 days'));
            case 'monthly':
                return date('Y-m-d', strtotime('-30 days'));
            case 'quarterly':
                return date('Y-m-d', strtotime('-90 days'));
            case 'yearly':
                return date('Y-m-d', strtotime('-365 days'));
            default:
                return date('Y-m-d', strtotime('-30 days'));
        }
    }

    /**
     * Get KPI grade
     */
    private function getKPIGrade(float $score): string
    {
        if ($score >= 90) return 'Excellent (A)';
        if ($score >= 80) return 'Very Good (B+)';
        if ($score >= 70) return 'Good (B)';
        if ($score >= 60) return 'Fair (C)';
        if ($score >= 50) return 'Poor (D)';
        return 'Critical (F)';
    }

    /**
     * Generate alerts for KPI issues
     */
    public function generateAlerts(int $tenantId): array
    {
        $kpis = $this->calculateKPIs($tenantId);
        $alerts = [];

        foreach ($kpis as $key => $kpi) {
            if ($key === 'overall') continue;

            if (isset($kpi['status']) && $kpi['status'] === 'critical') {
                $alerts[] = [
                    'type' => 'critical',
                    'category' => $key,
                    'message' => "Critical issue in {$key}: " . ($kpi['npl_ratio'] ?? $kpi['compliance_rate'] ?? 'Check details'),
                    'priority' => 'high',
                    'created_at' => date('Y-m-d H:i:s')
                ];
            } elseif (isset($kpi['status']) && $kpi['status'] === 'warning') {
                $alerts[] = [
                    'type' => 'warning',
                    'category' => $key,
                    'message' => "Warning in {$key}: Performance needs attention",
                    'priority' => 'medium',
                    'created_at' => date('Y-m-d H:i:s')
                ];
            }
        }

        return $alerts;
    }

    /**
     * Log audit trail
     */
    public function logAuditAction(array $auditData): int
    {
        $auditModel = new AuditLog();

        return $auditModel->create([
            'user_id' => $auditData['user_id'] ?? null,
            'action' => $auditData['action'],
            'resource_type' => $auditData['resource_type'] ?? null,
            'resource_id' => $auditData['resource_id'] ?? null,
            'old_values' => $auditData['old_values'] ?? null,
            'new_values' => $auditData['new_values'] ?? null,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null
        ]);
    }

    /**
     * Get audit trail for resource
     */
    public function getAuditTrail(string $resourceType, int $resourceId, int $limit = 50): array
    {
        $auditModel = new AuditLog();

        return $auditModel->findWhere([
            'resource_type' => $resourceType,
            'resource_id' => $resourceId
        ], ['created_at' => 'DESC'], $limit);
    }

    /**
     * Generate compliance report
     */
    public function generateComplianceReport(int $tenantId, string $period): array
    {
        $periodStart = $this->getPeriodStart($period);
        $periodEnd = date('Y-m-d');

        $stmt = $this->db->prepare("
            SELECT * FROM compliance_checks
            WHERE checked_at BETWEEN ? AND ?
            ORDER BY checked_at DESC
        ");
        $stmt->execute([$periodStart . ' 00:00:00', $periodEnd . ' 23:59:59']);
        $checks = $stmt->fetchAll();

        $passed = count(array_filter($checks, fn($c) => $c['status'] === 'passed'));
        $failed = count(array_filter($checks, fn($c) => $c['status'] === 'failed'));
        $total = count($checks);

        $complianceRate = $total > 0 ? round(($passed / $total) * 100, 2) : 100;

        return [
            'period' => ['start' => $periodStart, 'end' => $periodEnd],
            'total_checks' => $total,
            'passed_checks' => $passed,
            'failed_checks' => $failed,
            'compliance_rate' => $complianceRate,
            'status' => $complianceRate >= 95 ? 'Compliant' : 'Non-Compliant',
            'checks' => $checks,
            'generated_at' => date('Y-m-d H:i:s')
        ];
    }
}
