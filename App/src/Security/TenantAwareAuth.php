<?php
namespace App\Security;

use App\Models\User;
use App\Middleware\TenantMiddleware;

/**
 * Tenant-Aware Authentication Manager
 *
 * Handles authentication with tenant context awareness
 * Ensures proper session isolation between tenants
 */
class TenantAwareAuth
{
    private User $userModel;

    public function __construct()
    {
        $this->userModel = new User();
    }

    /**
     * Authenticate user with tenant context
     */
    public function authenticate(string $username, string $password, ?string $tenantSlug = null): array
    {
        // Basic input validation
        if (empty($username) || empty($password)) {
            return $this->authResponse(false, 'Username dan password harus diisi');
        }

        try {
            // Find user by username
            $user = $this->userModel->findByUsername($username);

            if (!$user) {
                $this->logFailedLogin($username, 'user_not_found');
                return $this->authResponse(false, 'Username atau password salah');
            }

            // Check if user is active
            if ($user['status'] !== 'active') {
                $this->logFailedLogin($username, 'user_inactive');
                return $this->authResponse(false, 'Akun tidak aktif');
            }

            // Verify password
            if (!password_verify($password, $user['password_hash'])) {
                $this->logFailedLogin($username, 'invalid_password');
                return $this->authResponse(false, 'Username atau password salah');
            }

            // Check tenant context for tenant users
            if ($user['tenant_id'] !== null) {
                // This is a tenant user
                if (!$this->validateTenantContext($user, $tenantSlug)) {
                    $this->logFailedLogin($username, 'invalid_tenant_context');
                    return $this->authResponse(false, 'Akses tidak valid untuk tenant ini');
                }
            }

            // Authentication successful
            $this->logSuccessfulLogin($user);
            $this->createSecureSession($user);

            return $this->authResponse(true, 'Login berhasil', [
                'user' => $this->sanitizeUserData($user),
                'redirect_url' => $this->getPostLoginRedirect($user)
            ]);

        } catch (\Exception $e) {
            error_log("Authentication error for {$username}: " . $e->getMessage());
            return $this->authResponse(false, 'Terjadi kesalahan sistem');
        }
    }

    /**
     * Validate tenant context for tenant users
     */
    private function validateTenantContext(array $user, ?string $tenantSlug): bool
    {
        // If user has tenant_id but no tenant context provided, check if they can access system
        if ($tenantSlug === null) {
            // Check if user has system-level permissions
            return $this->hasSystemPermissions($user);
        }

        // Check if tenant slug matches user's tenant
        $userTenant = $this->userModel->getUserTenant($user['id']);
        if (!$userTenant) {
            return false;
        }

        return $userTenant['slug'] === $tenantSlug;
    }

    /**
     * Check if user has system-level permissions
     */
    private function hasSystemPermissions(array $user): bool
    {
        // Define roles that can access system without tenant context
        $systemRoles = ['super_admin', 'system_admin'];

        // Check user's role permissions
        if (isset($user['permissions'])) {
            $permissions = json_decode($user['permissions'], true);
            return isset($permissions['system_access']) && $permissions['system_access'] === true;
        }

        return false;
    }

    /**
     * Create secure session with tenant isolation
     */
    private function createSecureSession(array $user): void
    {
        // Regenerate session ID for security
        session_regenerate_id(true);

        // Clear any existing session data
        $_SESSION = [];

        // Set secure session data
        $_SESSION['user'] = [
            'id' => $user['id'],
            'name' => $user['name'],
            'username' => $user['username'],
            'role' => $user['role'] ?? 'member',
            'tenant_id' => $user['tenant_id'],
            'login_time' => time(),
            'session_id' => session_id(),
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'last_activity' => time()
        ];

        // Set tenant context if user belongs to a tenant
        if ($user['tenant_id'] !== null) {
            $_SESSION['tenant_context'] = [
                'tenant_id' => $user['tenant_id'],
                'tenant_slug' => $this->userModel->getUserTenant($user['id'])['slug'] ?? null,
                'user_permissions' => $this->getUserPermissions($user)
            ];
        }

        // Set session security flags
        $_SESSION['security'] = [
            'csrf_token' => bin2hex(random_bytes(32)),
            'login_attempts' => 0,
            'last_password_change' => $user['password_changed_at'] ?? null,
            'require_password_change' => $this->requiresPasswordChange($user)
        ];

        // Update user's last login
        $this->userModel->updateLastLogin($user['id']);
    }

