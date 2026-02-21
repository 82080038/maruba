<?php
namespace App\Models;

class SavingsAccount extends Model
{
    protected string $table = 'savings_accounts';
    protected array $fillable = [
        'member_id', 'account_number', 'type', 'balance',
        'interest_rate', 'status'
    ];
    protected array $casts = [
        'member_id' => 'int',
        'balance' => 'float',
        'interest_rate' => 'float',
        'created_at' => 'datetime'
    ];

    /**
     * Find account by account number
     */
    public function findByAccountNumber(string $accountNumber): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE account_number = ?");
        $stmt->execute([$accountNumber]);
        $result = $stmt->fetch();

        return $result ? $this->castAttributes($result) : null;
    }

    /**
     * Get accounts by member ID
     */
    public function getByMemberId(int $memberId): array
    {
        return $this->findWhere(['member_id' => $memberId], ['type' => 'ASC']);
    }

    /**
     * Get accounts by type
     */
    public function getByType(string $type): array
    {
        return $this->findWhere(['type' => $type, 'status' => 'active']);
    }

    /**
     * Generate account number
     */
    public function generateAccountNumber(int $memberId, string $type): string
    {
        $prefix = match($type) {
            'pokok' => 'PK',
            'wajib' => 'WJ',
            'sukarela' => 'SR',
            'sisuka' => 'SK',
            default => 'SV'
        };

        $memberIdPadded = str_pad($memberId, 6, '0', STR_PAD_LEFT);
        $timestamp = date('ymdHis');

        return $prefix . $memberIdPadded . $timestamp;
    }

    /**
     * Create savings account with automatic account number
     */
    public function create(array $data): int
    {
        if (!isset($data['account_number'])) {
            $data['account_number'] = $this->generateAccountNumber(
                $data['member_id'],
                $data['type']
            );
        }

        return parent::create($data);
    }

    /**
     * Update account balance
     */
    public function updateBalance(int $accountId, float $newBalance): bool
    {
        return $this->update($accountId, ['balance' => $newBalance]);
    }

    /**
     * Get total savings by member
     */
    public function getTotalSavingsByMember(int $memberId): float
    {
        $accounts = $this->getByMemberId($memberId);
        return array_sum(array_column($accounts, 'balance'));
    }

    /**
     * Get total savings by type
     */
    public function getTotalSavingsByType(string $type): float
    {
        $accounts = $this->getByType($type);
        return array_sum(array_column($accounts, 'balance'));
    }

    /**
     * Calculate interest for account
     */
    public function calculateInterest(int $accountId, string $periodStart, string $periodEnd): float
    {
        $account = $this->find($accountId);
        if (!$account || $account['status'] !== 'active') {
            return 0.0;
        }

        // For simplicity, calculate interest based on average balance
        // In real implementation, this would be more complex
        $averageBalance = $account['balance']; // Simplified
        $annualRate = $account['interest_rate'];
        $days = (strtotime($periodEnd) - strtotime($periodStart)) / (60 * 60 * 24);
        $years = $days / 365;

        return $averageBalance * ($annualRate / 100) * $years;
    }

    /**
     * Get savings statistics
     */
    public function getStatistics(): array
    {
        $stmt = $this->db->prepare("
            SELECT
                COUNT(*) as total_accounts,
                SUM(balance) as total_balance,
                AVG(balance) as avg_balance,
                COUNT(CASE WHEN type = 'pokok' THEN 1 END) as pokok_count,
                COUNT(CASE WHEN type = 'wajib' THEN 1 END) as wajib_count,
                COUNT(CASE WHEN type = 'sukarela' THEN 1 END) as sukarela_count,
                COUNT(CASE WHEN type = 'sisuka' THEN 1 END) as sisuka_count,
                SUM(CASE WHEN type = 'pokok' THEN balance ELSE 0 END) as pokok_balance,
                SUM(CASE WHEN type = 'wajib' THEN balance ELSE 0 END) as wajib_balance,
                SUM(CASE WHEN type = 'sukarela' THEN balance ELSE 0 END) as sukarela_balance,
                SUM(CASE WHEN type = 'sisuka' THEN balance ELSE 0 END) as sisuka_balance
            FROM {$this->table}
            WHERE status = 'active'
        ");
        $stmt->execute();

        return $stmt->fetch();
    }
}
