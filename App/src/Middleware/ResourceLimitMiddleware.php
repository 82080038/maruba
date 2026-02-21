<?php
namespace App\Middleware;

use App\Models\Tenant;
use App\Models\TenantFeatureUsage;

class ResourceLimitMiddleware
{
    private Tenant $tenantModel;
    private TenantFeatureUsage $usageModel;

    public function __construct()
    {
        $this->tenantModel = new Tenant();
        $this->usageModel = new TenantFeatureUsage();
    }

    /**
     * Check and enforce resource limits for user creation
     */
    public function checkUserLimit(): bool
    {
        $currentTenant = $this->getCurrentTenant();
        if (!$currentTenant) {
            return false; // No tenant context
        }

        $access = $this->tenantModel->checkFeatureAccess($currentTenant['id'], 'users');
        if (!$access['allowed']) {
            $this->sendLimitExceededNotification($currentTenant, 'users', $access);
            return false;
        }

        // Track usage
        $this->tenantModel->trackFeatureUsage($currentTenant['id'], 'users', 1);
        return true;
    }

    /**
     * Check and enforce resource limits for member creation
     */
    public function checkMemberLimit(): bool
    {
        $currentTenant = $this->getCurrentTenant();
        if (!$currentTenant) {
            return false; // No tenant context
        }

        $access = $this->tenantModel->checkFeatureAccess($currentTenant['id'], 'members');
        if (!$access['allowed']) {
            $this->sendLimitExceededNotification($currentTenant, 'members', $access);
            return false;
        }

        // Track usage
        $this->tenantModel->trackFeatureUsage($currentTenant['id'], 'members', 1);
        return true;
    }

    /**
     * Check and enforce resource limits for storage usage
     */
    public function checkStorageLimit(int $fileSizeBytes): bool
    {
        $currentTenant = $this->getCurrentTenant();
        if (!$currentTenant) {
            return false; // No tenant context
        }

        $fileSizeGB = $fileSizeBytes / (1024 * 1024 * 1024); // Convert to GB

        $access = $this->tenantModel->checkFeatureAccess($currentTenant['id'], 'storage_gb');
        if (!$access['allowed']) {
            $this->sendLimitExceededNotification($currentTenant, 'storage_gb', $access);
            return false;
        }

        // Track storage usage (accumulate)
        $this->tenantModel->trackFeatureUsage($currentTenant['id'], 'storage_gb', $fileSizeGB);
        return true;
    }

    /**
     * Check and enforce API call limits
     */
    public function checkApiLimit(): bool
    {
        $currentTenant = $this->getCurrentTenant();
        if (!$currentTenant) {
            return false; // No tenant context
        }

        $access = $this->tenantModel->checkFeatureAccess($currentTenant['id'], 'api_calls');
        if (!$access['allowed']) {
            $this->sendLimitExceededNotification($currentTenant, 'api_calls', $access);
            return false;
        }

        // Track API usage
        $this->tenantModel->trackFeatureUsage($currentTenant['id'], 'api_calls', 1);
        return true;
    }

    /**
     * Check and enforce report generation limits
     */
    public function checkReportLimit(): bool
    {
        $currentTenant = $this->getCurrentTenant();
        if (!$currentTenant) {
            return false; // No tenant context
        }

        $access = $this->tenantModel->checkFeatureAccess($currentTenant['id'], 'reports');
        if (!$access['allowed']) {
            $this->sendLimitExceededNotification($currentTenant, 'reports', $access);
            return false;
        }

        // Track report usage
        $this->tenantModel->trackFeatureUsage($currentTenant['id'], 'reports', 1);
        return true;
    }

    /**
     * Get current tenant resource usage summary
     */
    public function getResourceUsage(): array
    {
        $currentTenant = $this->getCurrentTenant();
        if (!$currentTenant) {
            return ['error' => 'No tenant context'];
        }

        return $this->tenantModel->getUsageSummary($currentTenant['id']);
    }

    /**
     * Send notification when limit is exceeded
     */
    private function sendLimitExceededNotification(array $tenant, string $resource, array $access): void
    {
        // Send notification to tenant admin
        $message = "Peringatan: Batas penggunaan {$resource} telah tercapai.\n";
        $message .= "Penggunaan saat ini: {$access['current_usage']}\n";
        $message .= "Batas maksimal: {$access['limit']}\n";
        $message .= "Silakan upgrade paket berlangganan Anda.";

        \App\Helpers\Notification::send(
            'email',
            [
                'email' => $tenant['email'] ?? 'admin@' . APP_NAME . '.id',
                'name' => $tenant['name'] ?? 'Admin'
            ],
            'Batas Penggunaan Tercapai',
            $message
        );

        // Log the limit exceedance
        error_log("Resource limit exceeded for tenant {$tenant['id']}: {$resource} - Usage: {$access['current_usage']}, Limit: {$access['limit']}");
    }

    /**
     * Get current tenant from session or subdomain
     */
    private function getCurrentTenant(): ?array
    {
        // Check if we're in tenant context via middleware
        if (isset($_SESSION['tenant'])) {
            return $_SESSION['tenant'];
        }

        // Try to get tenant from subdomain
        $host = $_SERVER['HTTP_HOST'] ?? '';
        if (preg_match('/^([a-z0-9-]+)\.' . preg_quote($_SERVER['SERVER_NAME'] ?? 'localhost', '/') . '$/', $host, $matches)) {
            $slug = $matches[1];
            $tenant = $this->tenantModel->findBySlug($slug);
            if ($tenant && $tenant['status'] === 'active') {
                $_SESSION['tenant'] = $tenant;
                return $tenant;
            }
        }

        return null;
    }

    /**
     * Clean up expired usage records (should be called by cron job)
     */
    public function cleanupExpiredRecords(): int
    {
        return $this->usageModel->resetExpiredPeriods();
    }
}
