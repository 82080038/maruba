<?php
ob_start();
?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h6 class="mb-0">Pencairan</h6>
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
        <th>Tanggal Pengajuan</th>
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
          <td><?= $l['tenor_months'] ?> bln</td>
          <?php $ds = \App\Helpers\UiHelper::statusInfo($l['status']); ?>
          <td><span class="badge bg-<?= htmlspecialchars($ds['class']) ?>"><?= htmlspecialchars($ds['text']) ?></span></td>
          <td><?= date('d/m/Y', strtotime($l['created_at'])) ?></td>
          <td>
            <a href="<?= route_url('disbursement/create?loan_id='.$l['id']) ?>" class="btn btn-sm btn-primary">Cairkan</a>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php
$content = ob_get_clean();
include view_path('layout_dashboard');
