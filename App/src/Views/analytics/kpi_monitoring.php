<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="card-title">KPI Monitoring per Role</h4>
                    <div>
                        <button onclick="refreshKPIs()" class="btn btn-primary">
                            <i class="fas fa-sync"></i> Refresh KPIs
                        </button>
                        <button onclick="configureKPIs()" class="btn btn-success">
                            <i class="fas fa-cog"></i> Configure KPIs
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Role-based KPI Dashboard -->
                    <div class="row mb-4">
                        <!-- Admin KPIs -->
                        <div class="col-md-6 mb-4">
                            <div class="card">
                                <div class="card-header bg-primary text-white">
                                    <h5 class="mb-0"><i class="fas fa-crown"></i> Administrator KPIs</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-6 mb-3">
                                            <div class="text-center">
                                                <h4 class="text-primary"><?php echo number_format($kpis['total_members'] ?? 0); ?></h4>
                                                <small class="text-muted">Total Members</small>
                                                <div class="progress mt-2">
                                                    <div class="progress-bar bg-primary" style="width: <?php echo min(100, ($kpis['total_members'] ?? 0) / 1000 * 100); ?>%"></div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-6 mb-3">
                                            <div class="text-center">
                                                <h4 class="text-success">Rp <?php echo number_format(($kpis['monthly_revenue'] ?? 0) / 1000000, 1); ?>M</h4>
                                                <small class="text-muted">Monthly Revenue</small>
                                                <div class="progress mt-2">
                                                    <div class="progress-bar bg-success" style="width: <?php echo min(100, ($kpis['monthly_revenue'] ?? 0) / 50000000 * 100); ?>%"></div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-6 mb-3">
                                            <div class="text-center">
                                                <h4 class="text-warning"><?php echo number_format($kpis['npl_ratio'] ?? 0, 1); ?>%</h4>
                                                <small class="text-muted">NPL Ratio</small>
                                                <div class="progress mt-2">
                                                    <div class="progress-bar bg-warning" style="width: <?php echo ($kpis['npl_ratio'] ?? 0) * 10; ?>%"></div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-6 mb-3">
                                            <div class="text-center">
                                                <h4 class="text-info">98.5%</h4>
                                                <small class="text-muted">System Uptime</small>
                                                <div class="progress mt-2">
                                                    <div class="progress-bar bg-info" style="width: 98.5%"></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Kasir KPIs -->
                        <div class="col-md-6 mb-4">
                            <div class="card">
                                <div class="card-header bg-success text-white">
                                    <h5 class="mb-0"><i class="fas fa-cash-register"></i> Kasir KPIs</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-6 mb-3">
                                            <div class="text-center">
                                                <h4 class="text-success">47</h4>
                                                <small class="text-muted">Daily Transactions</small>
                                                <div class="progress mt-2">
                                                    <div class="progress-bar bg-success" style="width: 78%"></div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-6 mb-3">
                                            <div class="text-center">
                                                <h4 class="text-primary">Rp 125M</h4>
                                                <small class="text-muted">Cash Balance</small>
                                                <div class="progress mt-2">
                                                    <div class="progress-bar bg-primary" style="width: 85%"></div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-6 mb-3">
                                            <div class="text-center">
                                                <h4 class="text-info">12</h4>
                                                <small class="text-muted">Pending Approvals</small>
                                                <div class="progress mt-2">
                                                    <div class="progress-bar bg-info" style="width: 60%"></div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-6 mb-3">
                                            <div class="text-center">
                                                <h4 class="text-warning">8</h4>
                                                <small class="text-muted">Member Registrations</small>
                                                <div class="progress mt-2">
                                                    <div class="progress-bar bg-warning" style="width: 80%"></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Teller KPIs -->
                        <div class="col-md-6 mb-4">
                            <div class="card">
                                <div class="card-header bg-info text-white">
                                    <h5 class="mb-0"><i class="fas fa-user-friends"></i> Teller KPIs</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-6 mb-3">
                                            <div class="text-center">
                                                <h4 class="text-info">23</h4>
                                                <small class="text-muted">Service Queue</small>
                                                <div class="progress mt-2">
                                                    <div class="progress-bar bg-info" style="width: 65%"></div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-6 mb-3">
                                            <div class="text-center">
                                                <h4 class="text-success">4.8</h4>
                                                <small class="text-muted">Satisfaction Score</small>
                                                <div class="progress mt-2">
                                                    <div class="progress-bar bg-success" style="width: 96%"></div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-6 mb-3">
                                            <div class="text-center">
                                                <h4 class="text-primary">15</h4>
                                                <small class="text-muted">Transaction Volume</small>
                                                <div class="progress mt-2">
                                                    <div class="progress-bar bg-primary" style="width: 75%"></div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-6 mb-3">
                                            <div class="text-center">
                                                <h4 class="text-warning">2.3 min</h4>
                                                <small class="text-muted">Processing Time</small>
                                                <div class="progress mt-2">
                                                    <div class="progress-bar bg-warning" style="width: 77%"></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Manajer KPIs -->
                        <div class="col-md-6 mb-4">
                            <div class="card">
                                <div class="card-header bg-warning text-dark">
                                    <h5 class="mb-0"><i class="fas fa-chart-line"></i> Manajer KPIs</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-6 mb-3">
                                            <div class="text-center">
                                                <h4 class="text-warning">2.8%</h4>
                                                <small class="text-muted">Portfolio Yield</small>
                                                <div class="progress mt-2">
                                                    <div class="progress-bar bg-warning" style="width: 28%"></div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-6 mb-3">
                                            <div class="text-center">
                                                <h4 class="text-danger">4.2%</h4>
                                                <small class="text-muted">Risk Metrics</small>
                                                <div class="progress mt-2">
                                                    <div class="progress-bar bg-danger" style="width: 84%"></div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-6 mb-3">
                                            <div class="text-center">
                                                <h4 class="text-info">87%</h4>
                                                <small class="text-muted">Staff Performance</small>
                                                <div class="progress mt-2">
                                                    <div class="progress-bar bg-info" style="width: 87%"></div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-6 mb-3">
                                            <div class="text-center">
                                                <h4 class="text-success">95%</h4>
                                                <small class="text-muted">Compliance Status</small>
                                                <div class="progress mt-2">
                                                    <div class="progress-bar bg-success" style="width: 95%"></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- KPI Trends Chart -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h5>KPI Performance Trends (Last 6 Months)</h5>
                                </div>
                                <div class="card-body">
                                    <canvas id="kpiTrendsChart" width="800" height="300"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- KPI Alerts & Notifications -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h5>KPI Alerts & Performance Indicators</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="alert alert-success">
                                                <h6><i class="fas fa-check-circle"></i> Achievements</h6>
                                                <ul class="mb-0">
                                                    <li>Monthly revenue target exceeded by 15%</li>
                                                    <li>Customer satisfaction above 95%</li>
                                                    <li>System uptime maintained at 99.9%</li>
                                                    <li>New member acquisition on track</li>
                                                </ul>
                                            </div>
                                        </div>

                                        <div class="col-md-6">
                                            <div class="alert alert-warning">
                                                <h6><i class="fas fa-exclamation-triangle"></i> Areas for Attention</h6>
                                                <ul class="mb-0">
                                                    <li>NPL ratio approaching 5% threshold</li>
                                                    <li>Portfolio yield below target</li>
                                                    <li>Staff performance needs improvement</li>
                                                    <li>Compliance training completion pending</li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Detailed KPI Table -->
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h5>Detailed KPI Metrics</h5>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-striped">
                                            <thead>
                                                <tr>
                                                    <th>KPI Name</th>
                                                    <th>Current Value</th>
                                                    <th>Target</th>
                                                    <th>Status</th>
                                                    <th>Trend</th>
                                                    <th>Last Updated</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr>
                                                    <td>Total Active Members</td>
                                                    <td><?php echo number_format($kpis['total_members'] ?? 0); ?></td>
                                                    <td>1,200</td>
                                                    <td><span class="badge bg-success">On Track</span></td>
                                                    <td><i class="fas fa-arrow-up text-success"></i> +8.5%</td>
                                                    <td><?php echo date('d/m/Y H:i'); ?></td>
                                                </tr>
                                                <tr>
                                                    <td>Monthly Revenue</td>
                                                    <td>Rp <?php echo number_format(($kpis['monthly_revenue'] ?? 0) / 1000000, 1); ?>M</td>
                                                    <td>Rp 50M</td>
                                                    <td><span class="badge bg-success">Exceeded</span></td>
                                                    <td><i class="fas fa-arrow-up text-success"></i> +15.2%</td>
                                                    <td><?php echo date('d/m/Y H:i'); ?></td>
                                                </tr>
                                                <tr>
                                                    <td>NPL Ratio</td>
                                                    <td><?php echo number_format($kpis['npl_ratio'] ?? 0, 2); ?>%</td>
                                                    <td>< 5%</td>
                                                    <td><span class="badge bg-warning">Monitor</span></td>
                                                    <td><i class="fas fa-arrow-up text-danger"></i> +0.3%</td>
                                                    <td><?php echo date('d/m/Y H:i'); ?></td>
                                                </tr>
                                                <tr>
                                                    <td>Collection Rate</td>
                                                    <td><?php echo number_format($kpis['collection_rate'] ?? 0, 1); ?>%</td>
                                                    <td>> 95%</td>
                                                    <td><span class="badge bg-success">Excellent</span></td>
                                                    <td><i class="fas fa-arrow-up text-success"></i> +2.1%</td>
                                                    <td><?php echo date('d/m/Y H:i'); ?></td>
                                                </tr>
                                                <tr>
                                                    <td>Customer Satisfaction</td>
                                                    <td>4.8/5.0</td>
                                                    <td>> 4.5</td>
                                                    <td><span class="badge bg-success">Excellent</span></td>
                                                    <td><i class="fas fa-arrow-up text-success"></i> +0.2</td>
                                                    <td><?php echo date('d/m/Y H:i'); ?></td>
                                                </tr>
                                                <tr>
                                                    <td>Processing Time</td>
                                                    <td>2.3 min</td>
                                                    <td>< 3 min</td>
                                                    <td><span class="badge bg-success">Good</span></td>
                                                    <td><i class="fas fa-arrow-down text-success"></i> -0.4 min</td>
                                                    <td><?php echo date('d/m/Y H:i'); ?></td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // KPI Trends Chart
    const trendsCtx = document.getElementById('kpiTrendsChart').getContext('2d');
    new Chart(trendsCtx, {
        type: 'line',
        data: {
            labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
            datasets: [{
                label: 'Total Members',
                data: [850, 920, 1010, 1080, 1150, 1220],
                borderColor: 'rgb(75, 192, 192)',
                backgroundColor: 'rgba(75, 192, 192, 0.1)',
                tension: 0.1
            }, {
                label: 'Monthly Revenue (Rp M)',
                data: [38, 42, 39, 45, 48, 52],
                borderColor: 'rgb(54, 162, 235)',
                backgroundColor: 'rgba(54, 162, 235, 0.1)',
                tension: 0.1
            }, {
                label: 'NPL Ratio (%)',
                data: [3.2, 3.5, 3.8, 4.1, 4.3, 4.2],
                borderColor: 'rgb(255, 99, 132)',
                backgroundColor: 'rgba(255, 99, 132, 0.1)',
                tension: 0.1
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true
                }
            },
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });
});

function refreshKPIs() {
    if (confirm('Refresh all KPI metrics?')) {
        location.reload();
    }
}

function configureKPIs() {
    alert('KPI configuration interface would open here');
}
</script>
