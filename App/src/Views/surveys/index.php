<?php
ob_start();
?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h5>Survei</h5>
  <?php if (\App\Helpers\AuthHelper::can('surveys', 'create')): ?>
    <a href="<?= route_url('surveys/create') ?>" class="btn btn-primary">+ Tambah Survei</a>
  <?php endif; ?>
</div>

<div class="table-responsive">
  <table class="table table-striped table-hover">
    <thead>
      <tr>
        <th>No</th>
        <th>Pinjaman</th>
        <th>Anggota</th>
        <th>Surveyor</th>
        <th>Skor</th>
        <th>Hasil</th>
        <th>Lat/Lng</th>
        <th>Tanggal</th>
      </tr>
    </thead>
    <tbody>
      <?php $no=1; foreach ($surveys as $s): ?>
        <tr>
          <td><?= $no++ ?></td>
          <td><?= format_currency($s['amount']) ?></td>
          <td><?= htmlspecialchars($s['member_name']) ?></td>
          <td><?= htmlspecialchars($s['surveyor_name'] ?? '-') ?></td>
          <td><?= $s['score'] ?></td>
          <td><?= htmlspecialchars($s['result']) ?></td>
          <td><?= $s['geo_lat'] ?? '-' ?>, <?= $s['geo_lng'] ?? '-' ?></td>
          <td><?= date('d/m/Y H:i', strtotime($s['created_at'])) ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php
$content = ob_get_clean();
include view_path('layout_admin');
