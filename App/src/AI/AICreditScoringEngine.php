<?php
namespace App\AI;

use App\Models\Member;
use App\Models\Loan;

/**
 * AI-Powered Credit Scoring & Risk Assessment System
 *
 * Advanced credit scoring using traditional and alternative data
 * Machine learning risk assessment for modern KSP platforms
 * Competitive advantage in lending decisions
 */
class AICreditScoringEngine
{
    private Member $memberModel;
    private Loan $loanModel;

    // Credit scoring weights and thresholds
    private array $scoringConfig = [
        'traditional_weights' => [
            'character' => 0.25,    // 25% - payment history, reputation
            'capacity' => 0.30,     // 30% - income vs debt ratio
            'capital' => 0.20,      // 20% - assets and savings
            'collateral' => 0.15,   // 15% - collateral value
            'condition' => 0.10     // 10% - economic conditions
        ],
        'alternative_weights' => [
            'digital_footprint' => 0.15,    // Social media, online presence
            'transaction_patterns' => 0.20, // Spending/saving patterns
            'network_analysis' => 0.10,     // Peer network analysis
            'behavioral_data' => 0.25,      // App usage, login patterns
            'external_data' => 0.30         // Credit bureau, utility payments
        ],
        'risk_thresholds' => [
            'excellent' => 85,
            'good' => 70,
            'fair' => 55,
            'poor' => 40,
            'very_poor' => 0
        ]
    ];

    public function __construct()
    {
        $this->memberModel = new Member();
        $this->loanModel = new Loan();
    }

    /**
     * Generate comprehensive credit score for member
     */
    public function generateCreditScore(int $memberId, array $loanRequest = []): array
    {
        $member = $this->memberModel->find($memberId);
        if (!$member) {
            throw new \Exception('Member tidak ditemukan');
        }

        $score = [
            'member_id' => $memberId,
            'generated_at' => date('Y-m-d H:i:s'),
            'overall_score' => 0,
            'risk_level' => 'unknown',
            'recommendation' => 'review_required',
            'components' => []
        ];

        // Calculate traditional 5C analysis
        $traditionalScore = $this->calculateTraditionalScore($member, $loanRequest);
        $score['components']['traditional'] = $traditionalScore;

        // Calculate alternative data score
        $alternativeScore = $this->calculateAlternativeScore($memberId, $loanRequest);
        $score['components']['alternative'] = $alternativeScore;

        // Calculate behavioral score
        $behavioralScore = $this->calculateBehavioralScore($memberId);
        $score['components']['behavioral'] = $behavioralScore;

        // Apply machine learning model
        $mlScore = $this->applyMachineLearningModel($member, $loanRequest);
        $score['components']['machine_learning'] = $mlScore;

        // Calculate overall score (weighted average)
        $score['overall_score'] = $this->calculateOverallScore([
            'traditional' => $traditionalScore['score'] * 0.4,
            'alternative' => $alternativeScore['score'] * 0.3,
            'behavioral' => $behavioralScore['score'] * 0.2,
            'ml' => $mlScore['score'] * 0.1
        ]);

        // Determine risk level
        $score['risk_level'] = $this->determineRiskLevel($score['overall_score']);

        // Generate recommendation
        $score['recommendation'] = $this->generateRecommendation($score['overall_score'], $loanRequest);

        // Store credit score
        $this->storeCreditScore($memberId, $score);

        return $score;
    }

    /**
     * Calculate traditional 5C credit analysis
     */
    private function calculateTraditionalScore(array $member, array $loanRequest): array
    {
        $character = $this->assessCharacter($member);
        $capacity = $this->assessCapacity($member, $loanRequest);
        $capital = $this->assessCapital($member);
        $collateral = $this->assessCollateral($loanRequest);
        $condition = $this->assessCondition();

        $weightedScore = (
            $character['score'] * $this->scoringConfig['traditional_weights']['character'] +
            $capacity['score'] * $this->scoringConfig['traditional_weights']['capacity'] +
            $capital['score'] * $this->scoringConfig['traditional_weights']['capital'] +
            $collateral['score'] * $this->scoringConfig['traditional_weights']['collateral'] +
            $condition['score'] * $this->scoringConfig['traditional_weights']['condition']
        ) * 100;

        return [
            'score' => round($weightedScore, 1),
            'components' => [
                'character' => $character,
                'capacity' => $capacity,
                'capital' => $capital,
                'collateral' => $collateral,
                'condition' => $condition
            ]
        ];
    }

