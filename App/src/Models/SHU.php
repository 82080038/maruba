<?php
namespace App\Models;

class SHU extends Model
{
    protected string $table = 'shu_calculations';
    protected array $fillable = [
        'period_year', 'total_profit', 'total_shu', 'distribution_date',
        'status', 'approved_by', 'approved_at', 'notes'
    ];
    protected array $casts = [
        'period_year' => 'int',
        'total_profit' => 'float',
        'total_shu' => 'float',
        'distribution_date' => 'date',
        'approved_at' => 'datetime',
        'created_at' => 'datetime'
    ];

    /**
     * Calculate SHU for a period
     */
    public function calculateSHU(int $year, float $totalProfit, array $distributionRules = []): array
    {
        // Default distribution rules (can be configured per cooperative)
        $defaultRules = [
            'member_dividend' => 40, // 40% to members based on savings
            'loan_interest' => 30,   // 30% to members based on loans
            'reserve_fund' => 15,    // 15% to reserve fund
            'education_fund' => 10,  // 10% to education fund
            'social_fund' => 5       // 5% to social fund
        ];

        $rules = array_merge($defaultRules, $distributionRules);

        // Calculate total SHU (typically 30-50% of profit)
        $shuPercentage = 40; // 40% of profit goes to SHU
        $totalSHU = $totalProfit * ($shuPercentage / 100);

        // Calculate distribution amounts
        $distribution = [];
        foreach ($rules as $type => $percentage) {
            $distribution[$type] = $totalSHU * ($percentage / 100);
        }

        return [
            'period_year' => $year,
            'total_profit' => $totalProfit,
            'total_shu' => $totalSHU,
            'shu_percentage' => $shuPercentage,
            'distribution_rules' => $rules,
            'distribution_amounts' => $distribution
        ];
    }

    /**
     * Calculate member SHU allocations
     */
    public function calculateMemberAllocations(int $year, float $totalSHUMember): array
    {
        // Get all active members
        $memberModel = new Member();
        $members = $memberModel->findWhere(['status' => 'active']);

        $allocations = [];
        $totalWeight = 0;

        // Calculate weights for each member
        foreach ($members as $member) {
            $weight = $this->calculateMemberSHUWeight($member['id'], $year);
            $allocations[] = [
                'member_id' => $member['id'],
                'member_name' => $member['name'],
                'weight' => $weight,
                'allocation' => 0 // Will be calculated after total weight
            ];
            $totalWeight += $weight;
        }

        // Calculate actual allocations
        foreach ($allocations as &$allocation) {
            if ($totalWeight > 0) {
                $allocation['allocation'] = ($allocation['weight'] / $totalWeight) * $totalSHUMember;
            }
        }

        return [
            'year' => $year,
            'total_shu_member' => $totalSHUMember,
            'total_weight' => $totalWeight,
            'allocations' => $allocations
        ];
    }

    /**
     * Calculate SHU weight for a member
     */
    private function calculateMemberSHUWeight(int $memberId, int $year): float
    {
        $weight = 0;

        // Weight based on savings (40% of total weight)
        $savingsWeight = $this->getMemberSavingsWeight($memberId, $year);
        $weight += $savingsWeight * 0.4;

        // Weight based on loans (30% of total weight)
        $loanWeight = $this->getMemberLoanWeight($memberId, $year);
        $weight += $loanWeight * 0.3;

        // Weight based on membership duration (20% of total weight)
        $membershipWeight = $this->getMemberMembershipWeight($memberId);
        $weight += $membershipWeight * 0.2;

        // Weight based on activity (10% of total weight)
        $activityWeight = $this->getMemberActivityWeight($memberId, $year);
        $weight += $activityWeight * 0.1;

        return $weight;
    }

