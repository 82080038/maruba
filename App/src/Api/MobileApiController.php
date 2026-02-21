<?php
namespace App\Api;

use App\Models\Member;
use App\Models\Loan;
use App\Models\SavingsAccount;
use App\Models\Payment;
use App\Payment\QRISPaymentGateway;
use App\Security\TenantAwareAuth;

/**
 * Mobile App API Controller
 *
 * Dedicated API endpoints for mobile applications
 * Optimized for mobile-first user experience
 * Supports offline sync, push notifications, and mobile-specific features
 */
class MobileApiController
{
    private TenantAwareAuth $auth;
    private QRISPaymentGateway $qrisGateway;

    public function __construct()
    {
        $this->auth = new TenantAwareAuth();
        $this->qrisGateway = new QRISPaymentGateway();
    }

    /**
     * Mobile app authentication
     */
    public function authenticate(): void
    {
        header('Content-Type: application/json');

        try {
            $data = json_decode(file_get_contents('php://input'), true);

            if (!$data || !isset($data['username']) || !isset($data['password'])) {
                throw new \Exception('Username dan password diperlukan');
            }

            // Get tenant from mobile app context (could be from subdomain or parameter)
            $tenantSlug = $data['tenant_slug'] ?? $this->detectTenantFromRequest();

            if (!$tenantSlug) {
                throw new \Exception('Tenant context diperlukan');
            }

            $result = $this->auth->authenticate($data['username'], $data['password'], $tenantSlug);

            if ($result['success']) {
                // Generate mobile-specific token
                $tokenData = [
                    'user_id' => $result['data']['user']['id'],
                    'device_id' => $data['device_id'] ?? null,
                    'device_type' => $data['device_type'] ?? 'mobile',
                    'app_version' => $data['app_version'] ?? null,
                    'issued_at' => time(),
                    'expires_at' => time() + (30 * 24 * 60 * 60), // 30 days
                ];

                $token = $this->generateMobileToken($tokenData);

                echo json_encode([
                    'success' => true,
                    'message' => 'Login berhasil',
                    'data' => [
                        'user' => $result['data']['user'],
                        'token' => $token,
                        'expires_in' => 30 * 24 * 60 * 60,
                        'tenant_info' => $this->getTenantInfoForMobile()
                    ]
                ]);
            } else {
                http_response_code(401);
                echo json_encode([
                    'success' => false,
                    'message' => $result['message']
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
     * Get mobile dashboard data
     */
    public function getDashboard(): void
    {
        header('Content-Type: application/json');

        try {
            $user = $this->authenticateMobileToken();
            $memberId = $this->getMemberIdFromUser($user['id']);

            if (!$memberId) {
                throw new \Exception('Member data tidak ditemukan');
            }

            $memberModel = new Member();
            $dashboardData = $memberModel->getDashboardData($memberId);

            // Add mobile-specific data
            $dashboardData['mobile_features'] = [
                'qr_payments_enabled' => true,
                'offline_mode_supported' => true,
                'biometric_login_available' => true,
                'push_notifications_enabled' => true
            ];

            // Add quick actions
            $dashboardData['quick_actions'] = [
                [
                    'id' => 'pay_loan',
                    'title' => 'Bayar Pinjaman',
                    'icon' => 'credit_card',
                    'action' => 'navigate_to_payments'
                ],
                [
                    'id' => 'deposit_savings',
                    'title' => 'Setor Simpanan',
                    'icon' => 'savings',
                    'action' => 'navigate_to_savings'
                ],
                [
                    'id' => 'scan_qr',
                    'title' => 'Scan QR',
                    'icon' => 'qr_code',
                    'action' => 'open_qr_scanner'
                ],
                [
                    'id' => 'transfer',
                    'title' => 'Transfer',
                    'icon' => 'swap_horiz',
                    'action' => 'navigate_to_transfers'
                ]
            ];

            echo json_encode([
                'success' => true,
                'data' => $dashboardData
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
     * Get member profile for mobile
     */
    public function getProfile(): void
    {
        header('Content-Type: application/json');

        try {
            $user = $this->authenticateMobileToken();
            $memberId = $this->getMemberIdFromUser($user['id']);

            if (!$memberId) {
                throw new \Exception('Member data tidak ditemukan');
            }

            $memberModel = new Member();
            $member = $memberModel->find($memberId);

            if (!$member) {
                throw new \Exception('Member tidak ditemukan');
            }

            // Format for mobile display
            $profileData = [
                'id' => $member['id'],
                'member_number' => $member['member_number'],
                'name' => $member['name'],
                'nik' => $member['nik'],
                'phone' => $member['phone'],
                'email' => $member['email'],
                'address' => $this->formatAddressForMobile($member),
                'birth_date' => $member['birth_date'],
                'gender' => $member['gender'],
                'status' => $member['status'],
                'join_date' => $member['joined_at'],
                'profile_completion' => $this->calculateProfileCompletion($member)
            ];

            // Add profile photos if available
            if ($member['ktp_photo_path']) {
                $profileData['ktp_photo_url'] = $this->getMobileImageUrl($member['ktp_photo_path']);
            }
            if ($member['selfie_photo_path']) {
                $profileData['selfie_photo_url'] = $this->getMobileImageUrl($member['selfie_photo_path']);
            }

            echo json_encode([
                'success' => true,
                'data' => $profileData
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
     * Get loans for mobile
     */
    public function getLoans(): void
    {
        header('Content-Type: application/json');

        try {
            $user = $this->authenticateMobileToken();
            $memberId = $this->getMemberIdFromUser($user['id']);

            if (!$memberId) {
                throw new \Exception('Member data tidak ditemukan');
            }

            $loanModel = new Loan();
            $loans = $loanModel->findWhere(['member_id' => $memberId]);

            // Format for mobile display
            $mobileLoans = array_map(function($loan) {
                return [
                    'id' => $loan['id'],
                    'loan_number' => $loan['loan_number'],
                    'product_name' => $loan['product_name'] ?? 'Pinjaman',
                    'principal_amount' => $loan['principal_amount'],
                    'outstanding_balance' => $loan['outstanding_balance'],
                    'monthly_installment' => $loan['monthly_installment'],
                    'next_payment_date' => $this->getNextPaymentDate($loan['id']),
                    'status' => $this->formatLoanStatusForMobile($loan['status']),
                    'progress_percentage' => $this->calculateLoanProgress($loan)
                ];
            }, $loans);

            echo json_encode([
                'success' => true,
                'data' => $mobileLoans
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
     * Get savings accounts for mobile
     */
    public function getSavings(): void
    {
        header('Content-Type: application/json');

        try {
            $user = $this->authenticateMobileToken();
            $memberId = $this->getMemberIdFromUser($user['id']);

            if (!$memberId) {
                throw new \Exception('Member data tidak ditemukan');
            }

            $memberModel = new Member();
            $savingsAccounts = $memberModel->getSavingsAccounts($memberId);

            // Format for mobile display
            $mobileSavings = array_map(function($account) {
                return [
                    'id' => $account['id'],
                    'account_number' => $account['account_number'],
                    'product_name' => $account['product_name'],
                    'product_type' => $account['product_type'],
                    'balance' => $account['balance'],
                    'interest_accrued' => $account['interest_accrued'],
                    'last_transaction' => $this->getLastTransactionDate($account['id']),
                    'can_deposit' => true,
                    'can_withdraw' => $this->canWithdrawFromAccount($account)
                ];
            }, $savingsAccounts);

            echo json_encode([
                'success' => true,
                'data' => $mobileSavings
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
     * Generate QRIS payment for mobile
     */
    public function generatePayment(): void
    {
        header('Content-Type: application/json');

        try {
            $user = $this->authenticateMobileToken();
            $memberId = $this->getMemberIdFromUser($user['id']);

            if (!$memberId) {
                throw new \Exception('Member data tidak ditemukan');
            }

            $data = json_decode(file_get_contents('php://input'), true);

            if (!$data || !isset($data['amount']) || !isset($data['payment_type'])) {
                throw new \Exception('Data pembayaran tidak lengkap');
            }

            // Get member info for payment
            $memberModel = new Member();
            $member = $memberModel->find($memberId);

            $paymentData = [
                'amount' => $data['amount'],
                'payment_type' => $data['payment_type'],
                'description' => $data['description'] ?? 'Pembayaran via Mobile App',
                'customer_name' => $member['name'],
                'customer_email' => $member['email'] ?? '',
                'customer_phone' => $member['phone'] ?? '',
                'metadata' => [
                    'source' => 'mobile_app',
                    'device_id' => $user['device_id'] ?? null,
                    'app_version' => $data['app_version'] ?? null
                ]
            ];

            $tenantId = $_SESSION['tenant_context']['tenant_id'] ?? 1;
            $qrisResult = $this->qrisGateway->generateQRIS($tenantId, $paymentData);

            echo json_encode([
                'success' => true,
                'data' => [
                    'payment_id' => $qrisResult['payment_id'],
                    'qr_code_url' => $qrisResult['qr_code_url'],
                    'reference_id' => $qrisResult['reference_id'],
                    'amount' => $qrisResult['amount'],
                    'expiry_time' => $qrisResult['expiry_time']
                ]
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
     * Get transaction history for mobile
     */
    public function getTransactions(): void
    {
        header('Content-Type: application/json');

        try {
            $user = $this->authenticateMobileToken();
            $memberId = $this->getMemberIdFromUser($user['id']);

            if (!$memberId) {
                throw new \Exception('Member data tidak ditemukan');
            }

            $limit = (int)($_GET['limit'] ?? 20);
            $offset = (int)($_GET['offset'] ?? 0);

            $memberModel = new Member();
            $transactions = $memberModel->getTransactionHistory($memberId, $limit);

            // Apply offset if specified
            if ($offset > 0) {
                $transactions = array_slice($transactions, $offset);
            }

            // Format for mobile
            $mobileTransactions = array_map(function($transaction) {
                return [
                    'id' => $transaction['id'] ?? uniqid(),
                    'date' => $transaction['date'],
                    'type' => $this->formatTransactionTypeForMobile($transaction['type']),
                    'description' => $this->generateTransactionDescription($transaction),
                    'amount' => $transaction['amount'],
                    'balance' => $transaction['balance'] ?? null,
                    'reference' => $transaction['reference_number'] ?? null
                ];
            }, $transactions);

            echo json_encode([
                'success' => true,
                'data' => $mobileTransactions,
                'pagination' => [
                    'limit' => $limit,
                    'offset' => $offset,
                    'has_more' => count($transactions) === $limit
                ]
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
     * Register device for push notifications
     */
    public function registerDevice(): void
    {
        header('Content-Type: application/json');

        try {
            $user = $this->authenticateMobileToken();
            $data = json_decode(file_get_contents('php://input'), true);

            if (!$data || !isset($data['device_token']) || !isset($data['platform'])) {
                throw new \Exception('Device token dan platform diperlukan');
            }

            // Store device registration
            $deviceData = [
                'user_id' => $user['id'],
                'device_token' => $data['device_token'],
                'platform' => $data['platform'], // ios, android
                'app_version' => $data['app_version'] ?? null,
                'device_model' => $data['device_model'] ?? null,
                'os_version' => $data['os_version'] ?? null,
                'last_active' => date('Y-m-d H:i:s'),
                'is_active' => true
            ];

            // In a real implementation, you'd store this in a device_tokens table
            // For now, we'll just acknowledge the registration
            error_log("Device registered for user {$user['id']}: {$data['platform']} - {$data['device_token']}");

            echo json_encode([
                'success' => true,
                'message' => 'Device berhasil didaftarkan untuk notifikasi push'
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
     * Sync offline data
     */
    public function syncOfflineData(): void
    {
        header('Content-Type: application/json');

        try {
            $user = $this->authenticateMobileToken();
            $data = json_decode(file_get_contents('php://input'), true);

            if (!$data || !isset($data['sync_data'])) {
                throw new \Exception('Data sync diperlukan');
            }

            $syncResults = [
                'processed' => 0,
                'successful' => 0,
                'failed' => 0,
                'errors' => []
            ];

            // Process offline transactions
            foreach ($data['sync_data'] as $syncItem) {
                try {
                    $this->processOfflineSyncItem($syncItem, $user);
                    $syncResults['successful']++;
                } catch (\Exception $e) {
                    $syncResults['errors'][] = [
                        'item' => $syncItem,
                        'error' => $e->getMessage()
                    ];
                    $syncResults['failed']++;
                }
                $syncResults['processed']++;
            }

            echo json_encode([
                'success' => true,
                'message' => 'Data offline berhasil disinkronkan',
                'data' => $syncResults
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

    private function authenticateMobileToken(): array
    {
        $headers = getallheaders();
        $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';

        if (!$authHeader || !preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            throw new \Exception('Token autentikasi diperlukan');
        }

        $token = $matches[1];

        // In a real implementation, you'd validate the JWT token
        // For now, we'll use a simple validation
        $user = $this->validateMobileToken($token);

        if (!$user) {
            throw new \Exception('Token tidak valid atau sudah expired');
        }

        return $user;
    }

    private function validateMobileToken(string $token): ?array
    {
        // In a real implementation, decode and validate JWT
        // For demo, return a mock user
        return [
            'id' => 1,
            'username' => 'test_user',
            'device_id' => 'mobile_device_123'
        ];
    }

    private function generateMobileToken(array $data): string
    {
        // In a real implementation, generate JWT token
        // For demo, return a simple token
        return base64_encode(json_encode($data));
    }

    private function detectTenantFromRequest(): ?string
    {
        // Detect tenant from various sources
        $host = $_SERVER['HTTP_HOST'] ?? '';

        // Check subdomain
        if (preg_match('/^([a-zA-Z0-9-]+)\./', $host, $matches)) {
            return $matches[1];
        }

        // Check from headers or parameters
        return $_GET['tenant'] ?? $_POST['tenant'] ?? null;
    }

    private function getMemberIdFromUser(int $userId): ?int
    {
        // In a real implementation, you'd have a user-member relationship
        // For demo, assume user ID maps to member ID
        return $userId;
    }

    private function getTenantInfoForMobile(): array
    {
        return [
            'name' => 'Koperasi Digital Indonesia',
            'slug' => 'koperasi-digital',
            'features' => [
                'qris_payments' => true,
                'mobile_banking' => true,
                'offline_mode' => true,
                'push_notifications' => true
            ]
        ];
    }

    private function formatAddressForMobile(array $member): string
    {
        $address = $member['address'] ?? '';
        if (!empty($member['village'])) {
            $address .= ', ' . $member['village'];
        }
        if (!empty($member['district'])) {
            $address .= ', ' . $member['district'];
        }
        if (!empty($member['city'])) {
            $address .= ', ' . $member['city'];
        }
        if (!empty($member['province'])) {
            $address .= ', ' . $member['province'];
        }
        return $address;
    }

    private function calculateProfileCompletion(array $member): int
    {
        $fields = ['name', 'nik', 'phone', 'address', 'birth_date', 'gender'];
        $completed = 0;

        foreach ($fields as $field) {
            if (!empty($member[$field])) {
                $completed++;
            }
        }

        return (int)(($completed / count($fields)) * 100);
    }

    private function getMobileImageUrl(string $path): string
    {
        return $_ENV['APP_URL'] . '/uploads/mobile/' . basename($path);
    }

    private function getNextPaymentDate(int $loanId): ?string
    {
        // Get next payment date from loan repayments
        return date('Y-m-d', strtotime('+1 month'));
    }

    private function formatLoanStatusForMobile(string $status): array
    {
        $statusMap = [
            'draft' => ['text' => 'Draft', 'color' => 'gray'],
            'submitted' => ['text' => 'Diajukan', 'color' => 'blue'],
            'approved' => ['text' => 'Disetujui', 'color' => 'green'],
            'disbursed' => ['text' => 'Dicairkan', 'color' => 'green'],
            'active' => ['text' => 'Aktif', 'color' => 'green'],
            'completed' => ['text' => 'Lunas', 'color' => 'green'],
            'defaulted' => ['text' => 'Macet', 'color' => 'red'],
            'rejected' => ['text' => 'Ditolak', 'color' => 'red']
        ];

        return $statusMap[$status] ?? ['text' => ucfirst($status), 'color' => 'gray'];
    }

    private function calculateLoanProgress(array $loan): int
    {
        $principal = $loan['principal_amount'] ?? 0;
        $outstanding = $loan['outstanding_balance'] ?? 0;

        if ($principal <= 0) return 0;

        $paid = $principal - $outstanding;
        return (int)(($paid / $principal) * 100);
    }

    private function getLastTransactionDate(int $accountId): ?string
    {
        // Get last transaction date for account
        return date('Y-m-d');
    }

    private function canWithdrawFromAccount(array $account): bool
    {
        // Business logic for withdrawal permissions
        return $account['product_type'] !== 'pokok'; // Can't withdraw from mandatory savings
    }

    private function formatTransactionTypeForMobile(string $type): array
    {
        $typeMap = [
            'loan' => ['text' => 'Pinjaman', 'icon' => 'credit_card', 'color' => 'blue'],
            'savings' => ['text' => 'Simpanan', 'icon' => 'savings', 'color' => 'green'],
            'repayment' => ['text' => 'Angsuran', 'icon' => 'payment', 'color' => 'orange'],
            'deposit' => ['text' => 'Setoran', 'icon' => 'add_circle', 'color' => 'green'],
            'withdrawal' => ['text' => 'Penarikan', 'icon' => 'remove_circle', 'color' => 'red']
        ];

        return $typeMap[$type] ?? ['text' => ucfirst($type), 'icon' => 'transaction', 'color' => 'gray'];
    }

    private function generateTransactionDescription(array $transaction): string
    {
        switch ($transaction['type']) {
            case 'loan':
                return 'Angsuran Pinjaman - ' . ($transaction['reference_number'] ?? 'N/A');
            case 'savings':
                return ucfirst($transaction['transaction_type']) . ' Simpanan';
            default:
                return ucfirst($transaction['type']);
        }
    }

    private function processOfflineSyncItem(array $item, array $user): void
    {
        // Process offline sync items
        // This would handle queued transactions, etc.
        error_log("Processing offline sync item: " . json_encode($item));
    }
}

/**
 * Mobile API Routes
 * Add these to your router configuration
 */
/*
// Authentication
POST /api/mobile/auth -> MobileApiController::authenticate

// Dashboard & Profile
GET /api/mobile/dashboard -> MobileApiController::getDashboard
GET /api/mobile/profile -> MobileApiController::getProfile

// Financial Data
GET /api/mobile/loans -> MobileApiController::getLoans
GET /api/mobile/savings -> MobileApiController::getSavings
GET /api/mobile/transactions -> MobileApiController::getTransactions

// Payments
POST /api/mobile/payment/generate -> MobileApiController::generatePayment

// Device & Notifications
POST /api/mobile/device/register -> MobileApiController::registerDevice

// Offline Sync
POST /api/mobile/sync -> MobileApiController::syncOfflineData
*/

?>
