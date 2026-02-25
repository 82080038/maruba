<?php
/**
 * Analytics Controller - Advanced Business Intelligence & Predictive Analytics
 * Real-time dashboards, NPL forecasting, customer segmentation, risk scoring
 */

namespace App\Controllers;

use App\Database;
use App\Models\Audit;

class AnalyticsController
{
    private $db;
    private $audit;

    public function __construct()
    {
        $this->db = Database::getConnection();
        $this->audit = new Audit();
    }

    /**
     * Get current tenant ID
     */
    private function getCurrentTenantId()
    {
        if (!isset($_SESSION['tenant_id'])) {
            throw new \Exception('Tenant context not found');
        }
        return $_SESSION['tenant_id'];
    }

    /**
     * Get current user ID
     */
    private function getCurrentUserId()
    {
        return $_SESSION['user_id'] ?? null;
    }

    /**
     * Real-time Dashboard
     */
    public function dashboard()
    {
        $tenantId = $this->getCurrentTenantId();
        $role = $_SESSION['role'] ?? 'user';

        // Get dashboard configuration for user's role
        $dashboard = $this->getDashboardForRole($tenantId, $role);

        // Get real-time KPI data
        $kpis = $this->getRealtimeKPIs($tenantId);

        // Get dashboard widgets data
        $widgets = [];
        foreach (($dashboard['config']['widgets'] ?? []) as $widget) {
            $widgets[$widget] = $this->getWidgetData($tenantId, $widget);
        }

        require_once __DIR__ . '/../Views/analytics/dashboard.php';
    }

