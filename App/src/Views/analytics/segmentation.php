<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="card-title">Customer Segmentation Analysis</h4>
                    <div>
                        <button onclick="refreshSegmentation()" class="btn btn-primary">
                            <i class="fas fa-sync"></i> Refresh Analysis
                        </button>
                        <button onclick="exportSegmentation()" class="btn btn-success">
                            <i class="fas fa-download"></i> Export Report
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Segmentation Overview -->
                    <div class="row mb-4">
                        <?php if (!empty($segmentStats)): ?>
                            <?php foreach ($segmentStats as $segmentName => $stats): ?>
                            <div class="col-md-4 mb-3">
                                <div class="card h-100">
                                    <div class="card-body text-center">
                                        <div class="mb-3">
                                            <h5 class="card-title text-capitalize"><?php echo $segmentName; ?> Segment</h5>
                                            <div class="progress mb-3">
                                                <div class="progress-bar bg-primary" style="width: <?php echo $stats['percentage']; ?>%"></div>
                                            </div>
                                            <h6 class="text-muted"><?php echo $stats['percentage']; ?>% of Members</h6>
                                        </div>
                                        <hr>
                                        <div class="row text-center">
                                            <div class="col-6">
                                                <small class="text-muted">Count</small>
                                                <h6><?php echo $stats['count']; ?></h6>
                                            </div>
                                            <div class="col-6">
                                                <small class="text-muted">Avg Income</small>
                                                <h6>Rp <?php echo number_format($stats['avg_income'] / 1000, 0); ?>K</h6>
                                            </div>
                                        </div>
                                        <div class="row text-center mt-2">
                                            <div class="col-6">
                                                <small class="text-muted">Avg Savings</small>
                                                <h6>Rp <?php echo number_format($stats['avg_savings'] / 1000, 0); ?>K</h6>
                                            </div>
                                            <div class="col-6">
                                                <small class="text-muted">Avg Loans</small>
                                                <h6>Rp <?php echo number_format($stats['avg_loans'] / 1000, 0); ?>K</h6>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <!-- Segmentation Chart -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h5>Segment Distribution</h5>
                                </div>
                                <div class="card-body">
                                    <canvas id="segmentDistributionChart" width="400" height="300"></canvas>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h5>Segment Characteristics</h5>
                                </div>
                                <div class="card-body">
                                    <canvas id="segmentCharacteristicsChart" width="400" height="300"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Detailed Member List -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h5>Members by Segment</h5>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-striped">
                                            <thead>
                                                <tr>
                                                    <th>Member Name</th>
                                                    <th>Segment</th>
                                                    <th>Monthly Income</th>
                                                    <th>Total Savings</th>
                                                    <th>Total Loans</th>
                                                    <th>Loan Count</th>
                                                    <th>Payment Score</th>
                                                    <th>Membership (Days)</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if (!empty($members)): ?>
                                                    <?php foreach ($members as $member): ?>
                                                    <tr>
                                                        <td><?php echo htmlspecialchars($member['name']); ?></td>
                                                        <td>
                                                            <span class="badge bg-<?php
                                                                $score = $this->calculateMemberScore($member);
                                                                if ($score >= 80) echo 'primary';
                                                                elseif ($score >= 60) echo 'success';
                                                                elseif ($score >= 40) echo 'warning';
                                                                elseif ($score >= 20) echo 'secondary';
                                                                else echo 'dark';
                                                            ?>">
                                                                <?php
                                                                    $score = $this->calculateMemberScore($member);
                                                                    if ($score >= 80) echo 'Platinum';
                                                                    elseif ($score >= 60) echo 'Gold';
                                                                    elseif ($score >= 40) echo 'Silver';
                                                                    elseif ($score >= 20) echo 'Bronze';
                                                                    else echo 'Prospect';
                                                                ?>
                                                            </span>
                                                        </td>
                                                        <td>Rp <?php echo number_format($member['monthly_income'], 0, ',', '.'); ?></td>
                                                        <td>Rp <?php echo number_format($member['total_savings'], 0, ',', '.'); ?></td>
                                                        <td>Rp <?php echo number_format($member['total_loans'], 0, ',', '.'); ?></td>
                                                        <td><?php echo $member['loan_count']; ?></td>
                                                        <td><?php echo number_format($member['payment_score'], 1); ?>%</td>
                                                        <td><?php echo $member['membership_days']; ?> days</td>
                                                    </tr>
                                                    <?php endforeach; ?>
                                                <?php else: ?>
                                                    <tr>
                                                        <td colspan="8" class="text-center text-muted">
                                                            No member data available for segmentation
                                                        </td>
                                                    </tr>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Segmentation Insights -->
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h5>Segmentation Insights</h5>
                                </div>
                                <div class="card-body">
                                    <div class="alert alert-info">
                                        <h6><i class="fas fa-lightbulb"></i> Key Findings</h6>
                                        <ul class="mb-0">
                                            <li><strong>High-Value Segments:</strong> Platinum and Gold members represent <?php echo ($segmentStats['platinum']['percentage'] ?? 0) + ($segmentStats['gold']['percentage'] ?? 0); ?>% of total members</li>
                                            <li><strong>Risk Distribution:</strong> <?php echo $segmentStats['bronze']['percentage'] ?? 0; ?>% of members in Bronze segment need monitoring</li>
                                            <li><strong>Growth Potential:</strong> <?php echo $segmentStats['prospect']['percentage'] ?? 0; ?>% of members are in early relationship stage</li>
                                            <li><strong>Revenue Concentration:</strong> Top 20% of members generate majority of revenue</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h5>Recommended Actions</h5>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <h6>🎯 For Platinum Members:</h6>
                                        <ul>
                                            <li>Priority service and dedicated relationship manager</li>
                                            <li>Exclusive products and preferential rates</li>
                                            <li>Early access to new features</li>
                                        </ul>
                                    </div>

                                    <div class="mb-3">
                                        <h6>📈 For Gold Members:</h6>
                                        <ul>
                                            <li>Enhanced service levels</li>
                                            <li>Upsell opportunities for additional products</li>
                                            <li>Loyalty rewards and recognition</li>
                                        </ul>
                                    </div>

                                    <div class="mb-3">
                                        <h6>⚠️ For Bronze Members:</h6>
                                        <ul>
                                            <li>Enhanced credit monitoring</li>
                                            <li>Financial literacy programs</li>
                                            <li>Alternative product offerings</li>
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
    // Segment Distribution Pie Chart
    const segmentCtx = document.getElementById('segmentDistributionChart').getContext('2d');
    new Chart(segmentCtx, {
        type: 'doughnut',
        data: {
            labels: ['Platinum', 'Gold', 'Silver', 'Bronze', 'Prospect'],
            datasets: [{
                data: [
                    <?php echo $segmentStats['platinum']['count'] ?? 0; ?>,
                    <?php echo $segmentStats['gold']['count'] ?? 0; ?>,
                    <?php echo $segmentStats['silver']['count'] ?? 0; ?>,
                    <?php echo $segmentStats['bronze']['count'] ?? 0; ?>,
                    <?php echo $segmentStats['prospect']['count'] ?? 0; ?>
                ],
                backgroundColor: [
                    'rgb(54, 162, 235)',   // Platinum - Blue
                    'rgb(255, 205, 86)',   // Gold - Yellow
                    'rgb(75, 192, 192)',   // Silver - Teal
                    'rgb(255, 99, 132)',   // Bronze - Red
                    'rgb(153, 102, 255)'   // Prospect - Purple
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

    // Segment Characteristics Radar Chart
    const characteristicsCtx = document.getElementById('segmentCharacteristicsChart').getContext('2d');
    new Chart(characteristicsCtx, {
        type: 'radar',
        data: {
            labels: ['Average Income', 'Total Savings', 'Loan Amount', 'Payment Score', 'Membership Tenure'],
            datasets: [{
                label: 'Platinum Segment',
                data: [
                    <?php echo ($segmentStats['platinum']['avg_income'] ?? 0) / 100000; ?>,
                    <?php echo ($segmentStats['platinum']['avg_savings'] ?? 0) / 100000; ?>,
                    <?php echo ($segmentStats['platinum']['avg_loans'] ?? 0) / 100000; ?>,
                    <?php echo $segmentStats['platinum']['avg_payment_score'] ?? 0; ?>,
                    <?php echo ($segmentStats['platinum']['count'] ?? 0) * 10; ?> // Scaled tenure
                ],
                borderColor: 'rgb(54, 162, 235)',
                backgroundColor: 'rgba(54, 162, 235, 0.1)',
            }, {
                label: 'Gold Segment',
                data: [
                    <?php echo ($segmentStats['gold']['avg_income'] ?? 0) / 100000; ?>,
                    <?php echo ($segmentStats['gold']['avg_savings'] ?? 0) / 100000; ?>,
                    <?php echo ($segmentStats['gold']['avg_loans'] ?? 0) / 100000; ?>,
                    <?php echo $segmentStats['gold']['avg_payment_score'] ?? 0; ?>,
                    <?php echo ($segmentStats['gold']['count'] ?? 0) * 10; ?>
                ],
                borderColor: 'rgb(255, 205, 86)',
                backgroundColor: 'rgba(255, 205, 86, 0.1)',
            }]
        },
        options: {
            responsive: true,
            scales: {
                r: {
                    beginAtZero: true
                }
            }
        }
    });
});

function refreshSegmentation() {
    if (confirm('Refresh customer segmentation analysis?')) {
        location.reload();
    }
}

function exportSegmentation() {
    alert('Export functionality would generate detailed customer segmentation report');
}
</script>
