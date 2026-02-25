<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="card-title">Notification Templates</h4>
                    <a href="/notifications/templates/create" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Create Template
                    </a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Template Code</th>
                                    <th>Name</th>
                                    <th>Type</th>
                                    <th>Subject</th>
                                    <th>Usage Count</th>
                                    <th>Last Used</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($templates ?? [] as $template): ?>
                                <tr>
                                    <td><code><?php echo htmlspecialchars($template['template_code']); ?></code></td>
                                    <td><?php echo htmlspecialchars($template['name']); ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo $template['type'] === 'email' ? 'primary' : ($template['type'] === 'sms' ? 'success' : ($template['type'] === 'whatsapp' ? 'info' : 'secondary')); ?>">
                                            <?php echo ucfirst($template['type']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars(substr($template['subject'] ?? 'N/A', 0, 50)); ?>...</td>
                                    <td><?php echo $template['usage_count'] ?? 0; ?></td>
                                    <td><?php echo $template['last_used'] ? date('d/m/Y H:i', strtotime($template['last_used'])) : 'Never'; ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo $template['is_active'] ? 'success' : 'secondary'; ?>">
                                            <?php echo $template['is_active'] ? 'Active' : 'Inactive'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="/notifications/templates/edit/<?php echo $template['id']; ?>" class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <button onclick="testTemplate(<?php echo $template['id']; ?>)" class="btn btn-sm btn-outline-info">
                                            <i class="fas fa-paper-plane"></i>
                                        </button>
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

    <div class="row mt-4">
        <div class="col-md-3">
            <div class="card">
                <div class="card-body text-center">
                    <h5 class="text-primary">Email Templates</h5>
                    <h3><?php echo count(array_filter($templates ?? [], function($t) { return $t['type'] === 'email'; })); ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body text-center">
                    <h5 class="text-success">SMS Templates</h5>
                    <h3><?php echo count(array_filter($templates ?? [], function($t) { return $t['type'] === 'sms'; })); ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body text-center">
                    <h5 class="text-info">WhatsApp Templates</h5>
                    <h3><?php echo count(array_filter($templates ?? [], function($t) { return $t['type'] === 'whatsapp'; })); ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body text-center">
                    <h5 class="text-secondary">Total Usage</h5>
                    <h3><?php echo array_sum(array_column($templates ?? [], 'usage_count')); ?></h3>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function testTemplate(templateId) {
    if (confirm('Send test notification using this template?')) {
        fetch(`/notifications/templates/test/${templateId}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            }
        })
        .then(response => response.json())
        .then(data => {
            alert('Test notification sent successfully');
        })
        .catch(error => {
            alert('Error sending test notification: ' + error.message);
        });
    }
}
</script>
