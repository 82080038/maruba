<?php
ob_start();
?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h5>Pencairan Pinjaman</h5>
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
          <td><?= format_currency($l['amount']) ?></td>
          <td><?= $l['tenor_months'] ?> bln</td>
          <td><span class="badge bg-success"><?= ucfirst($l['status']) ?></span></td>
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
include view_path('layout_admin');