    /**
     * Get savings weight for member
     */
    private function getMemberSavingsWeight(int $memberId, int $year): float
    {
        $stmt = $this->db->prepare("
            SELECT AVG(balance) as avg_balance
            FROM savings_accounts sa
            JOIN savings_products sp ON sa.product_id = sp.id
            WHERE sa.member_id = ? AND sa.status = 'active'
            AND YEAR(sa.opened_at) <= ?
        ");
        $stmt->execute([$memberId, $year]);
        $result = $stmt->fetch();

        return (float)($result['avg_balance'] ?? 0);
    }

    /**
     * Get loan weight for member
     */
    private function getMemberLoanWeight(int $memberId, int $year): float
    {
        $stmt = $this->db->prepare("
            SELECT SUM(outstanding_balance) as total_outstanding
            FROM loans
            WHERE member_id = ? AND status IN ('active', 'disbursed')
            AND YEAR(application_date) <= ?
        ");
        $stmt->execute([$memberId, $year]);
        $result = $stmt->fetch();

        return (float)($result['total_outstanding'] ?? 0);
    }

    /**
     * Get membership weight for member
     */
    private function getMemberMembershipWeight(int $memberId): float
    {
        $memberModel = new Member();
        $member = $memberModel->find($memberId);

        if (!$member || !$member['joined_at']) {
            return 0;
        }

        $joinedDate = new \DateTime($member['joined_at']);
        $now = new \DateTime();
        $years = $joinedDate->diff($now)->y;

        // Base weight of 1, plus 0.1 for each year of membership
        return 1 + ($years * 0.1);
    }

    /**
     * Get activity weight for member
     */
    private function getMemberActivityWeight(int $memberId, int $year): float
    {
        // Count transactions in the year
        $stmt = $this->db->prepare("
            SELECT
                (SELECT COUNT(*) FROM savings_transactions WHERE member_id = ? AND YEAR(transaction_date) = ?) +
                (SELECT COUNT(*) FROM loan_repayments WHERE member_id = ? AND YEAR(paid_date) = ?) as activity_count
        ");
        $stmt->execute([$memberId, $year, $memberId, $year]);
        $result = $stmt->fetch();

        $activityCount = (int)($result['activity_count'] ?? 0);

        // Weight based on activity level
        if ($activityCount >= 12) return 1.0; // Very active (monthly transaction)
        if ($activityCount >= 6) return 0.7;  // Active (bi-monthly)
        if ($activityCount >= 3) return 0.4;  // Moderately active
        if ($activityCount >= 1) return 0.2;  // Low activity
        return 0.1; // Minimal activity
    }

    /**
     * Save SHU calculation
     */
    public function saveSHUCalculation(array $calculationData): int
    {
        $data = [
            'period_year' => $calculationData['period_year'],
            'total_profit' => $calculationData['total_profit'],
            'total_shu' => $calculationData['total_shu'],
            'distribution_date' => $calculationData['distribution_date'] ?? null,
            'status' => $calculationData['status'] ?? 'draft',
            'notes' => $calculationData['notes'] ?? ''
        ];

        return $this->create($data);
    }

    /**
     * Save member SHU allocations
     */
    public function saveMemberAllocations(int $shuId, array $allocations): void
    {
        foreach ($allocations as $allocation) {
            $stmt = $this->db->prepare("
                INSERT INTO shu_allocations (shu_id, member_id, allocation_amount, weight, created_at)
                VALUES (?, ?, ?, ?, NOW())
            ");
            $stmt->execute([
                $shuId,
                $allocation['member_id'],
                $allocation['allocation'],
                $allocation['weight']
            ]);
        }
    }

    /**
     * Approve SHU calculation
     */
    public function approveSHU(int $shuId, int $approvedBy): bool
    {
        return $this->update($shuId, [
            'status' => 'approved',
            'approved_by' => $approvedBy,
            'approved_at' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Distribute SHU to members
     */
    public function distributeSHU(int $shuId): bool
    {
        // Get SHU calculation
        $shu = $this->find($shuId);
        if (!$shu || $shu['status'] !== 'approved') {
            return false;
        }

        // Get member allocations
        $stmt = $this->db->prepare("
            SELECT * FROM shu_allocations WHERE shu_id = ?
        ");
        $stmt->execute([$shuId]);
        $allocations = $stmt->fetchAll();

        foreach ($allocations as $allocation) {
            // Create savings transaction for SHU distribution
            $stmt = $this->db->prepare("
                INSERT INTO savings_transactions (
                    account_id, member_id, type, amount, balance_before, balance_after,
                    transaction_date, processed_by, notes, created_at
                ) VALUES (?, ?, 'interest', ?, 0, ?, CURDATE(), 1, ?, NOW())
            ");

            // For simplicity, we'll distribute to first savings account
            $savingsModel = new SavingsAccount();
            $accounts = $savingsModel->getByMemberId($allocation['member_id']);

            if (!empty($accounts)) {
                $account = $accounts[0];
                $newBalance = $account['balance'] + $allocation['allocation_amount'];

                $stmt->execute([
                    $account['id'],
                    $allocation['member_id'],
                    $allocation['allocation_amount'],
                    $newBalance,
                    "SHU Distribution {$shu['period_year']}"
                ]);

                // Update account balance
                $savingsModel->updateBalance($account['id'], $newBalance);
            }
        }

        // Mark as distributed
        $this->update($shuId, [
            'status' => 'distributed',
            'distribution_date' => date('Y-m-d')
        ]);

        return true;
    }

    /**
     * Get SHU calculations by year
     */
    public function getSHUByYear(int $year): ?array
    {
        $calculations = $this->findWhere(['period_year' => $year]);
        return !empty($calculations) ? $calculations[0] : null;
    }

    /**
     * Get member SHU allocation
     */
    public function getMemberSHUAllocation(int $memberId, int $year): ?array
    {
        $shu = $this->getSHUByYear($year);
        if (!$shu) {
            return null;
        }

        $stmt = $this->db->prepare("
            SELECT * FROM shu_allocations
            WHERE shu_id = ? AND member_id = ?
        ");
        $stmt->execute([$shu['id'], $memberId]);
        return $stmt->fetch();
    }

    /**
     * Get SHU statistics
     */
    public function getSHUStatistics(): array
    {
        $stmt = $this->db->prepare("
            SELECT
                COUNT(*) as total_calculations,
                COUNT(CASE WHEN status = 'distributed' THEN 1 END) as distributed_calculations,
                SUM(total_shu) as total_shu_distributed,
                AVG(total_shu) as avg_shu_per_year,
                MAX(period_year) as latest_year
            FROM {$this->table}
        ");
        $stmt->execute();
        return $stmt->fetch();
    }

    /**
     * Generate SHU report
     */
    public function generateSHUReport(int $year): array
    {
        $shu = $this->getSHUByYear($year);
        if (!$shu) {
            return ['error' => 'SHU calculation not found for year ' . $year];
        }

        $stmt = $this->db->prepare("
            SELECT
                sa.*,
                m.name as member_name,
                m.nik
            FROM shu_allocations sa
            JOIN members m ON sa.member_id = m.id
            WHERE sa.shu_id = ?
            ORDER BY sa.allocation_amount DESC
        ");
        $stmt->execute([$shu['id']]);
        $allocations = $stmt->fetchAll();

        return [
            'shu_calculation' => $shu,
            'allocations' => $allocations,
            'total_allocated' => array_sum(array_column($allocations, 'allocation_amount')),
            'member_count' => count($allocations)
        ];
    }
}
