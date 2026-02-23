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

<div class="manajer-dashboard-container">
    <!-- Manajer Header -->
    <div class="manajer-header">
        <div>
            <h1 class="manajer-title">👔 Dashboard Manajer</h1>
            <p class="text-muted">Selamat <?= htmlspecialchars($sapaan) ?>, <?= htmlspecialchars($displayName) ?> (<?= htmlspecialchars($role) ?>)</p>
        </div>
        <div class="header-actions">
            <button id="refresh-manajer-dashboard" class="btn btn-primary">
                <i class="bi bi-arrow-clockwise"></i>
                Refresh
            </button>
            <a href="<?= route_url('loans?status=review') ?>" class="btn btn-warning">
                <i class="bi bi-check-square"></i>
                Approval Queue
            </a>
        </div>
    </div>

    <!-- Manajer Metrics Grid -->
    <div class="metrics-grid" id="manajer-metrics-grid">
        <?php foreach ($metrics as $metric): ?>
        <div class="metric-card manajer-metric">
            <div class="metric-icon">
                <?php
                $iconClass = match($metric['label']) {
                    'Portfolio Health' => 'bi-shield-check',
                    'Risk Assessments' => 'bi-exclamation-triangle',
                    'Approval Queue' => 'bi-clock-history',
                    'Team Size' => 'bi-people',
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
            <a href="<?= route_url('loans?status=review') ?>" class="btn btn-warning">
                <i class="bi bi-check-square"></i>
                Review Pinjaman
            </a>
            <a href="<?= route_url('reports') ?>" class="btn btn-info">
                <i class="bi bi-graph-up"></i>
                Laporan Analitik
            </a>
            <a href="<?= route_url('members') ?>" class="btn btn-secondary">
                <i class="bi bi-people"></i>
                Kelola Anggota
            </a>
            <a href="<?= route_url('users') ?>" class="btn btn-primary">
                <i class="bi bi-person-gear"></i>
                Kelola Tim
            </a>
        </div>
    </div>

    <!-- Approval Queue -->
    <div class="approval-queue">
        <h3>Queue Approval</h3>
        <div class="queue-stats">
            <div class="queue-item">
                <div class="queue-number"><?= $metrics[2]['value'] ?></div>
                <div class="queue-label">Pinjaman Menunggu Review</div>
            </div>
            <div class="queue-item">
                <div class="queue-number urgent">3</div>
                <div class="queue-label">Prioritas Tinggi</div>
            </div>
            <div class="queue-item">
                <div class="queue-number normal">5</div>
                <div class="queue-label">Normal Priority</div>
            </div>
        </div>
    </div>

    <!-- Recent Activities -->
    <div class="recent-activities">
        <h3>Aktivitas Tim Terbaru</h3>
        <div class="activity-list">
            <?php if (empty($activities)): ?>
            <p class="text-muted">Belum ada aktivitas terbaru</p>
            <?php else: ?>
            <?php foreach ($activities as $activity): ?>
            <div class="activity-item">
                <div class="activity-icon">
                    <i class="bi bi-<?= match($activity['action']) {
                        'create' => 'plus-circle',
                        'approve' => 'check-circle',
                        'disburse' => 'cash',
                        'login' => 'box-arrow-in-right',
                        default => 'activity'
                    } ?>"></i>
                </div>
                <div class="activity-content">
                    <div class="activity-text">
                        <strong><?= htmlspecialchars($activity['user_name'] ?? 'System') ?></strong>
                        <?= htmlspecialchars($activity['action']) ?>
                        <?php if ($activity['entity']): ?>
                            <?= htmlspecialchars($activity['entity']) ?> #<?= htmlspecialchars($activity['entity_id']) ?>
                        <?php endif; ?>
                    </div>
                    <div class="activity-time">
                        <?= date('d/m/Y H:i', strtotime($activity['created_at'])) ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Risk Overview -->
    <div class="risk-overview">
        <h3>Overview Risiko</h3>
        <div class="risk-metrics">
            <div class="risk-item low">
                <div class="risk-label">Risiko Rendah</div>
                <div class="risk-bar">
                    <div class="risk-progress low" style="width: 75%"></div>
                </div>
                <div class="risk-value">75%</div>
            </div>
            <div class="risk-item medium">
                <div class="risk-label">Risiko Sedang</div>
                <div class="risk-bar">
                    <div class="risk-progress medium" style="width: 20%"></div>
                </div>
                <div class="risk-value">20%</div>
            </div>
            <div class="risk-item high">
                <div class="risk-label">Risiko Tinggi</div>
                <div class="risk-bar">
                    <div class="risk-progress high" style="width: 5%"></div>
                </div>
                <div class="risk-value">5%</div>
            </div>
        </div>
    </div>
</div>

<style>
.manajer-dashboard-container {
    padding: 20px;
    max-width: 1200px;
    margin: 0 auto;
}

.manajer-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
    padding-bottom: 20px;
    border-bottom: 2px solid #e9ecef;
}

.manajer-title {
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

.manajer-metric {
    border-left: 4px solid #ffc107;
}

.metric-icon {
    font-size: 2rem;
    color: #ffc107;
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

.quick-actions, .approval-queue, .recent-activities, .risk-overview {
    background: white;
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 30px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.quick-actions h3, .approval-queue h3, .recent-activities h3, .risk-overview h3 {
    margin-bottom: 15px;
    color: #2c3e50;
}

.action-buttons {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.queue-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 20px;
}

.queue-item {
    text-align: center;
    padding: 20px;
    border-radius: 8px;
    background: #f8f9fa;
}

.queue-number {
    font-size: 2rem;
    font-weight: bold;
    color: #ffc107;
}

.queue-number.urgent {
    color: #dc3545;
}

.queue-number.normal {
    color: #28a745;
}

.queue-label {
    margin-top: 5px;
    font-size: 0.9rem;
    color: #6c757d;
}

.activity-list {
    max-height: 300px;
    overflow-y: auto;
}

.activity-item {
    display: flex;
    align-items: flex-start;
    padding: 10px 0;
    border-bottom: 1px solid #e9ecef;
}

.activity-item:last-child {
    border-bottom: none;
}

.activity-icon {
    margin-right: 10px;
    color: #6c757d;
    font-size: 1.2rem;
}

.activity-content {
    flex: 1;
}

.activity-text {
    font-size: 0.9rem;
    margin-bottom: 5px;
}

.activity-time {
    font-size: 0.8rem;
    color: #6c757d;
}

.risk-metrics {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.risk-item {
    display: flex;
    align-items: center;
    gap: 15px;
}

.risk-label {
    min-width: 100px;
    font-size: 0.9rem;
}

.risk-bar {
    flex: 1;
    height: 8px;
    background: #e9ecef;
    border-radius: 4px;
    overflow: hidden;
}

.risk-progress {
    height: 100%;
    border-radius: 4px;
}

.risk-progress.low {
    background: #28a745;
}

.risk-progress.medium {
    background: #ffc107;
}

.risk-progress.high {
    background: #dc3545;
}

.risk-value {
    min-width: 40px;
    text-align: right;
    font-weight: bold;
    font-size: 0.9rem;
}

@media (max-width: 768px) {
    .manajer-header {
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
    
    .queue-stats {
        grid-template-columns: 1fr;
    }
    
    .risk-item {
        flex-direction: column;
        align-items: flex-start;
    }
}
</style>

<script>
document.getElementById('refresh-manajer-dashboard').addEventListener('click', function() {
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
