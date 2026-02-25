<div class="container-fluid">
    <div class="row">
        <!-- KPI Cards -->
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted">Total Members</h6>
                            <h4 class="mb-0"><?php echo number_format($kpis['total_members'] ?? 0); ?></h4>
                        </div>
                        <div class="text-success">
                            <i class="fas fa-users fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted">Total Outstanding</h6>
                            <h4 class="mb-0">Rp <?php echo number_format(($kpis['total_loans'] ?? 0) / 1000000, 1); ?>M</h4>
                        </div>
                        <div class="text-primary">
                            <i class="fas fa-money-bill-wave fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted">NPL Ratio</h6>
                            <h4 class="mb-0 <?php echo ($kpis['npl_ratio'] ?? 0) > 5 ? 'text-danger' : 'text-success'; ?>">
                                <?php echo number_format($kpis['npl_ratio'] ?? 0, 1); ?>%
                            </h4>
                        </div>
                        <div class="text-<?php echo ($kpis['npl_ratio'] ?? 0) > 5 ? 'danger' : 'success'; ?>">
                            <i class="fas fa-chart-line fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted">Monthly Revenue</h6>
                            <h4 class="mb-0">Rp <?php echo number_format(($kpis['monthly_revenue'] ?? 0) / 1000000, 1); ?>M</h4>
                        </div>
                        <div class="text-info">
                            <i class="fas fa-dollar-sign fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-4">
        <!-- Analytics Widgets -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5>Member Growth Trend</h5>
                </div>
                <div class="card-body">
                    <canvas id="memberChart" width="400" height="200"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5>Loan Portfolio Trend</h5>
                </div>
                <div class="card-body">
                    <canvas id="loanChart" width="400" height="200"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-4">
        <!-- Quick Actions -->
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5>Advanced Analytics</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <a href="/analytics/npl-forecast" class="btn btn-outline-primary btn-block">
                                <i class="fas fa-chart-line"></i><br>
                                NPL Forecast
                            </a>
                        </div>
                        <div class="col-md-3">
                            <a href="/analytics/segmentation" class="btn btn-outline-success btn-block">
                                <i class="fas fa-users"></i><br>
                                Customer Segmentation
                            </a>
                        </div>
                        <div class="col-md-3">
                            <a href="/analytics/portfolio" class="btn btn-outline-info btn-block">
                                <i class="fas fa-portfolio"></i><br>
                                Portfolio Performance
                            </a>
                        </div>
                        <div class="col-md-3">
                            <a href="/analytics/risk-scoring" class="btn btn-outline-warning btn-block">
                                <i class="fas fa-exclamation-triangle"></i><br>
                                Risk Scoring
                            </a>
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-md-3">
                            <a href="/analytics/kpi-monitoring" class="btn btn-outline-secondary btn-block">
                                <i class="fas fa-tachometer-alt"></i><br>
                                KPI Monitoring
                            </a>
                        </div>
                        <div class="col-md-3">
                            <a href="/accounting" class="btn btn-outline-dark btn-block">
                                <i class="fas fa-calculator"></i><br>
                                Accounting
                            </a>
                        </div>
                        <div class="col-md-3">
                            <a href="/payments/auto-debit" class="btn btn-outline-primary btn-block">
                                <i class="fas fa-credit-card"></i><br>
                                Auto Debit
                            </a>
                        </div>
                        <div class="col-md-3">
                            <a href="/notifications/templates" class="btn btn-outline-success btn-block">
                                <i class="fas fa-bell"></i><br>
                                Notifications
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Risk Alerts -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5>Risk Alerts</h5>
                </div>
                <div class="card-body">
                    <?php if (($kpis['npl_ratio'] ?? 0) > 5): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle"></i>
                        <strong>High NPL Risk:</strong> Current NPL ratio (<?php echo number_format($kpis['npl_ratio'], 1); ?>%) exceeds 5% threshold.
                        <a href="/analytics/npl-forecast" class="alert-link">View forecast</a>
                    </div>
                    <?php endif; ?>

                    <?php if (($kpis['collection_rate'] ?? 100) < 85): ?>
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-circle"></i>
                        <strong>Collection Alert:</strong> Collection rate (<?php echo number_format($kpis['collection_rate'], 1); ?>%) below 85% target.
                        <a href="/analytics/portfolio" class="alert-link">Analyze portfolio</a>
                    </div>
                    <?php endif; ?>

                    <?php if (empty($alerts)): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i>
                        <strong>All Clear:</strong> No critical risk alerts at this time.
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Member Growth Chart
    const memberCtx = document.getElementById('memberChart').getContext('2d');
    new Chart(memberCtx, {
        type: 'line',
        data: {
            labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
            datasets: [{
                label: 'Total Members',
                data: [120, 135, 148, 162, 178, 195],
                borderColor: 'rgb(75, 192, 192)',
                tension: 0.1
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });

    // Loan Portfolio Chart
    const loanCtx = document.getElementById('loanChart').getContext('2d');
    new Chart(loanCtx, {
        type: 'bar',
        data: {
            labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
            datasets: [{
                label: 'Loan Amount (Rp M)',
                data: [450, 480, 520, 490, 530, 550],
                backgroundColor: 'rgba(54, 162, 235, 0.5)',
                borderColor: 'rgb(54, 162, 235)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
});
</script>
