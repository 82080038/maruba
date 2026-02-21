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
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
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
            background-color: #0d6efd;
            position: fixed;
            top: 0;
            left: 0;
            z-index: 100;
            flex-shrink: 0;
        }
        .sidebar .nav-link {
            color: rgba(255,255,255,.85);
            border-radius: 0.375rem;
            margin: 0.125rem 0;
            padding: 0.75rem 1rem;
            font-weight: 500;
            transition: all 0.2s ease;
        }
        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            color: #fff;
            background-color: rgba(255,255,255,.2);
            font-weight: 600;
        }
        .sidebar .nav-link i {
            margin-right: 0.5rem;
        }
        .main-content {
            margin-left: 250px;
            flex: 1;
            padding: 0;
            transition: margin-left 0.3s;
            min-height: 100vh;
        }
        .main-content .container {
            padding: 5px;
            max-width: 100%;
            width: 100%;
        }
        .main-content.no-sidebar .container {
            padding: 5px;
            max-width: 100%;
            width: 100%;
        }
        /* Responsive Sidebar Behavior */
        @media (max-width: 991px) {
            /* Desktop sidebar dihidden di mobile */
            .sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s ease;
                position: fixed;
                top: 0;
                left: 0;
                z-index: 1040;
                box-shadow: 2px 0 10px rgba(0,0,0,0.1);
            }
            
            /* Mobile sidebar muncul saat toggle diklik */
            .sidebar.show {
                transform: translateX(0);
            }
            
            /* Main content menggunakan full width di mobile */
            .main-content {
                margin-left: 0;
                width: 100%;
            }
            
            /* Container padding di mobile */
            .main-content .container {
                padding: 10px;
            }
            
            /* Hide desktop sidebar di mobile */
            .sidebar {
                display: none !important;
            }
        }
        
        /* Desktop Sidebar Behavior */
        @media (min-width: 992px) {
            /* Desktop sidebar selalu visible */
            .sidebar {
                transform: translateX(0);
                position: fixed;
                top: 0;
                left: 0;
                box-shadow: 2px 0 10px rgba(0,0,0,0.1);
            }
            
            /* Main content memberikan margin untuk sidebar */
            .main-content {
                margin-left: 250px;
                width: calc(100% - 250px);
            }
            
            /* Hide mobile toggle button di desktop */
            #mobileSidebarToggle {
                display: none !important;
            }
            
            /* Hide mobile offcanvas di desktop */
            .offcanvas {
                display: none !important;
            }
        }
        
        /* Show mobile sidebar toggle button on small and medium screens */
        @media (max-width: 991px) {
            #mobileSidebarToggle {
                display: block !important;
                visibility: visible !important;
                opacity: 1 !important;
                position: relative !important;
                z-index: 1050 !important;
            }
            
            /* Hide desktop sidebar di mobile */
            .sidebar {
                display: none !important;
            }
        }
        
        /* Hide mobile sidebar toggle button on large screens */
        @media (min-width: 992px) {
            #mobileSidebarToggle {
                display: none !important;
            }
        }
        
        /* Offcanvas background color - Enhanced Design */
        .offcanvas {
            background-color: #0d6efd; /* Match header color */
            border-right: 1px solid rgba(255,255,255,0.1);
        }
        
        .offcanvas-header {
            background-color: #0a58ca; /* Darker header */
            border-bottom: 1px solid rgba(255,255,255,0.2);
            padding: 1rem;
        }
        
        .offcanvas-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: white;
            display: flex;
            align-items: center;
        }
        
        .offcanvas-body {
            background-color: #0d6efd;
            padding: 1rem;
        }
        
        /* Enhanced Offcanvas link styling */
        .offcanvas .nav-link {
            color: rgba(255,255,255,0.9) !important;
            padding: 12px 16px;
            margin: 4px 0;
            border-radius: 8px;
            transition: all 0.3s ease;
            font-weight: 500;
            display: flex;
            align-items: center;
            text-decoration: none;
            border: 1px solid transparent;
        }
        
        .offcanvas .nav-link i {
            margin-right: 12px;
            font-size: 1.1rem;
            width: 20px;
            text-align: center;
        }
        
        .offcanvas .nav-link:hover {
            color: white !important;
            background-color: rgba(255,255,255,0.15);
            border-color: rgba(255,255,255,0.3);
            transform: translateX(4px);
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .offcanvas .nav-link.active {
            color: white !important;
            background-color: rgba(255,255,255,0.25);
            border-color: rgba(255,255,255,0.4);
            box-shadow: 0 2px 12px rgba(0,0,0,0.15);
        }
        
        .offcanvas .nav-link:active {
            transform: translateX(2px) scale(0.98);
        }
        
        /* Enhanced close button */
        .btn-close-white {
            filter: brightness(0) invert(1);
            opacity: 0.8;
            transition: opacity 0.2s ease;
        }
        
        .btn-close-white:hover {
            opacity: 1;
        }
        
        /* Mobile menu item click feedback */
        #mainNavigation .nav-link.clicked {
            background-color: rgba(255,255,255,0.3) !important;
            transform: scale(0.95);
            transition: all 0.2s ease;
        }
        
        /* Ensure menu items are clickable on mobile */
        #mainNavigation .nav-link {
            cursor: pointer;
            user-select: none;
            -webkit-tap-highlight-color: rgba(255,255,255,0.2);
        }
        
        /* Mobile menu touch feedback */
        @media (max-width: 991px) {
            #mainNavigation .nav-link:active {
                background-color: rgba(255,255,255,0.2) !important;
            }
        }
        .hamburger-icon {
            width: 20px;
            height: 14px;
            position: relative;
            display: inline-block;
        }
        
        .hamburger-icon span {
            display: block;
            position: absolute;
            height: 2px;
            width: 100%;
            background: white;
            border-radius: 1px;
            opacity: 1;
            left: 0;
            transform: rotate(0deg);
            transition: .25s ease-in-out;
        }
        
        .hamburger-icon span:nth-child(1) {
            top: 0px;
        }
        
        .hamburger-icon span:nth-child(2) {
            top: 6px;
        }
        
        .hamburger-icon span:nth-child(3) {
            top: 12px;
        }
        
        /* Ensure button is visible on mobile */
        #mobileSidebarToggle {
            min-width: 40px;
            min-height: 40px;
            touch-action: manipulation; /* Improves touch responsiveness */
            -webkit-tap-highlight-color: rgba(255,255,255,0.2); /* Visual feedback */
        }
        
        #mobileSidebarToggle svg {
            width: 20px;
            height: 20px;
        }
        @media (max-width: 576px) {
            .main-content .container {
                padding: 5px;
            }
            .card-body {
                padding: 1rem;
            }
        }
        .sidebar-toggle {
            display: none;
        }
        @media (max-width: 991px) {
            .sidebar-toggle {
                display: block;
            }
        }
        
        /* Mobile sidebar backdrop */
        .sidebar-backdrop {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 1035;
        }
        
        @media (max-width: 991px) {
            .sidebar-backdrop.show {
                display: block;
            }
        }
        
        /* Mobile navigation improvements */
        @media (max-width: 991px) {
            .navbar-brand {
                font-size: 1.1rem;
            }
            
            .navbar-nav .nav-link {
                padding: 0.5rem 1rem;
            }
            
            .dropdown-menu {
                border: none;
                box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
            }
            
            /* Touch device adjustments */
            .touch-device .nav-link,
            .touch-device .dropdown-item {
                min-height: 44px; /* iOS touch target size */
                display: flex;
                align-items: center;
            }
            
            /* Mobile sidebar menu improvements */
            #mainSidebar .nav-link {
                padding: 0.75rem 1rem;
                margin: 0.125rem 0;
                border-radius: 0.375rem;
                font-size: 0.95rem;
                transition: all 0.2s ease;
                display: flex;
                align-items: center;
                min-height: 44px; /* iOS touch target */
            }
            
            #mainSidebar .nav-link i {
                margin-right: 0.75rem;
                font-size: 1rem;
                width: 1.25rem;
                text-align: center;
            }
            
            #mainSidebar .nav-link:hover,
            #mainSidebar .nav-link.active {
                background-color: rgba(255,255,255,0.2);
                font-weight: 600;
                transform: translateX(2px);
            }
            
            #mainSidebar .nav-link:active {
                transform: translateX(4px);
                background-color: rgba(255,255,255,0.3);
            }
            
            /* Mobile sidebar brand improvements */
            #sidebarBrand {
                padding: 0.75rem 1rem;
                border-radius: 0.375rem;
                transition: all 0.2s ease;
                min-height: 44px;
            }
            
            #sidebarBrand:hover {
                background-color: rgba(255,255,255,0.1);
            }
            
            /* Mobile sidebar list improvements */
            #mainNavigation {
                padding: 0.5rem 0;
            }
            
            #mainNavigation .nav-item {
                margin-bottom: 0.25rem;
            }
            
            /* Mobile menu click feedback */
            #mainSidebar .nav-link.clicked {
                background-color: rgba(255,255,255,0.3);
                transform: translateX(4px);
            }
            
            /* Ripple effect for mobile menu */
            .ripple {
                position: absolute;
                border-radius: 50%;
                background-color: rgba(255,255,255,0.6);
                transform: scale(0);
                animation: ripple-animation 0.6s ease-out;
                pointer-events: none;
            }
            
            @keyframes ripple-animation {
                to {
                    transform: scale(4);
                    opacity: 0;
                }
            }
            
            /* Mobile sidebar scroll improvements */
            #mainSidebar {
                overflow-y: auto;
                -webkit-overflow-scrolling: touch;
            }
            
            /* Better touch feedback */
            #mainSidebar .nav-link:active {
                background-color: rgba(255,255,255,0.25);
                transform: translateX(3px) scale(0.98);
            }
        }
        
        /* Ensure proper z-index for mobile */
        @media (max-width: 991px) {
            #mainHeader {
                z-index: 1030;
            }
            
            #mainSidebar {
                z-index: 1040;
            }
            
            #mobileSidebarToggle {
                z-index: 1050;
            }
        }
    </style>
