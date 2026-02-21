<?php
// Navigation Helper Functions
namespace App\Helpers;

/**
 * Generate dynamic navigation menu HTML
 */
function generate_navigation_menu(): string
{
    $currentTenant = get_current_tenant();
    $user = \current_user();

    if (!$user) {
        return '';
    }

    // Get user permissions
    $userPermissions = get_user_permissions($user);

    // Get navigation menu
    $navigationModel = new \App\Models\Navigation();
    $menuItems = $navigationModel->getUserMenu(
        $currentTenant ? $currentTenant['id'] : null,
        $user['role'] ?? 'user',
        $userPermissions
    );

    return build_navigation_html($menuItems);
}

/**
 * Build navigation HTML from menu items
 */
function build_navigation_html(array $menuItems): string
{
    $currentPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';
    $html = '';

    // Group items by section
    $sections = [];
    foreach ($menuItems as $item) {
        $section = $item['custom_data']['section'] ?? 'other';
        if (!isset($sections[$section])) {
            $sections[$section] = [];
        }
        $sections[$section][] = $item;
    }

    // Define section titles and order
    $sectionConfig = [
        'main' => ['title' => 'Utama', 'order' => 1],
        'transactions' => ['title' => 'Transaksi', 'order' => 2],
        'master_data' => ['title' => 'Data Master', 'order' => 3],
        'reports' => ['title' => 'Laporan', 'order' => 4],
        'system' => ['title' => 'Sistem', 'order' => 5],
        'other' => ['title' => 'Lainnya', 'order' => 6]
    ];

    // Sort sections by order
    uasort($sections, function($a, $b) use ($sectionConfig) {
        $orderA = $sectionConfig[array_key_first($a)]['order'] ?? 99;
        $orderB = $sectionConfig[array_key_first($b)]['order'] ?? 99;
        return $orderA <=> $orderB;
    });

    // Build HTML for each section
    foreach ($sections as $sectionKey => $items) {
        if (empty($items)) continue;

        $sectionTitle = $sectionConfig[$sectionKey]['title'] ?? ucfirst($sectionKey);
        $html .= "<div class='menu-section'>\n";
        $html .= "<div class='menu-section-title'>{$sectionTitle}</div>\n";

        foreach ($items as $item) {
            $isActive = is_menu_item_active($item, $currentPath);
            $activeClass = $isActive ? 'active' : '';
            $route = htmlspecialchars($item['route'] ?? '#');
            $title = htmlspecialchars($item['title']);
            $icon = htmlspecialchars($item['icon'] ?? 'bi bi-circle');

            $html .= "<a href='{$route}' class='menu-item {$activeClass}'>\n";
            $html .= "<i class='{$icon}'></i> {$title}\n";
            $html .= "</a>\n";
        }

        $html .= "</div>\n";
    }

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
