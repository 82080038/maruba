<?php
ob_start();
?>
<?php
  $u = current_user();
  $displayName = $u['name'] ?? ($u['username'] ?? 'User');
  $role = user_role() ?: ($u['role'] ?? '-');

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

<div class="collector-dashboard-container">
    <!-- Collector Header -->
    <div class="collector-header">
        <div>
            <h1 class="collector-title">💼 Dashboard Collector</h1>
            <p class="text-muted">Selamat <?= htmlspecialchars($sapaan) ?>, <?= htmlspecialchars($displayName) ?> (<?= htmlspecialchars($role) ?>)</p>
        </div>
        <div class="header-actions">
            <button id="refresh-collector-dashboard" class="btn btn-primary">
                <i class="bi bi-arrow-clockwise"></i>
                Refresh
            </button>
            <a href="<?= route_url('repayments/create') ?>" class="btn btn-success">
                <i class="bi bi-cash"></i>
                Proses Penagihan
            </a>
        </div>
    </div>

    <!-- Collector Metrics Grid -->
    <div class="metrics-grid" id="collector-metrics-grid">
        <?php foreach ($metrics as $metric): ?>
        <div class="metric-card collector-metric">
            <div class="metric-icon">
                <?php
                $iconClass = match($metric['label']) {
                    'Collection Target' => 'bi-bullseye',
                    'Overdue Accounts' => 'bi-exclamation-circle',
                    'Success Rate' => 'bi-graph-up-arrow',
                    'Route Status' => 'bi-geo-alt',
                    default => 'bi-graph-up'
                };
                ?>
                <i class="bi <?= $iconClass ?>"></i>
            </div>
            <div class="metric-content">
                <h3 class="metric-value">
                    <?php
                    if ($metric['type'] === 'currency') {
                        echo 'Rp ' . number_format($metric['value'], 0, ',', '.');
                    } elseif ($metric['type'] === 'percent') {
                        echo $metric['value'] . '%';
                    } else {
                        echo htmlspecialchars($metric['value']);
                    }
                    ?>
                </h3>
                <p class="metric-label"><?= htmlspecialchars($metric['label']) ?></p>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Quick Actions -->
    <div class="quick-actions">
        <h3>Quick Actions</h3>
        <div class="action-buttons">
            <a href="<?= route_url('repayments/create') ?>" class="btn btn-success">
                <i class="bi bi-cash"></i>
                Proses Penagihan
            </a>
            <a href="<?= route_url('repayments') ?>" class="btn btn-info">
                <i class="bi bi-list-ul"></i>
                Daftar Penagihan
            </a>
            <a href="<?= route_url('members') ?>" class="btn btn-secondary">
                <i class="bi bi-people"></i>
                Data Anggota
            </a>
            <a href="tel:" class="btn btn-warning">
                <i class="bi bi-telephone"></i>
                Hubungi Anggota
            </a>
        </div>
    </div>

    <!-- Collection Route -->
    <div class="collection-route">
        <h3>Rute Penagihan Hari Ini</h3>
        <div class="route-summary">
            <div class="route-stat">
                <div class="route-number">12</div>
                <div class="route-label">Total Kunjungan</div>
            </div>
            <div class="route-stat">
                <div class="route-number completed">5</div>
                <div class="route-label">Selesai</div>
            </div>
            <div class="route-stat">
                <div class="route-number pending">7</div>
                <div class="route-label">Tersisa</div>
            </div>
        </div>
    </div>

    <!-- Collection List -->
    <div class="collection-list">
        <h3>Daftar Penagihan Prioritas</h3>
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Anggota</th>
                        <th>Telepon</th>
                        <th>Jatuh Tempo</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($collectionList)): ?>
                    <tr>
                        <td colspan="6" class="text-center">Tidak ada penagihan pending</td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($collectionList as $collection): ?>
                    <tr>
                        <td><?= htmlspecialchars($collection['member_name'] ?? '-') ?></td>
                        <td>
                            <a href="tel:<?= htmlspecialchars($collection['phone'] ?? '') ?>" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-telephone"></i>
                                <?= htmlspecialchars($collection['phone'] ?? '-') ?>
                            </a>
                        </td>
                        <td><?= date('d/m/Y', strtotime($collection['due_date'])) ?></td>
                        <td>Rp <?= number_format($collection['amount_due'], 0, ',', '.') ?></td>
                        <td>
                            <span class="badge bg-<?= $collection['status'] === 'due' ? 'warning' : 'success' ?>">
                                <?= htmlspecialchars($collection['status']) ?>
                            </span>
                        </td>
                        <td>
                            <a href="<?= route_url('repayments/create?loan_id=' . $collection['loan_id']) ?>" class="btn btn-sm btn-success">
                                <i class="bi bi-cash"></i>
                                Bayar
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Performance Chart -->
    <div class="performance-chart">
        <h3>Performa Penagihan</h3>
        <div class="chart-container">
            <div class="chart-bars">
                <div class="chart-bar">
                    <div class="bar-label">Sen</div>
                    <div class="bar-fill" style="height: 80%"></div>
                    <div class="bar-value">80%</div>
                </div>
                <div class="chart-bar">
                    <div class="bar-label">Sel</div>
                    <div class="bar-fill" style="height: 65%"></div>
                    <div class="bar-value">65%</div>
                </div>
                <div class="chart-bar">
                    <div class="bar-label">Rab</div>
                    <div class="bar-fill" style="height: 90%"></div>
                    <div class="bar-value">90%</div>
                </div>
                <div class="chart-bar">
                    <div class="bar-label">Kam</div>
                    <div class="bar-fill" style="height: 75%"></div>
                    <div class="bar-value">75%</div>
                </div>
                <div class="chart-bar">
                    <div class="bar-label">Jum</div>
                    <div class="bar-fill" style="height: 85%"></div>
                    <div class="bar-value">85%</div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.collector-dashboard-container {
    padding: 20px;
    max-width: 1200px;
    margin: 0 auto;
}