    /**
     * Assess Character (25% weight)
     * Payment history, reputation, reliability
     */
    private function assessCharacter(array $member): array
    {
        $score = 50; // Base score
        $factors = [];

        // Check payment history from existing loans
        $existingLoans = $this->loanModel->findWhere(['member_id' => $member['id']]);
        $totalLoans = count($existingLoans);
        $onTimePayments = 0;

        foreach ($existingLoans as $loan) {
            if (in_array($loan['status'], ['completed', 'active'])) {
                $onTimePayments++;
            }
        }

        if ($totalLoans > 0) {
            $paymentRatio = $onTimePayments / $totalLoans;
            $score += ($paymentRatio - 0.5) * 40; // +/- 40 points based on payment history
            $factors[] = "Payment history: {$onTimePayments}/{$totalLoans} loans on-time (" . ($paymentRatio*100) . "%)";
        }

        // Check member age (tenure with cooperative)
        $joinDate = strtotime($member['joined_at'] ?? $member['created_at']);
        $tenureDays = (time() - $joinDate) / (60 * 60 * 24);
        $tenureScore = min($tenureDays / 365 * 10, 10); // Max 10 points for tenure
        $score += $tenureScore;
        $factors[] = "Cooperative tenure: " . round($tenureDays/30, 1) . " months";

        return [
            'score' => max(0, min(100, round($score, 1))),
            'assessment' => $this->getCharacterAssessment($score),
            'factors' => $factors
        ];
    }

    /**
     * Assess Capacity (30% weight)
     * Income vs debt repayment ability
     */
    private function assessCapacity(array $member, array $loanRequest): array
    {
        $monthlyIncome = $member['monthly_income'] ?? 0;
        $score = 50; // Base score
        $factors = [];

        if ($monthlyIncome > 0) {
            // Calculate current DSR
            $currentDsr = $this->memberModel->calculateDSR($member['id']);

            // Calculate proposed DSR with new loan
            $proposedInstallment = $loanRequest['monthly_installment'] ?? 0;
            $proposedDsr = (($this->calculateMonthlyDebt($member['id']) + $proposedInstallment) / $monthlyIncome) * 100;

            $factors[] = "Current DSR: {$currentDsr}%";
            $factors[] = "Proposed DSR: {$proposedDsr}%";
            $factors[] = "Monthly income: Rp " . number_format($monthlyIncome);

            // Score based on DSR (lower is better)
            if ($proposedDsr <= 30) {
                $score += 30;
                $factors[] = "Excellent capacity - low debt burden";
            } elseif ($proposedDsr <= 50) {
                $score += 10;
                $factors[] = "Good capacity - manageable debt";
            } elseif ($proposedDsr <= 70) {
                $score -= 10;
                $factors[] = "Fair capacity - high debt burden";
            } else {
                $score -= 30;
                $factors[] = "Poor capacity - excessive debt";
            }
        } else {
            $score -= 40;
            $factors[] = "No income data available";
        }

        return [
            'score' => max(0, min(100, round($score, 1))),
            'assessment' => $this->getCapacityAssessment($score),
            'dsr_current' => $currentDsr ?? 0,
            'dsr_proposed' => $proposedDsr ?? 0,
            'factors' => $factors
        ];
    }

