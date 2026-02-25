<?php
ob_start();
?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h5>Pengguna</h5>
  <?php if (\App\Helpers\AuthHelper::can('users', 'create')): ?>
    <a href="<?= route_url('index.php/users/create') ?>" class="btn btn-primary">+ Tambah</a>
  <?php endif; ?>
</div>

<div class="table-responsive">
  <table class="table table-striped table-hover">
    <thead>
      <tr>
        <th>No</th>
        <th>Nama</th>
        <th>Username</th>
        <th>Role</th>
        <th>Status</th>
      </tr>
    </thead>
    <tbody>
      <?php $no=1; foreach ($users as $u): ?>
        <tr>
          <td><?= $no++ ?></td>
          <td><?= htmlspecialchars($u['name']) ?></td>
          <td><?= htmlspecialchars($u['username']) ?></td>
          <td><?= htmlspecialchars($u['role_name']) ?></td>
          <?php $us = \App\Helpers\UiHelper::statusInfo($u['status'] ?? ''); ?>
          <td><span class="badge bg-<?= htmlspecialchars($us['class']) ?>"><?= htmlspecialchars($us['text']) ?></span></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php
$content = ob_get_clean();
include view_path('layout_dashboard');
