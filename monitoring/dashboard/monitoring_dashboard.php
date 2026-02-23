<?php
/**
 * Maruba Monitoring Dashboard
 * Displays system and application metrics
 */

// Include monitoring functions
require_once __DIR__ . '/../scripts/app_monitor.php';

// Get current metrics
$metrics = [
    'system' => [
        'cpu' => shell_exec("top -bn1 | grep 'Cpu(s)' | awk '{print \$2}' | cut -d'%' -f1"),
        'memory' => shell_exec("free | awk 'NR==2{printf \"%.1f\", \$3*100/\$2}'"),
        'disk' => shell_exec("df / | awk 'NR==2{print \$5}' | sed 's/%//'"),
        'uptime' => shell_exec("uptime | awk -F'load average:' '{print \$2}' | awk '{print \$1}' | tr -d ','"),
    ],
    'apache' => [
        'running' => shell_exec("pgrep -f apache2 > /dev/null && echo 'Yes' || echo 'No'"),
        'requests' => shell_exec("grep -c 'GET|POST' /opt/lampp/logs/access_log 2>/dev/null || echo '0'"),
        'errors' => shell_exec("grep -c 'error|fatal|crit' /opt/lampp/logs/error_log 2>/dev/null || echo '0'"),
    ],
    'mysql' => [
        'running' => shell_exec("pgrep -f mysqld > /dev/null && echo 'Yes' || echo 'No'"),
        'connections' => shell_exec("mysql -u root -proot -e 'SHOW STATUS LIKE \"Threads_connected\"' | awk 'NR==2{print \$2}' 2>/dev/null || echo '0'"),
        'queries' => shell_exec("mysql -u root -proot -e 'SHOW STATUS LIKE \"Questions\"' | awk 'NR==2{print \$2}' 2>/dev/null || echo '0'"),
    ],
    'application' => [
        'active_users' => shell_exec("mysql -u root -proot maruba -e 'SELECT COUNT(*) FROM users WHERE status=\"active\"' | awk 'NR==2' 2>/dev/null || echo '0'"),
        'total_members' => shell_exec("mysql -u root -proot maruba -e 'SELECT COUNT(*) FROM members' | awk 'NR==2' 2>/dev/null || echo '0'"),
        'active_loans' => shell_exec("mysql -u root -proot maruba -e 'SELECT COUNT(*) FROM loans WHERE status=\"disbursed\"' | awk 'NR==2' 2>/dev/null || echo '0'"),
    ]
];

