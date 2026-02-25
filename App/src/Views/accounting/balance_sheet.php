<?php include view_path('layout/header'); ?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title mb-0">Balance Sheet</h4>
                    <div class="card-tools">
                        <form method="get" class="d-inline-flex">
                            <input type="date" name="date" class="form-control form-control-sm" value="<?= htmlspecialchars($date ?? date('Y-m-d')) ?>" required>
                            <button type="submit" class="btn btn-primary btn-sm ml-2">Filter</button>
                            <a href="<?= route_url('index.php/accounting/balance_sheet/export') ?>?date=<?= $date ?>" class="btn btn-success btn-sm ml-2">Export PDF</a>
                        </form>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="card bg-primary text-white">
                                <div class="card-body">
                                    <h5>Total Assets</h5>
                                    <h3>Rp <?= number_format($balanceSheet['assets'], 0, ',', '.') ?></h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card bg-info text-white">
                                <div class="card-body">
                                    <h5>Total Liabilities & Equity</h5>
                                    <h3>Rp <?= number_format($balanceSheet['total_liabilities_equity'], 0, ',', '.') ?></h3>
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
                                    <th>Balance</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td colspan="4" class="text-center font-weight-bold bg-light">ASSETS</td>
                                </tr>
                                <?php
                                $assetAccounts = array_filter($allAccounts, fn($acc) => $acc['type'] === 'asset');
                                foreach ($assetAccounts as $account):
                                ?>
                                    <tr>
                                        <td><?= strtoupper($account['type']) ?></td>
                                        <td><?= htmlspecialchars($account['code']) ?></td>
                                        <td><?= htmlspecialchars($account['name']) ?></td>
                                        <td class="text-right"><?= number_format($account['balance'], 0, ',', '.') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                <tr>
                                    <td colspan="4" class="text-center font-weight-bold bg-light">LIABILITIES</td>
                                </tr>
                                <?php
                                $liabilityAccounts = array_filter($allAccounts, fn($acc) => $acc['type'] === 'liability');
                                foreach ($liabilityAccounts as $account):
                                ?>
                                    <tr>
                                        <td><?= strtoupper($account['type']) ?></td>
                                        <td><?= htmlspecialchars($account['code']) ?></td>
                                        <td><?= htmlspecialchars($account['name']) ?></td>
                                        <td class="text-right"><?= number_format($account['balance'], 0, ',', '.') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                <tr>
                                    <td colspan="4" class="text-center font-weight-bold bg-light">EQUITY</td>
                                </tr>
                                <?php
                                $equityAccounts = array_filter($allAccounts, fn($acc) => $acc['type'] === 'equity');
                                foreach ($equityAccounts as $account):
                                ?>
                                    <tr>
                                        <td><?= strtoupper($account['type']) ?></td>
                                        <td><?= htmlspecialchars($account['code']) ?></td>
                                        <td><?= htmlspecialchars($account['name']) ?></td>
                                        <td class="text-right"><?= number_format($account['balance'], 0, ',', '.') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot class="table-dark">
                                <tr class="font-weight-bold">
                                    <td colspan="3">TOTAL</td>
                                    <td class="text-right">Rp <?= number_format($balanceSheet['total_liabilities_equity'], 0, ',', '.') ?></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    
                    <div class="mt-3">
                        <small class="text-muted">
                            <i class="fas fa-info-circle"></i>
                            Balance Sheet shows the financial position as of <?= $date ?>. Assets = Liabilities + Equity.
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include view_path('layout/footer'); ?>