    /**
     * Get user permissions for tenant context
     */
    private function getUserPermissions(array $user): array
    {
        $permissions = [];

        // Base permissions by role
        switch ($user['role']) {
            case 'super_admin':
                $permissions = [
                    'system_access' => true,
                    'tenant_management' => true,
                    'user_management' => true,
                    'billing_management' => true,
                    'system_configuration' => true
                ];
                break;

            case 'admin':
                $permissions = [
                    'tenant_admin' => true,
                    'user_management' => true,
                    'loan_management' => true,
                    'savings_management' => true,
                    'reports' => true,
                    'compliance' => true
                ];
                break;

            case 'manager':
                $permissions = [
                    'loan_approval' => true,
                    'reports' => true,
                    'user_view' => true,
                    'compliance_view' => true
                ];
                break;

            case 'kasir':
                $permissions = [
                    'transaction_processing' => true,
                    'cash_management' => true,
                    'daily_reports' => true
                ];
                break;

            case 'surveyor':
                $permissions = [
                    'loan_survey' => true,
                    'location_tracking' => true,
                    'survey_reports' => true
                ];
                break;

            case 'collector':
                $permissions = [
                    'repayment_collection' => true,
                    'customer_visits' => true,
                    'collection_reports' => true
                ];
                break;

            case 'member':
            default:
                $permissions = [
                    'profile_view' => true,
                    'loan_application' => true,
                    'repayment_view' => true,
                    'savings_access' => true
                ];
                break;
        }

        // Merge with custom permissions from database
        if (isset($user['permissions']) && is_string($user['permissions'])) {
            $customPermissions = json_decode($user['permissions'], true);
            if (is_array($customPermissions)) {
                $permissions = array_merge($permissions, $customPermissions);
            }
        }

        return $permissions;
    }

    /**
     * Check if user requires password change
     */
    private function requiresPasswordChange(array $user): bool
    {
        // Force password change for new users
        if (empty($user['password_changed_at'])) {
            return true;
        }

        // Force password change if older than 90 days
        $passwordAge = time() - strtotime($user['password_changed_at']);
        if ($passwordAge > (90 * 24 * 60 * 60)) {
            return true;
        }

        // Force password change if account was compromised
        if (isset($user['security_flags']) && in_array('password_reset_required', $user['security_flags'])) {
            return true;
        }

        return false;
    }

    /**
     * Validate current session security
     */
    public function validateSession(): array
    {
        // Check if session exists
        if (!isset($_SESSION['user'])) {
            return ['valid' => false, 'reason' => 'no_session'];
        }

        $user = $_SESSION['user'];

        // Check session timeout (24 hours)
        if (time() - $user['login_time'] > 86400) {
            return ['valid' => false, 'reason' => 'session_expired'];
        }

        // Check IP address consistency (optional security feature)
        if (isset($_SESSION['security']['check_ip']) && $_SESSION['security']['check_ip']) {
            if (($user['ip_address'] ?? '') !== ($_SERVER['REMOTE_ADDR'] ?? '')) {
                return ['valid' => false, 'reason' => 'ip_changed'];
            }
        }

        // Check user agent consistency (optional)
        if (isset($_SESSION['security']['check_user_agent']) && $_SESSION['security']['check_user_agent']) {
            if (($user['user_agent'] ?? '') !== ($_SERVER['HTTP_USER_AGENT'] ?? '')) {
                return ['valid' => false, 'reason' => 'user_agent_changed'];
            }
        }

        // Update last activity
        $_SESSION['user']['last_activity'] = time();

        return ['valid' => true];
    }

    /**
     * Logout user and clean session
     */
    public function logout(): void
    {
        $username = $_SESSION['user']['username'] ?? 'unknown';

        // Log logout
        error_log("User {$username} logged out");

        // Clear all session data
        $_SESSION = [];

        // Destroy session
        if (isset($_COOKIE[session_name()])) {
            setcookie(session_name(), '', time() - 42000, '/');
        }
        session_destroy();
    }

    /**
     * Get current authenticated user
     */
    public function getCurrentUser(): ?array
    {
        if (!$this->validateSession()['valid']) {
            return null;
        }

        return $_SESSION['user'] ?? null;
    }

    /**
     * Check if user has permission
     */
    public function hasPermission(string $permission): bool
    {
        $user = $this->getCurrentUser();
        if (!$user) {
            return false;
        }

        $permissions = $_SESSION['tenant_context']['user_permissions'] ?? [];

        return isset($permissions[$permission]) && $permissions[$permission] === true;
    }

    /**
     * Sanitize user data for session storage
     */
    private function sanitizeUserData(array $user): array
    {
        // Remove sensitive fields
        unset($user['password_hash']);
        unset($user['reset_token']);
        unset($user['security_flags']);

        return $user;
    }

