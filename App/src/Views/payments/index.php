<?php include view_path('layout/header'); ?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title mb-0">Payment Gateway</h4>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <a href="<?= route_url('payments/create') ?>" class="btn btn-primary">
                                <i class="fas fa-plus"></i> Pembayaran Baru
                            </a>
                            <button class="btn btn-info ml-2" onclick="refreshStatus()">
                                <i class="fas fa-sync"></i> Refresh Status
                            </button>
                        </div>
                        <div class="col-md-6 text-right">
                            <div class="input-group">
                                <select class="form-control" id="statusFilter">
                                    <option value="">Semua Status</option>
                                    <option value="pending">Pending</option>
                                    <option value="processing">Processing</option>
                                    <option value="completed">Completed</option>
                                    <option value="failed">Failed</option>
                                    <option value="cancelled">Cancelled</option>
                                    <option value="refunded">Refunded</option>
                                </select>
                                <div class="input-group-append">
                                    <button class="btn btn-outline-secondary" onclick="filterByStatus()">
                                        <i class="fas fa-filter"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Payment Statistics -->
                    <div class="row mb-4">
                        <div class="col-md-2">
                            <div class="card bg-warning text-white">
                                <div class="card-body text-center">
                                    <h4>0</h4>
                                    <small>Pending</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="card bg-info text-white">
                                <div class="card-body text-center">
                                    <h4>0</h4>
                                    <small>Processing</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="card bg-success text-white">
                                <div class="card-body text-center">
                                    <h4>0</h4>
                                    <small>Completed</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="card bg-danger text-white">
                                <div class="card-body text-center">
                                    <h4>0</h4>
                                    <small>Failed</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="card bg-secondary text-white">
                                <div class="card-body text-center">
                                    <h4>0</h4>
                                    <small>Cancelled</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="card bg-primary text-white">
                                <div class="card-body text-center">
                                    <h4>Rp 0</h4>
                                    <small>Total Volume</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead class="thead-dark">
                                <tr>
                                    <th>ID</th>
                                    <th>Tanggal</th>
                                    <th>Jenis</th>
                                    <th>Referensi</th>
                                    <th>Jumlah</th>
                                    <th>Metode</th>
                                    <th>Status</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($payments ?? [])): ?>
                                    <tr>
                                        <td colspan="8" class="text-center text-muted">
                                            <i class="fas fa-credit-card fa-2x mb-2"></i><br>
                                            Belum ada transaksi pembayaran
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach (($payments ?? []) as $payment): ?>
                                        <tr>
                                            <td><strong>#<?= htmlspecialchars($payment['id']) ?></strong></td>
                                            <td>
                                                <?= format_date_id($payment['created_at']) ?>
                                                <?php if ($payment['payment_date']): ?>
                                                    <br><small class="text-muted">Paid: <?= format_date_id($payment['payment_date']) ?></small>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php
                                                $typeText = match($payment['reference_type']) {
                                                    'loan_repayment' => 'Pelunasan Pinjaman',
                                                    'savings_deposit' => 'Setoran Simpanan',
                                                    'fee' => 'Biaya Admin',
                                                    default => ucfirst(str_replace('_', ' ', $payment['reference_type']))
                                                };
                                                echo $typeText;
                                                ?>
                                            </td>
                                            <td>
                                                #<?= htmlspecialchars($payment['reference_id']) ?>
                                                <?php if (isset($payment['tenant_name'])): ?>
                                                    <br><small class="text-muted">Tenant: <?= htmlspecialchars($payment['tenant_name']) ?></small>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-primary font-weight-bold">
                                                Rp <?= number_format($payment['amount'], 0, ',', '.') ?>
                                            </td>
                                            <td>
                                                <?php
                                                $methodIcon = match($payment['payment_method']) {
                                                    'virtual_account' => 'fas fa-university',
                                                    'bank_transfer' => 'fas fa-exchange-alt',
                                                    'cash' => 'fas fa-money-bill-wave',
                                                    'e_wallet' => 'fas fa-mobile-alt',
                                                    'auto_debit' => 'fas fa-robot',
                                                    default => 'fas fa-credit-card'
                                                };
                                                $methodText = match($payment['payment_method']) {
                                                    'virtual_account' => 'VA',
                                                    'bank_transfer' => 'Transfer',
                                                    'cash' => 'Tunai',
                                                    'e_wallet' => 'E-Wallet',
                                                    'auto_debit' => 'Auto Debit',
                                                    default => ucfirst(str_replace('_', ' ', $payment['payment_method']))
                                                };
                                                ?>
                                                <i class="<?= $methodIcon ?>"></i> <?= $methodText ?>
                                            </td>
                                            <td>
                                                <?php
                                                $statusClass = match($payment['status']) {
                                                    'pending' => 'warning',
                                                    'processing' => 'info',
                                                    'completed' => 'success',
                                                    'failed' => 'danger',
                                                    'cancelled' => 'secondary',
                                                    'refunded' => 'primary',
                                                    default => 'light'
                                                };
                                                $statusText = match($payment['status']) {
                                                    'pending' => 'Menunggu',
                                                    'processing' => 'Diproses',
                                                    'completed' => 'Selesai',
                                                    'failed' => 'Gagal',
                                                    'cancelled' => 'Dibatalkan',
                                                    'refunded' => 'Direfund',
                                                    default => ucfirst($payment['status'])
                                                };
                                                ?>
                                                <span class="badge badge-<?= $statusClass ?>">
                                                    <?= $statusText ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <button class="btn btn-outline-info" onclick="viewPayment(<?= $payment['id'] ?>)">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                    <?php if ($payment['status'] === 'pending'): ?>
                                                        <button class="btn btn-outline-success" onclick="processPayment(<?= $payment['id'] ?>)">
                                                            <i class="fas fa-play"></i>
                                                        </button>
                                                        <button class="btn btn-outline-danger" onclick="cancelPayment(<?= $payment['id'] ?>)">
                                                            <i class="fas fa-times"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                    <?php if ($payment['status'] === 'completed' && $payment['payment_method'] !== 'cash'): ?>
                                                        <button class="btn btn-outline-primary" onclick="refundPayment(<?= $payment['id'] ?>)">
                                                            <i class="fas fa-undo"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
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
</div>

