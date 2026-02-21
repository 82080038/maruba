<?php
namespace App\Models;

class SHUCalculation extends Model
{
    protected string $table = 'shu_calculations';
    protected array $fillable = [
        'period_year', 'total_profit', 'shu_percentage', 'total_shu',
        'calculation_date', 'status', 'approved_by'
    ];
    protected array $casts = [
        'period_year' => 'int',
        'total_profit' => 'float',
        'shu_percentage' => 'float',
        'total_shu' => 'float',
        'calculation_date' => 'datetime',
        'approved_by' => 'int',
        'created_at' => 'datetime'
    ];

    /**
     * Calculate SHU for a specific year
     */
    public function calculateSHU(int $year, float $shuPercentage = 30.0): int
    {
        // Get total profit from accounting (simplified - in real implementation,
        // this would query accounting journals for profit calculation)
        $totalProfit = $this->calculateTotalProfit($year);

        if ($totalProfit <= 0) {
            throw new \Exception('No profit available for SHU distribution');
        }

        $totalSHU = $totalProfit * ($shuPercentage / 100);

        // Create SHU calculation record
        $calculationId = $this->create([
            'period_year' => $year,
            'total_profit' => $totalProfit,
            'shu_percentage' => $shuPercentage,
            'total_shu' => $totalSHU,
            'calculation_date' => date('Y-m-d'),
            'status' => 'draft'
        ]);

        // Generate SHU distributions
        $this->generateSHUDistributions($calculationId, $year, $totalSHU);

        return $calculationId;
    }

    /**
     * Calculate total profit for the year (simplified)
     */
    private function calculateTotalProfit(int $year): float
    {
        // In a real implementation, this would query accounting journals
        // For now, we'll use a simplified calculation based on interest income minus expenses

        // Get interest income from loans
        $loanModel = new Loan();
        $loans = $loanModel->findWhere(['status' => ['approved', 'disbursed']]);

        $interestIncome = 0;
        foreach ($loans as $loan) {
            // Simplified interest calculation
            $interestIncome += $loan['amount'] * ($loan['rate'] / 100) * ($loan['tenor_months'] / 12);
        }

        // Get savings interest expense (simplified)
        $savingsModel = new \App\Models\SavingsAccount();
        $savingsStats = $savingsModel->getStatistics();

        // Assume 60% of interest income is profit after expenses
        $profit = $interestIncome * 0.6;

        return max(0, $profit);
    }

    /**
     * Generate SHU distributions for all members
     */
    private function generateSHUDistributions(int $calculationId, int $year, float $totalSHU): void
    {
        $memberModel = new Member();
        $savingsModel = new \App\Models\SavingsAccount();
        $loanModel = new Loan();

        $members = $memberModel->findWhere(['status' => 'active']);

        $distributions = [];
        $totalWeight = 0;

        // Calculate weights for each member
        foreach ($members as $member) {
            $memberId = $member['id'];

            // Get member's savings balance
            $savingsBalance = $savingsModel->getTotalSavingsByMember($memberId);

            // Get member's loan balance
            $memberLoans = $loanModel->findWhere([
                'member_id' => $memberId,
                'status' => ['approved', 'disbursed']
            ]);
            $loanBalance = array_sum(array_column($memberLoans, 'amount'));

            // Calculate weight (simplified: 60% savings, 40% loans)
            $weight = ($savingsBalance * 0.6) + ($loanBalance * 0.4);
            $totalWeight += $weight;

            $distributions[] = [
                'member_id' => $memberId,
                'savings_balance' => $savingsBalance,
                'loan_balance' => $loanBalance,
                'weight' => $weight,
                'shu_amount' => 0 // Will be calculated after all weights are known
            ];
        }

        // Calculate and save distributions
        $shuDistributionModel = new SHUDistribution();

        foreach ($distributions as $distribution) {
            if ($totalWeight > 0) {
                $distribution['shu_amount'] = ($distribution['weight'] / $totalWeight) * $totalSHU;
            }

            $shuDistributionModel->create([
                'shu_calculation_id' => $calculationId,
                'member_id' => $distribution['member_id'],
                'savings_balance' => $distribution['savings_balance'],
                'loan_balance' => $distribution['loan_balance'],
                'shu_amount' => $distribution['shu_amount']
            ]);
        }
    }

