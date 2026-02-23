<?php
namespace App\Controllers;

use App\Models\Member;
use App\Models\Loan;
use App\Models\LoanProduct;
use App\Models\SavingsAccount;
use App\Models\SavingsProduct;
use App\Helpers\AuthHelper;
use App\Helpers\FileUpload;

class MemberPortalController
{
    /**
     * Member registration page
     */
    public function register(): void
    {
        // Check if already logged in
        if (!empty($_SESSION['member'])) {
            header('Location: ' . route_url('member/dashboard'));
            return;
        }

        include view_path('member/register');
    }

    /**
     * Handle member registration submission
     */
    public function registerSubmit(): void
    {
        verify_csrf();

        $memberModel = new Member();

        // Basic validation
        $requiredFields = ['name', 'nik', 'phone', 'address', 'province', 'city', 'birth_date', 'birth_place'];
        foreach ($requiredFields as $field) {
            if (empty($_POST[$field])) {
                $_SESSION['error'] = "Field {$field} is required";
                header('Location: ' . route_url('member/register'));
                return;
            }
        }

        // Check if NIK already exists
        if ($memberModel->findByNik($_POST['nik'])) {
            $_SESSION['error'] = 'NIK sudah terdaftar';
            header('Location: ' . route_url('member/register'));
            return;
        }

        // Check if phone already exists
        if ($memberModel->findByPhone($_POST['phone'])) {
            $_SESSION['error'] = 'Nomor telepon sudah terdaftar';
            header('Location: ' . route_url('member/register'));
            return;
        }

        // Generate member number
        $memberNumber = $memberModel->generateMemberNumber();

        // Prepare member data
        $memberData = [
            'member_number' => $memberNumber,
            'name' => trim($_POST['name']),
            'nik' => preg_replace('/\D+/', '', $_POST['nik']),
            'phone' => preg_replace('/\D+/', '', $_POST['phone']),
            'email' => trim($_POST['email'] ?? ''),
            'address' => trim($_POST['address']),
            'province' => trim($_POST['province']),
            'city' => trim($_POST['city']),
            'district' => trim($_POST['district'] ?? ''),
            'village' => trim($_POST['village'] ?? ''),
            'postal_code' => trim($_POST['postal_code'] ?? ''),
            'birth_date' => $_POST['birth_date'],
            'birth_place' => trim($_POST['birth_place']),
            'gender' => $_POST['gender'],
            'marital_status' => $_POST['marital_status'] ?? 'single',
            'religion' => $_POST['religion'] ?? 'islam',
            'occupation' => trim($_POST['occupation'] ?? ''),
            'monthly_income' => (float)($_POST['monthly_income'] ?? 0),
            'education' => $_POST['education'] ?? 'sma',
            'status' => 'draft',
            'verification_status' => 'pending'
        ];

        // Handle document uploads
        $documentTypes = ['ktp', 'kk', 'selfie'];
        $uploadedDocuments = [];

        foreach ($documentTypes as $docType) {
            if (!empty($_FILES[$docType]['name'])) {
                try {
                    $uploadResult = FileUpload::upload($_FILES[$docType], 'members/temp/', [
                        'allowed_types' => ['image/jpeg', 'image/png'],
                        'max_size' => 5 * 1024 * 1024, // 5MB
                        'prefix' => "temp_{$memberNumber}_{$docType}_"
                    ]);

                    if ($uploadResult['success']) {
                        $fieldName = $docType . '_photo_path';
                        $memberData[$fieldName] = $uploadResult['path'];
                        $uploadedDocuments[] = $docType;
                    }
                } catch (\Exception $e) {
                    $_SESSION['error'] = "Failed to upload {$docType}: " . $e->getMessage();
                    header('Location: ' . route_url('member/register'));
                    return;
                }
            }
        }

        try {
            // Create member record
            $memberId = $memberModel->create($memberData);

            // Send notification to cooperative
            \App\Helpers\Notification::send('email', [
                'email' => 'admin@koperasi.local', // Should be configurable
                'name' => 'Admin Koperasi'
            ], 'Pendaftaran Anggota Baru', "Anggota baru {$memberData['name']} telah mendaftar dan menunggu verifikasi.");

            $_SESSION['success'] = 'Pendaftaran berhasil! Silakan tunggu verifikasi dari koperasi.';
            header('Location: ' . route_url('member/registration-success'));
            return;

        } catch (\Exception $e) {
            $_SESSION['error'] = 'Gagal mendaftar: ' . $e->getMessage();
            header('Location: ' . route_url('member/register'));
        }
    }

