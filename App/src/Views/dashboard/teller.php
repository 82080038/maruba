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

<div class="teller-dashboard-container">
    <!-- Teller Header -->
    <div class="teller-header">
        <div>
            <h1 class="teller-title">🏪 Dashboard Teller</h1>
            <p class="text-muted">Selamat <?= htmlspecialchars($sapaan) ?>, <?= htmlspecialchars($displayName) ?> (<?= htmlspecialchars($role) ?>)</p>
        </div>
        <div class="header-actions">
            <button id="refresh-teller-dashboard" class="btn btn-primary">
                <i class="bi bi-arrow-clockwise"></i>
                Refresh
            </button>
            <a href="<?= route_url('members/create') ?>" class="btn btn-success">
                <i class="bi bi-person-plus"></i>
                Registrasi Anggota
            </a>
        </div>
    </div>

    <!-- Teller Metrics Grid -->
    <div class="metrics-grid" id="teller-metrics-grid">
        <?php foreach ($metrics as $metric): ?>
        <div class="metric-card teller-metric">
            <div class="metric-icon">
                <?php
                $iconClass = match($metric['label']) {
                    'Total Tabungan' => 'bi-piggy-bank',
                    'Registrasi Hari Ini' => 'bi-person-plus',
                    'Deposit Hari Ini' => 'bi-cash-stack',
                    'Queue Status' => 'bi-people-line',
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
            <a href="<?= route_url('members/create') ?>" class="btn btn-success">
                <i class="bi bi-person-plus"></i>
                Registrasi Anggota
            </a>
            <a href="<?= route_url('savings/create') ?>" class="btn btn-primary">
                <i class="bi bi-piggy-bank"></i>
                Buka Tabungan
            </a>
            <a href="<?= route_url('members') ?>" class="btn btn-info">
                <i class="bi bi-people"></i>
                Data Anggota
            </a>
            <a href="<?= route_url('savings') ?>" class="btn btn-secondary">
                <i class="bi bi-wallet2"></i>
                Tabungan
            </a>
        </div>
    </div>

    <!-- Service Queue -->
    <div class="service-queue">
        <h3>Antrian Layanan</h3>
        <div class="queue-display">
            <div class="current-queue">
                <div class="queue-number">A-012</div>
                <div class="queue-label">Sedang Dilayani</div>
            </div>
            <div class="next-queues">
                <div class="queue-item">A-013</div>
                <div class="queue-item">A-014</div>
                <div class="queue-item">A-015</div>
            </div>
        </div>
        <div class="queue-controls">
            <button class="btn btn-success">
                <i class="bi bi-check-circle"></i>
                Selesai
            </button>
            <button class="btn btn-warning">
                <i class="bi bi-arrow-right-circle"></i>
                Next
            </button>
        </div>
    </div>

    <!-- Recent Members -->
    <div class="recent-members">
        <h3>Anggota Baru</h3>
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Nama</th>
                        <th>NIK</th>
                        <th>Telepon</th>
                        <th>Alamat</th>
                        <th>Tanggal Daftar</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($recentMembers)): ?>
                    <tr>
                        <td colspan="6" class="text-center">Belum ada anggota baru hari ini</td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($recentMembers as $member): ?>
                    <tr>
                        <td><?= htmlspecialchars($member['name']) ?></td>
                        <td><?= htmlspecialchars($member['nik'] ?? '-') ?></td>
                        <td>
                            <a href="tel:<?= htmlspecialchars($member['phone'] ?? '') ?>" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-telephone"></i>
                                <?= htmlspecialchars($member['phone'] ?? '-') ?>
                            </a>
                        </td>
                        <td><?= htmlspecialchars($member['address'] ?? '-') ?></td>
                        <td><?= date('d/m/Y', strtotime($member['created_at'])) ?></td>
                        <td>
                            <a href="<?= route_url('members/edit?id=' . $member['id']) ?>" class="btn btn-sm btn-outline-primary">
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

    <!-- Savings Summary -->
    <div class="savings-summary">
        <h3>Ringkasan Tabungan</h3>
        <div class="savings-stats">
            <div class="savings-item">
                <div class="savings-icon">
                    <i class="bi bi-piggy-bank"></i>
                </div>
                <div class="savings-content">
                    <div class="savings-label">Tabungan Pokok</div>
                    <div class="savings-value">Rp <?= number_format(25000000, 0, ',', '.') ?></div>
                </div>
            </div>
            <div class="savings-item">
                <div class="savings-icon">
                    <i class="bi bi-wallet2"></i>
                </div>
                <div class="savings-content">
                    <div class="savings-label">Tabungan Wajib</div>
                    <div class="savings-value">Rp <?= number_format(45000000, 0, ',', '.') ?></div>
                </div>
            </div>
            <div class="savings-item">
                <div class="savings-icon">
                    <i class="bi bi-wallet"></i>
                </div>
                <div class="savings-content">
                    <div class="savings-label">Tabungan Sukarela</div>
                    <div class="savings-value">Rp <?= number_format(32000000, 0, ',', '.') ?></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Transaction History -->
    <div class="transaction-history">
        <h3>Riwayat Transaksi Hari Ini</h3>
        <div class="transaction-list">
            <div class="transaction-item deposit">
                <div class="transaction-icon">
                    <i class="bi bi-arrow-down-circle"></i>
                </div>
                <div class="transaction-content">
                    <div class="transaction-text">
                        <strong>Deposit</strong> - John Doe
                    </div>
                    <div class="transaction-amount">+Rp 500.000</div>
                </div>
                <div class="transaction-time">09:15</div>
            </div>
            <div class="transaction-item withdraw">
                <div class="transaction-icon">
                    <i class="bi bi-arrow-up-circle"></i>
                </div>
                <div class="transaction-content">
                    <div class="transaction-text">
                        <strong>Withdrawal</strong> - Jane Smith
                    </div>
                    <div class="transaction-amount">-Rp 200.000</div>
                </div>
                <div class="transaction-time">10:30</div>
            </div>
            <div class="transaction-item deposit">
                <div class="transaction-icon">
                    <i class="bi bi-arrow-down-circle"></i>
                </div>
                <div class="transaction-content">
                    <div class="transaction-text">
                        <strong>Deposit</strong> - Bob Johnson
                    </div>
                    <div class="transaction-amount">+Rp 1.000.000</div>
                </div>
                <div class="transaction-time">11:45</div>
            </div>
        </div>
    </div>
