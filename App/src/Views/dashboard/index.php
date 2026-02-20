<?php
ob_start();
?>
<div class="row g-3">
  <?php foreach ($metrics as $metric): ?>
    <div class="col-12 col-sm-6 col-lg-3">
      <div class="card shadow-sm h-100">
        <div class="card-body d-flex flex-column justify-content-center">
          <div class="text-muted small mb-1"><?= htmlspecialchars($metric['label']) ?></div>
          <?php if (($metric['type'] ?? '') === 'currency'): ?>
            <div class="fs-4 fw-semibold"><?= format_currency($metric['value']) ?></div>
          <?php elseif (($metric['type'] ?? '') === 'percent'): ?>
            <div class="fs-4 fw-semibold"><?= format_number($metric['value'], 1) ?>%</div>
          <?php else: ?>
            <div class="fs-4 fw-semibold"><?= format_number($metric['value']) ?></div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  <?php endforeach; ?>
</div>

<div class="row g-3 mt-3">
  <div class="col-12 col-xl-6">
    <div class="card h-100 shadow-sm">
      <div class="card-body">
        <h6 class="card-title">Peta (placeholder)</h6>
        <div class="ratio ratio-4x3 bg-light border rounded" id="mapPlaceholder">
          <div class="d-flex align-items-center justify-content-center text-muted">Embed peta/Leaflet di sini</div>
        </div>
      </div>
    </div>
  </div>
  <div class="col-12 col-xl-6">
    <div class="card h-100 shadow-sm">
      <div class="card-body">
        <h6 class="card-title">Aktivitas Terbaru</h6>
        <ul class="list-group list-group-flush">
          <?php if (!empty($activities)): ?>
            <?php foreach ($activities as $act): ?>
              <li class="list-group-item">
                <div class="fw-semibold mb-1"><?= htmlspecialchars($act['action'] . ' ' . ($act['entity'] ?? '')) ?> #<?= htmlspecialchars($act['entity_id'] ?? '-') ?></div>
                <div class="text-muted small">oleh <?= htmlspecialchars($act['user_name'] ?? '-') ?> • <?= date('d/m/Y H:i', strtotime($act['created_at'])) ?></div>
              </li>
            <?php endforeach; ?>
          <?php else: ?>
            <li class="list-group-item text-muted">Belum ada aktivitas.</li>
          <?php endif; ?>
        </ul>
      </div>
    </div>
  </div>
</div>
<?php
$content = ob_get_clean();
include view_path('layout_admin');
