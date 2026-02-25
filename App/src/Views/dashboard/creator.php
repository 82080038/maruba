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

<div class="creator-dashboard-container">
    <!-- Creator Header -->
    <div class="creator-header">
        <div>
            <h1 class="creator-title">🎯 Dashboard Creator</h1>
            <p class="text-muted">Selamat <?= htmlspecialchars($sapaan) ?>, <?= htmlspecialchars($displayName) ?> (<?= htmlspecialchars($role) ?>)</p>
        </div>
        <div class="header-actions">
            <button id="refresh-creator-dashboard" class="btn btn-primary">
                <i class="bi bi-arrow-clockwise"></i>
                Refresh
            </button>
            <a href="<?= route_url('tenants') ?>" class="btn btn-success">
                <i class="bi bi-building"></i>
                Manage Tenants
            </a>
        </div>
    </div>

    <!-- Creator Metrics Grid -->
    <div class="metrics-grid" id="creator-metrics-grid">
        <?php foreach ($metrics as $metric): ?>
        <div class="metric-card creator-metric">
            <div class="metric-icon">
                <?php
                $iconClass = match($metric['label']) {
                    'System Health' => 'bi-heart-pulse',
                    'Active Tenants' => 'bi-building',
                    'Active Users' => 'bi-people',
                    'Security Alerts' => 'bi-shield-exclamation',
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

    <!-- System Actions -->
    <div class="system-actions">
        <h3>System Actions</h3>
        <div class="action-buttons">
            <a href="<?= route_url('tenants') ?>" class="btn btn-success">
                <i class="bi bi-building"></i>
                Manage Tenants
            </a>
            <a href="<?= route_url('users') ?>" class="btn btn-primary">
                <i class="bi bi-people"></i>
                Manage Users
            </a>
            <a href="<?= route_url('backup') ?>" class="btn btn-info">
                <i class="bi bi-cloud-download"></i>
                System Backup
            </a>
            <a href="<?= route_url('config') ?>" class="btn btn-warning">
                <i class="bi bi-gear"></i>
                System Config
            </a>
            <a href="<?= route_url('api') ?>" class="btn btn-secondary">
                <i class="bi bi-code-slash"></i>
                API Management
            </a>
        </div>
    </div>

    <!-- System Health -->
    <div class="system-health">
        <h3>System Health</h3>
        <div class="health-grid">
            <div class="health-item">
                <div class="health-icon online">
                    <i class="bi bi-server"></i>
                </div>
                <div class="health-content">
                    <div class="health-label">Web Server</div>
                    <div class="health-status">Apache - Online</div>
                    <div class="health-metrics">Uptime: 15 days</div>
                </div>
            </div>
            <div class="health-item">
                <div class="health-icon online">
                    <i class="bi bi-database"></i>
                </div>
                <div class="health-content">
                    <div class="health-label">Database</div>
                    <div class="health-status">MySQL - Online</div>
                    <div class="health-metrics">Connections: 12/100</div>
                </div>
            </div>
            <div class="health-item">
                <div class="health-icon online">
                    <i class="bi bi-hdd"></i>
                </div>
                <div class="health-content">
                    <div class="health-label">Storage</div>
                    <div class="health-status">45% Used</div>
                    <div class="health-metrics">225 GB / 500 GB</div>
                </div>
            </div>
            <div class="health-item">
                <div class="health-icon warning">
                    <i class="bi bi-cpu"></i>
                </div>
                <div class="health-content">
                    <div class="health-label">CPU Usage</div>
                    <div class="health-status">78% Load</div>
                    <div class="health-metrics">4 cores active</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tenant Statistics -->
    <div class="tenant-stats">
        <h3>Tenant Statistics</h3>
        <div class="tenant-grid">
            <div class="tenant-item">
                <div class="tenant-icon">
                    <i class="bi bi-building"></i>
                </div>
                <div class="tenant-content">
                    <div class="tenant-number"><?= $metrics[1]['value'] ?></div>
                    <div class="tenant-label">Active Tenants</div>
                    <div class="tenant-change positive">+2 this month</div>
                </div>
            </div>
            <div class="tenant-item">
                <div class="tenant-icon">
                    <i class="bi bi-people"></i>
                </div>
                <div class="tenant-content">
                    <div class="tenant-number">156</div>
                    <div class="tenant-label">Total Users</div>
                    <div class="tenant-change positive">+12 this week</div>
                </div>
            </div>
            <div class="tenant-item">
                <div class="tenant-icon">
                    <i class="bi bi-cash-stack"></i>
                </div>
                <div class="tenant-content">
                    <div class="tenant-number">Rp 2.5M</div>
                    <div class="tenant-label">Monthly Revenue</div>
                    <div class="tenant-change positive">+15% growth</div>
                </div>
            </div>
            <div class="tenant-item">
                <div class="tenant-icon">
                    <i class="bi bi-graph-up"></i>
                </div>
                <div class="tenant-content">
                    <div class="tenant-number">89%</div>
                    <div class="tenant-label">Satisfaction Rate</div>
                    <div class="tenant-change positive">+3% improvement</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Security Overview -->
    <div class="security-overview">
        <h3>Security Overview</h3>
        <div class="security-grid">
            <div class="security-item">
                <div class="security-icon safe">
                    <i class="bi bi-shield-check"></i>
                </div>
                <div class="security-content">
                    <div class="security-label">Firewall</div>
                    <div class="security-status">Active - 0 threats blocked</div>
                    <div class="security-metrics">Last scan: 2 hours ago</div>
                </div>
            </div>
            <div class="security-item">
                <div class="security-icon safe">
                    <i class="bi bi-lock"></i>
                </div>
                <div class="security-content">
                    <div class="security-label">Authentication</div>
                    <div class="security-status">2FA Enabled - 85% users</div>
                    <div class="security-metrics">Failed logins: 3 today</div>
                </div>
            </div>
            <div class="security-item">
                <div class="security-icon warning">
                    <i class="bi bi-bug"></i>
                </div>
                <div class="security-content">
                    <div class="security-label">Vulnerabilities</div>
                    <div class="security-status">2 medium issues found</div>
                    <div class="security-metrics">Patch required in 7 days</div>
                </div>
            </div>
            <div class="security-item">
                <div class="security-icon safe">
                    <i class="bi bi-eye"></i>
                </div>
                <div class="security-content">
                    <div class="security-label">Audit Trail</div>
                    <div class="security-status">Logging enabled - All systems</div>
                    <div class="security-metrics">Retention: 90 days</div>
                </div>
            </div>
        </div>
    </div>

    <!-- System Logs -->
    <div class="system-logs">
        <h3>System Logs</h3>
        <div class="log-filters">
            <select class="form-select">
                <option>All Logs</option>
                <option>Security</option>
                <option>System</option>
                <option>Database</option>
                <option>API</option>
            </select>
            <input type="date" class="form-control" value="<?= date('Y-m-d') ?>">
            <button class="btn btn-primary">
                <i class="bi bi-search"></i>
                Filter
            </button>
        </div>
        <div class="log-list">
            <?php if (empty($systemLogs)): ?>
            <p class="text-muted">No system logs available</p>
            <?php else: ?>
            <?php foreach ($systemLogs as $log): ?>
            <div class="log-item">
                <div class="log-icon">
                    <i class="bi bi-<?= match($log['action']) {
                        'login' => 'box-arrow-in-right',
                        'error' => 'exclamation-triangle',
                        'warning' => 'exclamation-circle',
                        'info' => 'info-circle',
                        default => 'activity'
                    } ?>"></i>
                </div>
                <div class="log-content">
                    <div class="log-text">
                        <strong><?= htmlspecialchars($log['action']) ?></strong>
                        <?php if ($log['entity']): ?>
                            on <?= htmlspecialchars($log['entity']) ?>
                            <?php if ($log['entity_id']): ?>
                                #<?= htmlspecialchars($log['entity_id']) ?>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                    <div class="log-time"><?= date('d/m/Y H:i:s', strtotime($log['created_at'])) ?></div>
                </div>
                <div class="log-level">
                    <span class="badge bg-<?= match($log['action']) {
                        'error' => 'danger',
                        'warning' => 'warning',
                        'login' => 'info',
                        default => 'secondary'
                    } ?>">
                        <?= htmlspecialchars($log['action']) ?>
                    </span>
                </div>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Performance Metrics -->
    <div class="performance-metrics">
        <h3>Performance Metrics</h3>
        <div class="performance-grid">
            <div class="perf-item">
                <div class="perf-label">Response Time</div>
                <div class="perf-value">245ms</div>
                <div class="perf-bar">
                    <div class="perf-progress good" style="width: 75%"></div>
                </div>
            </div>
            <div class="perf-item">
                <div class="perf-label">Throughput</div>
                <div class="perf-value">1,250 req/min</div>
                <div class="perf-bar">
                    <div class="perf-progress good" style="width: 85%"></div>
                </div>
            </div>
            <div class="perf-item">
                <div class="perf-label">Error Rate</div>
                <div class="perf-value">0.12%</div>
                <div class="perf-bar">
                    <div class="perf-progress good" style="width: 95%"></div>
                </div>
            </div>
            <div class="perf-item">
                <div class="perf-label">Memory Usage</div>
                <div class="perf-value">68%</div>
                <div class="perf-bar">
                    <div class="perf-progress warning" style="width: 68%"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.creator-dashboard-container {
    padding: 20px;
    max-width: 1200px;
    margin: 0 auto;
}

