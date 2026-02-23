<?php include view_path('layout/header'); ?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title mb-0">Tenant Management</h4>
                    <div class="card-tools">
                        <a href="<?= route_url('tenant/create') ?>" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Add Tenant
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Slug</th>
                                    <th>Status</th>
                                    <th>Created</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($tenants as $tenant): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($tenant['name']) ?></td>
                                        <td><?= htmlspecialchars($tenant['slug']) ?></td>
                                        <td>
                                            <span class="badge badge-<?= $tenant['status'] === 'active' ? 'success' : 'secondary' ?>">
                                                <?= ucfirst($tenant['status']) ?>
                                            </span>
                                        </td>
                                        <td><?= date('d M Y', strtotime($tenant['created_at'])) ?></td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <a href="<?= route_url('tenant/view/' . $tenant['id']) ?>" class="btn btn-outline-primary">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="<?= route_url('tenant/edit/' . $tenant['id']) ?>" class="btn btn-outline-info">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="<?= route_url('tenant/billing/' . $tenant['id']) ?>" class="btn btn-outline-success">
                                                    <i class="fas fa-file-invoice"></i>
                                                </a>
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