</head>
<body id="mainBody" style="<?php if (empty($_SESSION['user'])): ?>display: block;<?php endif; ?>">
<?php if (!empty($_SESSION['user'])): ?>
<!-- Mobile sidebar using Bootstrap 5 Offcanvas - Enhanced Design -->
<div class="offcanvas offcanvas-start" tabindex="-1" id="mobileSidebar" aria-labelledby="mobileSidebarLabel" style="width: 280px;">
    <div class="offcanvas-header">
        <h5 class="offcanvas-title text-white" id="mobileSidebarLabel">
            <i class="bi bi-building me-2"></i> KSP LGJ
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body">
        <!-- User Info Section -->
        <div class="text-white mb-3 p-3 rounded" style="background-color: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.2);">
            <div class="d-flex align-items-center">
                <i class="bi bi-person-circle fs-3 me-3"></i>
                <div>
                    <div class="fw-semibold"><?= htmlspecialchars($_SESSION['user']['name']) ?></div>
                    <div class="small opacity-75"><?= htmlspecialchars($_SESSION['user']['role']) ?></div>
                </div>
            </div>
        </div>
        
        <!-- Navigation Menu -->
        <ul class="nav nav-pills flex-column mb-auto" id="mobileNavigation">
            <li class="nav-item">
                <a href="<?= route_url('dashboard') ?>" class="nav-link text-white <?= is_active('dashboard') ? 'active' : '' ?>" id="mobileNavDashboard">
                    <i class="bi bi-speedometer2"></i> Dashboard
                </a>
            </li>
            <?php if (\App\Helpers\AuthHelper::can('loans', 'view')): ?>
            <li class="nav-item">
                <a href="<?= route_url('loans') ?>" class="nav-link text-white <?= is_active('loans') ? 'active' : '' ?>" id="mobileNavLoans">
                    <i class="bi bi-cash-stack"></i> Pinjaman
                </a>
            </li>
            <?php endif; ?>
            <?php if (\App\Helpers\AuthHelper::can('loans', 'disburse')): ?>
            <li class="nav-item">
                <a href="<?= route_url('disbursement') ?>" class="nav-link text-white <?= is_active('disbursement') ? 'active' : '' ?>" id="mobileNavDisbursement">
                    <i class="bi bi-cash"></i> Pencairan
                </a>
            </li>
            <?php endif; ?>
            <?php if (\App\Helpers\AuthHelper::can('members', 'view')): ?>
            <li class="nav-item">
                <a href="<?= route_url('members') ?>" class="nav-link text-white <?= is_active('members') ? 'active' : '' ?>" id="mobileNavMembers">
                    <i class="bi bi-people"></i> Anggota
                </a>
            </li>
            <?php endif; ?>
            <?php if (\App\Helpers\AuthHelper::can('products', 'view')): ?>
            <li class="nav-item">
                <a href="<?= route_url('products') ?>" class="nav-link text-white <?= is_active('products') ? 'active' : '' ?>" id="mobileNavProducts">
                    <i class="bi bi-box"></i> Produk
                </a>
            </li>
            <?php endif; ?>
            <?php if (\App\Helpers\AuthHelper::can('surveys', 'view')): ?>
            <li class="nav-item">
                <a href="<?= route_url('surveys') ?>" class="nav-link text-white <?= is_active('surveys') ? 'active' : '' ?>" id="mobileNavSurveys">
                    <i class="bi bi-clipboard-check"></i> Survei
                </a>
            </li>
            <?php endif; ?>
            <?php if (\App\Helpers\AuthHelper::can('repayments', 'view')): ?>
            <li class="nav-item">
                <a href="<?= route_url('repayments') ?>" class="nav-link text-white <?= is_active('repayments') ? 'active' : '' ?>" id="mobileNavRepayments">
                    <i class="bi bi-wallet2"></i> Angsuran
                </a>
            </li>
            <?php endif; ?>
            <?php if (\App\Helpers\AuthHelper::can('reports', 'view')): ?>
            <li class="nav-item">
                <a href="<?= route_url('reports') ?>" class="nav-link text-white <?= is_active('reports') ? 'active' : '' ?>" id="mobileNavReports">
                    <i class="bi bi-file-bar-graph"></i> Laporan
                </a>
            </li>
            <?php endif; ?>
            <?php if (\App\Helpers\AuthHelper::can('users', 'view')): ?>
            <li class="nav-item">
                <a href="<?= route_url('users') ?>" class="nav-link text-white <?= is_active('users') ? 'active' : '' ?>" id="mobileNavUsers">
                    <i class="bi bi-person-gear"></i> Pengguna
                </a>
            </li>
            <?php endif; ?>
            <?php if (\App\Helpers\AuthHelper::can('audit_logs', 'view')): ?>
            <li class="nav-item">
                <a href="<?= route_url('audit') ?>" class="nav-link text-white <?= is_active('audit') ? 'active' : '' ?>" id="mobileNavAudit">
                    <i class="bi bi-clock-history"></i> Audit Log
                </a>
            </li>
            <?php endif; ?>
            <li class="nav-item">
                <a href="<?= route_url('surat') ?>" class="nav-link text-white <?= is_active('surat') ? 'active' : '' ?>" id="mobileNavDocuments">
                    <i class="bi bi-file-text"></i> Surat-Surat
                </a>
            </li>
        </ul>
        
        <!-- Logout Section -->
        <div class="mt-4 pt-3 border-top border-white-20">
            <a href="<?= route_url('logout') ?>" class="nav-link text-white d-flex align-items-center" id="mobileNavLogout">
                <i class="bi bi-box-arrow-right me-2"></i> Logout
            </a>
        </div>
    </div>
