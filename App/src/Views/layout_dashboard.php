<?php
// Ensure bootstrap is loaded
if (!function_exists('current_user')) {
    require_once __DIR__ . '/../../bootstrap.php';
}

// Check if user is logged in
if (empty($_SESSION['user'])) {
    header('Location: ' . route_url(''));
    exit();
}

$serverRendered = isset($content);
$currentPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';
$appName = defined('APP_NAME') ? APP_NAME : ($_ENV['APP_NAME'] ?? getenv('APP_NAME') ?? 'KOPERASI APP');

// Update last activity
if (!empty($_SESSION['user'])) {
    $_SESSION['user']['last_activity'] = time();
}

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
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, shrink-to-fit=no">
    <meta name="csrf-token" content="<?= $_SESSION['csrf_token'] ?? '' ?>">
    <title><?php echo APP_NAME; ?> - <?php echo $title ?? 'Dashboard'; ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script>
        // Fallback if CDN fails
        if (typeof jQuery === 'undefined') {
            document.write('<script src="<?= asset_url('assets/js/jquery-3.7.1.min.js') ?>"><\/script>');
        }
    </script>
    
    <style>
        /* Main Header Styles */
        .main-header {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            height: 64px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            z-index: 1030;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .header-content {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 1rem;
            height: 100%;
        }
        
        .header-left {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .header-brand .brand-text {
            display: block;
            max-width: 240px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            color: #fff;
            font-weight: 600;
            font-size: 1.2rem;
        }
        
        .header-brand:hover {
            color: rgba(255,255,255,0.9);
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
            background: rgba(255,255,255,0.1);
            padding: 0.5rem 1rem;
            border-radius: 8px;
            backdrop-filter: blur(10px);
        }
        
        .time-display {
            font-size: 1.1rem;
            font-weight: 600;
            color: #fff;
        }
        
        .date-display {
            opacity: 0.9;
            color: #fff;
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
            top: 64px;
            left: 0;
            width: 250px;
            height: calc(100vh - 64px);
            background: linear-gradient(180deg, #2c3e50 0%, #34495e 100%);
            color: white;
            overflow-y: auto;
            transition: transform 0.3s ease;
            z-index: 1020;
            box-shadow: 2px 0 15px rgba(0,0,0,0.2);
            border-right: 1px solid rgba(255,255,255,0.1);
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
            margin-left: 250px;
            margin-top: 72px;
            min-height: calc(100vh - 136px);
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            box-shadow: 0 8px 32px rgba(0,0,0,0.08);
            border-radius: 20px 0 0 0;
            transition: margin-left 0.3s ease;
            padding: 24px;
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
            background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
            color: white;
            padding: 2rem;
            text-align: center;
            margin-left: 250px;
            box-shadow: 0 -2px 10px rgba(0,0,0,0.1);
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
                gap: 0.5rem;
            }
            
            .datetime-display {
                font-size: 0.8rem;
                padding: 0.3rem 0.6rem;
            }
            
            .time-display {
                font-size: 0.9rem;
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
        
        /* Logout button styling */
        .logout-btn {
            background-color: #dc3545 !important;
            color: white !important;
            border-radius: 8px;
            padding: 12px 16px !important;
            margin-top: 10px;
            transition: all 0.3s ease;
            border-left: 3px solid #dc3545 !important;
        }
        
        .logout-btn:hover {
            background-color: #c82333 !important;
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(220, 53, 69, 0.3);
            color: white !important;
            border-left-color: #c82333 !important;
        }
        
        .logout-btn i {
            font-size: 1.1em;
            color: white !important;
        }
        
        .logout-btn .text-danger {
            color: white !important;
        }
    </style>

    <script>
        // Define BASE_URL_JS for JavaScript
        const BASE_URL_JS = '<?php echo addslashes(BASE_URL) ?>';
        const LEGACY_BASE_URL_JS = '<?php echo addslashes(BASE_URL) ?>';
        
        // HSTS Cache Clearing for Development
        (function() {
            // Only run in development mode
            const isDevelopment = '<?php echo APP_ENV ?>' === 'development';
            
            if (isDevelopment) {
                // Check if we're on localhost and using HTTPS when we shouldn't
                const isLocalhost = window.location.hostname === 'localhost' || 
                          window.location.hostname === '127.0.0.1' ||
                          window.location.hostname.includes('localhost');
                
                // If we're on localhost but using HTTPS, redirect to HTTP
                if (isLocalhost && window.location.protocol === 'https:') {
                    console.log('🔧 Development mode: Clearing HSTS cache, redirecting to HTTP');
                    window.location.replace('http://' + window.location.hostname + window.location.pathname + window.location.search);
                    return; // Stop execution
                }
            }
            
            // Continue with normal initialization only if not redirecting
            // Initialize Indonesian formatting
            if (typeof IndonesianFormat !== 'undefined') {
                console.log('🇮🇩 Indonesian Formatting System Loaded');
            }
        })();
    </script>
<body>
    <!-- Development status banner -->
    <div style="background:#fef3c7;color:#92400e;padding:10px 16px;text-align:center;font-weight:600;border-bottom:1px solid #fcd34d;">
        ⚠️ Aplikasi masih dalam tahap pengembangan / preview. Fitur, data, dan keamanan dapat berubah sewaktu-waktu.
    </div>
    <!-- Loading Spinner -->
    <div class="loading-spinner" id="loadingSpinner">
        <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
    </div>

    <!-- Main Header -->
    <header class="main-header" id="mainHeader" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
        <div class="header-content">
            <div class="header-left">
                <button class="mobile-menu-toggle" id="mobileMenuToggle">
                    <i class="bi bi-list fs-4"></i>
                </button>
                <a href="<?= route_url('dashboard') ?>" class="header-brand">
                    <span class="brand-text"><?= htmlspecialchars($appName ?: 'KOPERASI APP') ?></span>
                </a>
            </div>
            
            <div class="header-info">
                <div class="datetime-display">
                    <div class="time-display" id="timeDisplay">00:00:00</div>
                    <div class="date-display" id="dateDisplay">Loading...</div>
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
                            <i class="bi bi-person me-2"></i> Profile
                        </a></li>
                        <li><a class="dropdown-item" href="<?= route_url('settings') ?>">
                            <i class="bi bi-gear me-2"></i> Settings
                        </a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger" href="<?= route_url('index.php/logout') ?>">
                            <i class="bi bi-box-arrow-right me-2"></i> <strong>Logout</strong>
                        </a></li>
                    </ul>
                </div>
                
                <!-- Quick Logout Button -->
                <button class="btn btn-outline-light btn-sm ms-2" onclick="confirmLogout()" title="Keluar">
                    <i class="bi bi-power"></i>
                </button>
                
                <!-- User Info -->
                <div class="dropdown">
                    <button class="btn btn-outline-light btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown">
                        <i class="bi bi-person-circle me-1"></i>
                        <?= htmlspecialchars(current_user()['name'] ?? 'User') ?>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><h6 class="dropdown-header"><?= htmlspecialchars(user_role()) ?></h6></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="#">
                            <i class="bi bi-person me-2"></i> Profil
                        </a></li>
                        <li><a class="dropdown-item" href="#">
                            <i class="bi bi-gear me-2"></i> Pengaturan
                        </a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger" href="<?= route_url('index.php/logout') ?>">
                            <i class="bi bi-box-arrow-right me-2"></i> <strong>Logout</strong>
                        </a></li>
                    </ul>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Sidenav -->
    <nav class="main-sidenav" id="mainSidenav" style="background: linear-gradient(180deg, #2c3e50 0%, #34495e 100%);">
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
            <div class="menu-section mt-3">
                <a href="<?= route_url('index.php/logout') ?>" class="menu-item logout-btn" onclick="return confirm('Apakah Anda yakin ingin keluar?')">
                    <i class="bi bi-box-arrow-right text-danger"></i> 
                    <span class="text-danger">Logout</span>
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
                    <p class="mb-0">&copy; <?= date('Y') ?> <?= htmlspecialchars($_ENV['APP_NAME'] ?? getenv('APP_NAME') ?? 'KOPERASI APP') ?>. Hak cipta dilindungi.</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <p class="mb-0">Versi 1.0.0 | Dibuat dengan ❤️</p>
                </div>
            </div>
        </div>
    </footer>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?= asset_url('assets/js/helpers-id.js') ?>"></script>
    <script src="<?= asset_url('assets/js/dom-helpers.js') ?>"></script>
    <script src="<?= asset_url('assets/js/ksp-ui-library.js') ?>"></script>
    <script src="<?= asset_url('assets/js/ksp-components.js') ?>"></script>
    <script src="<?= asset_url('assets/js/indonesian-format.js') ?>"></script>
    <script>
        // Minimal, error-free JavaScript
        console.log('KOPERASI APP Dashboard loaded successfully');
        
        // Basic date/time functionality (no jQuery dependency)
        function updateDateTime() {
            const timeDisplay = document.getElementById('timeDisplay');
            const dateDisplay = document.getElementById('dateDisplay');
            
            if (timeDisplay && dateDisplay) {
                const now = new Date();
                const timeString = now.toLocaleTimeString('id-ID', {
                    hour: '2-digit', minute: '2-digit', second: '2-digit'
                });
                const dateString = now.toLocaleDateString('id-ID', {
                    weekday: 'long', year: 'numeric', month: 'long', day: 'numeric'
                });
                
                timeDisplay.textContent = timeString;
                dateDisplay.textContent = dateString;
            }
        }
        
        // Update immediately and every second
        updateDateTime();
        setInterval(updateDateTime, 1000);
        
        console.log('Basic dashboard functionality initialized');
    </script>
</body>
</html>
