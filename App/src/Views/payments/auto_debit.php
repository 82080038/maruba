<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="card-title">Auto Debit Schedules</h4>
                    <div>
                        <a href="/payments/auto-debit/create" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Create Auto Debit
                        </a>
                        <button onclick="processAutoDebits()" class="btn btn-success">
                            <i class="fas fa-play"></i> Process Pending
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Member</th>
                                    <th>Loan Amount</th>
                                    <th>Debit Amount</th>
                                    <th>Frequency</th>
                                    <th>Next Debit</th>
                                    <th>Success Rate</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($schedules ?? [] as $schedule): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($schedule['member_name']); ?></td>
                                    <td>Rp <?php echo number_format($schedule['loan_amount'], 0, ',', '.'); ?></td>
                                    <td>Rp <?php echo number_format($schedule['debit_amount'], 0, ',', '.'); ?></td>
                                    <td><?php echo ucfirst($schedule['frequency']); ?></td>
                                    <td><?php echo date('d/m/Y', strtotime($schedule['next_debit_date'])); ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo $schedule['success_rate'] >= 80 ? 'success' : ($schedule['success_rate'] >= 60 ? 'warning' : 'danger'); ?>">
                                            <?php echo $schedule['success_rate']; ?>%
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?php echo $schedule['is_active'] ? 'success' : 'secondary'; ?>">
                                            <?php echo $schedule['is_active'] ? 'Active' : 'Inactive'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="/payments/auto-debit/transactions/<?php echo $schedule['id']; ?>" class="btn btn-sm btn-outline-info">
                                            <i class="fas fa-history"></i>
                                        </a>
                                        <?php if ($schedule['is_active']): ?>
                                        <button onclick="deactivateSchedule(<?php echo $schedule['id']; ?>)" class="btn btn-sm btn-outline-danger">
                                            <i class="fas fa-stop"></i>
                                        </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function processAutoDebits() {
    if (confirm('Process all pending auto debit schedules?')) {
        fetch('/payments/auto-debit/process', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            }
        })
        .then(response => response.json())
        .then(data => {
            alert(`Auto debit processing completed:\nSuccessful: ${data.successful}\nFailed: ${data.failed}`);
            location.reload();
        })
        .catch(error => {
            alert('Error processing auto debits: ' + error.message);
        });
    }
}

function deactivateSchedule(scheduleId) {
    if (confirm('Deactivate this auto debit schedule?')) {
        fetch(`/payments/auto-debit/deactivate/${scheduleId}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            }
        })
        .then(response => response.json())
        .then(data => {
            alert('Auto debit schedule deactivated');
            location.reload();
        })
        .catch(error => {
            alert('Error deactivating schedule: ' + error.message);
        });
    }
}
</script>