    /**
     * Get post-login redirect URL based on user role
     */
    private function getPostLoginRedirect(array $user): string
    {
        $baseUrl = '/';

        switch ($user['role']) {
            case 'super_admin':
                return $baseUrl . 'admin/dashboard';
            case 'admin':
                return $baseUrl . 'tenant/dashboard';
            case 'manager':
                return $baseUrl . 'manager/dashboard';
            case 'kasir':
                return $baseUrl . 'kasir/dashboard';
            case 'surveyor':
                return $baseUrl . 'surveyor/dashboard';
            case 'collector':
                return $baseUrl . 'collector/dashboard';
            case 'member':
            default:
                return $baseUrl . 'member/dashboard';
        }
    }

    /**
     * Log failed login attempt
     */
    private function logFailedLogin(string $username, string $reason): void
    {
        error_log("Failed login attempt for {$username}: {$reason} from {$_SERVER['REMOTE_ADDR']}");
    }

    /**
     * Log successful login
     */
    private function logSuccessfulLogin(array $user): void
    {
        error_log("User {$user['username']} logged in successfully from {$_SERVER['REMOTE_ADDR']}");
    }

    /**
     * Create standardized auth response
     */
    private function authResponse(bool $success, string $message, array $data = []): array
    {
        return [
            'success' => $success,
            'message' => $message,
            'data' => $data,
            'timestamp' => time()
        ];
    }

    /**
     * Get CSRF token for current session
     */
    public function getCsrfToken(): string
    {
        if (!isset($_SESSION['security']['csrf_token'])) {
            $_SESSION['security']['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['security']['csrf_token'];
    }

    /**
     * Validate CSRF token
     */
    public function validateCsrfToken(string $token): bool
    {
        $sessionToken = $_SESSION['security']['csrf_token'] ?? '';
        return hash_equals($sessionToken, $token);
    }
}

/**
 * Authentication Middleware for Route Protection
 */
class AuthMiddleware
{
    private TenantAwareAuth $auth;

    public function __construct()
    {
        $this->auth = new TenantAwareAuth();
    }

    /**
     * Require authentication for route
     */
    public function requireAuth(): void
    {
        if (!$this->auth->getCurrentUser()) {
            $this->redirectToLogin();
        }
    }

    /**
     * Require specific permission
     */
    public function requirePermission(string $permission): void
    {
        $this->requireAuth();

        if (!$this->auth->hasPermission($permission)) {
            $this->accessDenied();
        }
    }

    /**
     * Require specific role
     */
    public function requireRole(string $role): void
    {
        $this->requireAuth();

        $user = $this->auth->getCurrentUser();
        if (($user['role'] ?? '') !== $role) {
            $this->accessDenied();
        }
    }

    /**
     * Require tenant context
     */
    public function requireTenantContext(): void
    {
        $this->requireAuth();

        if (!TenantMiddleware::hasTenant()) {
            $this->accessDenied('Tenant context required');
        }
    }

    /**
     * Redirect to login page
     */
    private function redirectToLogin(): void
    {
        $returnUrl = urlencode($_SERVER['REQUEST_URI']);
        header("Location: /login?return_url={$returnUrl}");
        exit;
    }

    /**
     * Access denied response
     */
    private function accessDenied(string $message = 'Access denied'): void
    {
        http_response_code(403);
        echo json_encode([
            'error' => 'access_denied',
            'message' => $message
        ]);
        exit;
    }

    /**
     * Get current user
     */
    public function getCurrentUser(): ?array
    {
        return $this->auth->getCurrentUser();
    }

    /**
     * Validate current session
     */
    public function validateSession(): array
    {
        return $this->auth->validateSession();
    }
}

// =========================================
// USAGE EXAMPLES
// =========================================

/*
1. Basic Authentication:
   $auth = new TenantAwareAuth();
   $result = $auth->authenticate('username', 'password', 'tenant-slug');

2. Route Protection:
   $authMiddleware = new AuthMiddleware();
   $authMiddleware->requireAuth();
   $authMiddleware->requirePermission('loan_management');
   $authMiddleware->requireRole('admin');

3. Session Validation:
   $sessionValid = $auth->validateSession();
   if (!$sessionValid['valid']) {
       // Handle invalid session
   }

4. Permission Checking:
   if ($auth->hasPermission('loan_approval')) {
       // Show approval features
   }

5. CSRF Protection:
   $csrfToken = $auth->getCsrfToken();
   // Include in forms: <input type="hidden" name="_token" value="<?php echo $csrfToken; ?>">

   // Validate in controllers:
   if (!$auth->validateCsrfToken($_POST['_token'])) {
       die('CSRF token validation failed');
   }
*/

?>
