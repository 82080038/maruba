<?php
session_start();

$path = $_SERVER["REQUEST_URI"] ?? "/";
$path = parse_url($path, PHP_URL_PATH);

// Get current user role
$user_role = isset($_SESSION["user"]) ? $_SESSION["user"]["role"] : "guest";

// Define navigation menus per role
$navigation_menus = [
    "admin" => [
        ["title" => "Dashboard", "url" => "/index.php/dashboard", "icon" => "fas fa-tachometer-alt"],
        ["title" => "Anggota", "url" => "#", "icon" => "fas fa-users"],
        ["title" => "Simpanan", "url" => "#", "icon" => "fas fa-piggy-bank"],
        ["title" => "Pinjaman", "url" => "#", "icon" => "fas fa-hand-holding-usd"],
        ["title" => "Pembayaran", "url" => "#", "icon" => "fas fa-money-check-alt"],
        ["title" => "Survey", "url" => "#", "icon" => "fas fa-search"],
        ["title" => "Laporan", "url" => "#", "icon" => "fas fa-chart-bar"],
        ["title" => "Pengaturan", "url" => "#", "icon" => "fas fa-cog"]
    ],
    "kasir" => [
        ["title" => "Dashboard", "url" => "/index.php/dashboard", "icon" => "fas fa-tachometer-alt"],
        ["title" => "Simpanan", "url" => "#", "icon" => "fas fa-piggy-bank"],
        ["title" => "Pembayaran", "url" => "#", "icon" => "fas fa-money-check-alt"],
        ["title" => "Laporan", "url" => "#", "icon" => "fas fa-chart-line"]
    ],
    "manager" => [
        ["title" => "Dashboard", "url" => "/index.php/dashboard", "icon" => "fas fa-tachometer-alt"],
        ["title" => "Anggota", "url" => "#", "icon" => "fas fa-users"],
        ["title" => "Simpanan", "url" => "#", "icon" => "fas fa-piggy-bank"],
        ["title" => "Pinjaman", "url" => "#", "icon" => "fas fa-hand-holding-usd"],
        ["title" => "Laporan", "url" => "#", "icon" => "fas fa-chart-bar"]
    ],
    "teller" => [
        ["title" => "Dashboard", "url" => "/index.php/dashboard", "icon" => "fas fa-tachometer-alt"],
        ["title" => "Simpanan", "url" => "#", "icon" => "fas fa-piggy-bank"],
        ["title" => "Anggota", "url" => "#", "icon" => "fas fa-users"]
    ],
    "surveyor" => [
        ["title" => "Dashboard", "url" => "/index.php/dashboard", "icon" => "fas fa-tachometer-alt"],
        ["title" => "Survey", "url" => "#", "icon" => "fas fa-search"],
        ["title" => "Anggota", "url" => "#", "icon" => "fas fa-users"]
    ],
    "collector" => [
        ["title" => "Dashboard", "url" => "/index.php/dashboard", "icon" => "fas fa-tachometer-alt"],
        ["title" => "Penagihan", "url" => "#", "icon" => "fas fa-truck"],
        ["title" => "Anggota", "url" => "#", "icon" => "fas fa-users"]
    ],
    "akuntansi" => [
        ["title" => "Dashboard", "url" => "/index.php/dashboard", "icon" => "fas fa-tachometer-alt"],
        ["title" => "Akuntansi", "url" => "#", "icon" => "fas fa-calculator"],
        ["title" => "Laporan Keuangan", "url" => "#", "icon" => "fas fa-chart-pie"]
    ]
];

// Get menu for current role
$current_menu = $navigation_menus[$user_role] ?? [];

function render_navigation($menu_items, $current_path) {
    $nav_html = '<ul class="navbar-nav me-auto">';
    
    foreach ($menu_items as $item) {
        $active_class = ($current_path == $item["url"]) ? "active" : "";
        $nav_html .= '<li class="nav-item">';
        $nav_html .= '<a class="nav-link ' . $active_class . '" href="' . $item["url"] . '">';
        $nav_html .= '<i class="' . $item["icon"] . ' me-1"></i>' . $item["title"];
        $nav_html .= '</a></li>';
    }
    
    $nav_html .= '</ul>';
    return $nav_html;
}

