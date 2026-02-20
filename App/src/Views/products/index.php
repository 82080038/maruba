<?php
ob_start();
?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h5>Produk</h5>
  <?php if (\App\Helpers\AuthHelper::can('products', 'create')): ?>
    <a href="<?= route_url('products/create') ?>" class="btn btn-primary">+ Tambah</a>
  <?php endif; ?>
</div>

<div class="table-responsive">
  <table class="table table-striped table-hover">
    <thead>
      <tr>
        <th>No</th>
        <th>Nama</th>
        <th>Tipe</th>
        <th>Bunga (%)</th>
        <th>Tenor (bulan)</th>
        <th>Biaya</th>
      </tr>
    </thead>
    <tbody>
      <?php $no=1; foreach ($products as $p): ?>
        <tr>
          <td><?= $no++ ?></td>
          <td><?= htmlspecialchars($p['name']) ?></td>
          <td><span class="badge bg-<?= $p['type'] === 'loan' ? 'primary' : 'success' ?>"><?= ucfirst($p['type']) ?></span></td>
          <td><?= $p['rate'] ?></td>
          <td><?= $p['tenor_months'] ?></td>
          <td><?= format_currency($p['fee']) ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php
$content = ob_get_clean();
include view_path('layout_admin');
