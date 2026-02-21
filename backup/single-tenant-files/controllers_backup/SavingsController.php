<?php
namespace App\Controllers;

use App\Models\SavingsAccount;
use App\Models\SavingsTransaction;
use App\Models\Member;
use App\Helpers\AuthHelper;
use App\Helpers\FileUpload;

class SavingsController
{
    public function index(): void
    {
        require_login();
        AuthHelper::requirePermission('savings', 'view');

        $page = (int)($_GET['page'] ?? 1);
        $limit = (int)($_GET['limit'] ?? 15);
        $type = $_GET['type'] ?? null;

        $savingsModel = new SavingsAccount();
        $conditions = $type ? ['type' => $type] : [];

        $result = $savingsModel->paginate($page, $limit, $conditions);

        // Add member information
        foreach ($result['items'] as &$account) {
            $memberModel = new Member();
            $member = $memberModel->find($account['member_id']);
            $account['member_name'] = $member ? $member['name'] : 'Unknown';
            $account['member_nik'] = $member ? $member['nik'] : '';
        }

        include view_path('savings/index');
    }

    public function create(): void
    {
        require_login();
        AuthHelper::requirePermission('savings', 'create');

        $memberModel = new Member();
        $members = $memberModel->findWhere(['status' => 'active'], ['name' => 'ASC']);

        include view_path('savings/create');
    }

    public function store(): void
    {
        require_login();
        AuthHelper::requirePermission('savings', 'create');
        verify_csrf();

        $data = [
            'member_id' => (int)($_POST['member_id'] ?? 0),
            'type' => $_POST['type'] ?? '',
            'interest_rate' => (float)($_POST['interest_rate'] ?? 0),
            'status' => $_POST['status'] ?? 'active'
        ];

        if (!$data['member_id'] || empty($data['type'])) {
            $_SESSION['error'] = 'Member dan jenis simpanan wajib diisi.';
            header('Location: ' . route_url('savings/create'));
            return;
        }

        // Set default interest rates based on type
        if ($data['interest_rate'] == 0) {
            $data['interest_rate'] = match($data['type']) {
                'pokok' => 0.0,
                'wajib' => 0.0,
                'sukarela' => 3.0,
                'sisuka' => 6.0,
                default => 0.0
            };
        }

        $savingsModel = new SavingsAccount();

        try {
            $accountId = $savingsModel->create($data);
            $_SESSION['success'] = 'Rekening simpanan berhasil dibuat.';
            header('Location: ' . route_url('savings'));
        } catch (\Exception $e) {
            $_SESSION['error'] = 'Gagal membuat rekening simpanan: ' . $e->getMessage();
            header('Location: ' . route_url('savings/create'));
        }
    }

    public function show(): void
    {
        require_login();
        AuthHelper::requirePermission('savings', 'view');

        $id = (int)($_GET['id'] ?? 0);
        if (!$id) {
            http_response_code(404);
            echo 'Account not found';
            return;
        }

        $savingsModel = new SavingsAccount();
        $account = $savingsModel->find($id);

        if (!$account) {
            http_response_code(404);
            echo 'Account not found';
            return;
        }

        // Get member information
        $memberModel = new Member();
        $member = $memberModel->find($account['member_id']);

        // Get recent transactions
        $transactionModel = new SavingsTransaction();
        $transactions = $transactionModel->getByAccountId($id, 20);

        include view_path('savings/show');
    }

    public function deposit(): void
    {
        require_login();
        AuthHelper::requirePermission('savings', 'create');

        $accountId = (int)($_POST['account_id'] ?? 0);
        $amount = (float)($_POST['amount'] ?? 0);
        $description = trim($_POST['description'] ?? '');

        if (!$accountId || $amount <= 0) {
            $_SESSION['error'] = 'Rekening dan jumlah setor harus valid.';
            header('Location: ' . route_url('savings/show') . '?id=' . $accountId);
            return;
        }

        $transactionModel = new SavingsTransaction();

        try {
            $transactionModel->createTransaction([
                'savings_account_id' => $accountId,
                'type' => 'deposit',
                'amount' => $amount,
                'description' => $description ?: 'Setoran tunai',
                'transaction_date' => date('Y-m-d'),
                'processed_by' => current_user()['id']
            ]);

            $_SESSION['success'] = 'Setoran berhasil dicatat.';
            header('Location: ' . route_url('savings/show') . '?id=' . $accountId);
        } catch (\Exception $e) {
            $_SESSION['error'] = 'Gagal mencatat setoran: ' . $e->getMessage();
            header('Location: ' . route_url('savings/show') . '?id=' . $accountId);
        }
    }