</div>

<style>
.teller-dashboard-container {
    padding: 20px;
    max-width: 1200px;
    margin: 0 auto;
}

.teller-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
    padding-bottom: 20px;
    border-bottom: 2px solid #e9ecef;
}

.teller-title {
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

.teller-metric {
    border-left: 4px solid #28a745;
}

.metric-icon {
    font-size: 2rem;
    color: #28a745;
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

.quick-actions, .service-queue, .recent-members, .savings-summary, .transaction-history {
    background: white;
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 30px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.quick-actions h3, .service-queue h3, .recent-members h3, .savings-summary h3, .transaction-history h3 {
    margin-bottom: 15px;
    color: #2c3e50;
}

.action-buttons {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.queue-display {
    display: flex;
    align-items: center;
    gap: 30px;
    margin-bottom: 20px;
}

.current-queue {
    text-align: center;
    padding: 20px;
    border-radius: 8px;
    background: #28a745;
    color: white;
    min-width: 120px;
}

.queue-number {
    font-size: 2rem;
    font-weight: bold;
}

.queue-label {
    font-size: 0.9rem;
    margin-top: 5px;
}

.next-queues {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.queue-item {
    padding: 10px 15px;
    background: #f8f9fa;
    border-radius: 6px;
    font-weight: bold;
    color: #6c757d;
}

.queue-controls {
    display: flex;
    gap: 10px;
}

.savings-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
}

.savings-item {
    display: flex;
    align-items: center;
    padding: 15px;
    border-radius: 8px;
    background: #f8f9fa;
}

.savings-icon {
    font-size: 1.5rem;
    color: #28a745;
    margin-right: 15px;
}

.savings-label {
    font-size: 0.9rem;
    color: #6c757d;
}

.savings-value {
    font-size: 1.1rem;
    font-weight: bold;
    color: #2c3e50;
}

.transaction-list {
    max-height: 300px;
    overflow-y: auto;
}

.transaction-item {
    display: flex;
    align-items: center;
    padding: 15px;
    border-radius: 8px;
    margin-bottom: 10px;
}

.transaction-item.deposit {
    background: #d4edda;
    border-left: 4px solid #28a745;
}

.transaction-item.withdraw {
    background: #f8d7da;
    border-left: 4px solid #dc3545;
}

.transaction-icon {
    font-size: 1.5rem;
    margin-right: 15px;
}

.transaction-item.deposit .transaction-icon {
    color: #28a745;
}

.transaction-item.withdraw .transaction-icon {
    color: #dc3545;
}

.transaction-content {
    flex: 1;
}

.transaction-text {
    font-weight: 500;
}

.transaction-amount {
    font-weight: bold;
    font-size: 1.1rem;
}

.transaction-item.deposit .transaction-amount {
    color: #28a745;
}

.transaction-item.withdraw .transaction-amount {
    color: #dc3545;
}

.transaction-time {
    color: #6c757d;
    font-size: 0.9rem;
}

@media (max-width: 768px) {
    .teller-header {
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
    
    .queue-display {
        flex-direction: column;
        gap: 20px;
    }
    
    .savings-stats {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
document.getElementById('refresh-teller-dashboard').addEventListener('click', function() {
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
