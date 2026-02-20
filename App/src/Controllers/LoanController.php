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
        $members = $pdo->query('SELECT id, name, nik, phone FROM members WHERE status="active" ORDER BY name')->fetchAll();
        $products = $pdo->query('SELECT id, name, rate, tenor_months, fee FROM products WHERE type="loan" ORDER BY name')->fetchAll();
        include view_path('loans/create');
    }

    public function store(): void
    {
        require_login();
        AuthHelper::requirePermission('loans', 'create');
        $memberId = (int)($_POST['member_id'] ?? 0);
        $productId = (int)($_POST['product_id'] ?? 0);
        $amount = (float)($_POST['amount'] ?? 0);
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
            // Insert loan
            $stmt = $pdo->prepare('INSERT INTO loans (member_id, product_id, amount, tenor_months, purpose, status) VALUES (?, ?, ?, ?, ?, ?)');
            $stmt->execute([$memberId, $productId, $amount, $tenor, $purpose, 'draft']);
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
        $stmt = $pdo->prepare('
            SELECT l.*, m.name AS member_name, p.name AS product_name
            FROM loans l
            JOIN members m ON l.member_id = m.id
            JOIN products p ON l.product_id = p.id
            ORDER BY l.created_at DESC
        ');
        $stmt->execute();
        $loans = $stmt->fetchAll();
        include view_path('loans/index');
    }
}