.collector-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
    padding-bottom: 20px;
    border-bottom: 2px solid #e9ecef;
}

.collector-title {
    color: #2c3e50;
    font-size: 2rem;
    font-weight: 600;
    margin: 0;
}

.header-actions {
    display: flex;
    gap: 10px;
}

.metrics-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.metric-card {
    background: white;
    border-radius: 12px;
    padding: 20px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    display: flex;
    align-items: center;
    transition: transform 0.2s;
}

.metric-card:hover {
    transform: translateY(-2px);
}

.collector-metric {
    border-left: 4px solid #17a2b8;
}

.metric-icon {
    font-size: 2rem;
    color: #17a2b8;
    margin-right: 15px;
}

.metric-content h3 {
    font-size: 1.5rem;
    font-weight: 600;
    margin: 0;
    color: #2c3e50;
}

.metric-content p {
    margin: 5px 0 0 0;
    color: #6c757d;
    font-size: 0.9rem;
}

.quick-actions, .collection-route, .collection-list, .performance-chart {
    background: white;
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 30px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.quick-actions h3, .collection-route h3, .collection-list h3, .performance-chart h3 {
    margin-bottom: 15px;
    color: #2c3e50;
}

.action-buttons {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.route-summary {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
    gap: 20px;
}

.route-stat {
    text-align: center;
    padding: 20px;
    border-radius: 8px;
    background: #f8f9fa;
}

.route-number {
    font-size: 2rem;
    font-weight: bold;
    color: #17a2b8;
}

.route-number.completed {
    color: #28a745;
}

.route-number.pending {
    color: #ffc107;
}

.route-label {
    margin-top: 5px;
    font-size: 0.9rem;
    color: #6c757d;
}

.chart-container {
    height: 200px;
    overflow-x: auto;
}

.chart-bars {
    display: flex;
    align-items: flex-end;
    height: 100%;
    gap: 20px;
    min-width: 400px;
}

.chart-bar {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    height: 100%;
}

.bar-label {
    margin-bottom: 10px;
    font-weight: bold;
    color: #6c757d;
}

.bar-fill {
    width: 40px;
    background: linear-gradient(to top, #17a2b8, #20c997);
    border-radius: 4px 4px 0 0;
    transition: height 0.3s ease;
}

.bar-value {
    margin-top: 10px;
    font-weight: bold;
    color: #2c3e50;
}

@media (max-width: 768px) {
    .collector-header {
        flex-direction: column;
        gap: 15px;
        text-align: center;
    }
    
    .metrics-grid {
        grid-template-columns: 1fr;
    }
    
    .action-buttons {
        flex-direction: column;
    }
    
    .route-summary {
        grid-template-columns: 1fr;
    }
    
    .chart-bars {
        min-width: 300px;
    }
}
</style>

<script>
document.getElementById('refresh-collector-dashboard').addEventListener('click', function() {
    location.reload();
});

// Auto refresh every 30 seconds
setInterval(function() {
    location.reload();
}, 30000);
</script>

<?php
$content = ob_get_clean();
include view_path('layout_dashboard');
?>
