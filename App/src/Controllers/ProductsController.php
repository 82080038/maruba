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
        $stmt = $pdo->query('SELECT * FROM products ORDER BY type, name');
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
        $stmt = $pdo->prepare('INSERT INTO products (name, type, rate, tenor_months, fee) VALUES (?, ?, ?, ?, ?)');
        $stmt->execute([$name, $type, $rate, $tenor, $fee]);
        $_SESSION['success'] = 'Produk berhasil ditambahkan.';
        header('Location: ' . route_url('products'));
    }
}
