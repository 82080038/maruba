<?php
// Navigation Helper Functions
namespace App\Helpers;

/**
 * Generate dynamic navigation menu HTML
 */
function generate_navigation_menu(): string
{
    $user = \current_user();
    
    if (!$user) {
        return '';
    }
    
    $userRole = $user['role'] ?? 'user';
    
    // Static menu based on role
    $menus = get_static_menu_for_role($userRole);
    
    return build_navigation_html($menus);
}

/**
 * Get static menu configuration for role
 */
function get_static_menu_for_role(string $role): array
{
    $baseMenus = [
        [
            'title' => 'Dashboard',
            'icon' => 'bi-speedometer2',
            'route' => 'dashboard',
            'active' => true
        ]
    ];
    
    $roleMenus = [];
    
    switch ($role) {
        case 'admin':
        case 'creator':
            $roleMenus = [
                ['title' => 'Users', 'icon' => 'bi-people', 'route' => 'users'],
                ['title' => 'Roles', 'icon' => 'bi-shield-lock', 'route' => 'roles'],
                ['title' => 'Members', 'icon' => 'bi-person-badge', 'route' => 'members'],
                ['title' => 'Products', 'icon' => 'bi-box', 'route' => 'products'],
                ['title' => 'Loans', 'icon' => 'bi-bank', 'route' => 'loans'],
                ['title' => 'Surveys', 'icon' => 'bi-clipboard-check', 'route' => 'surveys'],
                ['title' => 'Repayments', 'icon' => 'bi-cash-stack', 'route' => 'repayments'],
                ['title' => 'Reports', 'icon' => 'bi-graph-up', 'route' => 'reports'],
                ['title' => 'Audit Logs', 'icon' => 'bi-journal-text', 'route' => 'audit-logs'],
                ['title' => 'System', 'icon' => 'bi-gear', 'route' => 'system']
            ];
            break;
            
        case 'manajer':
            $roleMenus = [
                ['title' => 'Members', 'icon' => 'bi-person-badge', 'route' => 'members'],
                ['title' => 'Loans', 'icon' => 'bi-bank', 'route' => 'loans'],
                ['title' => 'Products', 'icon' => 'bi-box', 'route' => 'products'],
                ['title' => 'Reports', 'icon' => 'bi-graph-up', 'route' => 'reports']
            ];
            break;
            
        case 'kasir':
            $roleMenus = [
                ['title' => 'Cash', 'icon' => 'bi-cash', 'route' => 'cash'],
                ['title' => 'Transactions', 'icon' => 'bi-arrow-left-right', 'route' => 'transactions'],
                ['title' => 'Repayments', 'icon' => 'bi-cash-stack', 'route' => 'repayments'],
                ['title' => 'Reports', 'icon' => 'bi-graph-up', 'route' => 'reports']
            ];
            break;
            
        case 'teller':
            $roleMenus = [
                ['title' => 'Savings', 'icon' => 'bi-piggy-bank', 'route' => 'savings'],
                ['title' => 'Transactions', 'icon' => 'bi-arrow-left-right', 'route' => 'transactions'],
                ['title' => 'Members', 'icon' => 'bi-person-badge', 'route' => 'members']
            ];
            break;
            
        case 'surveyor':
            $roleMenus = [
                ['title' => 'Surveys', 'icon' => 'bi-clipboard-check', 'route' => 'surveys'],
                ['title' => 'Members', 'icon' => 'bi-person-badge', 'route' => 'members'],
                ['title' => 'Reports', 'icon' => 'bi-graph-up', 'route' => 'reports']
            ];
            break;
            
        case 'collector':
            $roleMenus = [
                ['title' => 'Collections', 'icon' => 'bi-cash-stack', 'route' => 'collections'],
                ['title' => 'Repayments', 'icon' => 'bi-cash-stack', 'route' => 'repayments'],
                ['title' => 'Members', 'icon' => 'bi-person-badge', 'route' => 'members']
            ];
            break;
            
        case 'akuntansi':
            $roleMenus = [
                ['title' => 'Transactions', 'icon' => 'bi-arrow-left-right', 'route' => 'transactions'],
                ['title' => 'Reports', 'icon' => 'bi-graph-up', 'route' => 'reports'],
                ['title' => 'Audit Logs', 'icon' => 'bi-journal-text', 'route' => 'audit-logs']
            ];
            break;
    }
    
    return array_merge($baseMenus, $roleMenus);
}

