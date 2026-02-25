<?php include view_path('layout/header'); ?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title mb-0">Trial Balance</h4>
                    <div class="card-tools">
                        <form method="get" class="d-inline-flex">
                            <input type="date" name="start_date" class="form-control form-control-sm" value="<?= htmlspecialchars($startDate ?? date('Y-m-01')) ?>" required>
                            <input type="date" name="end_date" class="form-control form-control-sm ml-2" value="<?= htmlspecialchars($endDate ?? date('Y-m-d')) ?>" required>
                            <button type="submit" class="btn btn-primary btn-sm ml-2">Filter</button>
                            <a href="<?= route_url('accounting/trial_balance/export') ?>?start_date=<?= $startDate ?>&end_date=<?= $endDate ?>" class="btn btn-success btn-sm ml-2">Export CSV</a>
                        </form>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead class="table-dark">
                                <tr>
                                    <th>Account Code</th>
                                    <th>Account Name</th>
                                    <th>Debit</th>
                                    <th>Credit</th>
                                    <th>Balance</th>
                                    <th>Type</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($trialBalance as $entry): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($entry['account_code']) ?></td>
                                        <td><?= htmlspecialchars($entry['account_name']) ?></td>
                                        <td class="text-right"><?= number_format($entry['total_debit'], 2, ',', '.') ?></td>
                                        <td class="text-right"><?= number_format($entry['total_credit'], 2, ',', '.') ?></td>
                                        <td class="text-right <?= $entry['balance'] >= 0 ? 'text-success' : 'text-danger' ?>">
                                            <?= number_format($entry['balance'], 2, ',', '.') ?>
                                        </td>
                                        <td>
                                            <span class="badge badge-<?= $entry['account_type'] === 'asset' ? 'primary' : ($entry['account_type'] === 'liability' ? 'info' : ($entry['account_type'] === 'equity' ? 'success' : ($entry['account_type'] === 'income' ? 'success' : 'warning'))) ?>">
                                                <?= strtoupper($entry['account_type']) ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot class="table-dark">
                                <tr class="font-weight-bold">
                                    <td colspan="2">TOTALS</td>
                                    <td class="text-right"><?= number_format(array_sum(array_column($trialBalance, 'total_debit')), 2, ',', '.') ?></td>
                                    <td class="text-right"><?= number_format(array_sum(array_column($trialBalance, 'total_credit')), 2, ',', '.') ?></td>
                                    <td class="text-right <?= array_sum(array_column($trialBalance, 'balance')) >= 0 ? 'text-success' : 'text-danger' ?>">
                                        <?= number_format(array_sum(array_column($trialBalance, 'balance')), 2, ',', '.') ?>
                                    </td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    
                    <div class="mt-3">
                        <small class="text-muted">
                            <i class="fas fa-info-circle"></i>
                            Trial Balance shows the balances of all accounts. Total Debit should equal Total Credit.
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include view_path('layout/footer'); ?>
