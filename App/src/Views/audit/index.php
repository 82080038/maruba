<?php
ob_start();
?>
<h5>Audit Log</h5>

<div class="table-responsive">
  <table class="table table-striped table-hover">
    <thead>
      <tr>
        <th>No</th>
        <th>User</th>
        <th>Aksi</th>
        <th>Entity</th>
        <th>ID</th>
        <th>Meta</th>
        <th>Waktu</th>
      </tr>
    </thead>
    <tbody>
      <?php $no=1; foreach ($logs as $l): ?>
        <tr>
          <td><?= $no++ ?></td>
          <td><?= htmlspecialchars($l['user_name'] ?? '-') ?></td>
          <td><?= htmlspecialchars($l['action']) ?></td>
          <td><?= htmlspecialchars($l['entity'] ?? '-') ?></td>
          <td><?= $l['entity_id'] ?? '-' ?></td>
          <td><?= htmlspecialchars($l['meta'] ?? '-') ?></td>
          <td><?= date('d/m/Y H:i:s', strtotime($l['created_at'])) ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php
$content = ob_get_clean();
include view_path('layout_admin');
