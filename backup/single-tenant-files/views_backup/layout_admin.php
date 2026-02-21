<?php
// Check if user is logged in
if (empty($_SESSION['user'])) {
    header('Location: ' . route_url(''));
    exit;
}

$serverRendered = isset($content);
$currentPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';
$appName = defined('APP_NAME') ? APP_NAME : ($_ENV['APP_NAME'] ?? getenv('APP_NAME') ?? 'Koperasi App');
function is_active(string $needle, string $path): string {
    return (strpos($path, $needle) !== false) ? 'active' : '';
}

// Jika diminta mode partial (?partial=1), keluarkan hanya konten tanpa layout
if (!empty($_GET['partial'])) {
    echo '<div id="dynamicContent">' . ($content ?? '') . '</div>';
    return;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($title ?? $appName) ?></title>
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
            padding: 0.5rem 1rem;
            min-height: 56px;
            flex-wrap: nowrap;
            gap: .5rem;
        }
        
        .header-brand {
            display: flex;
            align-items: center;
            font-size: 1.25rem;
            font-weight: 700;
            text-decoration: none;
            color: white;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            gap: .35rem;
            max-width: 280px;
        }

        .header-brand .brand-text {
            display: block;
            max-width: 240px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            color: #fff;
        }
        
        .header-brand:hover {
            color: rgba(255,255,255,0.8);
        }
        
        .header-info {
            display: flex;
            align-items: center;
            gap: 1rem;
            flex-wrap: nowrap;
        }
        
        .datetime-display {
            display: flex;
            flex-direction: row;
            align-items: center;
            gap: .75rem;
            font-size: 0.9rem;
        }
        
        .time-display {
            font-size: 1.1rem;
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
            width: 36px;
            height: 36px;
            padding: 0;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            align-items: center;
            justify-content: center;
            margin-right: .5rem;
        }
        
        .mobile-menu-toggle:hover {
            background: rgba(255,255,255,0.3);
            transform: scale(1.05);
        }
        
        .header-icon-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: .25rem .5rem;
            color: #fff;
            border: 0;
            background: transparent;
        }
        .header-icon-btn:hover { color: rgba(255,255,255,.85); }
        
        /* Responsive tweaks: show burger on md-, hide some header parts on sm- */
        @media (max-width: 991.98px) {
            .mobile-menu-toggle { display: inline-flex; }
        }
        @media (max-width: 767.98px) {
            .datetime-display { display: none; }
            .user-name-text { display: none; }
            .header-content { gap: .5rem; }
            /* Biarkan urutan normal: burger kiri, brand kanan */
            .header-left { flex-direction: row; }
            .mobile-menu-toggle { margin-right: .5rem; margin-left: 0; }
            .header-brand { font-size: 1.05rem; max-width: 240px; }
            .header-brand .brand-text { max-width: 220px; }
        }
        
        /* Sidenav Styles */
        .main-sidenav {
            position: fixed;
            top: 64px; /* Header height (compact) */
            left: 0;
            width: 220px;
            height: calc(100vh - 64px);
            /* Samakan warna dengan header */
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            overflow-y: auto;
            transition: transform 0.3s ease;
            z-index: 1020;
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
        }
        
        .sidenav-header {
            display: none;
            padding: 0;
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
            padding: .5rem 0;
        }
        
        .menu-section {
            margin-bottom: 1rem;
        }
        
        .menu-section-title {
            padding: 0.35rem 1rem;
            font-size: 0.7rem;
            text-transform: uppercase;
            opacity: 0.6;
            letter-spacing: 1px;
        }
        
        .menu-item {
            display: block;
            padding: 0.5rem 1rem;
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
            width: 18px;
            margin-right: 0.6rem;
            text-align: center;
        }
        
        /* Main Content Styles */
        .main-content {
            margin-left: 220px;
            margin-top: 8px; /* Reduced gap to header (5-10px range) */
            min-height: calc(100vh - 88px);
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(6px);
            box-shadow: 0 8px 24px rgba(0,0,0,0.06);
            border-radius: 16px 0 0 0;
            transition: margin-left 0.3s ease;
            padding: 16px;
            overflow-y: auto;
        }
        /* Make tables slightly denser */
        .content-card .table { font-size: .95rem; }
        
        .page-header {
            background: white;
            padding: 0.75rem 1rem;
            border-radius: 12px;
            box-shadow: 0 1px 6px rgba(0,0,0,0.04);
            margin-bottom: 1rem;
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
            padding: 0;
            margin-bottom: 1.5rem;
        }
        
        /* Footer Styles */
        .main-footer {
            background: #2c3e50;
            color: white;
            padding: 2rem;
            text-align: center;
            margin-left: 220px;
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
                padding: 0;
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
            <span class="visually-hidden"><?= t('loading') ?></span>
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
                    <span class="brand-text"><?= htmlspecialchars($appName ?: 'Koperasi App') ?></span>
                </a>
            </div>
            
            <div class="header-info">
                <div class="datetime-display">
                    <div class="time-display" id="timeDisplay">00:00:00</div>
                    <div class="date-display" id="dateDisplay"><?= t('loading') ?></div>
                </div>
                <button class="header-icon-btn" id="fullscreenToggle" type="button" aria-label="Toggle fullscreen" title="Layar penuh">
                    <i class="bi bi-arrows-fullscreen fs-5" id="fullscreenIcon"></i>
                </button>
                
                <div class="user-menu dropdown">
                    <button class="btn btn-link text-white dropdown-toggle" type="button" id="userDropdown" data-bs-toggle="dropdown">
                        <i class="bi bi-person-circle me-1"></i>
                        <span class="user-name-text"><?= htmlspecialchars($_SESSION['user']['name'] ?? 'Guest') ?></span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="<?= route_url('profile') ?>">
                            <i class="bi bi-person me-2"></i> <?= t('profile') ?>
                        </a></li>
                        <li><a class="dropdown-item" href="<?= route_url('settings') ?>">
                            <i class="bi bi-gear me-2"></i> <?= t('settings') ?>
                        </a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="<?= route_url('logout') ?>">
                            <i class="bi bi-box-arrow-right me-2"></i> <?= t('logout') ?>
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
            <?php
            // Load navigation helper
            require_once __DIR__ . '/../Helpers/NavigationHelper.php';
            echo \App\Helpers\generate_navigation_menu();
            ?>

            <!-- Logout item -->
            <div class="menu-section">
                <a href="<?= route_url('logout') ?>" class="menu-item">
                    <i class="bi bi-box-arrow-right"></i> Logout
                </a>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="main-content" id="mainContent">
        <div class="content-wrapper">
            <!-- Flash Messages -->
            <?php if (!empty($_SESSION['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?= htmlspecialchars($_SESSION['success']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php unset($_SESSION['success']); ?>
            <?php endif; ?>
            <?php if (!empty($_SESSION['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?= htmlspecialchars($_SESSION['error']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>

            <!-- Dynamic Content -->
            <div class="content-card" id="dynamicContent">
                <?php if ($serverRendered ?? false): ?>
                    <?= $content ?? '' ?>
                <?php else: ?>
                    <!-- Content will be loaded here -->
                <?php endif; ?>
            </div>
        </div>
    </main>

    <!-- Main Footer -->
    <footer class="main-footer">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-6">
                    <p class="mb-0">&copy; <?= date('Y') ?> <?= htmlspecialchars($_ENV['APP_NAME'] ?? getenv('APP_NAME') ?? 'KSP LGJ') ?>. Hak cipta dilindungi.</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <p class="mb-0">Versi 1.0.0 | Dibuat dengan ❤️</p>
                </div>
            </div>
        </div>
    </footer>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?= asset_url('assets/js/helpers-id.js') ?>"></script>
    <script src="<?= asset_url('assets/js/dom-helpers.js') ?>"></script>
    
    <script>
        // Global variables
        const serverRendered = <?= $serverRendered ? 'true' : 'false' ?>;
        const BASE_URL_JS = '<?= rtrim(route_url(''), '/') ?>';
        const LEGACY_BASE_URL_JS = '<?= rtrim(legacy_route_url(''), '/') ?>';
        let currentPage = 'dashboard';
        let isLoading = false;
        
        function initializeDropdowns() {
            const dropdownTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="dropdown"]'));
            dropdownTriggerList.forEach(function (dropdownToggleEl) {
                const inst = bootstrap.Dropdown.getOrCreateInstance(dropdownToggleEl);
                // Pastikan handler tunggal untuk toggle (hindari duplikasi setelah partial load)
                if (dropdownToggleEl._dropdownClickHandler) {
                    dropdownToggleEl.removeEventListener('click', dropdownToggleEl._dropdownClickHandler);
                }
                const handler = function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    inst.toggle();
                };
                dropdownToggleEl.addEventListener('click', handler);
                dropdownToggleEl._dropdownClickHandler = handler;
            });
        }

        // Initialize application
        $(document).ready(function() {
            console.log('=== KSP LGJ Single Page Application ===');
            
            // Initialize components
            initializeDateTime();
            initializeMobileMenu();
            initializeNavigation();
            initializeFullscreenToggle();
            initializeDropdowns();
            
            // Center active menu item on load
            centerMenuItem($('.menu-item.active')[0]);

            // Initial page load
            if (!serverRendered) {
                initializeSidenav();
                // Load initial page only for SPA mode
                loadPage('dashboard');
            }
            // Always center the active menu item on load (server-rendered or SPA)
            const activeItem = document.querySelector('#sidenavMenu .menu-item.active');
            if (activeItem) {
                // Defer to ensure layout is ready
                setTimeout(function(){ centerMenuItem(activeItem); }, 0);
            }
            
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
        
        // Fullscreen toggle handler
        function initializeFullscreenToggle() {
            const btn = document.getElementById('fullscreenToggle');
            const icon = document.getElementById('fullscreenIcon');
            if (!btn || !icon) return;

            function isFullscreen() {
                return !!(document.fullscreenElement || document.webkitFullscreenElement || document.mozFullScreenElement || document.msFullscreenElement);
            }
            function updateIcon() {
                const on = isFullscreen();
                icon.classList.toggle('bi-arrows-fullscreen', !on);
                icon.classList.toggle('bi-fullscreen-exit', on);
            }
            function requestFs(el) {
                if (el.requestFullscreen) return el.requestFullscreen();
                if (el.webkitRequestFullscreen) return el.webkitRequestFullscreen();
                if (el.mozRequestFullScreen) return el.mozRequestFullScreen();
                if (el.msRequestFullscreen) return el.msRequestFullscreen();
            }
            function exitFs() {
                if (document.exitFullscreen) return document.exitFullscreen();
                if (document.webkitExitFullscreen) return document.webkitExitFullscreen();
                if (document.mozCancelFullScreen) return document.mozCancelFullScreen();
                if (document.msExitFullscreen) return document.msExitFullscreen();
            }

            btn.addEventListener('click', function() {
                if (isFullscreen()) {
                    exitFs();
                } else {
                    requestFs(document.documentElement);
                }
            });

            document.addEventListener('fullscreenchange', updateIcon);
            document.addEventListener('webkitfullscreenchange', updateIcon);
            document.addEventListener('mozfullscreenchange', updateIcon);
            document.addEventListener('MSFullscreenChange', updateIcon);

            updateIcon();
        }
        
        // Initialize sidenav menu (no-op in server-rendered mode)
        function initializeSidenav() {
            return;
        }
        function centerMenuItem(element) {
            const container = document.getElementById('mainSidenav');
            if (!container || !element) return;
            const el = element instanceof HTMLElement ? element : (element[0] || null);
            if (!el) return;
            const targetTop = el.offsetTop - (container.clientHeight / 2) + (el.clientHeight / 2);
            const maxScroll = container.scrollHeight - container.clientHeight;
            const newScroll = Math.max(0, Math.min(targetTop, maxScroll));
            container.scrollTo({ top: newScroll, behavior: 'smooth' });
        }

        // Ensure Bootstrap dropdown works in header
        function initializeDropdownMenus() {
            const dropdownTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="dropdown"]'));
            dropdownTriggerList.forEach(function (dropdownTriggerEl) {
                try {
                    new bootstrap.Dropdown(dropdownTriggerEl);
                } catch (e) {
                    // fallback
                }
            });
            // Manual toggle fallback for user dropdown
            const userBtn = document.getElementById('userDropdown');
            const userMenu = userBtn ? userBtn.nextElementSibling : null;
            if (userBtn && userMenu) {
                userBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    const isOpen = userMenu.classList.contains('show');
                    userMenu.classList.toggle('show', !isOpen);
                    userBtn.classList.toggle('show', !isOpen);
                });
                document.addEventListener('click', function(e) {
                    if (!userMenu.classList.contains('show')) return;
                    if (!e.target.closest('.user-menu')) {
                        userMenu.classList.remove('show');
                        userBtn.classList.remove('show');
                    }
                });
            }
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
                if (serverRendered) return; // allow normal navigation for server-rendered mode
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
            // Server-rendered: intercept sidebar nav to avoid full reload (jaga fullscreen)
            if (serverRendered) {
                // Intercept sidebar links
                $(document).on('click', '#sidenavMenu a', function(e) {
                    const href = this.getAttribute('href');
                    // Abaikan klik dengan modifier/target baru
                    if (e.ctrlKey || e.metaKey || e.shiftKey || e.altKey) return;
                    if (this.getAttribute('target') === '_blank') return;
                    if (!href || href === '#') return;
                    // biarkan logout/auth berjalan normal
                    if (/\/logout$/.test(href) || /\/login$/.test(href)) return;
                    // Jika target sama dengan halaman sekarang, cukup tutup sidenav di mobile
                    const clean = (u) => u.replace(/[#?].*$/, '');
                    if (clean(href) === clean(window.location.href)) {
                        if (window.innerWidth < 992) $('#mainSidenav').removeClass('show');
                        return;
                    }
                    e.preventDefault();
                    loadPartialPage(href, this);
                    if (window.innerWidth < 992) $('#mainSidenav').removeClass('show');
                });
                // Intercept header brand (logo) to avoid full reload
                $(document).on('click', '.header-brand', function(e) {
                    const href = this.getAttribute('href');
                    if (!href) return;
                    if (e.ctrlKey || e.metaKey || e.shiftKey || e.altKey) return;
                    e.preventDefault();
                    loadPartialPage(href, null);
                    if (window.innerWidth < 992) $('#mainSidenav').removeClass('show');
                });
                // Handle browser back/forward
                window.addEventListener('popstate', function() {
                    // Reload current URL content into container
                    loadPartialPage(window.location.href, null);
                });
            }
            $(document).on('click', '.menu-item', function() {
                centerMenuItem(this);
            });
            $(document).on('click', '.menu-section', function() {
                centerMenuItem(this);
            });
        }

        // Load partial page content (server-rendered) tanpa reload penuh
        function loadPartialPage(url, triggerEl) {
            $('#loadingSpinner').addClass('show');
            const injectPartial = function (html) {
                const $doc = $('<div>').append($.parseHTML(html));
                const $wrap = $doc.find('#dynamicContent').first();
                const inner = $wrap.length ? $wrap.html() : html;
                $('#dynamicContent').empty().html(inner);
                const len = $('#dynamicContent').children().length;
                if (url.indexOf('/dashboard') !== -1) {
                                    }
                return len > 0;
            };
            const onSuccess = function(pushUrl){
                if (triggerEl) { $('.menu-item').removeClass('active'); $(triggerEl).addClass('active'); }
                window.history.pushState({}, '', pushUrl);
                $('#loadingSpinner').removeClass('show');
                initializeDropdowns();
            };
            const ajaxUrl = url + (url.indexOf('?') === -1 ? '?partial=1' : '&partial=1');
            $.get(ajaxUrl).done(function(html){
                if (injectPartial(html)) { onSuccess(url); return; }
                // Jika kosong, coba ulang tanpa ?partial=1 dan ekstraksi biasa
                $.get(url).done(function(htmlFull){
                    const $docFull = $('<div>').append($.parseHTML(htmlFull));
                    const sels = ['#dynamicContent','#mainContent #dynamicContent','#mainContent .content-card','.content-card','main'];
                    let found = false;
                    for (let i=0;i<sels.length;i++){
                        const $f = $docFull.find(sels[i]).first();
                        if ($f.length && $.trim($f.html()).length){
                            $('#dynamicContent').empty().html($f.html());
                            found = true; break;
                        }
                    }
                    if (!found && $docFull.find('#dashboardMetrics').length){
                        const $dash = $docFull.find('#dashboardMetrics').closest('.row');
                        const $f = $dash.length ? $dash.parent() : $docFull.find('#dashboardMetrics');
                        $('#dynamicContent').empty().html($f.html());
                        found = true;
                    }
                    if (found) { onSuccess(url); return; }
                    window.location.href = url;
                }).fail(function(){ window.location.href = url; });
                // Coba ulang legacy
                if (BASE_URL_JS && LEGACY_BASE_URL_JS && url.startsWith(BASE_URL_JS + '/')) {
                    const alt = LEGACY_BASE_URL_JS + url.substring(BASE_URL_JS.length);
                    $.get(alt + (alt.indexOf('?') === -1 ? '?partial=1' : '&partial=1')).done(function(html2){
                        if (injectPartial(html2)) { onSuccess(url); return; }
                        if (url.indexOf('/dashboard') !== -1) {
                                                    }
                        window.location.href = url; // terakhir
                    }).fail(function(){ window.location.href = url; });
                } else {
                    window.location.href = url;
                }
            }).fail(function(){
                // Jika gagal langsung, coba legacy sekali
                if (BASE_URL_JS && LEGACY_BASE_URL_JS && url.startsWith(BASE_URL_JS + '/')) {
                    const alt = LEGACY_BASE_URL_JS + url.substring(BASE_URL_JS.length);
                    $.get(alt + (alt.indexOf('?') === -1 ? '?partial=1' : '&partial=1')).done(function(html2){
                        if (injectPartial(html2)) { onSuccess(url); return; }
                        if (url.indexOf('/dashboard') !== -1) {
                                                    }
                        window.location.href = url;
                    }).fail(function(){ window.location.href = url; });
                } else {
                    window.location.href = url;
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
            if (href && !serverRendered) {
                history.pushState({page: page}, '', href);
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
                        <div class="row" id="dashboardMetrics">
                            <div class="col-md-3 mb-4">
                                <div class="card bg-primary text-white">
                                    <div class="card-body">
                                        <h5 class="card-title">Total Anggota</h5>
                                        <h2 class="mb-0" id="totalMembers">-</h2>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3 mb-4">
                                <div class="card bg-success text-white">
                                    <div class="card-body">
                                        <h5 class="card-title">Pinjaman Aktif</h5>
                                        <h2 class="mb-0" id="activeLoans">-</h2>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3 mb-4">
                                <div class="card bg-warning text-white">
                                    <div class="card-body">
                                        <h5 class="card-title">Total Pinjaman</h5>
                                        <h2 class="mb-0" id="totalOutstanding">-</h2>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3 mb-4">
                                <div class="card bg-info text-white">
                                    <div class="card-body">
                                        <h5 class="card-title">NPL</h5>
                                        <h2 class="mb-0" id="nplRate">-</h2>
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
                                        <div id="recentActivities">
                                            <div class="text-center">
                                                <div class="spinner-border text-primary" role="status">
                                                    <span class="visually-hidden">Loading...</span>
                                                </div>
                                                <p class="mt-2">Memuat aktivitas terkini...</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0">Informasi Sistem</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-3">
                                            <strong>User:</strong> <span id="currentUser">-</span>
                                        </div>
                                        <div class="mb-3">
                                            <strong>Role:</strong> <span id="currentUserRole">-</span>
                                        </div>
                                        <div class="mb-3">
                                            <strong>Login Time:</strong> <span id="loginTime">-</span>
                                        </div>
                                        <div class="mb-3">
                                            <strong>Server Time:</strong> <span id="serverTime">-</span>
                                        </div>
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
                            <button class="btn btn-primary" onclick="showCreateLoanModal()">
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
                                <tbody id="loansTableBody">
                                    <tr>
                                        <td colspan="6" class="text-center">
                                            <div class="spinner-border text-primary" role="status">
                                                <span class="visually-hidden">Loading...</span>
                                            </div>
                                            <p class="mt-2">Memuat data pinjaman...</p>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    `
                },
                repayments: {
                    title: 'Data Angsuran',
                    subtitle: 'Kelola data angsuran pinjaman',
                    content: `
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h4>Daftar Angsuran</h4>
                            <button class="btn btn-primary" disabled>
                                <i class="bi bi-plus-circle"></i> Tambah Angsuran (coming soon)
                            </button>
                        </div>
                        <div class="card">
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>ID Angsuran</th>
                                                <th>ID Pinjaman</th>
                                                <th>Jumlah</th>
                                                <th>Tanggal</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody id="repaymentsTableBody">
                                            <tr><td colspan="5" class="text-center">Memuat data...</td></tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    `
                },
                members: {
                    title: 'Data Anggota',
                    subtitle: 'Kelola data anggota koperasi',
                    content: `
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h4>Daftar Anggota</h4>
                            <button class="btn btn-primary" onclick="showCreateMemberModal()">
                                <i class="bi bi-plus-circle me-2"></i>Tambah Anggota
                            </button>
                        </div>
                        <div class="row" id="membersGrid">
                            <div class="col-12 text-center">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                                <p class="mt-2">Memuat data anggota...</p>
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
            
            // Load actual data for the page
            loadPageData(page);
            
            console.log(`Page loaded: ${page}`);
        }
        
        // Load actual data for the page
        function loadPageData(page) {
            switch(page) {
                case 'dashboard':
                    loadDashboardData();
                    break;
                case 'loans':
                    loadLoansData();
                    break;
                case 'repayments':
                    loadRepaymentsData();
                    break;
                case 'members':
                    loadMembersData();
                    break;
                default:
                    console.log(`No data loader for page: ${page}`);
            }
        }
        
        // Load dashboard data
        function loadDashboardData() {
            $.ajax({
                url: '/maruba/index.php/dashboard?ajax=1',
                method: 'GET',
                dataType: 'json',
                success: function(response) {
                    if (response.metrics) {
                        response.metrics.forEach(metric => {
                            let value = metric.value;
                            if (metric.type === 'currency') {
                                value = formatCurrency(value);
                            } else if (metric.type === 'percent') {
                                value = value + '%';
                            }
                            
                            switch(metric.label) {
                                case 'Anggota Aktif':
                                    $('#totalMembers').text(value);
                                    break;
                                case 'Pinjaman Berjalan':
                                    $('#activeLoans').text(value);
                                    break;
                                case 'Outstanding':
                                    $('#totalOutstanding').text(value);
                                    break;
                                case 'NPL':
                                    $('#nplRate').text(value);
                                    break;
                            }
                        });
                    }
                    
                    if (response.activities) {
                        let activitiesHtml = '';
                        response.activities.forEach(activity => {
                            activitiesHtml += `
                                <div class="d-flex align-items-center mb-2">
                                    <i class="bi bi-circle-fill text-primary me-2" style="font-size: 0.5rem;"></i>
                                    <div>
                                        <strong>${activity.user_name || 'System'}</strong> 
                                        ${activity.action} 
                                        ${activity.entity} #${activity.entity_id}
                                        <br><small class="text-muted">${formatDateTime(activity.created_at)}</small>
                                    </div>
                                </div>
                            `;
                        });
                        $('#recentActivities').html(activitiesHtml || '<p>Tidak ada aktivitas terkini.</p>');
                    }
                    
                    if (response.user) {
                        $('#currentUser').text(response.user.name);
                        $('#currentUserRole').text(response.user.role);
                        $('#loginTime').text(formatDateTime(new Date()));
                    }
                    
                    $('#serverTime').text(formatDateTime(new Date()));
                },
                error: function() {
                    $('#dashboardMetrics').html('<div class="alert alert-danger">Gagal memuat data dashboard.</div>');
                    $('#recentActivities').html('<div class="alert alert-danger">Gagal memuat aktivitas terkini.</div>');
                }
            });
        }
        
        // Load loans data
        function loadLoansData() {
            // Simulate loading loans data
            setTimeout(function() {
                const loansData = [
                    { id: 'PJM001', member: 'Budi Santoso', amount: 5000000, tenor: 12, status: 'Aktif' },
                    { id: 'PJM002', member: 'Siti Aminah', amount: 3000000, tenor: 6, status: 'Aktif' },
                    { id: 'PJM003', member: 'Ahmad Fauzi', amount: 7500000, tenor: 18, status: 'Aktif' }
                ];
                
                let loansHtml = '';
                loansData.forEach(loan => {
                    loansHtml += `
                        <tr>
                            <td>${loan.id}</td>
                            <td>${loan.member}</td>
                            <td>${formatCurrency(loan.amount)}</td>
                            <td>${loan.tenor} bulan</td>
                            <td><span class="badge bg-success">${loan.status}</span></td>
                            <td>
                                <button class="btn btn-sm btn-outline-primary me-1">Detail</button>
                                <button class="btn btn-sm btn-outline-warning">Edit</button>
                            </td>
                        </tr>
                    `;
                });
                
                $('#loansTableBody').html(loansHtml || '<tr><td colspan="6" class="text-center">Tidak ada data pinjaman.</td></tr>');
            }, 1000);
        }

        // Load repayments data (placeholder until API available)
        function loadRepaymentsData() {
            setTimeout(function() {
                const repaymentsData = [
                    { id: 'ANG001', loan: 'PJM001', amount: 500000, date: '2026-02-01', status: 'Lunas' },
                    { id: 'ANG002', loan: 'PJM002', amount: 350000, date: '2026-02-05', status: 'Proses' },
                    { id: 'ANG003', loan: 'PJM003', amount: 420000, date: '2026-02-10', status: 'Proses' }
                ];

                let repaymentsHtml = '';
                repaymentsData.forEach(r => {
                    repaymentsHtml += `
                        <tr>
                            <td>${r.id}</td>
                            <td>${r.loan}</td>
                            <td>${formatCurrency(r.amount)}</td>
                            <td>${r.date}</td>
                            <td>${r.status}</td>
                        </tr>
                    `;
                });

                $('#repaymentsTableBody').html(repaymentsHtml || '<tr><td colspan="5" class="text-center">Tidak ada data angsuran.</td></tr>');
            }, 300);
        }
        
        // Load members data
        function loadMembersData() {
            // Simulate loading members data
            setTimeout(function() {
                const membersData = [
                    { id: 'ANG001', name: 'Budi Santoso', status: 'Aktif', joinDate: '2023-01-15' },
                    { id: 'ANG002', name: 'Siti Aminah', status: 'Aktif', joinDate: '2023-02-20' },
                    { id: 'ANG003', name: 'Ahmad Fauzi', status: 'Aktif', joinDate: '2023-03-10' }
                ];
                
                let membersHtml = '';
                membersData.forEach(member => {
                    membersHtml += `
                        <div class="col-md-4 mb-4">
                            <div class="card">
                                <div class="card-body text-center">
                                    <i class="bi bi-person-circle fs-1 text-primary mb-3"></i>
                                    <h5>${member.name}</h5>
                                    <p class="text-muted">${member.id}</p>
                                    <p class="mb-1"><span class="badge bg-success">${member.status}</span></p>
                                    <p class="mb-0"><small>Bergabung: ${formatDate(member.joinDate)}</small></p>
                                    <div class="mt-3">
                                        <button class="btn btn-sm btn-outline-primary me-1">Detail</button>
                                        <button class="btn btn-sm btn-outline-warning">Edit</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                });
                
                $('#membersGrid').html(membersHtml || '<div class="col-12 text-center">Tidak ada data anggota.</div>');
            }, 1000);
        }
        
        // Utility functions
        function formatCurrency(amount) {
            return new Intl.NumberFormat('id-ID', {
                style: 'currency',
                currency: 'IDR'
            }).format(amount);
        }
        
        function formatDateTime(dateString) {
            const date = new Date(dateString);
            return date.toLocaleString('id-ID', {
                year: 'numeric',
                month: 'long',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit'
            });
        }
        
        function formatDate(dateString) {
            const date = new Date(dateString);
            return date.toLocaleDateString('id-ID', {
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            });
        }
        
        // Modal functions (placeholders)
        function showCreateLoanModal() {
            alert('Create loan modal - to be implemented');
        }
        
        function showCreateMemberModal() {
            alert('Create member modal - to be implemented');
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
