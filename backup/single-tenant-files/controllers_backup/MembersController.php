<?php
namespace App\Controllers;
use App\Helpers\FileUpload;

class MembersController
{
    public function index(): void
    {
        require_login();
        AuthHelper::requirePermission('members', 'view');
        $title = 'Daftar Anggota';
        $pdo = \App\Database::getConnection();
        $stmt = $pdo->query('SELECT * FROM members ORDER BY name');
        $members = $stmt->fetchAll();
        include view_path('members/index');
    }

    public function register(): void
    {
        // Public registration form - no login required
        include view_path('members/register');
    }

    public function storeRegistration(): void
    {
        // Handle member registration - no login required
        $data = [
            'name' => trim($_POST['name'] ?? ''),
            'nik' => trim($_POST['nik'] ?? ''),
            'phone' => trim($_POST['phone'] ?? ''),
            'address' => trim($_POST['address'] ?? ''),
            'lat' => (float)($_POST['lat'] ?? 0),
            'lng' => (float)($_POST['lng'] ?? 0),
            'monthly_income' => (float)($_POST['monthly_income'] ?? 0),
            'occupation' => trim($_POST['occupation'] ?? ''),
            'emergency_contact_name' => trim($_POST['emergency_contact_name'] ?? ''),
            'emergency_contact_phone' => trim($_POST['emergency_contact_phone'] ?? ''),
            'status' => 'pending' // Pending verification
        ];

        // Validation
        if (empty($data['name']) || empty($data['nik']) || empty($data['phone']) || empty($data['address'])) {
            $_SESSION['registration_error'] = 'Nama, NIK, telepon, dan alamat wajib diisi.';
            header('Location: ' . route_url('members/register'));
            return;
        }

        // Check if NIK already exists
        $memberModel = new \App\Models\Member();
        $existingMember = $memberModel->findWhere(['nik' => $data['nik']]);
        if (!empty($existingMember)) {
            $_SESSION['registration_error'] = 'NIK sudah terdaftar dalam sistem.';
            header('Location: ' . route_url('members/register'));
            return;
        }

        try {
            // Handle document uploads
            $uploadedFiles = [];

            if (!empty($_FILES['ktp_photo']['name'])) {
                $uploadResult = FileUpload::upload($_FILES['ktp_photo'], 'members/documents/', [
                    'allowed_types' => ['image/jpeg', 'image/png'],
                    'max_size' => 5 * 1024 * 1024, // 5MB
                    'prefix' => 'ktp_'
                ]);

                if ($uploadResult['success']) {
                    $uploadedFiles['ktp_photo'] = $uploadResult['path'];
                } else {
                    $_SESSION['registration_error'] = 'Gagal upload foto KTP: ' . $uploadResult['error'];
                    header('Location: ' . route_url('members/register'));
                    return;
                }
            }

            if (!empty($_FILES['kk_photo']['name'])) {
                $uploadResult = FileUpload::upload($_FILES['kk_photo'], 'members/documents/', [
                    'allowed_types' => ['image/jpeg', 'image/png'],
                    'max_size' => 5 * 1024 * 1024, // 5MB
                    'prefix' => 'kk_'
                ]);

                if ($uploadResult['success']) {
                    $uploadedFiles['kk_photo'] = $uploadResult['path'];
                } else {
                    $_SESSION['registration_error'] = 'Gagal upload foto KK: ' . $uploadResult['error'];
                    header('Location: ' . route_url('members/register'));
                    return;
                }
            }

            if (!empty($_FILES['salary_slip']['name'])) {
                $uploadResult = FileUpload::upload($_FILES['salary_slip'], 'members/documents/', [
                    'allowed_types' => ['image/jpeg', 'image/png', 'application/pdf'],
                    'max_size' => 5 * 1024 * 1024, // 5MB
                    'prefix' => 'salary_'
                ]);

                if ($uploadResult['success']) {
                    $uploadedFiles['salary_slip'] = $uploadResult['path'];
                } else {
                    $_SESSION['registration_error'] = 'Gagal upload slip gaji: ' . $uploadResult['error'];
                    header('Location: ' . route_url('members/register'));
                    return;
                }
            }

            if (!empty($_FILES['house_photo']['name'])) {
                $uploadResult = FileUpload::upload($_FILES['house_photo'], 'members/documents/', [
                    'allowed_types' => ['image/jpeg', 'image/png'],
                    'max_size' => 5 * 1024 * 1024, // 5MB
                    'prefix' => 'house_'
                ]);

                if ($uploadResult['success']) {
                    $uploadedFiles['house_photo'] = $uploadResult['path'];
                } else {
                    $_SESSION['registration_error'] = 'Gagal upload foto rumah: ' . $uploadResult['error'];
                    header('Location: ' . route_url('members/register'));
                    return;
                }
            }

            // Store uploaded file paths
            $data['documents'] = json_encode($uploadedFiles);

            // Create member record
            $memberId = $memberModel->create($data);

            // Send notification to admin
            $adminUsers = $memberModel->findWhere(['role_id' => 1]); // Assuming role_id 1 is admin
            if (!empty($adminUsers)) {
                \App\Helpers\Notification::send(
                    'email',
                    ['email' => 'admin@ksp-lamgabejaya.id', 'name' => 'Admin'],
                    'Pendaftaran Anggota Baru',
                    "Anggota baru {$data['name']} telah mendaftar dan menunggu verifikasi."
                );
            }

            $_SESSION['registration_success'] = 'Pendaftaran berhasil! Kami akan memverifikasi data Anda dalam 1-2 hari kerja.';

        } catch (\Exception $e) {
            $_SESSION['registration_error'] = 'Terjadi kesalahan: ' . $e->getMessage();
            header('Location: ' . route_url('members/register'));
            return;
        }

        // Redirect to success page
        header('Location: ' . route_url('members/registration-success'));
    }

