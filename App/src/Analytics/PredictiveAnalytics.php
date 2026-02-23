<?php
namespace App\Analytics;

use App\Database;

/**
 * Predictive Analytics Class
 * Provides AI/ML features for business intelligence
 */

class PredictiveAnalytics
{
    private $pdo;
    
    public function __construct()
    {
        $this->pdo = Database::getConnection();
    }
    
    /**
     * Predict loan default probability
     */
    public function predictLoanDefaultProbability(int $memberId): array
    {
        // Get member data
        $stmt = $this->pdo->prepare("
            SELECT m.*, 
                   COUNT(l.id) as total_loans,
                   SUM(CASE WHEN l.status = 'default' THEN 1 ELSE 0 END) as defaulted_loans,
                   AVG(l.amount) as avg_loan_amount,
                   MAX(l.created_at) as last_loan_date
            FROM members m
            LEFT JOIN loans l ON m.id = l.member_id
            WHERE m.id = ?
            GROUP BY m.id
        ");
        $stmt->execute([$memberId]);
        $memberData = $stmt->fetch();
        
        if (!$memberData) {
            return ['probability' => 0.0, 'confidence' => 0.0, 'factors' => []];
        }
        
        // Calculate risk factors
        $factors = $this->calculateRiskFactors($memberData);
        
        // Simple logistic regression model
        $probability = $this->calculateDefaultProbability($factors);
        
        return [
            'probability' => $probability,
            'confidence' => $this->calculateConfidence($factors),
            'factors' => $factors
        ];
    }
    
    /**
     * Calculate risk factors for loan default prediction
     */
    private function calculateRiskFactors(array $memberData): array
    {
        $factors = [];
        
        // Loan history factor
        $totalLoans = $memberData['total_loans'] ?? 0;
        $defaultedLoans = $memberData['defaulted_loans'] ?? 0;
        $defaultRate = $totalLoans > 0 ? ($defaultedLoans / $totalLoans) : 0;
        $factors['default_rate'] = $defaultRate;
        
        // Loan amount factor
        $avgAmount = $memberData['avg_loan_amount'] ?? 0;
        $factors['avg_loan_amount'] = min($avgAmount / 1000000, 1); // Normalize to 1M
        
        // Recent activity factor
        $lastLoanDate = $memberData['last_loan_date'] ?? null;
        $daysSinceLastLoan = $lastLoanDate ? (time() - strtotime($lastLoanDate)) / 86400 : 365;
        $factors['days_since_last_loan'] = min($daysSinceLastLoan / 365, 1);
        
        return $factors;
    }
    
    /**
     * Calculate default probability using logistic regression
     */
    private function calculateDefaultProbability(array $factors): float
    {
        // Simplified logistic regression
        // probability = 1 / (1 + exp(-z))
        // z = -3 + 2*default_rate + 0.5*avg_amount + 0.3*days_since_last_loan
        
        $z = -3 + (2 * $factors['default_rate']) + (0.5 * $factors['avg_loan_amount']) + (0.3 * $factors['days_since_last_loan']);
        
        return 1 / (1 + exp(-$z));
    }
    
    /**
     * Calculate confidence level
     */
    private function calculateConfidence(array $factors): float
    {
        // Confidence based on data availability
        $dataPoints = 0;
        
        if ($factors['default_rate'] > 0) $dataPoints += 2;
        if ($factors['avg_loan_amount'] > 0) $dataPoints += 1;
        if ($factors['days_since_last_loan'] > 0) $dataPoints += 1;
        
        return min($dataPoints / 4, 1); // Max 4 factors = 100% confidence
    }
    
    /**
     * Predict member churn probability
     */
    public function predictMemberChurnProbability(int $memberId): array
    {
        // Get member activity data
        $stmt = $this->pdo->prepare("
            SELECT m.*,
                   DATEDIFF(NOW(), m.last_login) as days_since_login,
                   (SELECT COUNT(*) FROM repayments WHERE member_id = m.id AND DATE(created_at) >= DATE_SUB(NOW(), INTERVAL 30 DAY)) as recent_repayments,
                   (SELECT COUNT(*) FROM loans WHERE member_id = m.id AND DATE(created_at) >= DATE_SUB(NOW(), INTERVAL 30 DAY)) as recent_loans
            FROM members m
            WHERE m.id = ?
        ");
        $stmt->execute([$memberId]);
        $memberData = $stmt->fetch();
        
        if (!$memberData) {
            return ['probability' => 0.0, 'confidence' => 0.0, 'factors' => []];
        }
        
        $factors = $this->calculateChurnFactors($memberData);
        $probability = $this->calculateChurnProbability($factors);
        
        return [
            'probability' => $probability,
            'confidence' => $this->calculateChurnConfidence($factors),
            'factors' => $factors
        ];
    }
    
    /**
     * Calculate churn factors
     */
    private function calculateChurnFactors(array $memberData): array
    {
        $factors = [];
        
        // Login activity factor
        $daysSinceLogin = $memberData['days_since_login'] ?? 365;
        $factors['days_since_login'] = min($daysSinceLogin / 365, 1);
        
        // Recent activity factor
        $recentRepayments = $memberData['recent_repayments'] ?? 0;
        $factors['recent_repayments'] = min($recentRepayments / 10, 1);
        
        // Recent loans factor
        $recentLoans = $memberData['recent_loans'] ?? 0;
        $factors['recent_loans'] = min($recentLoans / 5, 1);
        
        return $factors;
    }
    
    /**
     * Calculate churn probability
     */
    private function calculateChurnProbability(array $factors): float
    {
        // Simplified logistic regression for churn
        $z = -2 + (1.5 * $factors['days_since_login']) - (2 * $factors['recent_repayments']) - (1.5 * $factors['recent_loans']);
        
        return 1 / (1 + exp(-$z));
    }
    
    /**
     * Calculate churn confidence
     */
    private function calculateChurnConfidence(array $factors): float
    {
        $dataPoints = 0;
        
        if ($factors['days_since_login'] < 365) $dataPoints += 1;
        if ($factors['recent_repayments'] > 0) $dataPoints += 1;
        if ($factors['recent_loans'] > 0) $dataPoints += 1;
        
        return min($dataPoints / 3, 1);
    }
    
    /**
     * Predict cash flow for next month
     */
    public function predictCashFlow(string $period = 'next_month'): array
    {
        $pdo = Database::getConnection();
        
        // Get historical data
        $stmt = $pdo->prepare("
            SELECT 
                DATE_FORMAT(created_at, '%Y-%m') as month,
                SUM(CASE WHEN type = 'deposit' THEN amount ELSE 0 END) as deposits,
                SUM(CASE WHEN type = 'withdraw' THEN amount ELSE 0 END) as withdrawals
            FROM cash_flow
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
            GROUP BY DATE_FORMAT(created_at, '%Y-%m')
            ORDER BY month DESC
        ");
        $stmt->execute();
        $historicalData = $stmt->fetchAll();
        
        if (empty($historicalData)) {
            return ['predicted_cash_flow' => 0, 'confidence' => 0, 'trend' => 'stable'];
        }
        
        // Simple linear regression for prediction
        $months = [];
        $deposits = [];
        $withdrawals = [];
        
        foreach ($historicalData as $data) {
            $months[] = $data['month'];
            $deposits[] = $data['deposits'];
            $withdrawals[] = $data['withdrawals'];
        }
        
        // Calculate trend
        $trend = $this->calculateTrend($deposits, $withdrawals);
        
        // Predict next month
        $predictedDeposits = $this->predictNextValue($deposits);
        $predictedWithdrawals = $this->predictNextValue($withdrawals);
        $predictedCashFlow = $predictedDeposits - $predictedWithdrawals;
        
        return [
            'predicted_cash_flow' => $predictedCashFlow,
            'predicted_deposits' => $predictedDeposits,
            'predicted_withdrawals' => $predictedWithdrawals,
            'confidence' => 0.7, // Simplified confidence
            'trend' => $trend,
            'historical_months' => count($historicalData)
        ];
    }
    
    /**
     * Calculate trend from data series
     */
    private function calculateTrend(array $deposits, array $withdrawals): string
    {
        if (count($deposits) < 2) {
            return 'stable';
        }
        
        $lastValue = array_pop($deposits);
        $secondLastValue = array_pop($deposits);
        
        if ($lastValue > $secondLastValue) {
            return 'increasing';
        } elseif ($lastValue < $secondLastValue) {
            return 'decreasing';
        } else {
            return 'stable';
        }
    }
    
    /**
     * Predict next value using linear regression
     */
    private function predictNextValue(array $values): float
    {
        if (count($values) < 2) {
            return 0;
        }
        
        $n = count($values);
        $sumX = array_sum(range(1, $n));
        $sumY = array_sum($values);
        $sumXY = 0;
        
        for ($i = 0; $i < $n; $i++) {
            $sumXY += ($i + 1) * $values[$i];
        }
        
        $slope = ($n * $sumXY - $sumX * $sumY) / ($n * $sumX - $sumX * $sumX);
        $intercept = ($sumY - $slope * $sumX) / $n;
        
        return $slope * ($n + 1) + $intercept;
    }
    
    /**
     * Get member credit score
     */
    public function getMemberCreditScore(int $memberId): array
    {
        // Get member data
        $stmt = $this->pdo->prepare("
            SELECT m.*,
                   COUNT(l.id) as total_loans,
                   SUM(CASE WHEN l.status = 'paid' THEN 1 ELSE 0 END) as paid_loans,
                   SUM(CASE WHEN l.status = 'default' THEN 1 ELSE 0 END) as defaulted_loans,
                   AVG(l.amount) as avg_loan_amount,
                   MAX(l.created_at) as last_loan_date,
                   DATEDIFF(NOW(), m.created_at) as member_age_days
            FROM members m
            LEFT JOIN loans l ON m.id = l.member_id
            WHERE m.id = ?
            GROUP BY m.id
        ");
        $stmt->execute([$memberId]);
        $memberData = $stmt->fetch();
        
        if (!$memberData) {
            return ['score' => 0, 'grade' => 'Poor', 'factors' => []];
        }
        
        $factors = $this->calculateCreditFactors($memberData);
        $score = $this->calculateCreditScore($factors);
        $grade = $this->getCreditGrade($score);
        
        return [
            'score' => $score,
            'grade' => $grade,
            'factors' => $factors
        ];
    }
    
    /**
     * Calculate credit score factors
     */
    private function calculateCreditFactors(array $memberData): array
    {
        $factors = [];
        
        // Payment history factor (40%)
        $totalLoans = $memberData['total_loans'] ?? 0;
        $paidLoans = $memberData['paid_loans'] ?? 0;
        $paymentRatio = $totalLoans > 0 ? ($paidLoans / $totalLoans) : 0;
        $factors['payment_history'] = $paymentRatio;
        
        // Credit utilization factor (30%)
        $avgAmount = $memberData['avg_loan_amount'] ?? 0;
        $factors['credit_utilization'] = min($avgAmount / 5000000, 1);
        
        // Credit history length (20%)
        $memberAge = $memberData['member_age_days'] ?? 0;
        $factors['credit_history'] = min($memberAge / 365, 1);
        
        // Recent activity factor (10%)
        $daysSinceLastLoan = $memberData['last_loan_date'] ? (time() - strtotime($memberData['last_loan_date'])) / 86400 : 365;
        $factors['recent_activity'] = max(0, 1 - ($daysSinceLastLoan / 365));
        
        return $factors;
    }
    
    /**
     * Calculate credit score using factors
     */
    private function calculateCreditScore(array $factors): int
    {
        $score = 300; // Base score
        
        // Payment history (40% weight)
        $score += round($factors['payment_history'] * 400 * 0.4);
        
        // Credit utilization (30% weight)
        $score -= round($factors['credit_utilization'] * 150 * 0.3);
        
        // Credit history length (20% weight)
        $score += round($factors['credit_history'] * 150 * 0.2);
        
        // Recent activity (10% weight)
        $score += round($factors['recent_activity'] * 100 * 0.1);
        
        return max(300, min(850, round($score)));
    }
    
    /**
     * Get credit grade based on score
     */
    private function getCreditScoreGrade(int $score): string
    {
        if ($score >= 750) return 'Excellent';
        if ($score >= 700) return 'Good';
        if ($score >= 650) return 'Fair';
        if ($score >= 600) return 'Poor';
        return 'Very Poor';
    }
    
    /**
     * Get portfolio risk assessment
     */
    public function getPortfolioRiskAssessment(): array
    {
        $pdo = Database::connect();
        
        // Get portfolio data
        $stmt = $pdo->prepare("
            SELECT 
                COUNT(*) as total_loans,
                SUM(amount) as total_amount,
                SUM(CASE WHEN status = 'default' THEN amount ELSE 0 END) as bad_amount,
                SUM(CASE WHEN status = 'paid' THEN amount ELSE 0 END) as paid_amount,
                AVG(CASE WHEN status = 'approved' THEN amount ELSE 0 END) as approved_amount
            FROM loans
        ");
        $stmt->execute();
        $portfolioData = $stmt->fetch();
        
        if (!$portfolioData) {
            return ['risk_level' => 'Unknown', 'risk_score' => 0, 'metrics' => []];
        }
        
        $totalAmount = $portfolioData['total_amount'] ?? 0;
        $badAmount = $portfolioData['bad_amount'] ?? 0;
        $paidAmount = $portfolioData['paid_amount'] ?? 0;
        $approvedAmount = $portfolioData['approved_amount'] ?? 0;
        
        // Calculate risk metrics
        $defaultRate = $totalAmount > 0 ? ($badAmount / $totalAmount) : 0;
        $paidRate = $totalAmount > 0 ? ($paidAmount / $totalAmount) : 0;
        $approvalRate = $totalAmount > 0 ? ($approvedAmount / $totalAmount) : 0;
        
        $riskScore = ($defaultRate * 0.5) + ((1 - $paidRate) * 0.3) + ((1 - $approvalRate) * 0.2);
        
        $riskLevel = $this->getRiskLevel($riskScore);
        
        return [
            'risk_level' => $riskLevel,
            'risk_score' => round($riskScore, 2),
            'metrics' => [
                'total_amount' => $totalAmount,
                'bad_amount' => $badAmount,
                'paid_amount' => $paidAmount,
                'approved_amount' => $approved_amount,
                'default_rate' => round($defaultRate * 100, 2),
                'paid_rate' => round($paidRate * 100, 2),
                'approval_rate' => round($approval_rate * 100, 2)
            ]
        ];
    }
    
    /**
     * Get risk level based on score
     */
    private function getRiskLevel(float $score): string
    {
        if ($score <= 0.1) return 'Very Low';
        if ($score <= 0.2) return 'Low';
        if ($score <= 0.3) return 'Medium';
        if ($score <= 0.5) return 'High';
        return 'Very High';
    }
    
    /**
     * Generate member segmentation
     */
    public function generateMemberSegmentation(): array
    {
        $pdo = Database::connect();
        
        $stmt = $pdo->prepare("
            SELECT m.*,
                   COUNT(l.id) as total_loans,
                   SUM(l.amount) as total_loan_amount,
                   AVG(l.amount) as avg_loan_amount,
                   MAX(l.created_at) as last_loan_date,
                   DATEDIFF(NOW(), m.created_at) as member_age_days
            FROM members m
            LEFT JOIN loans l ON m.id = l.member_id
            GROUP BY m.id
            ORDER BY total_loan_amount DESC
        ");
        $stmt->execute();
        $members = $stmt->fetchAll();
        
        $segments = [];
        
        foreach ($members as $member) {
            $segment = $this->segmentMember($member);
            $segments[] = $segment;
        }
        
        return $segments;
    }
    
    /**
     * Segment member based on behavior
     */
    private function segmentMember(array $member): array
    {
        $totalLoans = $member['total_loans'] ?? 0;
        $totalAmount = $member['total_loan_amount'] ?? 0;
        $avgAmount = $member['avg_loan_amount'] ?? 0;
        $memberAge = $member['member_age_days'] ?? 0;
        
        // Segmentation logic
        if ($totalLoans == 0) {
            $segment = 'New';
        } elseif ($totalAmount < 1000000) {
            $segment = 'Low Value';
        } elseif ($totalAmount < 5000000) {
            $segment = 'Medium Value';
        } else {
            $segment = 'High Value';
        }
        
        // Add behavior-based segmentation
        if ($memberAge < 30) {
            $segment .= ' (New Member)';
        } elseif ($memberAge > 365) {
            $segment .= ' (Long-term)';
        }
        
        return [
            'member_id' => $member['id'],
            'name' => $member['name'],
            'segment' => $segment,
            'total_loans' => $totalLoans,
            'total_amount' => $totalAmount,
            'avg_loan_amount' => $avgAmount,
            'member_age_days' => $memberAge
        ];
    }
    
    /**
     * Get predictive insights summary
     */
    public function getPredictiveInsights(): array
    {
        return [
            'available_models' => [
                'loan_default_prediction',
                'member_churn_prediction',
                'cash_flow_prediction',
                'member_credit_scoring',
                'portfolio_risk_assessment',
                'member_segmentation'
            ],
            'accuracy_notes' => 'Models use simplified statistical methods for demonstration',
            'data_requirements' => '6 months of historical data minimum',
            'update_frequency' => 'Monthly',
            'confidence_threshold' => '70%'
        ];
    }
}
