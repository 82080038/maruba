<?php
namespace App\Controllers;
use App\Helpers\AuthHelper;

class MembersController
{
    public function index(): void
    {
        require_login();
        AuthHelper::requirePermission('members', 'view');
        $title = 'Daftar Anggota';
        $pdo = \App\Database::getConnection();
        $stmt = $pdo->query('SELECT * FROM members ORDER BY name');
        $members = $stmt->fetchAll();
        include view_path('members/index');
    }

    public function create(): void
    {
        require_login();
        AuthHelper::requirePermission('members', 'create');
        $title = 'Tambah Anggota';
        include view_path('members/create');
    }

    public function store(): void
    {
        require_login();
        AuthHelper::requirePermission('members', 'create');
        verify_csrf();
        $name = trim($_POST['name'] ?? '');
        $nik = preg_replace('/\D+/', '', (string)($_POST['nik'] ?? ''));
        $phone = preg_replace('/\D+/', '', (string)($_POST['phone'] ?? ''));
        $address = trim($_POST['address'] ?? '');
        $lat = (float)($_POST['lat'] ?? 0);
        $lng = (float)($_POST['lng'] ?? 0);
        if (empty($name) || empty($nik) || empty($phone) || empty($address)) {
            $_SESSION['error'] = 'Data wajib diisi.';
            header('Location: ' . route_url('members/create'));
            return;
        }
        $pdo = \App\Database::getConnection();
        $stmt = $pdo->prepare('INSERT INTO members (name, nik, phone, address, lat, lng) VALUES (?, ?, ?, ?, ?, ?)');
        $stmt->execute([$name, $nik, $phone, $address, $lat, $lng]);
        $_SESSION['success'] = 'Anggota berhasil ditambahkan.';
        header('Location: ' . route_url('members'));
    }
}
