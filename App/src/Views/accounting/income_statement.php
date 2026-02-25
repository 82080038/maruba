<?php include view_path('layout/header'); ?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title mb-0">Income Statement</h4>
                    <div class="card-tools">
                        <form method="get" class="d-inline-flex">
                            <input type="date" name="start_date" class="form-control form-control-sm" value="<?= htmlspecialchars($startDate ?? date('Y-m-01')) ?>" required>
                            <input type="date" name="end_date" class="form-control form-control-sm ml-2" value="<?= htmlspecialchars($endDate ?? date('Y-m-d')) ?>" required>
                            <button type="submit" class="btn btn-primary btn-sm ml-2">Filter</button>
                            <a href="<?= route_url('accounting/income_statement/export') ?>?start_date=<?= $startDate ?>&end_date=<?= $endDate ?>" class="btn btn-success btn-sm ml-2">Export PDF</a>
                        </form>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="card bg-success text-white">
                                <div class="card-body">
                                    <h5>Total Income</h5>
                                    <h3>Rp <?= number_format($incomeStatement['income'], 0, ',', '.') ?></h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card bg-warning text-white">
                                <div class="card-body">
                                    <h5>Total Expenses</h5>
                                    <h3>Rp <?= number_format($incomeStatement['expenses'], 0, ',', '.') ?></h3>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="card bg-<?= $incomeStatement['net_profit'] >= 0 ? 'success' : 'danger' ?> text-white">
                                <div class="card-body text-center">
                                    <h5>Net Profit/Loss</h5>
                                    <h3>Rp <?= number_format($incomeStatement['net_profit'], 0, ',', '.') ?></h3>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead class="table-dark">
                                <tr>
                                    <th>Account Type</th>
                                    <th>Account Code</th>
                                    <th>Account Name</th>
                                    <th>Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td colspan="4" class="text-center font-weight-bold bg-light">INCOME</td>
                                </tr>
                                <?php
                                $incomeAccounts = array_filter($allAccounts, fn($acc) => $acc['type'] === 'income');
                                foreach ($incomeAccounts as $account):
                                ?>
                                    <tr>
                                        <td><?= strtoupper($account['type']) ?></td>
                                        <td><?= htmlspecialchars($account['code']) ?></td>
                                        <td><?= htmlspecialchars($account['name']) ?></td>
                                        <td class="text-right text-success">+Rp <?= number_format($account['balance'], 0, ',', '.') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                <tr>
                                    <td colspan="4" class="text-center font-weight-bold bg-light">EXPENSES</td>
                                </tr>
                                <?php
                                $expenseAccounts = array_filter($allAccounts, fn($acc) => $acc['type'] === 'expense');
                                foreach ($expenseAccounts as $account):
                                ?>
                                    <tr>
                                        <td><?= strtoupper($account['type']) ?></td>
                                        <td><?= htmlspecialchars($account['code']) ?></td>
                                        <td><?= htmlspecialchars($account['name']) ?></td>
                                        <td class="text-right text-danger">-Rp <?= number_format($account['balance'], 0, ',', '.') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot class="table-dark">
                                <tr class="font-weight-bold">
                                    <td colspan="3">NET PROFIT/LOSS</td>
                                    <td class="text-right <?= $incomeStatement['net_profit'] >= 0 ? 'text-success' : 'text-danger' ?>">
                                        Rp <?= number_format($incomeStatement['net_profit'], 0, ',', '.') ?>
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    
                    <div class="mt-3">
                        <small class="text-muted">
                            <i class="fas fa-info-circle"></i>
                            Income Statement for period <?= $startDate ?> to <?= $endDate ?>. Net Profit = Income - Expenses.
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include view_path('layout/footer'); ?>
