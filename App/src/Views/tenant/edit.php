<?php include view_path('layout/header'); ?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title mb-0">Edit Tenant</h4>
                </div>
                <div class="card-body">
                    <form method="POST" action="<?= route_url('tenant/update/' . $tenant['id']) ?>">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="name">Tenant Name</label>
                                    <input type="text" class="form-control" id="name" name="name" value="<?= htmlspecialchars($tenant['name']) ?>" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="slug">Slug</label>
                                    <input type="text" class="form-control" id="slug" name="slug" value="<?= htmlspecialchars($tenant['slug']) ?>" required>
                                    <small class="text-muted">Only lowercase letters, numbers, and hyphens</small>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="domain">Domain</label>
                                    <input type="text" class="form-control" id="domain" name="domain" value="<?= htmlspecialchars($tenant['domain'] ?? '') ?>" placeholder="example.maruba.id">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="subscription_plan">Subscription Plan</label>
                                    <select class="form-control" id="subscription_plan" name="subscription_plan">
                                        <option value="starter" <?= $tenant['subscription_plan'] === 'starter' ? 'selected' : '' ?>>Starter</option>
                                        <option value="professional" <?= $tenant['subscription_plan'] === 'professional' ? 'selected' : '' ?>>Professional</option>
                                        <option value="enterprise" <?= $tenant['subscription_plan'] === 'enterprise' ? 'selected' : '' ?>>Enterprise</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="status">Status</label>
                                    <select class="form-control" id="status" name="status">
                                        <option value="active" <?= $tenant['status'] === 'active' ? 'selected' : '' ?>>Active</option>
                                        <option value="inactive" <?= $tenant['status'] === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                                        <option value="suspended" <?= $tenant['status'] === 'suspended' ? 'selected' : '' ?>>Suspended</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="description">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="3"><?= htmlspecialchars($tenant['description'] ?? '') ?></textarea>
                        </div>
                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Update Tenant
                            </button>
                            <a href="<?= route_url('tenant/view/' . $tenant['id']) ?>" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Back
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include view_path('layout/footer'); ?>
