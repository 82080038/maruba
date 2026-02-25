<?php include view_path('layout/header'); ?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title mb-0">Tenant Billing - <?= htmlspecialchars($tenant['name']) ?></h4>
                    <div class="card-tools">
                        <a href="<?= route_url('billing/create?tenant_id=' . $tenant['id']) ?>" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Create Billing
                        </a>
                        <a href="<?= route_url('tenant/view/' . $tenant['id']) ?>" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Back to Tenant
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Period</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                    <th>Due Date</th>
                                    <th>Paid Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($billings as $billing): ?>
                                    <tr>
                                        <td><?= date('M Y', strtotime($billing['billing_period_start'])) ?></td>
                                        <td>Rp <?= number_format($billing['amount'], 0, ',', '.') ?></td>
                                        <td>
                                            <span class="badge badge-<?= $billing['status'] === 'paid' ? 'success' : ($billing['status'] === 'overdue' ? 'danger' : 'warning') ?>">
                                                <?= ucfirst($billing['status']) ?>
                                            </span>
                                        </td>
                                        <td><?= date('d M Y', strtotime($billing['due_date'])) ?></td>
                                        <td><?= $billing['paid_date'] ? date('d M Y', strtotime($billing['paid_date'])) : '-' ?></td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <a href="<?= route_url('billing/view/' . $billing['id']) ?>" class="btn btn-outline-primary">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <?php if ($billing['status'] !== 'paid'): ?>
                                                    <a href="<?= route_url('billing/pay/' . $billing['id']) ?>" class="btn btn-outline-success">
                                                        <i class="fas fa-credit-card"></i> Pay
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
            </div>
        </div>
    </div>
</div>

<?php include view_path('layout/footer'); ?>
