<?php
namespace App\Controllers;
use App\Helpers\AuthHelper;

class DisbursementController
{
    public function index(): void
    {
        require_login();
        AuthHelper::requirePermission('loans', 'disburse');
        $title = 'Pencairan Pinjaman';
        $pdo = \App\Database::getConnection();
        $stmt = $pdo->prepare('
            SELECT l.*, m.name AS member_name, p.name AS product_name
            FROM loans l
            JOIN members m ON l.member_id = m.id
            JOIN products p ON l.product_id = p.id
            WHERE l.status = "approved"
            ORDER BY l.created_at DESC
        ');
        $stmt->execute();
        $loans = $stmt->fetchAll();
        include view_path('disbursement/index');
    }

    public function create(): void
    {
        require_login();
        AuthHelper::requirePermission('loans', 'disburse');
        $title = 'Pencairan';
        $loanId = (int)($_GET['loan_id'] ?? 0);
        if (!$loanId) {
            $_SESSION['error'] = 'Pinjaman tidak dipilih.';
            header('Location: ' . route_url('index.php/disbursement'));
            return;
        }
        $pdo = \App\Database::getConnection();
        $stmt = $pdo->prepare('
            SELECT l.*, m.name AS member_name, p.name AS product_name
            FROM loans l
            JOIN members m ON l.member_id = m.id
            JOIN products p ON l.product_id = p.id
            WHERE l.id = ? AND l.status = "approved"
        ');
        $stmt->execute([$loanId]);
        $loan = $stmt->fetch();
        if (!$loan) {
            $_SESSION['error'] = 'Pinjaman tidak ditemukan atau status tidak sesuai.';
            header('Location: ' . route_url('index.php/disbursement'));
            return;
        }
        include view_path('disbursement/create');
    }

    public function store(): void
    {
        require_login();
        AuthHelper::requirePermission('loans', 'disburse');
        $loanId = (int)($_POST['loan_id'] ?? 0);
        $disbursedAt = trim($_POST['disbursed_at'] ?? '');
        $notes = trim($_POST['notes'] ?? '');
        if (!$loanId || empty($disbursedAt)) {
            $_SESSION['error'] = 'Data tidak lengkap.';
            header('Location: ' . route_url('disbursement/create?loan_id='.$loanId));
            return;
        }
        $pdo = \App\Database::getConnection();
        $pdo->beginTransaction();
        try {
            // Update loan status
            $stmt = $pdo->prepare('UPDATE loans SET status = "disbursed", disbursed_by = ?, disbursed_at = ?, notes = ? WHERE id = ?');
            $stmt->execute([current_user()['id'], $disbursedAt, $notes, $loanId]);
            // Handle proof upload
            if (!empty($_FILES['proof']['name']) && $_FILES['proof']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = __DIR__ . '/../../public/uploads';
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
                $ext = pathinfo($_FILES['proof']['name'], PATHINFO_EXTENSION);
                $filename = 'disbursement_' . $loanId . '_' . time() . '.' . $ext;
                $dest = $uploadDir . '/' . $filename;
                if (move_uploaded_file($_FILES['proof']['tmp_name'], $dest)) {
                    // Simpan path di loan_docs (doc_type: disbursement_proof)
                    $stmt = $pdo->prepare('INSERT INTO loan_docs (loan_id, doc_type, path, uploaded_by) VALUES (?, ?, ?, ?)');
                    $stmt->execute([$loanId, 'disbursement_proof', '/uploads/' . $filename, current_user()['id']]);
                }
            }
            // Audit log
            $stmt = $pdo->prepare('INSERT INTO audit_logs (user_id, action, entity, entity_id, meta) VALUES (?, ?, ?, ?, ?)');
            $stmt->execute([current_user()['id'], 'disburse', 'loan', $loanId, json_encode(['disbursed_at'=>$disbursedAt])]);
            $pdo->commit();
            $_SESSION['success'] = 'Pencairan berhasil dicatat.';
            header('Location: ' . route_url('index.php/disbursement'));
        } catch (\Exception $e) {
            $pdo->rollBack();
            $_SESSION['error'] = 'Gagal menyimpan: ' . $e->getMessage();
            header('Location: ' . route_url('disbursement/create?loan_id='.$loanId));
        }
    }
}