    /**
     * Get dashboard configuration for user role
     */
    private function getDashboardForRole($tenantId, $role)
    {
        $stmt = $this->db->prepare("
            SELECT * FROM analytics_dashboards
            WHERE tenant_id = ? AND JSON_CONTAINS(role_access, ?)
            ORDER BY created_at DESC LIMIT 1
        ");
        $stmt->execute([$tenantId, json_encode([$role])]);

        $dashboard = $stmt->fetch();

        // Return default dashboard if none configured
        if (!$dashboard) {
            $dashboard = [
                'name' => 'Default Dashboard',
                'config' => [
                    'widgets' => ['total_members', 'total_loans', 'total_savings', 'npl_ratio', 'monthly_revenue']
                ]
            ];
        }

        return $dashboard;
    }

    /**
     * Get real-time KPI data
     */
    private function getRealtimeKPIs($tenantId)
    {
        $kpis = [];

        // Total Members
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM members WHERE tenant_id = ? AND status = 'active'");
        $stmt->execute([$tenantId]);
        $kpis['total_members'] = $stmt->fetchColumn();

        // Total Loans Outstanding
        $stmt = $this->db->prepare("SELECT COALESCE(SUM(amount), 0) FROM loans WHERE tenant_id = ? AND status IN ('approved', 'disbursed')");
        $stmt->execute([$tenantId]);
        $kpis['total_loans'] = $stmt->fetchColumn();

        // Total Savings
        $stmt = $this->db->prepare("SELECT COALESCE(SUM(balance), 0) FROM savings_accounts WHERE tenant_id = ?");
        $stmt->execute([$tenantId]);
        $kpis['total_savings'] = $stmt->fetchColumn();

        // NPL Ratio
        $stmt = $this->db->prepare("
            SELECT
                (SELECT COUNT(*) FROM loans WHERE tenant_id = ? AND status = 'disbursed' AND DATEDIFF(CURDATE(), last_payment_date) > 90) /
                NULLIF((SELECT COUNT(*) FROM loans WHERE tenant_id = ? AND status = 'disbursed'), 0) * 100 as npl_ratio
        ");
        $stmt->execute([$tenantId, $tenantId]);
        $kpis['npl_ratio'] = round($stmt->fetchColumn(), 2);

        // Monthly Revenue (last 30 days)
        $stmt = $this->db->prepare("
            SELECT COALESCE(SUM(amount_paid), 0)
            FROM repayments
            WHERE tenant_id = ? AND payment_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
        ");
        $stmt->execute([$tenantId]);
        $kpis['monthly_revenue'] = $stmt->fetchColumn();

        // Collection Rate
        $stmt = $this->db->prepare("
            SELECT
                (SELECT COUNT(*) FROM repayments WHERE tenant_id = ? AND status = 'paid' AND payment_date <= due_date AND due_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)) /
                NULLIF((SELECT COUNT(*) FROM repayments WHERE tenant_id = ? AND due_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)), 0) * 100 as collection_rate
        ");
        $stmt->execute([$tenantId, $tenantId]);
        $kpis['collection_rate'] = round($stmt->fetchColumn(), 2);

        return $kpis;
    }

    /**
     * Get widget data for dashboard
     */
    private function getWidgetData($tenantId, $widgetType)
    {
        switch ($widgetType) {
            case 'total_members':
                return $this->getMembersChart($tenantId);

            case 'total_loans':
                return $this->getLoansChart($tenantId);

            case 'total_savings':
                return $this->getSavingsChart($tenantId);

            case 'npl_ratio':
                return $this->getNPLChart($tenantId);

            case 'monthly_revenue':
                return $this->getRevenueChart($tenantId);

            case 'member_growth':
                return $this->getMemberGrowthChart($tenantId);

            case 'loan_portfolio':
                return $this->getLoanPortfolioChart($tenantId);

            default:
                return ['type' => 'info', 'data' => 'Widget not configured'];
        }
    }

    /**
     * Members chart data
     */
    private function getMembersChart($tenantId)
    {
        // Monthly member growth for last 12 months
        $stmt = $this->db->prepare("
            SELECT
                DATE_FORMAT(created_at, '%Y-%m') as month,
                COUNT(*) as count
            FROM members
            WHERE tenant_id = ? AND created_at >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
            GROUP BY DATE_FORMAT(created_at, '%Y-%m')
            ORDER BY month ASC
        ");
        $stmt->execute([$tenantId]);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            'type' => 'line',
            'title' => 'Pertumbuhan Anggota',
            'data' => $data,
            'x_key' => 'month',
            'y_key' => 'count'
        ];
    }

    /**
     * Loans chart data
     */
    private function getLoansChart($tenantId)
    {
        // Monthly loan disbursements for last 12 months
        $stmt = $this->db->prepare("
            SELECT
                DATE_FORMAT(disbursed_at, '%Y-%m') as month,
                COUNT(*) as loans_count,
                SUM(amount) as total_amount
            FROM loans
            WHERE tenant_id = ? AND disbursed_at >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH) AND status = 'disbursed'
            GROUP BY DATE_FORMAT(disbursed_at, '%Y-%m')
            ORDER BY month ASC
        ");
        $stmt->execute([$tenantId]);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            'type' => 'bar',
            'title' => 'Pencairan Pinjaman Bulanan',
            'data' => $data,
            'x_key' => 'month',
            'y_key' => 'total_amount'
        ];
    }

    /**
     * Predictive Analytics - NPL Forecasting
     */
    public function nplForecast()
    {
        $tenantId = $this->getCurrentTenantId();

        // Get historical NPL data for last 24 months
        $historicalNPL = $this->getHistoricalNPL($tenantId);

        // Generate NPL forecast for next 12 months
        $forecast = $this->generateNPLForecast($historicalNPL);

        // Get current risk factors
        $riskFactors = $this->analyzeRiskFactors($tenantId);

        require_once __DIR__ . '/../Views/analytics/npl_forecast.php';
    }

    /**
     * Get historical NPL data
     */
    private function getHistoricalNPL($tenantId)
    {
        $data = [];
        for ($i = 23; $i >= 0; $i--) {
            $month = date('Y-m', strtotime("-{$i} months"));

            $stmt = $this->db->prepare("
                SELECT
                    COUNT(CASE WHEN DATEDIFF(CURDATE(), last_payment_date) > 90 THEN 1 END) as npl_count,
                    COUNT(*) as total_loans,
                    (COUNT(CASE WHEN DATEDIFF(CURDATE(), last_payment_date) > 90 THEN 1 END) / COUNT(*)) * 100 as npl_ratio
                FROM loans
                WHERE tenant_id = ? AND disbursed_at <= LAST_DAY(?)
            ");
            $stmt->execute([$tenantId, $month . '-01']);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            $data[] = [
                'month' => $month,
                'npl_ratio' => round($result['npl_ratio'] ?? 0, 2),
                'npl_count' => $result['npl_count'] ?? 0,
                'total_loans' => $result['total_loans'] ?? 0
            ];
        }

        return $data;
    }

    /**
     * Generate NPL forecast using simple linear regression
     */
    private function generateNPLForecast($historicalData)
    {
        $n = count($historicalData);
        if ($n < 6) {
            return []; // Not enough data for forecasting
        }

        // Simple linear regression for trend analysis
        $x = range(1, $n);
        $y = array_column($historicalData, 'npl_ratio');

        $x_mean = array_sum($x) / $n;
        $y_mean = array_sum($y) / $n;

        $numerator = 0;
        $denominator = 0;

        for ($i = 0; $i < $n; $i++) {
            $numerator += ($x[$i] - $x_mean) * ($y[$i] - $y_mean);
            $denominator += pow($x[$i] - $x_mean, 2);
        }

        $slope = $denominator > 0 ? $numerator / $denominator : 0;
        $intercept = $y_mean - ($slope * $x_mean);

        // Generate forecast for next 12 months
        $forecast = [];
        for ($i = 1; $i <= 12; $i++) {
            $futureMonth = date('Y-m', strtotime("+{$i} months"));
            $predicted = $intercept + ($slope * ($n + $i));

            // Ensure prediction stays within reasonable bounds
            $predicted = max(0, min(100, $predicted));

            $forecast[] = [
                'month' => $futureMonth,
                'predicted_npl' => round($predicted, 2),
                'confidence' => 'medium' // Would be calculated based on model accuracy
            ];
        }

        return $forecast;
    }

    /**
     * Analyze current risk factors
     */
    private function analyzeRiskFactors($tenantId)
    {
        $factors = [];

        // Economic indicators
        $stmt = $this->db->prepare("
            SELECT AVG(monthly_income) as avg_income
            FROM members
            WHERE tenant_id = ?
        ");
        $stmt->execute([$tenantId]);
        $avgIncome = $stmt->fetchColumn();

        $factors['economic_stress'] = $avgIncome < 3000000 ? 'high' : 'low';

        // Portfolio concentration
        $stmt = $this->db->prepare("
            SELECT
                COUNT(*) as total_loans,
                AVG(amount) as avg_loan_size,
                MAX(amount) as largest_loan
            FROM loans
            WHERE tenant_id = ? AND status = 'disbursed'
        ");
        $stmt->execute([$tenantId]);
        $portfolio = $stmt->fetch(PDO::FETCH_ASSOC);

        $concentration = ($portfolio['largest_loan'] / $portfolio['total_loans']) > ($portfolio['avg_loan_size'] * 2) ? 'high' : 'low';
        $factors['portfolio_concentration'] = $concentration;

        // Collection performance (last 3 months)
        $stmt = $this->db->prepare("
            SELECT
                AVG(CASE WHEN payment_date <= due_date THEN 1 ELSE 0 END) * 100 as collection_rate
            FROM repayments
            WHERE tenant_id = ? AND due_date >= DATE_SUB(CURDATE(), INTERVAL 3 MONTH)
        ");
        $stmt->execute([$tenantId]);
        $collectionRate = $stmt->fetchColumn();

        $factors['collection_performance'] = $collectionRate < 85 ? 'high_risk' : 'normal';

        return $factors;
    }

    /**
     * Customer Segmentation Analysis
     */
    public function customerSegmentation()
    {
        $tenantId = $this->getCurrentTenantId();

        // Get all active members with their loan and savings data
        $stmt = $this->db->prepare("
            SELECT
                m.id, m.name, m.monthly_income, m.created_at,
                COALESCE(SUM(sa.balance), 0) as total_savings,
                COALESCE(SUM(l.amount), 0) as total_loans,
                COALESCE(AVG(r.payment_score), 0) as payment_score,
                COUNT(l.id) as loan_count,
                DATEDIFF(CURDATE(), m.created_at) as membership_days
            FROM members m
            LEFT JOIN savings_accounts sa ON m.id = sa.member_id
            LEFT JOIN loans l ON m.id = l.member_id AND l.status IN ('approved', 'disbursed')
            LEFT JOIN repayments r ON l.id = r.loan_id
            WHERE m.tenant_id = ? AND m.status = 'active'
            GROUP BY m.id, m.name, m.monthly_income, m.created_at
        ");
        $stmt->execute([$tenantId]);
        $members = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Perform segmentation
        $segments = $this->performSegmentation($members);

        // Get segment statistics
        $segmentStats = $this->calculateSegmentStats($segments);

        require_once __DIR__ . '/../Views/analytics/segmentation.php';
    }

    /**
     * Perform RFM-like segmentation for cooperative members
     */
    private function performSegmentation($members)
    {
        $segments = [
            'platinum' => ['count' => 0, 'members' => [], 'criteria' => 'High income, multiple loans, excellent payment'],
            'gold' => ['count' => 0, 'members' => [], 'criteria' => 'Good income, active loans, good payment'],
            'silver' => ['count' => 0, 'members' => [], 'criteria' => 'Moderate income, some loans, fair payment'],
            'bronze' => ['count' => 0, 'members' => [], 'criteria' => 'Low income, minimal activity, poor payment'],
            'prospect' => ['count' => 0, 'members' => [], 'criteria' => 'New members, building relationship']
        ];

        foreach ($members as $member) {
            $score = $this->calculateMemberScore($member);

            if ($score >= 80) {
                $segment = 'platinum';
            } elseif ($score >= 60) {
                $segment = 'gold';
            } elseif ($score >= 40) {
                $segment = 'silver';
            } elseif ($score >= 20) {
                $segment = 'bronze';
            } else {
                $segment = 'prospect';
            }

            $segments[$segment]['count']++;
            $segments[$segment]['members'][] = $member;
        }

        return $segments;
    }

    /**
     * Calculate member score based on multiple factors
     */
    private function calculateMemberScore($member)
    {
        $score = 0;

        // Income factor (30%)
        $incomeScore = min(30, ($member['monthly_income'] / 100000) * 30);
        $score += $incomeScore;

        // Savings factor (20%)
        $savingsScore = min(20, ($member['total_savings'] / 500000) * 20);
        $score += $savingsScore;

        // Loan activity factor (20%)
        $loanScore = min(20, $member['loan_count'] * 5);
        $score += $loanScore;

        // Payment performance factor (20%)
        $paymentScore = $member['payment_score'] * 20;
        $score += $paymentScore;

        // Membership tenure factor (10%)
        $tenureMonths = $member['membership_days'] / 30;
        $tenureScore = min(10, $tenureMonths * 0.5);
        $score += $tenureScore;

        return round($score);
    }

    /**
     * Calculate segment statistics
     */
    private function calculateSegmentStats($segments)
    {
        $stats = [];

        foreach ($segments as $segmentName => $segment) {
            if (empty($segment['members'])) {
                $stats[$segmentName] = [
                    'count' => 0,
                    'percentage' => 0,
                    'avg_income' => 0,
                    'avg_savings' => 0,
                    'avg_loans' => 0,
                    'avg_payment_score' => 0
                ];
                continue;
            }

            $members = $segment['members'];
            $count = count($members);

            $stats[$segmentName] = [
                'count' => $count,
                'percentage' => 0, // Will be calculated after all segments
                'avg_income' => round(array_sum(array_column($members, 'monthly_income')) / $count),
                'avg_savings' => round(array_sum(array_column($members, 'total_savings')) / $count),
                'avg_loans' => round(array_sum(array_column($members, 'total_loans')) / $count),
                'avg_payment_score' => round(array_sum(array_column($members, 'payment_score')) / $count)
            ];
        }

        // Calculate percentages
        $totalMembers = array_sum(array_column($stats, 'count'));
        foreach ($stats as &$stat) {
            $stat['percentage'] = $totalMembers > 0 ? round(($stat['count'] / $totalMembers) * 100, 1) : 0;
        }

        return $stats;
    }

    /**
     * Portfolio Performance Tracking
     */
    public function portfolioPerformance()
    {
        $tenantId = $this->getCurrentTenantId();

        // Get portfolio metrics
        $portfolioMetrics = $this->calculatePortfolioMetrics($tenantId);

        // Get risk-adjusted returns
        $riskReturns = $this->calculateRiskAdjustedReturns($tenantId);

        // Get benchmark comparison
        $benchmarkComparison = $this->getBenchmarkComparison($tenantId);

        // Get sector allocation
        $sectorAllocation = $this->getSectorAllocation($tenantId);

        require_once __DIR__ . '/../Views/analytics/portfolio.php';
    }

    /**
     * Calculate portfolio performance metrics
     */
    private function calculatePortfolioMetrics($tenantId)
    {
        // Total portfolio value
        $stmt = $this->db->prepare("
            SELECT SUM(amount) as total_outstanding
            FROM loans
            WHERE tenant_id = ? AND status = 'disbursed'
        ");
        $stmt->execute([$tenantId]);
        $totalOutstanding = $stmt->fetchColumn();

        // Monthly interest income (estimated)
        $stmt = $this->db->prepare("
            SELECT SUM(amount * 0.015 / 12) as monthly_interest
            FROM loans
            WHERE tenant_id = ? AND status = 'disbursed'
        ");
        $stmt->execute([$tenantId]);
        $monthlyInterest = $stmt->fetchColumn();

        // NPL ratio
        $stmt = $this->db->prepare("
            SELECT
                COUNT(CASE WHEN DATEDIFF(CURDATE(), last_payment_date) > 90 THEN 1 END) / COUNT(*) * 100 as npl_ratio
            FROM loans
            WHERE tenant_id = ? AND status = 'disbursed'
        ");
        $stmt->execute([$tenantId]);
        $nplRatio = $stmt->fetchColumn();

        // PAR (Portfolio at Risk)
        $stmt = $this->db->prepare("
            SELECT
                SUM(CASE WHEN DATEDIFF(CURDATE(), last_payment_date) > 30 THEN amount ELSE 0 END) /
                SUM(amount) * 100 as par_ratio
            FROM loans
            WHERE tenant_id = ? AND status = 'disbursed'
        ");
        $stmt->execute([$tenantId]);
        $parRatio = $stmt->fetchColumn();

        return [
            'total_outstanding' => $totalOutstanding ?? 0,
            'monthly_interest' => $monthlyInterest ?? 0,
            'annual_yield' => ($monthlyInterest ?? 0) * 12 / ($totalOutstanding ?? 1) * 100,
            'npl_ratio' => round($nplRatio ?? 0, 2),
            'par_ratio' => round($parRatio ?? 0, 2),
            'portfolio_health' => $this->assessPortfolioHealth($nplRatio ?? 0, $parRatio ?? 0)
        ];
    }

    /**
     * Assess portfolio health based on NPL and PAR ratios
     */
    private function assessPortfolioHealth($nplRatio, $parRatio)
    {
        if ($nplRatio < 2 && $parRatio < 5) {
            return ['status' => 'excellent', 'color' => 'green', 'description' => 'Portofolio sangat sehat'];
        } elseif ($nplRatio < 5 && $parRatio < 10) {
            return ['status' => 'good', 'color' => 'blue', 'description' => 'Portofolio sehat'];
        } elseif ($nplRatio < 8 && $parRatio < 15) {
            return ['status' => 'fair', 'color' => 'yellow', 'description' => 'Portofolio cukup sehat'];
        } else {
            return ['status' => 'poor', 'color' => 'red', 'description' => 'Perlu perhatian khusus'];
        }
    }

    /**
     * Risk Scoring Engine
     */
    public function riskScoring()
    {
        $tenantId = $this->getCurrentTenantId();

        // Get risk scoring models
        $stmt = $this->db->prepare("
            SELECT * FROM risk_scoring_models
            WHERE tenant_id = ? AND is_active = 1
            ORDER BY created_at DESC
        ");
        $stmt->execute([$tenantId]);
        $models = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Get recent risk assessments
        $stmt = $this->db->prepare("
            SELECT
                m.name as member_name,
                l.amount as loan_amount,
                rsm.name as model_name,
                'pending' as status,
                NOW() as assessment_date
            FROM loans l
            JOIN members m ON l.member_id = m.id
            CROSS JOIN risk_scoring_models rsm
            WHERE l.tenant_id = ? AND l.status = 'draft' AND rsm.is_active = 1
            LIMIT 20
        ");
        $stmt->execute([$tenantId]);
        $assessments = $stmt->fetchAll(PDO::FETCH_ASSOC);

        require_once __DIR__ . '/../Views/analytics/risk_scoring.php';
    }

    /**
     * Calculate risk score for a loan application
     */
    public function calculateRiskScore($loanId)
    {
        $tenantId = $this->getCurrentTenantId();

        // Get loan and member data
        $stmt = $this->db->prepare("
            SELECT l.*, m.monthly_income, m.occupation, m.created_at as member_since
            FROM loans l
            JOIN members m ON l.member_id = m.id
            WHERE l.id = ? AND l.tenant_id = ?
        ");
        $stmt->execute([$loanId, $tenantId]);
        $loanData = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$loanData) {
            throw new \Exception('Loan not found');
        }

        // Get active risk model
        $stmt = $this->db->prepare("
            SELECT * FROM risk_scoring_models
            WHERE tenant_id = ? AND model_type = 'credit_score' AND is_active = 1
            ORDER BY version DESC LIMIT 1
        ");
        $stmt->execute([$tenantId]);
        $model = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$model) {
            // Use default scoring if no model configured
            $score = $this->calculateDefaultRiskScore($loanData);
            $riskLevel = $this->getRiskLevel($score);
        } else {
            // Use configured model
            $score = $this->calculateModelRiskScore($loanData, $model);
            $riskLevel = $this->getRiskLevel($score);
        }

        // Store risk assessment
        $stmt = $this->db->prepare("
            INSERT INTO analytics_predictions
            (tenant_id, prediction_type, reference_id, prediction_value, model_version, input_data, created_at)
            VALUES (?, 'risk_assessment', ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([
            $tenantId,
            $loanId,
            $score,
            $model['version'] ?? 'default',
            json_encode($loanData)
        ]);

        return [
            'loan_id' => $loanId,
            'risk_score' => $score,
            'risk_level' => $riskLevel,
            'recommendation' => $this->getRiskRecommendation($score),
            'factors' => $this->getRiskFactors($loanData)
        ];
    }

    /**
     * Calculate default risk score
     */
    private function calculateDefaultRiskScore($loanData)
    {
        $score = 100; // Start with perfect score

        // Income factor (-30 points max)
        if ($loanData['monthly_income'] < 2000000) {
            $score -= 30;
        } elseif ($loanData['monthly_income'] < 3000000) {
            $score -= 15;
        }

        // Loan amount factor (-25 points max)
        $loanToIncomeRatio = $loanData['amount'] / $loanData['monthly_income'];
        if ($loanToIncomeRatio > 0.5) {
            $score -= 25;
        } elseif ($loanToIncomeRatio > 0.3) {
            $score -= 10;
        }

        // Membership tenure (-15 points max)
        $tenureMonths = (strtotime('now') - strtotime($loanData['member_since'])) / (30 * 24 * 60 * 60);
        if ($tenureMonths < 6) {
            $score -= 15;
        } elseif ($tenureMonths < 12) {
            $score -= 5;
        }

        // Occupation factor (-10 points max)
        if (empty($loanData['occupation'])) {
            $score -= 10;
        }

        // Ensure score is between 0 and 100
        return max(0, min(100, $score));
    }

    /**
     * Get risk level based on score
     */
    private function getRiskLevel($score)
    {
        if ($score >= 80) return 'low_risk';
        if ($score >= 60) return 'medium_risk';
        if ($score >= 40) return 'high_risk';
        return 'very_high_risk';
    }

    /**
     * Get risk recommendation
     */
    private function getRiskRecommendation($score)
    {
        if ($score >= 80) return 'Approve - Low risk application';
        if ($score >= 60) return 'Approve with conditions - Monitor closely';
        if ($score >= 40) return 'Review required - Additional documentation needed';
        return 'Decline - High risk application';
    }

    /**
     * Get risk factors
     */
    private function getRiskFactors($loanData)
    {
        $factors = [];

        $loanToIncomeRatio = $loanData['amount'] / $loanData['monthly_income'];
        $factors['loan_to_income_ratio'] = round($loanToIncomeRatio * 100, 1) . '%';

        $tenureMonths = (strtotime('now') - strtotime($loanData['member_since'])) / (30 * 24 * 60 * 60);
        $factors['membership_tenure'] = round($tenureMonths, 1) . ' months';

        $factors['income_level'] = 'Rp ' . number_format($loanData['monthly_income']);
        $factors['loan_amount'] = 'Rp ' . number_format($loanData['amount']);

        return $factors;
    }

    /**
     * KPI Monitoring per Role
     */
    public function kpiMonitoring()
    {
        $tenantId = $this->getCurrentTenantId();
        $userRole = $_SESSION['role'] ?? 'user';

        // Get KPIs for user's role
        $kpis = $this->getKPIsForRole($tenantId, $userRole);

        // Calculate current KPI values
        $kpiValues = [];
        foreach ($kpis as $kpi) {
            $kpiValues[$kpi['kpi_code']] = $this->calculateKPIValue($tenantId, $kpi);
        }

        // Get KPI trends (last 6 months)
        $kpiTrends = $this->getKPITrends($tenantId, $kpis);

        require_once __DIR__ . '/../Views/analytics/kpi_monitoring.php';
    }

    /**
     * Get KPIs for specific role
     */
    private function getKPIsForRole($tenantId, $role)
    {
        // Define role-based KPIs
        $roleKPIs = [
            'admin' => ['total_members', 'total_loans', 'total_savings', 'npl_ratio', 'monthly_revenue'],
            'kasir' => ['daily_transactions', 'cash_balance', 'payment_reminders', 'member_registrations'],
            'teller' => ['service_queue', 'member_satisfaction', 'transaction_volume', 'processing_time'],
            'manajer' => ['portfolio_performance', 'risk_metrics', 'staff_performance', 'compliance_status'],
            'surveyor' => ['survey_completion', 'location_coverage', 'risk_assessments', 'response_time'],
            'collector' => ['collection_rate', 'overdue_amount', 'recovery_rate', 'collection_targets']
        ];

        $userKPIs = $roleKPIs[$role] ?? ['total_members', 'total_loans'];

        $placeholders = str_repeat('?,', count($userKPIs) - 1) . '?';
        $stmt = $this->db->prepare("
            SELECT * FROM analytics_kpis
            WHERE tenant_id = ? AND kpi_code IN ({$placeholders})
            ORDER BY category ASC, name ASC
        ");

        $stmt->execute([$tenantId, ...$userKPIs]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Calculate KPI value
     */
    private function calculateKPIValue($tenantId, $kpi)
    {
        $config = json_decode($kpi['calculation_config'], true);

        switch ($kpi['calculation_type']) {
            case 'simple':
                return $this->calculateSimpleKPI($tenantId, $config);

            case 'ratio':
                return $this->calculateRatioKPI($tenantId, $config);

            case 'aggregate':
                return $this->calculateAggregateKPI($tenantId, $config);

            default:
                return 0;
        }
    }

    /**
     * Calculate simple KPI
     */
    private function calculateSimpleKPI($tenantId, $config)
    {
        $table = $config['table'];
        $field = $config['field'];
        $condition = $config['condition'] ?? '1=1';

        $stmt = $this->db->prepare("
            SELECT {$field} as value FROM {$table}
            WHERE tenant_id = ? AND {$condition}
            ORDER BY created_at DESC LIMIT 1
        ");
        $stmt->execute([$tenantId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result ? $result['value'] : 0;
    }

    /**
     * Calculate ratio KPI
     */
    private function calculateRatioKPI($tenantId, $config)
    {
        $numerator = $this->calculateKPIComponent($tenantId, $config['numerator']);
        $denominator = $this->calculateKPIComponent($tenantId, $config['denominator']);

        return $denominator > 0 ? ($numerator / $denominator) * 100 : 0;
    }

    /**
     * Calculate aggregate KPI
     */
    private function calculateAggregateKPI($tenantId, $config)
    {
        $table = $config['table'];
        $field = $config['field'];
        $condition = $config['condition'] ?? '1=1';
        $aggregation = $config['aggregation'] ?? 'SUM';

        $stmt = $this->db->prepare("
            SELECT {$aggregation}({$field}) as value FROM {$table}
            WHERE tenant_id = ? AND {$condition}
        ");
        $stmt->execute([$tenantId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result ? $result['value'] : 0;
    }

    /**
     * Calculate KPI component for ratios
     */
    private function calculateKPIComponent($tenantId, $component)
    {
        $table = $component['table'];
        $field = $component['field'];
        $condition = $component['condition'] ?? '1=1';

        $stmt = $this->db->prepare("
            SELECT COUNT(*) as value FROM {$table}
            WHERE tenant_id = ? AND {$condition}
        ");
        $stmt->execute([$tenantId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result ? $result['value'] : 0;
    }

    /**
     * Get KPI trends for last 6 months
     */
    private function getKPITrends($tenantId, $kpis)
    {
        $trends = [];

        foreach ($kpis as $kpi) {
            $trends[$kpi['kpi_code']] = [];

            for ($i = 5; $i >= 0; $i--) {
                $month = date('Y-m', strtotime("-{$i} months"));
                // In a real implementation, you'd store historical KPI values
                // For now, we'll use current values as placeholders
                $value = $this->calculateKPIValue($tenantId, $kpi);

                $trends[$kpi['kpi_code']][] = [
                    'month' => $month,
                    'value' => $value
                ];
            }
        }

        return $trends;
    }
}
