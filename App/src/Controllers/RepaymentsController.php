<?php
namespace App\Controllers;
use App\Helpers\AuthHelper;

class RepaymentsController
{
    public function index(): void
    {
        require_login();
        AuthHelper::requirePermission('repayments', 'view');
        $title = 'Angsuran';
        $pdo = \App\Database::getConnection();
        $stmt = $pdo->prepare('
            SELECT r.*, l.amount AS loan_amount, l.tenor_months, m.name AS member_name, u.name AS collector_name
            FROM repayments r
            JOIN loans l ON r.loan_id = l.id
            JOIN members m ON l.member_id = m.id
            LEFT JOIN users u ON r.collector_id = u.id
            ORDER BY r.due_date DESC
        ');
        $stmt->execute();
        $repayments = $stmt->fetchAll();
        include view_path('repayments/index');
    }

    public function create(): void
    {
        require_login();
        AuthHelper::requirePermission('repayments', 'create');
        $title = 'Catat Pembayaran';
        $pdo = \App\Database::getConnection();
        $stmt = $pdo->prepare('
            SELECT r.id, r.due_date, r.amount_due, l.amount AS loan_amount, m.name AS member_name
            FROM repayments r
            JOIN loans l ON r.loan_id = l.id
            JOIN members m ON l.member_id = m.id
            WHERE r.status = "due" AND r.due_date <= CURDATE()
            ORDER BY r.due_date
        ');
        $stmt->execute();
        $due = $stmt->fetchAll();
        include view_path('repayments/create');
    }

    public function store(): void
    {
        require_login();
        AuthHelper::requirePermission('repayments', 'create');
        $repaymentId = (int)($_POST['repayment_id'] ?? 0);
        $amountPaid = (float)($_POST['amount_paid'] ?? 0);
        $method = trim($_POST['method'] ?? '');
        if (!$repaymentId || $amountPaid <= 0 || empty($method)) {
            $_SESSION['error'] = 'Data tidak lengkap.';
            header('Location: ' . route_url('repayments/create'));
            return;
        }
        $pdo = \App\Database::getConnection();
        $pdo->beginTransaction();
        try {
            // Update repayment
            $stmt = $pdo->prepare('UPDATE repayments SET amount_paid = ?, method = ?, paid_date = CURDATE(), status = "paid", collector_id = ? WHERE id = ?');
            $stmt->execute([$amountPaid, $method, current_user()['id'], $repaymentId]);
            // Handle proof upload if any
            if (!empty($_FILES['proof']['name']) && $_FILES['proof']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = __DIR__ . '/../../public/uploads';
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
                $ext = pathinfo($_FILES['proof']['name'], PATHINFO_EXTENSION);
                $filename = 'repayment_' . $repaymentId . '_' . time() . '.' . $ext;
                $dest = $uploadDir . '/' . $filename;
                if (move_uploaded_file($_FILES['proof']['tmp_name'], $dest)) {
                    $stmt = $pdo->prepare('UPDATE repayments SET proof_path = ? WHERE id = ?');
                    $stmt->execute(['/uploads/' . $filename, $repaymentId]);
                }
            }
            $pdo->commit();
            $_SESSION['success'] = 'Pembayaran berhasil dicatat.';
            header('Location: ' . route_url('repayments'));
        } catch (\Exception $e) {
            $pdo->rollBack();
            $_SESSION['error'] = 'Gagal menyimpan: ' . $e->getMessage();
            header('Location: ' . route_url('repayments/create'));
        }
    }
}
