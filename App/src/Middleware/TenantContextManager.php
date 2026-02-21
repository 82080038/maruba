<?php
namespace App\Middleware;

use App\Security\TenantAwareAuth;
use App\Database\Security\RLSEnforcementMiddleware;

/**
 * Enhanced Tenant Context Manager
 *
 * Manages tenant context throughout the application lifecycle
 * Provides secure session handling and context switching
 */
class TenantContextManager
{
    private TenantAwareAuth $auth;
    private array $context = [];
    private array $securityFlags = [];

    public function __construct()
    {
        $this->auth = new TenantAwareAuth();
    }

    /**
     * Initialize tenant context for current request
     */
    public function initializeContext(): void
    {
        // Validate session first
        $sessionValidation = $this->auth->validateSession();

        if (!$sessionValidation['valid']) {
            $this->clearContext();
            return;
        }

        $user = $this->auth->getCurrentUser();

        // Set basic context
        $this->context = [
            'user_id' => $user['id'],
            'username' => $user['username'],
            'role' => $user['role'],
            'session_id' => session_id(),
            'request_id' => $this->generateRequestId(),
            'timestamp' => time(),
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
        ];

        // Set tenant context if applicable
        if ($user['tenant_id'] !== null) {
            $this->setTenantContext($user['tenant_id']);
        } else {
            // System admin context
            $this->setSystemContext();
        }

        // Set security flags
        $this->setSecurityFlags();

        // Initialize RLS context
        $this->initializeRLS();

        // Log context initialization
        $this->logContextAccess('initialized');
    }

    /**
     * Set tenant-specific context
     */
    private function setTenantContext(int $tenantId): void
    {
        // Get tenant information
        $tenantInfo = $this->getTenantInfo($tenantId);

        if (!$tenantInfo) {
            throw new \Exception("Invalid tenant context: {$tenantId}");
        }

        $this->context['tenant'] = [
            'id' => $tenantId,
            'name' => $tenantInfo['name'],
            'slug' => $tenantInfo['slug'],
            'status' => $tenantInfo['status'],
            'subscription_plan' => $tenantInfo['subscription_plan'],
            'subscription_ends_at' => $tenantInfo['subscription_ends_at']
        ];

        // Validate tenant access
        $this->validateTenantAccess($tenantInfo);

        // Set tenant-specific permissions
        $this->context['permissions'] = $this->auth->getUserPermissions($this->auth->getCurrentUser());

        // Set tenant database context
        $this->initializeTenantDatabase();
    }

    /**
     * Set system administrator context
     */
    private function setSystemContext(): void
    {
        $this->context['system'] = [
            'access_level' => 'super_admin',
            'can_manage_tenants' => true,
            'can_manage_system' => true,
            'can_view_all_data' => true
        ];

        $this->context['permissions'] = [
            'system_access' => true,
            'tenant_management' => true,
            'user_management' => true,
            'billing_management' => true,
            'system_configuration' => true,
            'audit_logs' => true
        ];
    }

    /**
     * Set security flags for the context
     */
    private function setSecurityFlags(): void
    {
        $user = $this->auth->getCurrentUser();

        $this->securityFlags = [
            'session_secure' => $this->isSessionSecure(),
            'ip_consistent' => $this->isIPConsistent(),
            'recent_login' => $this->isRecentLogin(),
            'password_fresh' => !$this->auth->requiresPasswordChange($user),
            'tenant_active' => isset($this->context['tenant']) ? $this->isTenantActive() : true,
            'subscription_valid' => isset($this->context['tenant']) ? $this->isSubscriptionValid() : true
        ];

        $this->context['security_flags'] = $this->securityFlags;
    }

    /**
     * Initialize RLS context for database security
     */
    private function initializeRLS(): void
    {
        if (isset($this->context['tenant'])) {
            $rlsMiddleware = new RLSEnforcementMiddleware(\App\Database::getTenantConnection());
            $rlsMiddleware->handle();
        }
    }

    /**
     * Initialize tenant database connection
     */
    private function initializeTenantDatabase(): void
    {
        if (!isset($this->context['tenant'])) {
            return;
        }

        try {
            $tenantSlug = $this->context['tenant']['slug'];
            $tenantDb = \App\Models\Tenant::getTenantDatabase($tenantSlug);

            if ($tenantDb) {
                // Store tenant database in context
                $this->context['database'] = [
                    'connection' => $tenantDb,
                    'type' => 'tenant_isolated'
                ];

                // Set global tenant context for other components
                $GLOBALS['tenant_db'] = $tenantDb;
                $GLOBALS['tenant_info'] = $this->context['tenant'];
            }
        } catch (\Exception $e) {
            error_log("Failed to initialize tenant database: " . $e->getMessage());
            throw new \Exception("Tenant database initialization failed");
        }
    }

