<?php
ob_start();
?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h5>Daftar Anggota</h5>
  <?php if (\App\Helpers\AuthHelper::can('members', 'create')): ?>
    <a href="<?= route_url('members/create') ?>" class="btn btn-primary">+ Tambah</a>
  <?php endif; ?>
</div>

<div class="table-responsive">
  <table class="table table-striped table-hover">
    <thead>
      <tr>
        <th>No</th>
        <th>Nama</th>
        <th>NIK</th>
        <th>Telepon</th>
        <th>Alamat</th>
        <th>Lat/Lng</th>
        <th>Status</th>
      </tr>
    </thead>
    <tbody>
      <?php $no=1; foreach ($members as $m): ?>
        <tr>
          <td><?= $no++ ?></td>
          <td><?= htmlspecialchars($m['name']) ?></td>
          <td><?= htmlspecialchars($m['nik'] ?? '-') ?></td>
          <td><?= htmlspecialchars($m['phone'] ?? '-') ?></td>
          <td><?= htmlspecialchars($m['address'] ?? '-') ?></td>
          <td><?= $m['lat'] ?? '-' ?>, <?= $m['lng'] ?? '-' ?></td>
          <td><span class="badge bg-<?= $m['status'] === 'active' ? 'success' : 'secondary' ?>"><?= ucfirst($m['status']) ?></span></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php
$content = ob_get_clean();
include view_path('layout_admin');
