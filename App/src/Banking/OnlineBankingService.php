<?php
namespace App\Banking;

use App\Models\Member;
use App\Models\SavingsAccount;
use App\Models\Payment;

/**
 * Online Banking Features for KSP
 *
 * Core banking functionality: online transfers, virtual accounts, ATM integration
 * Essential for modern Indonesian cooperative digital platforms
 */
class OnlineBankingService
{
    private Member $memberModel;
    private SavingsAccount $savingsModel;
    private Payment $paymentModel;

    public function __construct()
    {
        $this->memberModel = new Member();
        $this->savingsModel = new SavingsAccount();
        $this->paymentModel = new Payment();
    }

    /**
     * Generate virtual account for member
     */
    public function generateVirtualAccount(int $memberId, array $options = []): array
    {
        $member = $this->memberModel->find($memberId);
        if (!$member) {
            throw new \Exception('Member tidak ditemukan');
        }

        $tenantId = $member['tenant_id'] ?? 1;

        // Generate unique virtual account number
        $virtualAccount = $this->generateVirtualAccountNumber($tenantId, $memberId);

        // Store virtual account mapping
        $this->storeVirtualAccount([
            'tenant_id' => $tenantId,
            'member_id' => $memberId,
            'virtual_account_number' => $virtualAccount,
            'account_type' => $options['account_type'] ?? 'savings',
            'is_active' => true,
            'expires_at' => date('Y-m-d H:i:s', strtotime('+1 year')),
            'created_at' => date('Y-m-d H:i:s')
        ]);

        return [
            'virtual_account_number' => $virtualAccount,
            'member_name' => $member['name'],
            'member_id' => $memberId,
            'bank_name' => $this->getBankName(),
            'expires_at' => date('Y-m-d H:i:s', strtotime('+1 year')),
            'instructions' => $this->getVirtualAccountInstructions()
        ];
    }

