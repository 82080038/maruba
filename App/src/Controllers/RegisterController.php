<?php
namespace App\Controllers;

use App\Database;
use App\Models\Tenant;
use App\Models\CooperativeRegistration;
use App\Models\User;
use App\Models\Role;

class RegisterController
{
    public function __construct()
    {
        $this->ensureAdminMappingTable();
    }

    public function showRegisterForm(): void
    {
        include view_path('auth/register');
    }

    public function registerUser(): void
    {
        verify_csrf();

        $name = trim($_POST['name'] ?? '');
        $username = trim($_POST['username'] ?? '');
        $password = trim($_POST['password'] ?? '');
        $passwordConfirm = trim($_POST['password_confirm'] ?? '');
        $cooperativeId = $_POST['cooperative_id'] ?? '';
        $cooperativeType = 'tenant';

        if ($cooperativeId === '__new') {
            $msg = 'Tidak ada koperasi di lokasi tersebut. Silakan daftarkan koperasi baru.';
            if ($this->wantsJson()) {
                $this->json(['success' => false, 'error' => $msg]);
                return;
            }
            $_SESSION['info'] = $msg;
            header('Location: ' . route_url('register/cooperative'));
            return;
        }

        if (!$name || !$username || !$password || !$passwordConfirm) {
            $msg = 'Semua field wajib diisi.';
            if ($this->wantsJson()) {
                $this->json(['success' => false, 'error' => $msg]);
                return;
            }
            $_SESSION['error'] = $msg;
            header('Location: ' . route_url('register'));
            return;
        }
        if ($password !== $passwordConfirm) {
            $msg = 'Konfirmasi kata sandi tidak sama.';
            if ($this->wantsJson()) {
                $this->json(['success' => false, 'error' => $msg]);
                return;
            }
            $_SESSION['error'] = $msg;
            header('Location: ' . route_url('register'));
            return;
        }

        $tenantModel = new Tenant();
        $tenant = $tenantModel->find((int)$cooperativeId);
        if (!$tenant) {
            $msg = 'Koperasi tidak ditemukan.';
            if ($this->wantsJson()) {
                $this->json(['success' => false, 'error' => $msg]);
                return;
            }
            $_SESSION['error'] = $msg;
            header('Location: ' . route_url('register'));
            return;
        }

        if ($this->cooperativeHasAdmin('tenant', (int)$tenant['id'])) {
            $msg = 'Koperasi ini sudah memiliki admin.';
            if ($this->wantsJson()) {
                $this->json(['success' => false, 'error' => $msg]);
                return;
            }
            $_SESSION['error'] = $msg;
            header('Location: ' . route_url('register'));
            return;
        }

        // Username uniqueness
        $userModel = new User();
        if ($userModel->findByUsername($username)) {
            $msg = 'Username sudah digunakan.';
            if ($this->wantsJson()) {
                $this->json(['success' => false, 'error' => $msg]);
                return;
            }
            $_SESSION['error'] = $msg;
            header('Location: ' . route_url('register'));
            return;
        }

        // Create user as admin
        $adminRoleId = $this->getAdminRoleId();
        $userId = $userModel->create([
            'name' => $name,
            'username' => $username,
            'password' => $password,
            'role_id' => $adminRoleId,
            'status' => 'active'
        ]);

        $this->assignCooperativeAdmin('tenant', (int)$tenant['id'], $userId);

        // Auto login
        $_SESSION['user'] = [
            'id' => $userId,
            'name' => $name,
            'username' => $username,
            'role' => 'admin',
            'login_time' => time(),
            'tenant_id' => $tenant['id']
        ];

        if ($this->wantsJson()) {
            $this->json(['success' => true, 'message' => 'Registrasi berhasil', 'user_id' => $userId]);
        } else {
            $_SESSION['success'] = 'Registrasi berhasil. Anda menjadi admin koperasi terpilih.';
            header('Location: ' . route_url('dashboard'));
        }
    }

    public function showCooperativeForm(): void
    {
        include view_path('cooperative/register');
    }