</div>

<!-- Desktop Sidebar - RESTORED -->
<div class="sidebar d-flex flex-column p-3 text-white" id="mainSidebar">
    <a href="<?= route_url('dashboard') ?>" class="d-flex align-items-center mb-3 mb-md-0 me-md-auto text-white text-decoration-none" id="sidebarBrand">
        <i class="bi bi-building fs-4 me-2"></i>
        <span class="fs-4 fw-bold">KSP LGJ</span>
    </a>
    <hr style="border-color: rgba(255,255,255,0.2);">
    <ul class="nav nav-pills flex-column mb-auto" id="mainNavigation">
        <li class="nav-item">
            <a href="<?= route_url('dashboard') ?>" class="nav-link <?= is_active('dashboard') ? 'active' : '' ?>" id="navDashboard">
                <i class="bi bi-speedometer2"></i> Dashboard
            </a>
        </li>
        <?php if (\App\Helpers\AuthHelper::can('loans', 'view')): ?>
        <li>
            <a href="<?= route_url('loans') ?>" class="nav-link <?= is_active('loans') ? 'active' : '' ?>" id="navLoans">
                <i class="bi bi-cash-stack"></i> Pinjaman
            </a>
        </li>
        <?php endif; ?>
        <?php if (\App\Helpers\AuthHelper::can('loans', 'disburse')): ?>
        <li>
            <a href="<?= route_url('disbursement') ?>" class="nav-link <?= is_active('disbursement') ? 'active' : '' ?>" id="navDisbursement">
                <i class="bi bi-cash"></i> Pencairan
            </a>
        </li>
        <?php endif; ?>
        <?php if (\App\Helpers\AuthHelper::can('members', 'view')): ?>
        <li>
            <a href="<?= route_url('members') ?>" class="nav-link <?= is_active('members') ? 'active' : '' ?>" id="navMembers">
                <i class="bi bi-people"></i> Anggota
            </a>
        </li>
        <?php endif; ?>
        <?php if (\App\Helpers\AuthHelper::can('products', 'view')): ?>
        <li>
            <a href="<?= route_url('products') ?>" class="nav-link <?= is_active('products') ? 'active' : '' ?>" id="navProducts">
                <i class="bi bi-box"></i> Produk
            </a>
        </li>
        <?php endif; ?>
        <?php if (\App\Helpers\AuthHelper::can('surveys', 'view')): ?>
        <li>
            <a href="<?= route_url('surveys') ?>" class="nav-link <?= is_active('surveys') ? 'active' : '' ?>" id="navSurveys">
                <i class="bi bi-clipboard-check"></i> Survei
            </a>
        </li>
        <?php endif; ?>
        <?php if (\App\Helpers\AuthHelper::can('repayments', 'view')): ?>
        <li>
            <a href="<?= route_url('repayments') ?>" class="nav-link <?= is_active('repayments') ? 'active' : '' ?>" id="navRepayments">
                <i class="bi bi-wallet2"></i> Angsuran
            </a>
        </li>
        <?php endif; ?>
        <?php if (\app\Helpers\AuthHelper::can('reports', 'view')): ?>
        <li>
            <a href="<?= route_url('reports') ?>" class="nav-link <?= is_active('reports') ? 'active' : '' ?>" id="navReports">
                <i class="bi bi-file-bar-graph"></i> Laporan
            </a>
        </li>
        <?php endif; ?>
        <?php if (\App\Helpers\AuthHelper::can('users', 'view')): ?>
        <li>
            <a href="<?= route_url('users') ?>" class="nav-link <?= is_active('users') ? 'active' : '' ?>" id="navUsers">
                <i class="bi bi-person-gear"></i> Pengguna
            </a>
        </li>
        <?php endif; ?>
        <?php if (\App\Helpers\AuthHelper::can('audit_logs', 'view')): ?>
        <li>
            <a href="<?= route_url('audit') ?>" class="nav-link <?= is_active('audit') ? 'active' : '' ?>" id="navAudit">
                <i class="bi bi-clock-history"></i> Audit Log
            </a>
        </li>
        <?php endif; ?>
        <li>
            <a href="<?= route_url('surat') ?>" class="nav-link <?= is_active('surat') ? 'active' : '' ?>" id="navDocuments">
                <i class="bi bi-file-text"></i> Surat-Surat
            </a>
        </li>
        <li>
            <a href="<?= route_url('logout') ?>" class="nav-link" id="navLogout">
                <i class="bi bi-box-arrow-right"></i> Logout
            </a>
        </li>
    </ul>
