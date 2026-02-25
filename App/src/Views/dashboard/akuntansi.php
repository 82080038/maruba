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

<div class="akuntansi-dashboard-container">
    <!-- Akuntansi Header -->
    <div class="akuntansi-header">
        <div>
            <h1 class="akuntansi-title">📊 Dashboard Akuntansi</h1>
            <p class="text-muted">Selamat <?= htmlspecialchars($sapaan) ?>, <?= htmlspecialchars($displayName) ?> (<?= htmlspecialchars($role) ?>)</p>
        </div>
        <div class="header-actions">
            <button id="refresh-akuntansi-dashboard" class="btn btn-primary">
                <i class="bi bi-arrow-clockwise"></i>
                Refresh
            </button>
            <a href="<?= route_url('accounting/journal/create') ?>" class="btn btn-success">
                <i class="bi bi-plus-circle"></i>
                Journal Entry
            </a>
        </div>
    </div>

    <!-- Akuntansi Metrics Grid -->
    <div class="metrics-grid" id="akuntansi-metrics-grid">
        <?php foreach ($metrics as $metric): ?>
        <div class="metric-card akuntansi-metric">
            <div class="metric-icon">
                <?php
                $iconClass = match($metric['label']) {
                    'Pending Entries' => 'bi-clock-history',
                    'Chart Accounts' => 'bi-diagram-3',
                    'Tax Status' => 'bi-shield-check',
                    'Audit Findings' => 'bi-exclamation-triangle',
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
            <a href="<?= route_url('accounting/journal/create') ?>" class="btn btn-success">
                <i class="bi bi-plus-circle"></i>
                Journal Entry
            </a>
            <a href="<?= route_url('accounting/journal') ?>" class="btn btn-primary">
                <i class="bi bi-book"></i>
                Journal
            </a>
            <a href="<?= route_url('accounting/chart') ?>" class="btn btn-info">
                <i class="bi bi-diagram-3"></i>
                Chart of Accounts
            </a>
            <a href="<?= route_url('accounting/reports') ?>" class="btn btn-secondary">
                <i class="bi bi-file-text"></i>
                Laporan
            </a>
        </div>
    </div>

    <!-- Trial Balance Summary -->
    <div class="trial-balance">
        <h3>Trial Balance</h3>
        <div class="balance-summary">
            <div class="balance-item">
                <div class="balance-label">Total Debit</div>
                <div class="balance-value debit">Rp <?= number_format(125000000, 0, ',', '.') ?></div>
            </div>
            <div class="balance-item">
                <div class="balance-label">Total Kredit</div>
                <div class="balance-value credit">Rp <?= number_format(125000000, 0, ',', '.') ?></div>
            </div>
            <div class="balance-item">
                <div class="balance-label">Balance Status</div>
                <div class="balance-status balanced">
                    <i class="bi bi-check-circle"></i>
                    Balanced
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Journal Entries -->
    <div class="recent-entries">
        <h3>Journal Entries Terbaru</h3>
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>No. Ref</th>
                        <th>Akun</th>
                        <th>Debit</th>
                        <th>Kredit</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($recentEntries)): ?>
                    <tr>
                        <td colspan="7" class="text-center">Belum ada journal entries</td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($recentEntries as $entry): ?>
                    <tr>
                        <td><?= date('d/m/Y', strtotime($entry['created_at'])) ?></td>
                        <td><?= htmlspecialchars($entry['reference'] ?? 'JE-' . str_pad($entry['id'], 6, '0', STR_PAD_LEFT)) ?></td>
                        <td><?= htmlspecialchars($entry['account_name'] ?? '-') ?></td>
                        <td class="text-end"><?= $entry['debit'] ? 'Rp ' . number_format($entry['debit'], 0, ',', '.') : '-' ?></td>
                        <td class="text-end"><?= $entry['credit'] ? 'Rp ' . number_format($entry['credit'], 0, ',', '.') : '-' ?></td>
                        <td>
                            <span class="badge bg-<?= $entry['status'] === 'posted' ? 'success' : ($entry['status'] === 'pending' ? 'warning' : 'secondary') ?>">
                                <?= htmlspecialchars($entry['status'] ?? 'draft') ?>
                            </span>
                        </td>
                        <td>
                            <a href="<?= route_url('accounting/journal/edit?id=' . $entry['id']) ?>" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-pencil"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Financial Summary -->
    <div class="financial-summary">
        <h3>Ringkasan Keuangan</h3>
        <div class="financial-cards">
            <div class="financial-card">
                <div class="card-header">
                    <i class="bi bi-cash-stack"></i>
                    <h4>Kas & Bank</h4>
                </div>
                <div class="card-value">Rp <?= number_format(45000000, 0, ',', '.') ?></div>
                <div class="card-change positive">+5.2%</div>
            </div>
            <div class="financial-card">
                <div class="card-header">
                    <i class="bi bi-people"></i>
                    <h4>Piutang</h4>
                </div>
                <div class="card-value">Rp <?= number_format(28000000, 0, ',', '.') ?></div>
                <div class="card-change negative">-2.1%</div>
            </div>
            <div class="financial-card">
                <div class="card-header">
                    <i class="bi bi-building"></i>
                    <h4>Aset Tetap</h4>
                </div>
                <div class="card-value">Rp <?= number_format(150000000, 0, ',', '.') ?></div>
                <div class="card-change positive">+0.8%</div>
            </div>
            <div class="financial-card">
                <div class="card-header">
                    <i class="bi bi-box"></i>
                    <h4>Persediaan</h4>
                </div>
                <div class="card-value">Rp <?= number_format(12000000, 0, ',', '.') ?></div>
                <div class="card-change positive">+3.5%</div>
            </div>
        </div>
    </div>

    <!-- Tax Compliance -->
    <div class="tax-compliance">
        <h3>Kepatuhan Pajak</h3>
        <div class="tax-items">
            <div class="tax-item compliant">
                <div class="tax-icon">
                    <i class="bi bi-check-circle"></i>
                </div>
                <div class="tax-content">
                    <div class="tax-label">PPN Masukan</div>
                    <div class="tax-status">Lapor Bulanan - Selesai</div>
                    <div class="tax-date">Terakhir: 20 Feb 2026</div>
                </div>
            </div>
            <div class="tax-item compliant">
                <div class="tax-icon">
                    <i class="bi bi-check-circle"></i>
                </div>
                <div class="tax-content">
                    <div class="tax-label">PPN Keluaran</div>
                    <div class="tax-status">Lapor Bulanan - Selesai</div>
                    <div class="tax-date">Terakhir: 20 Feb 2026</div>
                </div>
            </div>
            <div class="tax-item pending">
                <div class="tax-icon">
                    <i class="bi bi-clock"></i>
                </div>
                <div class="tax-content">
                    <div class="tax-label">PPh 21</div>
                    <div class="tax-status">Lapor Bulanan - Pending</div>
                    <div class="tax-date">Jatuh tempo: 25 Feb 2026</div>
                </div>
            </div>
            <div class="tax-item compliant">
                <div class="tax-icon">
                    <i class="bi bi-check-circle"></i>
                </div>
                <div class="tax-content">
                    <div class="tax-label">PPh 23</div>
                    <div class="tax-status">Lapor Bulanan - Selesai</div>
                    <div class="tax-date">Terakhir: 15 Feb 2026</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Audit Trail -->
    <div class="audit-trail">
        <h3>Audit Trail</h3>
        <div class="audit-stats">
            <div class="audit-item">
                <div class="audit-number">0</div>
                <div class="audit-label">Critical Issues</div>
            </div>
            <div class="audit-item">
                <div class="audit-number">2</div>
                <div class="audit-label">Warnings</div>
            </div>
            <div class="audit-item">
                <div class="audit-number">15</div>
                <div class="audit-label">Info</div>
            </div>
            <div class="audit-item">
                <div class="audit-number">98%</div>
                <div class="audit-label">Compliance Score</div>
            </div>
        </div>
    </div>
