<?php
namespace App\Models;

class Payment extends Model
{
    protected string $table = 'payments';
    protected array $fillable = [
        'reference_id', 'reference_type', 'member_id', 'amount',
        'payment_method', 'status', 'transaction_id', 'payment_date',
        'notes', 'processed_by'
    ];
    protected array $casts = [
        'reference_id' => 'int',
        'member_id' => 'int',
        'amount' => 'float',
        'processed_by' => 'int',
        'payment_date' => 'datetime',
        'created_at' => 'datetime'
    ];

    /**
     * Generate virtual account number
     */
    public function generateVirtualAccount(int $memberId, string $bankCode = 'BCA'): string
    {
        $prefix = match($bankCode) {
            'BCA' => '988',
            'BNI' => '988',
            'BRI' => '002',
            'MANDIRI' => '008',
            default => '988'
        };

        // Use member ID padded to 6 digits + timestamp
        $memberIdPadded = str_pad($memberId, 6, '0', STR_PAD_LEFT);
        $timestamp = date('His');

        return $prefix . $memberIdPadded . $timestamp;
    }

    /**
     * Create payment record
     */
    public function create(array $data): int
    {
        // Generate virtual account if bank transfer
        if (($data['payment_method'] ?? '') === 'bank_transfer') {
            $data['virtual_account'] = $this->generateVirtualAccount(
                $data['member_id'],
                $data['bank_code'] ?? 'BCA'
            );
        }

        return parent::create($data);
    }

    /**
     * Process payment (mark as paid)
     */
    public function processPayment(int $paymentId, array $paymentData, int $processedBy): bool
    {
        $payment = $this->find($paymentId);
        if (!$payment || $payment['status'] !== 'pending') {
            return false;
        }

        $updateData = [
            'status' => 'paid',
            'payment_date' => $paymentData['payment_date'] ?? date('Y-m-d H:i:s'),
            'transaction_id' => $paymentData['transaction_id'] ?? null,
            'notes' => ($payment['notes'] ?? '') . ' - ' . ($paymentData['notes'] ?? ''),
            'processed_by' => $processedBy
        ];

        // Update payment status
        $success = $this->update($paymentId, $updateData);

        if ($success) {
            // Process the payment based on reference type
            $this->processPaymentByType($payment, $paymentData);
        }

        return $success;
    }

    /**
     * Process payment based on reference type
     */
    private function processPaymentByType(array $payment, array $paymentData): void
    {
        switch ($payment['reference_type']) {
            case 'repayment':
                $this->processRepaymentPayment($payment, $paymentData);
                break;
            case 'savings_deposit':
                $this->processSavingsDeposit($payment, $paymentData);
                break;
            case 'loan_fee':
                $this->processLoanFeePayment($payment, $paymentData);
                break;
            case 'membership_fee':
                $this->processMembershipFeePayment($payment, $paymentData);
                break;
        }
    }

    /**
     * Process repayment payment
     */
    private function processRepaymentPayment(array $payment, array $paymentData): void
    {
        $repaymentModel = new \App\Models\Repayment();
        $repaymentModel->recordPayment($payment['reference_id'], [
            'amount_paid' => $payment['amount'],
            'method' => $payment['payment_method'],
            'paid_date' => $payment['payment_date'],
            'proof_path' => $paymentData['proof_path'] ?? null,
            'notes' => 'Paid via payment system'
        ]);
    }

    /**
     * Process savings deposit
     */
    private function processSavingsDeposit(array $payment, array $paymentData): void
    {
        $transactionModel = new \App\Models\SavingsTransaction();
        $transactionModel->createTransaction([
            'savings_account_id' => $payment['reference_id'],
            'type' => 'deposit',
            'amount' => $payment['amount'],
            'description' => 'Deposit via payment system',
            'transaction_date' => date('Y-m-d'),
            'processed_by' => $payment['processed_by'] ?? 1
        ]);
    }

    /**
     * Process loan fee payment
     */
    private function processLoanFeePayment(array $payment, array $paymentData): void
    {
        // Update loan status to disbursed after fee payment
        $loanModel = new \App\Models\Loan();
        $loan = $loanModel->find($payment['reference_id']);

        if ($loan && $loan['status'] === 'approved') {
            $loanModel->update($payment['reference_id'], ['status' => 'disbursed']);

            // Create accounting journal for loan disbursement
            $accountingModel = new \App\Models\AccountingJournal();
            $accountingModel->createLoanDisbursementJournal($payment['reference_id']);
        }
    }

    /**
     * Process membership fee payment
     */
    private function processMembershipFeePayment(array $payment, array $paymentData): void
    {
        // Activate member after membership fee payment
        $memberModel = new \App\Models\Member();
        $memberModel->update($payment['reference_id'], ['status' => 'active']);
    }

    /**
     * Get payments by member
     */
    public function getByMember(int $memberId): array
    {
        return $this->findWhere(['member_id' => $memberId], ['created_at' => 'DESC']);
    }

    /**
     * Get payments by reference
     */
    public function getByReference(int $referenceId, string $referenceType): array
    {
        return $this->findWhere([
            'reference_id' => $referenceId,
            'reference_type' => $referenceType
        ], ['created_at' => 'DESC']);
    }

    /**
     * Get pending payments
     */
    public function getPendingPayments(): array
    {
        return $this->findWhere(['status' => 'pending'], ['created_at' => 'DESC']);
    }

    /**
     * Get payment statistics
     */
    public function getStatistics(): array
    {
        $stmt = $this->db->prepare("
            SELECT
                COUNT(*) as total_payments,
                SUM(CASE WHEN status = 'paid' THEN amount ELSE 0 END) as total_paid,
                SUM(CASE WHEN status = 'pending' THEN amount ELSE 0 END) as total_pending,
                COUNT(CASE WHEN status = 'paid' THEN 1 END) as paid_count,
                COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_count,
                AVG(CASE WHEN status = 'paid' THEN amount END) as avg_payment_amount
            FROM {$this->table}
        ");
        $stmt->execute();

        return $stmt->fetch();
    }

    /**
     * Check payment status from gateway (simplified)
     */
    public function checkPaymentStatus(string $transactionId): array
    {
        // In a real implementation, this would call the payment gateway API
        // For now, simulate payment status check

        $payment = $this->findWhere(['transaction_id' => $transactionId]);

        if (!empty($payment)) {
            $payment = $payment[0];
            return [
                'status' => $payment['status'],
                'amount' => $payment['amount'],
                'payment_date' => $payment['payment_date']
            ];
        }

        return ['status' => 'not_found'];
    }

    /**
     * Create payment for repayment
     */
    public function createRepaymentPayment(int $repaymentId, int $memberId, float $amount): int
    {
        return $this->create([
            'reference_id' => $repaymentId,
            'reference_type' => 'repayment',
            'member_id' => $memberId,
            'amount' => $amount,
            'payment_method' => 'virtual_account',
            'status' => 'pending',
            'notes' => 'Repayment payment'
        ]);
    }

    /**
     * Create payment for savings deposit
     */
    public function createSavingsDepositPayment(int $accountId, int $memberId, float $amount): int
    {
        return $this->create([
            'reference_id' => $accountId,
            'reference_type' => 'savings_deposit',
            'member_id' => $memberId,
            'amount' => $amount,
            'payment_method' => 'bank_transfer',
            'status' => 'pending',
            'notes' => 'Savings deposit'
        ]);
    }
}