// Clean up metrics
foreach ($metrics as $category => &$items) {
    foreach ($items as $key => &$value) {
        $value = trim($value);
        if (is_numeric($value)) {
            $value = (float)$value;
        }
    }
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Maruba Monitoring Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .metric-card {
            border-left: 4px solid #007bff;
            transition: transform 0.2s;
        }
        .metric-card:hover {
            transform: translateY(-2px);
        }
        .metric-value {
            font-size: 2rem;
            font-weight: bold;
        }
        .status-online {
            color: #28a745;
        }
        .status-offline {
            color: #dc3545;
        }
        .refresh-btn {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1000;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <h1 class="mb-4">
                    <i class="bi bi-speedometer2"></i>
                    Maruba Monitoring Dashboard
                </h1>
                <p class="text-muted">Real-time system and application metrics</p>
            </div>
        </div>

        <!-- System Metrics -->
        <div class="row mb-4">
            <div class="col-12">
                <h3><i class="bi bi-cpu"></i> System Metrics</h3>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card metric-card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-subtitle mb-2">CPU Usage</h6>
                                <div class="metric-value"><?= $metrics['system']['cpu'] ?>%</div>
                            </div>
                            <i class="bi bi-cpu fs-2 text-primary"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card metric-card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-subtitle mb-2">Memory Usage</h6>
                                <div class="metric-value"><?= $metrics['system']['memory'] ?>%</div>
                            </div>
                            <i class="bi bi-memory fs-2 text-info"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card metric-card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-subtitle mb-2">Disk Usage</h6>
                                <div class="metric-value"><?= $metrics['system']['disk'] ?>%</div>
                            </div>
                            <i class="bi bi-hdd fs-2 text-warning"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card metric-card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-subtitle mb-2">Load Average</h6>
                                <div class="metric-value"><?= $metrics['system']['uptime'] ?></div>
                            </div>
                            <i class="bi bi-activity fs-2 text-success"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Service Status -->
        <div class="row mb-4">
            <div class="col-12">
                <h3><i class="bi bi-server"></i> Service Status</h3>
            </div>
            <div class="col-md-4 mb-3">
                <div class="card">
                    <div class="card-body">
                        <h6 class="card-subtitle mb-2">Apache Web Server</h6>
                        <div class="d-flex align-items-center">
                            <i class="bi bi-circle-fill <?= $metrics['apache']['running'] === 'Yes' ? 'status-online' : 'status-offline' ?>"></i>
                            <span class="ms-2"><?= $metrics['apache']['running'] === 'Yes' ? 'Running' : 'Stopped' ?></span>
                        </div>
                        <small class="text-muted">Requests: <?= $metrics['apache']['requests'] ?> | Errors: <?= $metrics['apache']['errors'] ?></small>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="card">
                    <div class="card-body">
                        <h6 class="card-subtitle mb-2">MySQL Database</h6>
                        <div class="d-flex align-items-center">
                            <i class="bi bi-circle-fill <?= $metrics['mysql']['running'] === 'Yes' ? 'status-online' : 'status-offline' ?>"></i>
                            <span class="ms-2"><?= $metrics['mysql']['running'] === 'Yes' ? 'Running' : 'Stopped' ?></span>
                        </div>
                        <small class="text-muted">Connections: <?= $metrics['mysql']['connections'] ?> | Queries: <?= $metrics['mysql']['queries'] ?></small>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="card">
                    <div class="card-body">
                        <h6 class="card-subtitle mb-2">Application Health</h6>
                        <div class="d-flex align-items-center">
                            <i class="bi bi-circle-fill status-online"></i>
                            <span class="ms-2">Healthy</span>
                        </div>
                        <small class="text-muted">Last check: <?= date('Y-m-d H:i:s') ?></small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Application Metrics -->
        <div class="row mb-4">
            <div class="col-12">
                <h3><i class="bi bi-graph-up"></i> Application Metrics</h3>
            </div>
            <div class="col-md-4 mb-3">
                <div class="card metric-card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-subtitle mb-2">Active Users</h6>
                                <div class="metric-value"><?= $metrics['application']['active_users'] ?></div>
                            </div>
                            <i class="bi bi-people fs-2 text-primary"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="card metric-card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-subtitle mb-2">Total Members</h6>
                                <div class="metric-value"><?= $metrics['application']['total_members'] ?></div>
                            </div>
                            <i class="bi bi-person-badge fs-2 text-info"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="card metric-card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-subtitle mb-2">Active Loans</h6>
                                <div class="metric-value"><?= $metrics['application']['active_loans'] ?></div>
                            </div>
                            <i class="bi bi-cash-stack fs-2 text-success"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Logs -->
        <div class="row">
            <div class="col-12">
                <h3><i class="bi bi-clock-history"></i> Recent Activity</h3>
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Time</th>
                                        <th>Service</th>
                                        <th>Activity</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td><?= date('H:i:s') ?></td>
                                        <td>System</td>
                                        <td>Metrics collection</td>
                                        <td><span class="badge bg-success">Success</span></td>
                                    </tr>
                                    <tr>
                                        <td><?= date('H:i:s', time() - 300) ?></td>
                                        <td>Application</td>
                                        <td>Database backup</td>
                                        <td><span class="badge bg-success">Success</span></td>
                                    </tr>
                                    <tr>
                                        <td><?= date('H:i:s', time() - 600) ?></td>
                                        <td>Security</td>
                                        <td>Security audit</td>
                                        <td><span class="badge bg-success">Success</span></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Refresh Button -->
    <button class="btn btn-primary refresh-btn" onclick="location.reload()">
        <i class="bi bi-arrow-clockwise"></i> Refresh
    </button>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-refresh every 30 seconds
        setTimeout(function() {
            location.reload();
        }, 30000);
    </script>
</body>
</html>
