<?php
ob_start();
?>
<h5>Laporan</h5>

<div class="row g-3 mb-4">
  <div class="col-md-3">
    <div class="card bg-light">
      <div class="card-body">
        <div class="text-muted small">Outstanding</div>
        <div class="fs-5 fw-semibold"><?= format_currency($outstanding) ?></div>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="card bg-light">
      <div class="card-body">
        <div class="text-muted small">NPL Count</div>
        <div class="fs-5 fw-semibold"><?= $nplCount ?></div>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="card bg-light">
      <div class="card-body">
        <div class="text-muted small">Anggota Aktif</div>
        <div class="fs-5 fw-semibold"><?= $membersCount ?></div>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="card bg-light">
      <div class="card-body">
        <div class="text-muted small">Total Pinjaman</div>
        <div class="fs-5 fw-semibold"><?= array_sum(array_column($loanStatus, 'cnt')) ?></div>
      </div>
    </div>
  </div>
</div>

<div class="card">
  <div class="card-body">
    <h6>Pinjaman per Status</h6>
    <ul class="list-group list-group-flush">
      <?php foreach ($loanStatus as $ls): ?>
        <li class="list-group-item d-flex justify-content-between">
          <span><?= ucfirst($ls['status']) ?></span>
          <span class="badge bg-secondary"><?= $ls['cnt'] ?></span>
        </li>
      <?php endforeach; ?>
    </ul>
  </div>
</div>

<div class="mt-3">
  <?php if (\App\Helpers\AuthHelper::can('reports', 'export')): ?>
    <a href="<?= route_url('reports/export') ?>" class="btn btn-success">Export CSV (Pinjaman)</a>
  <?php endif; ?>
</div>
<?php
$content = ob_get_clean();
include view_path('layout_admin');
