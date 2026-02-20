<?php
namespace App\Controllers;

class AuthController
{
    public function showLogin(): void
    {
        $title = 'Login Koperasi';
        include view_path('auth/login');
    }

    public function login(): void
    {
        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';

        // DB check
        $pdo = \App\Database::getConnection();
        $stmt = $pdo->prepare('SELECT u.*, r.name AS role_name FROM users u JOIN roles r ON u.role_id = r.id WHERE u.username = ?');
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            $_SESSION['user'] = [
                'id' => $user['id'],
                'name' => $user['name'],
                'username' => $user['username'],
                'role' => $user['role_name'],
            ];
            header('Location: ' . route_url('dashboard'));
            return;
        }

        $_SESSION['error'] = 'Login gagal. Periksa username/password.';
        header('Location: ' . route_url(''));
    }

    public function logout(): void
    {
        session_destroy();
        header('Location: ' . route_url(''));
    }
}
