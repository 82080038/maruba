<?php
namespace App\Controllers;
use App\Helpers\AuthHelper;

class SurveysController
{
    public function index(): void
    {
        require_login();
        AuthHelper::requirePermission('surveys', 'view');
        $title = 'Survei';
        $pdo = \App\Database::getConnection();
        $stmt = $pdo->prepare('
            SELECT s.*, l.amount, l.status AS loan_status, m.name AS member_name, u.name AS surveyor_name
            FROM surveys s
            JOIN loans l ON s.loan_id = l.id
            JOIN members m ON l.member_id = m.id
            LEFT JOIN users u ON s.surveyor_id = u.id
            ORDER BY s.created_at DESC
        ');
        $stmt->execute();
        $surveys = $stmt->fetchAll();
        include view_path('surveys/index');
    }

    public function create(): void
    {
        require_login();
        AuthHelper::requirePermission('surveys', 'create');
        $title = 'Tambah Survei';
        $pdo = \App\Database::getConnection();
        $stmt = $pdo->prepare('SELECT l.id, l.amount, m.name AS member_name FROM loans l JOIN members m ON l.member_id = m.id WHERE l.status IN ("draft","survey") ORDER BY l.created_at DESC');
        $stmt->execute();
        $loans = $stmt->fetchAll();
        include view_path('surveys/create');
    }

    public function store(): void
    {
        require_login();
        AuthHelper::requirePermission('surveys', 'create');
        $loanId = (int)($_POST['loan_id'] ?? 0);
        $result = trim($_POST['result'] ?? '');
        $score = (int)($_POST['score'] ?? 0);
        $lat = (float)($_POST['lat'] ?? 0);
        $lng = (float)($_POST['lng'] ?? 0);
        if (!$loanId || empty($result) || $score < 0 || $score > 100) {
            $_SESSION['error'] = 'Data tidak lengkap atau skor tidak valid (0-100).';
            header('Location: ' . route_url('surveys/create'));
            return;
        }
        $pdo = \App\Database::getConnection();
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare('INSERT INTO surveys (loan_id, surveyor_id, result, score, geo_lat, geo_lng) VALUES (?, ?, ?, ?, ?, ?)');
            $stmt->execute([$loanId, current_user()['id'], $result, $score, $lat, $lng]);
            // Update loan status to review
            $stmt = $pdo->prepare('UPDATE loans SET status = "review", assigned_surveyor_id = ? WHERE id = ?');
            $stmt->execute([current_user()['id'], $loanId]);
            $pdo->commit();
            $_SESSION['success'] = 'Survei berhasil disimpan.';
            header('Location: ' . route_url('surveys'));
        } catch (\Exception $e) {
            $pdo->rollBack();
            $_SESSION['error'] = 'Gagal menyimpan: ' . $e->getMessage();
            header('Location: ' . route_url('surveys/create'));
        }
    }
}