</div>
<?php endif; ?>

<div class="main-content<?php if (empty($_SESSION['user'])): ?> no-sidebar<?php endif; ?>" id="mainContent" style="<?php if (empty($_SESSION['user'])): ?>margin-left: 0;<?php endif; ?>">
<?php if (!empty($_SESSION['user'])): ?>
<!-- Header Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark shadow-sm" id="mainHeader" style="background-color: #0d6efd;">
    <div class="container-fluid" id="headerContainer">
        <!-- Mobile menu toggle for sidebar - Bootstrap 5 Offcanvas -->
        <button class="btn me-2 d-block d-lg-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#mobileSidebar" aria-controls="mobileSidebar" style="background-color: rgba(255,255,255,0.1); color: white; border: 1px solid rgba(255,255,255,0.2); align-items: center; justify-content: center; width: auto; height: 40px; font-size: 1.25rem; padding: 0 12px;" title="Menu">
            <!-- Primary: SVG Icon -->
            <svg width="20" height="20" viewBox="0 0 16 16" fill="currentColor" style="display: block;">
                <path fill-rule="evenodd" d="M2.5 12a.5.5 0 0 1 .5-.5h10a.5.5 0 0 1 0 1H3a.5.5 0 0 1-.5-.5zm0-4a.5.5 0 0 1 .5-.5h10a.5.5 0 0 1 0 1H3a.5.5 0 0 1-.5-.5zm0-4a.5.5 0 0 1 .5-.5h10a.5.5 0 0 1 0 1H3a.5.5 0 0 1-.5-.5z"></path>
            </svg>
            <!-- Fallback 1: Bootstrap Icon -->
            <i class="bi bi-list fs-5" style="display: none;"></i>
            <!-- Fallback 2: Text Hamburger -->
            <span style="display: none;">☰</span>
            <!-- Fallback 3: CSS Hamburger -->
            <div class="hamburger-icon" style="display: none;">
                <span></span>
                <span></span>
                <span></span>
            </div>
            <!-- Label for clarity - ALWAYS VISIBLE ON MOBILE -->
            <span class="ms-2" style="font-size: 0.875rem; display: inline-block;">MENU</span>
        </button>
        
        <a class="navbar-brand fw-bold" href="<?= route_url('dashboard') ?>" id="headerBrand" style="color: #fff;">
            <i class="bi bi-building me-2"></i> KSP LGJ
        </a>
        
        <!-- User dropdown (no hamburger needed) -->
        <div class="dropdown d-block d-lg-none">
            <a class="nav-link dropdown-toggle" href="#" id="userDropdownToggle" role="button" data-bs-toggle="dropdown" aria-expanded="false" style="color: #fff; padding: 0.5rem;">
                <i class="bi bi-person-circle me-1"></i> <?= htmlspecialchars($_SESSION['user']['name']) ?>
            </a>
            <ul class="dropdown-menu dropdown-menu-end" id="userDropdownMenu">
                <li><a class="dropdown-item" href="<?= route_url('logout') ?>" id="headerLogout">
                    <i class="bi bi-box-arrow-right me-2"></i> Logout
                </a></li>
            </ul>
        </div>
        
        <!-- Desktop user menu -->
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto" id="headerNav">
                <li class="nav-item dropdown d-none d-lg-block">
                    <a class="nav-link dropdown-toggle" href="#" id="userDropdownToggleDesktop" role="button" data-bs-toggle="dropdown" aria-expanded="false" style="color: #fff;">
                        <i class="bi bi-person-circle me-1"></i> <?= htmlspecialchars($_SESSION['user']['name']) ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end" id="userDropdownMenuDesktop">
                        <li><a class="dropdown-item" href="<?= route_url('logout') ?>" id="headerLogoutDesktop">
                            <i class="bi bi-box-arrow-right me-2"></i> Logout
                        </a></li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>
