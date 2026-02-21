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
            // === SESSION & CACHE CLEARING ===
            // 1. Clear all existing session data
            $_SESSION = array();

            // 2. Regenerate session ID for security
            session_regenerate_id(true);

            // 3. Clear any previous error messages
            unset($_SESSION['error']);

            // 4. Clear all caches using CacheUtil
            require_once __DIR__ . '/../Helpers/CacheUtil.php';
            \CacheUtil::clearAll();

            // 5. Clear browser cache headers to ensure fresh content
            \CacheUtil::clearBrowserCache();

            // 6. Set fresh session data
            $_SESSION['user'] = [
                'id' => $user['id'],
                'name' => $user['name'],
                'username' => $user['username'],
                'role' => $user['role_name'],
                'login_time' => time(), // Track login time
                'session_fresh' => true, // Mark as fresh session
            ];

            // 7. Log successful login with session clearing
            error_log("User {$username} logged in - Session cleared and all caches reset");

            header('Location: /maruba/index.php/dashboard');
            return;
        }

        $_SESSION['error'] = 'Login gagal. Periksa username/password.';
        header('Location: /maruba/index.php/');
    }

    public function logout(): void
    {
        // Log logout action
        $username = $_SESSION['user']['username'] ?? 'unknown';
        error_log("User {$username} logged out - Clearing session and cache");

        // Clear all caches using CacheUtil
        require_once __DIR__ . '/../Helpers/CacheUtil.php';
        \CacheUtil::clearAll();
        \CacheUtil::clearBrowserCache();

        // Clear all session data
        $_SESSION = array();

        // Destroy session completely
        if (isset($_COOKIE[session_name()])) {
            setcookie(session_name(), '', time() - 42000, '/');
        }
        session_destroy();

        header('Location: ' . route_url('/'));
    }

    /**
     * Helper method to clear all caches
     * Can be called from other parts of the application
     */
    private function clearAllCaches(): void
    {
        // Clear OPcache if available
        if (function_exists('opcache_reset')) {
            opcache_reset();
            error_log('OPcache cleared');
        }

        // Clear APCu cache if available
        if (function_exists('apcu_clear_cache')) {
            apcu_clear_cache();
            error_log('APCu cache cleared');
        }

        // Clear file-based cache if exists
        $cache_dir = __DIR__ . '/../../cache';
        if (is_dir($cache_dir)) {
            $files = glob($cache_dir . '/*');
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
            error_log('File cache cleared');
        }
    }
}
