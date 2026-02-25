<?php include view_path('layout/header'); ?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title mb-0">Chart of Accounts - FIXED VERSION</h4>
                    <div class="card-tools">
                        <a href="<?= route_url('index.php/accounting/chart/create') ?>" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Add Account
                        </a>
                        <a href="<?= route_url('index.php/accounting/chart/export') ?>" class="btn btn-success ml-2">
                            <i class="fas fa-download"></i> Export CSV
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <?php foreach ($hierarchy as $accountType => $categories): ?>
                        <div class="mb-4">
                            <h5 class="text-primary">
                                <?= strtoupper($accountType) ?>
                            </h5>
                            
                            <?php foreach ($categories as $category => $accounts): ?>
                                <div class="mb-3">
                                    <h6 class="text-muted"><?= ucfirst($category) ?></h6>
                                    <div class="table-responsive">
                                        <table class="table table-sm table-striped">
                                            <thead>
                                                <tr>
                                                    <th>Code</th>
                                                    <th>Name</th>
                                                    <th>Type</th>
                                                    <th>Status</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($accounts as $account): ?>
                                                    <tr>
                                                        <td><?= htmlspecialchars($account['code']) ?></td>
                                                        <td><?= htmlspecialchars($account['name']) ?></td>
                                                        <td>
                                                            <span class="badge badge-primary">
                                                                <?= strtoupper($account['type']) ?>
                                                            </span>
                                                        </td>
                                                        <td>
                                                            <span class="badge badge-<?= $account['is_active'] ? 'success' : 'secondary' ?>">
                                                                <?= $account['is_active'] ? 'Active' : 'Inactive' ?>
                                                            </span>
                                                        </td>
                                                        <td>
                                                            <div class="btn-group btn-group-sm">
                                                                <a href="<?= route_url('accounting/chart/edit/' . $account['id']) ?>" class="btn btn-outline-primary">
                                                                    <i class="fas fa-edit"></i>
                                                                </a>
                                                                <a href="<?= route_url('accounting/chart/view/' . $account['id']) ?>" class="btn btn-outline-info">
                                                                    <i class="fas fa-eye"></i>
                                                                </a>
                                                                <?php if ($account['is_active']): ?>
                                                                    <a href="<?= route_url('accounting/chart/deactivate/' . $account['id']) ?>" class="btn btn-outline-warning" onclick="return confirm('Deactivate this account?')">
                                                                        <i class="fas fa-ban"></i>
                                                                    </a>
                                                                <?php endif; ?>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endforeach; ?>
                    
                    <div class="mt-3">
                        <small class="text-muted">
                            <i class="fas fa-info-circle"></i>
                            Chart of Accounts shows all accounts organized by type and category. Active accounts can be used in journal entries.
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include view_path('layout/footer'); ?>
