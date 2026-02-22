<?php
ob_start();
?>
<?php
  $u = current_user();
  $displayName = $u['name'] ?? ($u['username'] ?? 'User');
  $role = user_role() ?: ($u['role'] ?? '-');
  $appName = $_ENV['APP_NAME'] ?? getenv('APP_NAME') ?? 'KSP Lam Gabe Jaya';

  // Sapaan waktu dalam bahasa Indonesia
  $h = (int) date('G'); // 0-23
  if ($h < 11) {
      $sapaan = 'Pagi';
  } elseif ($h < 15) {
      $sapaan = 'Siang';
  } elseif ($h < 18) {
      $sapaan = 'Sore';
  } else {
      $sapaan = 'Malam';
  }
?>

<div class="dashboard-container">
    <!-- Dashboard Header -->
    <div class="dashboard-header">
        <div>
            <h1 class="h3 mb-4">Dashboard <?php echo APP_NAME; ?></h1>
            <p class="text-muted">Selamat <?= htmlspecialchars($sapaan) ?>, <?= htmlspecialchars($displayName) ?> (<?= htmlspecialchars($role) ?>)</p>
        </div>
        <div class="header-actions">
            <button id="refresh-dashboard" class="btn btn-primary">
                <i class="bi bi-arrow-clockwise"></i>
                Refresh
            </button>
            <label class="form-check-label">
                <input type="checkbox" id="auto-refresh-toggle" class="form-check-input" checked>
                Auto Refresh (30s)
            </label>
        </div>
    </div>

    <!-- Metrics Grid -->
    <div class="metrics-grid" id="metrics-grid">
        <!-- Metrics will be loaded via JavaScript -->
        <div class="loading">
            <div class="spinner"></div>
            Memuat data dashboard...
        </div>
    </div>

    <!-- Alerts Section -->
    <div class="alerts-section" id="alerts-section">
        <!-- Alerts will be loaded via JavaScript -->
    </div>

    <!-- Charts Section -->
    <div class="charts-section">
        <div class="chart-card">
            <h3 class="chart-title">Status Pinjaman</h3>
            <div id="loan-status-chart">
                <div class="loading">
                    <div class="spinner"></div>
                    Memuat chart...
                </div>
            </div>
        </div>

        <div class="chart-card">
            <h3 class="chart-title">Trend Pembayaran</h3>
            <div id="payment-trend-chart">
                <div class="loading">
                    <div class="spinner"></div>
                    Memuat chart...
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Activities Table -->
    <div class="table-container">
        <table class="table">
            <thead>
                <tr>
                    <th>Aksi</th>
                    <th>Entitas</th>
                    <th>Pengguna</th>
                    <th>Waktu</th>
                </tr>
            </thead>
            <tbody id="activities-table">
                <?php if (!empty($activities)): ?>
                    <?php foreach ($activities as $act): ?>
                        <tr>
                            <td><?= htmlspecialchars($act['action']) ?></td>
                            <td><?= htmlspecialchars($act['entity'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($act['user_name'] ?? '-') ?></td>
                            <td><?= htmlspecialchars(date('d/m/Y H:i', strtotime($act['created_at']))) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="4" class="text-center">Belum ada aktivitas.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
require_once __DIR__ . '/../IndonesianFormat.php';

// Get dashboard data
$metrics = $metrics ?? [];
$activities = $activities ?? [];
$user = $_SESSION['user'] ?? null;
$tenant = $_SESSION['tenant'] ?? null;
?>

<div class="container-fluid">
    <!-- Page Header -->
    <div class="row align-items-center mb-4">
        <div class="col">
            <h4 class="mb-1 fw-bold">
                <i class="bi bi-speedometer2 text-primary me-2"></i>Dashboard
            </h4>
            <p class="text-muted mb-0"><?php echo IndonesianFormat::translate('overview_and_recent_activity'); ?></p>
        </div>
        <div class="col-auto">
            <small class="text-muted">
                <i class="bi bi-clock me-1"></i><?php echo IndonesianFormat::now(); ?>
            </small>
        </div>
    </div>

    <!-- Metrics Cards -->
    <div class="row g-4 mb-4">
        <!-- Total Anggota -->
        <div class="col-xl-3 col-lg-6 col-md-6 col-sm-12">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h6 class="card-title text-muted mb-1"><?php echo IndonesianFormat::translate('total_members'); ?></h6>
                            <h4 class="card-text mb-0 fw-bold" data-format-type="number">
                                <?php echo $metrics['total_members'] ?? 0; ?>
                            </h4>
                        </div>
                        <div class="bg-primary bg-opacity-10 rounded-circle p-3">
                            <i class="bi bi-people-fill fs-2 text-primary"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pinjaman Aktif -->
        <div class="col-xl-3 col-lg-6 col-md-6 col-sm-12">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h6 class="card-title text-muted mb-1"><?php echo IndonesianFormat::translate('active_loans'); ?></h6>
                            <h4 class="card-text mb-0 fw-bold" data-format-type="number">
                                <?php echo $metrics['active_loans'] ?? 0; ?>
                            </h4>
                        </div>
                        <div class="bg-success bg-opacity-10 rounded-circle p-3">
                            <i class="bi bi-cash-stack fs-2 text-success"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Total Outstanding -->
        <div class="col-xl-3 col-lg-6 col-md-6 col-sm-12">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h6 class="card-title text-muted mb-1"><?php echo IndonesianFormat::translate('total_outstanding'); ?></h6>
                            <h4 class="card-text mb-0 fw-bold" data-format-type="currency">
                                <?php echo $metrics['total_outstanding'] ?? 0; ?>
                            </h4>
                        </div>
                        <div class="bg-warning bg-opacity-10 rounded-circle p-3">
                            <i class="bi bi-currency-dollar fs-2 text-warning"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- NPL Ratio -->
        <div class="col-xl-3 col-lg-6 col-md-6 col-sm-12">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h6 class="card-title text-muted mb-1">NPL Ratio</h6>
                            <h4 class="card-text mb-0 fw-bold" data-format-type="percentage">
                                <?php echo $metrics['npl_ratio'] ?? 0; ?>
                            </h4>
                        </div>
                        <div class="bg-<?php echo ($metrics['npl_ratio'] ?? 0) > 5 ? 'danger' : 'info'; ?> bg-opacity-10 rounded-circle p-3">
                            <i class="bi bi-exclamation-triangle-fill fs-2 text-<?php echo ($metrics['npl_ratio'] ?? 0) > 5 ? 'danger' : 'info'; ?>"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content Grid -->
    <div class="row">
        <!-- Recent Activities -->
        <div class="col-lg-8 mb-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-0">
                    <h6 class="card-title mb-0 fw-bold">
                        <i class="bi bi-activity me-2 text-primary"></i><?php echo IndonesianFormat::translate('recent_activities'); ?>
                    </h6>
                </div>
                <div class="card-body">
                    <div id="recentActivities" class="activity-list">
                        <?php if (empty($activities)): ?>
                            <p class="text-muted mb-0"><?php echo IndonesianFormat::translate('no_recent_activities'); ?></p>
                        <?php else: ?>
                            <?php foreach (array_slice($activities, 0, 10) as $activity): ?>
                                <div class="d-flex align-items-center mb-3">
                                    <div class="activity-icon bg-primary bg-opacity-10 rounded-circle p-2 me-3">
                                        <i class="bi bi-circle-fill text-primary"></i>
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div>
                                                <p class="mb-1 fw-medium">
                                                    <span class="text-primary"><?php echo htmlspecialchars($activity['user_name'] ?? 'System'); ?></span>
                                                    <?php echo htmlspecialchars($activity['action'] ?? ''); ?>
                                                    <?php echo htmlspecialchars($activity['entity'] ?? ''); ?>
                                                    <?php if (!empty($activity['entity_id'])): ?>
                                                        <strong>#<?php echo htmlspecialchars($activity['entity_id']); ?></strong>
                                                    <?php endif; ?>
                                                </p>
                                            </div>
                                            <small class="text-muted" data-timestamp="<?php echo $activity['created_at']; ?>">
                                                <?php echo IndonesianFormat::timeAgo($activity['created_at']); ?>
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- System Information -->
        <div class="col-lg-4 mb-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-0">
                    <h6 class="card-title mb-0 fw-bold">
                        <i class="bi bi-info-circle me-2 text-primary"></i><?php echo IndonesianFormat::translate('system_info'); ?>
                    </h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <strong><?php echo IndonesianFormat::translate('user'); ?>:</strong><br>
                        <span class="text-primary"><?php echo htmlspecialchars($user['name'] ?? '-'); ?></span>
                    </div>
                    <div class="mb-3">
                        <strong><?php echo IndonesianFormat::translate('role'); ?>:</strong><br>
                        <span class="text-muted"><?php echo htmlspecialchars($user['role'] ?? '-'); ?></span>
                    </div>
                    <div class="mb-3">
                        <strong><?php echo IndonesianFormat::translate('login_time'); ?>:</strong><br>
                        <span class="text-muted" id="loginTime"><?php echo IndonesianFormat::timeAgo($user['last_login'] ?? date('Y-m-d H:i:s')); ?></span>
                    </div>
                    <div class="mb-3">
                        <strong><?php echo IndonesianFormat::translate('current_time'); ?>:</strong><br>
                        <span class="text-muted" id="serverTime"><?php echo IndonesianFormat::now(); ?></span>
                    </div>
                    <?php if ($tenant): ?>
                        <div class="mb-3">
                            <strong><?php echo IndonesianFormat::translate('cooperative'); ?>:</strong><br>
                            <span class="text-success"><?php echo htmlspecialchars($tenant['name'] ?? '-'); ?></span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="card border-0 shadow-sm mt-3">
                <div class="card-header bg-white border-0">
                    <h6 class="card-title mb-0 fw-bold">
                        <i class="bi bi-lightning me-2 text-warning"></i><?php echo IndonesianFormat::translate('quick_actions'); ?>
                    </h6>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <button class="btn btn-primary btn-sm" onclick="KSP.Components.createMemberModal()">
                            <i class="bi bi-person-plus me-1"></i><?php echo IndonesianFormat::translate('add_member'); ?>
                        </button>
                        <button class="btn btn-success btn-sm" onclick="KSP.Components.createLoanModal()">
                            <i class="bi bi-plus-circle me-1"></i><?php echo IndonesianFormat::translate('create_loan'); ?>
                        </button>
                        <button class="btn btn-info btn-sm" onclick="window.location.href='/reports'">
                            <i class="bi bi-graph-up me-1"></i><?php echo IndonesianFormat::translate('view_reports'); ?>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Initialize Indonesian formatting for this page
$(document).ready(function() {
    // Update times every minute
    setInterval(function() {
        $('#serverTime').text(IndonesianFormat.now());
    }, 60000);

    // Update login time ago
    setInterval(function() {
        $('.auto-time-update').each(function() {
            const $element = $(this);
            const timestamp = $element.data('timestamp');
            if (timestamp) {
                $element.text(IndonesianFormat.timeAgo(timestamp));
            }
        });
    }, 60000);

    console.log('🇮🇩 Dashboard loaded with Indonesian formatting');
});
</script>

<!-- Load CSS and JS -->
<link rel="stylesheet" href="<?= asset_url('css/dashboard.css') ?>">
<script src="<?= asset_url('js/dashboard.js') ?>"></script>

<?php
$content = ob_get_clean();
include view_path('layout_dashboard');