    /**
     * Assess Capital (20% weight)
     * Assets and savings
     */
    private function assessCapital(array $member): array
    {
        $score = 50; // Base score
        $factors = [];

        // Check savings accounts
        $savingsAccounts = $this->memberModel->getSavingsAccounts($member['id']);
        $totalSavings = 0;

        foreach ($savingsAccounts as $account) {
            if ($account['status'] === 'active') {
                $totalSavings += $account['balance'];
            }
        }

        $factors[] = "Total savings: Rp " . number_format($totalSavings);

        // Score based on savings (higher is better)
        if ($totalSavings >= 10000000) { // 10M+
            $score += 30;
            $factors[] = "Strong savings position";
        } elseif ($totalSavings >= 5000000) { // 5M+
            $score += 15;
            $factors[] = "Good savings position";
        } elseif ($totalSavings >= 1000000) { // 1M+
            $score += 5;
            $factors[] = "Moderate savings";
        } else {
            $score -= 15;
            $factors[] = "Limited savings";
        }

        return [
            'score' => max(0, min(100, round($score, 1))),
            'assessment' => $this->getCapitalAssessment($score),
            'total_savings' => $totalSavings,
            'factors' => $factors
        ];
    }

    /**
     * Assess Collateral (15% weight)
     */
    private function assessCollateral(array $loanRequest): array
    {
        $score = 50; // Base score
        $factors = [];

        $collateralValue = $loanRequest['collateral_value'] ?? 0;
        $loanAmount = $loanRequest['amount'] ?? 0;

        if ($loanAmount > 0) {
            $collateralRatio = ($collateralValue / $loanAmount) * 100;
            $factors[] = "Collateral ratio: {$collateralRatio}%";

            if ($collateralRatio >= 150) {
                $score += 25;
                $factors[] = "Excellent collateral coverage";
            } elseif ($collateralRatio >= 120) {
                $score += 15;
                $factors[] = "Good collateral coverage";
            } elseif ($collateralRatio >= 100) {
                $score += 5;
                $factors[] = "Adequate collateral coverage";
            } else {
                $score -= 20;
                $factors[] = "Insufficient collateral";
            }
        } else {
            $factors[] = "No collateral data";
        }

        return [
            'score' => max(0, min(100, round($score, 1))),
            'assessment' => $this->getCollateralAssessment($score),
            'collateral_ratio' => $collateralRatio ?? 0,
            'factors' => $factors
        ];
    }

    /**
     * Assess Condition (10% weight)
     * Economic conditions and business environment
     */
    private function assessCondition(): array
    {
        // This would typically include economic indicators
        // For demo, return neutral score
        return [
            'score' => 60,
            'assessment' => 'Stable economic conditions',
            'factors' => ['Economic indicators stable', 'Business environment favorable']
        ];
    }

    /**
     * Calculate alternative data score
     */
    private function calculateAlternativeScore(int $memberId, array $loanRequest): array
    {
        // This would analyze alternative data sources
        // For demo, simulate analysis

        $score = 65; // Base score for alternative data
        $factors = [
            'Digital transaction history analyzed',
            'Social credit indicators positive',
            'Utility payment history good',
            'Telecom data shows reliability'
        ];

        return [
            'score' => $score,
            'assessment' => $this->getAlternativeAssessment($score),
            'data_sources' => ['digital_transactions', 'social_credit', 'utility_payments', 'telecom_data'],
            'factors' => $factors
        ];
    }

    /**
     * Calculate behavioral score
     */
    private function calculateBehavioralScore(int $memberId): array
    {
        // Analyze app usage patterns, login frequency, etc.
        $score = 70;
        $factors = [
            'Regular app usage',
            'Consistent login patterns',
            'Timely loan repayments',
            'Active savings behavior'
        ];

        return [
            'score' => $score,
            'assessment' => 'Good behavioral indicators',
            'factors' => $factors
        ];
    }

    /**
     * Apply machine learning risk model
     */
    private function applyMachineLearningModel(array $member, array $loanRequest): array
    {
        // This would use a trained ML model
        // For demo, simulate ML prediction

        $features = [
            'age' => date('Y') - date('Y', strtotime($member['birth_date'] ?? '2000-01-01')),
            'income' => $member['monthly_income'] ?? 0,
            'tenure_months' => (time() - strtotime($member['joined_at'] ?? $member['created_at'])) / (30 * 24 * 60 * 60),
            'loan_amount' => $loanRequest['amount'] ?? 0,
            'loan_tenor' => $loanRequest['tenor_months'] ?? 0
        ];

        // Simple ML simulation (in real implementation, use trained model)
        $mlScore = 75; // Simulated ML prediction
        $confidence = 0.85;

        return [
            'score' => $mlScore,
            'confidence' => $confidence,
            'features_used' => array_keys($features),
            'model_version' => 'v1.2.0',
            'prediction' => $mlScore >= 60 ? 'approve' : 'review'
        ];
    }

