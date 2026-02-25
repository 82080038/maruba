<?php
namespace App\Controllers;

use App\Models\Navigation;
use App\Models\Tenant;
use App\Helpers\AuthHelper;

class NavigationController
{
    /**
     * Get dynamic navigation menu for current user
     */
    public function getUserMenu(): void
    {
        $currentTenant = $this->getCurrentTenant();
        $user = current_user();

        if (!$user) {
            http_response_code(401);
            echo json_encode(['error' => 'User not authenticated']);
            return;
        }

        $navigationModel = new Navigation();

        // Get user permissions (this would come from your auth system)
        $userPermissions = $this->getUserPermissions($user);

        // Get menu for tenant and user
        $menuItems = $navigationModel->getUserMenu(
            $currentTenant ? $currentTenant['id'] : null,
            $user['role'] ?? 'user',
            $userPermissions
        );

        // Format for frontend
        $formattedMenu = $this->formatMenuForFrontend($menuItems);

        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'menu' => $formattedMenu]);
    }

    /**
     * Setup navigation menu for new tenant
     */
    public function setupTenantMenu(): void
    {
        require_login();

        $tenantId = (int)($_POST['tenant_id'] ?? 0);
        if (!$tenantId) {
            $_SESSION['error'] = 'Tenant ID required';
            header('Location: ' . route_url('tenants'));
            return;
        }

        // Check if user can setup menu for this tenant
        $currentTenant = $this->getCurrentTenant();
        if (!$currentTenant || $currentTenant['id'] !== $tenantId) {
            $_SESSION['error'] = 'Access denied';
            header('Location: ' . route_url('tenants'));
            return;
        }

        $navigationModel = new Navigation();

        try {
            $navigationModel->setupDefaultMenu($tenantId);
            $_SESSION['success'] = 'Navigation menu setup completed';
        } catch (\Exception $e) {
            $_SESSION['error'] = 'Failed to setup navigation menu: ' . $e->getMessage();
        }

        header('Location: ' . route_url('tenant/navigation'));
    }

    /**
     * Show navigation management interface
     */
    public function manageMenu(): void
    {
        require_login();

        $currentTenant = $this->getCurrentTenant();
        if (!$currentTenant) {
            http_response_code(403);
            echo 'Access denied - No tenant found';
            return;
        }

        $navigationModel = new Navigation();
        $menuItems = $navigationModel->getTenantMenus($currentTenant['id']);

        include view_path('tenant/navigation/manage');
    }

    /**
     * Add new menu item
     */
    public function addMenuItem(): void
    {
        require_login();

        $currentTenant = $this->getCurrentTenant();
        if (!$currentTenant) {
            http_response_code(403);
            echo 'Access denied';
            return;
        }

        $menuData = [
            'menu_key' => trim($_POST['menu_key'] ?? ''),
            'title' => trim($_POST['title'] ?? ''),
            'icon' => trim($_POST['icon'] ?? ''),
            'route' => trim($_POST['route'] ?? ''),
            'parent_id' => !empty($_POST['parent_id']) ? (int)$_POST['parent_id'] : null,
            'order' => (int)($_POST['order'] ?? 0),
            'permissions' => json_decode($_POST['permissions'] ?? '[]', true),
            'custom_data' => json_decode($_POST['custom_data'] ?? '{}', true)
        ];

        if (empty($menuData['menu_key']) || empty($menuData['title'])) {
            $_SESSION['error'] = 'Menu key and title are required';
            header('Location: ' . route_url('tenant/navigation/manage'));
            return;
        }

        $navigationModel = new Navigation();

        try {
            $navigationModel->addCustomMenuItem($currentTenant['id'], $menuData);
            $_SESSION['success'] = 'Menu item added successfully';
        } catch (\Exception $e) {
            $_SESSION['error'] = 'Failed to add menu item: ' . $e->getMessage();
        }

        header('Location: ' . route_url('tenant/navigation/manage'));
    }

    /**
     * Update menu item
     */
    public function updateMenuItem(): void
    {
        require_login();

        $menuId = (int)($_POST['menu_id'] ?? 0);
        if (!$menuId) {
            $_SESSION['error'] = 'Menu ID required';
            header('Location: ' . route_url('tenant/navigation/manage'));
            return;
        }

        $currentTenant = $this->getCurrentTenant();
        if (!$currentTenant) {
            $_SESSION['error'] = 'Access denied';
            header('Location: ' . route_url('tenant/navigation/manage'));
            return;
        }

        $updateData = [
            'title' => trim($_POST['title'] ?? ''),
            'icon' => trim($_POST['icon'] ?? ''),
            'route' => trim($_POST['route'] ?? ''),
            'order' => (int)($_POST['order'] ?? 0),
            'is_active' => isset($_POST['is_active']),
            'permissions' => json_decode($_POST['permissions'] ?? '[]', true),
            'custom_data' => json_decode($_POST['custom_data'] ?? '{}', true)
        ];

        $navigationModel = new Navigation();
        $menuItem = $navigationModel->find($menuId);

        // Check if menu belongs to current tenant
        if (!$menuItem || $menuItem['tenant_id'] !== $currentTenant['id']) {
            $_SESSION['error'] = 'Access denied';
            header('Location: ' . route_url('tenant/navigation/manage'));
            return;
        }

        try {
            $navigationModel->updateMenuItem($menuId, $updateData);
            $_SESSION['success'] = 'Menu item updated successfully';
        } catch (\Exception $e) {
            $_SESSION['error'] = 'Failed to update menu item: ' . $e->getMessage();
        }

        header('Location: ' . route_url('tenant/navigation/manage'));
    }

    /**
     * Toggle menu item active status
     */
    public function toggleMenuItem(): void
    {
        require_login();

        $menuId = (int)($_GET['id'] ?? 0);
        if (!$menuId) {
            $_SESSION['error'] = 'Menu ID required';
            header('Location: ' . route_url('tenant/navigation/manage'));
            return;
        }

        $currentTenant = $this->getCurrentTenant();
        if (!$currentTenant) {
            $_SESSION['error'] = 'Access denied';
            header('Location: ' . route_url('tenant/navigation/manage'));
            return;
        }

        $navigationModel = new Navigation();
        $menuItem = $navigationModel->find($menuId);

        // Check if menu belongs to current tenant
        if (!$menuItem || $menuItem['tenant_id'] !== $currentTenant['id']) {
            $_SESSION['error'] = 'Access denied';
            header('Location: ' . route_url('tenant/navigation/manage'));
            return;
        }

        try {
            $navigationModel->toggleMenuItem($menuId);
            $_SESSION['success'] = 'Menu item status updated';
        } catch (\Exception $e) {
            $_SESSION['error'] = 'Failed to update menu status: ' . $e->getMessage();
        }

        header('Location: ' . route_url('tenant/navigation/manage'));
    }

    /**
     * Remove menu item
     */
    public function removeMenuItem(): void
    {
        require_login();

        $menuId = (int)($_GET['id'] ?? 0);
        if (!$menuId) {
            $_SESSION['error'] = 'Menu ID required';
            header('Location: ' . route_url('tenant/navigation/manage'));
            return;
        }

        $currentTenant = $this->getCurrentTenant();
        if (!$currentTenant) {
            $_SESSION['error'] = 'Access denied';
            header('Location: ' . route_url('tenant/navigation/manage'));
            return;
        }

        $navigationModel = new Navigation();
        $menuItem = $navigationModel->find($menuId);

        // Check if menu belongs to current tenant
        if (!$menuItem || $menuItem['tenant_id'] !== $currentTenant['id']) {
            $_SESSION['error'] = 'Access denied';
            header('Location: ' . route_url('tenant/navigation/manage'));
            return;
        }

        try {
            $navigationModel->removeMenuItem($menuId);
            $_SESSION['success'] = 'Menu item removed successfully';
        } catch (\Exception $e) {
            $_SESSION['error'] = 'Failed to remove menu item: ' . $e->getMessage();
        }

        header('Location: ' . route_url('tenant/navigation/manage'));
    }

    /**
     * Reorder menu items
     */
    public function reorderMenu(): void
    {
        require_login();

        $currentTenant = $this->getCurrentTenant();
        if (!$currentTenant) {
            http_response_code(403);
            echo json_encode(['error' => 'Access denied']);
            return;
        }

        $menuOrder = json_decode(file_get_contents('php://input'), true);
        if (!$menuOrder || !is_array($menuOrder)) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid menu order data']);
            return;
        }

        $navigationModel = new Navigation();

        try {
            $navigationModel->reorderMenuItems($menuOrder);
            echo json_encode(['success' => true, 'message' => 'Menu reordered successfully']);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    // ===== PRIVATE METHODS =====

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
            $tenantModel = new Tenant();
            $tenant = $tenantModel->findBySlug($slug);
            if ($tenant && $tenant['status'] === 'active') {
                $_SESSION['tenant'] = $tenant;
                return $tenant;
            }
        }

        return null;
    }

    /**
     * Get user permissions (placeholder - implement based on your auth system)
     */
    private function getUserPermissions(array $user): array
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

    /**
     * Format menu items for frontend consumption
     */
    private function formatMenuForFrontend(array $menuItems): array
    {
        $formatted = [];

        foreach ($menuItems as $item) {
            $formattedItem = [
                'id' => $item['id'],
                'key' => $item['menu_key'],
                'title' => $item['title'],
                'icon' => $item['icon'],
                'route' => $item['route'],
                'order' => $item['order'],
                'section' => $item['custom_data']['section'] ?? 'other'
            ];

            if (!empty($item['children'])) {
                $formattedItem['children'] = $this->formatMenuForFrontend($item['children']);
            }

            $formatted[] = $formattedItem;
        }

        return $formatted;
    }
}
