<?php
namespace App\Controllers;
use App\Helpers\AuthHelper;

class ProductsController
{
    public function index(): void
    {
        require_login();
        AuthHelper::requirePermission('products', 'view');
        $title = 'Produk';
        $pdo = \App\Database::getConnection();
        $tenantId = $this->getCurrentTenantId();

        if ($tenantId === null) {
            // System admin - can see all tenants
            $stmt = $pdo->query('SELECT p.*, t.name as tenant_name FROM products p LEFT JOIN tenants t ON p.tenant_id = t.id ORDER BY p.type, p.name');
        } else {
            // Tenant user - only see their tenant data
            $stmt = $pdo->prepare('SELECT * FROM products WHERE tenant_id = ? ORDER BY type, name');
            $stmt->execute([$tenantId]);
        }

        $products = $stmt->fetchAll();
        include view_path('products/index');
    }

    public function create(): void
    {
        require_login();
        AuthHelper::requirePermission('products', 'create');
        $title = 'Tambah Produk';
        include view_path('products/create');
    }

    public function store(): void
    {
        require_login();
        AuthHelper::requirePermission('products', 'create');
        $name = trim($_POST['name'] ?? '');
        $type = $_POST['type'] ?? 'loan';
        $rate = (float)($_POST['rate'] ?? 0);
        $tenor = (int)($_POST['tenor_months'] ?? 0);
        $fee = (float)($_POST['fee'] ?? 0);
        if (empty($name) || !in_array($type, ['loan','savings'])) {
            $_SESSION['error'] = 'Data tidak valid.';
            header('Location: ' . route_url('products/create'));
            return;
        }
        $pdo = \App\Database::getConnection();
        $tenantId = $this->getCurrentTenantId();
        $stmt = $pdo->prepare('INSERT INTO products (name, type, rate, tenor_months, fee, tenant_id) VALUES (?, ?, ?, ?, ?, ?)');
        $stmt->execute([$name, $type, $rate, $tenor, $fee, $tenantId]);
        $_SESSION['success'] = 'Produk berhasil ditambahkan.';
        header('Location: ' . route_url('products'));
    }

    /**
     * Get current tenant ID for data isolation
     */
    private function getCurrentTenantId(): ?int
    {
        // Check if user is system admin (tenant_id = NULL)
        $currentUser = current_user();
        if (!$currentUser) {
            return null;
        }

        // Get user details including tenant_id
        $pdo = \App\Database::getConnection();
        $stmt = $pdo->prepare('SELECT tenant_id FROM users WHERE id = ?');
        $stmt->execute([$currentUser['id']]);
        $user = $stmt->fetch();

        return $user ? $user['tenant_id'] : null;
    }
}
