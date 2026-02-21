<?php
namespace App\Controllers;

use App\Models\User;
use App\Models\Role;
use App\Helpers\AuthHelper;
use App\Helpers\Notification;

class UsersController
{
    public function index(): void
    {
        require_login();
        AuthHelper::requirePermission('users', 'view');

        $page = (int)($_GET['page'] ?? 1);
        $limit = (int)($_GET['limit'] ?? 15);
        $status = $_GET['status'] ?? null;

        $userModel = new User();
        $conditions = $status ? ['status' => $status] : [];

        $result = $userModel->paginate($page, $limit, $conditions);

        // Add role information
        $roleModel = new Role();
        $roles = $roleModel->all();

        include view_path('users/index');
    }

    public function create(): void
    {
        require_login();
        AuthHelper::requirePermission('users', 'create');

        $roleModel = new Role();
        $roles = $roleModel->all(['name' => 'ASC']);

        include view_path('users/create');
    }

    public function store(): void
    {
        require_login();
        AuthHelper::requirePermission('users', 'create');
        verify_csrf();

        // Check resource limits before creating user
        $limitMiddleware = new \App\Middleware\ResourceLimitMiddleware();
        if (!$limitMiddleware->checkUserLimit()) {
            $_SESSION['error'] = 'Batas jumlah pengguna telah tercapai. Silakan upgrade paket berlangganan.';
            header('Location: ' . route_url('users/create'));
            return;
        }

        $data = [
            'name' => trim($_POST['name'] ?? ''),
            'username' => trim($_POST['username'] ?? ''),
            'password' => trim($_POST['password'] ?? ''),
            'role_id' => (int)($_POST['role_id'] ?? 0),
            'status' => $_POST['status'] ?? 'active'
        ];

        // Validation
        if (empty($data['name']) || empty($data['username']) || empty($data['password']) || !$data['role_id']) {
            $_SESSION['error'] = 'Semua field wajib diisi.';
            header('Location: ' . route_url('users/create'));
            return;
        }

        // Check username uniqueness
        $userModel = new User();
        $existingUser = $userModel->findByUsername($data['username']);
        if ($existingUser) {
            $_SESSION['error'] = 'Username sudah digunakan.';
            header('Location: ' . route_url('users/create'));
            return;
        }

        try {
            $userId = $userModel->create($data);

            // Send welcome notification
            $roleModel = new Role();
            $role = $roleModel->find($data['role_id']);

            Notification::send('email', [
                'email' => '', // User doesn't have email field yet
                'name' => $data['name']
            ], 'Akun KSP Dibuat', "Akun Anda telah dibuat dengan role: {$role['name']}");

            $_SESSION['success'] = 'Pengguna berhasil ditambahkan.';
            header('Location: ' . route_url('users'));

        } catch (\Exception $e) {
            $_SESSION['error'] = 'Gagal menambahkan pengguna: ' . $e->getMessage();
            header('Location: ' . route_url('users/create'));
        }
    }

    public function show(): void
    {
        require_login();
        AuthHelper::requirePermission('users', 'view');

        $id = (int)($_GET['id'] ?? 0);
        if (!$id) {
            http_response_code(404);
            echo 'User not found';
            return;
        }

        $userModel = new User();
        $user = $userModel->findWithRole($id);

        if (!$user) {
            http_response_code(404);
            echo 'User not found';
            return;
        }

        // Get user activity
        $auditModel = new \App\Models\AuditLog();
        $recentActivity = $auditModel->getUserActivities($id, 10);

        include view_path('users/show');
    }

    public function edit(): void
    {
        require_login();
        AuthHelper::requirePermission('users', 'edit');

        $id = (int)($_GET['id'] ?? 0);
        if (!$id) {
            http_response_code(404);
            echo 'User not found';
            return;
        }

        $userModel = new User();
        $user = $userModel->findWithRole($id);

        if (!$user) {
            http_response_code(404);
            echo 'User not found';
            return;
        }

        $roleModel = new Role();
        $roles = $roleModel->all(['name' => 'ASC']);

        include view_path('users/edit');
    }

