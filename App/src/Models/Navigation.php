<?php
namespace App\Models;

class Navigation extends Model
{
    protected string $table = 'navigation_menus';
    protected array $fillable = [
        'tenant_id', 'menu_key', 'title', 'icon', 'route',
        'parent_id', 'order', 'is_active', 'permissions',
        'custom_data'
    ];
    protected array $casts = [
        'tenant_id' => 'int',
        'parent_id' => 'int',
        'order' => 'int',
        'is_active' => 'bool',
        'permissions' => 'array',
        'custom_data' => 'array',
        'created_at' => 'datetime'
    ];

    /**
     * Get navigation menu for user based on tenant and role
     */
    public function getUserMenu(?int $tenantId, string $userRole, array $userPermissions = []): array
    {
        $menus = $this->findWhere([
            'tenant_id' => $tenantId,
            'is_active' => true
        ], ['`order`' => 'ASC']);

        $filteredMenus = [];
        foreach ($menus as $menu) {
            if ($this->userHasAccess($menu, $userRole, $userPermissions)) {
                $filteredMenus[] = $menu;
            }
        }

        return $this->buildMenuHierarchy($filteredMenus);
    }

    /**
     * Get default menu structure for new tenants
     */
    public function getDefaultMenuStructure(): array
    {
        return [
            // Main section
            [
                'menu_key' => 'dashboard',
                'title' => 'Dashboard',
                'icon' => 'bi bi-speedometer2',
                'route' => '/dashboard',
                'parent_id' => null,
                'order' => 1,
                'permissions' => ['dashboard.view'],
                'custom_data' => ['section' => 'main']
            ],
            // Transactions section
            [
                'menu_key' => 'loans',
                'title' => 'Pinjaman',
                'icon' => 'bi bi-cash-stack',
                'route' => '/loans',
                'parent_id' => null,
                'order' => 10,
                'permissions' => ['loans.view'],
                'custom_data' => ['section' => 'transactions']
            ],
            [
                'menu_key' => 'repayments',
                'title' => 'Pembayaran',
                'icon' => 'bi bi-wallet2',
                'route' => '/repayments',
                'parent_id' => null,
                'order' => 11,
                'permissions' => ['repayments.view'],
                'custom_data' => ['section' => 'transactions']
            ],
            [
                'menu_key' => 'disbursement',
                'title' => 'Pencairan',
                'icon' => 'bi bi-credit-card',
                'route' => '/disbursement',
                'parent_id' => null,
                'order' => 12,
                'permissions' => ['disbursement.view'],
                'custom_data' => ['section' => 'transactions']
            ],
            // Master Data section
            [
                'menu_key' => 'members',
                'title' => 'Anggota',
                'icon' => 'bi bi-people',
                'route' => '/members',
                'parent_id' => null,
                'order' => 20,
                'permissions' => ['members.view'],
                'custom_data' => ['section' => 'master_data']
            ],
            [
                'menu_key' => 'products',
                'title' => 'Produk',
                'icon' => 'bi bi-box',
                'route' => '/products',
                'parent_id' => null,
                'order' => 21,
                'permissions' => ['products.view'],
                'custom_data' => ['section' => 'master_data']
            ],
            [
                'menu_key' => 'surveys',
                'title' => 'Survey',
                'icon' => 'bi bi-clipboard-check',
                'route' => '/surveys',
                'parent_id' => null,
                'order' => 22,
                'permissions' => ['surveys.view'],
                'custom_data' => ['section' => 'master_data']
            ],
            // Reports section
            [
                'menu_key' => 'reports',
                'title' => 'Laporan',
                'icon' => 'bi bi-file-bar-graph',
                'route' => '/reports',
                'parent_id' => null,
                'order' => 30,
                'permissions' => ['reports.view'],
                'custom_data' => ['section' => 'reports']
            ],
            [
                'menu_key' => 'audit',
                'title' => 'Audit Log',
                'icon' => 'bi bi-clock-history',
                'route' => '/audit',
                'parent_id' => null,
                'order' => 31,
                'permissions' => ['audit.view'],
                'custom_data' => ['section' => 'reports']
            ],
            // System section
            [
                'menu_key' => 'users',
                'title' => 'Pengguna',
                'icon' => 'bi bi-person-gear',
                'route' => '/users',
                'parent_id' => null,
                'order' => 40,
                'permissions' => ['users.view'],
                'custom_data' => ['section' => 'system']
            ],
            [
                'menu_key' => 'documents',
                'title' => 'Dokumen',
                'icon' => 'bi bi-file-text',
                'route' => '/surat',
                'parent_id' => null,
                'order' => 41,
                'permissions' => ['documents.view'],
                'custom_data' => ['section' => 'system']
            ],
            [
                'menu_key' => 'accounting',
                'title' => 'Akuntansi',
                'icon' => 'bi bi-calculator',
                'route' => '/accounting',
                'parent_id' => null,
                'order' => 42,
                'permissions' => ['accounting.view'],
                'custom_data' => ['section' => 'system']
            ],
            [
                'menu_key' => 'payroll',
                'title' => 'Penggajian',
                'icon' => 'bi bi-cash-coin',
                'route' => '/payroll',
                'parent_id' => null,
                'order' => 43,
                'permissions' => ['payroll.view'],
                'custom_data' => ['section' => 'system']
            ],
            [
                'menu_key' => 'compliance',
                'title' => 'Kepatuhan',
                'icon' => 'bi bi-shield-check',
                'route' => '/compliance',
                'parent_id' => null,
                'order' => 44,
                'permissions' => ['compliance.view'],
                'custom_data' => ['section' => 'system']
            ],
            // Advanced features (subscription-dependent)
            [
                'menu_key' => 'savings',
                'title' => 'Simpanan',
                'icon' => 'bi bi-piggy-bank',
                'route' => '/savings',
                'parent_id' => null,
                'order' => 13,
                'permissions' => ['savings.view'],
                'custom_data' => ['section' => 'transactions', 'plan_required' => 'professional']
            ],
            [
                'menu_key' => 'shu',
                'title' => 'SHU',
                'icon' => 'bi bi-trophy',
                'route' => '/shu',
                'parent_id' => null,
                'order' => 14,
                'permissions' => ['shu.view'],
                'custom_data' => ['section' => 'transactions', 'plan_required' => 'professional']
            ],
            [
                'menu_key' => 'analytics',
                'title' => 'Analytics',
                'icon' => 'bi bi-graph-up',
                'route' => '/analytics',
                'parent_id' => null,
                'order' => 32,
                'permissions' => ['analytics.view'],
                'custom_data' => ['section' => 'reports', 'plan_required' => 'enterprise']
            ],
            [
                'menu_key' => 'customization',
                'title' => 'Kustomisasi',
                'icon' => 'bi bi-palette',
                'route' => '/tenant/customization',
                'parent_id' => null,
                'order' => 45,
                'permissions' => ['customization.view'],
                'custom_data' => ['section' => 'system', 'plan_required' => 'professional']
            ]
        ];
    }