    /**
     * Approve SHU calculation
     */
    public function approveCalculation(int $calculationId, int $approvedBy): bool
    {
        $calculation = $this->find($calculationId);
        if (!$calculation) {
            return false;
        }

        return $this->update($calculationId, [
            'status' => 'approved',
            'approved_by' => $approvedBy
        ]);
    }

    /**
     * Distribute SHU to members
     */
    public function distributeSHU(int $calculationId, int $processedBy): bool
    {
        $calculation = $this->find($calculationId);
        if (!$calculation || $calculation['status'] !== 'approved') {
            return false;
        }

        // Update calculation status
        $this->update($calculationId, ['status' => 'distributed']);

        // Update all distributions to distributed status
        $shuDistributionModel = new SHUDistribution();
        $distributions = $shuDistributionModel->findWhere(['shu_calculation_id' => $calculationId]);

        foreach ($distributions as $distribution) {
            $shuDistributionModel->update($distribution['id'], [
                'status' => 'distributed',
                'distributed_at' => date('Y-m-d H:i:s')
            ]);

            // Create savings transaction for SHU distribution
            $savingsModel = new \App\Models\SavingsAccount();
            $transactionModel = new \App\Models\SavingsTransaction();

            // Try to find member's SISUKA account first, then any savings account
            $accounts = $savingsModel->getByMemberId($distribution['member_id']);
            $sisukaAccount = null;

            foreach ($accounts as $account) {
                if ($account['type'] === 'sisuka') {
                    $sisukaAccount = $account;
                    break;
                }
            }

            // If no SISUKA account, use the first active savings account
            if (!$sisukaAccount && !empty($accounts)) {
                $sisukaAccount = $accounts[0];
            }

            // If member has no savings account, create a SISUKA account
            if (!$sisukaAccount) {
                $memberModel = new Member();
                $member = $memberModel->find($distribution['member_id']);

                if ($member) {
                    $accountId = $savingsModel->create([
                        'member_id' => $distribution['member_id'],
                        'type' => 'sisuka',
                        'interest_rate' => 6.0
                    ]);
                    $sisukaAccount = $savingsModel->find($accountId);
                }
            }

            // Deposit SHU amount to member's account
            if ($sisukaAccount) {
                try {
                    $transactionModel->createTransaction([
                        'savings_account_id' => $sisukaAccount['id'],
                        'type' => 'interest',
                        'amount' => $distribution['shu_amount'],
                        'description' => "SHU Tahun {$calculation['period_year']}",
                        'transaction_date' => date('Y-m-d'),
                        'processed_by' => $processedBy
                    ]);
                } catch (\Exception $e) {
                    // Log error but continue with other distributions
                    error_log("SHU distribution failed for member {$distribution['member_id']}: " . $e->getMessage());
                }
            }
        }

        return true;
    }

    /**
     * Get SHU calculation with distributions
     */
    public function findWithDistributions(int $id): ?array
    {
        $calculation = $this->find($id);
        if (!$calculation) {
            return null;
        }

        $shuDistributionModel = new SHUDistribution();
        $distributions = $shuDistributionModel->findWhere(['shu_calculation_id' => $id]);

        $calculation['distributions'] = $distributions;
        $calculation['total_distributed'] = array_sum(array_column($distributions, 'shu_amount'));

        return $calculation;
    }

    /**
     * Get SHU statistics
     */
    public function getStatistics(): array
    {
        $stmt = $this->db->prepare("
            SELECT
                COUNT(*) as total_calculations,
                SUM(total_shu) as total_shu_distributed,
                AVG(shu_percentage) as avg_shu_percentage,
                COUNT(CASE WHEN status = 'distributed' THEN 1 END) as distributed_count,
                MAX(period_year) as latest_year
            FROM {$this->table}
            WHERE status = 'distributed'
        ");
        $stmt->execute();

        return $stmt->fetch();
    }
}
