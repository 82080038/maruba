<?php
ob_start();
?>
<?php
  $u = current_user();
  $displayName = $u['name'] ?? ($u['username'] ?? 'User');
  $role = user_role() ?: ($u['role'] ?? '-');

  // Sapaan waktu dalam bahasa Indonesia
  $h = (int) date('G'); // 0-23
  if ($h < 11) {
      $sapaan = 'Pagi';
  } elseif ($h < 15) {
      $sapaan = 'Siang';
  } elseif ($h < 18) {
      $sapaan = 'Sore';
  } else {
      $sapaan = 'Malam';
  }
?>

<div class="surveyor-dashboard-container">
    <!-- Surveyor Header -->
    <div class="surveyor-header">
        <div>
            <h1 class="surveyor-title">🔍 Dashboard Surveyor</h1>
            <p class="text-muted">Selamat <?= htmlspecialchars($sapaan) ?>, <?= htmlspecialchars($displayName) ?> (<?= htmlspecialchars($role) ?>)</p>
        </div>
        <div class="header-actions">
            <button id="refresh-surveyor-dashboard" class="btn btn-primary">
                <i class="bi bi-arrow-clockwise"></i>
                Refresh
            </button>
            <a href="<?= route_url('surveys/create') ?>" class="btn btn-success">
                <i class="bi bi-plus-circle"></i>
                Buat Survei
            </a>
        </div>
    </div>

    <!-- Surveyor Metrics Grid -->
    <div class="metrics-grid" id="surveyor-metrics-grid">
        <?php foreach ($metrics as $metric): ?>
        <div class="metric-card surveyor-metric">
            <div class="metric-icon">
                <?php
                $iconClass = match($metric['label']) {
                    'Survei Pending' => 'bi-clipboard-check',
                    'Completion Rate' => 'bi-graph-up-arrow',
                    'Coverage' => 'bi-geo-alt',
                    'Avg Score' => 'bi-star',
                    default => 'bi-graph-up'
                };
                ?>
                <i class="bi <?= $iconClass ?>"></i>
            </div>
            <div class="metric-content">
                <h3 class="metric-value">
                    <?php
                    if ($metric['type'] === 'currency') {
                        echo 'Rp ' . number_format($metric['value'], 0, ',', '.');
                    } elseif ($metric['type'] === 'percent') {
                        echo $metric['value'] . '%';
                    } else {
                        echo htmlspecialchars($metric['value']);
                    }
                    ?>
                </h3>
                <p class="metric-label"><?= htmlspecialchars($metric['label']) ?></p>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Quick Actions -->
    <div class="quick-actions">
        <h3>Quick Actions</h3>
        <div class="action-buttons">
            <a href="<?= route_url('surveys/create') ?>" class="btn btn-success">
                <i class="bi bi-plus-circle"></i>
                Buat Survei
            </a>
            <a href="<?= route_url('surveys') ?>" class="btn btn-info">
                <i class="bi bi-list-ul"></i>
                Daftar Survei
            </a>
            <a href="<?= route_url('members') ?>" class="btn btn-secondary">
                <i class="bi bi-people"></i>
                Data Anggota
            </a>
            <a href="<?= route_url('loan_docs') ?>" class="btn btn-warning">
                <i class="bi bi-file-earmark"></i>
                Dokumen
            </a>
        </div>
    </div>

    <!-- Survey Queue -->
    <div class="survey-queue">
        <h3>Antrian Survei</h3>
        <div class="queue-stats">
            <div class="queue-item urgent">
                <div class="queue-number">3</div>
                <div class="queue-label">Prioritas Tinggi</div>
            </div>
            <div class="queue-item normal">
                <div class="queue-number">5</div>
                <div class="queue-label">Normal</div>
            </div>
            <div class="queue-item completed">
                <div class="queue-number">12</div>
                <div class="queue-label">Selesai</div>
            </div>
        </div>
    </div>

    <!-- Recent Surveys -->
    <div class="recent-surveys">
        <h3>Survei Terbaru</h3>
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Anggota</th>
                        <th>Pinjaman</th>
                        <th>Lokasi</th>
                        <th>Skor</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($recentSurveys)): ?>
                    <tr>
                        <td colspan="6" class="text-center">Belum ada survei</td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($recentSurveys as $survey): ?>
                    <tr>
                        <td><?= htmlspecialchars($survey['member_name'] ?? '-') ?></td>
                        <td>#<?= htmlspecialchars($survey['loan_id']) ?></td>
                        <td>
                            <a href="#" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-geo-alt"></i>
                                Lihat Lokasi
                            </a>
                        </td>
                        <td>
                            <?php if ($survey['score']): ?>
                                <div class="score-badge">
                                    <?= htmlspecialchars($survey['score']) ?>
                                    <i class="bi bi-star-fill"></i>
                                </div>
                            <?php else: ?>
                                <span class="text-muted">-</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="badge bg-<?= $survey['result'] ? 'success' : 'warning' ?>">
                                <?= $survey['result'] ? 'Selesai' : 'Pending' ?>
                            </span>
                        </td>
                        <td>
                            <?php if (!$survey['result']): ?>
                                <a href="<?= route_url('surveys/edit?id=' . $survey['id']) ?>" class="btn btn-sm btn-primary">
                                    <i class="bi bi-pencil"></i>
                                    Edit
                                </a>
                            <?php else: ?>
                                <a href="<?= route_url('surveys/view?id=' . $survey['id']) ?>" class="btn btn-sm btn-info">
                                    <i class="bi bi-eye"></i>
                                    Lihat
                                </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Geographic Coverage -->
    <div class="geographic-coverage">
        <h3>Cakupan Wilayah</h3>
        <div class="coverage-map">
            <div class="map-placeholder">
                <i class="bi bi-map"></i>
                <p>Peta Cakupan Survei</p>
            </div>
            <div class="coverage-stats">
                <div class="coverage-item">
                    <div class="coverage-icon covered">
                        <i class="bi bi-check-circle"></i>
                    </div>
                    <div class="coverage-content">
                        <div class="coverage-label">Pangururan</div>
                        <div class="coverage-status">Selesai</div>
                    </div>
                </div>
                <div class="coverage-item">
                    <div class="coverage-icon covered">
                        <i class="bi bi-check-circle"></i>
                    </div>
                    <div class="coverage-content">
                        <div class="coverage-label">Simanindo</div>
                        <div class="coverage-status">Selesai</div>
                    </div>
                </div>
                <div class="coverage-item">
                    <div class="coverage-icon partial">
                        <i class="bi bi-half"></i>
                    </div>
                    <div class="coverage-content">
                        <div class="coverage-label">Onan Runggu</div>
                        <div class="coverage-status">50%</div>
                    </div>
                </div>
                <div class="coverage-item">
                    <div class="coverage-icon pending">
                        <i class="bi bi-clock"></i>
                    </div>
                    <div class="coverage-content">
                        <div class="coverage-label">Nainggolan</div>
                        <div class="coverage-status">Pending</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Performance Chart -->
    <div class="performance-chart">
        <h3>Performa Survei</h3>
        <div class="chart-container">
            <div class="performance-bars">
                <div class="performance-bar">
                    <div class="bar-label">Senin</div>
                    <div class="bar-fill" style="height: 60%"></div>
                    <div class="bar-value">3 survei</div>
                </div>
                <div class="performance-bar">
                    <div class="bar-label">Selasa</div>
                    <div class="bar-fill" style="height: 80%"></div>
                    <div class="bar-value">4 survei</div>
                </div>
                <div class="performance-bar">
                    <div class="bar-label">Rabu</div>
                    <div class="bar-fill" style="height: 100%"></div>
                    <div class="bar-value">5 survei</div>
                </div>
                <div class="performance-bar">
                    <div class="bar-label">Kamis</div>
                    <div class="bar-fill" style="height: 40%"></div>
                    <div class="bar-value">2 survei</div>
                </div>
                <div class="performance-bar">
                    <div class="bar-label">Jumat</div>
                    <div class="bar-fill" style="height: 70%"></div>
                    <div class="bar-value">3.5 survei</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Survey Checklist -->
    <div class="survey-checklist">
        <h3>Checklist Survei</h3>
        <div class="checklist-items">
            <div class="checklist-item">
                <input type="checkbox" id="check1" checked>
                <label for="check1">Verifikasi KTP anggota</label>
            </div>
            <div class="checklist-item">
                <input type="checkbox" id="check2" checked>
                <label for="check2">Foto lokasi usaha</label>
            </div>
            <div class="checklist-item">
                <input type="checkbox" id="check3">
                <label for="check3">Wawancara pemohon</label>
            </div>
            <div class="checklist-item">
                <input type="checkbox" id="check4">
                <label for="check4">Cek referensi</label>
            </div>
            <div class="checklist-item">
                <input type="checkbox" id="check5">
                <label for="check5">Upload dokumen</label>
            </div>
        </div>
    </div>
