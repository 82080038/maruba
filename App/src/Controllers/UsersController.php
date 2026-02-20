<?php
namespace App\Controllers;
use App\Helpers\AuthHelper;

class UsersController
{
    public function index(): void
    {
        require_login();
        AuthHelper::requirePermission('users', 'view');
        $title = 'Pengguna';
        $pdo = \App\Database::getConnection();
        $stmt = $pdo->prepare('SELECT u.*, r.name AS role_name FROM users u JOIN roles r ON u.role_id = r.id ORDER BY u.name');
        $stmt->execute();
        $users = $stmt->fetchAll();
        include view_path('users/index');
    }

    public function create(): void
    {
        require_login();
        AuthHelper::requirePermission('users', 'create');
        $title = 'Tambah Pengguna';
        $pdo = \App\Database::getConnection();
        $stmt = $pdo->query('SELECT id, name FROM roles ORDER BY name');
        $roles = $stmt->fetchAll();
        include view_path('users/create');
    }

    public function store(): void
    {
        require_login();
        AuthHelper::requirePermission('users', 'create');
        $name = trim($_POST['name'] ?? '');
        $username = trim($_POST['username'] ?? '');
        $password = trim($_POST['password'] ?? '');
        $roleId = (int)($_POST['role_id'] ?? 0);
        if (empty($name) || empty($username) || empty($password) || !$roleId) {
            $_SESSION['error'] = 'Data wajib diisi.';
            header('Location: ' . route_url('users/create'));
            return;
        }
        $hash = password_hash($password, PASSWORD_BCRYPT);
        $pdo = \App\Database::getConnection();
        $stmt = $pdo->prepare('INSERT INTO users (name, username, password_hash, role_id) VALUES (?, ?, ?, ?)');
        $stmt->execute([$name, $username, $hash, $roleId]);
        $_SESSION['success'] = 'Pengguna berhasil ditambahkan.';
        header('Location: ' . route_url('users'));
    }
}
