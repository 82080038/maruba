<?php
namespace App\Models;

class SavingsAccount extends Model
{
    protected string $table = 'savings_accounts';
    protected array $fillable = [
        'member_id', 'product_id', 'account_number', 'balance',
        'interest_accrued', 'last_interest_calculation', 'status',
        'opened_at', 'closed_at', 'tenant_id'
    ];
    protected array $casts = [
        'member_id' => 'int',
        'product_id' => 'int',
        'balance' => 'float',
        'interest_accrued' => 'float',
        'opened_at' => 'datetime',
        'closed_at' => 'datetime',
        'last_interest_calculation' => 'date',
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
     * Generate account number
     */
    public function generateAccountNumber(int $memberId, int $productId): string
    {
        $productModel = new SavingsProduct();
        $product = $productModel->find($productId);

        $prefix = match($product['type'] ?? 'sukarela') {
            'pokok' => 'PK',
            'wajib' => 'WJ',
            'sukarela' => 'SR',
            'investasi' => 'SI',
            'berjangka' => 'BJ',
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
                $data['product_id']
            );
        }

        if (!isset($data['opened_at'])) {
            $data['opened_at'] = date('Y-m-d H:i:s');
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
     * Deposit to account
     */
    public function deposit(int $accountId, float $amount, int $processedBy, string $notes = ''): bool
    {
        $account = $this->find($accountId);
        if (!$account || $account['status'] !== 'active') {
            return false;
        }

        $newBalance = $account['balance'] + $amount;

        // Update account balance
        $this->update($accountId, ['balance' => $newBalance]);

        // Record transaction
        $transactionModel = new SavingsTransaction();
        $transactionModel->create([
            'account_id' => $accountId,
            'member_id' => $account['member_id'],
            'type' => 'deposit',
            'amount' => $amount,
            'balance_before' => $account['balance'],
            'balance_after' => $newBalance,
            'processed_by' => $processedBy,
            'notes' => $notes
        ]);

        return true;
    }

    /**
     * Withdraw from account
     */
    public function withdraw(int $accountId, float $amount, int $processedBy, string $notes = ''): bool
    {
        $account = $this->find($accountId);
        if (!$account || $account['status'] !== 'active') {
            return false;
        }

        // Check minimum balance
        $productModel = new SavingsProduct();
        $product = $productModel->find($account['product_id']);
        $minBalance = $product['minimum_balance'] ?? 0;

        if (($account['balance'] - $amount) < $minBalance) {
            return false; // Insufficient balance
        }

        $newBalance = $account['balance'] - $amount;

        // Update account balance
        $this->update($accountId, ['balance' => $newBalance]);

        // Record transaction
        $transactionModel = new SavingsTransaction();
        $transactionModel->create([
            'account_id' => $accountId,
            'member_id' => $account['member_id'],
            'type' => 'withdrawal',
            'amount' => $amount,
            'balance_before' => $account['balance'],
            'balance_after' => $newBalance,
            'processed_by' => $processedBy,
            'notes' => $notes
        ]);

        return true;
    }

    /**
     * Calculate and accrue interest for account
     */
    public function calculateInterest(int $accountId): float
    {
        $account = $this->find($accountId);
        if (!$account || $account['status'] !== 'active') {
            return 0.0;
        }

        $productModel = new SavingsProduct();
        $product = $productModel->find($account['product_id']);

        if (!$product || !$product['interest_rate']) {
            return 0.0;
        }

        $interestRate = $product['interest_rate'];
        $calculationMethod = $product['interest_calculation'];

        $lastCalculation = $account['last_interest_calculation'] ?? $account['opened_at'];
        $currentDate = date('Y-m-d');

        // Calculate interest based on method
        $interest = 0.0;

        if ($calculationMethod === 'monthly') {
            // Monthly interest calculation
            $daysSinceLastCalc = (strtotime($currentDate) - strtotime($lastCalculation)) / (60 * 60 * 24);
            if ($daysSinceLastCalc >= 30) { // Approximately monthly
                $monthlyRate = $interestRate / 12;
                $interest = $account['balance'] * ($monthlyRate / 100);
            }
        } elseif ($calculationMethod === 'yearly') {
            // Yearly interest calculation
            $daysInYear = 365;
            $daysSinceLastCalc = (strtotime($currentDate) - strtotime($lastCalculation)) / (60 * 60 * 24);
            $yearlyFraction = $daysSinceLastCalc / $daysInYear;
            $interest = $account['balance'] * ($interestRate / 100) * $yearlyFraction;
        }

        if ($interest > 0) {
            // Accrue interest
            $newAccrued = ($account['interest_accrued'] ?? 0) + $interest;
            $this->update($accountId, [
                'interest_accrued' => $newAccrued,
                'last_interest_calculation' => $currentDate
            ]);
        }

        return $interest;
    }

    /**
     * Post accrued interest to account balance
     */
    public function postInterest(int $accountId, int $processedBy): bool
    {
        $account = $this->find($accountId);
        if (!$account || $account['status'] !== 'active') {
            return false;
        }

        $accruedInterest = $account['interest_accrued'] ?? 0;
        if ($accruedInterest <= 0) {
            return false;
        }

        $newBalance = $account['balance'] + $accruedInterest;

        // Update account balance and reset accrued interest
        $this->update($accountId, [
            'balance' => $newBalance,
            'interest_accrued' => 0
        ]);

        // Record interest transaction
        $transactionModel = new SavingsTransaction();
        $transactionModel->create([
            'account_id' => $accountId,
            'member_id' => $account['member_id'],
            'type' => 'interest',
            'amount' => $accruedInterest,
            'balance_before' => $account['balance'],
            'balance_after' => $newBalance,
            'processed_by' => $processedBy,
            'notes' => 'Interest posting for ' . date('M Y')
        ]);

        return true;
    }

    /**
     * Get accounts by member ID
     */
    public function getByMemberId(int $memberId): array
    {
        $stmt = $this->db->prepare("
            SELECT sa.*, sp.name as product_name, sp.type as product_type
            FROM {$this->table} sa
            JOIN savings_products sp ON sa.product_id = sp.id
            WHERE sa.member_id = ? AND sa.status = 'active'
            ORDER BY sa.opened_at DESC
        ");
        $stmt->execute([$memberId]);
        return $stmt->fetchAll();
    }

    /**
     * Get total savings by member
     */
    public function getTotalSavingsByMember(int $memberId): float
    {
        $stmt = $this->db->prepare("
            SELECT SUM(balance) as total
            FROM {$this->table}
            WHERE member_id = ? AND status = 'active'
        ");
        $stmt->execute([$memberId]);
        $result = $stmt->fetch();
        return (float)($result['total'] ?? 0);
    }

    /**
     * Get savings statistics
     */
    public function getSavingsStatistics(): array
    {
        $stmt = $this->db->prepare("
            SELECT
                COUNT(*) as total_accounts,
                COUNT(DISTINCT member_id) as total_members_with_savings,
                SUM(balance) as total_balance,
                AVG(balance) as avg_balance_per_account,
                SUM(interest_accrued) as total_accrued_interest,
                COUNT(CASE WHEN status = 'active' THEN 1 END) as active_accounts,
                COUNT(CASE WHEN status = 'frozen' THEN 1 END) as frozen_accounts,
                COUNT(CASE WHEN status = 'closed' THEN 1 END) as closed_accounts
            FROM {$this->table}
        ");
        $stmt->execute();
        return $stmt->fetch();
    }

    /**
     * Get savings by product type
     */
    public function getSavingsByProductType(): array
    {
        $stmt = $this->db->prepare("
            SELECT
                sp.type,
                sp.name as product_name,
                COUNT(sa.id) as account_count,
                SUM(sa.balance) as total_balance,
                AVG(sa.balance) as avg_balance
            FROM {$this->table} sa
            JOIN savings_products sp ON sa.product_id = sp.id
            WHERE sa.status = 'active'
            GROUP BY sp.type, sp.name
            ORDER BY total_balance DESC
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