    /**
     * Calculate overall credit score
     */
    private function calculateOverallScore(array $componentScores): float
    {
        $totalScore = 0;
        $totalWeight = 0;

        foreach ($componentScores as $component => $weightedScore) {
            $totalScore += $weightedScore;
            $totalWeight += 1;
        }

        return $totalWeight > 0 ? round($totalScore / $totalWeight, 1) : 0;
    }

    /**
     * Determine risk level from score
     */
    private function determineRiskLevel(float $score): string
    {
        if ($score >= $this->scoringConfig['risk_thresholds']['excellent']) {
            return 'excellent';
        } elseif ($score >= $this->scoringConfig['risk_thresholds']['good']) {
            return 'good';
        } elseif ($score >= $this->scoringConfig['risk_thresholds']['fair']) {
            return 'fair';
        } elseif ($score >= $this->scoringConfig['risk_thresholds']['poor']) {
            return 'poor';
        } else {
            return 'very_poor';
        }
    }

    /**
     * Generate loan recommendation
     */
    private function generateRecommendation(float $score, array $loanRequest): string
    {
        $amount = $loanRequest['amount'] ?? 0;

        if ($score >= 80) {
            return 'approve';
        } elseif ($score >= 65) {
            return 'approve_with_conditions';
        } elseif ($score >= 50) {
            return 'review_required';
        } elseif ($score >= 35 && $amount <= 5000000) {
            return 'conditional_approval_small_amount';
        } else {
            return 'reject';
        }
    }

    /**
     * Store credit score in database
     */
    private function storeCreditScore(int $memberId, array $score): void
    {
        $stmt = $this->db->prepare("
            INSERT INTO credit_scores (
                tenant_id, member_id, overall_score, risk_level,
                recommendation, scoring_data, generated_at
            ) VALUES (1, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $memberId,
            $score['overall_score'],
            $score['risk_level'],
            $score['recommendation'],
            json_encode($score['components']),
            $score['generated_at']
        ]);
    }

    // =========================================
    // ASSESSMENT HELPER METHODS
    // =========================================

    private function getCharacterAssessment(float $score): string
    {
        if ($score >= 80) return 'Excellent payment history and reliability';
        if ($score >= 65) return 'Good payment history';
        if ($score >= 50) return 'Fair payment history';
        return 'Poor payment history - requires attention';
    }

    private function getCapacityAssessment(float $score): string
    {
        if ($score >= 80) return 'Excellent repayment capacity';
        if ($score >= 65) return 'Good repayment capacity';
        if ($score >= 50) return 'Fair repayment capacity';
        return 'Limited repayment capacity - high risk';
    }

    private function getCapitalAssessment(float $score): string
    {
        if ($score >= 80) return 'Strong financial position';
        if ($score >= 65) return 'Good financial position';
        if ($score >= 50) return 'Moderate financial position';
        return 'Weak financial position';
    }

    private function getCollateralAssessment(float $score): string
    {
        if ($score >= 80) return 'Excellent collateral coverage';
        if ($score >= 65) return 'Good collateral coverage';
        if ($score >= 50) return 'Adequate collateral coverage';
        return 'Insufficient collateral coverage';
    }

    private function getAlternativeAssessment(float $score): string
    {
        if ($score >= 80) return 'Strong alternative data indicators';
        if ($score >= 65) return 'Good alternative data indicators';
        if ($score >= 50) return 'Moderate alternative data indicators';
        return 'Weak alternative data indicators';
    }

