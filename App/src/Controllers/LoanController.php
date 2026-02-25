<?php
namespace App\Controllers;
use App\Helpers\AuthHelper;

class LoanController
{
    public function create(): void
    {
        require_login();
        AuthHelper::requirePermission('loans', 'create');
        $title = 'Pengajuan Pinjaman';
        // Ambil anggota dan produk
        $pdo = \App\Database::getConnection();
        $tenantId = $this->getCurrentTenantId();

        if ($tenantId === null) {
            // System admin - can see all tenants
            $members = $pdo->query('SELECT id, name, nik, phone FROM members WHERE status="active" ORDER BY name')->fetchAll();
            $products = $pdo->query('SELECT id, name, rate, tenor_months, fee FROM products WHERE type="loan" ORDER BY name')->fetchAll();
        } else {
            // Tenant user - only see their tenant data
            $stmtMembers = $pdo->prepare('SELECT id, name, nik, phone FROM members WHERE tenant_id = ? AND status="active" ORDER BY name');
            $stmtMembers->execute([$tenantId]);
            $members = $stmtMembers->fetchAll();

            $stmtProducts = $pdo->prepare('SELECT id, name, rate, tenor_months, fee FROM products WHERE tenant_id = ? AND type="loan" ORDER BY name');
            $stmtProducts->execute([$tenantId]);
            $products = $stmtProducts->fetchAll();
        }

        include view_path('loans/create');
    }

    public function store(): void
    {
        require_login();
        AuthHelper::requirePermission('loans', 'create');
        verify_csrf();

        $memberId = (int)($_POST['member_id'] ?? 0);
        $productId = (int)($_POST['product_id'] ?? 0);
        $amountRaw = (string)($_POST['amount'] ?? '');
        $amount = (float)preg_replace('/[^0-9.]/', '', $amountRaw);
        $tenor = (int)($_POST['tenor_months'] ?? 0);
        $purpose = trim($_POST['purpose'] ?? '');

        // Validasi sederhana
        if (!$memberId || !$productId || $amount <= 0 || $tenor <= 0 || empty($purpose)) {
            $_SESSION['error'] = 'Data tidak lengkap.';
            header('Location: ' . route_url('loans/create'));
            return;
        }

        $pdo = \App\Database::getConnection();
        $pdo->beginTransaction();
        try {
            // Insert loan with tenant_id
            $tenantId = $this->getCurrentTenantId();
            $stmt = $pdo->prepare('INSERT INTO loans (member_id, product_id, amount, tenor_months, purpose, status, tenant_id) VALUES (?, ?, ?, ?, ?, ?, ?)');
            $stmt->execute([$memberId, $productId, $amount, $tenor, $purpose, 'draft', $tenantId]);
            $loanId = $pdo->lastInsertId();

            // Handle file uploads
            if (!empty($_FILES['docs'])) {
                $uploadDir = __DIR__ . '/../../public/uploads';
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
                foreach ($_FILES['docs']['name'] as $type => $name) {
                    if (empty($name) || $_FILES['docs']['error'][$type] !== UPLOAD_ERR_OK) continue;
                    $tmp = $_FILES['docs']['tmp_name'][$type];
                    $ext = pathinfo($name, PATHINFO_EXTENSION);
                    $filename = 'loan_' . $loanId . '_' . $type . '_' . time() . '.' . $ext;
                    $dest = $uploadDir . '/' . $filename;
                    if (move_uploaded_file($tmp, $dest)) {
                        $stmt = $pdo->prepare('INSERT INTO loan_docs (loan_id, doc_type, path, uploaded_by) VALUES (?, ?, ?, ?)');
                        $stmt->execute([$loanId, $type, '/uploads/' . $filename, current_user()['id']]);
                    }
                }
            }

            // Audit log
            $stmt = $pdo->prepare('INSERT INTO audit_logs (user_id, action, entity, entity_id, meta) VALUES (?, ?, ?, ?, ?)');
            $stmt->execute([current_user()['id'], 'create', 'loan', $loanId, json_encode(['member_id'=>$memberId,'amount'=>$amount])]);

            $pdo->commit();
            $_SESSION['success'] = 'Pengajuan berhasil. Nomor: #' . $loanId;
            header('Location: ' . route_url('loans'));
        } catch (\Exception $e) {
            $pdo->rollBack();
            $_SESSION['error'] = 'Gagal menyimpan: ' . $e->getMessage();
            header('Location: ' . route_url('loans/create'));
        }
    }

    public function index(): void
    {
        require_login();
        AuthHelper::requirePermission('loans', 'view');
        $title = 'Daftar Pinjaman';
        $pdo = \App\Database::getConnection();
        $tenantId = $this->getCurrentTenantId();

        if ($tenantId === null) {
            // System admin - can see all tenants
            $stmt = $pdo->query('
                SELECT l.*, m.name AS member_name, p.name AS product_name, t.name as tenant_name
                FROM loans l
                JOIN members m ON l.member_id = m.id
                JOIN products p ON l.product_id = p.id
                LEFT JOIN tenants t ON l.tenant_id = t.id
                ORDER BY l.created_at DESC
            ');
        } else {
            // Tenant user - only see their tenant data
            $stmt = $pdo->prepare('
                SELECT l.*, m.name AS member_name, p.name AS product_name
                FROM loans l
                JOIN members m ON l.member_id = m.id
                JOIN products p ON l.product_id = p.id
                WHERE l.tenant_id = ?
                ORDER BY l.created_at DESC
            ');
            $stmt->execute([$tenantId]);
        }

        $loans = $stmt->fetchAll();
        include view_path('loans/index');
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
