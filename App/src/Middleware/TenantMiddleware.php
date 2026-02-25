<?php
namespace App\Middleware;

use App\Models\Tenant;

class TenantMiddleware
{
    private Tenant $tenantModel;

    public function __construct()
    {
        $this->tenantModel = new Tenant();
    }

    /**
     * Handle tenant routing and database switching
     */
    public function handle(): void
    {
        $host = $_SERVER['HTTP_HOST'] ?? '';
        $uri = $_SERVER['REQUEST_URI'] ?? '';

        // Check if this is a tenant subdomain request
        $tenantSlug = $this->extractTenantSlug($host);

        if ($tenantSlug) {
            $this->switchToTenantDatabase($tenantSlug);
            $this->setTenantContext($tenantSlug);
        } else {
            // Main application - no tenant context
            $this->clearTenantContext();
        }
    }

    /**
     * Extract tenant slug from hostname
     * Supports formats like:
     * - tenant1.app-domain.com
     * - tenant1.localhost (for development)
     */
    private function extractTenantSlug(string $host): ?string
    {
        $mainDomain = $_ENV['MAIN_DOMAIN'] ?? 'localhost';

        // Remove port if present
        $host = explode(':', $host)[0];

        // If host is exactly the main domain, no tenant
        if ($host === $mainDomain) {
            return null;
        }

        // Check for subdomain pattern
        if (strpos($host, '.' . $mainDomain) !== false) {
            $subdomain = str_replace('.' . $mainDomain, '', $host);
            return $subdomain;
        }

        // For localhost development, check for tenant- prefix
        if (strpos($host, 'tenant-') === 0) {
            return str_replace('tenant-', '', $host);
        }

        return null;
    }

    /**
     * Switch database connection to tenant database
     */
    private function switchToTenantDatabase(string $slug): void
    {
        try {
            $tenant = $this->tenantModel->findBySlug($slug);

            if (!$tenant) {
                $this->handleInvalidTenant($slug);
                return;
            }

            // Check if tenant is active
            if ($tenant['status'] !== 'active') {
                $this->handleInactiveTenant($tenant);
                return;
            }

            // Check subscription status
            if ($this->isSubscriptionExpired($tenant)) {
                $this->handleExpiredSubscription($tenant);
                return;
            }

            // Switch to tenant database
            $tenantDb = $this->tenantModel->getTenantDatabase($slug);

            // Store tenant database connection in global scope
            $GLOBALS['tenant_db'] = $tenantDb;
            $GLOBALS['tenant_info'] = $tenant;

        } catch (\Exception $e) {
            error_log("Tenant database switch error: " . $e->getMessage());
            $this->handleTenantError();
        }
    }

    /**
     * Set tenant context in session/global
     */
    private function setTenantContext(string $slug): void
    {
        $_SESSION['tenant_slug'] = $slug;
        $GLOBALS['current_tenant'] = $slug;
    }

    /**
     * Clear tenant context
     */
    private function clearTenantContext(): void
    {
        unset($_SESSION['tenant_slug']);
        unset($GLOBALS['current_tenant']);
        unset($GLOBALS['tenant_db']);
        unset($GLOBALS['tenant_info']);
    }

    /**
     * Check if tenant subscription is expired
     */
    private function isSubscriptionExpired(array $tenant): bool
    {
        if (!$tenant['subscription_ends_at']) {
            return false; // No subscription end date set
        }

        $now = new \DateTime();
        $endDate = new \DateTime($tenant['subscription_ends_at']);

        return $now > $endDate;
    }

    /**
     * Handle invalid tenant
     */
    private function handleInvalidTenant(string $slug): void
    {
        http_response_code(404);
        echo json_encode([
            'error' => 'Tenant not found',
            'message' => "Koperasi '{$slug}' tidak ditemukan"
        ]);
    }

    /**
     * Handle inactive tenant
     */
    private function handleInactiveTenant(array $tenant): void
    {
        http_response_code(403);
        echo json_encode([
            'error' => 'Tenant inactive',
            'message' => 'Koperasi sedang tidak aktif'
        ]);
    }

    /**
     * Handle expired subscription
     */
    private function handleExpiredSubscription(array $tenant): void
    {
        http_response_code(402); // Payment required
        echo json_encode([
            'error' => 'Subscription expired',
            'message' => 'Langganan koperasi telah berakhir. Silakan perpanjang langganan.',
            'tenant' => $tenant['name']
        ]);
    }

    /**
     * Handle tenant error
     */
    private function handleTenantError(): void
    {
        http_response_code(500);
        echo json_encode([
            'error' => 'Tenant error',
            'message' => 'Terjadi kesalahan sistem'
        ]);
    }

    /**
     * Get current tenant database connection
     */
    public static function getTenantDb(): ?\PDO
    {
        return $GLOBALS['tenant_db'] ?? null;
    }

    /**
     * Get current tenant info
     */
    public static function getTenantInfo(): ?array
    {
        return $GLOBALS['tenant_info'] ?? null;
    }

    /**
     * Check if we're in tenant context
     */
    public static function hasTenant(): bool
    {
        return isset($GLOBALS['current_tenant']);
    }

    /**
     * Get current tenant slug
     */
    public static function getTenantSlug(): ?string
    {
        return $GLOBALS['current_tenant'] ?? null;
    }
}
