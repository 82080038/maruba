<?php
ob_start();
?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h6 class="mb-0"><?= t('reports') ?></h6>
  <?php if (\App\Helpers\AuthHelper::can('reports', 'export')): ?>
    <a href="<?= route_url('index.php/reports/export') ?>" class="btn btn-success">Ekspor CSV (Pinjaman)</a>
  <?php endif; ?>
</div>

<div class="row g-3 mb-4">
  <div class="col-md-3">
    <div class="card bg-light">
      <div class="card-body">
        <div class="text-muted small"><?= t('outstanding') ?></div>
        <div class="fs-5 fw-semibold"><?= format_currency($outstanding) ?></div>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="card bg-light">
      <div class="card-body">
        <div class="text-muted small"><?= t('npl count') ?></div>
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
          <span><?= htmlspecialchars(t($ls['status'])) ?></span>
          <span class="badge bg-secondary"><?= $ls['cnt'] ?></span>
        </li>
      <?php endforeach; ?>
    </ul>
  </div>
</div>

 
<?php
$content = ob_get_clean();
include view_path('layout_dashboard');
