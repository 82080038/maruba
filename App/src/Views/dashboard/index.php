<?php
ob_start();
?>
<div class="row g-3" id="dashboardMetrics">
  <?php foreach ($metrics as $index => $metric): ?>
    <div class="col-12 col-sm-6 col-lg-3">
      <div class="card shadow-sm h-100" id="metricCard<?= $index ?>">
        <div class="card-body d-flex flex-column justify-content-center" id="metricBody<?= $index ?>">
          <div class="text-muted small mb-1"><?= htmlspecialchars($metric['label']) ?></div>
          <?php if (($metric['type'] ?? '') === 'currency'): ?>
            <div class="fs-4 fw-semibold" id="metricValue<?= $index ?>"><?= format_currency($metric['value']) ?></div>
          <?php elseif (($metric['type'] ?? '') === 'percent'): ?>
            <div class="fs-4 fw-semibold" id="metricValue<?= $index ?>"><?= format_number($metric['value'], 1) ?>%</div>
          <?php else: ?>
            <div class="fs-4 fw-semibold" id="metricValue<?= $index ?>"><?= format_number($metric['value']) ?></div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  <?php endforeach; ?>
</div>

<div class="row g-3 mt-3" id="dashboardContent">
  <div class="col-12 col-xl-6" id="mapColumn">
    <div class="card h-100 shadow-sm" id="mapCard">
      <div class="card-body" id="mapCardBody">
        <h6 class="card-title" id="mapTitle">Peta (placeholder)</h6>
        <div class="ratio ratio-4x3 bg-light border rounded" id="mapPlaceholder">
          <div class="d-flex align-items-center justify-content-center text-muted" id="mapPlaceholderText">Embed peta/Leaflet di sini</div>
        </div>
      </div>
    </div>
  </div>
  <div class="col-12 col-xl-6" id="activityColumn">
    <div class="card h-100 shadow-sm" id="activityCard">
      <div class="card-body" id="activityCardBody">
        <h6 class="card-title" id="activityTitle">Aktivitas Terbaru</h6>
        <ul class="list-group list-group-flush" id="activityList">
          <?php if (!empty($activities)): ?>
            <?php foreach ($activities as $index => $act): ?>
              <li class="list-group-item" id="activityItem<?= $index ?>">
                <div class="fw-semibold mb-1" id="activityAction<?= $index ?>"><?= htmlspecialchars($act['action'] . ' ' . ($act['entity'] ?? '')) ?> #<?= htmlspecialchars($act['entity_id'] ?? '-') ?></div>
                <div class="text-muted small" id="activityMeta<?= $index ?>">oleh <?= htmlspecialchars($act['user_name'] ?? '-') ?> • <?= date('d/m/Y H:i', strtotime($act['created_at'])) ?></div>
              </li>
            <?php endforeach; ?>
          <?php else: ?>
            <li class="list-group-item text-muted" id="noActivityMessage">Belum ada aktivitas.</li>
          <?php endif; ?>
        </ul>
      </div>
    </div>
  </div>
</div>

<div class="row g-3 mt-3">
  <div class="col-12">
    <div class="card shadow-sm" id="activitiesCard">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h6 class="mb-0">Aktivitas Terbaru</h6>
      </div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-striped mb-0">
            <thead>
              <tr>
                <th>Aksi</th>
                <th>Entitas</th>
                <th>ID</th>
                <th>Pengguna</th>
                <th>Waktu</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($activities)): ?>
                <tr><td colspan="5" class="text-center py-3">Belum ada aktivitas.</td></tr>
              <?php else: ?>
                <?php foreach ($activities as $act): ?>
                  <tr>
                    <td><?= htmlspecialchars($act['action']) ?></td>
                    <td><?= htmlspecialchars($act['entity']) ?></td>
                    <td>#<?= htmlspecialchars($act['entity_id']) ?></td>
                    <td><?= htmlspecialchars($act['user_name'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($act['created_at']) ?></td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<?php
$content = ob_get_clean();
include view_path('layout_admin');
