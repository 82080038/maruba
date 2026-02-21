<?php
namespace App\Services;

use App\Models\Member;
use App\Models\SavingsAccount;

/**
 * PPOB (Payment Point Online Bank) Services
 *
 * Essential for modern Indonesian KSP platforms
 * Major revenue generator through bill payments, top-ups, vouchers
 * Competitive advantage over traditional cooperatives
 */
class PPOBService
{
    private Member $memberModel;
    private SavingsAccount $savingsModel;

    // PPOB service providers configuration
    private array $providers = [
        'pulsa' => [
            'name' => 'Pulsa & Data Packages',
            'fee_percentage' => 0.02, // 2%
            'min_amount' => 5000,
            'max_amount' => 1000000
        ],
        'pln' => [
            'name' => 'PLN Electricity Bills',
            'fee_percentage' => 0.015, // 1.5%
            'min_amount' => 10000,
            'max_amount' => 5000000
        ],
        'bpjs' => [
            'name' => 'BPJS Health & Pension',
            'fee_percentage' => 0.01, // 1%
            'min_amount' => 25000,
            'max_amount' => 1000000
        ],
        'telkom' => [
            'name' => 'Telkom Bills',
            'fee_percentage' => 0.015, // 1.5%
            'min_amount' => 50000,
            'max_amount' => 2000000
        ],
        'pdam' => [
            'name' => 'PDAM Water Bills',
            'fee_percentage' => 0.02, // 2%
            'min_amount' => 25000,
            'max_amount' => 1000000
        ],
        'internet' => [
            'name' => 'Internet & Cable TV Bills',
            'fee_percentage' => 0.018, // 1.8%
            'min_amount' => 50000,
            'max_amount' => 1500000
        ],
        'ewallet' => [
            'name' => 'E-Wallet Top Up',
            'fee_percentage' => 0.025, // 2.5%
            'min_amount' => 10000,
            'max_amount' => 2000000
        ],
        'voucher' => [
            'name' => 'Game & Shopping Vouchers',
            'fee_percentage' => 0.03, // 3%
            'min_amount' => 10000,
            'max_amount' => 500000
        ]
    ];

    public function __construct()
    {
        $this->memberModel = new Member();
        $this->savingsModel = new SavingsAccount();
    }

    /**
     * Get available PPOB services
     */
    public function getAvailableServices(): array
    {
        $services = [];

        foreach ($this->providers as $code => $config) {
            $services[$code] = [
                'code' => $code,
                'name' => $config['name'],
                'fee_percentage' => $config['fee_percentage'],
                'min_amount' => $config['min_amount'],
                'max_amount' => $config['max_amount'],
                'fee_example' => $this->calculateFee(100000, $config['fee_percentage']),
                'is_active' => true
            ];
        }

        return $services;
    }

