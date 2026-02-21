<?php
namespace App\Controllers;

use App\Models\Payment;
use App\Models\Repayment;
use App\Models\SavingsAccount;
use App\Models\Member;
use App\Helpers\AuthHelper;
use App\Helpers\FileUpload;

class PaymentController
{
    public function index(): void
    {
        require_login();
        AuthHelper::requirePermission('payments', 'view');

        $page = (int)($_GET['page'] ?? 1);
        $limit = (int)($_GET['limit'] ?? 15);
        $status = $_GET['status'] ?? null;

        $paymentModel = new Payment();
        $conditions = $status ? ['status' => $status] : [];

        $result = $paymentModel->paginate($page, $limit, $conditions);

        // Add member information
        foreach ($result['items'] as &$payment) {
            $memberModel = new Member();
            $member = $memberModel->find($payment['member_id']);
            $payment['member_name'] = $member ? $member['name'] : 'Unknown';
        }

        include view_path('payments/index');
    }

    public function create(): void
    {
        require_login();
        AuthHelper::requirePermission('payments', 'create');

        $type = $_GET['type'] ?? '';
        $referenceId = (int)($_GET['reference_id'] ?? 0);

        if (empty($type) || !$referenceId) {
            http_response_code(400);
            echo 'Payment type and reference ID required';
            return;
        }

        // Get payment details based on type
        $paymentData = $this->getPaymentData($type, $referenceId);

        if (!$paymentData) {
            http_response_code(404);
            echo 'Payment reference not found';
            return;
        }

        include view_path('payments/create');
    }

    public function store(): void
    {
        require_login();
        AuthHelper::requirePermission('payments', 'create');
        verify_csrf();

        $type = $_POST['type'] ?? '';
        $referenceId = (int)($_POST['reference_id'] ?? 0);
        $memberId = (int)($_POST['member_id'] ?? 0);
        $amount = (float)($_POST['amount'] ?? 0);
        $paymentMethod = $_POST['payment_method'] ?? 'virtual_account';

        if (empty($type) || !$referenceId || !$memberId || $amount <= 0) {
            $_SESSION['error'] = 'Data pembayaran tidak lengkap.';
            header('Location: ' . route_url('payments/create') . '?type=' . $type . '&reference_id=' . $referenceId);
            return;
        }

        $paymentModel = new Payment();

        try {
            $paymentId = 0;

            switch ($type) {
                case 'repayment':
                    $paymentId = $paymentModel->createRepaymentPayment($referenceId, $memberId, $amount);
                    break;
                case 'savings_deposit':
                    $paymentId = $paymentModel->createSavingsDepositPayment($referenceId, $memberId, $amount);
                    break;
                default:
                    $paymentId = $paymentModel->create([
                        'reference_id' => $referenceId,
                        'reference_type' => $type,
                        'member_id' => $memberId,
                        'amount' => $amount,
                        'payment_method' => $paymentMethod,
                        'status' => 'pending',
                        'notes' => $_POST['notes'] ?? ''
                    ]);
            }

            $_SESSION['success'] = 'Pembayaran berhasil dibuat.';

            // Redirect to payment details or payment gateway
            if ($paymentMethod === 'virtual_account') {
                header('Location: ' . route_url('payments/show') . '?id=' . $paymentId);
            } else {
                header('Location: ' . route_url('payments'));
            }

        } catch (\Exception $e) {
            $_SESSION['error'] = 'Gagal membuat pembayaran: ' . $e->getMessage();
            header('Location: ' . route_url('payments/create') . '?type=' . $type . '&reference_id=' . $referenceId);
        }
    }

    public function show(): void
    {
        require_login();

        $id = (int)($_GET['id'] ?? 0);
        if (!$id) {
            http_response_code(404);
            echo 'Payment not found';
            return;
        }

        $paymentModel = new Payment();
        $payment = $paymentModel->find($id);

        if (!$payment) {
            http_response_code(404);
            echo 'Payment not found';
            return;
        }

        // Check if user can view this payment (own payment or admin)
        $currentUser = current_user();
        $memberModel = new Member();
        $member = $memberModel->find($payment['member_id']);

        if (!AuthHelper::hasPermission('payments', 'view') && $member['user_id'] !== $currentUser['id']) {
            http_response_code(403);
            echo 'Access denied';
            return;
        }

        // Add member info
        $payment['member_name'] = $member ? $member['name'] : 'Unknown';
        $payment['member_phone'] = $member ? $member['phone'] : '';

        include view_path('payments/show');
    }

    public function confirmPayment(): void
    {
        require_login();
        AuthHelper::requirePermission('payments', 'edit');

        $id = (int)($_GET['id'] ?? 0);
        if (!$id) {
            http_response_code(400);
            echo 'Payment ID required';
            return;
        }

        $paymentModel = new Payment();
        $payment = $paymentModel->find($id);

        if (!$payment) {
            http_response_code(404);
            echo 'Payment not found';
            return;
        }

        include view_path('payments/confirm');
    }

