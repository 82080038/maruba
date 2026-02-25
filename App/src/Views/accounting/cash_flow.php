<?php include view_path('layout/header'); ?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title mb-0">Cash Flow Statement</h4>
                    <div class="card-tools">
                        <form method="get" class="d-inline-flex">
                            <input type="date" name="start_date" class="form-control form-control-sm" value="<?= htmlspecialchars($startDate ?? date('Y-m-01')) ?>" required>
                            <input type="date" name="end_date" class="form-control form-control-sm ml-2" value="<?= htmlspecialchars($endDate ?? date('Y-m-d')) ?>" required>
                            <button type="submit" class="btn btn-primary btn-sm ml-2">Filter</button>
                            <a href="<?= route_url('index.php/accounting/cash_flow/export') ?>?start_date=<?= $startDate ?>&end_date=<?= $endDate ?>" class="btn btn-success btn-sm ml-2">Export PDF</a>
                        </form>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="card bg-primary text-white">
                                <div class="card-body">
                                    <h5>Operating Activities</h5>
                                    <h3>Rp <?= number_format($cashFlow['operating_activities'], 0, ',', '.') ?></h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-info text-white">
                                <div class="card-body">
                                    <h5>Investing Activities</h5>
                                    <h3>Rp <?= number_format($cashFlow['investing_activities'], 0, ',', '.') ?></h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-warning text-white">
                                <div class="card-body">
                                    <h5>Financing Activities</h5>
                                    <h3>Rp <?= number_format($cashFlow['financing_activities'], 0, ',', '.') ?></h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-<?= $cashFlow['net_cash_flow'] >= 0 ? 'success' : 'danger' ?> text-white">
                                <div class="card-body">
                                    <h5>Net Cash Flow</h5>
                                    <h3>Rp <?= number_format($cashFlow['net_cash_flow'], 0, ',', '.') ?></h3>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead class="table-dark">
                                <tr>
                                    <th>Activity Type</th>
                                    <th>Description</th>
                                    <th>Amount</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td colspan="4" class="text-center font-weight-bold bg-light">OPERATING ACTIVITIES</td>
                                </tr>
                                <?php foreach ($operatingActivities as $activity): ?>
                                    <tr>
                                        <td>Operating</td>
                                        <td><?= htmlspecialchars($activity['description']) ?></td>
                                        <td class="text-right <?= $activity['amount'] >= 0 ? 'text-success' : 'text-danger' ?>">
                                            <?= $activity['amount'] >= 0 ? '+' : '' ?>Rp <?= number_format($activity['amount'], 0, ',', '.') ?>
                                        </td>
                                        <td><?= date('d/m/Y', strtotime($activity['date'])) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                <tr>
                                    <td colspan="4" class="text-center font-weight-bold bg-light">INVESTING ACTIVITIES</td>
                                </tr>
                                <?php foreach ($investingActivities as $activity): ?>
                                    <tr>
                                        <td>Investing</td>
                                        <td><?= htmlspecialchars($activity['description']) ?></td>
                                        <td class="text-right <?= $activity['amount'] >= 0 ? 'text-success' : 'text-danger' ?>">
                                            <?= $activity['amount'] >= 0 ? '+' : '' ?>Rp <?= number_format($activity['amount'], 0, ',', '.') ?>
                                        </td>
                                        <td><?= date('d/m/Y', strtotime($activity['date'])) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                <tr>
                                    <td colspan="4" class="text-center font-weight-bold bg-light">FINANCING ACTIVITIES</td>
                                </tr>
                                <?php foreach ($financingActivities as $activity): ?>
                                    <tr>
                                        <td>Financing</td>
                                        <td><?= htmlspecialchars($activity['description']) ?></td>
                                        <td class="text-right <?= $activity['amount'] >= 0 ? 'text-success' : 'text-danger' ?>">
                                            <?= $activity['amount'] >= 0 ? '+' : '' ?>Rp <?= number_format($activity['amount'], 0, ',', '.') ?>
                                        </td>
                                        <td><?= date('d/m/Y', strtotime($activity['date'])) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot class="table-dark">
                                <tr class="font-weight-bold">
                                    <td colspan="2">NET CASH FLOW</td>
                                    <td class="text-right <?= $cashFlow['net_cash_flow'] >= 0 ? 'text-success' : 'text-danger' ?>">
                                        <?= $cashFlow['net_cash_flow'] >= 0 ? '+' : '' ?>Rp <?= number_format($cashFlow['net_cash_flow'], 0, ',', '.') ?>
                                    </td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    
                    <div class="mt-3">
                        <small class="text-muted">
                            <i class="fas fa-info-circle"></i>
                            Cash Flow Statement for period <?= $startDate ?> to <?= $endDate ?>. Shows cash movements from operating, investing, and financing activities.
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include view_path('layout/footer'); ?>