</div>

<style>
.akuntansi-dashboard-container {
    padding: 20px;
    max-width: 1200px;
    margin: 0 auto;
}

.akuntansi-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
    padding-bottom: 20px;
    border-bottom: 2px solid #e9ecef;
}

.akuntansi-title {
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

.akuntansi-metric {
    border-left: 4px solid #17a2b8;
}

.metric-icon {
    font-size: 2rem;
    color: #17a2b8;
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

.quick-actions, .trial-balance, .recent-entries, .financial-summary, .tax-compliance, .audit-trail {
    background: white;
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 30px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.quick-actions h3, .trial-balance h3, .recent-entries h3, .financial-summary h3, .tax-compliance h3, .audit-trail h3 {
    margin-bottom: 15px;
    color: #2c3e50;
}

.action-buttons {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.balance-summary {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
}

.balance-item {
    text-align: center;
    padding: 20px;
    border-radius: 8px;
    background: #f8f9fa;
}

.balance-label {
    font-size: 0.9rem;
    color: #6c757d;
    margin-bottom: 10px;
}

.balance-value {
    font-size: 1.5rem;
    font-weight: bold;
    margin-bottom: 10px;
}

.balance-value.debit {
    color: #dc3545;
}

.balance-value.credit {
    color: #28a745;
}

.balance-status {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    color: #28a745;
    font-weight: 500;
}

.financial-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
}

.financial-card {
    padding: 20px;
    border-radius: 8px;
    background: #f8f9fa;
    text-align: center;
}

.card-header {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    margin-bottom: 15px;
}

.card-header i {
    font-size: 1.5rem;
    color: #17a2b8;
}

.card-header h4 {
    margin: 0;
    color: #2c3e50;
}

.card-value {
    font-size: 1.8rem;
    font-weight: bold;
    color: #2c3e50;
    margin-bottom: 10px;
}

.card-change {
    font-size: 0.9rem;
    font-weight: 500;
}

.card-change.positive {
    color: #28a745;
}

.card-change.negative {
    color: #dc3545;
}

.tax-items {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.tax-item {
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 15px;
    border-radius: 8px;
    background: #f8f9fa;
}

.tax-icon {
    font-size: 1.5rem;
}

.tax-item.compliant .tax-icon {
    color: #28a745;
}

.tax-item.pending .tax-icon {
    color: #ffc107;
}

.tax-content {
    flex: 1;
}

.tax-label {
    font-weight: 500;
    margin-bottom: 5px;
}

.tax-status {
    font-size: 0.9rem;
    color: #6c757d;
    margin-bottom: 3px;
}

.tax-date {
    font-size: 0.8rem;
    color: #6c757d;
}

.audit-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
    gap: 20px;
}

.audit-item {
    text-align: center;
    padding: 20px;
    border-radius: 8px;
    background: #f8f9fa;
}

.audit-number {
    font-size: 2rem;
    font-weight: bold;
    color: #17a2b8;
}

.audit-label {
    margin-top: 5px;
    font-size: 0.9rem;
    color: #6c757d;
}

@media (max-width: 768px) {
    .akuntansi-header {
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
    
    .balance-summary {
        grid-template-columns: 1fr;
    }
    
    .financial-cards {
        grid-template-columns: 1fr;
    }
    
    .audit-stats {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
document.getElementById('refresh-akuntansi-dashboard').addEventListener('click', function() {
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