/**
 * Build navigation HTML
 */
function build_navigation_html(array $menuItems): string
{
    $html = '<ul class="nav-menu">';
    
    foreach ($menuItems as $item) {
        $activeClass = ($item['active'] ?? false) ? 'active' : '';
        $html .= sprintf(
            '<li class="menu-item %s">
                <a href="%s" class="menu-link" data-page="%s" data-href="%s">
                    <i class="bi %s menu-icon"></i>
                    <span class="menu-text">%s</span>
                </a>
            </li>',
            htmlspecialchars($activeClass),
            route_url('index.php/' . $item['route']),
            htmlspecialchars($item['route']),
            route_url('index.php/' . $item['route']),
            htmlspecialchars($item['icon']),
            htmlspecialchars($item['title'])
        );
    }
    
    $html .= '</ul>';
    return $html;
}

/**
 * Check if menu item is active
 */
function is_menu_item_active(array $item, string $currentPath): bool
{
    $route = $item['route'] ?? '';
    if (empty($route) || $route === '#') {
        return false;
    }

    // Remove query string and trailing slash for comparison
    $cleanRoute = rtrim(parse_url($route, PHP_URL_PATH) ?? $route, '/');
    $cleanCurrent = rtrim($currentPath, '/');

    // Exact match
    if ($cleanRoute === $cleanCurrent) {
        return true;
    }

    // Prefix match (for sub-pages)
    if (strpos($cleanCurrent, $cleanRoute . '/') === 0) {
        return true;
    }

    // Special cases for index pages
    if ($cleanRoute === '/dashboard' && $cleanCurrent === '/') {
        return true;
    }

    return false;
}

/**
 * Get current tenant
 */
function get_current_tenant(): ?array
{
    // Check if we're in tenant context via middleware
    if (isset($_SESSION['tenant'])) {
        return $_SESSION['tenant'];
    }

    // Try to get tenant from subdomain
    $host = $_SERVER['HTTP_HOST'] ?? '';
    if (preg_match('/^([a-z0-9-]+)\.' . preg_quote($_SERVER['SERVER_NAME'] ?? 'localhost', '/') . '$/', $host, $matches)) {
        $slug = $matches[1];
        $tenantModel = new \App\Models\Tenant();
        $tenant = $tenantModel->findBySlug($slug);
        if ($tenant && $tenant['status'] === 'active') {
            $_SESSION['tenant'] = $tenant;
            return $tenant;
        }
    }

    return null;
}

/**
 * Get user permissions
 */
function get_user_permissions(array $user): array
{
    // This should be implemented based on your authentication system
    // For now, return basic permissions based on role

    $permissions = [];

    switch ($user['role'] ?? 'user') {
        case 'admin':
            $permissions = [
                'dashboard.view',
                'loans.view', 'loans.create',
                'repayments.view', 'repayments.create',
                'disbursement.view',
                'members.view', 'members.create',
                'products.view', 'products.create',
                'surveys.view', 'surveys.create',
                'reports.view',
                'audit.view',
                'users.view', 'users.create',
                'documents.view',
                'accounting.view',
                'payroll.view',
                'compliance.view',
                'savings.view',
                'shu.view',
                'analytics.view',
                'customization.view'
            ];
            break;

        case 'manager':
            $permissions = [
                'dashboard.view',
                'loans.view', 'loans.create',
                'repayments.view',
                'members.view',
                'products.view',
                'reports.view',
                'documents.view'
            ];
            break;

        case 'kasir':
            $permissions = [
                'dashboard.view',
                'loans.view',
                'repayments.view', 'repayments.create',
                'members.view',
                'disbursement.view'
            ];
            break;

        case 'surveyor':
            $permissions = [
                'dashboard.view',
                'loans.view',
                'surveys.view', 'surveys.create',
                'members.view'
            ];
            break;

        default:
            $permissions = [
                'dashboard.view'
            ];
    }

    return $permissions;
}