<?php endif; ?>
    <div class="container" id="mainContainer">
        <?php if (!empty($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert" id="errorAlert">
                <?= htmlspecialchars($_SESSION['error']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <?php if (!empty($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert" id="successAlert">
                <?= htmlspecialchars($_SESSION['success']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <?= $content ?? '' ?>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= public_url('assets/js/helpers-id.js') ?>"></script>
<script src="<?= asset_url('assets/js/dom-helpers.js') ?>"></script>
<script>
// Ensure jQuery is loaded before running scripts
(function($) {
    'use strict';
    
    // Close sidebar when clicking on backdrop
    $('#sidebarBackdrop').on('click', function () {
        $('#mainSidebar').removeClass('show');
        $('#sidebarBackdrop').removeClass('show');
    });

    // Close sidebar when clicking outside on mobile
    $(document).on('click', function(e) {
        const sidebar = $('#mainSidebar');
        const backdrop = $('#sidebarBackdrop');
        const toggle = $('#mobileSidebarToggle');
        const isMobile = window.innerWidth < 992; // Bootstrap lg breakpoint
        
        if (isMobile && !sidebar.is(e.target) && sidebar.has(e.target).length === 0 && 
            !toggle.is(e.target) && toggle.has(e.target).length === 0 && 
            !backdrop.is(e.target)) {
            sidebar.removeClass('show');
            backdrop.removeClass('show');
        }
    });

    // Close sidebar when navigating to a new page
    $('#mainNavigation .nav-link').on('click', function(e) {
        const isMobile = window.innerWidth < 992;
        
        console.log('Desktop menu clicked:', $(this).text().trim(), 'Mobile mode:', isMobile);
        
        if (isMobile) {
            // Add visual feedback for mobile menu click
            $(this).addClass('clicked');
            setTimeout(() => {
                $(this).removeClass('clicked');
            }, 200);
            
            // Close sidebar after a short delay for better UX
            setTimeout(() => {
                $('#mainSidebar').removeClass('show');
                $('#sidebarBackdrop').removeClass('show');
                console.log('Sidebar closed after menu click');
            }, 150);
        }
        
        // Update active state
        $('#mainNavigation .nav-link').removeClass('active');
        $(this).addClass('active');
        
        // Allow default navigation to proceed (don't preventDefault)
        // This ensures the link actually navigates to the target page
    });

    // Handle sidebar brand click
    $('#sidebarBrand').on('click', function(e) {
        const isMobile = window.innerWidth < 992;
        
        if (isMobile) {
            // Close sidebar when brand is clicked on mobile
            $('#mainSidebar').removeClass('show');
            $('#sidebarBackdrop').removeClass('show');
            console.log('Sidebar brand clicked - sidebar closed');
        }
    });

    // Close sidebar when navigating to a new page
    $('#mainNavigation .nav-link').on('click', function(e) {
        const isMobile = window.innerWidth < 992;
        
        console.log('Menu clicked:', $(this).text().trim(), 'Mobile mode:', isMobile);
        console.log('Event target:', e.target);
        console.log('Link href:', $(this).attr('href'));
        
        if (isMobile) {
            // Add visual feedback for mobile menu click
            $(this).addClass('clicked');
            setTimeout(() => {
                $(this).removeClass('clicked');
            }, 200);
            
            // Close sidebar after a short delay for better UX
            setTimeout(() => {
                $('#mainSidebar').removeClass('show');
                $('#sidebarBackdrop').removeClass('show');
                console.log('Sidebar closed after menu click');
            }, 150);
        }
        
        // Update active state
        $('#mainNavigation .nav-link').removeClass('active');
        $(this).addClass('active');
        
        // Allow default navigation to proceed (don't preventDefault)
        // This ensures the link actually navigates to the target page
    });

    // Handle sidebar brand click
    $('#sidebarBrand').on('click', function(e) {
        const isMobile = window.innerWidth < 992;
        
        if (isMobile) {
            // Close sidebar when brand is clicked on mobile
            $('#mainSidebar').removeClass('show');
            $('#sidebarBackdrop').removeClass('show');
            console.log('Sidebar brand clicked - sidebar closed');
        }
    });

    // Handle logout link
    $('#navLogout').on('click', function(e) {
        const isMobile = window.innerWidth < 992;
        
        if (isMobile) {
            // Close sidebar immediately for logout
            $('#mainSidebar').removeClass('show');
            $('#sidebarBackdrop').removeClass('show');
            console.log('Logout clicked - sidebar closed');
        }
    });

    // Add mobile-specific menu interactions
    if (window.innerWidth < 992) {
        // Add ripple effect for mobile menu items
        $('#mainSidebar .nav-link').on('click', function(e) {
            const ripple = $('<span class="ripple"></span>');
            const size = Math.max(this.offsetWidth, this.offsetHeight);
            const rect = this.getBoundingClientRect();
            
            ripple.css({
                width: size,
                height: size,
                left: e.clientX - rect.left - size / 2,
                top: e.clientY - rect.top - size / 2
            });
            
            $(this).append(ripple);
            
            setTimeout(() => {
                ripple.remove();
            }, 600);
        });
    }

    // Handle window resize - ensure proper responsive behavior
    $(window).on('resize', function() {
        const windowWidth = window.innerWidth;
        const isMobile = windowWidth < 992;
        const sidebar = $('#mainSidebar');
        const backdrop = $('#sidebarBackdrop');
        
        console.log('Window resized to:', windowWidth, 'isMobile:', isMobile);
        
        if (!isMobile) {
            // Desktop behavior - show sidebar, hide mobile elements
            sidebar.removeClass('show').addClass('desktop-mode');
            backdrop.removeClass('show');
            $('#navbarNav').removeClass('show');
            console.log('Switched to desktop mode - sidebar visible');
        } else {
            // Mobile behavior - hide sidebar by default
            sidebar.removeClass('show').removeClass('desktop-mode');
            backdrop.removeClass('show');
            console.log('Switched to mobile mode - sidebar hidden');
        }
    });

    // User dropdown enhancements
    $('#userDropdownToggle').on('click', function(e) {
        e.preventDefault();
        $('#userDropdownMenu').toggleClass('show');
        
        // Close sidebar when dropdown opens on mobile
        const isMobile = window.innerWidth < 992;
        if (isMobile) {
            $('#mainSidebar').removeClass('show');
            $('#sidebarBackdrop').removeClass('show');
        }
    });

    // Close dropdown when clicking outside
    $(document).on('click', function(e) {
        if (!$('#userDropdownToggle').is(e.target) && $('#userDropdownToggle').has(e.target).length === 0 && 
            !$('#userDropdownMenu').is(e.target) && $('#userDropdownMenu').has(e.target).length === 0) {
            $('#userDropdownMenu').removeClass('show');
        }
    });

    // Close dropdown when Escape key is pressed
    $(document).on('keydown', function(e) {
        if (e.key === 'Escape') {
            $('#userDropdownMenu').removeClass('show');
            if (window.innerWidth < 992) {
                $('#mainSidebar').removeClass('show');
                $('#sidebarBackdrop').removeClass('show');
            }
        }
    });

    // Auto-hide alerts after 5 seconds
    setTimeout(function() {
        $('#errorAlert, #successAlert').fadeOut('slow');
    }, 5000);

    // Global AJAX error handler
    $(document).ajaxError(function(event, jqXHR, ajaxSettings, thrownError) {
        const errorMsg = 'Terjadi kesalahan. Silakan coba lagi.';
        // Create error alert if it doesn't exist
        if (!$('#errorAlert').length) {
            $('#mainContainer').prepend('<div class="alert alert-danger alert-dismissible fade show" role="alert" id="errorAlert">' + errorMsg + '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>');
        }
    });

    // Initialize tooltips
    $(function () {
        $('[data-toggle="tooltip"]').tooltip();
    });

    // Handle touch events for mobile
    if ('ontouchstart' in window) {
        $('body').addClass('touch-device');
    }

    // Debug: Check responsive sidebar behavior
    $(document).ready(function() {
        console.log('=== Mobile Sidebar Toggle Debug ===');
        console.log('Window width:', window.innerWidth);
        console.log('Should be mobile:', window.innerWidth < 992);
        
        // Check button existence and visibility
        var toggleButton = $('#mobileSidebarToggle');
        console.log('Toggle button found:', toggleButton.length);
        
        if (toggleButton.length > 0) {
            console.log('Button CSS display:', toggleButton.css('display'));
            console.log('Button CSS visibility:', toggleButton.css('visibility'));
            console.log('Button position:', toggleButton.css('position'));
            console.log('Button z-index:', toggleButton.css('z-index'));
            console.log('Button width:', toggleButton.css('width'));
            console.log('Button height:', toggleButton.css('height'));
            console.log('Button background:', toggleButton.css('background-color'));
            
            // Check if button is actually visible on screen
            var buttonOffset = toggleButton.offset();
            console.log('Button position on page:', buttonOffset);
            console.log('Button is visible:', buttonOffset.top > 0 && buttonOffset.left > 0);
            
            // Force button to be visible on mobile
            if (window.innerWidth < 992) {
                toggleButton.css({
                    'display': 'block',
                    'visibility': 'visible',
                    'opacity': '1',
                    'position': 'relative',
                    'z-index': '1050'
                });
                console.log('Forced mobile visibility');
            }
            
            // FALLBACK SYSTEM: Check and fix icon visibility
            console.log('=== FALLBACK SYSTEM START ===');
            
            // Check SVG icon
            var svgIcon = toggleButton.find('svg');
            var menuLabel = toggleButton.find('span').last(); // Get the MENU label
            console.log('SVG icon found:', svgIcon.length);
            console.log('MENU label found:', menuLabel.length);
            
            if (svgIcon.length > 0) {
                var svgDisplay = svgIcon.css('display');
                var svgFill = svgIcon.css('fill');
                console.log('SVG display:', svgDisplay);
                console.log('SVG fill:', svgFill);
                
                // Check if SVG is actually visible
                if (svgDisplay === 'none' || svgDisplay === 'hidden' || svgFill === 'none' || svgFill === 'transparent') {
                    console.log('SVG not visible, trying fallback 1: Bootstrap Icon');
                    svgIcon.hide();
                    toggleButton.find('.bi-list').show();
                    menuLabel.show(); // Ensure MENU label stays visible
                } else {
                    // Add border to test visibility
                    svgIcon.css('border', '1px solid red');
                    menuLabel.css('border', '1px solid red');
                    setTimeout(function() {
                        svgIcon.css('border', 'none');
                        menuLabel.css('border', 'none');
                    }, 2000);
                    console.log('SVG should be visible (red border test)');
                }
            } else {
                console.log('SVG not found, trying fallback 1: Bootstrap Icon');
                toggleButton.find('.bi-list').show();
                menuLabel.show(); // Ensure MENU label stays visible
            }
            
            // Check Bootstrap icon fallback
            var bootstrapIcon = toggleButton.find('.bi-list');
            if (bootstrapIcon.is(':visible')) {
                console.log('Bootstrap icon fallback active');
                bootstrapIcon.css('border', '1px solid green');
                menuLabel.css('border', '1px solid green');
                setTimeout(function() {
                    bootstrapIcon.css('border', 'none');
                    menuLabel.css('border', 'none');
                }, 2000);
            } else {
                console.log('Bootstrap icon not visible, trying fallback 2: Text');
                bootstrapIcon.hide();
                toggleButton.find('span:not(:last)').show(); // Show hamburger text, not MENU label
                menuLabel.show(); // Ensure MENU label stays visible
            }
            
            // Check text fallback
            var textFallback = toggleButton.find('span:not(:last)'); // Get hamburger text, not MENU label
            if (textFallback.is(':visible')) {
                console.log('Text fallback active (☰)');
                textFallback.css('border', '1px solid blue');
                menuLabel.css('border', '1px solid blue');
                setTimeout(function() {
                    textFallback.css('border', 'none');
                    menuLabel.css('border', 'none');
                }, 2000);
            } else {
                console.log('Text fallback not visible, trying fallback 3: CSS Hamburger');
                textFallback.hide();
                toggleButton.find('.hamburger-icon').show();
                menuLabel.show(); // Ensure MENU label stays visible
            }
            
            // Check CSS hamburger fallback
            var cssHamburger = toggleButton.find('.hamburger-icon');
            if (cssHamburger.is(':visible')) {
                console.log('CSS hamburger fallback active');
                cssHamburger.css('border', '1px solid yellow');
                menuLabel.css('border', '1px solid yellow');
                setTimeout(function() {
                    cssHamburger.css('border', 'none');
                    menuLabel.css('border', 'none');
                }, 2000);
            }
            
            // Final check: Ensure MENU label is always visible
            if (!menuLabel.is(':visible')) {
                console.log('MENU label not visible, forcing it to show');
                menuLabel.css('display', 'inline-block').show();
            }
            
            console.log('=== FALLBACK SYSTEM COMPLETE ===');
        }
        
        // Test click functionality with multiple event handlers
        toggleButton.off('click touchstart').on('click touchstart', function(e) {
            e.preventDefault();
            console.log('=== TOGGLE BUTTON CLICKED! ===');
            console.log('Event type:', e.type);
            console.log('Event triggered on:', e.target);
            
            var sidebar = $('#mainSidebar');
            var backdrop = $('#sidebarBackdrop');
            
            console.log('Sidebar element found:', sidebar.length);
            console.log('Backdrop element found:', backdrop.length);
            console.log('Sidebar classes before:', sidebar.attr('class'));
            console.log('Sidebar CSS transform before:', sidebar.css('transform'));
            
            // Toggle sidebar
            sidebar.toggleClass('show');
            backdrop.toggleClass('show');
            
            console.log('Sidebar classes after:', sidebar.attr('class'));
            console.log('Sidebar CSS transform after:', sidebar.css('transform'));
            console.log('Sidebar is now visible:', sidebar.hasClass('show'));
            
            // Add visual feedback
            if (sidebar.hasClass('show')) {
                toggleButton.css('background-color', 'rgba(255,255,255,0.3)');
                console.log('Sidebar opened - button highlighted');
            } else {
                toggleButton.css('background-color', 'rgba(255,255,255,0.1)');
                console.log('Sidebar closed - button normal');
            }
        });
        
        // Alternative: Test with mousedown/touchstart
        toggleButton.on('mousedown touchstart', function(e) {
            console.log('Mouse/Touch event detected on toggle button');
            $(this).css('transform', 'scale(0.95)');
        });
        
        toggleButton.on('mouseup touchend', function(e) {
            console.log('Mouse/Touch released on toggle button');
            $(this).css('transform', 'scale(1)');
        });
        
        // Event delegation for better reliability
        $(document).on('click touchstart', '#mobileSidebarToggle', function(e) {
            e.preventDefault();
            console.log('=== DELEGATED TOGGLE BUTTON CLICKED! ===');
            console.log('Event type:', e.type);
            console.log('Delegation working!');
            
            var sidebar = $('#mainSidebar');
            var backdrop = $('#sidebarBackdrop');
            
            // Toggle sidebar
            sidebar.toggleClass('show');
            backdrop.toggleClass('show');
            
            // Visual feedback
            if (sidebar.hasClass('show')) {
                $(this).css('background-color', 'rgba(255,255,255,0.3)');
            } else {
                $(this).css('background-color', 'rgba(255,255,255,0.1)');
            }
        });
        
        console.log('=== Mobile Menu Setup Complete ===');
    });
    
})(jQuery);

$(document).ready(function() {
    console.log('=== Dual Navigation System ===');
    console.log('Window width:', window.innerWidth);
    console.log('Bootstrap offcanvas available:', typeof bootstrap !== 'undefined');
    
    // Test offcanvas functionality
    var offcanvasElement = document.getElementById('mobileSidebar');
    if (offcanvasElement) {
        var offcanvas = new bootstrap.Offcanvas(offcanvasElement);
        console.log('Bootstrap offcanvas initialized');
    }
    
    // Close offcanvas when mobile menu items are clicked
    $('#mobileNavigation .nav-link').on('click', function(e) {
        const isMobile = window.innerWidth < 992;
        const menuText = $(this).text().trim();
        
        console.log('Mobile menu clicked:', menuText, 'Mobile mode:', isMobile);
        
        if (isMobile) {
            // Add visual feedback
            $(this).addClass('clicked');
            setTimeout(() => {
                $(this).removeClass('clicked');
            }, 200);
            
            // Close offcanvas after navigation
            setTimeout(() => {
                var offcanvas = bootstrap.Offcanvas.getInstance(document.getElementById('mobileSidebar'));
                if (offcanvas) {
                    offcanvas.hide();
                }
            }, 150);
        }
    });
    
    // Handle desktop sidebar menu clicks
    $('#mainNavigation .nav-link').on('click', function(e) {
        const isMobile = window.innerWidth < 992;
        const menuText = $(this).text().trim();
        
        console.log('Desktop menu clicked:', menuText, 'Mobile mode:', isMobile);
        
        if (isMobile) {
            // Add visual feedback
            $(this).addClass('clicked');
            setTimeout(() => {
                $(this).removeClass('clicked');
            }, 200);
            
            // Close sidebar after navigation
            setTimeout(() => {
                $('#mainSidebar').removeClass('show');
                $('#sidebarBackdrop').removeClass('show');
                console.log('Desktop sidebar closed after menu click');
            }, 150);
        }
        
        // Update active state
        $('#mainNavigation .nav-link').removeClass('active');
        $(this).addClass('active');
    });
    
    // Handle sidebar brand click
    $('#sidebarBrand').on('click', function(e) {
        const isMobile = window.innerWidth < 992;
        
        if (isMobile) {
            // Close sidebar when brand is clicked on mobile
            $('#mainSidebar').removeClass('show');
            $('#sidebarBackdrop').removeClass('show');
            console.log('Sidebar brand clicked - sidebar closed');
        }
    });
    
    console.log('=== Dual Navigation Setup Complete ===');
});
</script>
</body>
</html>