<!-- Payment Details Modal -->
<div class="modal fade" id="paymentModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Detail Pembayaran</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body" id="paymentContent">
                <!-- Payment details loaded via AJAX -->
                <div class="text-center">
                    <div class="spinner-border" role="status">
                        <span class="sr-only">Loading...</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function filterByStatus() {
    const status = document.getElementById('statusFilter').value;
    const url = new URL(window.location);
    if (status) {
        url.searchParams.set('status', status);
    } else {
        url.searchParams.delete('status');
    }
    window.location.href = url.toString();
}

function refreshStatus() {
    // Refresh payment statuses from gateway
    fetch('<?= route_url('api/payments/refresh') ?>', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(`${data.updated} pembayaran berhasil diupdate`);
            location.reload();
        } else {
            alert('Gagal me-refresh status pembayaran');
        }
    })
    .catch(error => {
        console.error('Error refreshing payments:', error);
        alert('Terjadi kesalahan saat me-refresh pembayaran');
    });
}

function viewPayment(paymentId) {
    // Load payment details via AJAX
    fetch(`<?= route_url('api/payments') ?>/${paymentId}`)
        .then(response => response.json())
        .then(data => {
            let statusBadge = '';
            const statusClass = getStatusClass(data.status);
            const statusText = getStatusText(data.status);

            let content = `
                <div class="row">
                    <div class="col-md-6">
                        <h6>Informasi Pembayaran</h6>
                        <table class="table table-sm">
                            <tr><td>ID:</td><td><strong>#${data.id}</strong></td></tr>
                            <tr><td>Jenis:</td><td>${getTypeText(data.reference_type)}</td></tr>
                            <tr><td>Referensi:</td><td>${data.reference_type} #${data.reference_id}</td></tr>
                            <tr><td>Jumlah:</td><td><strong class="text-primary">Rp ${new Intl.NumberFormat('id-ID').format(data.amount)}</strong></td></tr>
                            <tr><td>Metode:</td><td>${getMethodText(data.payment_method)}</td></tr>
                            <tr><td>Status:</td><td><span class="badge badge-${statusClass}">${statusText}</span></td></tr>
                            <tr><td>Dibuat:</td><td>${new Date(data.created_at).toLocaleString('id-ID')}</td></tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <h6>Informasi Gateway</h6>
                        <table class="table table-sm">
                            <tr><td>Provider:</td><td>${data.gateway_provider || '-'}</td></tr>
                            <tr><td>Transaction ID:</td><td>${data.transaction_id || '-'}</td></tr>
                            <tr><td>Gateway Ref:</td><td>${data.gateway_reference || '-'}</td></tr>
                            <tr><td>Tanggal Bayar:</td><td>${data.payment_date ? new Date(data.payment_date).toLocaleString('id-ID') : '-'}</td></tr>
                            <tr><td>Konfirmasi:</td><td>${data.confirmation_date ? new Date(data.confirmation_date).toLocaleString('id-ID') : '-'}</td></tr>
                            ${data.failure_reason ? `<tr><td>Alasan Gagal:</td><td class="text-danger">${data.failure_reason}</td></tr>` : ''}
                        </table>
                    </div>
                </div>
                ${data.notes ? `<div class="row mt-3"><div class="col-12"><h6>Catatan</h6><p>${data.notes}</p></div></div>` : ''}
            `;

            document.getElementById('paymentContent').innerHTML = content;
            $('#paymentModal').modal('show');
        })
        .catch(error => {
            console.error('Error loading payment details:', error);
            alert('Gagal memuat detail pembayaran');
        });
}

