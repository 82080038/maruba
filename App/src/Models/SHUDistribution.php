<?php
namespace App\Models;

class SHUDistribution extends Model
{
    protected string $table = 'shu_distributions';
    protected array $fillable = [
        'shu_calculation_id', 'member_id', 'savings_balance',
        'loan_balance', 'shu_amount', 'status', 'distributed_at'
    ];
    protected array $casts = [
        'shu_calculation_id' => 'int',
        'member_id' => 'int',
        'savings_balance' => 'float',
        'loan_balance' => 'float',
        'shu_amount' => 'float',
        'distributed_at' => 'datetime',
        'created_at' => 'datetime'
    ];

    /**
     * Get distributions by calculation ID
     */
    public function getByCalculationId(int $calculationId): array
    {
        return $this->findWhere(['shu_calculation_id' => $calculationId], ['shu_amount' => 'DESC']);
    }

    /**
     * Get distributions by member ID
     */
    public function getByMemberId(int $memberId): array
    {
        return $this->findWhere(['member_id' => $memberId], ['created_at' => 'DESC']);
    }

    /**
     * Get total SHU distributed to member
     */
    public function getTotalSHUByMember(int $memberId): float
    {
        $stmt = $this->db->prepare("
            SELECT SUM(shu_amount) as total_shu
            FROM {$this->table}
            WHERE member_id = ? AND status = 'distributed'
        ");
        $stmt->execute([$memberId]);

        $result = $stmt->fetch();
        return (float)($result['total_shu'] ?? 0);
    }

    /**
     * Get SHU distribution statistics
     */
    public function getStatistics(): array
    {
        $stmt = $this->db->prepare("
            SELECT
                COUNT(*) as total_distributions,
                SUM(shu_amount) as total_shu_distributed,
                AVG(shu_amount) as avg_shu_per_member,
                COUNT(CASE WHEN status = 'distributed' THEN 1 END) as distributed_count,
                COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_count
            FROM {$this->table}
        ");
        $stmt->execute();

        return $stmt->fetch();
    }

    /**
     * Get SHU distribution summary by year
     */
    public function getYearlySummary(int $year): array
    {
        $stmt = $this->db->prepare("
            SELECT
                m.name as member_name,
                m.nik,
                sd.savings_balance,
                sd.loan_balance,
                sd.shu_amount,
                sd.status,
                sd.distributed_at
            FROM {$this->table} sd
            JOIN members m ON sd.member_id = m.id
            JOIN shu_calculations sc ON sd.shu_calculation_id = sc.id
            WHERE sc.period_year = ?
            ORDER BY sd.shu_amount DESC
        ");
        $stmt->execute([$year]);

        return $stmt->fetchAll();
    }

    /**
     * Mark distribution as distributed
     */
    public function markAsDistributed(int $distributionId, string $distributedAt = null): bool
    {
        return $this->update($distributionId, [
            'status' => 'distributed',
            'distributed_at' => $distributedAt ?: date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Get pending distributions
     */
    public function getPendingDistributions(): array
    {
        $stmt = $this->db->prepare("
            SELECT sd.*, m.name as member_name, sc.period_year
            FROM {$this->table} sd
            JOIN members m ON sd.member_id = m.id
            JOIN shu_calculations sc ON sd.shu_calculation_id = sc.id
            WHERE sd.status = 'pending'
            ORDER BY sd.shu_amount DESC
        ");
        $stmt->execute();

        $results = $stmt->fetchAll();
        return array_map([$this, 'castAttributes'], $results);
    }
}