</div>

<style>
.surveyor-dashboard-container {
    padding: 20px;
    max-width: 1200px;
    margin: 0 auto;
}

.surveyor-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
    padding-bottom: 20px;
    border-bottom: 2px solid #e9ecef;
}

.surveyor-title {
    color: #2c3e50;
    font-size: 2rem;
    font-weight: 600;
    margin: 0;
}

.header-actions {
    display: flex;
    gap: 10px;
}

.metrics-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.metric-card {
    background: white;
    border-radius: 12px;
    padding: 20px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    display: flex;
    align-items: center;
    transition: transform 0.2s;
}

.metric-card:hover {
    transform: translateY(-2px);
}

.surveyor-metric {
    border-left: 4px solid #6f42c1;
}

.metric-icon {
    font-size: 2rem;
    color: #6f42c1;
    margin-right: 15px;
}

.metric-content h3 {
    font-size: 1.5rem;
    font-weight: 600;
    margin: 0;
    color: #2c3e50;
}

.metric-content p {
    margin: 5px 0 0 0;
    color: #6c757d;
    font-size: 0.9rem;
}

.quick-actions, .survey-queue, .recent-surveys, .geographic-coverage, .performance-chart, .survey-checklist {
    background: white;
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 30px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.quick-actions h3, .survey-queue h3, .recent-surveys h3, .geographic-coverage h3, .performance-chart h3, .survey-checklist h3 {
    margin-bottom: 15px;
    color: #2c3e50;
}

.action-buttons {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.queue-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
    gap: 20px;
}

.queue-item {
    text-align: center;
    padding: 20px;
    border-radius: 8px;
    background: #f8f9fa;
}

.queue-item.urgent {
    background: #f8d7da;
    color: #dc3545;
}

.queue-item.normal {
    background: #d1ecf1;
    color: #17a2b8;
}

.queue-item.completed {
    background: #d4edda;
    color: #28a745;
}

.queue-number {
    font-size: 2rem;
    font-weight: bold;
}

.queue-label {
    margin-top: 5px;
    font-size: 0.9rem;
}

.score-badge {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    background: #ffc107;
    color: #212529;
    padding: 4px 8px;
    border-radius: 4px;
    font-weight: bold;
}

.coverage-map {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}

.map-placeholder {
    background: #f8f9fa;
    border-radius: 8px;
    padding: 40px;
    text-align: center;
    color: #6c757d;
}

.map-placeholder i {
    font-size: 3rem;
    margin-bottom: 10px;
}

.coverage-stats {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.coverage-item {
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 10px;
    border-radius: 6px;
    background: #f8f9fa;
}

.coverage-icon {
    font-size: 1.5rem;
}

.coverage-icon.covered {
    color: #28a745;
}

.coverage-icon.partial {
    color: #ffc107;
}

.coverage-icon.pending {
    color: #6c757d;
}

.coverage-label {
    font-weight: 500;
}

.coverage-status {
    font-size: 0.9rem;
    color: #6c757d;
}

.performance-bars {
    display: flex;
    align-items: flex-end;
    height: 200px;
    gap: 20px;
    min-width: 400px;
}

.performance-bar {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    height: 100%;
}

.bar-label {
    margin-bottom: 10px;
    font-weight: bold;
    color: #6c757d;
}

.bar-fill {
    width: 40px;
    border-radius: 4px 4px 0 0;
    transition: height 0.3s ease;
}

.bar-value {
    margin-top: 10px;
    font-weight: bold;
    color: #2c3e50;
    font-size: 0.9rem;
}

.checklist-items {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.checklist-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px;
    border-radius: 6px;
    background: #f8f9fa;
}

.checklist-item input[type="checkbox"] {
    width: 20px;
    height: 20px;
}

.checklist-item label {
    flex: 1;
    cursor: pointer;
}

@media (max-width: 768px) {
    .surveyor-header {
        flex-direction: column;
        gap: 15px;
        text-align: center;
    }
    
    .metrics-grid {
        grid-template-columns: 1fr;
    }
    
    .action-buttons {
        flex-direction: column;
    }
    
    .queue-stats {
        grid-template-columns: 1fr;
    }
    
    .coverage-map {
        grid-template-columns: 1fr;
    }
    
    .performance-bars {
        min-width: 300px;
    }
}
</style>

<script>
document.getElementById('refresh-surveyor-dashboard').addEventListener('click', function() {
    location.reload();
});

// Auto refresh every 30 seconds
setInterval(function() {
    location.reload();
}, 30000);
</script>

<?php
$content = ob_get_clean();
include view_path('layout_dashboard');
?>
