<?php
namespace App\Models;

class Tenant extends Model
{
    protected string $table = 'tenants';
    protected array $fillable = [
        'name', 'slug', 'description', 'logo_path', 'status',
        'board_members', 'registration_number', 'tax_id', 'business_license',
        'chairman_details', 'manager_details', 'secretary_details', 'treasurer_details',
        'address_details', 'operating_hours', 'social_media', 'last_profile_update',
        'profile_completion_percentage'
    ];
    protected array $casts = [
        'trial_ends_at' => 'datetime',
        'subscription_ends_at' => 'datetime',
        'max_members' => 'int',
        'max_users' => 'int',
        'created_at' => 'datetime'
    ];

    /**
     * Get tenant by slug
     */
    public function findBySlug(string $slug): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE slug = ?");
        $stmt->execute([$slug]);
        $result = $stmt->fetch();

        return $result ? $this->castAttributes($result) : null;
    }

    /**
     * Get active tenants
     */
    public function getActiveTenants(): array
    {
        return $this->findWhere(['status' => 'active'], ['name' => 'ASC']);
    }

    /**
     * Get tenant with statistics
     */
    public function findWithStats(int $id): ?array
    {
        $tenant = $this->find($id);
        if (!$tenant) return null;

        // Get member count from tenant database
        $tenantDb = $this->getTenantDatabase($tenant['slug']);
        $memberCount = $this->getTenantStat($tenantDb, 'members', 'COUNT(*)');
        $loanCount = $this->getTenantStat($tenantDb, 'loans', 'COUNT(*)');
        $userCount = $this->getTenantStat($tenantDb, 'users', 'COUNT(*)');

        $tenant['stats'] = [
            'members' => $memberCount,
            'loans' => $loanCount,
            'users' => $userCount
        ];

        return $tenant;
    }

    /**
     * Create tenant database and tables
     */
    public function createTenantDatabase(array $tenantData): int
    {
        // Create tenant record
        $tenantId = $this->create($tenantData);
        $tenant = $this->find($tenantId);

        // Create tenant database
        $this->createTenantSchema($tenant['slug']);

        return $tenantId;
    }

    /**
     * Create tenant database schema
     */
    private function createTenantSchema(string $slug): void
    {
        $tenantDbName = "tenant_{$slug}";
        $mainDb = Database::getConnection();

        // Create tenant database
        $mainDb->exec("CREATE DATABASE IF NOT EXISTS `{$tenantDbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

        // Switch to tenant database and create tables
        $tenantDb = $this->getTenantDatabase($slug);

        // Create tables for tenant (copy from main schema but without tenant table)
        $this->createTenantTables($tenantDb);
    }

    /**
     * Get tenant database connection
     */
    public function getTenantDatabase(string $slug): \PDO
    {
        $tenantDbName = "tenant_{$slug}";
        $host = $_ENV['DB_HOST'] ?? 'localhost';
        $port = $_ENV['DB_PORT'] ?? '3306';
        $user = $_ENV['DB_USER'] ?? 'root';
        $pass = $_ENV['DB_PASS'] ?? '';

        $dsn = "mysql:host=$host;port=$port;dbname=$tenantDbName;charset=utf8mb4";
        $options = [
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
            \PDO::ATTR_EMULATE_PREPARES => false,
        ];

        return new \PDO($dsn, $user, $pass, $options);
    }

    /**
     * Create tenant tables
     */
    private function createTenantTables(\PDO $db): void
    {
        // Users table (tenant-specific)
        $db->exec("
            CREATE TABLE users (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(100) NOT NULL,
                username VARCHAR(50) NOT NULL UNIQUE,
                password_hash VARCHAR(255) NOT NULL,
                role_id INT NOT NULL,
                status ENUM('active','inactive') DEFAULT 'active',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ");

        // Roles table (tenant-specific)
        $db->exec("
            CREATE TABLE roles (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(50) NOT NULL UNIQUE,
                permissions JSON NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ");

        // Add foreign key for users
        $db->exec("ALTER TABLE users ADD FOREIGN KEY (role_id) REFERENCES roles(id)");

        // Copy other tables from main schema (members, products, loans, etc.)
        $this->copyTablesToTenant($db);
    }

    /**
     * Copy tables to tenant database
     */
    private function copyTablesToTenant(\PDO $db): void
    {
        $tables = [
            'members', 'products', 'loans', 'surveys', 'repayments',
            'loan_docs', 'audit_logs'
        ];

        foreach ($tables as $table) {
            $sql = $this->getTableSchema($table);
            if ($sql) {
                $db->exec($sql);
            }
        }
    }

    /**
     * Get table schema from main database
     */
    private function getTableSchema(string $table): ?string
    {
        $mainDb = Database::getConnection();

        // Get CREATE TABLE statement
        $stmt = $mainDb->prepare("SHOW CREATE TABLE {$table}");
        $stmt->execute();
        $result = $stmt->fetch();

        return $result ? $result['Create Table'] : null;
    }

    /**
     * Get tenant statistic
     */
    private function getTenantStat(\PDO $db, string $table, string $query): int
    {
        try {
            $stmt = $db->prepare("SELECT {$query} as count FROM {$table}");
            $stmt->execute();
            $result = $stmt->fetch();
            return (int)($result['count'] ?? 0);
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Check tenant limits
     */
    public function checkLimits(string $slug, string $resource): bool
    {
        $tenant = $this->findBySlug($slug);
        if (!$tenant) return false;

        $tenantDb = $this->getTenantDatabase($slug);

        switch ($resource) {
            case 'members':
                $current = $this->getTenantStat($tenantDb, 'members', 'COUNT(*)');
                return $current < ($tenant['max_members'] ?? PHP_INT_MAX);

            case 'users':
                $current = $this->getTenantStat($tenantDb, 'users', 'COUNT(*)');
                return $current < ($tenant['max_users'] ?? PHP_INT_MAX);

            default:
                return true;
        }
    }

    /**
     * Get tenant billing info
     */
    public function getBillingInfo(int $id): ?array
    {
        $stmt = $this->db->prepare("
            SELECT t.*,
                   COUNT(b.id) as invoice_count,
                   COALESCE(SUM(b.amount), 0) as total_billed,
                   MAX(b.created_at) as last_invoice_date
            FROM {$this->table} t
            LEFT JOIN tenant_billings b ON t.id = b.tenant_id
            WHERE t.id = ?
            GROUP BY t.id
        ");
        $stmt->execute([$id]);
        $result = $stmt->fetch();

        return $result ? $this->castAttributes($result) : null;
    }

    /**
     * Update cooperative profile
     */
    public function updateProfile(int $tenantId, array $profileData): bool
    {
        $profileData['last_profile_update'] = date('Y-m-d H:i:s');
        $profileData['profile_completion_percentage'] = $this->calculateProfileCompletion($profileData);

        return $this->update($tenantId, $profileData);
    }

    /**
     * Calculate profile completion percentage
     */
    private function calculateProfileCompletion(array $profileData): int
    {
        $requiredFields = [
            'description', 'registration_number', 'tax_id', 'business_license',
            'chairman_details', 'manager_details', 'address_details'
        ];

        $completedFields = 0;
        foreach ($requiredFields as $field) {
            if (!empty($profileData[$field])) {
                $completedFields++;
            }
        }

        // Check for legal documents
        if (!empty($profileData['legal_documents'])) {
            $docs = json_decode($profileData['legal_documents'], true);
            if (is_array($docs) && count($docs) >= 3) { // At least 3 documents
                $completedFields++;
            }
        }

        // Check for board members
        if (!empty($profileData['board_members'])) {
            $board = json_decode($profileData['board_members'], true);
            if (is_array($board) && count($board) >= 3) { // Chairman, Manager, Secretary
                $completedFields++;
            }
        }

        return min(100, round(($completedFields / (count($requiredFields) + 2)) * 100));
    }

    /**
     * Upload legal document for tenant
     */
    public function uploadLegalDocument(int $tenantId, string $documentType, array $file): ?string
    {
        $tenant = $this->find($tenantId);
        if (!$tenant) {
            throw new \Exception('Tenant not found');
        }

        $uploadResult = \App\Helpers\FileUpload::upload($file, 'tenants/legal_docs/', [
            'allowed_types' => ['image/jpeg', 'image/png', 'application/pdf'],
            'max_size' => 10 * 1024 * 1024, // 10MB
            'prefix' => $tenant['slug'] . '_' . $documentType . '_'
        ]);

        if (!$uploadResult['success']) {
            throw new \Exception('Failed to upload document: ' . $uploadResult['error']);
        }

        // Update legal documents
        $legalDocs = $tenant['legal_documents'] ?? [];
        $legalDocs[$documentType] = $uploadResult['path'];

        $this->update($tenantId, ['legal_documents' => json_encode($legalDocs)]);

        return $uploadResult['path'];
    }

    /**
     * Get tenant profile completion status
     */
    public function getProfileCompletionStatus(int $tenantId): array
    {
        $tenant = $this->find($tenantId);
        if (!$tenant) {
            return ['error' => 'Tenant not found'];
        }

        $completionItems = [
            'description' => !empty($tenant['description']),
            'registration_number' => !empty($tenant['registration_number']),
            'tax_id' => !empty($tenant['tax_id']),
            'business_license' => !empty($tenant['business_license']),
            'chairman_details' => !empty($tenant['chairman_details']),
            'manager_details' => !empty($tenant['manager_details']),
            'secretary_details' => !empty($tenant['secretary_details']),
            'treasurer_details' => !empty($tenant['treasurer_details']),
            'address_details' => !empty($tenant['address_details']),
            'operating_hours' => !empty($tenant['operating_hours']),
            'legal_documents' => $this->hasRequiredLegalDocuments($tenant),
            'board_members' => $this->hasCompleteBoardMembers($tenant),
            'logo' => !empty($tenant['logo_path'])
        ];

        $completedCount = count(array_filter($completionItems));
        $totalCount = count($completionItems);
        $percentage = round(($completedCount / $totalCount) * 100);

        return [
            'percentage' => $percentage,
            'completed' => $completedCount,
            'total' => $totalCount,
            'items' => $completionItems
        ];
    }

    /**
     * Check if tenant has required legal documents
     */
    private function hasRequiredLegalDocuments(array $tenant): bool
    {
        $legalDocs = json_decode($tenant['legal_documents'] ?? '[]', true);
        $required = ['akta_pendirian', 'sk_menkumham', 'anggaran_dasar'];

        foreach ($required as $doc) {
            if (empty($legalDocs[$doc])) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if tenant has complete board members
     */
    private function hasCompleteBoardMembers(array $tenant): bool
    {
        $boardMembers = json_decode($tenant['board_members'] ?? '[]', true);
        $required = ['chairman', 'manager', 'secretary'];

        foreach ($required as $position) {
            if (empty($boardMembers[$position])) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get tenant activity summary
     */
    public function getActivitySummary(int $tenantId): array
    {
        $tenant = $this->find($tenantId);
        if (!$tenant) {
            return ['error' => 'Tenant not found'];
        }

        $tenantDb = $this->getTenantDatabase($tenant['slug']);

        try {
            // Get member count
            $stmt = $tenantDb->prepare("SELECT COUNT(*) as count FROM members WHERE status = 'active'");
            $stmt->execute();
            $memberCount = $stmt->fetch()['count'];

            // Get loan statistics
            $stmt = $tenantDb->prepare("SELECT COUNT(*) as count, SUM(amount) as total FROM loans WHERE status IN ('approved', 'disbursed')");
            $stmt->execute();
            $loanStats = $stmt->fetch();

            // Get savings statistics
            $stmt = $tenantDb->prepare("SELECT SUM(balance) as total FROM savings_accounts WHERE status = 'active'");
            $stmt->execute();
            $savingsTotal = $stmt->fetch()['total'];

            // Get recent transactions
            $stmt = $tenantDb->prepare("SELECT COUNT(*) as count FROM repayments WHERE paid_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
            $stmt->execute();
            $recentPayments = $stmt->fetch()['count'];

            return [
                'members' => $memberCount,
                'loans' => [
                    'count' => $loanStats['count'],
                    'total_amount' => $loanStats['total']
                ],
                'savings' => [
                    'total_balance' => $savingsTotal
                ],
                'recent_activity' => [
                    'payments_last_30_days' => $recentPayments
                ]
            ];

        } catch (\Exception $e) {
            return ['error' => 'Failed to get activity summary: ' . $e->getMessage()];
        }
    }

    /**
     * Update tenant contact information
     */
    public function updateContactInfo(int $tenantId, array $contactData): bool
    {
        return $this->update($tenantId, [
            'address_details' => json_encode($contactData),
            'last_profile_update' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Upgrade tenant subscription
     */
    public function upgradeSubscription(int $tenantId, string $newPlan, string $billingCycle = 'monthly'): bool
    {
        $tenant = $this->find($tenantId);
        if (!$tenant) {
            return false;
        }

        $planModel = new SubscriptionPlan();
        $currentPlan = $planModel->findByName($tenant['subscription_plan']);
        $newPlanData = $planModel->findByName($newPlan);

        if (!$newPlanData || !$newPlanData['is_active']) {
            return false; // Invalid or inactive plan
        }

        // Calculate prorated amount if upgrading mid-cycle
        $proratedAmount = $this->calculateProratedAmount($tenant, $newPlanData, $billingCycle);

        // Update tenant subscription
        $subscriptionEnd = $this->calculateSubscriptionEnd($billingCycle);

        $success = $this->update($tenantId, [
            'subscription_plan' => $newPlan,
            'billing_cycle' => $billingCycle,
            'subscription_ends_at' => $subscriptionEnd,
            'last_profile_update' => date('Y-m-d H:i:s')
        ]);

        if ($success) {
            // Create prorated billing record
            if ($proratedAmount > 0) {
                $billingModel = new TenantBilling();
                $billingModel->create([
                    'tenant_id' => $tenantId,
                    'amount' => $proratedAmount,
                    'billing_period_start' => date('Y-m-d'),
                    'billing_period_end' => $subscriptionEnd,
                    'status' => 'pending',
                    'notes' => "Prorated upgrade to {$newPlan} plan"
                ]);
            }

            // Log subscription change
            $this->logSubscriptionChange($tenantId, $tenant['subscription_plan'], $newPlan, 'upgrade');
        }

        return $success;
    }

    /**
     * Downgrade tenant subscription
     */
    public function downgradeSubscription(int $tenantId, string $newPlan, string $effectiveDate = null): bool
    {
        $tenant = $this->find($tenantId);
        if (!$tenant) {
            return false;
        }

        $planModel = new SubscriptionPlan();
        $newPlanData = $planModel->findByName($newPlan);

        if (!$newPlanData || !$newPlanData['is_active']) {
            return false; // Invalid or inactive plan
        }

        $effectiveDate = $effectiveDate ?? date('Y-m-d', strtotime('+1 month'));

        // Update tenant subscription (effective next billing cycle)
        $success = $this->update($tenantId, [
            'subscription_plan' => $newPlan,
            'last_profile_update' => date('Y-m-d H:i:s')
        ]);

        if ($success) {
            // Schedule downgrade for next billing cycle
            $this->schedulePlanChange($tenantId, $newPlan, $effectiveDate);

            // Log subscription change
            $this->logSubscriptionChange($tenantId, $tenant['subscription_plan'], $newPlan, 'downgrade', $effectiveDate);
        }

        return $success;
    }

    /**
     * Cancel tenant subscription
     */
    public function cancelSubscription(int $tenantId, string $reason = ''): bool
    {
        $tenant = $this->find($tenantId);
        if (!$tenant) {
            return false;
        }

        // Mark as cancelled but keep active until end of billing period
        $success = $this->update($tenantId, [
            'status' => 'suspended', // Will be deactivated at end of period
            'last_profile_update' => date('Y-m-d H:i:s')
        ]);

        if ($success) {
            // Log cancellation
            $this->logSubscriptionChange($tenantId, $tenant['subscription_plan'], 'cancelled', 'cancel', null, $reason);
        }

        return $success;
    }

    /**
     * Reactivate suspended tenant
     */
    public function reactivateTenant(int $tenantId, string $newPlan = null): bool
    {
        $tenant = $this->find($tenantId);
        if (!$tenant || $tenant['status'] !== 'suspended') {
            return false;
        }

        $updateData = [
            'status' => 'active',
            'last_profile_update' => date('Y-m-d H:i:s')
        ];

        // Update plan if specified
        if ($newPlan) {
            $planModel = new SubscriptionPlan();
            $planData = $planModel->findByName($newPlan);
            if ($planData) {
                $updateData['subscription_plan'] = $newPlan;
                $updateData['subscription_ends_at'] = $this->calculateSubscriptionEnd($tenant['billing_cycle'] ?? 'monthly');
            }
        }

        return $this->update($tenantId, $updateData);
    }

    /**
     * Check feature access for tenant
     */
    public function checkFeatureAccess(int $tenantId, string $feature): array
    {
        $usageModel = new TenantFeatureUsage();
        return $usageModel->canUseFeature($tenantId, $feature);
    }

    /**
     * Track feature usage for tenant
     */
    public function trackFeatureUsage(int $tenantId, string $feature, int $amount = 1): bool
    {
        $usageModel = new TenantFeatureUsage();
        return $usageModel->trackUsage($tenantId, $feature, $amount);
    }

    /**
     * Get tenant usage summary
     */
    public function getUsageSummary(int $tenantId): array
    {
        $usageModel = new TenantFeatureUsage();
        return $usageModel->getTenantUsageSummary($tenantId);
    }

    /**
     * Get subscription details
     */
    public function getSubscriptionDetails(int $tenantId): ?array
    {
        $tenant = $this->find($tenantId);
        if (!$tenant) {
            return null;
        }

        $planModel = new SubscriptionPlan();
        $plan = $planModel->findByName($tenant['subscription_plan']);

        return [
            'tenant_id' => $tenantId,
            'current_plan' => $tenant['subscription_plan'],
            'billing_cycle' => $tenant['billing_cycle'],
            'subscription_ends_at' => $tenant['subscription_ends_at'],
            'trial_ends_at' => $tenant['trial_ends_at'],
            'status' => $tenant['status'],
            'plan_details' => $plan,
            'is_expired' => $this->isSubscriptionExpired($tenant),
            'days_until_expiry' => $this->getDaysUntilExpiry($tenant)
        ];
    }

    /**
     * Calculate prorated amount for plan changes
     */
    private function calculateProratedAmount(array $tenant, array $newPlan, string $billingCycle): float
    {
        if (!$tenant['subscription_ends_at']) {
            // No existing subscription, charge full amount
            return $newPlan['price_' . $billingCycle] ?? 0;
        }

        $now = new \DateTime();
        $endDate = new \DateTime($tenant['subscription_ends_at']);
        $interval = $now->diff($endDate);

        if ($billingCycle === 'yearly') {
            $totalDays = 365;
        } else {
            $totalDays = (int)$now->format('t'); // Days in current month
        }

        $remainingDays = max(0, $interval->days);
        $dailyRate = ($newPlan['price_' . $billingCycle] ?? 0) / $totalDays;

        return round($dailyRate * $remainingDays, 2);
    }

    /**
     * Calculate subscription end date
     */
    private function calculateSubscriptionEnd(string $billingCycle): string
    {
        $now = new \DateTime();

        if ($billingCycle === 'yearly') {
            $now->modify('+1 year');
        } else {
            $now->modify('+1 month');
        }

        return $now->format('Y-m-d');
    }

    /**
     * Schedule plan change for future date
     */
    private function schedulePlanChange(int $tenantId, string $newPlan, string $effectiveDate): void
    {
        // This would typically use a job queue or scheduled task
        // For now, we'll just log it
        error_log("Scheduled plan change for tenant {$tenantId}: {$newPlan} effective {$effectiveDate}");
    }

    /**
     * Check if subscription is expired
     */
    private function isSubscriptionExpired(array $tenant): bool
    {
        if (!$tenant['subscription_ends_at']) {
            return false;
        }

        $now = new \DateTime();
        $endDate = new \DateTime($tenant['subscription_ends_at']);

        return $now > $endDate;
    }

    /**
     * Get days until subscription expiry
     */
    private function getDaysUntilExpiry(array $tenant): ?int
    {
        if (!$tenant['subscription_ends_at']) {
            return null;
        }

        $now = new \DateTime();
        $endDate = new \DateTime($tenant['subscription_ends_at']);

        if ($now > $endDate) {
            return 0; // Already expired
        }

        return $now->diff($endDate)->days;
    }

    /**
     * Update tenant theme settings
     */
    public function updateTheme(int $tenantId, array $themeSettings): bool
    {
        $validThemes = ['light', 'dark', 'blue', 'green', 'purple', 'orange'];
        $validColors = ['primary', 'secondary', 'success', 'danger', 'warning', 'info'];

        // Validate theme
        if (isset($themeSettings['theme']) && !in_array($themeSettings['theme'], $validThemes)) {
            throw new \Exception('Invalid theme selected');
        }

        // Validate primary color
        if (isset($themeSettings['primary_color']) && !in_array($themeSettings['primary_color'], $validColors)) {
            throw new \Exception('Invalid primary color selected');
        }

        $themeData = [
            'theme' => $themeSettings['theme'] ?? 'light',
            'primary_color' => $themeSettings['primary_color'] ?? 'primary',
            'navbar_bg' => $themeSettings['navbar_bg'] ?? '#ffffff',
            'sidebar_bg' => $themeSettings['sidebar_bg'] ?? '#f8f9fa',
            'accent_color' => $themeSettings['accent_color'] ?? '#007bff',
            'font_family' => $themeSettings['font_family'] ?? 'Inter, sans-serif',
            'border_radius' => $themeSettings['border_radius'] ?? '0.375rem'
        ];

        return $this->update($tenantId, [
            'theme_settings' => json_encode($themeData),
            'last_profile_update' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Update tenant branding settings
     */
    public function updateBranding(int $tenantId, array $brandingSettings): bool
    {
        $brandingData = [
            'company_name' => trim($brandingSettings['company_name'] ?? ''),
            'tagline' => trim($brandingSettings['tagline'] ?? ''),
            'description' => trim($brandingSettings['description'] ?? ''),
            'website' => trim($brandingSettings['website'] ?? ''),
            'email' => trim($brandingSettings['email'] ?? ''),
            'phone' => trim($brandingSettings['phone'] ?? ''),
            'address' => trim($brandingSettings['address'] ?? ''),
            'social_media' => $brandingSettings['social_media'] ?? [],
            'business_hours' => $brandingSettings['business_hours'] ?? []
        ];

        return $this->update($tenantId, [
            'branding_settings' => json_encode($brandingData),
            'last_profile_update' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Upload tenant logo
     */
    public function uploadLogo(int $tenantId, array $file): ?string
    {
        $tenant = $this->find($tenantId);
        if (!$tenant) {
            throw new \Exception('Tenant not found');
        }

        $uploadResult = \App\Helpers\FileUpload::upload($file, 'tenants/logos/', [
            'allowed_types' => ['image/jpeg', 'image/png', 'image/svg+xml'],
            'max_size' => 2 * 1024 * 1024, // 2MB
            'prefix' => $tenant['slug'] . '_logo_'
        ]);

        if (!$uploadResult['success']) {
            throw new \Exception('Failed to upload logo: ' . $uploadResult['error']);
        }

        // Update tenant logo path
        $this->update($tenantId, [
            'logo_path' => $uploadResult['path'],
            'last_profile_update' => date('Y-m-d H:i:s')
        ]);

        return $uploadResult['path'];
    }

    /**
     * Upload tenant favicon
     */
    public function uploadFavicon(int $tenantId, array $file): ?string
    {
        $tenant = $this->find($tenantId);
        if (!$tenant) {
            throw new \Exception('Tenant not found');
        }

        $uploadResult = \App\Helpers\FileUpload::upload($file, 'tenants/favicons/', [
            'allowed_types' => ['image/x-icon', 'image/png', 'image/jpeg'],
            'max_size' => 512 * 1024, // 512KB
            'prefix' => $tenant['slug'] . '_favicon_'
        ]);

        if (!$uploadResult['success']) {
            throw new \Exception('Failed to upload favicon: ' . $uploadResult['error']);
        }

        // Update tenant favicon path
        $this->update($tenantId, [
            'favicon_path' => $uploadResult['path'],
            'last_profile_update' => date('Y-m-d H:i:s')
        ]);

        return $uploadResult['path'];
    }

    /**
     * Update tenant UI preferences
     */
    public function updateUIPreferences(int $tenantId, array $preferences): bool
    {
        $validPreferences = [
            'sidebar_collapsed' => 'boolean',
            'compact_mode' => 'boolean',
            'show_notifications' => 'boolean',
            'language' => 'string',
            'timezone' => 'string',
            'date_format' => 'string',
            'currency_format' => 'string'
        ];

        $uiPreferences = [];
        foreach ($validPreferences as $key => $type) {
            if (isset($preferences[$key])) {
                if ($type === 'boolean') {
                    $uiPreferences[$key] = (bool)$preferences[$key];
                } else {
                    $uiPreferences[$key] = trim($preferences[$key]);
                }
            }
        }

        return $this->update($tenantId, [
            'ui_preferences' => json_encode($uiPreferences),
            'last_profile_update' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Get tenant customization settings
     */
    public function getCustomizationSettings(int $tenantId): array
    {
        $tenant = $this->find($tenantId);
        if (!$tenant) {
            return ['error' => 'Tenant not found'];
        }

        return [
            'theme_settings' => json_decode($tenant['theme_settings'] ?? '{}', true),
            'branding_settings' => json_decode($tenant['branding_settings'] ?? '{}', true),
            'ui_preferences' => json_decode($tenant['ui_preferences'] ?? '{}', true),
            'logo_path' => $tenant['logo_path'],
            'favicon_path' => $tenant['favicon_path']
        ];
    }

    /**
     * Reset tenant customization to defaults
     */
    public function resetCustomization(int $tenantId): bool
    {
        return $this->update($tenantId, [
            'theme_settings' => json_encode([
                'theme' => 'light',
                'primary_color' => 'primary',
                'navbar_bg' => '#ffffff',
                'sidebar_bg' => '#f8f9fa',
                'accent_color' => '#007bff',
                'font_family' => 'Inter, sans-serif',
                'border_radius' => '0.375rem'
            ]),
            'branding_settings' => json_encode([]),
            'ui_preferences' => json_encode([
                'sidebar_collapsed' => false,
                'compact_mode' => false,
                'show_notifications' => true,
                'language' => 'id',
                'timezone' => 'Asia/Jakarta',
                'date_format' => 'd/m/Y',
                'currency_format' => 'IDR'
            ]),
            'logo_path' => null,
            'favicon_path' => null,
            'last_profile_update' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Generate tenant CSS for custom theme
     */
    public function generateTenantCSS(int $tenantId): string
    {
        $settings = $this->getCustomizationSettings($tenantId);
        $theme = $settings['theme_settings'] ?? [];

        $css = ":root {\n";

        if (!empty($theme['primary_color'])) {
            $css .= "  --bs-primary: {$theme['primary_color']};\n";
        }

        if (!empty($theme['accent_color'])) {
            $css .= "  --accent-color: {$theme['accent_color']};\n";
        }

        if (!empty($theme['navbar_bg'])) {
            $css .= "  --navbar-bg: {$theme['navbar_bg']};\n";
        }

        if (!empty($theme['sidebar_bg'])) {
            $css .= "  --sidebar-bg: {$theme['sidebar_bg']};\n";
        }

        if (!empty($theme['font_family'])) {
            $css .= "  --font-family: {$theme['font_family']};\n";
        }

        if (!empty($theme['border_radius'])) {
            $css .= "  --border-radius: {$theme['border_radius']};\n";
        }

        $css .= "}\n\n";

        // Add custom theme classes
        if (!empty($theme['theme'])) {
            if ($theme['theme'] === 'dark') {
                $css .= ".theme-dark { background-color: #212529; color: #ffffff; }\n";
                $css .= ".theme-dark .card { background-color: #343a40; border-color: #495057; }\n";
            } elseif ($theme['theme'] === 'blue') {
                $css .= ".theme-blue { --bs-primary: #0d6efd; --accent-color: #0d6efd; }\n";
            } elseif ($theme['theme'] === 'green') {
                $css .= ".theme-green { --bs-primary: #198754; --accent-color: #198754; }\n";
            } elseif ($theme['theme'] === 'purple') {
                $css .= ".theme-purple { --bs-primary: #6f42c1; --accent-color: #6f42c1; }\n";
            } elseif ($theme['theme'] === 'orange') {
                $css .= ".theme-orange { --bs-primary: #fd7e14; --accent-color: #fd7e14; }\n";
            }
        }

        return $css;
    }

    /**
     * Get tenant public profile (for public display)
     */
    public function getPublicProfile(string $slug): ?array
    {
        $tenant = $this->findBySlug($slug);
        if (!$tenant || $tenant['status'] !== 'active') {
            return null;
        }

        $branding = json_decode($tenant['branding_settings'] ?? '{}', true);

        return [
            'name' => $tenant['name'],
            'slug' => $tenant['slug'],
            'description' => $tenant['description'],
            'logo_path' => $tenant['logo_path'],
            'branding' => $branding,
            'subscription_plan' => $tenant['subscription_plan'],
            'established' => $tenant['created_at']
        ];
    }
}