    /**
     * Registration success page
     */
    public function registrationSuccess(): void
    {
        include view_path('member/registration_success');
    }

    /**
     * Member login page
     */
    public function login(): void
    {
        if (!empty($_SESSION['member'])) {
            header('Location: ' . route_url('member/dashboard'));
            return;
        }

        include view_path('member/login');
    }

    /**
     * Handle member login
     */
    public function authenticate(): void
    {
        $nik = trim($_POST['nik'] ?? '');
        $password = trim($_POST['password'] ?? '');

        if (empty($nik) || empty($password)) {
            $_SESSION['error'] = 'NIK dan password diperlukan';
            header('Location: ' . route_url('member/login'));
            return;
        }

        $memberModel = new Member();
        $member = $memberModel->findByNik($nik);

        if (!$member) {
            $_SESSION['error'] = 'NIK tidak ditemukan';
            header('Location: ' . route_url('member/login'));
            return;
        }

        if ($member['status'] !== 'active') {
            $_SESSION['error'] = 'Akun belum aktif. Silakan tunggu verifikasi.';
            header('Location: ' . route_url('member/login'));
            return;
        }

        // For now, use a simple password check (should be improved with proper hashing)
        // In production, members would get temporary password via email/SMS
        if ($password !== 'default123') { // Temporary default password
            $_SESSION['error'] = 'Password salah';
            header('Location: ' . route_url('member/login'));
            return;
        }

        // Set member session
        $_SESSION['member'] = $member;

        header('Location: ' . route_url('member/dashboard'));
    }

    /**
     * Member logout
     */
    public function logout(): void
    {
        unset($_SESSION['member']);
        header('Location: ' . route_url('member/login'));
    }

    /**
     * Member dashboard
     */
    public function dashboard(): void
    {
        $this->requireMemberLogin();

        $memberId = $_SESSION['member']['id'];
        $memberModel = new Member();
        $dashboardData = $memberModel->getDashboardData($memberId);

        include view_path('member/dashboard');
    }

    /**
     * Member loans page
     */
    public function loans(): void
    {
        $this->requireMemberLogin();

        $memberId = $_SESSION['member']['id'];
        $memberModel = new Member();
        $loans = $memberModel->getLoans($memberId);

        include view_path('member/loans');
    }