    /**
     * Get current context
     */
    public function getContext(): array
    {
        return $this->context;
    }

    /**
     * Get current tenant context
     */
    public function getTenantContext(): ?array
    {
        return $this->context['tenant'] ?? null;
    }

    /**
     * Get current user permissions
     */
    public function getPermissions(): array
    {
        return $this->context['permissions'] ?? [];
    }

    /**
     * Check if user has permission
     */
    public function hasPermission(string $permission): bool
    {
        return in_array($permission, $this->context['permissions'] ?? []);
    }

    /**
     * Check if context is secure
     */
    public function isContextSecure(): bool
    {
        // Check all security flags
        foreach ($this->securityFlags as $flag => $value) {
            if (!$value) {
                return false;
            }
        }

        return true;
    }

    /**
     * Switch tenant context (for system admins)
     */
    public function switchTenantContext(int $tenantId): bool
    {
        // Only allow for system administrators
        if (!isset($this->context['system']) || !$this->context['system']['can_manage_tenants']) {
            return false;
        }

        try {
            $this->clearTenantContext();
            $this->setTenantContext($tenantId);
            $this->logContextAccess('switched', $tenantId);

            return true;
        } catch (\Exception $e) {
            error_log("Failed to switch tenant context: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Clear tenant context
     */
    public function clearTenantContext(): void
    {
        if (isset($this->context['tenant'])) {
            unset($this->context['tenant']);
        }

        if (isset($this->context['database'])) {
            unset($this->context['database']);
        }

        if (isset($GLOBALS['tenant_db'])) {
            unset($GLOBALS['tenant_db']);
        }

        if (isset($GLOBALS['tenant_info'])) {
            unset($GLOBALS['tenant_info']);
        }

        $this->logContextAccess('cleared');
    }

    /**
     * Get tenant information
     */
    private function getTenantInfo(int $tenantId): ?array
    {
        $tenantModel = new \App\Models\Tenant();
        return $tenantModel->find($tenantId);
    }

    /**
     * Validate tenant access
     */
    private function validateTenantAccess(array $tenantInfo): void
    {
        // Check if tenant is active
        if ($tenantInfo['status'] !== 'active') {
            throw new \Exception("Tenant is not active: {$tenantInfo['status']}");
        }

        // Check subscription status
        if ($this->isSubscriptionExpired($tenantInfo)) {
            throw new \Exception("Tenant subscription has expired");
        }

        // Check user access to tenant
        $user = $this->auth->getCurrentUser();
        if ($user['tenant_id'] !== null && $user['tenant_id'] !== $tenantInfo['id']) {
            // Regular user trying to access different tenant
            throw new \Exception("Access denied to tenant: {$tenantInfo['id']}");
        }
    }

    /**
     * Check if session is secure
     */
    private function isSessionSecure(): bool
    {
        // Check session age (max 24 hours)
        $sessionAge = time() - ($_SESSION['user']['login_time'] ?? 0);
        if ($sessionAge > 86400) {
            return false;
        }

        // Check for suspicious patterns
        $loginAttempts = $_SESSION['security']['login_attempts'] ?? 0;
        if ($loginAttempts > 5) {
            return false;
        }

        return true;
    }

    /**
     * Check IP consistency
     */
    private function isIPConsistent(): bool
    {
        $sessionIP = $_SESSION['user']['ip_address'] ?? '';
        $currentIP = $_SERVER['REMOTE_ADDR'] ?? '';

        // Allow for proxy/load balancer scenarios
        if (empty($sessionIP) || empty($currentIP)) {
            return true;
        }

        return $sessionIP === $currentIP;
    }

    /**
     * Check if login is recent
     */
    private function isRecentLogin(): bool
    {
        $loginTime = $_SESSION['user']['login_time'] ?? 0;
        $timeSinceLogin = time() - $loginTime;

        // Consider login recent if less than 30 days
        return $timeSinceLogin < (30 * 24 * 60 * 60);
    }

    /**
     * Check if tenant is active
     */
    private function isTenantActive(): bool
    {
        return ($this->context['tenant']['status'] ?? '') === 'active';
    }

    /**
     * Check if subscription is valid
     */
    private function isSubscriptionValid(): bool
    {
        $endDate = $this->context['tenant']['subscription_ends_at'] ?? null;

        if (!$endDate) {
            return true; // No end date set
        }

        $endTime = strtotime($endDate);
        return $endTime > time();
    }

    /**
     * Check if subscription is expired
     */
    private function isSubscriptionExpired(array $tenantInfo): bool
    {
        $endDate = $tenantInfo['subscription_ends_at'] ?? null;

        if (!$endDate) {
            return false;
        }

        $now = new \DateTime();
        $endDateTime = new \DateTime($endDate);

        return $now > $endDateTime;
    }

    /**
     * Generate unique request ID
     */
    private function generateRequestId(): string
    {
        return sprintf(
            '%s-%s-%s',
            time(),
            session_id(),
            bin2hex(random_bytes(4))
        );
    }

    /**
     * Log context access for auditing
     */
    private function logContextAccess(string $action, ?int $targetTenantId = null): void
    {
        $user = $this->auth->getCurrentUser();

        $logData = [
            'timestamp' => date('Y-m-d H:i:s'),
            'user_id' => $user['id'] ?? null,
            'username' => $user['username'] ?? 'unknown',
            'action' => $action,
            'request_id' => $this->context['request_id'] ?? null,
            'tenant_id' => $this->context['tenant']['id'] ?? null,
            'target_tenant_id' => $targetTenantId,
            'ip_address' => $this->context['ip_address'] ?? 'unknown',
            'user_agent' => $this->context['user_agent'] ?? 'unknown',
            'security_flags' => $this->securityFlags
        ];

        error_log("TENANT_CONTEXT: " . json_encode($logData));
    }

    /**
     * Get context summary for debugging
     */
    public function getContextSummary(): array
    {
        return [
            'user' => [
                'id' => $this->context['user_id'] ?? null,
                'username' => $this->context['username'] ?? null,
                'role' => $this->context['role'] ?? null
            ],
            'tenant' => isset($this->context['tenant']) ? [
                'id' => $this->context['tenant']['id'],
                'name' => $this->context['tenant']['name'],
                'slug' => $this->context['tenant']['slug'],
                'status' => $this->context['tenant']['status']
            ] : null,
            'system' => $this->context['system'] ?? null,
            'security' => $this->securityFlags,
            'permissions_count' => count($this->context['permissions'] ?? []),
            'database_connected' => isset($this->context['database']),
            'request_id' => $this->context['request_id'] ?? null
        ];
    }
}

/**
 * Tenant Context Middleware
 * Integrates with application request lifecycle
 */
class TenantContextMiddleware
{
    private TenantContextManager $contextManager;

    public function __construct()
    {
        $this->contextManager = new TenantContextManager();
    }

    /**
     * Handle tenant context for current request
     */
    public function handle(): void
    {
        try {
            $this->contextManager->initializeContext();

            // Store context in global scope for easy access
            $GLOBALS['app_context'] = $this->contextManager->getContext();
            $GLOBALS['context_manager'] = $this->contextManager;

        } catch (\Exception $e) {
            error_log("Tenant context initialization failed: " . $e->getMessage());

            // Clear any partial context
            $this->clearContext();

            // Redirect to error page or login
            if (!headers_sent()) {
                header('Location: /login?error=context_failed');
                exit;
            }
        }
    }

    /**
     * Get current context
     */
    public function getContext(): array
    {
        return $this->contextManager->getContext();
    }

    /**
     * Check if context is secure
     */
    public function isContextSecure(): bool
    {
        return $this->contextManager->isContextSecure();
    }

    /**
     * Clear context
     */
    private function clearContext(): void
    {
        unset($GLOBALS['app_context']);
        unset($GLOBALS['context_manager']);
        unset($GLOBALS['tenant_db']);
        unset($GLOBALS['tenant_info']);
    }

    /**
     * Get context summary
     */
    public function getContextSummary(): array
    {
        return $this->contextManager->getContextSummary();
    }
}

// =========================================
// GLOBAL HELPER FUNCTIONS
// =========================================

/**
 * Get current application context
 */
function get_app_context(): array
{
    return $GLOBALS['app_context'] ?? [];
}

/**
 * Get current tenant context
 */
function get_tenant_context(): ?array
{
    $context = get_app_context();
    return $context['tenant'] ?? null;
}

/**
 * Check if user has permission
 */
function has_permission(string $permission): bool
{
    if (!isset($GLOBALS['context_manager'])) {
        return false;
    }

    return $GLOBALS['context_manager']->hasPermission($permission);
}

/**
 * Get current user from context
 */
function get_current_user(): ?array
{
    $context = get_app_context();
    return $context ? [
        'id' => $context['user_id'] ?? null,
        'username' => $context['username'] ?? null,
        'role' => $context['role'] ?? null
    ] : null;
}

/**
 * Check if context is secure
 */
function is_context_secure(): bool
{
    if (!isset($GLOBALS['context_manager'])) {
        return false;
    }

    return $GLOBALS['context_manager']->isContextSecure();
}
