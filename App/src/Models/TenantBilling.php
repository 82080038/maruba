<?php
namespace App\Models;

class TenantBilling extends Model
{
    protected string $table = 'tenant_billings';
    protected array $fillable = [
        'tenant_id', 'amount', 'currency', 'billing_period_start',
        'billing_period_end', 'status', 'payment_method', 'payment_date', 'notes'
    ];
    protected array $casts = [
        'tenant_id' => 'int',
        'amount' => 'float',
        'billing_period_start' => 'datetime',
        'billing_period_end' => 'datetime',
        'payment_date' => 'datetime',
        'created_at' => 'datetime'
    ];

    /**
     * Generate billing for a tenant
     */
    public function generateBilling(int $tenantId, string $billingPeriodStart, string $billingPeriodEnd): int
    {
        $tenantModel = new Tenant();
        $tenant = $tenantModel->find($tenantId);

        if (!$tenant) {
            throw new \Exception('Tenant not found');
        }

        // Calculate billing amount based on tenant plan and usage
        $amount = $this->calculateBillingAmount($tenant, $billingPeriodStart, $billingPeriodEnd);

        $billingData = [
            'tenant_id' => $tenantId,
            'amount' => $amount,
            'currency' => 'IDR',
            'billing_period_start' => $billingPeriodStart,
            'billing_period_end' => $billingPeriodEnd,
            'status' => 'pending',
            'notes' => 'Auto-generated billing for ' . date('M Y', strtotime($billingPeriodStart))
        ];

        return $this->create($billingData);
    }

    /**
     * Calculate billing amount based on plan and usage
     */
    private function calculateBillingAmount(array $tenant, string $startDate, string $endDate): float
    {
        $plan = $tenant['subscription_plan'] ?? 'starter';
        $baseAmount = $this->getPlanBaseAmount($plan);

        // Add usage-based charges
        $usageCharges = $this->calculateUsageCharges($tenant, $startDate, $endDate);

        return $baseAmount + $usageCharges;
    }

    /**
     * Get base amount for subscription plan
     */
    private function getPlanBaseAmount(string $plan): float
    {
        $plans = [
            'starter' => 500000,      // Rp 500.000/month
            'professional' => 1500000, // Rp 1.500.000/month
            'enterprise' => 3000000   // Rp 3.000.000/month
        ];

        return $plans[$plan] ?? 500000;
    }

    /**
     * Calculate usage-based charges
     */
    private function calculateUsageCharges(array $tenant, string $startDate, string $endDate): float
    {
        $totalCharges = 0.0;

        try {
            // Get tenant database connection
            $tenantModel = new Tenant();
            $tenantDb = $tenantModel->getTenantDatabase($tenant['slug']);

            // Calculate member charges (above included limit)
            $memberCharges = $this->calculateMemberCharges($tenantDb, $tenant);
            $totalCharges += $memberCharges;

            // Calculate transaction charges (above included limit)
            $transactionCharges = $this->calculateTransactionCharges($tenantDb, $tenant, $startDate, $endDate);
            $totalCharges += $transactionCharges;

            // Calculate storage charges (above included limit)
            $storageCharges = $this->calculateStorageCharges($tenantDb, $tenant);
            $totalCharges += $storageCharges;

        } catch (\Exception $e) {
            // If tenant database is not accessible, use default charges
            error_log('Billing calculation error: ' . $e->getMessage());
        }

        return $totalCharges;
    }

    /**
     * Calculate member charges (above plan limit)
     */
    private function calculateMemberCharges(\PDO $tenantDb, array $tenant): float
    {
        // Get current member count
        $stmt = $tenantDb->prepare("SELECT COUNT(*) as count FROM members WHERE status = 'active'");
        $stmt->execute();
        $memberCount = (int)$stmt->fetch()['count'];

        $includedMembers = $this->getIncludedMembers($tenant['subscription_plan']);
        $extraMembers = max(0, $memberCount - $includedMembers);

        return $extraMembers * 2000; // Rp 2.000 per extra member
    }

