<?php
namespace App\Models;

class CreditAnalysis extends Model
{
    protected string $table = 'credit_analyses';
    protected array $fillable = [
        'loan_id', 'analyst_id', 'character_score', 'capacity_score',
        'capital_score', 'collateral_score', 'condition_score',
        'total_score', 'dsr_ratio', 'recommendation', 'notes', 'status'
    ];
    protected array $casts = [
        'loan_id' => 'int',
        'analyst_id' => 'int',
        'character_score' => 'float',
        'capacity_score' => 'float',
        'capital_score' => 'float',
        'collateral_score' => 'float',
        'condition_score' => 'float',
        'total_score' => 'float',
        'dsr_ratio' => 'float',
        'created_at' => 'datetime'
    ];

    /**
     * Analyze credit for a loan application
     */
    public function analyzeCredit(int $loanId, int $analystId): int
    {
        $loanModel = new \App\Models\Loan();
        $memberModel = new \App\Models\Member();

        $loan = $loanModel->find($loanId);
        $member = $loan ? $memberModel->find($loan['member_id']) : null;

        if (!$loan || !$member) {
            throw new \Exception('Loan or member not found');
        }

        // Perform 5C analysis
        $characterAnalysis = $this->analyzeCharacter($member);
        $capacityAnalysis = $this->analyzeCapacity($member, $loan);
        $capitalAnalysis = $this->analyzeCapital($member);
        $collateralAnalysis = $this->analyzeCollateral($loan);
        $conditionAnalysis = $this->analyzeCondition($loan);

        // Calculate total score (weighted average)
        $weights = [
            'character' => 0.2,
            'capacity' => 0.3,
            'capital' => 0.15,
            'collateral' => 0.2,
            'condition' => 0.15
        ];

        $totalScore = (
            $characterAnalysis['score'] * $weights['character'] +
            $capacityAnalysis['score'] * $weights['capacity'] +
            $capitalAnalysis['score'] * $weights['capital'] +
            $collateralAnalysis['score'] * $weights['collateral'] +
            $conditionAnalysis['score'] * $weights['condition']
        );

        // Calculate DSR (Debt Service Ratio)
        $dsrRatio = $this->calculateDSR($member['id'], $loan['amount'], $loan['tenor_months']);

        // Generate recommendation
        $recommendation = $this->generateRecommendation($totalScore, $dsrRatio);

        // Combine all notes
        $notes = json_encode([
            'character' => $characterAnalysis,
            'capacity' => $capacityAnalysis,
            'capital' => $capitalAnalysis,
            'collateral' => $collateralAnalysis,
            'condition' => $conditionAnalysis,
            'dsr_calculation' => $this->getDSRCalculation($member['id'], $loan['amount'], $loan['tenor_months'])
        ]);

        return $this->create([
            'loan_id' => $loanId,
            'analyst_id' => $analystId,
            'character_score' => $characterAnalysis['score'],
            'capacity_score' => $capacityAnalysis['score'],
            'capital_score' => $capitalAnalysis['score'],
            'collateral_score' => $collateralAnalysis['score'],
            'condition_score' => $conditionAnalysis['score'],
            'total_score' => $totalScore,
            'dsr_ratio' => $dsrRatio,
            'recommendation' => $recommendation,
            'notes' => $notes,
            'status' => 'completed'
        ]);
    }

