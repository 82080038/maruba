<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="card-title">Risk Scoring Engine</h4>
                    <div>
                        <button onclick="refreshModels()" class="btn btn-primary">
                            <i class="fas fa-sync"></i> Refresh Models
                        </button>
                        <button onclick="createModel()" class="btn btn-success">
                            <i class="fas fa-plus"></i> New Model
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Risk Models Overview -->
                    <div class="row mb-4">
                        <?php if (!empty($models)): ?>
                            <?php foreach ($models as $model): ?>
                            <div class="col-md-4 mb-3">
                                <div class="card h-100">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-start mb-3">
                                            <div>
                                                <h6 class="card-title"><?php echo htmlspecialchars($model['name']); ?></h6>
                                                <small class="text-muted"><?php echo htmlspecialchars($model['model_code']); ?></small>
                                            </div>
                                            <span class="badge bg-<?php echo $model['is_active'] ? 'success' : 'secondary'; ?>">
                                                <?php echo $model['is_active'] ? 'Active' : 'Inactive'; ?>
                                            </span>
                                        </div>

                                        <div class="row text-center mb-3">
                                            <div class="col-6">
                                                <small class="text-muted">Type</small>
                                                <h6><?php echo ucfirst(str_replace('_', ' ', $model['model_type'])); ?></h6>
                                            </div>
                                            <div class="col-6">
                                                <small class="text-muted">Algorithm</small>
                                                <h6><?php echo ucfirst(str_replace('_', ' ', $model['algorithm'])); ?></h6>
                                            </div>
                                        </div>

                                        <div class="row text-center mb-3">
                                            <div class="col-6">
                                                <small class="text-muted">Accuracy</small>
                                                <h6 class="text-<?php echo ($model['accuracy_score'] ?? 0) >= 75 ? 'success' : (($model['accuracy_score'] ?? 0) >= 60 ? 'warning' : 'danger'); ?>">
                                                    <?php echo number_format($model['accuracy_score'] ?? 0, 1); ?>%
                                                </h6>
                                            </div>
                                            <div class="col-6">
                                                <small class="text-muted">Version</small>
                                                <h6><?php echo htmlspecialchars($model['version'] ?? '1.0'); ?></h6>
                                            </div>
                                        </div>

                                        <div class="d-flex gap-2">
                                            <button onclick="testModel('<?php echo $model['model_code']; ?>')" class="btn btn-outline-primary btn-sm flex-fill">
                                                Test
                                            </button>
                                            <button onclick="editModel('<?php echo $model['id']; ?>')" class="btn btn-outline-secondary btn-sm flex-fill">
                                                Edit
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="col-12">
                                <div class="text-center py-5">
                                    <i class="fas fa-brain fa-3x text-muted mb-3"></i>
                                    <h5 class="text-muted">No Risk Models Configured</h5>
                                    <p class="text-muted">Create your first risk scoring model to begin automated credit assessment</p>
                                    <button onclick="createModel()" class="btn btn-primary">Create First Model</button>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Risk Assessment Interface -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h5>Risk Assessment</h5>
                                </div>
                                <div class="card-body">
                                    <form id="riskAssessmentForm">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label for="loanId" class="form-label">Loan Application</label>
                                                    <select class="form-select" id="loanId" name="loan_id" required>
                                                        <option value="">Select loan application...</option>
                                                        <?php
                                                        // This would be populated with pending loan applications
                                                        // For demo purposes, showing sample options
                                                        ?>
                                                        <option value="1">LA-2024-001 - John Doe - Rp 5,000,000</option>
                                                        <option value="2">LA-2024-002 - Jane Smith - Rp 10,000,000</option>
                                                        <option value="3">LA-2024-003 - Bob Johnson - Rp 15,000,000</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label for="modelSelect" class="form-label">Risk Model</label>
                                                    <select class="form-select" id="modelSelect" name="model_code">
                                                        <?php if (!empty($models)): ?>
                                                            <?php foreach ($models as $model): ?>
                                                                <option value="<?php echo $model['model_code']; ?>" <?php echo $model['is_active'] ? 'selected' : ''; ?>>
                                                                    <?php echo htmlspecialchars($model['name']); ?> (<?php echo number_format($model['accuracy_score'] ?? 0, 1); ?>% accuracy)
                                                                </option>
                                                            <?php endforeach; ?>
                                                        <?php endif; ?>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="d-flex gap-2">
                                            <button type="button" onclick="calculateRisk()" class="btn btn-primary">
                                                <i class="fas fa-calculator"></i> Calculate Risk Score
                                            </button>
                                            <button type="button" onclick="batchAssessment()" class="btn btn-outline-primary">
                                                <i class="fas fa-list"></i> Batch Assessment
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Risk Assessment Results -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="card" id="assessmentResults" style="display: none;">
                                <div class="card-header">
                                    <h5>Risk Assessment Results</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="text-center mb-4">
                                                <h3 id="riskScore" class="mb-2">--</h3>
                                                <h6 class="text-muted">Risk Score</h6>
                                                <div class="progress mt-3">
                                                    <div id="scoreBar" class="progress-bar" style="width: 0%"></div>
                                                </div>
                                            </div>

                                            <div class="text-center">
                                                <h5 id="riskLevel" class="mb-2">--</h5>
                                                <h6 class="text-muted">Risk Level</h6>
                                            </div>
                                        </div>

                                        <div class="col-md-6">
                                            <div id="recommendationCard" class="alert">
                                                <h6><i class="fas fa-lightbulb"></i> Recommendation</h6>
                                                <p id="recommendationText">--</p>
                                            </div>

                                            <div class="mt-3">
                                                <h6>Risk Factors:</h6>
                                                <ul id="riskFactors" class="list-unstyled">
                                                    <!-- Risk factors will be populated here -->
                                                </ul>
                                            </div>
                                        </div>
                                    </div>

                                    <hr>

                                    <div class="row">
                                        <div class="col-md-6">
                                            <button onclick="approveLoan()" class="btn btn-success btn-block">
                                                <i class="fas fa-check"></i> Approve Loan
                                            </button>
                                        </div>
                                        <div class="col-md-6">
                                            <button onclick="rejectLoan()" class="btn btn-danger btn-block">
                                                <i class="fas fa-times"></i> Reject Loan
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Risk Model Performance -->
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h5>Model Performance Metrics</h5>
                                </div>
                                <div class="card-body">
                                    <canvas id="modelPerformanceChart" width="400" height="300"></canvas>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h5>Risk Distribution</h5>
                                </div>
                                <div class="card-body">
                                    <canvas id="riskDistributionChart" width="400" height="300"></canvas>
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
    // Model Performance Chart
    const performanceCtx = document.getElementById('modelPerformanceChart').getContext('2d');
    new Chart(performanceCtx, {
        type: 'bar',
        data: {
            labels: ['Accuracy', 'Precision', 'Recall', 'F1-Score'],
            datasets: [{
                label: 'Model Performance',
                data: [78.5, 82.3, 75.1, 78.6],
                backgroundColor: 'rgba(54, 162, 235, 0.5)',
                borderColor: 'rgb(54, 162, 235)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true,
                    max: 100
                }
            }
        }
    });

    // Risk Distribution Chart
    const distributionCtx = document.getElementById('riskDistributionChart').getContext('2d');
    new Chart(distributionCtx, {
        type: 'doughnut',
        data: {
            labels: ['Low Risk (80-100)', 'Medium Risk (60-79)', 'High Risk (40-59)', 'Very High Risk (0-39)'],
            datasets: [{
                data: [35, 45, 15, 5],
                backgroundColor: [
                    'rgb(75, 192, 192)',  // Low - Green
                    'rgb(255, 205, 86)',  // Medium - Yellow
                    'rgb(255, 159, 64)',  // High - Orange
                    'rgb(255, 99, 132)'   // Very High - Red
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

function refreshModels() {
    if (confirm('Refresh risk scoring models?')) {
        location.reload();
    }
}

function createModel() {
    // Redirect to model creation page
    alert('Model creation interface would open here');
}

function testModel(modelCode) {
    alert(`Testing model: ${modelCode}`);
}

function editModel(modelId) {
    alert(`Edit model with ID: ${modelId}`);
}

function calculateRisk() {
    const form = document.getElementById('riskAssessmentForm');
    const formData = new FormData(form);

    if (!formData.get('loan_id')) {
        alert('Please select a loan application');
        return;
    }

    // Show loading state
    document.getElementById('assessmentResults').style.display = 'block';

    // Simulate risk calculation (in real implementation, this would make an AJAX call)
    setTimeout(() => {
        // Mock risk assessment result
        const mockResult = {
            loan_id: 1,
            risk_score: 72,
            risk_level: 'medium_risk',
            recommendation: 'Approve with conditions - Monitor closely',
            factors: {
                loan_to_income_ratio: '45%',
                membership_tenure: '24 months',
                income_level: 'Rp 4,500,000',
                loan_amount: 'Rp 2,000,000'
            }
        };

        displayRiskResult(mockResult);
    }, 2000);
}

function displayRiskResult(result) {
    // Update score display
    document.getElementById('riskScore').textContent = result.risk_score;
    document.getElementById('scoreBar').style.width = result.risk_score + '%';

    // Update risk level
    const riskLevelText = result.risk_level.replace('_', ' ').toUpperCase();
    document.getElementById('riskLevel').textContent = riskLevelText;

    // Update recommendation
    document.getElementById('recommendationText').textContent = result.recommendation;

    // Update risk factors
    const factorsList = document.getElementById('riskFactors');
    factorsList.innerHTML = '';
    for (const [key, value] of Object.entries(result.factors)) {
        const li = document.createElement('li');
        li.innerHTML = `<small><strong>${key.replace(/_/g, ' ')}:</strong> ${value}</small>`;
        factorsList.appendChild(li);
    }

    // Update card color based on risk level
    const card = document.getElementById('recommendationCard');
    card.className = 'alert';
    if (result.risk_score >= 80) {
        card.classList.add('alert-success');
    } else if (result.risk_score >= 60) {
        card.classList.add('alert-warning');
    } else {
        card.classList.add('alert-danger');
    }
}

function approveLoan() {
    if (confirm('Approve this loan application?')) {
        alert('Loan approved successfully');
        document.getElementById('assessmentResults').style.display = 'none';
        document.getElementById('riskAssessmentForm').reset();
    }
}

function rejectLoan() {
    if (confirm('Reject this loan application?')) {
        alert('Loan rejected');
        document.getElementById('assessmentResults').style.display = 'none';
        document.getElementById('riskAssessmentForm').reset();
    }
}

function batchAssessment() {
    alert('Batch assessment would process multiple loan applications');
}
</script>