    /**
     * Calculate transaction charges (above plan limit)
     */
    private function calculateTransactionCharges(\PDO $tenantDb, array $tenant, string $startDate, string $endDate): float
    {
        // Count transactions in billing period
        $stmt = $tenantDb->prepare("
            SELECT COUNT(*) as count FROM audit_logs
            WHERE created_at BETWEEN ? AND ?
            AND action IN ('record_repayment', 'create_loan', 'update_loan_status')
        ");
        $stmt->execute([$startDate, $endDate]);
        $transactionCount = (int)$stmt->fetch()['count'];

        $includedTransactions = $this->getIncludedTransactions($tenant['subscription_plan']);
        $extraTransactions = max(0, $transactionCount - $includedTransactions);

        return $extraTransactions * 100; // Rp 100 per extra transaction
    }

    /**
     * Calculate storage charges (above plan limit)
     */
    private function calculateStorageCharges(\PDO $tenantDb, array $tenant): float
    {
        // Estimate storage usage (simplified)
        // In real implementation, this would calculate actual file storage used
        $estimatedGb = 1.0; // Assume 1GB base usage

        $includedGb = $this->getIncludedStorageGb($tenant['subscription_plan']);
        $extraGb = max(0, $estimatedGb - $includedGb);

        return $extraGb * 100000; // Rp 100.000 per extra GB
    }

    /**
     * Get included members for plan
     */
    private function getIncludedMembers(string $plan): int
    {
        $limits = [
            'starter' => 100,
            'professional' => 500,
            'enterprise' => 1000 // Unlimited in enterprise, but set high limit
        ];

        return $limits[$plan] ?? 100;
    }

    /**
     * Get included transactions for plan
     */
    private function getIncludedTransactions(string $plan): int
    {
        $limits = [
            'starter' => 50,
            'professional' => 500,
            'enterprise' => 2000 // Unlimited in enterprise, but set high limit
        ];

        return $limits[$plan] ?? 50;
    }

    /**
     * Get included storage GB for plan
     */
    private function getIncludedStorageGb(string $plan): float
    {
        $limits = [
            'starter' => 1.0,
            'professional' => 5.0,
            'enterprise' => 20.0
        ];

        return $limits[$plan] ?? 1.0;
    }

    /**
     * Record payment for billing
     */
    public function recordPayment(int $billingId, array $paymentData): bool
    {
        $billing = $this->find($billingId);
        if (!$billing) {
            return false;
        }

        $updateData = [
            'status' => 'paid',
            'payment_method' => $paymentData['payment_method'] ?? 'transfer',
            'payment_date' => $paymentData['payment_date'] ?? date('Y-m-d H:i:s'),
            'notes' => ($billing['notes'] ?? '') . ' - Paid: ' . ($paymentData['notes'] ?? '')
        ];

        return $this->update($billingId, $updateData);
    }

    /**
     * Get billings for tenant
     */
    public function getByTenant(int $tenantId): array
    {
        return $this->findWhere(['tenant_id' => $tenantId], ['billing_period_end' => 'DESC']);
    }

    /**
     * Get pending billings
     */
    public function getPendingBillings(): array
    {
        $stmt = $this->db->prepare("
            SELECT b.*, t.name as tenant_name, t.slug as tenant_slug
            FROM {$this->table} b
            JOIN tenants t ON b.tenant_id = t.id
            WHERE b.status = 'pending'
            ORDER BY b.billing_period_end DESC
        ");
        $stmt->execute();

        $results = $stmt->fetchAll();
        return array_map([$this, 'castAttributes'], $results);
    }

    /**
     * Get overdue billings
     */
    public function getOverdueBillings(): array
    {
        $stmt = $this->db->prepare("
            SELECT b.*, t.name as tenant_name, t.slug as tenant_slug
            FROM {$this->table} b
            JOIN tenants t ON b.tenant_id = t.id
            WHERE b.status = 'pending'
            AND b.billing_period_end < CURDATE()
            ORDER BY b.billing_period_end ASC
        ");
        $stmt->execute();

        $results = $stmt->fetchAll();
        return array_map([$this, 'castAttributes'], $results);
    }

    /**
     * Generate monthly billings for all active tenants
     */
    public function generateMonthlyBillings(): array
    {
        $tenantModel = new Tenant();
        $activeTenants = $tenantModel->getActiveTenants();

        $generated = [];
        $currentMonth = date('Y-m-01');
        $nextMonth = date('Y-m-01', strtotime('+1 month'));

        foreach ($activeTenants as $tenant) {
            try {
                // Check if billing already exists for this period
                $existing = $this->findWhere([
                    'tenant_id' => $tenant['id'],
                    'billing_period_start' => $currentMonth
                ]);

                if (empty($existing)) {
                    $billingId = $this->generateBilling($tenant['id'], $currentMonth, $nextMonth);
                    $generated[] = [
                        'tenant_id' => $tenant['id'],
                        'tenant_name' => $tenant['name'],
                        'billing_id' => $billingId
                    ];
                }
            } catch (\Exception $e) {
                error_log("Failed to generate billing for tenant {$tenant['id']}: " . $e->getMessage());
            }
        }

        return $generated;
    }

    /**
     * Calculate total revenue
     */
    public function getRevenueStats(): array
    {
        $stmt = $this->db->prepare("
            SELECT
                SUM(CASE WHEN status = 'paid' THEN amount ELSE 0 END) as paid_revenue,
                SUM(CASE WHEN status = 'pending' THEN amount ELSE 0 END) as pending_revenue,
                SUM(amount) as total_billed,
                COUNT(CASE WHEN status = 'paid' THEN 1 END) as paid_count,
                COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_count,
                COUNT(*) as total_count
            FROM {$this->table}
            WHERE billing_period_start >= DATE_FORMAT(CURDATE(), '%Y-%m-01')
        ");
        $stmt->execute();

        return $stmt->fetch();
    }
}