    /**
     * Loan detail page
     */
    public function loanDetail(): void
    {
        $this->requireMemberLogin();

        $loanId = (int)($_GET['id'] ?? 0);
        $memberId = $_SESSION['member']['id'];

        $loanModel = new Loan();
        $loan = $loanModel->find($loanId);

        if (!$loan || $loan['member_id'] !== $memberId) {
            $_SESSION['error'] = 'Pinjaman tidak ditemukan';
            header('Location: ' . route_url('member/loans'));
            return;
        }

        // Get loan repayments
        $stmt = $this->db->prepare("
            SELECT * FROM loan_repayments
            WHERE loan_id = ?
            ORDER BY due_date ASC
        ");
        $stmt->execute([$loanId]);
        $repayments = $stmt->fetchAll();

        include view_path('member/loan_detail');
    }

    /**
     * Loan application page
     */
    public function applyLoan(): void
    {
        $this->requireMemberLogin();

        $memberId = $_SESSION['member']['id'];
        $memberModel = new Member();

        // Check if member can apply for loan
        $loanCheck = $memberModel->canApplyForLoan($memberId, 0); // 0 for general check
        if (!$loanCheck['allowed']) {
            $_SESSION['error'] = $loanCheck['reason'];
            header('Location: ' . route_url('member/dashboard'));
            return;
        }

        // Get available loan products
        $loanProductModel = new LoanProduct();
        $loanProducts = $loanProductModel->findWhere(['is_active' => true], ['name' => 'ASC']);

        include view_path('member/apply_loan');
    }

    /**
     * Submit loan application
     */
    public function submitLoanApplication(): void
    {
        $this->requireMemberLogin();
        verify_csrf();

        $memberId = $_SESSION['member']['id'];
        $memberModel = new Member();

        $productId = (int)($_POST['product_id'] ?? 0);
        $amount = (float)($_POST['amount'] ?? 0);
        $tenor = (int)($_POST['tenor'] ?? 0);
        $purpose = trim($_POST['purpose'] ?? '');

        // Validation
        if (!$productId || !$amount || !$tenor || empty($purpose)) {
            $_SESSION['error'] = 'Semua field wajib diisi';
            header('Location: ' . route_url('member/apply-loan'));
            return;
        }

        // Check loan product
        $loanProductModel = new LoanProduct();
        $product = $loanProductModel->find($productId);
        if (!$product || !$product['is_active']) {
            $_SESSION['error'] = 'Produk pinjaman tidak valid';
            header('Location: ' . route_url('member/apply-loan'));
            return;
        }

        // Validate amount and tenor
        if ($amount < $product['min_amount'] || $amount > $product['max_amount']) {
            $_SESSION['error'] = "Jumlah pinjaman harus antara Rp " . number_format($product['min_amount']) . " - Rp " . number_format($product['max_amount']);
            header('Location: ' . route_url('member/apply-loan'));
            return;
        }

        if ($tenor < $product['min_tenor'] || $tenor > $product['max_tenor']) {
            $_SESSION['error'] = "Tenor harus antara {$product['min_tenor']} - {$product['max_tenor']} bulan";
            header('Location: ' . route_url('member/apply-loan'));
            return;
        }

        // Check if member can apply
        $loanCheck = $memberModel->canApplyForLoan($memberId, $amount);
        if (!$loanCheck['allowed']) {
            $_SESSION['error'] = $loanCheck['reason'];
            header('Location: ' . route_url('member/apply-loan'));
            return;
        }

        // Calculate loan details
        $interestRate = $product['interest_rate'];
        $totalAmount = $this->calculateLoanTotal($amount, $interestRate, $tenor, $product['interest_type']);
        $monthlyInstallment = round($totalAmount / $tenor, 2);

        try {
            $loanModel = new Loan();
            $loanData = [
                'loan_number' => $this->generateLoanNumber(),
                'member_id' => $memberId,
                'product_id' => $productId,
                'principal_amount' => $amount,
                'interest_rate' => $interestRate,
                'interest_type' => $product['interest_type'],
                'tenor_months' => $tenor,
                'monthly_installment' => $monthlyInstallment,
                'total_amount' => $totalAmount,
                'outstanding_balance' => $totalAmount,
                'purpose' => $purpose,
                'status' => 'draft',
                'application_date' => date('Y-m-d')
            ];

            $loanId = $loanModel->create($loanData);

            // Send notification to cooperative
            \App\Helpers\Notification::send('email', [
                'email' => 'admin@koperasi.local',
                'name' => 'Admin Koperasi'
            ], 'Pengajuan Pinjaman Baru', "Anggota {$_SESSION['member']['name']} telah mengajukan pinjaman sebesar Rp " . number_format($amount));

            $_SESSION['success'] = 'Pengajuan pinjaman berhasil dikirim. Silakan tunggu proses verifikasi.';
            header('Location: ' . route_url('member/loans'));

        } catch (\Exception $e) {
            $_SESSION['error'] = 'Gagal mengajukan pinjaman: ' . $e->getMessage();
            header('Location: ' . route_url('member/apply-loan'));
        }
    }

}