    public function registrationSuccess(): void
    {
        // Show registration success message
        include view_path('members/registration_success');
    }

    public function verify(): void
    {
        require_login();
        AuthHelper::requirePermission('members', 'edit');

        $id = (int)($_GET['id'] ?? 0);
        $action = $_GET['action'] ?? '';

        if (!$id || !in_array($action, ['approve', 'reject'])) {
            http_response_code(400);
            echo 'Invalid parameters';
            return;
        }

        $memberModel = new \App\Models\Member();

        try {
            if ($action === 'approve') {
                $success = $memberModel->update($id, ['status' => 'active']);

                if ($success) {
                    // Create default savings accounts for new member
                    $this->createDefaultSavingsAccounts($id);

                    // Send approval notification
                    $member = $memberModel->find($id);
                    if ($member && !empty($member['phone'])) {
                        \App\Helpers\Notification::send(
                            'whatsapp',
                            $member,
                            'Pendaftaran Disetujui',
                            "Selamat {$member['name']}! Pendaftaran Anda sebagai anggota APLIKASI KSP telah disetujui. Anda sekarang dapat mengakses portal anggota."
                        );
                    }

                    $_SESSION['success'] = 'Anggota berhasil diverifikasi dan disetujui.';
                }
            } elseif ($action === 'reject') {
                $success = $memberModel->update($id, ['status' => 'rejected']);

                if ($success) {
                    $_SESSION['success'] = 'Pendaftaran anggota ditolak.';
                }
            }

            if (!$success) {
                $_SESSION['error'] = 'Gagal memverifikasi anggota.';
            }

        } catch (\Exception $e) {
            $_SESSION['error'] = 'Error verifying member: ' . $e->getMessage();
        }

        header('Location: ' . route_url('members'));
    }

    private function createDefaultSavingsAccounts(int $memberId): void
    {
        $savingsModel = new \App\Models\SavingsAccount();

        // Create mandatory savings account (Simpanan Pokok)
        try {
            $savingsModel->create([
                'member_id' => $memberId,
                'type' => 'pokok',
                'interest_rate' => 0.0,
                'balance' => 50000 // Default pokok amount
            ]);
        } catch (\Exception $e) {
            // Log error but continue
            error_log("Failed to create pokok savings for member {$memberId}: " . $e->getMessage());
        }

        // Create mandatory savings account (Simpanan Wajib)
        try {
            $savingsModel->create([
                'member_id' => $memberId,
                'type' => 'wajib',
                'interest_rate' => 0.0,
                'balance' => 0
            ]);
        } catch (\Exception $e) {
            error_log("Failed to create wajib savings for member {$memberId}: " . $e->getMessage());
        }
    }

    public function pendingVerifications(): void
    {
        require_login();
        AuthHelper::requirePermission('members', 'view');

        $memberModel = new \App\Models\Member();
        $pendingMembers = $memberModel->findWhere(['status' => 'pending'], ['created_at' => 'DESC']);

        include view_path('members/pending_verifications');
    }

    public function store(): void
    {
        require_login();
        AuthHelper::requirePermission('members', 'create');
        verify_csrf();

        // Check resource limits before creating member
        $limitMiddleware = new \App\Middleware\ResourceLimitMiddleware();
        if (!$limitMiddleware->checkMemberLimit()) {
            $_SESSION['error'] = 'Batas jumlah anggota telah tercapai. Silakan upgrade paket berlangganan.';
            header('Location: ' . route_url('members/create'));
            return;
        }

        $name = trim($_POST['name'] ?? '');
        $nik = preg_replace('/\D+/', '', (string)($_POST['nik'] ?? ''));
        $phone = preg_replace('/\D+/', '', (string)($_POST['phone'] ?? ''));
        $address = trim($_POST['address'] ?? '');
        $lat = (float)($_POST['lat'] ?? 0);
        $lng = (float)($_POST['lng'] ?? 0);
        if (empty($name) || empty($nik) || empty($phone) || empty($address)) {
            $_SESSION['error'] = 'Data wajib diisi.';
            header('Location: ' . route_url('members/create'));
            return;
        }
        $pdo = \App\Database::getConnection();
        $stmt = $pdo->prepare('INSERT INTO members (name, nik, phone, address, lat, lng) VALUES (?, ?, ?, ?, ?, ?)');
        $stmt->execute([$name, $nik, $phone, $address, $lat, $lng]);
        $_SESSION['success'] = 'Anggota berhasil ditambahkan.';
        header('Location: ' . route_url('members'));
    }
}