    public function withdraw(): void
    {
        require_login();
        AuthHelper::requirePermission('savings', 'create');

        $accountId = (int)($_POST['account_id'] ?? 0);
        $amount = (float)($_POST['amount'] ?? 0);
        $description = trim($_POST['description'] ?? '');

        if (!$accountId || $amount <= 0) {
            $_SESSION['error'] = 'Rekening dan jumlah tarik harus valid.';
            header('Location: ' . route_url('savings/show') . '?id=' . $accountId);
            return;
        }

        $transactionModel = new SavingsTransaction();

        try {
            $transactionModel->createTransaction([
                'savings_account_id' => $accountId,
                'type' => 'withdrawal',
                'amount' => $amount,
                'description' => $description ?: 'Penarikan tunai',
                'transaction_date' => date('Y-m-d'),
                'processed_by' => current_user()['id']
            ]);

            $_SESSION['success'] = 'Penarikan berhasil dicatat.';
            header('Location: ' . route_url('savings/show') . '?id=' . $accountId);
        } catch (\Exception $e) {
            $_SESSION['error'] = 'Gagal mencatat penarikan: ' . $e->getMessage();
            header('Location: ' . route_url('savings/show') . '?id=' . $accountId);
        }
    }

    // ===== API ENDPOINTS =====
    public function getMemberAccountsApi(): void
    {
        require_login();

        $memberId = (int)($_GET['member_id'] ?? 0);
        if (!$memberId) {
            http_response_code(400);
            echo json_encode(['error' => 'Member ID required']);
            return;
        }

        $savingsModel = new SavingsAccount();
        $accounts = $savingsModel->getByMemberId($memberId);

        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'accounts' => $accounts]);
    }

    public function getAccountTransactionsApi(): void
    {
        require_login();

        $accountId = (int)($_GET['account_id'] ?? 0);
        if (!$accountId) {
            http_response_code(400);
            echo json_encode(['error' => 'Account ID required']);
            return;
        }

        $transactionModel = new SavingsTransaction();
        $transactions = $transactionModel->getByAccountId($accountId);

        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'transactions' => $transactions]);
    }

    public function createTransactionApi(): void
    {
        require_login();

        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid JSON data']);
            return;
        }

        $transactionModel = new SavingsTransaction();

        try {
            $transactionId = $transactionModel->createTransaction($input);

            echo json_encode([
                'success' => true,
                'transaction_id' => $transactionId,
                'message' => 'Transaction created successfully'
            ]);
        } catch (\Exception $e) {
            http_response_code(400);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    // ===== REPORTING =====
    public function exportSavings(): void
    {
        require_login();
        AuthHelper::requirePermission('savings', 'view');

        $type = $_GET['type'] ?? 'all';

        $savingsModel = new SavingsAccount();
        $memberModel = new Member();

        if ($type === 'all') {
            $accounts = $savingsModel->all(['created_at' => 'DESC']);
        } else {
            $accounts = $savingsModel->getByType($type);
        }

        // CSV export
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="savings_report_' . date('Y-m-d') . '.csv"');

        $output = fopen('php://output', 'w');

        fputcsv($output, [
            'Account Number', 'Member Name', 'Type', 'Balance', 'Interest Rate', 'Status', 'Created Date'
        ]);

        foreach ($accounts as $account) {
            $member = $memberModel->find($account['member_id']);

            fputcsv($output, [
                $account['account_number'],
                $member ? $member['name'] : 'Unknown',
                $account['type'],
                $account['balance'],
                $account['interest_rate'],
                $account['status'],
                $account['created_at']
            ]);
        }

        fclose($output);
        exit;
    }

    public function getSavingsStatsApi(): void
    {
        require_login();

        $savingsModel = new SavingsAccount();
        $transactionModel = new SavingsTransaction();

        $accountStats = $savingsModel->getStatistics();
        $transactionStats = $transactionModel->getStatistics();

        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'account_stats' => $accountStats,
            'transaction_stats' => $transactionStats
        ]);
    }
}
