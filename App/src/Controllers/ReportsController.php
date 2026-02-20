<?php
namespace App\Controllers;
use App\Helpers\AuthHelper;

class ReportsController
{
    public function index(): void
    {
        require_login();
        AuthHelper::requirePermission('reports', 'view');
        $title = 'Laporan';
        $pdo = \App\Database::getConnection();

        // Outstanding
        $stmt = $pdo->prepare('SELECT SUM(amount) AS total FROM loans WHERE status IN ("approved","disbursed")');
        $stmt->execute();
        $outstanding = $stmt->fetch()['total'] ?? 0;

        // NPL count
        $stmt = $pdo->prepare('SELECT COUNT(*) AS cnt FROM loans WHERE status = "default"');
        $stmt->execute();
        $nplCount = $stmt->fetch()['cnt'] ?? 0;

        // Total members
        $stmt = $pdo->prepare('SELECT COUNT(*) AS cnt FROM members WHERE status="active"');
        $stmt->execute();
        $membersCount = $stmt->fetch()['cnt'] ?? 0;

        // Loans by status
        $stmt = $pdo->prepare('SELECT status, COUNT(*) AS cnt FROM loans GROUP BY status');
        $stmt->execute();
        $loanStatus = $stmt->fetchAll();

        include view_path('reports/index');
    }

    public function export(): void
    {
        require_login();
        AuthHelper::requirePermission('reports', 'export');
        $pdo = \App\Database::getConnection();
        $stmt = $pdo->prepare('
            SELECT l.id, m.name AS member_name, p.name AS product_name, l.amount, l.tenor_months, l.rate, l.status, l.created_at
            FROM loans l
            JOIN members m ON l.member_id = m.id
            JOIN products p ON l.product_id = p.id
            ORDER BY l.created_at DESC
        ');
        $stmt->execute();
        $loans = $stmt->fetchAll();

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="loans_' . date('Y-m-d') . '.csv"');
        $out = fopen('php://output', 'w');
        fputcsv($out, ['ID', 'Anggota', 'Produk', 'Pinjaman', 'Tenor', 'Bunga', 'Status', 'Tanggal']);
        foreach ($loans as $row) {
            fputcsv($out, [
                $row['id'],
                $row['member_name'],
                $row['product_name'],
                $row['amount'],
                $row['tenor_months'],
                $row['rate'],
                $row['status'],
                $row['created_at']
            ]);
        }
        fclose($out);
        exit;
    }
}