    public function update(): void
    {
        require_login();
        AuthHelper::requirePermission('users', 'edit');
        verify_csrf();

        $id = (int)($_GET['id'] ?? 0);
        if (!$id) {
            http_response_code(400);
            echo 'User ID required';
            return;
        }

        $data = [
            'name' => trim($_POST['name'] ?? ''),
            'username' => trim($_POST['username'] ?? ''),
            'role_id' => (int)($_POST['role_id'] ?? 0),
            'status' => $_POST['status'] ?? 'active'
        ];

        // Handle password change
        $newPassword = trim($_POST['password'] ?? '');
        if (!empty($newPassword)) {
            $data['password'] = $newPassword;
        }

        // Validation
        if (empty($data['name']) || empty($data['username']) || !$data['role_id']) {
            $_SESSION['error'] = 'Field wajib tidak boleh kosong.';
            header('Location: ' . route_url('users/edit') . '?id=' . $id);
            return;
        }

        // Check username uniqueness (exclude current user)
        $userModel = new User();
        $existingUser = $userModel->findByUsername($data['username']);
        if ($existingUser && $existingUser['id'] != $id) {
            $_SESSION['error'] = 'Username sudah digunakan.';
            header('Location: ' . route_url('users/edit') . '?id=' . $id);
            return;
        }

        try {
            $success = $userModel->update($id, $data);

            if ($success) {
                $_SESSION['success'] = 'Pengguna berhasil diperbarui.';
                header('Location: ' . route_url('users'));
            } else {
                $_SESSION['error'] = 'Gagal memperbarui pengguna.';
                header('Location: ' . route_url('users/edit') . '?id=' . $id);
            }

        } catch (\Exception $e) {
            $_SESSION['error'] = 'Gagal memperbarui pengguna: ' . $e->getMessage();
            header('Location: ' . route_url('users/edit') . '?id=' . $id);
        }
    }

    public function delete(): void
    {
        require_login();
        AuthHelper::requirePermission('users', 'delete');

        $id = (int)($_GET['id'] ?? 0);
        if (!$id) {
            http_response_code(400);
            echo 'User ID required';
            return;
        }

        $userModel = new User();
        $user = $userModel->find($id);

        if (!$user) {
            http_response_code(404);
            echo 'User not found';
            return;
        }

        // Prevent deleting own account
        $currentUser = current_user();
        if ($currentUser['id'] == $id) {
            $_SESSION['error'] = 'Tidak dapat menghapus akun sendiri.';
            header('Location: ' . route_url('users'));
            return;
        }

        try {
            $success = $userModel->delete($id);

            if ($success) {
                $_SESSION['success'] = 'Pengguna berhasil dihapus.';
            } else {
                $_SESSION['error'] = 'Gagal menghapus pengguna.';
            }

        } catch (\Exception $e) {
            $_SESSION['error'] = 'Gagal menghapus pengguna: ' . $e->getMessage();
        }

        header('Location: ' . route_url('users'));
    }

    // ===== ROLE MANAGEMENT =====
    public function roles(): void
    {
        require_login();
        AuthHelper::requirePermission('roles', 'view');

        $roleModel = new Role();
        $roles = $roleModel->all(['name' => 'ASC']);

        // Add user count for each role
        $userModel = new User();
        foreach ($roles as &$role) {
            $role['user_count'] = $userModel->count(['role_id' => $role['id']]);
        }

        include view_path('users/roles');
    }

    public function createRole(): void
    {
        require_login();
        AuthHelper::requirePermission('roles', 'create');

        include view_path('users/create_role');
    }

    public function storeRole(): void
    {
        require_login();
        AuthHelper::requirePermission('roles', 'create');
        verify_csrf();

        $data = [
            'name' => trim($_POST['name'] ?? ''),
            'permissions' => json_decode($_POST['permissions'] ?? '[]', true)
        ];

        if (empty($data['name'])) {
            $_SESSION['error'] = 'Nama role wajib diisi.';
            header('Location: ' . route_url('users/roles/create'));
            return;
        }

        $roleModel = new Role();

        // Check uniqueness
        $existingRole = $roleModel->findWhere(['name' => $data['name']]);
        if (!empty($existingRole)) {
            $_SESSION['error'] = 'Role dengan nama ini sudah ada.';
            header('Location: ' . route_url('users/roles/create'));
            return;
        }

        try {
            $roleModel->create($data);
            $_SESSION['success'] = 'Role berhasil ditambahkan.';
            header('Location: ' . route_url('users/roles'));

        } catch (\Exception $e) {
            $_SESSION['error'] = 'Gagal menambahkan role: ' . $e->getMessage();
            header('Location: ' . route_url('users/roles/create'));
        }
    }

    public function editRole(): void
    {
        require_login();
        AuthHelper::requirePermission('roles', 'edit');

        $id = (int)($_GET['id'] ?? 0);
        if (!$id) {
            http_response_code(404);
            echo 'Role not found';
            return;
        }

        $roleModel = new Role();
        $role = $roleModel->find($id);

        if (!$role) {
            http_response_code(404);
            echo 'Role not found';
            return;
        }

        include view_path('users/edit_role');
    }

