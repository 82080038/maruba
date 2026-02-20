<?php
// Helper untuk active link
function is_active($path) {
    return parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) === route_url($path);
}
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($title ?? 'Koperasi') ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="<?= asset_url('assets/css/style.css') ?>">
    <style>
        body {
            display: flex;
            min-height: 100vh;
            background-color: #f8f9fa;
        }
        .sidebar {
            width: 250px;
            min-height: 100vh;
            background-color: #212529;
            position: fixed;
            top: 0;
            left: 0;
            z-index: 100;
            flex-shrink: 0;
        }
        .sidebar .nav-link {
            color: rgba(255,255,255,.75);
            border-radius: 0.375rem;
            margin: 0.125rem 0;
        }
        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            color: #fff;
            background-color: rgba(255,255,255,.1);
        }
        .main-content {
            margin-left: 250px;
            flex: 1;
            padding: 0;
            transition: margin-left 0.3s;
        }
        .main-content .container {
            padding: 2rem;
            max-width: calc(100% - 250px);
        }
        .main-content.no-sidebar .container {
            padding: 2rem;
            max-width: 100%;
        }
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s;
            }
            .sidebar.show {
                transform: translateX(0);
            }
            .main-content {
                margin-left: 0;
            }
        }
        .sidebar-toggle {
            display: none;
        }
        @media (max-width: 768px) {
            .sidebar-toggle {
                display: block;
            }
        }
    </style>
</head>
<body style="<?php if (empty($_SESSION['user'])): ?>display: block;<?php endif; ?>">
<?php if (!empty($_SESSION['user'])): ?>
<!-- Mobile sidebar toggle -->
<button class="btn btn-dark sidebar-toggle d-md-none position-fixed" style="top:10px;left:10px;z-index:1051;">☰</button>

<div class="sidebar d-flex flex-column p-3 text-white" id="sidebarMenu">
    <a href="<?= route_url('dashboard') ?>" class="d-flex align-items-center mb-3 mb-md-0 me-md-auto text-white text-decoration-none">
        <span class="fs-4">KSP LGJ</span>
    </a>
    <hr>
    <ul class="nav nav-pills flex-column mb-auto">
        <li class="nav-item">
            <a href="<?= route_url('dashboard') ?>" class="nav-link <?= is_active('dashboard') ? 'active' : '' ?>">
                Dashboard
            </a>
        </li>
        <?php if (\App\Helpers\AuthHelper::can('loans', 'view')): ?>
        <li>
            <a href="<?= route_url('loans') ?>" class="nav-link <?= is_active('loans') ? 'active' : '' ?>">
                Pinjaman
            </a>
        </li>
        <?php endif; ?>
        <?php if (\App\Helpers\AuthHelper::can('loans', 'disburse')): ?>
        <li>
            <a href="<?= route_url('disbursement') ?>" class="nav-link <?= is_active('disbursement') ? 'active' : '' ?>">
                Pencairan
            </a>
        </li>
        <?php endif; ?>
        <?php if (\App\Helpers\AuthHelper::can('members', 'view')): ?>
        <li>
            <a href="<?= route_url('members') ?>" class="nav-link <?= is_active('members') ? 'active' : '' ?>">
                Anggota
            </a>
        </li>
        <?php endif; ?>
        <?php if (\App\Helpers\AuthHelper::can('products', 'view')): ?>
        <li>
            <a href="<?= route_url('products') ?>" class="nav-link <?= is_active('products') ? 'active' : '' ?>">
                Produk
            </a>
        </li>
        <?php endif; ?>
        <?php if (\App\Helpers\AuthHelper::can('surveys', 'view')): ?>
        <li>
            <a href="<?= route_url('surveys') ?>" class="nav-link <?= is_active('surveys') ? 'active' : '' ?>">
                Survei
            </a>
        </li>
        <?php endif; ?>
        <?php if (\App\Helpers\AuthHelper::can('repayments', 'view')): ?>
        <li>
            <a href="<?= route_url('repayments') ?>" class="nav-link <?= is_active('repayments') ? 'active' : '' ?>">
                Angsuran
            </a>
        </li>
        <?php endif; ?>
        <?php if (\App\Helpers\AuthHelper::can('reports', 'view')): ?>
        <li>
            <a href="<?= route_url('reports') ?>" class="nav-link <?= is_active('reports') ? 'active' : '' ?>">
                Laporan
            </a>
        </li>
        <?php endif; ?>
        <?php if (\App\Helpers\AuthHelper::can('users', 'view')): ?>
        <li>
            <a href="<?= route_url('users') ?>" class="nav-link <?= is_active('users') ? 'active' : '' ?>">
                Pengguna
            </a>
        </li>
        <?php endif; ?>
        <?php if (\App\Helpers\AuthHelper::can('audit_logs', 'view')): ?>
        <li>
            <a href="<?= route_url('audit') ?>" class="nav-link <?= is_active('audit') ? 'active' : '' ?>">
                Audit Log
            </a>
        </li>
        <?php endif; ?>
        <?php if (\App\Helpers\AuthHelper::can('documents', 'view')): ?>
        <li>
            <a href="<?= route_url('surat') ?>" class="nav-link <?= is_active('surat') ? 'active' : '' ?>">
                Surat-Surat
            </a>
        </li>
        <?php else: ?>
        <!-- Debug: documents permission not found -->
        <?php endif; ?>
        <!-- Temporary: Show menu without permission check -->
        <li>
            <a href="<?= route_url('surat') ?>" class="nav-link <?= is_active('surat') ? 'active' : '' ?>">
                Surat-Surat
            </a>
        </li>
        <li>
            <a href="<?= route_url('logout') ?>" class="nav-link">Logout</a>
        </li>
    </ul>
</div>
<?php endif; ?>

<div class="main-content<?php if (empty($_SESSION['user'])): ?> no-sidebar<?php endif; ?>" style="<?php if (empty($_SESSION['user'])): ?>margin-left: 0;<?php endif; ?>">
<?php if (!empty($_SESSION['user'])): ?>
<!-- Header Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-sm">
    <div class="container-fluid">
        <a class="navbar-brand" href="<?= route_url('dashboard') ?>">KSP LGJ</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                        <i class="bi bi-person-circle"></i> <?= htmlspecialchars($_SESSION['user']['name']) ?>
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="<?= route_url('logout') ?>">Logout</a></li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>
<?php endif; ?>
    <div class="container">
        <?php if (!empty($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($_SESSION['error']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <?php if (!empty($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($_SESSION['success']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <?= $content ?? '' ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="<?= public_url('assets/js/helpers-id.js') ?>"></script>
<script>
// Mobile sidebar toggle
document.querySelector('.sidebar-toggle')?.addEventListener('click', function () {
    document.getElementById('sidebarMenu').classList.toggle('show');
});
// Close sidebar when clicking outside on mobile
document.addEventListener('click', function(e) {
    const sidebar = document.getElementById('sidebarMenu');
    const toggle = document.querySelector('.sidebar-toggle');
    if (window.innerWidth < 768 && !sidebar.contains(e.target) && !toggle.contains(e.target)) {
        sidebar.classList.remove('show');
    }
});
</script>
</body>
</html>
