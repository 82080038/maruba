<?php
ob_start();
?>
<h5>Log Audit</h5>

<div class="table-responsive">
  <table class="table table-striped table-hover">
    <thead>
      <tr>
        <th>No</th>
        <th>Pengguna</th>
        <th>Aksi</th>
        <th>Entitas</th>
        <th>ID</th>
        <th>Detail</th>
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
          <td><?= format_date_id($l['created_at'], true) ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php
$content = ob_get_clean();
include view_path('layout_admin');
