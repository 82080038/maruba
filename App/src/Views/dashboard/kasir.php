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

<div class="kasir-dashboard-container">
    <!-- Kasir Header -->
    <div class="kasir-header">
        <div>
            <h1 class="kasir-title">💰 Dashboard Kasir</h1>
            <p class="text-muted">Selamat <?= htmlspecialchars($sapaan) ?>, <?= htmlspecialchars($displayName) ?> (<?= htmlspecialchars($role) ?>)</p>
        </div>
        <div class="header-actions">
            <button id="refresh-kasir-dashboard" class="btn btn-primary">
                <i class="bi bi-arrow-clockwise"></i>
                Refresh
            </button>
            <a href="<?= route_url('repayments/create') ?>" class="btn btn-success">
                <i class="bi bi-plus-circle"></i>
                Bayar Cicilan
            </a>
        </div>
    </div>

    <!-- Kasir Metrics Grid -->
    <div class="metrics-grid" id="kasir-metrics-grid">
        <?php foreach ($metrics as $metric): ?>
        <div class="metric-card kasir-metric">
            <div class="metric-icon">
                <?php
                $iconClass = match($metric['label']) {
                    'Cash Flow Hari Ini' => 'bi-cash-stack',
                    'Transaksi Pending' => 'bi-clock-history',
                    'Payment Gateway' => 'bi-wifi',
                    'Reconciled Today' => 'bi-check-circle',
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
            <a href="<?= route_url('repayments/create') ?>" class="btn btn-primary">
                <i class="bi bi-cash"></i>
                Proses Pembayaran
            </a>
            <a href="<?= route_url('repayments') ?>" class="btn btn-info">
                <i class="bi bi-list-ul"></i>
                Lihat Transaksi
            </a>
            <a href="<?= route_url('reports') ?>" class="btn btn-secondary">
                <i class="bi bi-file-text"></i>
                Laporan Harian
            </a>
        </div>
    </div>

    <!-- Recent Transactions -->
    <div class="recent-transactions">
        <h3>Transaksi Terbaru</h3>
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>Anggota</th>
                        <th>Pinjaman</th>
                        <th>Jumlah</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($recentTransactions)): ?>
                    <tr>
                        <td colspan="5" class="text-center">Belum ada transaksi hari ini</td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($recentTransactions as $transaction): ?>
                    <tr>
                        <td><?= date('d/m/Y H:i', strtotime($transaction['paid_date'])) ?></td>
                        <td><?= htmlspecialchars($transaction['member_name'] ?? '-') ?></td>
                        <td>Rp <?= number_format($transaction['loan_amount'] ?? 0, 0, ',', '.') ?></td>
                        <td>Rp <?= number_format($transaction['amount_paid'] ?? 0, 0, ',', '.') ?></td>
                        <td>
                            <span class="badge bg-<?= $transaction['status'] === 'paid' ? 'success' : 'warning' ?>">
                                <?= htmlspecialchars($transaction['status']) ?>
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Payment Status -->
    <div class="payment-status">
        <h3>Status Payment Gateway</h3>
        <div class="status-indicators">
            <div class="status-item">
                <div class="status-dot online"></div>
                <span>Bank Transfer</span>
            </div>
            <div class="status-item">
                <div class="status-dot online"></div>
                <span>E-Wallet</span>
            </div>
            <div class="status-item">
                <div class="status-dot offline"></div>
                <span>Virtual Account</span>
            </div>
        </div>
    </div>
</div>

<style>
.kasir-dashboard-container {
    padding: 20px;
    max-width: 1200px;
    margin: 0 auto;
}

.kasir-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
    padding-bottom: 20px;
    border-bottom: 2px solid #e9ecef;
}

.kasir-title {
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

.kasir-metric {
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

.quick-actions {
    background: white;
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 30px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.quick-actions h3 {
    margin-bottom: 15px;
    color: #2c3e50;
}

.action-buttons {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.recent-transactions, .payment-status {
    background: white;
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 30px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.recent-transactions h3, .payment-status h3 {
    margin-bottom: 15px;
    color: #2c3e50;
}

.status-indicators {
    display: flex;
    gap: 20px;
}

.status-item {
    display: flex;
    align-items: center;
    gap: 8px;
}

.status-dot {
    width: 12px;
    height: 12px;
    border-radius: 50%;
}

.status-dot.online {
    background-color: #28a745;
}

.status-dot.offline {
    background-color: #dc3545;
}

@media (max-width: 768px) {
    .kasir-header {
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
    
    .status-indicators {
        flex-direction: column;
    }
}
</style>

<script>
document.getElementById('refresh-kasir-dashboard').addEventListener('click', function() {
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
