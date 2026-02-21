<?php
namespace App\Controllers;

use App\Models\Member;
use App\Models\Loan;
use App\Models\Repayment;
use App\Models\SavingsAccount;
use App\Models\SavingsTransaction;
use App\Helpers\AuthHelper;

class MemberPortalController
{
    public function login(): void
    {
        // If already logged in as member, redirect to dashboard
        if ($this->isMemberLoggedIn()) {
            header('Location: ' . route_url('member/dashboard'));
            exit;
        }

        // Show member login form
        include view_path('member/login');
    }

    public function authenticate(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo 'Method not allowed';
            return;
        }

        $nik = $_POST['nik'] ?? '';
        $phone = $_POST['phone'] ?? '';

        if (!$nik || !$phone) {
            $_SESSION['member_login_error'] = 'NIK dan nomor telepon harus diisi';
            header('Location: ' . route_url('member/login'));
            exit;
        }

        $memberModel = new Member();
        $member = $memberModel->findWhere([
            'nik' => $nik,
            'phone' => $phone,
            'status' => 'active'
        ]);

        if (!$member) {
            $_SESSION['member_login_error'] = 'Data anggota tidak ditemukan atau tidak aktif';
            header('Location: ' . route_url('member/login'));
            exit;
        }

        $member = $member[0]; // Get first result

        // Set member session
        $_SESSION['member'] = $member;
        $_SESSION['member_login_time'] = time();

        header('Location: ' . route_url('member/dashboard'));
        exit;
    }

    public function logout(): void
    {
        unset($_SESSION['member']);
        unset($_SESSION['member_login_time']);
        header('Location: ' . route_url('member/login'));
        exit;
    }

    public function dashboard(): void
    {
        if (!$this->isMemberLoggedIn()) {
            header('Location: ' . route_url('member/login'));
            exit;
        }

        $member = $_SESSION['member'];
        $memberId = $member['id'];

        // Get member loans
        $loanModel = new Loan();
        $loans = $loanModel->findWhere(['member_id' => $memberId]);

        // Get recent repayments
        $repaymentModel = new Repayment();
        $recentRepayments = $repaymentModel->findWhere(
            ['loan_id' => array_column($loans, 'id')],
            ['due_date' => 'DESC'],
            5
        );

        // Calculate totals
        $totalLoans = count($loans);
        $activeLoans = count(array_filter($loans, fn($loan) => in_array($loan['status'], ['approved', 'disbursed'])));
        $totalOutstanding = array_sum(array_column(
            array_filter($loans, fn($loan) => in_array($loan['status'], ['approved', 'disbursed'])),
            'amount'
        ));

        include view_path('member/dashboard');
    }

    public function loans(): void
    {
        if (!$this->isMemberLoggedIn()) {
            header('Location: ' . route_url('member/login'));
            exit;
        }

        $member = $_SESSION['member'];
        $memberId = $member['id'];

        $loanModel = new Loan();
        $loans = $loanModel->findWhere(['member_id' => $memberId], ['created_at' => 'DESC']);

        include view_path('member/loans');
    }

    public function loanDetail(): void
    {
        if (!$this->isMemberLoggedIn()) {
            header('Location: ' . route_url('member/login'));
            exit;
        }

        $loanId = (int)($_GET['id'] ?? 0);
        if (!$loanId) {
            http_response_code(404);
            echo 'Pinjaman tidak ditemukan';
            return;
        }

        $member = $_SESSION['member'];
        $loanModel = new Loan();
        $loan = $loanModel->findWithDetails($loanId);

        if (!$loan || $loan['member_id'] !== $member['id']) {
            http_response_code(403);
            echo 'Akses ditolak';
            return;
        }

        // Get repayment schedule
        $repaymentSchedule = $loanModel->calculateRepaymentSchedule($loanId);

        // Get repayment history
        $repaymentModel = new Repayment();
        $repayments = $repaymentModel->getByLoanId($loanId);

        include view_path('member/loan_detail');
    }

    public function repayments(): void
    {
        if (!$this->isMemberLoggedIn()) {
            header('Location: ' . route_url('member/login'));
            exit;
        }

        $member = $_SESSION['member'];
        $memberId = $member['id'];

        $loanModel = new Loan();
        $loans = $loanModel->findWhere(['member_id' => $memberId]);

        $repaymentModel = new Repayment();
        $repayments = $repaymentModel->findWhere(
            ['loan_id' => array_column($loans, 'id')],
            ['due_date' => 'DESC']
        );

        include view_path('member/repayments');
    }

    public function savings(): void
    {
        if (!$this->isMemberLoggedIn()) {
            header('Location: ' . route_url('member/login'));
            exit;
        }

        $member = $_SESSION['member'];
        $memberId = $member['id'];

        $savingsModel = new SavingsAccount();
        $transactionModel = new SavingsTransaction();

        $savingsAccounts = $savingsModel->getByMemberId($memberId);
        $recentTransactions = $transactionModel->getByMemberId($memberId, 10);

        // Calculate total savings
        $totalSavings = $savingsModel->getTotalSavingsByMember($memberId);

        include view_path('member/savings');
    }

    public function savingsDetail(): void
    {
        if (!$this->isMemberLoggedIn()) {
            header('Location: ' . route_url('member/login'));
            exit;
        }

        $accountId = (int)($_GET['id'] ?? 0);
        if (!$accountId) {
            http_response_code(404);
            echo 'Account not found';
            return;
        }

        $member = $_SESSION['member'];

        $savingsModel = new SavingsAccount();
        $account = $savingsModel->find($accountId);

        if (!$account || $account['member_id'] !== $member['id']) {
            http_response_code(403);
            echo 'Access denied';
            return;
        }

        $transactionModel = new SavingsTransaction();
        $transactions = $transactionModel->getByAccountId($accountId, 50);

        include view_path('member/savings_detail');
    }

    public function updateProfile(): void
    {
        if (!$this->isMemberLoggedIn()) {
            header('Location: ' . route_url('member/login'));
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo 'Method not allowed';
            return;
        }

        $member = $_SESSION['member'];
        $memberId = $member['id'];

        $data = [
            'phone' => $_POST['phone'] ?? $member['phone'],
            'address' => $_POST['address'] ?? $member['address'],
            'lat' => (float)($_POST['lat'] ?? $member['lat']),
            'lng' => (float)($_POST['lng'] ?? $member['lng'])
        ];

        $memberModel = new Member();
        $success = $memberModel->update($memberId, $data);

        if ($success) {
            // Update session data
            $_SESSION['member'] = array_merge($member, $data);
            $_SESSION['member_update_success'] = 'Profil berhasil diperbarui';
        } else {
            $_SESSION['member_update_error'] = 'Gagal memperbarui profil';
        }

        header('Location: ' . route_url('member/profile'));
        exit;
    }

    private function isMemberLoggedIn(): bool
    {
        return isset($_SESSION['member']) && !empty($_SESSION['member']);
    }

    public function requireMemberLogin(): void
    {
        if (!$this->isMemberLoggedIn()) {
            header('Location: ' . route_url('member/login'));
            exit;
        }
    }

    public function getCurrentMember()
    {
        return $_SESSION['member'] ?? null;
    }
}
