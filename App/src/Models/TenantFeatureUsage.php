<?php
namespace App\Models;

class TenantFeatureUsage extends Model
{
    protected string $table = 'tenant_feature_usage';
    protected array $fillable = [
        'tenant_id', 'feature_name', 'usage_count', 'usage_limit',
        'period_start', 'period_end'
    ];
    protected array $casts = [
        'tenant_id' => 'int',
        'usage_count' => 'int',
        'usage_limit' => 'int',
        'period_start' => 'date',
        'period_end' => 'date',
        'created_at' => 'datetime'
    ];

    /**
     * Track feature usage
     */
    public function trackUsage(int $tenantId, string $featureName, int $increment = 1): bool
    {
        $currentPeriod = $this->getCurrentPeriod();

        // Find or create usage record
        $existing = $this->findWhere([
            'tenant_id' => $tenantId,
            'feature_name' => $featureName,
            'period_start' => $currentPeriod['start'],
            'period_end' => $currentPeriod['end']
        ]);

        if (!empty($existing)) {
            // Update existing record
            $newCount = $existing[0]['usage_count'] + $increment;
            return $this->update($existing[0]['id'], ['usage_count' => $newCount]);
        } else {
            // Create new record with plan limits
            $tenantModel = new Tenant();
            $tenant = $tenantModel->find($tenantId);

            $planLimits = [];
            if ($tenant && $tenant['subscription_plan']) {
                $planModel = new SubscriptionPlan();
                $planLimits = $planModel->getPlanLimits($tenant['subscription_plan']);
            }

            // Set feature-specific limits
            $limit = match($featureName) {
                'users' => $planLimits['max_users'] ?? 5,
                'members' => $planLimits['max_members'] ?? 100,
                'storage_gb' => $planLimits['max_storage_gb'] ?? 1,
                'api_calls' => 10000, // 10k API calls per month
                'reports' => 100, // 100 reports per month
                default => 0
            };

            return $this->create([
                'tenant_id' => $tenantId,
                'feature_name' => $featureName,
                'usage_count' => $increment,
                'usage_limit' => $limit,
                'period_start' => $currentPeriod['start'],
                'period_end' => $currentPeriod['end']
            ]);
        }
    }

    /**
     * Check if tenant can use feature
     */
    public function canUseFeature(int $tenantId, string $featureName, int $requiredAmount = 1): array
    {
        $tenantModel = new Tenant();
        $tenant = $tenantModel->find($tenantId);

        if (!$tenant || $tenant['status'] !== 'active') {
            return ['allowed' => false, 'reason' => 'Tenant inactive'];
        }

        // Check subscription status
        if ($this->isSubscriptionExpired($tenant)) {
            return ['allowed' => false, 'reason' => 'Subscription expired'];
        }

        // Check plan features
        $planModel = new SubscriptionPlan();
        if (!$planModel->hasFeature($tenant['subscription_plan'], $featureName)) {
            return ['allowed' => false, 'reason' => 'Feature not included in plan'];
        }

        // Check usage limits
        $currentPeriod = $this->getCurrentPeriod();
        $existing = $this->findWhere([
            'tenant_id' => $tenantId,
            'feature_name' => $featureName,
            'period_start' => $currentPeriod['start'],
            'period_end' => $currentPeriod['end']
        ]);

        if (!empty($existing)) {
            $usage = $existing[0];
            if (($usage['usage_count'] + $requiredAmount) > $usage['usage_limit']) {
                return [
                    'allowed' => false,
                    'reason' => 'Usage limit exceeded',
                    'current_usage' => $usage['usage_count'],
                    'limit' => $usage['usage_limit']
                ];
            }
        }

        return ['allowed' => true];
    }

    /**
     * Get tenant usage summary
     */
    public function getTenantUsageSummary(int $tenantId): array
    {
        $currentPeriod = $this->getCurrentPeriod();
        $usages = $this->findWhere([
            'tenant_id' => $tenantId,
            'period_start' => $currentPeriod['start'],
            'period_end' => $currentPeriod['end']
        ]);

        $summary = [];
        foreach ($usages as $usage) {
            $summary[$usage['feature_name']] = [
                'used' => $usage['usage_count'],
                'limit' => $usage['usage_limit'],
                'remaining' => max(0, $usage['usage_limit'] - $usage['usage_count']),
                'percentage' => $usage['usage_limit'] > 0 ? round(($usage['usage_count'] / $usage['usage_limit']) * 100, 1) : 0
            ];
        }

        return $summary;
    }

    /**
     * Get current billing period
     */
    private function getCurrentPeriod(): array
    {
        $now = new \DateTime();
        $start = new \DateTime($now->format('Y-m-01'));
        $end = new \DateTime($now->format('Y-m-t'));

        return [
            'start' => $start->format('Y-m-d'),
            'end' => $end->format('Y-m-d')
        ];
    }

    /**
     * Check if subscription is expired
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
     * Reset usage for new period (to be called by cron job)
     */
    public function resetExpiredPeriods(): int
    {
        $yesterday = date('Y-m-d', strtotime('-1 day'));

        $stmt = $this->db->prepare("
            DELETE FROM {$this->table}
            WHERE period_end < ?
        ");

        $stmt->execute([$yesterday]);
        return $stmt->rowCount();
    }

    /**
     * Get usage statistics
     */
    public function getUsageStatistics(): array
    {
        $stmt = $this->db->prepare("
            SELECT
                feature_name,
                COUNT(DISTINCT tenant_id) as tenants_using,
                SUM(usage_count) as total_usage,
                AVG(usage_count * 1.0 / NULLIF(usage_limit, 0)) * 100 as avg_usage_percentage
            FROM {$this->table}
            GROUP BY feature_name
            ORDER BY total_usage DESC
        ");
        $stmt->execute();

        return $stmt->fetchAll();
    }
}
