<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="card-title">Portfolio Performance Tracking</h4>
                    <div>
                        <button onclick="refreshPortfolio()" class="btn btn-primary">
                            <i class="fas fa-sync"></i> Refresh Metrics
                        </button>
                        <button onclick="exportPortfolio()" class="btn btn-success">
                            <i class="fas fa-download"></i> Export Report
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Portfolio Metrics Cards -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="card">
                                <div class="card-body text-center">
                                    <div class="mb-3">
                                        <h6 class="text-muted">Total Outstanding</h6>
                                        <h4 class="text-primary">Rp <?php echo number_format(($portfolioMetrics['total_outstanding'] ?? 0) / 1000000, 1); ?>M</h4>
                                    </div>
                                    <small class="text-muted">Active Loan Portfolio</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card">
                                <div class="card-body text-center">
                                    <div class="mb-3">
                                        <h6 class="text-muted">Monthly Yield</h6>
                                        <h4 class="text-success"><?php echo number_format($portfolioMetrics['annual_yield'] ?? 0, 2); ?>%</h4>
                                    </div>
                                    <small class="text-muted">Annual Portfolio Yield</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card">
                                <div class="card-body text-center">
                                    <div class="mb-3">
                                        <h6 class="text-muted">NPL Ratio</h6>
                                        <h4 class="<?php echo ($portfolioMetrics['npl_ratio'] ?? 0) > 5 ? 'text-danger' : 'text-warning'; ?>">
                                            <?php echo number_format($portfolioMetrics['npl_ratio'] ?? 0, 2); ?>%
                                        </h4>
                                    </div>
                                    <small class="text-muted">Non-Performing Loans</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card">
                                <div class="card-body text-center">
                                    <div class="mb-3">
                                        <h6 class="text-muted">PAR Ratio</h6>
                                        <h4 class="<?php echo ($portfolioMetrics['par_ratio'] ?? 0) > 10 ? 'text-danger' : 'text-info'; ?>">
                                            <?php echo number_format($portfolioMetrics['par_ratio'] ?? 0, 2); ?>%
                                        </h4>
                                    </div>
                                    <small class="text-muted">Portfolio at Risk</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Portfolio Health Status -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <h5>Portfolio Health Assessment</h5>
                                        <span class="badge bg-<?php
                                            $health = $portfolioMetrics['portfolio_health'] ?? [];
                                            echo match($health['status'] ?? 'unknown') {
                                                'excellent' => 'success',
                                                'good' => 'info',
                                                'fair' => 'warning',
                                                'poor' => 'danger',
                                                default => 'secondary'
                                            };
                                        ?> fs-6">
                                            <?php echo ucfirst($portfolioMetrics['portfolio_health']['status'] ?? 'Unknown'); ?>
                                        </span>
                                    </div>

                                    <div class="alert alert-<?php
                                        $health = $portfolioMetrics['portfolio_health'] ?? [];
                                        echo match($health['status'] ?? 'unknown') {
                                            'excellent' => 'success',
                                            'good' => 'info',
                                            'fair' => 'warning',
                                            'poor' => 'danger',
                                            default => 'secondary'
                                        };
                                    ?>">
                                        <h6><i class="fas fa-heartbeat"></i> <?php echo $portfolioMetrics['portfolio_health']['description'] ?? 'Portfolio health assessment unavailable'; ?></h6>
                                    </div>

                                    <!-- Health Metrics -->
                                    <div class="row text-center">
                                        <div class="col-md-3">
                                            <div class="border rounded p-3">
                                                <h6 class="text-muted">NPL Target</h6>
                                                <h4 class="text-primary">≤ 5%</h4>
                                                <small>Current: <?php echo number_format($portfolioMetrics['npl_ratio'] ?? 0, 1); ?>%</small>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="border rounded p-3">
                                                <h6 class="text-muted">PAR Target</h6>
                                                <h4 class="text-success">≤ 10%</h4>
                                                <small>Current: <?php echo number_format($portfolioMetrics['par_ratio'] ?? 0, 1); ?>%</small>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="border rounded p-3">
                                                <h6 class="text-muted">Yield Target</h6>
                                                <h4 class="text-info">≥ 15%</h4>
                                                <small>Current: <?php echo number_format($portfolioMetrics['annual_yield'] ?? 0, 1); ?>%</small>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="border rounded p-3">
                                                <h6 class="text-muted">Growth Target</h6>
                                                <h4 class="text-warning">≥ 10%</h4>
                                                <small>Monthly Target</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Performance Charts -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h5>Risk-Adjusted Performance</h5>
                                </div>
                                <div class="card-body">
                                    <canvas id="riskAdjustedChart" width="400" height="300"></canvas>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h5>Portfolio Composition</h5>
                                </div>
                                <div class="card-body">
                                    <canvas id="portfolioCompositionChart" width="400" height="300"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Benchmark Comparison -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h5>Benchmark Performance Comparison</h5>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-striped">
                                            <thead>
                                                <tr>
                                                    <th>Metric</th>
                                                    <th>Current Portfolio</th>
                                                    <th>Industry Benchmark</th>
                                                    <th>Peer Average</th>
                                                    <th>Status</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr>
                                                    <td>NPL Ratio</td>
                                                    <td><?php echo number_format($portfolioMetrics['npl_ratio'] ?? 0, 2); ?>%</td>
                                                    <td>3.5%</td>
                                                    <td>4.2%</td>
                                                    <td>
                                                        <span class="badge bg-<?php echo ($portfolioMetrics['npl_ratio'] ?? 0) <= 3.5 ? 'success' : 'warning'; ?>">
                                                            <?php echo ($portfolioMetrics['npl_ratio'] ?? 0) <= 3.5 ? 'Above Average' : 'Below Average'; ?>
                                                        </span>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td>Portfolio Yield</td>
                                                    <td><?php echo number_format($portfolioMetrics['annual_yield'] ?? 0, 2); ?>%</td>
                                                    <td>16.5%</td>
                                                    <td>15.8%</td>
                                                    <td>
                                                        <span class="badge bg-<?php echo ($portfolioMetrics['annual_yield'] ?? 0) >= 16.5 ? 'success' : 'warning'; ?>">
                                                            <?php echo ($portfolioMetrics['annual_yield'] ?? 0) >= 16.5 ? 'Above Average' : 'Below Average'; ?>
                                                        </span>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td>PAR 30 Days</td>
                                                    <td><?php echo number_format($portfolioMetrics['par_ratio'] ?? 0, 2); ?>%</td>
                                                    <td>8.5%</td>
                                                    <td>9.2%</td>
                                                    <td>
                                                        <span class="badge bg-<?php echo ($portfolioMetrics['par_ratio'] ?? 0) <= 8.5 ? 'success' : 'danger'; ?>">
                                                            <?php echo ($portfolioMetrics['par_ratio'] ?? 0) <= 8.5 ? 'Above Average' : 'Below Average'; ?>
                                                        </span>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td>Cost-to-Income Ratio</td>
                                                    <td>68.5%</td>
                                                    <td>65.0%</td>
                                                    <td>67.2%</td>
                                                    <td><span class="badge bg-warning">Below Average</span></td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Recommendations -->
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h5>Risk Management Actions</h5>
                                </div>
                                <div class="card-body">
                                    <div class="alert alert-warning">
                                        <h6><i class="fas fa-exclamation-triangle"></i> Immediate Focus Areas</h6>
                                        <ul class="mb-0">
                                            <li>Monitor high-risk segments closely</li>
                                            <li>Strengthen early warning systems</li>
                                            <li>Review underwriting criteria</li>
                                            <li>Enhance collection strategies</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h5>Growth Opportunities</h5>
                                </div>
                                <div class="card-body">
                                    <div class="alert alert-info">
                                        <h6><i class="fas fa-chart-line"></i> Strategic Initiatives</h6>
                                        <ul class="mb-0">
                                            <li>Target high-quality borrowers</li>
                                            <li>Diversify product offerings</li>
                                            <li>Optimize pricing strategies</li>
                                            <li>Expand geographic reach</li>
                                        </ul>
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
    // Risk-Adjusted Performance Chart
    const riskCtx = document.getElementById('riskAdjustedChart').getContext('2d');
    new Chart(riskCtx, {
        type: 'scatter',
        data: {
            datasets: [{
                label: 'Portfolio Performance',
                data: [
                    {x: <?php echo $portfolioMetrics['npl_ratio'] ?? 0; ?>, y: <?php echo $portfolioMetrics['annual_yield'] ?? 0; ?>}
                ],
                backgroundColor: 'rgb(75, 192, 192)',
                pointRadius: 8
            }, {
                label: 'Industry Benchmark',
                data: [{x: 3.5, y: 16.5}],
                backgroundColor: 'rgb(255, 99, 132)',
                pointRadius: 8
            }]
        },
        options: {
            responsive: true,
            scales: {
                x: {
                    title: {
                        display: true,
                        text: 'NPL Ratio (%)'
                    },
                    beginAtZero: true
                },
                y: {
                    title: {
                        display: true,
                        text: 'Annual Yield (%)'
                    },
                    beginAtZero: true
                }
            }
        }
    });

    // Portfolio Composition Chart
    const compositionCtx = document.getElementById('portfolioCompositionChart').getContext('2d');
    new Chart(compositionCtx, {
        type: 'doughnut',
        data: {
            labels: ['Performing Loans', 'Non-Performing Loans', 'In Arrears 1-30 days', 'In Arrears 31-90 days'],
            datasets: [{
                data: [
                    <?php echo 100 - ($portfolioMetrics['npl_ratio'] ?? 0) - ($portfolioMetrics['par_ratio'] ?? 0); ?>,
                    <?php echo $portfolioMetrics['npl_ratio'] ?? 0; ?>,
                    <?php echo ($portfolioMetrics['par_ratio'] ?? 0) * 0.7; ?>,
                    <?php echo ($portfolioMetrics['par_ratio'] ?? 0) * 0.3; ?>
                ],
                backgroundColor: [
                    'rgb(75, 192, 192)',  // Performing - Green
                    'rgb(255, 99, 132)',  // NPL - Red
                    'rgb(255, 205, 86)',  // Arrears 1-30 - Yellow
                    'rgb(255, 159, 64)'   // Arrears 31-90 - Orange
                ],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });
});

function refreshPortfolio() {
    if (confirm('Refresh portfolio performance metrics?')) {
        location.reload();
    }
}

function exportPortfolio() {
    alert('Export functionality would generate comprehensive portfolio performance report');
}
</script>
