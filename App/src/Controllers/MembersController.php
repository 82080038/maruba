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

        // Get current tenant ID
        $tenantId = $this->getCurrentTenantId();
        $pdo = \App\Database::getConnection();

        if ($tenantId === null) {
            // System admin - can see all tenants
            $stmt = $pdo->query('SELECT m.*, t.name as tenant_name FROM members m LEFT JOIN tenants t ON m.tenant_id = t.id ORDER BY m.name');
        } else {
            // Tenant user - only see their tenant data
            $stmt = $pdo->prepare('SELECT * FROM members WHERE tenant_id = ? ORDER BY name');
            $stmt->execute([$tenantId]);
        }

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

        if ($existingMember) {
            $_SESSION['registration_error'] = 'NIK sudah terdaftar dalam sistem.';
            header('Location: ' . route_url('members/register'));
            return;
        }

        // Add tenant_id for data isolation
        $data['tenant_id'] = $this->getCurrentTenantId();

        // Save member
        $memberId = $memberModel->create($data);
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

    public function edit(): void
    {
        require_login();
        AuthHelper::requirePermission('members', 'edit');
        
        $id = (int)($_GET['id'] ?? 0);
        if (!$id) {
            $_SESSION['error'] = 'ID anggota tidak valid.';
            header('Location: ' . route_url('members'));
            return;
        }
        
        $memberModel = new \App\Models\Member();
        $member = $memberModel->find($id);
        
        if (!$member) {
            $_SESSION['error'] = 'Anggota tidak ditemukan.';
            header('Location: ' . route_url('members'));
            return;
        }
        
        // Check tenant ownership
        $tenantId = $this->getCurrentTenantId();
        if ($tenantId !== null && $member['tenant_id'] != $tenantId) {
            $_SESSION['error'] = 'Akses ditolak.';
            header('Location: ' . route_url('members'));
            return;
        }
        
        include view_path('members/edit');
    }
    
    public function update(): void
    {
        require_login();
        AuthHelper::requirePermission('members', 'edit');
        verify_csrf();
        
        $id = (int)($_POST['id'] ?? 0);
        if (!$id) {
            $_SESSION['error'] = 'ID anggota tidak valid.';
            header('Location: ' . route_url('members'));
            return;
        }
        
        $memberModel = new \App\Models\Member();
        $member = $memberModel->find($id);
        
        if (!$member) {
            $_SESSION['error'] = 'Anggota tidak ditemukan.';
            header('Location: ' . route_url('members'));
            return;
        }
        
        // Check tenant ownership
        $tenantId = $this->getCurrentTenantId();
        if ($tenantId !== null && $member['tenant_id'] != $tenantId) {
            $_SESSION['error'] = 'Akses ditolak.';
            header('Location: ' . route_url('members'));
            return;
        }
        
        $data = [
            'name' => trim($_POST['name'] ?? ''),
            'nik' => trim($_POST['nik'] ?? ''),
            'phone' => trim($_POST['phone'] ?? ''),
            'email' => trim($_POST['email'] ?? ''),
            'address' => trim($_POST['address'] ?? ''),
            'occupation' => trim($_POST['occupation'] ?? ''),
            'monthly_income' => (float)($_POST['monthly_income'] ?? 0),
            'emergency_contact_name' => trim($_POST['emergency_contact_name'] ?? ''),
            'emergency_contact_phone' => trim($_POST['emergency_contact_phone'] ?? ''),
            'status' => $_POST['status'] ?? 'pending',
            'verification_status' => $_POST['verification_status'] ?? 'pending',
            'latitude' => (float)($_POST['latitude'] ?? 0),
            'longitude' => (float)($_POST['longitude'] ?? 0),
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        // Add verified_at if status is verified
        if ($data['verification_status'] === 'verified' && !$member['verified_at']) {
            $data['verified_at'] = date('Y-m-d H:i:s');
            $data['verified_by'] = current_user()['id'];
        }
        
        // Validation
        if (empty($data['name']) || empty($data['nik']) || empty($data['phone']) || empty($data['address'])) {
            $_SESSION['error'] = 'Nama, NIK, telepon, dan alamat wajib diisi.';
            header('Location: ' . route_url('members/edit') . '?id=' . $id);
            return;
        }
        
        // Validate NIK format
        if (!preg_match('/^[0-9]{16}$/', $data['nik'])) {
            $_SESSION['error'] = 'NIK harus 16 digit angka.';
            header('Location: ' . route_url('members/edit') . '?id=' . $id);
            return;
        }
        
        // Validate phone format
        if (!preg_match('/^[0-9]{10,13}$/', $data['phone'])) {
            $_SESSION['error'] = 'Format telepon tidak valid.';
            header('Location: ' . route_url('members/edit') . '?id=' . $id);
            return;
        }
        
        // Check for duplicate NIK
        $existingMember = $memberModel->findByNik($data['nik']);
        if ($existingMember && $existingMember['id'] != $id) {
            $_SESSION['error'] = 'NIK sudah digunakan oleh anggota lain.';
            header('Location: ' . route_url('members/edit') . '?id=' . $id);
            return;
        }
        
        try {
            $success = $memberModel->update($id, $data);
            
            if ($success) {
                $_SESSION['success'] = 'Data anggota berhasil diperbarui.';
                header('Location: ' . route_url('members/show') . '?id=' . $id);
            } else {
                $_SESSION['error'] = 'Gagal memperbarui data anggota.';
                header('Location: ' . route_url('members/edit') . '?id=' . $id);
            }
        } catch (\Exception $e) {
            $_SESSION['error'] = 'Gagal memperbarui data anggota: ' . $e->getMessage();
            header('Location: ' . route_url('members/edit') . '?id=' . $id);
        }
    }
    
    public function show(): void
    {
        require_login();
        AuthHelper::requirePermission('members', 'view');
        
        $id = (int)($_GET['id'] ?? 0);
        if (!$id) {
            $_SESSION['error'] = 'ID anggota tidak valid.';
            header('Location: ' . route_url('members'));
            return;
        }
        
        $memberModel = new \App\Models\Member();
        $member = $memberModel->find($id);
        
        if (!$member) {
            $_SESSION['error'] = 'Anggota tidak ditemukan.';
            header('Location: ' . route_url('members'));
            return;
        }
        
        // Check tenant ownership
        $tenantId = $this->getCurrentTenantId();
        if ($tenantId !== null && $member['tenant_id'] != $tenantId) {
            $_SESSION['error'] = 'Akses ditolak.';
            header('Location: ' . route_url('members'));
            return;
        }
        
        // Get member transactions
        $transactions = $memberModel->getTransactionHistory($id, 10);
        
        include view_path('members/show');
    }
    
    public function delete(): void
    {
        require_login();
        AuthHelper::requirePermission('members', 'delete');
        verify_csrf();
        
        $id = (int)($_POST['id'] ?? 0);
        if (!$id) {
            $_SESSION['error'] = 'ID anggota tidak valid.';
            header('Location: ' . route_url('members'));
            return;
        }
        
        $memberModel = new \App\Models\Member();
        $member = $memberModel->find($id);
        
        if (!$member) {
            $_SESSION['error'] = 'Anggota tidak ditemukan.';
            header('Location: ' . route_url('members'));
            return;
        }
        
        // Check tenant ownership
        $tenantId = $this->getCurrentTenantId();
        if ($tenantId !== null && $member['tenant_id'] != $tenantId) {
            $_SESSION['error'] = 'Akses ditolak.';
            header('Location: ' . route_url('members'));
            return;
        }
        
        // Check if member has active loans
        $loanModel = new \App\Models\Loan();
        $activeLoans = $loanModel->getActiveLoansByMember($id);
        
        if (!empty($activeLoans)) {
            $_SESSION['error'] = 'Anggota tidak dapat dihapus karena masih memiliki pinjaman aktif.';
            header('Location: ' . route_url('members/show') . '?id=' . $id);
            return;
        }
        
        // Check if member has savings balance
        $savingsModel = new \App\Models\SavingsAccount();
        $savingsBalance = $savingsModel->getTotalBalanceByMember($id);
        
        if ($savingsBalance > 0) {
            $_SESSION['error'] = 'Anggota tidak dapat dihapus karena masih memiliki saldo simpanan.';
            header('Location: ' . route_url('members/show') . '?id=' . $id);
            return;
        }
        
        try {
            $success = $memberModel->delete($id);
            
            if ($success) {
                $_SESSION['success'] = 'Anggota berhasil dihapus.';
                header('Location: ' . route_url('members'));
            } else {
                $_SESSION['error'] = 'Gagal menghapus anggota.';
                header('Location: ' . route_url('members/show') . '?id=' . $id);
            }
        } catch (\Exception $e) {
            $_SESSION['error'] = 'Gagal menghapus anggota: ' . $e->getMessage();
            header('Location: ' . route_url('members/show') . '?id=' . $id);
        }
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
        $tenantId = $this->getCurrentTenantId();
        $stmt = $pdo->prepare('INSERT INTO members (name, nik, phone, address, lat, lng, tenant_id) VALUES (?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute([$name, $nik, $phone, $address, $lat, $lng, $tenantId]);
        $_SESSION['success'] = 'Anggota berhasil ditambahkan.';
        header('Location: ' . route_url('members'));
    }

    /**
     * Get current tenant ID for data isolation
     */
    private function getCurrentTenantId(): ?int
    {
        // Check if user is system admin (tenant_id = NULL)
        $currentUser = current_user();
        if (!$currentUser) {
            return null;
        }

        // Get user details including tenant_id
        $pdo = \App\Database::getConnection();
        $stmt = $pdo->prepare('SELECT tenant_id FROM users WHERE id = ?');
        $stmt->execute([$currentUser['id']]);
        $user = $stmt->fetch();

        return $user ? $user['tenant_id'] : null;
    }
}
