<?php
ob_start();
?>
<?php
  $member = $_SESSION['member'];
  $totalLoans = $totalLoans ?? count($loans ?? []);
  $activeLoans = $activeLoans ?? count(array_filter($loans ?? [], fn($loan) => in_array($loan['status'], ['approved', 'disbursed'])));
  $totalOutstanding = $totalOutstanding ?? 0;
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Anggota - KOPERASI APP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        :root {
            --primary-color: #2563eb;
            --success-color: #10b981;
            --warning-color: #f59e0b;
            --danger-color: #ef4444;
        }

        .navbar-custom {
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .dashboard-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            border: none;
            transition: transform 0.2s;
        }

        .dashboard-card:hover {
            transform: translateY(-2px);
        }

        .metric-card {
            color: white;
            border-radius: 15px;
            padding: 1.5rem;
            text-align: center;
        }

        .metric-value {
            font-size: 2rem;
            font-weight: 700;
            margin: 0.5rem 0;
        }

        .sidebar {
            background: white;
            min-height: 100vh;
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
        }

        .nav-link {
            color: #64748b;
            font-weight: 500;
            padding: 0.75rem 1rem;
            margin: 0.25rem 0;
            border-radius: 10px;
            transition: all 0.2s;
        }

        .nav-link:hover,
        .nav-link.active {
            color: white;
        }

        .nav-link i {
            width: 20px;
            margin-right: 10px;
        }

        .loan-status {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .status-draft { background: #e2e8f0; color: #64748b; }
        .status-survey { background: #fef3c7; color: #d97706; }
        .status-review { background: #dbeafe; color: #2563eb; }
        .status-approved { background: #d1fae5; color: #065f46; }
        .status-disbursed { background: #10b981; color: white; }
        .status-default { background: #fee2e2; color: #dc2626; }

        .table-responsive {
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-custom navbar-dark">
        <div class="container-fluid">
            <a class="navbar-brand fw-bold" href="#">
                <i class="bi bi-bank me-2"></i><?php echo APP_NAME; ?>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle me-1"></i><?php echo htmlspecialchars($member['name']); ?>
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="<?php echo route_url('index.php/member/profile'); ?>">
                                <i class="bi bi-person me-2"></i>Profil
                            </a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="<?php echo route_url('index.php/member/logout'); ?>">
                                <i class="bi bi-box-arrow-right me-2"></i>Keluar
                            </a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 px-0">
                <div class="sidebar p-3">
                    <nav class="nav flex-column">
                        <a class="nav-link active" href="<?php echo route_url('index.php/member/dashboard'); ?>">
                            <i class="bi bi-house"></i> Dashboard
                        </a>
                        <a class="nav-link" href="<?php echo route_url('index.php/member/loans'); ?>">
                            <i class="bi bi-cash-stack"></i> Pinjaman Saya
                        </a>
                        <a class="nav-link" href="<?php echo route_url('index.php/member/repayments'); ?>">
                            <i class="bi bi-receipt"></i> Pembayaran
                        </a>
                        <a class="nav-link" href="<?php echo route_url('index.php/member/profile'); ?>">
                            <i class="bi bi-person"></i> Profil
                        </a>
                    </nav>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 p-4">
                <!-- Welcome Message -->
                <div class="mb-4">
                    <h2 class="mb-1">Selamat Datang, <?php echo htmlspecialchars(explode(' ', $member['name'])[0]); ?>!</h2>
                    <p class="text-muted">Ringkasan akun dan aktivitas Anda</p>
                </div>

                <!-- Metrics Cards -->
                <div class="row g-3 mb-4">
                    <div class="col-md-3">
                        <div class="metric-card">
                            <i class="bi bi-cash-stack fs-1 mb-2"></i>
                            <div class="metric-value"><?php echo $totalLoans; ?></div>
                            <div>Total Pinjaman</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="metric-card">
                            <i class="bi bi-check-circle fs-1 mb-2"></i>
                            <div class="metric-value"><?php echo $activeLoans; ?></div>
                            <div>Pinjaman Aktif</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="metric-card">
                            <i class="bi bi-piggy-bank fs-1 mb-2"></i>
                            <div class="metric-value">Rp <?php echo number_format($totalSavings ?? 0, 0, ',', '.'); ?></div>
                            <div>Total Simpanan</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="metric-card">
                            <i class="bi bi-star fs-1 mb-2"></i>
                            <div class="metric-value">Aktif</div>
                            <div>Status Anggota</div>
                        </div>
                    </div>
                </div>

                <!-- Recent Loans -->
                <div class="row g-3">
                    <div class="col-lg-8">
                        <div class="dashboard-card p-4">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h5 class="mb-0">Pinjaman Terbaru</h5>
                                <a href="<?php echo route_url('index.php/member/loans'); ?>" class="btn btn-outline-primary btn-sm">
                                    Lihat Semua
                                </a>
                            </div>

                            <?php if (!empty($loans)): ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Jumlah</th>
                                                <th>Tenor</th>
                                                <th>Status</th>
                                                <th>Tanggal</th>
                                                <th>Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach (array_slice($loans, 0, 5) as $loan): ?>
                                                <tr>
                                                    <td class="fw-semibold">Rp <?php echo number_format($loan['amount'], 0, ',', '.'); ?></td>
                                                    <td><?php echo $loan['tenor_months']; ?> bulan</td>
                                                    <td>
                                                        <span class="loan-status status-<?php echo $loan['status']; ?>">
                                                            <?php echo ucfirst($loan['status']); ?>
                                                        </span>
                                                    </td>
                                                    <td><?php echo date('d/m/Y', strtotime($loan['created_at'])); ?></td>
                                                    <td>
                                                        <a href="<?php echo route_url('index.php/member/loan-detail') . '?id=' . $loan['id']; ?>"
                                                           class="btn btn-sm btn-outline-primary">
                                                            Detail
                                                        </a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <div class="text-center py-4">
                                    <i class="bi bi-cash-stack fs-1 text-muted mb-3"></i>
                                    <h6 class="text-muted">Belum ada pinjaman</h6>
                                    <p class="text-muted small">Anda belum mengajukan pinjaman apapun</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="col-lg-4">
                        <div class="dashboard-card p-4">
                            <h5 class="mb-3">Informasi Cepat</h5>
                            <div class="d-flex align-items-center mb-3">
                                <i class="bi bi-person-circle fs-4 me-3 text-primary"></i>
                                <div>
                                    <div class="fw-semibold"><?php echo htmlspecialchars($member['name']); ?></div>
                                    <small class="text-muted">NIK: <?php echo htmlspecialchars($member['nik']); ?></small>
                                </div>
                            </div>
                            <div class="d-flex align-items-center mb-3">
                                <i class="bi bi-telephone fs-4 me-3 text-success"></i>
                                <div>
                                    <div><?php echo htmlspecialchars($member['phone']); ?></div>
                                    <small class="text-muted">Nomor telepon aktif</small>
                                </div>
                            </div>
                            <div class="d-flex align-items-center">
                                <i class="bi bi-geo-alt fs-4 me-3 text-warning"></i>
                                <div>
                                    <div><?php echo htmlspecialchars(substr($member['address'], 0, 30)) . '...'; ?></div>
                                    <small class="text-muted">Alamat terdaftar</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php
$content = ob_get_clean();
echo $content;
?>
