<?php
namespace App\Models;

class SavingsTransaction extends Model
{
    protected string $table = 'savings_transactions';
    protected array $fillable = [
        'savings_account_id', 'type', 'amount', 'balance_before',
        'balance_after', 'description', 'transaction_date', 'processed_by'
    ];
    protected array $casts = [
        'savings_account_id' => 'int',
        'amount' => 'float',
        'balance_before' => 'float',
        'balance_after' => 'float',
        'processed_by' => 'int',
        'transaction_date' => 'datetime',
        'created_at' => 'datetime'
    ];

    /**
     * Get transactions by account ID
     */
    public function getByAccountId(int $accountId, int $limit = 50): array
    {
        return $this->findWhere(
            ['savings_account_id' => $accountId],
            ['transaction_date' => 'DESC', 'created_at' => 'DESC'],
            $limit
        );
    }

    /**
     * Get transactions by member ID
     */
    public function getByMemberId(int $memberId, int $limit = 50): array
    {
        $stmt = $this->db->prepare("
            SELECT st.* FROM {$this->table} st
            JOIN savings_accounts sa ON st.savings_account_id = sa.id
            WHERE sa.member_id = ?
            ORDER BY st.transaction_date DESC, st.created_at DESC
            LIMIT ?
        ");
        $stmt->execute([$memberId, $limit]);

        $results = $stmt->fetchAll();
        return array_map([$this, 'castAttributes'], $results);
    }

    /**
     * Create transaction and update account balance
     */
    public function createTransaction(array $data): int
    {
        $accountModel = new SavingsAccount();
        $account = $accountModel->find($data['savings_account_id']);

        if (!$account) {
            throw new \Exception('Savings account not found');
        }

        $data['balance_before'] = $account['balance'];

        // Calculate new balance based on transaction type
        switch ($data['type']) {
            case 'deposit':
                $data['balance_after'] = $account['balance'] + $data['amount'];
                break;
            case 'withdrawal':
                if ($account['balance'] < $data['amount']) {
                    throw new \Exception('Insufficient balance');
                }
                $data['balance_after'] = $account['balance'] - $data['amount'];
                break;
            case 'interest':
                $data['balance_after'] = $account['balance'] + $data['amount'];
                break;
            case 'transfer':
                // For transfers, balance calculation depends on direction
                // This would need additional logic
                $data['balance_after'] = $account['balance'] + $data['amount'];
                break;
            default:
                throw new \Exception('Invalid transaction type');
        }

        // Create transaction record
        $transactionId = $this->create($data);

        // Update account balance
        $accountModel->updateBalance($data['savings_account_id'], $data['balance_after']);

        // Log audit trail
        $auditModel = new AuditLog();
        $auditModel->logAction(
            $data['processed_by'] ?? null,
            'savings_transaction',
            'savings_transaction',
            $transactionId,
            [
                'account_id' => $data['savings_account_id'],
                'type' => $data['type'],
                'amount' => $data['amount'],
                'balance_before' => $data['balance_before'],
                'balance_after' => $data['balance_after']
            ]
        );

        return $transactionId;
    }

    /**
     * Get transaction statistics
     */
    public function getStatistics(string $periodStart = null, string $periodEnd = null): array
    {
        $whereClause = '';
        $params = [];

        if ($periodStart && $periodEnd) {
            $whereClause = 'WHERE transaction_date BETWEEN ? AND ?';
            $params = [$periodStart, $periodEnd];
        }

        $stmt = $this->db->prepare("
            SELECT
                COUNT(*) as total_transactions,
                SUM(CASE WHEN type = 'deposit' THEN amount ELSE 0 END) as total_deposits,
                SUM(CASE WHEN type = 'withdrawal' THEN amount ELSE 0 END) as total_withdrawals,
                SUM(CASE WHEN type = 'interest' THEN amount ELSE 0 END) as total_interest,
                COUNT(CASE WHEN type = 'deposit' THEN 1 END) as deposit_count,
                COUNT(CASE WHEN type = 'withdrawal' THEN 1 END) as withdrawal_count,
                COUNT(CASE WHEN type = 'interest' THEN 1 END) as interest_count,
                AVG(CASE WHEN type = 'deposit' THEN amount END) as avg_deposit,
                AVG(CASE WHEN type = 'withdrawal' THEN amount END) as avg_withdrawal
            FROM {$this->table}
            {$whereClause}
        ");
        $stmt->execute($params);

        return $stmt->fetch();
    }

    /**
     * Get recent transactions across all accounts
     */
    public function getRecentTransactions(int $limit = 20): array
    {
        $stmt = $this->db->prepare("
            SELECT st.*, sa.account_number, sa.type as account_type, m.name as member_name
            FROM {$this->table} st
            JOIN savings_accounts sa ON st.savings_account_id = sa.id
            JOIN members m ON sa.member_id = m.id
            ORDER BY st.transaction_date DESC, st.created_at DESC
            LIMIT ?
        ");
        $stmt->execute([$limit]);

        $results = $stmt->fetchAll();
        return array_map([$this, 'castAttributes'], $results);
    }

    /**
     * Transfer between accounts
     */
    public function transferBetweenAccounts(
        int $fromAccountId,
        int $toAccountId,
        float $amount,
        int $processedBy,
        string $description = null
    ): array {
        $accountModel = new SavingsAccount();

        $fromAccount = $accountModel->find($fromAccountId);
        $toAccount = $accountModel->find($toAccountId);

        if (!$fromAccount || !$toAccount) {
            throw new \Exception('Account not found');
        }

        if ($fromAccount['balance'] < $amount) {
            throw new \Exception('Insufficient balance in source account');
        }

        $this->db->beginTransaction();

        try {
            // Debit from source account
            $this->create([
                'savings_account_id' => $fromAccountId,
                'type' => 'transfer',
                'amount' => -$amount,
                'balance_before' => $fromAccount['balance'],
                'balance_after' => $fromAccount['balance'] - $amount,
                'description' => $description ?: "Transfer to {$toAccount['account_number']}",
                'transaction_date' => date('Y-m-d'),
                'processed_by' => $processedBy
            ]);

            // Credit to destination account
            $this->create([
                'savings_account_id' => $toAccountId,
                'type' => 'transfer',
                'amount' => $amount,
                'balance_before' => $toAccount['balance'],
                'balance_after' => $toAccount['balance'] + $amount,
                'description' => $description ?: "Transfer from {$fromAccount['account_number']}",
                'transaction_date' => date('Y-m-d'),
                'processed_by' => $processedBy
            ]);

            // Update account balances
            $accountModel->updateBalance($fromAccountId, $fromAccount['balance'] - $amount);
            $accountModel->updateBalance($toAccountId, $toAccount['balance'] + $amount);

            $this->db->commit();

            return [
                'success' => true,
                'from_account' => $fromAccount['account_number'],
                'to_account' => $toAccount['account_number'],
                'amount' => $amount
            ];

        } catch (\Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
}