function processPayment(paymentId) {
    if (confirm('Apakah Anda yakin ingin memproses pembayaran ini?')) {
        fetch(`<?= route_url('api/payments/process') ?>`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ payment_id: paymentId })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Pembayaran berhasil diproses');
                location.reload();
            } else {
                alert('Gagal memproses pembayaran: ' + (data.error || 'Unknown error'));
            }
        })
        .catch(error => {
            console.error('Error processing payment:', error);
            alert('Terjadi kesalahan saat memproses pembayaran');
        });
    }
}

function cancelPayment(paymentId) {
    const reason = prompt('Masukkan alasan pembatalan:');
    if (reason !== null && reason.trim() !== '') {
        fetch(`<?= route_url('api/payments/cancel') ?>`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ payment_id: paymentId, reason: reason.trim() })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Pembayaran berhasil dibatalkan');
                location.reload();
            } else {
                alert('Gagal membatalkan pembayaran: ' + (data.error || 'Unknown error'));
            }
        })
        .catch(error => {
            console.error('Error canceling payment:', error);
            alert('Terjadi kesalahan saat membatalkan pembayaran');
        });
    }
}

function refundPayment(paymentId) {
    const reason = prompt('Masukkan alasan refund:');
    if (reason !== null && reason.trim() !== '') {
        fetch(`<?= route_url('api/payments/refund') ?>`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ payment_id: paymentId, reason: reason.trim() })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Refund berhasil diproses');
                location.reload();
            } else {
                alert('Gagal memproses refund: ' + (data.error || 'Unknown error'));
            }
        })
        .catch(error => {
            console.error('Error refunding payment:', error);
            alert('Terjadi kesalahan saat memproses refund');
        });
    }
}

// Helper functions
function getStatusClass(status) {
    return {
        'pending': 'warning',
        'processing': 'info',
        'completed': 'success',
        'failed': 'danger',
        'cancelled': 'secondary',
        'refunded': 'primary'
    }[status] || 'light';
}

function getStatusText(status) {
    return {
        'pending': 'Menunggu',
        'processing': 'Diproses',
        'completed': 'Selesai',
        'failed': 'Gagal',
        'cancelled': 'Dibatalkan',
        'refunded': 'Direfund'
    }[status] || status;
}

function getTypeText(type) {
    return {
        'loan_repayment': 'Pelunasan Pinjaman',
        'savings_deposit': 'Setoran Simpanan',
        'fee': 'Biaya Admin'
    }[type] || type;
}

function getMethodText(method) {
    return {
        'virtual_account': 'Virtual Account',
        'bank_transfer': 'Transfer Bank',
        'cash': 'Tunai',
        'e_wallet': 'E-Wallet',
        'auto_debit': 'Auto Debit'
    }[method] || method;
}
</script>

<?php include view_path('layout/footer'); ?>