    /**
     * Analyze Character (5C)
     * Based on member history, payment behavior, etc.
     */
    private function analyzeCharacter(array $member): array
    {
        $score = 50; // Base score
        $notes = [];

        // Check member status
        if ($member['status'] === 'active') {
            $score += 20;
            $notes[] = "Member status aktif (+20)";
        } else {
            $score -= 30;
            $notes[] = "Member status tidak aktif (-30)";
        }

        // Check if member has savings
        $savingsModel = new \App\Models\SavingsAccount();
        $savingsAccounts = $savingsModel->getByMemberId($member['id']);

        if (!empty($savingsAccounts)) {
            $totalSavings = $savingsModel->getTotalSavingsByMember($member['id']);
            if ($totalSavings >= 1000000) { // 1M minimum savings
                $score += 15;
                $notes[] = "Memiliki simpanan >1jt (+15)";
            } elseif ($totalSavings >= 500000) {
                $score += 10;
                $notes[] = "Memiliki simpanan >500rb (+10)";
            }
        } else {
            $score -= 10;
            $notes[] = "Tidak memiliki simpanan (-10)";
        }

        // Check loan history
        $loanModel = new \App\Models\Loan();
        $memberLoans = $loanModel->findWhere(['member_id' => $member['id']]);

        $badLoans = count(array_filter($memberLoans, fn($loan) => $loan['status'] === 'default'));
        if ($badLoans > 0) {
            $score -= 25;
            $notes[] = "Memiliki {$badLoans} pinjaman macet (-25)";
        } else {
            $score += 10;
            $notes[] = "Tidak memiliki riwayat macet (+10)";
        }

        // Cap score between 0-100
        $score = max(0, min(100, $score));

        return [
            'score' => $score,
            'notes' => $notes,
            'assessment' => $this->getScoreAssessment($score)
        ];
    }

    /**
     * Analyze Capacity (5C)
     * Based on income, expenses, ability to repay
     */
    private function analyzeCapacity(array $member, array $loan): array
    {
        $score = 50; // Base score
        $notes = [];

        // Get monthly income (from member profile or estimated)
        $monthlyIncome = $member['monthly_income'] ?? 3000000; // Default assumption

        // Estimate monthly expenses (simplified)
        $monthlyExpenses = $monthlyIncome * 0.7; // Assume 70% of income is expenses

        // Calculate proposed loan installment
        $monthlyInstallment = $loan['amount'] / $loan['tenor_months'];

        // Check if installment is reasonable (<50% of disposable income)
        $disposableIncome = $monthlyIncome - $monthlyExpenses;
        $installmentRatio = $monthlyInstallment / $disposableIncome;

        if ($installmentRatio < 0.3) {
            $score += 25;
            $notes[] = "Angsuran <30% pendapatan disposable (+25)";
        } elseif ($installmentRatio < 0.5) {
            $score += 10;
            $notes[] = "Angsuran <50% pendapatan disposable (+10)";
        } elseif ($installmentRatio < 0.7) {
            $score -= 10;
            $notes[] = "Angsuran cukup tinggi (-10)";
        } else {
            $score -= 30;
            $notes[] = "Angsuran terlalu tinggi (-30)";
        }

        // Check income stability
        if ($monthlyIncome >= 5000000) {
            $score += 15;
            $notes[] = "Pendapatan tinggi (+15)";
        } elseif ($monthlyIncome >= 3000000) {
            $score += 5;
            $notes[] = "Pendapatan cukup (+5)";
        }

        // Cap score between 0-100
        $score = max(0, min(100, $score));

        return [
            'score' => $score,
            'monthly_income' => $monthlyIncome,
            'monthly_installment' => $monthlyInstallment,
            'installment_ratio' => $installmentRatio,
            'notes' => $notes,
            'assessment' => $this->getScoreAssessment($score)
        ];
    }

    /**
     * Analyze Capital (5C)
     * Based on savings, assets, financial strength
     */
    private function analyzeCapital(array $member): array
    {
        $score = 50; // Base score
        $notes = [];

        $savingsModel = new \App\Models\SavingsAccount();
        $totalSavings = $savingsModel->getTotalSavingsByMember($member['id']);

        // Savings ratio (minimum 10% of annual income)
        $annualIncome = ($member['monthly_income'] ?? 3000000) * 12;
        $savingsRatio = $totalSavings / $annualIncome;

        if ($savingsRatio >= 0.2) {
            $score += 20;
            $notes[] = "Simpanan >20% pendapatan tahunan (+20)";
        } elseif ($savingsRatio >= 0.1) {
            $score += 10;
            $notes[] = "Simpanan >10% pendapatan tahunan (+10)";
        } elseif ($savingsRatio >= 0.05) {
            $score += 5;
            $notes[] = "Simpanan >5% pendapatan tahunan (+5)";
        } else {
            $score -= 15;
            $notes[] = "Simpanan kurang (-15)";
        }

        // Check savings types
        $savingsAccounts = $savingsModel->getByMemberId($member['id']);
        $hasMandatorySavings = false;

        foreach ($savingsAccounts as $account) {
            if ($account['type'] === 'pokok' && $account['balance'] >= 50000) {
                $hasMandatorySavings = true;
                $score += 10;
                $notes[] = "Memiliki simpanan pokok (+10)";
                break;
            }
        }

        if (!$hasMandatorySavings) {
            $score -= 20;
            $notes[] = "Tidak memiliki simpanan pokok (-20)";
        }

        // Cap score between 0-100
        $score = max(0, min(100, $score));

        return [
            'score' => $score,
            'total_savings' => $totalSavings,
            'savings_ratio' => $savingsRatio,
            'notes' => $notes,
            'assessment' => $this->getScoreAssessment($score)
        ];
    }