    /**
     * Process PPOB transaction
     */
    public function processTransaction(array $transactionData): array
    {
        $memberId = $transactionData['member_id'];
        $serviceCode = $transactionData['service_code'];
        $amount = $transactionData['amount'];
        $customerNumber = $transactionData['customer_number'];
        $description = $transactionData['description'] ?? '';

        // Validate service
        if (!isset($this->providers[$serviceCode])) {
            throw new \Exception('Layanan PPOB tidak tersedia');
        }

        $service = $this->providers[$serviceCode];

        // Validate amount
        if ($amount < $service['min_amount'] || $amount > $service['max_amount']) {
            throw new \Exception("Nominal harus antara Rp " . number_format($service['min_amount']) . " - Rp " . number_format($service['max_amount']));
        }

        // Calculate fee
        $fee = $this->calculateFee($amount, $service['fee_percentage']);
        $totalAmount = $amount + $fee;

        // Get member account
        $memberAccount = $this->getMemberAccount($memberId);
        if (!$memberAccount) {
            throw new \Exception('Rekening simpanan tidak ditemukan');
        }

        // Check balance
        if ($memberAccount['balance'] < $totalAmount) {
            throw new \Exception('Saldo tidak mencukupi untuk transaksi ini');
        }

        // Begin transaction
        $this->db->beginTransaction();

        try {
            // Debit member account
            $this->updateAccountBalance($memberAccount['id'], -$totalAmount);
            $this->createTransaction($memberAccount['id'], $memberId, 'ppob_payment', $totalAmount,
                $memberAccount['balance'] - $totalAmount, $memberAccount['balance'] - $totalAmount,
                "PPOB: {$service['name']} - {$customerNumber} (biaya: Rp " . number_format($fee) . ")");

            // Create PPOB transaction record
            $ppobId = $this->createPPOBTransaction([
                'member_id' => $memberId,
                'account_id' => $memberAccount['id'],
                'service_code' => $serviceCode,
                'service_name' => $service['name'],
                'customer_number' => $customerNumber,
                'amount' => $amount,
                'fee' => $fee,
                'total_amount' => $totalAmount,
                'description' => $description,
                'status' => 'processing',
                'reference_number' => $this->generateReferenceNumber()
            ]);

            $this->db->commit();

            // Queue for processing (would integrate with PPOB provider API)
            $this->queuePPOBProcessing($ppobId, $transactionData);

            // Send notification
            $this->sendPPOBNotification($memberId, $ppobId, $service['name'], $totalAmount);

            return [
                'success' => true,
                'transaction_id' => $ppobId,
                'reference_number' => $this->getReferenceNumber($ppobId),
                'service_name' => $service['name'],
                'amount' => $amount,
                'fee' => $fee,
                'total_amount' => $totalAmount,
                'status' => 'processing',
                'estimated_completion' => date('Y-m-d H:i:s', strtotime('+5 minutes'))
            ];

        } catch (\Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Check PPOB transaction status
     */
    public function checkTransactionStatus(string $referenceNumber): array
    {
        $stmt = $this->db->prepare("
            SELECT * FROM ppob_transactions
            WHERE reference_number = ?
        ");
        $stmt->execute([$referenceNumber]);
        $transaction = $stmt->fetch();

        if (!$transaction) {
            return ['error' => 'Transaksi tidak ditemukan'];
        }

        return [
            'reference_number' => $referenceNumber,
            'status' => $transaction['status'],
            'service_name' => $transaction['service_name'],
            'amount' => $transaction['amount'],
            'fee' => $transaction['fee'],
            'total_amount' => $transaction['total_amount'],
            'customer_number' => $transaction['customer_number'],
            'created_at' => $transaction['created_at'],
            'completed_at' => $transaction['completed_at'],
            'failure_reason' => $transaction['failure_reason']
        ];
    }

    /**
     * Get PPOB transaction history
     */
    public function getTransactionHistory(int $memberId, int $limit = 20): array
    {
        $stmt = $this->db->prepare("
            SELECT
                reference_number,
                service_name,
                service_code,
                customer_number,
                amount,
                fee,
                total_amount,
                status,
                created_at,
                completed_at
            FROM ppob_transactions
            WHERE member_id = ?
            ORDER BY created_at DESC
            LIMIT ?
        ");
        $stmt->execute([$memberId, $limit]);
        return $stmt->fetchAll();
    }

    /**
     * Get PPOB service details and pricing
     */
    public function getServiceDetails(string $serviceCode): array
    {
        if (!isset($this->providers[$serviceCode])) {
            throw new \Exception('Layanan tidak tersedia');
        }

        $service = $this->providers[$serviceCode];

        // Get popular denominations/pricing for this service
        $pricing = $this->getServicePricing($serviceCode);

        return [
            'code' => $serviceCode,
            'name' => $service['name'],
            'fee_percentage' => $service['fee_percentage'],
            'min_amount' => $service['min_amount'],
            'max_amount' => $service['max_amount'],
            'pricing' => $pricing,
            'description' => $this->getServiceDescription($serviceCode),
            'processing_time' => $this->getServiceProcessingTime($serviceCode),
            'is_active' => true
        ];
    }

    /**
     * Get popular PPOB services summary
     */
    public function getPopularServices(): array
    {
        $popularServices = [
            'pulsa' => ['count' => 0, 'revenue' => 0],
            'pln' => ['count' => 0, 'revenue' => 0],
            'ewallet' => ['count' => 0, 'revenue' => 0],
            'bpjs' => ['count' => 0, 'revenue' => 0]
        ];

        // In real implementation, get from database
        // For demo, return static data
        return [
            [
                'code' => 'pulsa',
                'name' => 'Pulsa & Paket Data',
                'monthly_transactions' => 1250,
                'monthly_revenue' => 750000,
                'growth_percentage' => 15.5
            ],
            [
                'code' => 'pln',
                'name' => 'Token Listrik PLN',
                'monthly_transactions' => 890,
                'monthly_revenue' => 445000,
                'growth_percentage' => 22.3
            ],
            [
                'code' => 'ewallet',
                'name' => 'Top Up E-Wallet',
                'monthly_transactions' => 675,
                'monthly_revenue' => 202500,
                'growth_percentage' => 31.2
            ],
            [
                'code' => 'bpjs',
                'name' => 'BPJS Kesehatan',
                'monthly_transactions' => 445,
                'monthly_revenue' => 111250,
                'growth_percentage' => 8.7
            ]
        ];
    }

    /**
     * Get PPOB statistics for dashboard
     */
    public function getPPOBStatistics(int $tenantId, string $period = '30d'): array
    {
        // In real implementation, query database for statistics
        // For demo, return sample data
        return [
            'total_transactions' => 3260,
            'total_revenue' => 1508750,
            'successful_transactions' => 3205,
            'failed_transactions' => 55,
            'success_rate' => 98.3,
            'average_transaction' => 46250,
            'top_services' => $this->getPopularServices(),
            'period' => $period
        ];
    }

    // =========================================
    // HELPER METHODS
    // =========================================

    private function get db()
    {
        return \App\Database::getConnection();
    }

    private function calculateFee(float $amount, float $percentage): float
    {
        return ceil($amount * $percentage);
    }

    private function getMemberAccount(int $memberId): ?array
    {
        // Get member's primary savings account
        $accounts = $this->memberModel->getSavingsAccounts($memberId);

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

    private function createPPOBTransaction(array $data): int
    {
        $stmt = $this->db->prepare("
            INSERT INTO ppob_transactions (
                tenant_id, member_id, account_id, service_code, service_name,
                customer_number, amount, fee, total_amount, description,
                status, reference_number, created_at
            ) VALUES (1, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");

        $stmt->execute([
            $data['member_id'],
            $data['account_id'],
            $data['service_code'],
            $data['service_name'],
            $data['customer_number'],
            $data['amount'],
            $data['fee'],
            $data['total_amount'],
            $data['description'],
            $data['status'],
            $data['reference_number']
        ]);

        return $this->db->lastInsertId();
    }

    private function generateReferenceNumber(): string
    {
        return 'PPOB' . date('YmdHis') . rand(100, 999);
    }

    private function getReferenceNumber(int $ppobId): string
    {
        $stmt = $this->db->prepare("SELECT reference_number FROM ppob_transactions WHERE id = ?");
        $stmt->execute([$ppobId]);
        return $stmt->fetch()['reference_number'];
    }

    private function queuePPOBProcessing(int $ppobId, array $transactionData): void
    {
        // Queue for PPOB provider processing
        // In real implementation, would use queue system
        error_log("PPOB transaction queued for processing: PPOB ID {$ppobId}");

        // Simulate processing completion (in real app, this would be async)
        $this->completePPOBTransaction($ppobId);
    }

    private function completePPOBTransaction(int $ppobId): void
    {
        $stmt = $this->db->prepare("
            UPDATE ppob_transactions
            SET status = 'completed', completed_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$ppobId]);
    }

    private function sendPPOBNotification(int $memberId, int $ppobId, string $serviceName, float $totalAmount): void
    {
        // Send PPOB transaction notification
        // Would integrate with MultiChannelNotifier
        error_log("PPOB notification sent for member {$memberId}, service: {$serviceName}, amount: {$totalAmount}");
    }

    private function getServicePricing(string $serviceCode): array
    {
        // Return popular pricing/denominations for each service
        $pricing = [
            'pulsa' => [
                ['amount' => 10000, 'fee' => 200],
                ['amount' => 25000, 'fee' => 500],
                ['amount' => 50000, 'fee' => 1000],
                ['amount' => 100000, 'fee' => 2000]
            ],
            'pln' => [
                ['amount' => 20000, 'fee' => 300],
                ['amount' => 50000, 'fee' => 750],
                ['amount' => 100000, 'fee' => 1500],
                ['amount' => 500000, 'fee' => 7500]
            ],
            'ewallet' => [
                ['amount' => 25000, 'fee' => 625],
                ['amount' => 50000, 'fee' => 1250],
                ['amount' => 100000, 'fee' => 2500],
                ['amount' => 200000, 'fee' => 5000]
            ]
        ];

        return $pricing[$serviceCode] ?? [];
    }

    private function getServiceDescription(string $serviceCode): string
    {
        $descriptions = [
            'pulsa' => 'Isi ulang pulsa dan paket data untuk semua operator Indonesia (Telkomsel, Indosat, XL, Tri, Smartfren)',
            'pln' => 'Pembayaran token listrik PLN dan tagihan listrik pascabayar',
            'bpjs' => 'Pembayaran iuran BPJS Kesehatan dan Ketenagakerjaan',
            'telkom' => 'Pembayaran tagihan Telkom (telepon rumah, Speedy, IndiHome)',
            'pdam' => 'Pembayaran tagihan PDAM air bersih di seluruh Indonesia',
            'internet' => 'Pembayaran tagihan internet dan TV kabel',
            'ewallet' => 'Top up saldo Gopay, OVO, Dana, LinkAja, ShopeePay, dan e-wallet lainnya',
            'voucher' => 'Pembelian voucher game (Free Fire, Mobile Legends, PUBG) dan voucher belanja'
        ];

        return $descriptions[$serviceCode] ?? 'Layanan pembayaran digital';
    }

    private function getServiceProcessingTime(string $serviceCode): string
    {
        $times = [
            'pulsa' => 'Instant (1-5 detik)',
            'pln' => 'Instant (1-10 detik)',
            'bpjs' => 'Instant (1-5 detik)',
            'telkom' => 'Instant (1-5 detik)',
            'pdam' => 'Instant (1-5 detik)',
            'internet' => 'Instant (1-5 detik)',
            'ewallet' => 'Instant (1-10 detik)',
            'voucher' => 'Instant (1-5 detik)'
        ];

        return $times[$serviceCode] ?? 'Instant (1-10 detik)';
    }
}

/**
 * PPOB API Controller
 */
class PPOBController
{
    private PPOBService $ppobService;

    public function __construct()
    {
        $this->ppobService = new PPOBService();
    }

    /**
     * Get available PPOB services
     */
    public function getServices(): void
    {
        header('Content-Type: application/json');

        try {
            $services = $this->ppobService->getAvailableServices();

            echo json_encode([
                'success' => true,
                'data' => $services
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
     * Get service details
     */
    public function getServiceDetails(): void
    {
        header('Content-Type: application/json');

        try {
            $serviceCode = $_GET['service_code'] ?? '';

            if (!$serviceCode) {
                throw new \Exception('Kode layanan diperlukan');
            }

            $details = $this->ppobService->getServiceDetails($serviceCode);

            echo json_encode([
                'success' => true,
                'data' => $details
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
     * Process PPOB transaction
     */
    public function processTransaction(): void
    {
        header('Content-Type: application/json');

        try {
            $user = $this->authenticateUser();
            $data = json_decode(file_get_contents('php://input'), true);

            if (!$data || !isset($data['service_code']) || !isset($data['amount']) || !isset($data['customer_number'])) {
                throw new \Exception('Data transaksi tidak lengkap');
            }

            $memberId = $this->getMemberIdFromUser($user['id']);
            $transactionData = array_merge($data, ['member_id' => $memberId]);

            $result = $this->ppobService->processTransaction($transactionData);

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
     * Check transaction status
     */
    public function checkStatus(): void
    {
        header('Content-Type: application/json');

        try {
            $referenceNumber = $_GET['reference_number'] ?? '';

            if (!$referenceNumber) {
                throw new \Exception('Nomor referensi diperlukan');
            }

            $status = $this->ppobService->checkTransactionStatus($referenceNumber);

            if (isset($status['error'])) {
                echo json_encode([
                    'success' => false,
                    'message' => $status['error']
                ]);
            } else {
                echo json_encode([
                    'success' => true,
                    'data' => $status
                ]);
            }

        } catch (\Exception $e) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Get transaction history
     */
    public function getHistory(): void
    {
        header('Content-Type: application/json');

        try {
            $user = $this->authenticateUser();
            $memberId = $this->getMemberIdFromUser($user['id']);
            $limit = (int)($_GET['limit'] ?? 20);

            $history = $this->ppobService->getTransactionHistory($memberId, $limit);

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
     * Get PPOB statistics
     */
    public function getStatistics(): void
    {
        header('Content-Type: application/json');

        try {
            $tenantId = $_SESSION['tenant_context']['tenant_id'] ?? 1;
            $period = $_GET['period'] ?? '30d';

            $stats = $this->ppobService->getPPOBStatistics($tenantId, $period);

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

    /**
     * Get popular services
     */
    public function getPopularServices(): void
    {
        header('Content-Type: application/json');

        try {
            $services = $this->ppobService->getPopularServices();

            echo json_encode([
                'success' => true,
                'data' => $services
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
        return [
            'id' => 1,
            'username' => 'test_user'
        ];
    }

    private function getMemberIdFromUser(int $userId): int
    {
        // In real implementation, get member ID from user relationship
        return $userId;
    }
}

// =========================================
// PPOB DATABASE TABLES
// =========================================

/*
-- PPOB Transactions Table
CREATE TABLE ppob_transactions (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT NOT NULL DEFAULT 1,
    member_id INT NOT NULL,
    account_id INT NOT NULL,
    service_code VARCHAR(20) NOT NULL,
    service_name VARCHAR(100) NOT NULL,
    customer_number VARCHAR(50) NOT NULL,
    amount DECIMAL(15,2) NOT NULL,
    fee DECIMAL(10,2) NOT NULL,
    total_amount DECIMAL(15,2) NOT NULL,
    description TEXT NULL,
    status ENUM('pending', 'processing', 'completed', 'failed') DEFAULT 'processing',
    reference_number VARCHAR(50) NOT NULL UNIQUE,
    provider_reference VARCHAR(100) NULL,
    failure_reason TEXT NULL,
    completed_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_tenant_member (tenant_id, member_id),
    INDEX idx_reference (reference_number),
    INDEX idx_status (status),
    FOREIGN KEY (tenant_id) REFERENCES tenants(id),
    FOREIGN KEY (member_id) REFERENCES members(id),
    FOREIGN KEY (account_id) REFERENCES savings_accounts(id)
);

-- PPOB Service Providers Table
CREATE TABLE ppob_providers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(20) NOT NULL UNIQUE,
    name VARCHAR(100) NOT NULL,
    api_endpoint VARCHAR(255) NOT NULL,
    api_key VARCHAR(255) NOT NULL,
    secret_key VARCHAR(255) NOT NULL,
    fee_percentage DECIMAL(5,4) NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert default PPOB providers
INSERT INTO ppob_providers (code, name, api_endpoint, api_key, secret_key, fee_percentage) VALUES
('pulsa_provider', 'Pulsa Master', 'https://api.pulsa-master.com', 'api_key_here', 'secret_here', 0.02),
('pln_provider', 'PLN Gateway', 'https://api.pln-gateway.com', 'api_key_here', 'secret_here', 0.015),
('ewallet_provider', 'E-Wallet Hub', 'https://api.ewallet-hub.com', 'api_key_here', 'secret_here', 0.025);

-- API Routes to add:
GET  /api/ppob/services -> PPOBController::getServices
GET  /api/ppob/service-details -> PPOBController::getServiceDetails
POST /api/ppob/transaction -> PPOBController::processTransaction
GET  /api/ppob/status -> PPOBController::checkStatus
GET  /api/ppob/history -> PPOBController::getHistory
GET  /api/ppob/popular -> PPOBController::getPopularServices
GET  /api/ppob/stats -> PPOBController::getStatistics
*/

?>
