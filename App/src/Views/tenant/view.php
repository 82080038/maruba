<?php include view_path('layout/header'); ?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title mb-0">Tenant Details</h4>
                    <div class="card-tools">
                        <a href="<?= route_url('tenant/edit/' . $tenant['id']) ?>" class="btn btn-primary">
                            <i class="fas fa-edit"></i> Edit
                        </a>
                        <a href="<?= route_url('tenant/billing/' . $tenant['id']) ?>" class="btn btn-success">
                            <i class="fas fa-file-invoice"></i> Billing
                        </a>
                        <a href="<?= route_url('tenant') ?>" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Back
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h5>Basic Information</h5>
                            <table class="table table-borderless">
                                <tr>
                                    <td><strong>Name:</strong></td>
                                    <td><?= htmlspecialchars($tenant['name']) ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Slug:</strong></td>
                                    <td><?= htmlspecialchars($tenant['slug']) ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Domain:</strong></td>
                                    <td><?= htmlspecialchars($tenant['domain'] ?? 'Not set') ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Status:</strong></td>
                                    <td>
                                        <span class="badge badge-<?= $tenant['status'] === 'active' ? 'success' : 'secondary' ?>">
                                            <?= ucfirst($tenant['status']) ?>
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>Subscription Plan:</strong></td>
                                    <td><?= ucfirst($tenant['subscription_plan'] ?? 'starter') ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Created:</strong></td>
                                    <td><?= date('d M Y H:i', strtotime($tenant['created_at'])) ?></td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <h5>Description</h5>
                            <p><?= nl2br(htmlspecialchars($tenant['description'] ?? 'No description')) ?></p>
                            
                            <h5>Statistics</h5>
                            <div class="row">
                                <div class="col-6">
                                    <div class="text-center">
                                        <h3><?= $tenant['member_count'] ?? 0 ?></h3>
                                        <small class="text-muted">Members</small>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="text-center">
                                        <h3><?= $tenant['loan_count'] ?? 0 ?></h3>
                                        <small class="text-muted">Loans</small>
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

<?php include view_path('layout/footer'); ?>