.creator-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
    padding-bottom: 20px;
    border-bottom: 2px solid #e9ecef;
}

.creator-title {
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

.creator-metric {
    border-left: 4px solid #dc3545;
}

.metric-icon {
    font-size: 2rem;
    color: #dc3545;
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

.system-actions, .system-health, .tenant-stats, .security-overview, .system-logs, .performance-metrics {
    background: white;
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 30px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.system-actions h3, .system-health h3, .tenant-stats h3, .security-overview h3, .system-logs h3, .performance-metrics h3 {
    margin-bottom: 15px;
    color: #2c3e50;
}

.action-buttons {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.health-grid, .tenant-grid, .security-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
}

.health-item, .tenant-item, .security-item {
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 15px;
    border-radius: 8px;
    background: #f8f9fa;
}

.health-icon, .tenant-icon, .security-icon {
    font-size: 1.5rem;
}

.health-icon.online, .tenant-icon, .security-icon.safe {
    color: #28a745;
}

.health-icon.warning, .security-icon.warning {
    color: #ffc107;
}

.health-icon.offline, .security-icon.danger {
    color: #dc3545;
}

.health-content, .tenant-content, .security-content {
    flex: 1;
}

.health-label, .tenant-label, .security-label {
    font-weight: 500;
    margin-bottom: 5px;
}

.health-status, .tenant-status, .security-status {
    font-size: 0.9rem;
    color: #6c757d;
    margin-bottom: 3px;
}

.health-metrics, .tenant-metrics, .security-metrics {
    font-size: 0.8rem;
    color: #6c757d;
}

.tenant-number {
    font-size: 1.8rem;
    font-weight: bold;
    color: #2c3e50;
    margin-bottom: 5px;
}

.tenant-change {
    font-size: 0.9rem;
    font-weight: 500;
}

.tenant-change.positive {
    color: #28a745;
}

.tenant-change.negative {
    color: #dc3545;
}

.log-filters {
    display: flex;
    gap: 10px;
    margin-bottom: 20px;
    flex-wrap: wrap;
}

.log-list {
    max-height: 400px;
    overflow-y: auto;
}

.log-item {
    display: flex;
    align-items: center;
    padding: 10px;
    border-bottom: 1px solid #e9ecef;
}

.log-item:last-child {
    border-bottom: none;
}

.log-icon {
    margin-right: 10px;
    color: #6c757d;
    font-size: 1.2rem;
}

.log-content {
    flex: 1;
}

.log-text {
    font-size: 0.9rem;
    margin-bottom: 5px;
}

.log-time {
    font-size: 0.8rem;
    color: #6c757d;
}

.performance-grid {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.perf-item {
    display: flex;
    align-items: center;
    gap: 15px;
}

.perf-label {
    min-width: 120px;
    font-weight: 500;
}

.perf-value {
    min-width: 80px;
    font-weight: bold;
    color: #2c3e50;
}

.perf-bar {
    flex: 1;
    height: 8px;
    background: #e9ecef;
    border-radius: 4px;
    overflow: hidden;
}

.perf-progress {
    height: 100%;
    border-radius: 4px;
}

.perf-progress.good {
    background: #28a745;
}

.perf-progress.warning {
    background: #ffc107;
}

.perf-progress.danger {
    background: #dc3545;
}

@media (max-width: 768px) {
    .creator-header {
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
    
    .health-grid, .tenant-grid, .security-grid {
        grid-template-columns: 1fr;
    }
    
    .log-filters {
        flex-direction: column;
    }
    
    .perf-item {
        flex-direction: column;
        align-items: flex-start;
    }
}
</style>

<script>
document.getElementById('refresh-creator-dashboard').addEventListener('click', function() {
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