    /**
     * Analyze Collateral (5C)
     * Based on available collateral/assets
     */
    private function analyzeCollateral(array $loan): array
    {
        $score = 50; // Base score
        $notes = [];

        // For now, simplified analysis based on loan amount
        // In real implementation, this would check actual collateral

        $loanAmount = $loan['amount'];

        if ($loanAmount <= 5000000) {
            // Small loans might not need collateral
            $score += 20;
            $notes[] = "Pinjaman kecil, agunan tidak wajib (+20)";
        } elseif ($loanAmount <= 20000000) {
            $score += 10;
            $notes[] = "Pinjaman sedang, agunan direkomendasikan (+10)";
        } elseif ($loanAmount <= 50000000) {
            $score -= 5;
            $notes[] = "Pinjaman besar, agunan sangat direkomendasikan (-5)";
        } else {
            $score -= 20;
            $notes[] = "Pinjaman sangat besar, agunan wajib (-20)";
        }

        // Cap score between 0-100
        $score = max(0, min(100, $score));

        return [
            'score' => $score,
            'loan_amount' => $loanAmount,
            'notes' => $notes,
            'assessment' => $this->getScoreAssessment($score)
        ];
    }

    /**
     * Analyze Condition (5C)
     * Based on economic conditions, business viability, etc.
     */
    private function analyzeCondition(array $loan): array
    {
        $score = 50; // Base score
        $notes = [];

        // Get product info
        $productModel = new \App\Models\Product();
        $product = $productModel->find($loan['product_id']);

        if ($product) {
            if ($product['rate'] <= 1.5) {
                $score += 15;
                $notes[] = "Bunga rendah, kondisi ekonomi mendukung (+15)";
            } elseif ($product['rate'] <= 2.0) {
                $score += 5;
                $notes[] = "Bunga sedang (+5)";
            } else {
                $score -= 10;
                $notes[] = "Bunga tinggi, kondisi ekonomi kurang mendukung (-10)";
            }
        }

        // Check loan tenor
        if ($loan['tenor_months'] <= 12) {
            $score += 10;
            $notes[] = "Tenor pendek, risiko lebih rendah (+10)";
        } elseif ($loan['tenor_months'] <= 24) {
            $score += 5;
            $notes[] = "Tenor sedang (+5)";
        } else {
            $score -= 5;
            $notes[] = "Tenor panjang, risiko lebih tinggi (-5)";
        }

        // Cap score between 0-100
        $score = max(0, min(100, $score));

        return [
            'score' => $score,
            'product_rate' => $product ? $product['rate'] : 0,
            'loan_tenor' => $loan['tenor_months'],
            'notes' => $notes,
            'assessment' => $this->getScoreAssessment($score)
        ];
    }

