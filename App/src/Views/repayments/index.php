<?php
ob_start();
?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h6 class="mb-0">Angsuran</h6>
  <?php if (\App\Helpers\AuthHelper::can('repayments', 'create')): ?>
    <a href="<?= route_url('index.php/repayments/create') ?>" class="btn btn-primary">+ Catat Pembayaran</a>
  <?php endif; ?>
  
</div>

<div class="table-responsive">
  <table class="table table-striped table-hover">
    <thead>
      <tr>
        <th>No</th>
        <th>Pinjaman</th>
        <th>Anggota</th>
        <th>Jatuh Tempo</th>
        <th>Tagihan</th>
        <th>Dibayar</th>
        <th>Metode</th>
        <th>Collector</th>
        <th>Status</th>
      </tr>
    </thead>
    <tbody>
      <?php $no=1; foreach ($repayments as $r): ?>
        <tr>
          <td><?= $no++ ?></td>
          <td class="currency text-end"><?= format_currency($r['loan_amount']) ?></td>
          <td><?= htmlspecialchars($r['member_name']) ?></td>
          <td><?= date('d/m/Y', strtotime($r['due_date'])) ?></td>
          <td class="currency text-end"><?= format_currency($r['amount_due']) ?></td>
          <td class="currency text-end"><?= format_currency($r['amount_paid']) ?></td>
          <td><?= htmlspecialchars($r['method'] ?? '-') ?></td>
          <td><?= htmlspecialchars($r['collector_name'] ?? '-') ?></td>
          <?php $rs = \App\Helpers\UiHelper::statusInfo($r['status']); ?>
          <td><span class="badge bg-<?= htmlspecialchars($rs['class']) ?>"><?= htmlspecialchars($rs['text']) ?></span></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php
$content = ob_get_clean();
include view_path('layout_dashboard');