    /**
     * Process online fund transfer between members
     */
    public function transferBetweenMembers(array $transferData): array
    {
        $fromMemberId = $transferData['from_member_id'];
        $toMemberId = $transferData['to_member_id'];
        $amount = $transferData['amount'];
        $description = $transferData['description'] ?? 'Transfer antar anggota';

        // Validate transfer
        $this->validateMemberTransfer($fromMemberId, $toMemberId, $amount);

        // Get source and destination accounts
        $fromAccount = $this->getDefaultSavingsAccount($fromMemberId);
        $toAccount = $this->getDefaultSavingsAccount($toMemberId);

        if (!$fromAccount || !$toAccount) {
            throw new \Exception('Rekening simpanan tidak ditemukan');
        }

        // Check sufficient balance
        if ($fromAccount['balance'] < $amount) {
            throw new \Exception('Saldo tidak mencukupi');
        }

        // Begin transaction
        $this->db->beginTransaction();

        try {
            // Debit source account
            $this->updateAccountBalance($fromAccount['id'], -$amount);
            $this->createTransaction($fromAccount['id'], $fromMemberId, 'withdrawal', $amount,
                $fromAccount['balance'] - $amount, $fromAccount['balance'] - $amount, 'Transfer keluar: ' . $description);

            // Credit destination account
            $this->updateAccountBalance($toAccount['id'], $amount);
            $this->createTransaction($toAccount['id'], $toMemberId, 'deposit', $amount,
                $toAccount['balance'] + $amount, $toAccount['balance'] + $amount, 'Transfer masuk: ' . $description);

            // Create transfer record
            $transferId = $this->createTransferRecord([
                'from_member_id' => $fromMemberId,
                'to_member_id' => $toMemberId,
                'from_account_id' => $fromAccount['id'],
                'to_account_id' => $toAccount['id'],
                'amount' => $amount,
                'description' => $description,
                'transfer_type' => 'member_to_member',
                'status' => 'completed',
                'completed_at' => date('Y-m-d H:i:s')
            ]);

            $this->db->commit();

            // Send notifications
            $this->sendTransferNotifications($fromMemberId, $toMemberId, $amount, $transferId);

            return [
                'success' => true,
                'transfer_id' => $transferId,
                'amount' => $amount,
                'from_member' => $fromMemberId,
                'to_member' => $toMemberId,
                'completed_at' => date('Y-m-d H:i:s')
            ];

        } catch (\Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Process bank transfer to external account
     */
    public function transferToExternalBank(array $transferData): array
    {
        $fromMemberId = $transferData['from_member_id'];
        $bankCode = $transferData['bank_code'];
        $accountNumber = $transferData['account_number'];
        $accountName = $transferData['account_name'];
        $amount = $transferData['amount'];
        $description = $transferData['description'] ?? 'Transfer ke bank eksternal';

        // Validate transfer limits and fees
        $this->validateExternalTransfer($fromMemberId, $amount);

        // Calculate fees
        $fee = $this->calculateTransferFee($amount, 'external');

        // Check sufficient balance including fee
        $fromAccount = $this->getDefaultSavingsAccount($fromMemberId);
        $totalDebit = $amount + $fee;

        if ($fromAccount['balance'] < $totalDebit) {
            throw new \Exception('Saldo tidak mencukupi termasuk biaya transfer');
        }

        // Begin transaction
        $this->db->beginTransaction();

        try {
            // Debit member account
            $this->updateAccountBalance($fromAccount['id'], -$totalDebit);
            $this->createTransaction($fromAccount['id'], $fromMemberId, 'withdrawal', $totalDebit,
                $fromAccount['balance'] - $totalDebit, $fromAccount['balance'] - $totalDebit,
                'Transfer bank: ' . $description . ' (biaya: Rp ' . number_format($fee) . ')');

            // Create external transfer record
            $transferId = $this->createTransferRecord([
                'from_member_id' => $fromMemberId,
                'to_bank_code' => $bankCode,
                'to_account_number' => $accountNumber,
                'to_account_name' => $accountName,
                'amount' => $amount,
                'fee' => $fee,
                'description' => $description,
                'transfer_type' => 'external_bank',
                'status' => 'processing',
                'processed_at' => date('Y-m-d H:i:s')
            ]);

            $this->db->commit();

            // Queue for bank processing (would integrate with bank API)
            $this->queueBankTransfer($transferId, $transferData);

            return [
                'success' => true,
                'transfer_id' => $transferId,
                'amount' => $amount,
                'fee' => $fee,
                'total_debit' => $totalDebit,
                'status' => 'processing',
                'estimated_completion' => date('Y-m-d H:i:s', strtotime('+1 hour'))
            ];

        } catch (\Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Process ATM withdrawal via online banking
     */
    public function processATMWithdrawal(int $memberId, float $amount, string $atmLocation = null): array
    {
        // Validate withdrawal limits
        $this->validateATMWithdrawal($memberId, $amount);

        // Get member account
        $account = $this->getDefaultSavingsAccount($memberId);
        if (!$account) {
            throw new \Exception('Rekening simpanan tidak ditemukan');
        }

        // Calculate ATM fee
        $fee = $this->calculateATMFee($amount);

        // Check balance
        $totalDebit = $amount + $fee;
        if ($account['balance'] < $totalDebit) {
            throw new \Exception('Saldo tidak mencukupi');
        }

        // Begin transaction
        $this->db->beginTransaction();

        try {
            // Debit account
            $this->updateAccountBalance($account['id'], -$totalDebit);
            $this->createTransaction($account['id'], $memberId, 'withdrawal', $totalDebit,
                $account['balance'] - $totalDebit, $account['balance'] - $totalDebit,
                'Tarik tunai ATM' . ($atmLocation ? ' - ' . $atmLocation : '') . ' (biaya: Rp ' . number_format($fee) . ')');

            // Record ATM transaction
            $atmId = $this->createATMTransaction([
                'member_id' => $memberId,
                'account_id' => $account['id'],
                'amount' => $amount,
                'fee' => $fee,
                'atm_location' => $atmLocation,
                'transaction_type' => 'withdrawal',
                'status' => 'completed',
                'completed_at' => date('Y-m-d H:i:s')
            ]);

            $this->db->commit();

            return [
                'success' => true,
                'transaction_id' => $atmId,
                'amount' => $amount,
                'fee' => $fee,
                'remaining_balance' => $account['balance'] - $totalDebit,
                'completed_at' => date('Y-m-d H:i:s')
            ];

        } catch (\Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Get online banking dashboard data
     */
    public function getOnlineBankingDashboard(int $memberId): array
    {
        $member = $this->memberModel->find($memberId);
        if (!$member) {
            throw new \Exception('Member tidak ditemukan');
        }

        // Get savings accounts
        $accounts = $this->memberModel->getSavingsAccounts($memberId);

        // Get recent transactions
        $transactions = $this->memberModel->getTransactionHistory($memberId, 10);

        // Get pending transfers
        $pendingTransfers = $this->getPendingTransfers($memberId);

        // Get virtual accounts
        $virtualAccounts = $this->getMemberVirtualAccounts($memberId);

        // Calculate transfer limits
        $transferLimits = $this->getTransferLimits($memberId);

        return [
            'member_info' => [
                'id' => $member['id'],
                'name' => $member['name'],
                'member_number' => $member['member_number']
            ],
            'accounts' => array_map(function($account) {
                return [
                    'id' => $account['id'],
                    'account_number' => $account['account_number'],
                    'product_name' => $account['product_name'],
                    'balance' => $account['balance'],
                    'can_transfer' => $this->canTransferFromAccount($account),
                    'can_withdraw' => $this->canWithdrawFromAccount($account)
                ];
            }, $accounts),
            'recent_transactions' => array_slice($transactions, 0, 5),
            'pending_transfers' => $pendingTransfers,
            'virtual_accounts' => $virtualAccounts,
            'transfer_limits' => $transferLimits,
            'available_features' => [
                'member_transfer' => true,
                'bank_transfer' => true,
                'atm_withdrawal' => true,
                'virtual_account' => true,
                'bill_payment' => true
            ]
        ];
    }

    /**
     * Get transfer history
     */
    public function getTransferHistory(int $memberId, int $limit = 20): array
    {
        $stmt = $this->db->prepare("
            SELECT
                t.*,
                fm.name as from_member_name,
                tm.name as to_member_name,
                t.created_at as requested_at
            FROM transfers t
            LEFT JOIN members fm ON t.from_member_id = fm.id
            LEFT JOIN members tm ON t.to_member_id = tm.id
            WHERE t.from_member_id = ? OR t.to_member_id = ?
            ORDER BY t.created_at DESC
            LIMIT ?
        ");

        $stmt->execute([$memberId, $memberId, $limit]);
        return $stmt->fetchAll();
    }

    // =========================================
    // HELPER METHODS
    // =========================================

    private function getDb()
    {
        return \App\Database::getConnection();
    }

    private function generateVirtualAccountNumber(int $tenantId, int $memberId): string
    {
        // Generate 16-digit virtual account number
        // Format: [TenantPrefix][MemberId][Random][CheckDigit]
        $tenantPrefix = str_pad($tenantId, 2, '0', STR_PAD_LEFT);
        $memberPart = str_pad($memberId % 10000, 4, '0', STR_PAD_LEFT);
        $randomPart = rand(1000, 9999);

        $base = $tenantPrefix . $memberPart . $randomPart;
        $checkDigit = $this->calculateLuhnCheckDigit($base);

        return $base . $checkDigit;
    }

    private function calculateLuhnCheckDigit(string $number): int
    {
        // Simplified Luhn algorithm for check digit
        $sum = 0;
        $alternate = false;

        for ($i = strlen($number) - 1; $i >= 0; $i--) {
            $digit = (int)$number[$i];

            if ($alternate) {
                $digit *= 2;
                if ($digit > 9) {
                    $digit -= 9;
                }
            }

            $sum += $digit;
            $alternate = !$alternate;
        }

        return (10 - ($sum % 10)) % 10;
    }

    private function storeVirtualAccount(array $data): void
    {
        $stmt = $this->db->prepare("
            INSERT INTO virtual_accounts (
                tenant_id, member_id, virtual_account_number,
                account_type, is_active, expires_at, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $data['tenant_id'],
            $data['member_id'],
            $data['virtual_account_number'],
            $data['account_type'],
            $data['is_active'],
            $data['expires_at'],
            $data['created_at']
        ]);
    }

    private function getBankName(): string
    {
        // In production, this would be configurable per tenant
        return 'KSP Digital Indonesia';
    }

    private function getVirtualAccountInstructions(): array
    {
        return [
            'Cara transfer ke Virtual Account:',
            '1. Buka aplikasi mobile banking atau internet banking',
            '2. Pilih menu Transfer atau Transfer Antar Bank',
            '3. Masukkan nomor Virtual Account: [nomor_va]',
            '4. Masukkan nominal transfer',
            '5. Konfirmasi transfer',
            '6. Simpan bukti transfer',
            '',
            'Biaya admin: Gratis',
            'Waktu proses: Instant'
        ];
    }

    private function validateMemberTransfer(int $fromMemberId, int $toMemberId, float $amount): void
    {
        if ($fromMemberId === $toMemberId) {
            throw new \Exception('Tidak dapat transfer ke rekening sendiri');
        }

        if ($amount <= 0) {
            throw new \Exception('Nominal transfer harus lebih dari 0');
        }

        // Check daily transfer limit
        $dailyTotal = $this->getDailyTransferTotal($fromMemberId);
        $dailyLimit = 50000000; // 50M per day

        if (($dailyTotal + $amount) > $dailyLimit) {
            throw new \Exception('Batas transfer harian terlampaui');
        }
    }

    private function validateExternalTransfer(int $memberId, float $amount): void
    {
        // Similar validations but with different limits
        $dailyTotal = $this->getDailyExternalTransferTotal($memberId);
        $dailyLimit = 25000000; // 25M per day for external transfers

        if (($dailyTotal + $amount) > $dailyLimit) {
            throw new \Exception('Batas transfer ke bank eksternal terlampaui');
        }
    }

    private function validateATMWithdrawal(int $memberId, float $amount): void
    {
        $dailyTotal = $this->getDailyATMWithdrawalTotal($memberId);
        $dailyLimit = 5000000; // 5M per day for ATM

        if (($dailyTotal + $amount) > $dailyLimit) {
            throw new \Exception('Batas tarik tunai ATM terlampaui');
        }
    }

    private function getDefaultSavingsAccount(int $memberId): ?array
    {
        $accounts = $this->memberModel->getSavingsAccounts($memberId);

        // Return first active account or null
        foreach ($accounts as $account) {
            if ($account['status'] === 'active') {
                return $account;
            }
        }

        return null;
    }

    private function updateAccountBalance(int $accountId, float $amount): void
    {
        $stmt = $this->db->prepare("
            UPDATE savings_accounts
            SET balance = balance + ?, updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$amount, $accountId]);
    }

    private function createTransaction(int $accountId, int $memberId, string $type, float $amount,
                                     float $balanceBefore, float $balanceAfter, string $notes): void
    {
        $stmt = $this->db->prepare("
            INSERT INTO savings_transactions (
                account_id, member_id, type, amount,
                balance_before, balance_after, transaction_date,
                processed_by, notes, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, CURDATE(), 1, ?, NOW())
        ");

        $stmt->execute([
            $accountId, $memberId, $type, $amount,
            $balanceBefore, $balanceAfter, $notes
        ]);
    }

    private function createTransferRecord(array $data): int
    {
        $stmt = $this->db->prepare("
            INSERT INTO transfers (
                from_member_id, to_member_id, from_account_id, to_account_id,
                to_bank_code, to_account_number, to_account_name,
                amount, fee, description, transfer_type, status,
                completed_at, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");

        $stmt->execute([
            $data['from_member_id'] ?? null,
            $data['to_member_id'] ?? null,
            $data['from_account_id'] ?? null,
            $data['to_account_id'] ?? null,
            $data['to_bank_code'] ?? null,
            $data['to_account_number'] ?? null,
            $data['to_account_name'] ?? null,
            $data['amount'],
            $data['fee'] ?? 0,
            $data['description'] ?? '',
            $data['transfer_type'],
            $data['status'],
            $data['completed_at'] ?? null
        ]);

        return $this->db->lastInsertId();
    }

    private function createATMTransaction(array $data): int
    {
        $stmt = $this->db->prepare("
            INSERT INTO atm_transactions (
                member_id, account_id, amount, fee, atm_location,
                transaction_type, status, completed_at, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");

        $stmt->execute([
            $data['member_id'],
            $data['account_id'],
            $data['amount'],
            $data['fee'],
            $data['atm_location'],
            $data['transaction_type'],
            $data['status'],
            $data['completed_at']
        ]);

        return $this->db->lastInsertId();
    }

    private function calculateTransferFee(float $amount, string $type): float
    {
        // Simplified fee calculation
        switch ($type) {
            case 'external':
                return min(6500, $amount * 0.001); // Max 6.5k or 0.1%
            case 'member':
                return 0; // Free for member transfers
            default:
                return 2500; // Default fee
        }
    }

    private function calculateATMFee(float $amount): float
    {
        // ATM withdrawal fee
        return 2500; // Fixed fee
    }

    private function getDailyTransferTotal(int $memberId): float
    {
        $stmt = $this->db->prepare("
            SELECT COALESCE(SUM(amount), 0) as total
            FROM transfers
            WHERE from_member_id = ? AND DATE(created_at) = CURDATE()
        ");
        $stmt->execute([$memberId]);
        return (float)$stmt->fetch()['total'];
    }

    private function getDailyExternalTransferTotal(int $memberId): float
    {
        $stmt = $this->db->prepare("
            SELECT COALESCE(SUM(amount), 0) as total
            FROM transfers
            WHERE from_member_id = ? AND transfer_type = 'external_bank'
            AND DATE(created_at) = CURDATE()
        ");
        $stmt->execute([$memberId]);
        return (float)$stmt->fetch()['total'];
    }

    private function getDailyATMWithdrawalTotal(int $memberId): float
    {
        $stmt = $this->db->prepare("
            SELECT COALESCE(SUM(amount), 0) as total
            FROM atm_transactions
            WHERE member_id = ? AND transaction_type = 'withdrawal'
            AND DATE(created_at) = CURDATE()
        ");
        $stmt->execute([$memberId]);
        return (float)$stmt->fetch()['total'];
    }

    private function canTransferFromAccount(array $account): bool
    {
        return $account['status'] === 'active' && $account['balance'] > 0;
    }

    private function canWithdrawFromAccount(array $account): bool
    {
        return $account['status'] === 'active' && $account['balance'] > 0;
    }

    private function sendTransferNotifications(int $fromMemberId, int $toMemberId, float $amount, int $transferId): void
    {
        // Implementation for sending transfer notifications
        // Would integrate with MultiChannelNotifier
        error_log("Transfer notification sent for transfer ID: {$transferId}");
    }

    private function queueBankTransfer(int $transferId, array $transferData): void
    {
        // Queue for bank API processing
        // In real implementation, would use queue system like Redis/RabbitMQ
        error_log("Bank transfer queued for processing: Transfer ID {$transferId}");
    }

    private function getPendingTransfers(int $memberId): array
    {
        // Get pending transfers for member
        return []; // Placeholder
    }

    private function getMemberVirtualAccounts(int $memberId): array
    {
        // Get member's virtual accounts
        return []; // Placeholder
    }

    private function getTransferLimits(int $memberId): array
    {
        return [
            'daily_member_transfer' => 50000000, // 50M
            'daily_external_transfer' => 25000000, // 25M
            'daily_atm_withdrawal' => 5000000, // 5M
            'monthly_total' => 500000000 // 500M
        ];
    }
}

/**
 * Online Banking API Controller
 */
class OnlineBankingController
{
    private OnlineBankingService $bankingService;

    public function __construct()
    {
        $this->bankingService = new OnlineBankingService();
    }

    /**
     * Get online banking dashboard
     */
    public function getDashboard(): void
    {
        header('Content-Type: application/json');

        try {
            $user = $this->authenticateUser();
            $memberId = $this->getMemberIdFromUser($user['id']);

            $dashboard = $this->bankingService->getOnlineBankingDashboard($memberId);

            echo json_encode([
                'success' => true,
                'data' => $dashboard
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
     * Transfer between members
     */
    public function transferToMember(): void
    {
        header('Content-Type: application/json');

        try {
            $user = $this->authenticateUser();
            $data = json_decode(file_get_contents('php://input'), true);

            if (!$data || !isset($data['to_member_id']) || !isset($data['amount'])) {
                throw new \Exception('Data transfer tidak lengkap');
            }

            $fromMemberId = $this->getMemberIdFromUser($user['id']);
            $transferData = array_merge($data, ['from_member_id' => $fromMemberId]);

            $result = $this->bankingService->transferBetweenMembers($transferData);

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
     * Transfer to external bank
     */
    public function transferToBank(): void
    {
        header('Content-Type: application/json');

        try {
            $user = $this->authenticateUser();
            $data = json_decode(file_get_contents('php://input'), true);

            if (!$data || !isset($data['bank_code']) || !isset($data['account_number']) || !isset($data['amount'])) {
                throw new \Exception('Data transfer bank tidak lengkap');
            }

            $fromMemberId = $this->getMemberIdFromUser($user['id']);
            $transferData = array_merge($data, ['from_member_id' => $fromMemberId]);

            $result = $this->bankingService->transferToExternalBank($transferData);

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
     * Generate virtual account
     */
    public function generateVirtualAccount(): void
    {
        header('Content-Type: application/json');

        try {
            $user = $this->authenticateUser();
            $memberId = $this->getMemberIdFromUser($user['id']);
            $data = json_decode(file_get_contents('php://input'), true);

            $result = $this->bankingService->generateVirtualAccount($memberId, $data ?? []);

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
     * Process ATM withdrawal
     */
    public function atmWithdrawal(): void
    {
        header('Content-Type: application/json');

        try {
            $user = $this->authenticateUser();
            $memberId = $this->getMemberIdFromUser($user['id']);
            $data = json_decode(file_get_contents('php://input'), true);

            if (!$data || !isset($data['amount'])) {
                throw new \Exception('Nominal penarikan diperlukan');
            }

            $result = $this->bankingService->processATMWithdrawal(
                $memberId,
                $data['amount'],
                $data['atm_location'] ?? null
            );

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
     * Get transfer history
     */
    public function getTransferHistory(): void
    {
        header('Content-Type: application/json');

        try {
            $user = $this->authenticateUser();
            $memberId = $this->getMemberIdFromUser($user['id']);
            $limit = (int)($_GET['limit'] ?? 20);

            $history = $this->bankingService->getTransferHistory($memberId, $limit);

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

    // =========================================
    // HELPER METHODS
    // =========================================

    private function authenticateUser(): array
    {
        // In real implementation, validate JWT token or session
        // For demo, return mock user
        return [
            'id' => 1,
            'username' => 'test_user'
        ];
    }

    private function getMemberIdFromUser(int $userId): int
    {
        // In real implementation, get member ID from user relationship
        return $userId; // Simplified for demo
    }
}

// =========================================
// DATABASE TABLES FOR ONLINE BANKING
// =========================================

/*
-- Virtual Accounts Table
CREATE TABLE virtual_accounts (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT NOT NULL,
    member_id INT NOT NULL,
    virtual_account_number VARCHAR(20) NOT NULL UNIQUE,
    account_type ENUM('savings', 'loan') DEFAULT 'savings',
    is_active BOOLEAN DEFAULT TRUE,
    expires_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_tenant_member (tenant_id, member_id),
    INDEX idx_virtual_account (virtual_account_number),
    FOREIGN KEY (tenant_id) REFERENCES tenants(id),
    FOREIGN KEY (member_id) REFERENCES members(id)
);

-- Transfers Table
CREATE TABLE transfers (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT NOT NULL DEFAULT 1,
    from_member_id INT NULL,
    to_member_id INT NULL,
    from_account_id INT NULL,
    to_account_id INT NULL,
    to_bank_code VARCHAR(10) NULL,
    to_account_number VARCHAR(20) NULL,
    to_account_name VARCHAR(100) NULL,
    amount DECIMAL(15,2) NOT NULL,
    fee DECIMAL(10,2) DEFAULT 0,
    description TEXT NULL,
    transfer_type ENUM('member_to_member', 'external_bank') NOT NULL,
    status ENUM('pending', 'processing', 'completed', 'failed') DEFAULT 'pending',
    processed_at TIMESTAMP NULL,
    completed_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_tenant_from (tenant_id, from_member_id),
    INDEX idx_tenant_to (tenant_id, to_member_id),
    INDEX idx_status (status),
    FOREIGN KEY (tenant_id) REFERENCES tenants(id),
    FOREIGN KEY (from_member_id) REFERENCES members(id),
    FOREIGN KEY (to_member_id) REFERENCES members(id),
    FOREIGN KEY (from_account_id) REFERENCES savings_accounts(id),
    FOREIGN KEY (to_account_id) REFERENCES savings_accounts(id)
);

-- ATM Transactions Table
CREATE TABLE atm_transactions (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT NOT NULL DEFAULT 1,
    member_id INT NOT NULL,
    account_id INT NOT NULL,
    amount DECIMAL(15,2) NOT NULL,
    fee DECIMAL(10,2) DEFAULT 0,
    atm_location VARCHAR(255) NULL,
    transaction_type ENUM('withdrawal', 'balance_check') DEFAULT 'withdrawal',
    status ENUM('pending', 'completed', 'failed') DEFAULT 'completed',
    completed_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_tenant_member (tenant_id, member_id),
    INDEX idx_account (account_id),
    FOREIGN KEY (tenant_id) REFERENCES tenants(id),
    FOREIGN KEY (member_id) REFERENCES members(id),
    FOREIGN KEY (account_id) REFERENCES savings_accounts(id)
);

-- API Routes to add:
POST /api/banking/dashboard -> OnlineBankingController::getDashboard
POST /api/banking/transfer/member -> OnlineBankingController::transferToMember
POST /api/banking/transfer/bank -> OnlineBankingController::transferToBank
POST /api/banking/virtual-account -> OnlineBankingController::generateVirtualAccount
POST /api/banking/atm/withdraw -> OnlineBankingController::atmWithdrawal
GET  /api/banking/transfers -> OnlineBankingController::getTransferHistory
*/

?>