    /**
     * Calculate DSR (Debt Service Ratio)
     */
    public function calculateDSR(int $memberId, float $newLoanAmount, int $tenorMonths): float
    {
        $repaymentModel = new \App\Models\Repayment();

        // Get existing monthly repayments
        $existingRepayments = $repaymentModel->findWhere(['member_id' => $memberId, 'status' => ['due', 'paid']]);

        $monthlyRepaymentExisting = 0;
        foreach ($existingRepayments as $repayment) {
            if ($repayment['status'] === 'due') {
                $monthlyRepaymentExisting += $repayment['amount_due'];
            }
        }

        // Calculate new loan monthly repayment
        $monthlyRepaymentNew = $newLoanAmount / $tenorMonths;

        // Total monthly debt service
        $totalMonthlyDebt = $monthlyRepaymentExisting + $monthlyRepaymentNew;

        // Get monthly income
        $memberModel = new \App\Models\Member();
        $member = $memberModel->find($memberId);
        $monthlyIncome = $member['monthly_income'] ?? 3000000;

        // Calculate DSR
        $dsr = ($totalMonthlyDebt / $monthlyIncome) * 100;

        return round($dsr, 2);
    }

    /**
     * Get DSR calculation details
     */
    private function getDSRCalculation(int $memberId, float $newLoanAmount, int $tenorMonths): array
    {
        $repaymentModel = new \App\Models\Repayment();

        $existingRepayments = $repaymentModel->findWhere(['member_id' => $memberId, 'status' => 'due']);

        $monthlyRepaymentExisting = 0;
        foreach ($existingRepayments as $repayment) {
            $monthlyRepaymentExisting += $repayment['amount_due'];
        }

        $monthlyRepaymentNew = $newLoanAmount / $tenorMonths;
        $totalMonthlyDebt = $monthlyRepaymentExisting + $monthlyRepaymentNew;

        $memberModel = new \App\Models\Member();
        $member = $memberModel->find($memberId);
        $monthlyIncome = $member['monthly_income'] ?? 3000000;

        return [
            'monthly_income' => $monthlyIncome,
            'existing_monthly_debt' => $monthlyRepaymentExisting,
            'new_monthly_debt' => $monthlyRepaymentNew,
            'total_monthly_debt' => $totalMonthlyDebt,
            'dsr_ratio' => round(($totalMonthlyDebt / $monthlyIncome) * 100, 2)
        ];
    }

    /**
     * Generate recommendation based on score and DSR
     */
    private function generateRecommendation(float $totalScore, float $dsrRatio): string
    {
        if ($totalScore >= 80 && $dsrRatio <= 30) {
            return 'RECOMMENDED - Sangat baik untuk disetujui';
        } elseif ($totalScore >= 70 && $dsrRatio <= 40) {
            return 'RECOMMENDED - Baik untuk disetujui dengan syarat';
        } elseif ($totalScore >= 60 && $dsrRatio <= 50) {
            return 'CONDITIONAL - Disetujui dengan pengawasan ketat';
        } elseif ($totalScore >= 50 && $dsrRatio <= 60) {
            return 'REVIEW - Perlu review mendalam';
        } elseif ($totalScore >= 40 || $dsrRatio <= 70) {
            return 'NOT RECOMMENDED - Risiko tinggi';
        } else {
            return 'REJECTED - Tidak memenuhi kriteria';
        }
    }

    /**
     * Get score assessment
     */
    private function getScoreAssessment(float $score): string
    {
        if ($score >= 80) return 'Excellent';
        if ($score >= 70) return 'Good';
        if ($score >= 60) return 'Fair';
        if ($score >= 50) return 'Poor';
        return 'Very Poor';
    }

    /**
     * Get analysis by loan ID
     */
    public function getByLoanId(int $loanId): ?array
    {
        $analysis = $this->findWhere(['loan_id' => $loanId]);
        return !empty($analysis) ? $analysis[0] : null;
    }

    /**
     * Get analysis statistics
     */
    public function getStatistics(): array
    {
        $stmt = $this->db->prepare("
            SELECT
                COUNT(*) as total_analyses,
                AVG(total_score) as avg_score,
                AVG(dsr_ratio) as avg_dsr,
                COUNT(CASE WHEN recommendation LIKE 'RECOMMENDED%' THEN 1 END) as recommended_count,
                COUNT(CASE WHEN recommendation LIKE 'NOT RECOMMENDED%' OR recommendation LIKE 'REJECTED%' THEN 1 END) as rejected_count
            FROM {$this->table}
            WHERE status = 'completed'
        ");
        $stmt->execute();

        return $stmt->fetch();
    }
}