    /**
     * Setup default menu for new tenant
     */
    public function setupDefaultMenu(int $tenantId): void
    {
        $defaultMenus = $this->getDefaultMenuStructure();

        foreach ($defaultMenus as $menuData) {
            $menuData['tenant_id'] = $tenantId;
            $menuData['is_active'] = true;
            $this->create($menuData);
        }
    }

    /**
     * Check if user has access to menu item
     */
    private function userHasAccess(array $menu, string $userRole, array $userPermissions): bool
    {
        // Check if menu has required permissions
        if (!empty($menu['permissions'])) {
            foreach ($menu['permissions'] as $requiredPermission) {
                if (!in_array($requiredPermission, $userPermissions)) {
                    return false;
                }
            }
        }

        // Check plan requirements
        if (isset($menu['custom_data']['plan_required'])) {
            $tenantModel = new Tenant();
            $tenant = $tenantModel->find($menu['tenant_id']);

            if (!$tenant || $tenant['subscription_plan'] !== $menu['custom_data']['plan_required']) {
                return false;
            }
        }

        return true;
    }

    /**
     * Build hierarchical menu structure
     */
    private function buildMenuHierarchy(array $menus): array
    {
        $menuTree = [];
        $menuMap = [];

        // First pass: create menu map
        foreach ($menus as $menu) {
            $menuMap[$menu['id']] = $menu;
            $menuMap[$menu['id']]['children'] = [];
        }

        // Second pass: build hierarchy
        foreach ($menus as $menu) {
            if ($menu['parent_id'] === null) {
                $menuTree[] = &$menuMap[$menu['id']];
            } else {
                if (isset($menuMap[$menu['parent_id']])) {
                    $menuMap[$menu['parent_id']]['children'][] = &$menuMap[$menu['id']];
                }
            }
        }

        return $menuTree;
    }

    /**
     * Update menu item
     */
    public function updateMenuItem(int $menuId, array $data): bool
    {
        return $this->update($menuId, $data);
    }

    /**
     * Toggle menu item active status
     */
    public function toggleMenuItem(int $menuId): bool
    {
        $menu = $this->find($menuId);
        if (!$menu) {
            return false;
        }

        return $this->update($menuId, ['is_active' => !$menu['is_active']]);
    }

    /**
     * Get menu items for tenant
     */
    public function getTenantMenus(int $tenantId): array
    {
        return $this->findWhere(['tenant_id' => $tenantId], ['order' => 'ASC']);
    }

    /**
     * Add custom menu item
     */
    public function addCustomMenuItem(int $tenantId, array $menuData): int
    {
        $menuData['tenant_id'] = $tenantId;
        $menuData['is_active'] = $menuData['is_active'] ?? true;
        $menuData['order'] = $menuData['order'] ?? 999;

        return $this->create($menuData);
    }

    /**
     * Remove menu item
     */
    public function removeMenuItem(int $menuId): bool
    {
        return $this->delete($menuId);
    }

    /**
     * Reorder menu items
     */
    public function reorderMenuItems(array $menuOrder): bool
    {
        $this->db->beginTransaction();

        try {
            foreach ($menuOrder as $menuId => $order) {
                $this->update($menuId, ['order' => $order]);
            }

            $this->db->commit();
            return true;
        } catch (\Exception $e) {
            $this->db->rollBack();
            return false;
        }
    }
}