function render_quick_actions($user_role) {
    $actions = [
        "admin" => [
            ["title" => "Tambah Anggota", "url" => "#", "color" => "primary", "icon" => "fas fa-user-plus"],
            ["title" => "Simpanan Baru", "url" => "#", "color" => "success", "icon" => "fas fa-plus-circle"],
            ["title" => "Pinjaman Baru", "url" => "#", "color" => "warning", "icon" => "fas fa-hand-holding-usd"],
            ["title" => "Pembayaran", "url" => "#", "color" => "info", "icon" => "fas fa-money-check-alt"],
            ["title" => "Survey Baru", "url" => "#", "color" => "secondary", "icon" => "fas fa-search"],
            ["title" => "Laporan", "url" => "#", "color" => "dark", "icon" => "fas fa-chart-bar"]
        ],
        "kasir" => [
            ["title" => "Simpanan Baru", "url" => "#", "color" => "success", "icon" => "fas fa-plus-circle"],
            ["title" => "Penarikan", "url" => "#", "color" => "warning", "icon" => "fas fa-minus-circle"],
            ["title" => "Pembayaran", "url" => "#", "color" => "info", "icon" => "fas fa-money-check-alt"],
            ["title" => "Laporan Harian", "url" => "#", "color" => "secondary", "icon" => "fas fa-calendar-day"]
        ],
        "manager" => [
            ["title" => "Tambah Anggota", "url" => "#", "color" => "primary", "icon" => "fas fa-user-plus"],
            ["title" => "Simpanan Baru", "url" => "#", "color" => "success", "icon" => "fas fa-plus-circle"],
            ["title" => "Pinjaman Baru", "url" => "#", "color" => "warning", "icon" => "fas fa-hand-holding-usd"],
            ["title" => "Laporan", "url" => "#", "color" => "dark", "icon" => "fas fa-chart-bar"]
        ],
        "teller" => [
            ["title" => "Setoran Baru", "url" => "#", "color" => "success", "icon" => "fas fa-plus-circle"],
            ["title" => "Penarikan", "url" => "#", "color" => "warning", "icon" => "fas fa-minus-circle"],
            ["title" => "Cek Saldo", "url" => "#", "color" => "info", "icon" => "fas fa-search"]
        ],
        "surveyor" => [
            ["title" => "Survey Baru", "url" => "#", "color" => "primary", "icon" => "fas fa-search"],
            ["title" => "Daftar Survey", "url" => "#", "color" => "secondary", "icon" => "fas fa-list"],
            ["title" => "Registrasi Anggota", "url" => "#", "color" => "success", "icon" => "fas fa-user-plus"]
        ],
        "collector" => [
            ["title" => "Jadwal Penagihan", "url" => "#", "color" => "primary", "icon" => "fas fa-calendar"],
            ["title" => "Pembayaran Baru", "url" => "#", "color" => "success", "icon" => "fas fa-money-check-alt"],
            ["title" => "Daftar Anggota", "url" => "#", "color" => "info", "icon" => "fas fa-users"]
        ],
        "akuntansi" => [
            ["title" => "Jurnal Umum", "url" => "#", "color" => "primary", "icon" => "fas fa-book"],
            ["title" => "Buku Besar", "url" => "#", "color" => "success", "icon" => "fas fa-calculator"],
            ["title" => "Neraca", "url" => "#", "color" => "warning", "icon" => "fas fa-balance-scale"],
            ["title" => "Laporan Laba Rugi", "url" => "#", "color" => "info", "icon" => "fas fa-chart-line"]
        ]
    ];
    
    $role_actions = $actions[$user_role] ?? [];
    
    $actions_html = '<div class="row mb-4">';
    foreach ($role_actions as $action) {
        $actions_html .= '<div class="col-md-2">';
        $actions_html .= '<a href="' . $action["url"] . '" class="btn btn-' . $action["color"] . ' w-100 mb-2">';
        $actions_html .= '<i class="' . $action["icon"] . ' me-1"></i>' . $action["title"];
        $actions_html .= '</a></div>';
    }
    $actions_html .= '</div>';
    
    return $actions_html;
}

