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

<!-- Load CSS and JS -->
<link rel="stylesheet" href="<?= asset_url('css/dashboard.css') ?>">
<script src="<?= asset_url('js/dashboard.js') ?>"></script>

<?php
$content = ob_get_clean();
include view_path('layout_admin');

