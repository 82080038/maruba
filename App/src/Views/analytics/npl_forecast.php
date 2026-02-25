<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="card-title">NPL Forecasting & Predictive Analytics</h4>
                    <div>
                        <button onclick="refreshForecast()" class="btn btn-primary">
                            <i class="fas fa-sync"></i> Refresh Forecast
                        </button>
                        <button onclick="exportForecast()" class="btn btn-success">
                            <i class="fas fa-download"></i> Export Report
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <!-- NPL Trend Chart -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h5>Historical NPL Trend (24 Months)</h5>
                                </div>
                                <div class="card-body">
                                    <canvas id="nplTrendChart" width="800" height="300"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Forecast Chart -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h5>NPL Forecast (Next 12 Months)</h5>
                                </div>
                                <div class="card-body">
                                    <canvas id="nplForecastChart" width="800" height="300"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Risk Factors Analysis -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h5>Current Risk Factors</h5>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <span>Economic Stress</span>
                                            <span class="badge bg-<?php echo ($riskFactors['economic_stress'] ?? 'low') === 'high' ? 'danger' : 'success'; ?>">
                                                <?php echo ucfirst($riskFactors['economic_stress'] ?? 'low'); ?>
                                            </span>
                                        </div>
                                        <div class="progress">
                                            <div class="progress-bar bg-<?php echo ($riskFactors['economic_stress'] ?? 'low') === 'high' ? 'danger' : 'success'; ?>"
                                                 style="width: <?php echo ($riskFactors['economic_stress'] ?? 'low') === 'high' ? '80' : '30'; ?>%"></div>
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <span>Portfolio Concentration</span>
                                            <span class="badge bg-<?php echo ($riskFactors['portfolio_concentration'] ?? 'low') === 'high' ? 'danger' : 'success'; ?>">
                                                <?php echo ucfirst($riskFactors['portfolio_concentration'] ?? 'low'); ?>
                                            </span>
                                        </div>
                                        <div class="progress">
                                            <div class="progress-bar bg-<?php echo ($riskFactors['portfolio_concentration'] ?? 'low') === 'high' ? 'danger' : 'success'; ?>"
                                                 style="width: <?php echo ($riskFactors['portfolio_concentration'] ?? 'low') === 'high' ? '75' : '25'; ?>%"></div>
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <span>Collection Performance</span>
                                            <span class="badge bg-<?php echo ($riskFactors['collection_performance'] ?? 'normal') === 'high_risk' ? 'danger' : 'success'; ?>">
                                                <?php echo ucfirst(str_replace('_', ' ', $riskFactors['collection_performance'] ?? 'normal')); ?>
                                            </span>
                                        </div>
                                        <div class="progress">
                                            <div class="progress-bar bg-<?php echo ($riskFactors['collection_performance'] ?? 'normal') === 'high_risk' ? 'danger' : 'success'; ?>"
                                                 style="width: <?php echo ($riskFactors['collection_performance'] ?? 'normal') === 'high_risk' ? '85' : '45'; ?>%"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h5>Forecast Summary</h5>
                                </div>
                                <div class="card-body">
                                    <?php if (!empty($forecast)): ?>
                                        <div class="row text-center">
                                            <div class="col-4">
                                                <h6 class="text-muted">Current NPL</h6>
                                                <h4 class="text-primary"><?php echo number_format($historicalNPL[count($historicalNPL)-1]['npl_ratio'] ?? 0, 2); ?>%</h4>
                                            </div>
                                            <div class="col-4">
                                                <h6 class="text-muted">3-Month Forecast</h6>
                                                <h4 class="text-warning"><?php echo number_format($forecast[2]['predicted_npl'] ?? 0, 2); ?>%</h4>
                                            </div>
                                            <div class="col-4">
                                                <h6 class="text-muted">12-Month Forecast</h6>
                                                <h4 class="text-danger"><?php echo number_format($forecast[11]['predicted_npl'] ?? 0, 2); ?>%</h4>
                                            </div>
                                        </div>

                                        <hr>

                                        <div class="alert alert-<?php echo ($forecast[11]['predicted_npl'] ?? 0) > 5 ? 'danger' : 'success'; ?>">
                                            <h6><i class="fas fa-exclamation-triangle"></i> Risk Assessment</h6>
                                            <p>
                                                <?php if (($forecast[11]['predicted_npl'] ?? 0) > 8): ?>
                                                    <strong>High Risk:</strong> NPL projected to exceed 8% in 12 months. Immediate action required.
                                                <?php elseif (($forecast[11]['predicted_npl'] ?? 0) > 5): ?>
                                                    <strong>Moderate Risk:</strong> NPL projected between 5-8%. Monitor closely.
                                                <?php else: ?>
                                                    <strong>Low Risk:</strong> NPL projected below 5%. Current trajectory acceptable.
                                                <?php endif; ?>
                                            </p>
                                        </div>
                                    <?php else: ?>
                                        <div class="text-center text-muted">
                                            <i class="fas fa-chart-line fa-3x mb-3"></i>
                                            <p>Insufficient historical data for forecasting</p>
                                            <small>Need at least 6 months of NPL data</small>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Recommendations -->
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h5>Risk Mitigation Recommendations</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <h6>Immediate Actions (Next 30 days):</h6>
                                            <ul class="list-unstyled">
                                                <li><i class="fas fa-check-circle text-success"></i> Review high-risk loan applications</li>
                                                <li><i class="fas fa-check-circle text-success"></i> Strengthen collection processes</li>
                                                <li><i class="fas fa-check-circle text-success"></i> Monitor economic indicators</li>
                                                <li><i class="fas fa-check-circle text-success"></i> Diversify loan portfolio</li>
                                            </ul>
                                        </div>
                                        <div class="col-md-6">
                                            <h6>Strategic Actions (3-6 months):</h6>
                                            <ul class="list-unstyled">
                                                <li><i class="fas fa-arrow-right text-primary"></i> Implement enhanced credit scoring</li>
                                                <li><i class="fas fa-arrow-right text-primary"></i> Develop early warning systems</li>
                                                <li><i class="fas fa-arrow-right text-primary"></i> Establish risk-based pricing</li>
                                                <li><i class="fas fa-arrow-right text-primary"></i> Build contingency reserves</li>
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
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // NPL Historical Trend Chart
    const nplTrendCtx = document.getElementById('nplTrendChart').getContext('2d');
    new Chart(nplTrendCtx, {
        type: 'line',
        data: {
            labels: <?php echo json_encode(array_column($historicalNPL ?? [], 'month')); ?>,
            datasets: [{
                label: 'Historical NPL Ratio (%)',
                data: <?php echo json_encode(array_column($historicalNPL ?? [], 'npl_ratio')); ?>,
                borderColor: 'rgb(255, 99, 132)',
                backgroundColor: 'rgba(255, 99, 132, 0.1)',
                tension: 0.1
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'NPL Ratio (%)'
                    }
                },
                x: {
                    title: {
                        display: true,
                        text: 'Month'
                    }
                }
            }
        }
    });

    // NPL Forecast Chart
    const nplForecastCtx = document.getElementById('nplForecastChart').getContext('2d');
    new Chart(nplForecastCtx, {
        type: 'line',
        data: {
            labels: <?php echo json_encode(array_column($forecast ?? [], 'month')); ?>,
            datasets: [{
                label: 'Forecasted NPL Ratio (%)',
                data: <?php echo json_encode(array_column($forecast ?? [], 'predicted_npl')); ?>,
                borderColor: 'rgb(54, 162, 235)',
                backgroundColor: 'rgba(54, 162, 235, 0.1)',
                borderDash: [5, 5],
                tension: 0.1
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'NPL Ratio (%)'
                    }
                },
                x: {
                    title: {
                        display: true,
                        text: 'Month'
                    }
                }
            }
        }
    });
});

function refreshForecast() {
    if (confirm('Refresh NPL forecast with latest data?')) {
        location.reload();
    }
}

function exportForecast() {
    // In a real implementation, this would generate and download a PDF/excel report
    alert('Export functionality would generate comprehensive NPL forecast report');
}
</script>
