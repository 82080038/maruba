<?php
namespace App\Controllers;

class AuthController
{
    public function showLogin(): void
    {
        $title = 'Login Koperasi';
        include __DIR__ . '/../Views/auth/login.php';
    }

    public function login(): void
    {
        verify_csrf();

        // Simple rate limiting: max 5 attempts per 5 minutes per username/IP
        $usernameKey = strtolower(trim($_POST['username'] ?? '')) ?: 'unknown';
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $bucket = $usernameKey . '|' . $ip;
        $now = time();
        $_SESSION['login_attempts'] = $_SESSION['login_attempts'] ?? [];
        $attempt = $_SESSION['login_attempts'][$bucket] ?? ['count' => 0, 'first' => $now];
        if ($now - $attempt['first'] > 300) {
            $attempt = ['count' => 0, 'first' => $now];
        }
        if ($attempt['count'] >= 5) {
            $_SESSION['error'] = 'Terlalu banyak percobaan login. Coba lagi setelah beberapa menit.';
            header('Location: /maruba/index.php/login');
            return;
        }

        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';

        // DB check
        $pdo = \App\Database::getConnection();
        $stmt = $pdo->prepare('SELECT u.*, r.name AS role_name FROM users u JOIN roles r ON u.role_id = r.id WHERE u.username = ?');
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            // === IMPROVED SESSION MANAGEMENT ===
            // 1. Preserve important session data before clearing
            $csrfToken = $_SESSION['csrf_token'] ?? null;
            $loginAttempts = $_SESSION['login_attempts'] ?? [];
            
            // 2. Regenerate session ID for security
            session_regenerate_id(true);
            
            // 3. Restore important data
            if ($csrfToken) {
                $_SESSION['csrf_token'] = $csrfToken;
            }
            $_SESSION['login_attempts'] = $loginAttempts;

            // 4. Clear any previous error messages
            unset($_SESSION['error']);

            // 5. Clear only specific caches (not all caches)
            $this->clearLoginCaches();

            // 6. Set fresh session data
            $_SESSION['user'] = [
                'id' => $user['id'],
                'name' => $user['name'],
                'username' => $user['username'],
                'role' => $user['role_name'],
                'login_time' => time(),
                'session_fresh' => true,
                'last_activity' => time(),
            ];

            // 7. Log successful login
            error_log("User {$username} logged in successfully from IP: {$ip}");

            // Reset rate-limit bucket
            unset($_SESSION['login_attempts'][$bucket]);

            header('Location: /maruba/index.php/dashboard');
            return;
        }

        // Increment rate limit counter on failure
        $attempt['count'] += 1;
        $_SESSION['login_attempts'][$bucket] = $attempt;

        $_SESSION['error'] = 'Login gagal. Periksa username/password.';
        error_log("Login failed for username: {$username} from IP: {$ip}");
        header('Location: /maruba/index.php/');
    }

    public function logout(): void
    {
        $username = $_SESSION['user']['username'] ?? 'unknown';
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        
        // Log logout action
        error_log("User {$username} logged out from IP: {$ip}");

        // Clear only specific caches (not all caches)
        $this->clearLogoutCaches();

        // Clear session data but preserve some info for logging
        $userBackup = $_SESSION['user'] ?? null;
        
        // Clear all session data
        $_SESSION = array();

        // Destroy session completely
        if (isset($_COOKIE[session_name()])) {
            setcookie(session_name(), '', time() - 42000, '/');
        }
        session_destroy();

        // Clear browser cache headers
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');

        header('Location: ' . route_url('/'));
    }

    /**
     * Clear caches specific to login process
     */
    private function clearLoginCaches(): void
    {
        // Clear only session-related caches
        if (class_exists('CacheUtil')) {
            \CacheUtil::clearSessionCache();
            \CacheUtil::clearBrowserCache();
        }
    }

    /**
     * Clear caches specific to logout process
     */
    private function clearLogoutCaches(): void
    {
        // Clear session and browser caches
        if (class_exists('CacheUtil')) {
            \CacheUtil::clearSessionCache();
            \CacheUtil::clearBrowserCache();
        }
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