    public function registerCooperative(): void
    {
        verify_csrf();

        $data = [
            'cooperative_name' => trim($_POST['cooperative_name'] ?? ''),
            'legal_type' => trim($_POST['legal_type'] ?? ''),
            'registration_number' => trim($_POST['registration_number'] ?? ''),
            'description' => trim($_POST['description'] ?? ''),
            'province' => trim($_POST['province'] ?? ''),
            'city' => trim($_POST['city'] ?? ''),
            'district' => trim($_POST['district'] ?? ''),
            'postal_code' => trim($_POST['postal_code'] ?? ''),
            'phone' => trim($_POST['phone'] ?? ''),
            'email' => trim($_POST['email'] ?? ''),
            'established_date' => $_POST['established_date'] ?? null,
            'status' => 'draft',
            'submitted_at' => date('Y-m-d H:i:s'),
            'subscription_plan' => $_POST['subscription_plan'] ?? 'starter'
        ];

        $admin = [
            'name' => trim($_POST['admin_name'] ?? ''),
            'username' => trim($_POST['admin_username'] ?? ''),
            'password' => trim($_POST['admin_password'] ?? ''),
            'password_confirm' => trim($_POST['admin_password_confirm'] ?? '')
        ];

        if (!$data['cooperative_name'] || !$data['province'] || !$data['city'] || !$admin['name'] || !$admin['username'] || !$admin['password']) {
            $msg = 'Field wajib belum lengkap.';
            if ($this->wantsJson()) {
                $this->json(['success' => false, 'error' => $msg]);
                return;
            }
            $_SESSION['error'] = $msg;
            header('Location: ' . route_url('register/cooperative'));
            return;
        }
        if ($admin['password'] !== $admin['password_confirm']) {
            $msg = 'Konfirmasi kata sandi admin tidak sama.';
            if ($this->wantsJson()) {
                $this->json(['success' => false, 'error' => $msg]);
                return;
            }
            $_SESSION['error'] = $msg;
            header('Location: ' . route_url('register/cooperative'));
            return;
        }

        $coopRegModel = new CooperativeRegistration();
        try {
            $registrationId = $coopRegModel->create($data);
        } catch (\Exception $e) {
            $msg = 'Gagal mendaftar koperasi: ' . $e->getMessage();
            if ($this->wantsJson()) {
                $this->json(['success' => false, 'error' => $msg]);
                return;
            }
            $_SESSION['error'] = $msg;
            header('Location: ' . route_url('register/cooperative'));
            return;
        }

        // Create admin user if not exists
        $userModel = new User();
        if ($userModel->findByUsername($admin['username'])) {
            $msg = 'Username admin sudah digunakan.';
            if ($this->wantsJson()) {
                $this->json(['success' => false, 'error' => $msg]);
                return;
            }
            $_SESSION['error'] = $msg;
            header('Location: ' . route_url('register/cooperative'));
            return;
        }

        $adminRoleId = $this->getAdminRoleId();
        $userId = $userModel->create([
            'name' => $admin['name'],
            'username' => $admin['username'],
            'password' => $admin['password'],
            'role_id' => $adminRoleId,
            'status' => 'active'
        ]);

        $this->assignCooperativeAdmin('registration', (int)$registrationId, $userId);

        $_SESSION['user'] = [
            'id' => $userId,
            'name' => $admin['name'],
            'username' => $admin['username'],
            'role' => 'admin',
            'login_time' => time(),
            'cooperative_registration_id' => $registrationId
        ];

        if ($this->wantsJson()) {
            $this->json(['success' => true, 'message' => 'Koperasi berhasil didaftarkan', 'registration_id' => $registrationId, 'user_id' => $userId]);
        } else {
            $_SESSION['success'] = 'Koperasi berhasil didaftarkan. Anda tercatat sebagai admin koperasi ini.';
            header('Location: ' . route_url('dashboard'));
        }
    }

    private function ensureAdminMappingTable(): void
    {
        $db = Database::getConnection();
        $db->exec("CREATE TABLE IF NOT EXISTS cooperative_admins (
            id INT AUTO_INCREMENT PRIMARY KEY,
            cooperative_type ENUM('tenant','registration') NOT NULL,
            cooperative_id INT NOT NULL,
            user_id INT NOT NULL,
            UNIQUE KEY uniq_coop_admin (cooperative_type, cooperative_id),
            UNIQUE KEY uniq_user (user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    }

    private function cooperativeHasAdmin(string $type, int $id): bool
    {
        $db = Database::getConnection();
        $stmt = $db->prepare('SELECT id FROM cooperative_admins WHERE cooperative_type = ? AND cooperative_id = ? LIMIT 1');
        $stmt->execute([$type, $id]);
        return (bool)$stmt->fetch();
    }

    private function assignCooperativeAdmin(string $type, int $id, int $userId): void
    {
        $db = Database::getConnection();
        $stmt = $db->prepare('INSERT INTO cooperative_admins (cooperative_type, cooperative_id, user_id) VALUES (?,?,?)');
        $stmt->execute([$type, $id, $userId]);
    }

    private function getAdminRoleId(): int
    {
        $roleModel = new Role();
        $adminRole = $roleModel->findWhere(['name' => 'admin'], [], 1);
        if (!empty($adminRole)) {
            return (int)$adminRole[0]['id'];
        }
        // fallback: first role
        $roles = $roleModel->all(['id' => 'ASC']);
        return (int)($roles[0]['id'] ?? 1);
    }

    private function extractLocations(array $cooperatives): array
    {
        $locations = [];
        foreach ($cooperatives as $coop) {
            $addr = [];
            if (!empty($coop['address_details'])) {
                $decoded = is_array($coop['address_details']) ? $coop['address_details'] : json_decode($coop['address_details'], true);
                if (is_array($decoded)) {
                    $addr = $decoded;
                }
            }
            $province = $addr['province'] ?? 'Lainnya';
            $city = $addr['city'] ?? 'Lainnya';
            $district = $addr['district'] ?? 'Lainnya';
            $locations[$province][$city][$district] = true;
        }
        return $locations;
    }

    public function cooperativesJson(): void
    {
        header('Content-Type: application/json');

        $tenantModel = new Tenant();
        $cooperatives = $tenantModel->getActiveTenants();

        $data = array_map(function($c) {
            $addr = [];
            if (!empty($c['address_details'])) {
                $decoded = is_array($c['address_details']) ? $c['address_details'] : json_decode($c['address_details'], true);
                if (is_array($decoded)) {
                    $addr = $decoded;
                }
            }
            return [
                'id' => (int)$c['id'],
                'name' => $c['name'],
                'slug' => $c['slug'] ?? '',
                'province' => $c['province'] ?? ($addr['province'] ?? ''),
                'city' => $c['city'] ?? ($addr['city'] ?? ''),
                'district' => $c['district'] ?? ($addr['district'] ?? ''),
            ];
        }, $cooperatives);

        echo json_encode(['success' => true, 'data' => $data]);
    }

    private function wantsJson(): bool
    {
        $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
        $xhr = strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest';
        return $xhr || stripos($accept, 'application/json') !== false;
    }

    private function json(array $payload): void
    {
        header('Content-Type: application/json');
        echo json_encode($payload);
    }
}