if ($path == "/" || $path == "/index.php") {
    echo '<!DOCTYPE html>
<html>
<head>
    <title>Login - Maruba Koperasi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0"><i class="fas fa-university me-2"></i>Login - Maruba Koperasi</h4>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="/index.php/login">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Username</label>
                                        <input type="text" name="username" class="form-control" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Password</label>
                                        <input type="password" name="password" class="form-control" required>
                                    </div>
                                    <button type="submit" class="btn btn-primary w-100">
                                        <i class="fas fa-sign-in-alt me-1"></i> Login
                                    </button>
                                </div>
                                <div class="col-md-6">
                                    <div class="card bg-light">
                                        <div class="card-header">
                                            <h6 class="mb-0"><i class="fas fa-rocket me-1"></i>Quick Login</h6>
                                        </div>
                                        <div class="card-body">
                                            <div class="row g-2">
                                                <div class="col-6">
                                                    <button type="button" onclick="fillForm(\'admin\',\'admin123\')" class="btn btn-sm btn-outline-primary w-100">
                                                        <i class="fas fa-user-shield me-1"></i> Admin
                                                    </button>
                                                </div>
                                                <div class="col-6">
                                                    <button type="button" onclick="fillForm(\'manager\',\'manager123\')" class="btn btn-sm btn-outline-success w-100">
                                                        <i class="fas fa-user-tie me-1"></i> Manager
                                                    </button>
                                                </div>
                                                <div class="col-6">
                                                    <button type="button" onclick="fillForm(\'kasir\',\'kasir123\')" class="btn btn-sm btn-outline-info w-100">
                                                        <i class="fas fa-cash-register me-1"></i> Kasir
                                                    </button>
                                                </div>
                                                <div class="col-6">
                                                    <button type="button" onclick="fillForm(\'teller\',\'teller123\')" class="btn btn-sm btn-outline-warning w-100">
                                                        <i class="fas fa-piggy-bank me-1"></i> Teller
                                                    </button>
                                                </div>
                                                <div class="col-6">
                                                    <button type="button" onclick="fillForm(\'surveyor\',\'surveyor123\')" class="btn btn-sm btn-outline-secondary w-100">
                                                        <i class="fas fa-search me-1"></i> Surveyor
                                                    </button>
                                                </div>
                                                <div class="col-6">
                                                    <button type="button" onclick="fillForm(\'collector\',\'collector123\')" class="btn btn-sm btn-outline-dark w-100">
                                                        <i class="fas fa-truck me-1"></i> Collector
                                                    </button>
                                                </div>
                                                <div class="col-6">
                                                    <button type="button" onclick="fillForm(\'akuntansi\',\'akuntansi123\')" class="btn btn-sm btn-outline-danger w-100">
                                                        <i class="fas fa-calculator me-1"></i> Akuntansi
                                                    </button>
                                                </div>
                                                <div class="col-6">
                                                    <button type="button" onclick="fillForm(\'staf\',\'staf123\')" class="btn btn-sm btn-outline-light w-100">
                                                        <i class="fas fa-user me-1"></i> Staf
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
    function fillForm(username, password) {
        document.querySelector("input[name=username]").value = username;
        document.querySelector("input[name=password]").value = password;
        document.querySelector("form").submit();
    }
    </script>
</body>
</html>';
    
} elseif ($path == "/index.php/login" && $_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST["username"];
    $password = $_POST["password"];
    
    // Authentication for all roles
    $users = [
        "admin" => "admin123",
        "manager" => "manager123", 
        "kasir" => "kasir123",
        "teller" => "teller123",
        "surveyor" => "surveyor123",
        "collector" => "collector123",
        "akuntansi" => "akuntansi123",
        "staf" => "staf123"
    ];
    
    if (isset($users[$username]) && $users[$username] === $password) {
        $_SESSION["user"] = array("username" => $username, "role" => $username);
        header("Location: /index.php/dashboard");
        exit;
    } else {
        echo '<h1>Login Failed</h1><p>Invalid credentials</p><a href="/index.php">Try again</a>';
    }
    
} elseif ($path == "/index.php/dashboard") {
    if (!isset($_SESSION["user"])) {
        header("Location: /index.php");
        exit;
    }
    
    $user = $_SESSION["user"];
    $user_role = $user["role"];
    $nav_html = render_navigation($current_menu, $path);
    $actions_html = render_quick_actions($user_role);
    
    echo '<!DOCTYPE html>
<html>
<head>
    <title>Dashboard - Maruba Koperasi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="/index.php/dashboard">
                <i class="fas fa-university me-2"></i>Maruba Koperasi
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                ' . render_navigation($current_menu, $path) . '
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user me-1"></i> ' . htmlspecialchars($user["username"]) . '
                            <span class="badge bg-secondary ms-1">' . ucfirst($user_role) . '</span>
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="#"><i class="fas fa-user me-1"></i> Profile</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="/index.php/logout"><i class="fas fa-sign-out-alt me-1"></i> Logout</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    
    <div class="container-fluid mt-4">
        <div class="row">
            <div class="col-12">
                <h1><i class="fas fa-tachometer-alt me-2"></i>Dashboard</h1>
                <p class="text-muted">Selamat datang, ' . htmlspecialchars($user["username"]) . '! 
                   <span class="badge bg-primary ms-1">' . ucfirst($user_role) . '</span></p>
            </div>
        </div>

        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card stat-card bg-primary text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h4>150</h4>
                                <p>Total Anggota</p>
                            </div>
                            <div class="align-self-center">
                                <i class="fas fa-users fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card bg-success text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h4>Rp 50M</h4>
                                <p>Total Simpanan</p>
                            </div>
                            <div class="align-self-center">
                                <i class="fas fa-piggy-bank fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card bg-warning text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h4>Rp 75M</h4>
                                <p>Total Pinjaman</p>
                            </div>
                            <div class="align-self-center">
                                <i class="fas fa-hand-holding-usd fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card bg-info text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h4>95%</h4>
                                <p>Pembayaran Lancar</p>
                            </div>
                            <div class="align-self-center">
                                <i class="fas fa-chart-line fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mb-4">
            <div class="col-12">
                <h3>Aksi Cepat</h3>
            </div>
        </div>
        
        ' . $actions_html . '

        <div class="row">
            <div class="col-12">
                <h3>Aktivitas Terkini</h3>
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Tanggal</th>
                                <th>Keterangan</th>
                                <th>Jumlah</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>2026-03-06 14:30</td>
                                <td>Simpanan - Budi Santoso</td>
                                <td>Rp 1.000.000</td>
                                <td><span class="badge bg-success">Selesai</span></td>
                            </tr>
                            <tr>
                                <td>2026-03-06 13:45</td>
                                <td>Pinjaman - Siti Nurhaliza</td>
                                <td>Rp 5.000.000</td>
                                <td><span class="badge bg-warning">Proses</span></td>
                            </tr>
                            <tr>
                                <td>2026-03-06 12:15</td>
                                <td>Pembayaran - Ahmad Fauzi</td>
                                <td>Rp 500.000</td>
                                <td><span class="badge bg-success">Selesai</span></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>';
    
} elseif ($path == "/index.php/logout") {
    session_destroy();
    header("Location: /index.php");
    exit;
    
} else {
    http_response_code(404);
    echo "<h1>404 - Page Not Found</h1>";
}
?>