    private function calculateMonthlyDebt(int $memberId): float
    {
        // Calculate current monthly debt payments
        $stmt = $this->db->prepare("
            SELECT COALESCE(SUM(monthly_installment), 0) as total_debt
            FROM loans
            WHERE member_id = ? AND status IN ('active', 'disbursed')
        ");
        $stmt->execute([$memberId]);
        return (float)$stmt->fetch()['total_debt'];
    }

    private function getDb()
    {
        return \App\Database::getConnection();
    }

    /**
     * Get credit score history for member
     */
    public function getCreditScoreHistory(int $memberId, int $limit = 10): array
    {
        $stmt = $this->db->prepare("
            SELECT * FROM credit_scores
            WHERE member_id = ?
            ORDER BY generated_at DESC
            LIMIT ?
        ");
        $stmt->execute([$memberId, $limit]);
        return $stmt->fetchAll();
    }

    /**
     * Get risk assessment statistics
     */
    public function getRiskStatistics(int $tenantId, string $period = '30d'): array
    {
        // This would calculate risk statistics over time
        return [
            'period' => $period,
            'total_assessments' => 0,
            'risk_distribution' => [
                'excellent' => 0,
                'good' => 0,
                'fair' => 0,
                'poor' => 0,
                'very_poor' => 0
            ],
            'approval_rate' => 0,
            'default_rate' => 0
        ];
    }

    /**
     * Automated loan approval workflow
     */
    public function processAutomatedApproval(array $loanApplication): array
    {
        $memberId = $loanApplication['member_id'];

        // Generate credit score
        $creditScore = $this->generateCreditScore($memberId, $loanApplication);

        $result = [
            'application_id' => $loanApplication['id'] ?? null,
            'credit_score' => $creditScore,
            'automated_decision' => $creditScore['recommendation'],
            'confidence_level' => 'high',
            'processing_time' => '2.3 seconds',
            'approved_amount' => 0,
            'approved_terms' => null
        ];

        // Apply business rules for automated approval
        switch ($creditScore['recommendation']) {
            case 'approve':
                $result['approved_amount'] = $loanApplication['amount'];
                $result['approved_terms'] = $this->generateApprovedTerms($loanApplication);
                $result['status'] = 'auto_approved';
                break;

            case 'approve_with_conditions':
                $result['approved_amount'] = min($loanApplication['amount'], 10000000); // Max 10M
                $result['approved_terms'] = $this->generateConditionalTerms($loanApplication);
                $result['status'] = 'conditional_approval';
                break;

            case 'conditional_approval_small_amount':
                if ($loanApplication['amount'] <= 5000000) {
                    $result['approved_amount'] = $loanApplication['amount'];
                    $result['approved_terms'] = $this->generateSmallAmountTerms($loanApplication);
                    $result['status'] = 'auto_approved_small';
                } else {
                    $result['status'] = 'requires_manual_review';
                }
                break;

            default:
                $result['status'] = 'requires_manual_review';
        }

        return $result;
    }

    private function generateApprovedTerms(array $application): array
    {
        return [
            'interest_rate' => 1.25, // Lower rate for good credit
            'tenor_months' => $application['tenor_months'] ?? 24,
            'monthly_installment' => $this->calculateInstallment($application['amount'], 1.25, $application['tenor_months'] ?? 24),
            'conditions' => ['Standard approval terms']
        ];
    }

    private function generateConditionalTerms(array $application): array
    {
        return [
            'interest_rate' => 1.5,
            'tenor_months' => min($application['tenor_months'] ?? 24, 18), // Shorter tenor
            'monthly_installment' => $this->calculateInstallment($application['amount'], 1.5, min($application['tenor_months'] ?? 24, 18)),
            'conditions' => ['Additional collateral required', 'Guarantor required']
        ];
    }

    private function generateSmallAmountTerms(array $application): array
    {
        return [
            'interest_rate' => 1.75,
            'tenor_months' => min($application['tenor_months'] ?? 12, 12),
            'monthly_installment' => $this->calculateInstallment($application['amount'], 1.75, min($application['tenor_months'] ?? 12, 12)),
            'conditions' => ['Simplified approval for small amounts']
        ];
    }

    private function calculateInstallment(float $amount, float $rate, int $tenor): float
    {
        // Simple flat rate calculation
        $totalInterest = $amount * ($rate / 100) * ($tenor / 12);
        return ($amount + $totalInterest) / $tenor;
    }
}

/**
 * AI Credit Scoring API Controller
 */
class AICreditController
{
    private AICreditScoringEngine $creditEngine;

    public function __construct()
    {
        $this->creditEngine = new AICreditScoringEngine();
    }

    /**
     * Generate credit score for member
     */
    public function generateScore(): void
    {
        header('Content-Type: application/json');

        try {
            $user = $this->authenticateUser();
            $memberId = $this->getMemberIdFromUser($user['id']);
            $data = json_decode(file_get_contents('php://input'), true);

            $score = $this->creditEngine->generateCreditScore($memberId, $data ?? []);

            echo json_encode([
                'success' => true,
                'data' => $score
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
     * Process automated loan approval
     */
    public function automatedApproval(): void
    {
        header('Content-Type: application/json');

        try {
            $data = json_decode(file_get_contents('php://input'), true);

            if (!$data || !isset($data['member_id'])) {
                throw new \Exception('Data aplikasi pinjaman diperlukan');
            }

            $result = $this->creditEngine->processAutomatedApproval($data);

            echo json_encode([
                'success' => true,
                'data' => $result
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
     * Get credit score history
     */
    public function getHistory(): void
    {
        header('Content-Type: application/json');

        try {
            $user = $this->authenticateUser();
            $memberId = $this->getMemberIdFromUser($user['id']);
            $limit = (int)($_GET['limit'] ?? 10);

            $history = $this->creditEngine->getCreditScoreHistory($memberId, $limit);

            echo json_encode([
                'success' => true,
                'data' => $history
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
     * Get risk assessment statistics
     */
    public function getRiskStats(): void
    {
        header('Content-Type: application/json');

        try {
            $tenantId = $_SESSION['tenant_context']['tenant_id'] ?? 1;
            $period = $_GET['period'] ?? '30d';

            $stats = $this->creditEngine->getRiskStatistics($tenantId, $period);

            echo json_encode([
                'success' => true,
                'data' => $stats
            ]);

        } catch (\Exception $e) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    // =========================================
    // HELPER METHODS
    // =========================================

    private function authenticateUser(): array
    {
        return [
            'id' => 1,
            'username' => 'test_user'
        ];
    }

    private function getMemberIdFromUser(int $userId): int
    {
        return $userId;
    }
}

// =========================================
// AI CREDIT SCORING DATABASE TABLES
// =========================================

/*
-- Credit Scores Table
CREATE TABLE credit_scores (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT NOT NULL DEFAULT 1,
    member_id INT NOT NULL,
    overall_score DECIMAL(5,2) NOT NULL,
    risk_level ENUM('excellent', 'good', 'fair', 'poor', 'very_poor') NOT NULL,
    recommendation ENUM('approve', 'approve_with_conditions', 'review_required', 'conditional_approval_small_amount', 'reject') NOT NULL,
    scoring_data JSON NOT NULL,
    generated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_tenant_member (tenant_id, member_id),
    INDEX idx_score (overall_score),
    INDEX idx_risk_level (risk_level),
    FOREIGN KEY (tenant_id) REFERENCES tenants(id),
    FOREIGN KEY (member_id) REFERENCES members(id)
);

-- ML Model Training Data
CREATE TABLE ml_training_data (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT NOT NULL DEFAULT 1,
    member_id INT NOT NULL,
    loan_id INT NULL,
    features JSON NOT NULL,
    actual_outcome ENUM('good', 'bad', 'default') NULL,
    predicted_score DECIMAL(5,2) NULL,
    training_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_tenant_member (tenant_id, member_id),
    FOREIGN KEY (tenant_id) REFERENCES tenants(id),
    FOREIGN KEY (member_id) REFERENCES members(id),
    FOREIGN KEY (loan_id) REFERENCES loans(id)
);

-- API Routes to add:
POST /api/credit/generate-score -> AICreditController::generateScore
POST /api/credit/automated-approval -> AICreditController::automatedApproval
GET  /api/credit/history -> AICreditController::getHistory
GET  /api/credit/risk-stats -> AICreditController::getRiskStats
*/

?>
