<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($title ?? 'Koperasi KSP LGJ') ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="<?= asset_url('assets/css/style.css') ?>">
    
    <style>
        /* Global Styles */
        body {
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
            overflow-x: hidden;
        }
        
        /* Header Styles */
        .main-header {
            position: sticky;
            top: 0;
            z-index: 1030;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
        }
        
        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 2rem;
        }
        
        .header-brand {
            display: flex;
            align-items: center;
            font-size: 1.5rem;
            font-weight: 700;
            text-decoration: none;
            color: white;
        }
        
        .header-brand:hover {
            color: rgba(255,255,255,0.8);
        }
        
        .header-info {
            display: flex;
            align-items: center;
            gap: 2rem;
        }
        
        .datetime-display {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            font-size: 0.9rem;
        }
        
        .time-display {
            font-size: 1.2rem;
            font-weight: 600;
        }
        
        .date-display {
            opacity: 0.8;
        }
        
        .mobile-menu-toggle {
            display: none;
            background: rgba(255,255,255,0.2);
            border: 1px solid rgba(255,255,255,0.3);
            color: white;
            padding: 0.5rem;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .mobile-menu-toggle:hover {
            background: rgba(255,255,255,0.3);
            transform: scale(1.05);
        }
        
        /* Sidenav Styles */
        .main-sidenav {
            position: fixed;
            top: 80px; /* Header height */
            left: 0;
            width: 280px;
            height: calc(100vh - 80px);
            background: linear-gradient(180deg, #2c3e50 0%, #34495e 100%);
            color: white;
            overflow-y: auto;
            transition: transform 0.3s ease;
            z-index: 1020;
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
        }
        
        .sidenav-header {
            padding: 1.5rem;
            background: rgba(255,255,255,0.1);
            border-bottom: 1px solid rgba(255,255,255,0.2);
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .user-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: rgba(255,255,255,0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }
        
        .user-details {
            flex: 1;
        }
        
        .user-name {
            font-weight: 600;
            margin-bottom: 0.25rem;
        }
        
        .user-role {
            font-size: 0.85rem;
            opacity: 0.8;
        }
        
        .sidenav-menu {
            padding: 1rem 0;
        }
        
        .menu-section {
            margin-bottom: 1.5rem;
        }
        
        .menu-section-title {
            padding: 0.5rem 1.5rem;
            font-size: 0.75rem;
            text-transform: uppercase;
            opacity: 0.6;
            letter-spacing: 1px;
        }
        
        .menu-item {
            display: block;
            padding: 0.75rem 1.5rem;
            color: rgba(255,255,255,0.8);
            text-decoration: none;
            transition: all 0.3s ease;
            border-left: 3px solid transparent;
            position: relative;
        }
        
        .menu-item:hover {
            color: white;
            background: rgba(255,255,255,0.1);
            border-left-color: #3498db;
            transform: translateX(3px);
        }
        
        .menu-item.active {
            color: white;
            background: rgba(52,152,219,0.2);
            border-left-color: #3498db;
        }
        
        .menu-item i {
            width: 20px;
            margin-right: 0.75rem;
            text-align: center;
        }
        
        /* Main Content Styles */
        .main-content {
            margin-left: 280px;
            margin-top: 80px; /* Header height */
            min-height: calc(100vh - 80px);
            padding: 2rem;
            overflow-y: auto;
            background: #f8f9fa;
        }
        
        .content-wrapper {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .page-header {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 2rem;
        }
        
        .page-title {
            font-size: 2rem;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 0.5rem;
        }
        
        .page-subtitle {
            color: #7f8c8d;
            font-size: 1.1rem;
        }
        
        .content-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            padding: 2rem;
            margin-bottom: 2rem;
        }
        
        /* Footer Styles */
        .main-footer {
            background: #2c3e50;
            color: white;
            padding: 2rem;
            text-align: center;
            margin-left: 280px;
        }
        
        /* Responsive Styles */
        @media (max-width: 991px) {
            .mobile-menu-toggle {
                display: block;
            }
            
            .main-sidenav {
                transform: translateX(-100%);
            }
            
            .main-sidenav.show {
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: 0;
            }
            
            .main-footer {
                margin-left: 0;
            }
            
            .header-info {
                gap: 1rem;
            }
            
            .datetime-display {
                font-size: 0.8rem;
            }
            
            .time-display {
                font-size: 1rem;
            }
        }
        
        @media (max-width: 576px) {
            .header-content {
                padding: 1rem;
            }
            
            .main-content {
                padding: 1rem;
            }
            
            .page-header {
                padding: 1rem;
            }
            
            .page-title {
                font-size: 1.5rem;
            }
            
            .content-card {
                padding: 1rem;
            }
        }
        
        /* Scrollbar Styles */
        .main-sidenav::-webkit-scrollbar {
            width: 6px;
        }
        
        .main-sidenav::-webkit-scrollbar-track {
            background: rgba(255,255,255,0.1);
        }
        
        .main-sidenav::-webkit-scrollbar-thumb {
            background: rgba(255,255,255,0.3);
            border-radius: 3px;
        }
        
        .main-sidenav::-webkit-scrollbar-thumb:hover {
            background: rgba(255,255,255,0.5);
        }
        
        .main-content::-webkit-scrollbar {
            width: 8px;
        }
        
        .main-content::-webkit-scrollbar-track {
            background: #f1f1f1;
        }
        
        .main-content::-webkit-scrollbar-thumb {
            background: #c1c1c1;
            border-radius: 4px;
        }
        
        .main-content::-webkit-scrollbar-thumb:hover {
            background: #a8a8a8;
        }
        
        /* Loading Animation */
        .loading-spinner {
            display: none;
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            z-index: 9999;
        }
        
        .loading-spinner.show {
            display: block;
        }
        
        .spinner-border {
            width: 3rem;
            height: 3rem;
            border-width: 0.3em;
        }
    </style>
</head>
<body>
    <!-- Loading Spinner -->
    <div class="loading-spinner" id="loadingSpinner">
        <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
    </div>

    <!-- Main Header -->
    <header class="main-header" id="mainHeader">
        <div class="header-content">
            <div class="header-left">
                <button class="mobile-menu-toggle" id="mobileMenuToggle">
                    <i class="bi bi-list fs-4"></i>
                </button>
                <a href="<?= route_url('dashboard') ?>" class="header-brand">
                    <i class="bi bi-building me-2"></i>
                    KSP LGJ
                </a>
            </div>
            
            <div class="header-info">
                <div class="datetime-display">
                    <div class="time-display" id="timeDisplay">00:00:00</div>
                    <div class="date-display" id="dateDisplay">Loading...</div>
                </div>
                
                <div class="user-menu dropdown">
                    <button class="btn btn-link text-white dropdown-toggle" type="button" id="userDropdown" data-bs-toggle="dropdown">
                        <i class="bi bi-person-circle me-1"></i>
                        <?= htmlspecialchars($_SESSION['user']['name'] ?? 'Guest') ?>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="<?= route_url('profile') ?>">
                            <i class="bi bi-person me-2"></i> Profile
                        </a></li>
                        <li><a class="dropdown-item" href="<?= route_url('settings') ?>">
                            <i class="bi bi-gear me-2"></i> Settings
                        </a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="<?= route_url('logout') ?>">
                            <i class="bi bi-box-arrow-right me-2"></i> Logout
                        </a></li>
                    </ul>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Sidenav -->
    <nav class="main-sidenav" id="mainSidenav">
        <div class="sidenav-header">
            <div class="user-info">
                <div class="user-avatar">
                    <i class="bi bi-person-circle"></i>
                </div>
                <div class="user-details">
                    <div class="user-name"><?= htmlspecialchars($_SESSION['user']['name'] ?? 'Guest') ?></div>
                    <div class="user-role"><?= htmlspecialchars($_SESSION['user']['role'] ?? 'User') ?></div>
                </div>
            </div>
        </div>
        
        <div class="sidenav-menu" id="sidenavMenu">
            <!-- Menu items will be dynamically loaded here -->
        </div>
    </nav>

    <!-- Main Content -->
    <main class="main-content" id="mainContent">
        <div class="content-wrapper">
            <!-- Page Header -->
            <div class="page-header">
                <h1 class="page-title" id="pageTitle">Dashboard</h1>
                <p class="page-subtitle" id="pageSubtitle">Selamat datang di sistem koperasi</p>
            </div>
            
            <!-- Dynamic Content -->
            <div class="content-card" id="dynamicContent">
                <!-- Content will be loaded here -->
            </div>
        </div>
    </main>

    <!-- Main Footer -->
    <footer class="main-footer">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-6">
                    <p class="mb-0">&copy; <?= date('Y') ?> KSP LGJ. All rights reserved.</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <p class="mb-0">Version 1.0.0 | Built with ❤️</p>
                </div>
            </div>
        </div>
    </footer>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?= public_url('assets/js/helpers-id.js') ?>"></script>
    <script src="<?= asset_url('assets/js/dom-helpers.js') ?>"></script>
    
    <script>
        // Global variables
        let currentPage = 'dashboard';
        let isLoading = false;
        
        // Initialize application
        $(document).ready(function() {
            console.log('=== KSP LGJ Single Page Application ===');
            
            // Initialize components
            initializeDateTime();
            initializeSidenav();
            initializeMobileMenu();
            initializeNavigation();
            
            // Load initial page
            loadPage('dashboard');
            
            console.log('Application initialized successfully');
        });
        
        // Initialize date and time display
        function initializeDateTime() {
            function updateDateTime() {
                const now = new Date();
                
                // Update time
                const timeString = now.toLocaleTimeString('id-ID', {
                    hour: '2-digit',
                    minute: '2-digit',
                    second: '2-digit'
                });
                $('#timeDisplay').text(timeString);
                
                // Update date
                const dateString = now.toLocaleDateString('id-ID', {
                    weekday: 'long',
                    year: 'numeric',
                    month: 'long',
                    day: 'numeric'
                });
                $('#dateDisplay').text(dateString);
            }
            
            // Update immediately
            updateDateTime();
            
            // Update every second
            setInterval(updateDateTime, 1000);
        }
        
        // Initialize sidenav menu
        function initializeSidenav() {
            // Load menu items dynamically
            loadMenuItems();
        }
        
        // Load menu items based on user permissions
        function loadMenuItems() {
            const menuItems = [
                {
                    section: 'Utama',
                    items: [
                        { id: 'dashboard', icon: 'bi-speedometer2', label: 'Dashboard', href: 'dashboard' },
                    ]
                },
                {
                    section: 'Transaksi',
                    items: [
                        { id: 'loans', icon: 'bi-cash-stack', label: 'Pinjaman', href: 'loans', permission: 'loans.view' },
                        { id: 'repayments', icon: 'bi-wallet2', label: 'Angsuran', href: 'repayments', permission: 'repayments.view' },
                    ]
                },
                {
                    section: 'Data Master',
                    items: [
                        { id: 'members', icon: 'bi-people', label: 'Anggota', href: 'members', permission: 'members.view' },
                        { id: 'products', icon: 'bi-box', label: 'Produk', href: 'products', permission: 'products.view' },
                        { id: 'surveys', icon: 'bi-clipboard-check', label: 'Survei', href: 'surveys', permission: 'surveys.view' },
                    ]
                },
                {
                    section: 'Laporan',
                    items: [
                        { id: 'reports', icon: 'bi-file-bar-graph', label: 'Laporan', href: 'reports', permission: 'reports.view' },
                        { id: 'audit', icon: 'bi-clock-history', label: 'Audit Log', href: 'audit', permission: 'audit_logs.view' },
                    ]
                },
                {
                    section: 'Sistem',
                    items: [
                        { id: 'users', icon: 'bi-person-gear', label: 'Pengguna', href: 'users', permission: 'users.view' },
                        { id: 'documents', icon: 'bi-file-text', label: 'Surat-Surat', href: 'surat', permission: 'documents.view' },
                    ]
                }
            ];
            
            let menuHTML = '';
            
            menuItems.forEach(section => {
                // Filter items based on permissions
                const allowedItems = section.items.filter(item => {
                    // If no permission required, allow
                    if (!item.permission) return true;
                    
                    // Check if user has permission (simplified check)
                    return true; // TODO: Implement actual permission check
                });
                
                if (allowedItems.length > 0) {
                    menuHTML += `
                        <div class="menu-section">
                            <div class="menu-section-title">${section.section}</div>
                    `;
                    
                    allowedItems.forEach(item => {
                        const isActive = item.id === currentPage ? 'active' : '';
                        menuHTML += `
                            <a href="#" class="menu-item ${isActive}" data-page="${item.id}" data-href="${item.href}">
                                <i class="bi ${item.icon}"></i>
                                ${item.label}
                            </a>
                        `;
                    });
                    
                    menuHTML += `
                        </div>
                    `;
                }
            });
            
            // Add logout
            menuHTML += `
                <div class="menu-section">
                    <a href="<?= route_url('logout') ?>" class="menu-item">
                        <i class="bi bi-box-arrow-right"></i>
                        Logout
                    </a>
                </div>
            `;
            
            $('#sidenavMenu').html(menuHTML);
        }
        
        // Initialize mobile menu
        function initializeMobileMenu() {
            $('#mobileMenuToggle').on('click', function() {
                $('#mainSidenav').toggleClass('show');
            });
            
            // Close sidenav when clicking outside
            $(document).on('click', function(e) {
                const $target = $(e.target);
                const isMobile = window.innerWidth < 992;
                
                if (isMobile && !$target.closest('#mainSidenav').length && !$target.closest('#mobileMenuToggle').length) {
                    $('#mainSidenav').removeClass('show');
                }
            });
        }
        
        // Initialize navigation
        function initializeNavigation() {
            $(document).on('click', '.menu-item[data-page]', function(e) {
                e.preventDefault();
                
                const page = $(this).data('page');
                const href = $(this).data('href');
                
                // Update active state
                $('.menu-item').removeClass('active');
                $(this).addClass('active');
                
                // Load page
                loadPage(page, href);
                
                // Close mobile menu
                if (window.innerWidth < 992) {
                    $('#mainSidenav').removeClass('show');
                }
            });
        }
        
        // Load page content
        function loadPage(page, href = null) {
            if (isLoading) return;
            
            isLoading = true;
            currentPage = page;
            
            // Show loading spinner
            $('#loadingSpinner').addClass('show');
            
            // Update URL without page reload
            if (href) {
                history.pushState({page: page}, '', `<?= route_url('') ?>${href}`);
            }
            
            // Simulate loading content (replace with actual AJAX call)
            setTimeout(function() {
                loadPageContent(page);
                
                // Hide loading spinner
                $('#loadingSpinner').removeClass('show');
                isLoading = false;
            }, 500);
        }
        
        // Load page content based on page type
        function loadPageContent(page) {
            const pageConfig = {
                dashboard: {
                    title: 'Dashboard',
                    subtitle: 'Ringkasan statistik dan aktivitas terkini',
                    content: `
                        <div class="row">
                            <div class="col-md-3 mb-4">
                                <div class="card bg-primary text-white">
                                    <div class="card-body">
                                        <h5 class="card-title">Total Anggota</h5>
                                        <h2 class="mb-0">1,234</h2>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3 mb-4">
                                <div class="card bg-success text-white">
                                    <div class="card-body">
                                        <h5 class="card-title">Pinjaman Aktif</h5>
                                        <h2 class="mb-0">567</h2>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3 mb-4">
                                <div class="card bg-warning text-white">
                                    <div class="card-body">
                                        <h5 class="card-title">Total Pinjaman</h5>
                                        <h2 class="mb-0">Rp 2.5M</h2>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3 mb-4">
                                <div class="card bg-info text-white">
                                    <div class="card-body">
                                        <h5 class="card-title">Angsuran Bulan Ini</h5>
                                        <h2 class="mb-0">Rp 450K</h2>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-8">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0">Aktivitas Terkini</h5>
                                    </div>
                                    <div class="card-body">
                                        <p>Daftar aktivitas terkini akan ditampilkan di sini...</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0">Statistik</h5>
                                    </div>
                                    <div class="card-body">
                                        <p>Statistik penting akan ditampilkan di sini...</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `
                },
                loans: {
                    title: 'Data Pinjaman',
                    subtitle: 'Kelola data pinjaman anggota',
                    content: `
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h4>Daftar Pinjaman</h4>
                            <button class="btn btn-primary">
                                <i class="bi bi-plus-circle me-2"></i>Tambah Pinjaman
                            </button>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>No. Pinjaman</th>
                                        <th>Anggota</th>
                                        <th>Jumlah</th>
                                        <th>Tenor</th>
                                        <th>Status</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>PJM001</td>
                                        <td>Budi Santoso</td>
                                        <td>Rp 5.000.000</td>
                                        <td>12 bulan</td>
                                        <td><span class="badge bg-success">Aktif</span></td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary">Detail</button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    `
                },
                members: {
                    title: 'Data Anggota',
                    subtitle: 'Kelola data anggota koperasi',
                    content: `
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h4>Daftar Anggota</h4>
                            <button class="btn btn-primary">
                                <i class="bi bi-plus-circle me-2"></i>Tambah Anggota
                            </button>
                        </div>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="card">
                                    <div class="card-body text-center">
                                        <i class="bi bi-person-circle fs-1 text-primary mb-3"></i>
                                        <h5>Budi Santoso</h5>
                                        <p class="text-muted">ANG001</p>
                                        <p class="mb-0">Aktif</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `
                }
            };
            
            const config = pageConfig[page] || pageConfig.dashboard;
            
            // Update page header
            $('#pageTitle').text(config.title);
            $('#pageSubtitle').text(config.subtitle);
            
            // Update content
            $('#dynamicContent').html(config.content);
            
            console.log(`Page loaded: ${page}`);
        }
        
        // Handle browser back/forward
        window.addEventListener('popstate', function(e) {
            if (e.state && e.state.page) {
                loadPage(e.state.page);
            }
        });
        
        // Handle window resize
        $(window).on('resize', function() {
            if (window.innerWidth >= 992) {
                $('#mainSidenav').removeClass('show');
            }
        });
    </script>
</body>
</html>
