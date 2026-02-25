<?php
ob_start();
?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h6 class="mb-0">Pinjaman</h6>
  <?php if (\App\Helpers\AuthHelper::can('loans', 'create')): ?>
    <a href="<?= route_url('index.php/loans/create') ?>" class="btn btn-primary">+ Ajukan Pinjaman</a>
  <?php endif; ?>
</div>

<div class="table-responsive">
  <table class="table table-striped table-hover">
    <thead>
      <tr>
        <th>No</th>
        <th>Anggota</th>
        <th>Produk</th>
        <th>Pinjaman</th>
        <th>Tenor</th>
        <th>Status</th>
        <th>Tanggal</th>
        <th>Aksi</th>
      </tr>
    </thead>
    <tbody>
      <?php $no=1; foreach ($loans as $l): ?>
        <tr>
          <td><?= $no++ ?></td>
          <td><?= htmlspecialchars($l['member_name']) ?></td>
          <td><?= htmlspecialchars($l['product_name']) ?></td>
          <td class="currency text-end"><?= format_currency($l['amount']) ?></td>
          <td><?= format_number($l['tenor_months']) ?> bln</td>
          <?php $s = \App\Helpers\UiHelper::statusInfo($l['status']); ?>
          <td><span class="badge bg-<?= htmlspecialchars($s['class']) ?>"><?= htmlspecialchars($s['text']) ?></span></td>
          <td><?= date('d/m/Y', strtotime($l['created_at'])) ?></td>
          <td>
            <?php if (\App\Helpers\AuthHelper::can('loans', 'view')): ?>
              <a href="<?= route_url('loans/view?id='.$l['id']) ?>" class="btn btn-sm btn-outline-info">Lihat</a>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php
$content = ob_get_clean();
include view_path('layout_dashboard');