    public function updateRole(): void
    {
        require_login();
        AuthHelper::requirePermission('roles', 'edit');
        verify_csrf();

        $id = (int)($_GET['id'] ?? 0);
        if (!$id) {
            http_response_code(400);
            echo 'Role ID required';
            return;
        }

        $data = [
            'name' => trim($_POST['name'] ?? ''),
            'permissions' => json_decode($_POST['permissions'] ?? '[]', true)
        ];

        if (empty($data['name'])) {
            $_SESSION['error'] = 'Nama role wajib diisi.';
            header('Location: ' . route_url('users/roles/edit') . '?id=' . $id);
            return;
        }

        $roleModel = new Role();

        // Check uniqueness (exclude current role)
        $existingRole = $roleModel->findWhere(['name' => $data['name']]);
        if (!empty($existingRole) && $existingRole[0]['id'] != $id) {
            $_SESSION['error'] = 'Role dengan nama ini sudah ada.';
            header('Location: ' . route_url('users/roles/edit') . '?id=' . $id);
            return;
        }

        try {
            $success = $roleModel->update($id, $data);

            if ($success) {
                $_SESSION['success'] = 'Role berhasil diperbarui.';
                header('Location: ' . route_url('users/roles'));
            } else {
                $_SESSION['error'] = 'Gagal memperbarui role.';
                header('Location: ' . route_url('users/roles/edit') . '?id=' . $id);
            }

        } catch (\Exception $e) {
            $_SESSION['error'] = 'Gagal memperbarui role: ' . $e->getMessage();
            header('Location: ' . route_url('users/roles/edit') . '?id=' . $id);
        }
    }

    // ===== API ENDPOINTS =====
    public function getUsersApi(): void
    {
        require_login();

        $page = (int)($_GET['page'] ?? 1);
        $limit = (int)($_GET['limit'] ?? 15);

        $userModel = new User();
        $result = $userModel->paginate($page, $limit);

        header('Content-Type: application/json');
        echo json_encode($result);
    }

    public function getRolesApi(): void
    {
        require_login();

        $roleModel = new Role();
        $roles = $roleModel->all(['name' => 'ASC']);

        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'roles' => $roles]);
    }

    // ===== PROFILE MANAGEMENT =====
    public function profile(): void
    {
        require_login();

        $user = current_user();
        $userModel = new User();
        $userDetails = $userModel->findWithRole($user['id']);

        include view_path('users/profile');
    }

    public function updateProfile(): void
    {
        require_login();
        verify_csrf();

        $user = current_user();
        $userId = $user['id'];

        $data = [
            'name' => trim($_POST['name'] ?? ''),
            'username' => trim($_POST['username'] ?? '')
        ];

        // Handle password change
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        if (!empty($newPassword)) {
            if (empty($currentPassword)) {
                $_SESSION['error'] = 'Password saat ini wajib diisi untuk mengubah password.';
                header('Location: ' . route_url('users/profile'));
                return;
            }

            if ($newPassword !== $confirmPassword) {
                $_SESSION['error'] = 'Konfirmasi password tidak cocok.';
                header('Location: ' . route_url('users/profile'));
                return;
            }

            $userModel = new User();
            if (!$userModel->verifyPassword($currentPassword, $user['password_hash'])) {
                $_SESSION['error'] = 'Password saat ini salah.';
                header('Location: ' . route_url('users/profile'));
                return;
            }

            $data['password'] = $newPassword;
        }

        if (empty($data['name']) || empty($data['username'])) {
            $_SESSION['error'] = 'Nama dan username wajib diisi.';
            header('Location: ' . route_url('users/profile'));
            return;
        }

        // Check username uniqueness
        $userModel = new User();
        $existingUser = $userModel->findByUsername($data['username']);
        if ($existingUser && $existingUser['id'] != $userId) {
            $_SESSION['error'] = 'Username sudah digunakan.';
            header('Location: ' . route_url('users/profile'));
            return;
        }

        try {
            unset($data['username']); // Prevent username change for now
            $success = $userModel->update($userId, $data);

            if ($success) {
                // Update session data
                $_SESSION['user']['name'] = $data['name'];
                $_SESSION['success'] = 'Profil berhasil diperbarui.';
            } else {
                $_SESSION['error'] = 'Gagal memperbarui profil.';
            }

        } catch (\Exception $e) {
            $_SESSION['error'] = 'Gagal memperbarui profil: ' . $e->getMessage();
        }

        header('Location: ' . route_url('users/profile'));
    }
}
