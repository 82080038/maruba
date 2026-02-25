<?php
namespace App\Models;

class SubscriptionPlan extends Model
{
    protected string $table = 'subscription_plans';
    protected array $fillable = [
        'name', 'display_name', 'description', 'price_monthly', 'price_yearly',
        'max_users', 'max_members', 'max_storage_gb', 'features', 'is_active'
    ];
    protected array $casts = [
        'price_monthly' => 'float',
        'price_yearly' => 'float',
        'max_users' => 'int',
        'max_members' => 'int',
        'max_storage_gb' => 'int',
        'is_active' => 'bool',
        'features' => 'array',
        'created_at' => 'datetime'
    ];

    /**
     * Get active subscription plans
     */
    public function getActivePlans(): array
    {
        return $this->findWhere(['is_active' => true], ['price_monthly' => 'ASC']);
    }

    /**
     * Get plan by name
     */
    public function findByName(string $name): ?array
    {
        $plans = $this->findWhere(['name' => $name, 'is_active' => true]);
        return !empty($plans) ? $plans[0] : null;
    }

    /**
     * Check if plan has feature
     */
    public function hasFeature(string $planName, string $feature): bool
    {
        $plan = $this->findByName($planName);
        if (!$plan || empty($plan['features'])) {
            return false;
        }

        $features = json_decode($plan['features'], true);
        return isset($features[$feature]) && $features[$feature] === true;
    }

    /**
     * Get plan limits
     */
    public function getPlanLimits(string $planName): array
    {
        $plan = $this->findByName($planName);
        if (!$plan) {
            return [
                'max_users' => 0,
                'max_members' => 0,
                'max_storage_gb' => 0
            ];
        }

        return [
            'max_users' => $plan['max_users'] ?? 0,
            'max_members' => $plan['max_members'] ?? 0,
            'max_storage_gb' => $plan['max_storage_gb'] ?? 0
        ];
    }

    /**
     * Calculate price for plan and billing cycle
     */
    public function calculatePrice(string $planName, string $billingCycle = 'monthly'): float
    {
        $plan = $this->findByName($planName);
        if (!$plan) {
            return 0.0;
        }

        return $billingCycle === 'yearly' ? ($plan['price_yearly'] ?? 0) : ($plan['price_monthly'] ?? 0);
    }

    /**
     * Get plan comparison data
     */
    public function getPlanComparison(): array
    {
        $plans = $this->getActivePlans();
        $comparison = [];

        foreach ($plans as $plan) {
            $features = json_decode($plan['features'] ?? '{}', true);
            $comparison[$plan['name']] = [
                'display_name' => $plan['display_name'],
                'description' => $plan['description'],
                'price_monthly' => $plan['price_monthly'],
                'price_yearly' => $plan['price_yearly'],
                'max_users' => $plan['max_users'],
                'max_members' => $plan['max_members'],
                'max_storage_gb' => $plan['max_storage_gb'],
                'features' => $features
            ];
        }

        return $comparison;
    }
}