    public function processPayment(): void
    {
        require_login();
        AuthHelper::requirePermission('payments', 'edit');
        verify_csrf();

        $id = (int)($_POST['payment_id'] ?? 0);
        $transactionId = trim($_POST['transaction_id'] ?? '');
        $paymentDate = $_POST['payment_date'] ?? date('Y-m-d H:i:s');
        $notes = trim($_POST['notes'] ?? '');

        if (!$id) {
            $_SESSION['error'] = 'ID pembayaran diperlukan.';
            header('Location: ' . route_url('payments'));
            return;
        }

        $paymentModel = new Payment();
        $user = current_user();

        try {
            // Handle proof upload
            $proofPath = null;
            if (!empty($_FILES['payment_proof']['name'])) {
                $uploadResult = FileUpload::upload($_FILES['payment_proof'], 'payments/proofs/', [
                    'allowed_types' => ['image/jpeg', 'image/png', 'application/pdf'],
                    'max_size' => 5 * 1024 * 1024, // 5MB
                    'prefix' => 'proof_'
                ]);

                if ($uploadResult['success']) {
                    $proofPath = $uploadResult['path'];
                } else {
                    $_SESSION['error'] = 'Gagal upload bukti pembayaran: ' . $uploadResult['error'];
                    header('Location: ' . route_url('payments/confirm') . '?id=' . $id);
                    return;
                }
            }

            $paymentData = [
                'transaction_id' => $transactionId,
                'payment_date' => $paymentDate,
                'notes' => $notes,
                'proof_path' => $proofPath
            ];

            $success = $paymentModel->processPayment($id, $paymentData, $user['id']);

            if ($success) {
                $_SESSION['success'] = 'Pembayaran berhasil diproses.';

                // Send notification to member
                $payment = $paymentModel->find($id);
                if ($payment) {
                    $memberModel = new Member();
                    $member = $memberModel->find($payment['member_id']);

                    if ($member && !empty($member['phone'])) {
                        \App\Helpers\Notification::send(
                            'whatsapp',
                            $member,
                            'Pembayaran Dikonfirmasi',
                            "Pembayaran sebesar Rp " . number_format($payment['amount'], 0, ',', '.') . " telah dikonfirmasi. Terima kasih."
                        );
                    }
                }
            } else {
                $_SESSION['error'] = 'Gagal memproses pembayaran.';
            }

        } catch (\Exception $e) {
            $_SESSION['error'] = 'Error processing payment: ' . $e->getMessage();
        }

        header('Location: ' . route_url('payments'));
    }

    // ===== MEMBER PAYMENT METHODS =====
    public function memberPayments(): void
    {
        // For member portal - show their payment history
        if (!$this->isMemberLoggedIn()) {
            header('Location: ' . route_url('member/login'));
            exit;
        }

        $member = $_SESSION['member'];
        $memberId = $member['id'];

        $paymentModel = new Payment();
        $payments = $paymentModel->getByMember($memberId);

        include view_path('member/payments');
    }

    public function createMemberPayment(): void
    {
        if (!$this->isMemberLoggedIn()) {
            header('Location: ' . route_url('member/login'));
            exit;
        }

        $type = $_GET['type'] ?? '';
        $referenceId = (int)($_GET['reference_id'] ?? 0);

        if (empty($type) || !$referenceId) {
            http_response_code(400);
            echo 'Payment type and reference ID required';
            return;
        }

        $member = $_SESSION['member'];

        // Get payment details
        $paymentData = $this->getPaymentData($type, $referenceId);

        if (!$paymentData || $paymentData['member_id'] !== $member['id']) {
            http_response_code(403);
            echo 'Access denied';
            return;
        }

        include view_path('member/create_payment');
    }

    // ===== API ENDPOINTS =====
    public function getPaymentStatusApi(): void
    {
        $transactionId = $_GET['transaction_id'] ?? '';

        if (empty($transactionId)) {
            http_response_code(400);
            echo json_encode(['error' => 'Transaction ID required']);
            return;
        }

        $paymentModel = new Payment();
        $status = $paymentModel->checkPaymentStatus($transactionId);

        header('Content-Type: application/json');
        echo json_encode(['status' => $status]);
    }

    public function getMemberPaymentsApi(): void
    {
        if (!$this->isMemberLoggedIn()) {
            http_response_code(401);
            echo json_encode(['error' => 'Authentication required']);
            return;
        }

        $member = $_SESSION['member'];
        $memberId = $member['id'];

        $paymentModel = new Payment();
        $payments = $paymentModel->getByMember($memberId);

        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'payments' => $payments]);
    }

    // ===== UTILITY METHODS =====
    private function getPaymentData(string $type, int $referenceId): ?array
    {
        switch ($type) {
            case 'repayment':
                $repaymentModel = new Repayment();
                $repayment = $repaymentModel->find($referenceId);
                if ($repayment) {
                    $loanModel = new \App\Models\Loan();
                    $loan = $loanModel->find($repayment['loan_id']);
                    $memberModel = new Member();
                    $member = $loan ? $memberModel->find($loan['member_id']) : null;

                    return [
                        'type' => 'repayment',
                        'reference_id' => $referenceId,
                        'member_id' => $member ? $member['id'] : 0,
                        'member_name' => $member ? $member['name'] : 'Unknown',
                        'amount' => $repayment['amount_due'] - $repayment['amount_paid'],
                        'description' => 'Angsuran pinjaman #' . $repayment['loan_id']
                    ];
                }
                break;

            case 'savings_deposit':
                $savingsModel = new SavingsAccount();
                $account = $savingsModel->find($referenceId);
                if ($account) {
                    $memberModel = new Member();
                    $member = $memberModel->find($account['member_id']);

                    return [
                        'type' => 'savings_deposit',
                        'reference_id' => $referenceId,
                        'member_id' => $member ? $member['id'] : 0,
                        'member_name' => $member ? $member['name'] : 'Unknown',
                        'amount' => 0, // To be filled by user
                        'description' => 'Setoran ' . ucfirst($account['type']) . ' #' . $account['account_number']
                    ];
                }
                break;
        }

        return null;
    }

    private function isMemberLoggedIn(): bool
    {
        return isset($_SESSION['member']) && !empty($_SESSION['member']);
    }
}
