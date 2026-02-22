<?php include view_path('layout/header'); ?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title mb-0">Sistem Akuntansi</h4>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-8">
                            <a href="<?= route_url('accounting/journal/create') ?>" class="btn btn-primary">
                                <i class="fas fa-plus"></i> Jurnal Baru
                            </a>
                            <a href="<?= route_url('accounting/chart') ?>" class="btn btn-info ml-2">
                                <i class="fas fa-book"></i> Chart of Accounts
                            </a>
                            <a href="<?= route_url('accounting/reports') ?>" class="btn btn-success ml-2">
                                <i class="fas fa-chart-bar"></i> Laporan Keuangan
                            </a>
                        </div>
                        <div class="col-md-4 text-right">
                            <div class="input-group">
                                <input type="text" class="form-control" placeholder="Cari jurnal..." id="searchInput">
                                <div class="input-group-append">
                                    <button class="btn btn-outline-secondary" type="button">
                                        <i class="fas fa-search"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Financial Summary Cards -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="card bg-primary text-white">
                                <div class="card-body">
                                    <h5 class="card-title">Total Aset</h5>
                                    <h3>Rp 0</h3>
                                    <small>Assets</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-success text-white">
                                <div class="card-body">
                                    <h5 class="card-title">Total Liabilitas</h5>
                                    <h3>Rp 0</h3>
                                    <small>Liabilities</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-warning text-white">
                                <div class="card-body">
                                    <h5 class="card-title">Total Ekuitas</h5>
                                    <h3>Rp 0</h3>
                                    <small>Equity</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-info text-white">
                                <div class="card-body">
                                    <h5 class="card-title">Laba/Rugi</h5>
                                    <h3>Rp 0</h3>
                                    <small>Profit/Loss</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead class="thead-dark">
                                <tr>
                                    <th>No. Jurnal</th>
                                    <th>Tanggal</th>
                                    <th>Deskripsi</th>
                                    <th>Referensi</th>
                                    <th>Total Debit</th>
                                    <th>Total Kredit</th>
                                    <th>Status</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($journals ?? [])): ?>
                                    <tr>
                                        <td colspan="8" class="text-center text-muted">
                                            <i class="fas fa-book fa-2x mb-2"></i><br>
                                            Belum ada jurnal akuntansi
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach (($journals ?? []) as $journal): ?>
                                        <tr>
                                            <td><strong><?= htmlspecialchars($journal['journal_number']) ?></strong></td>
                                            <td><?= format_date_id($journal['transaction_date']) ?></td>
                                            <td>
                                                <?= htmlspecialchars($journal['description']) ?>
                                                <?php if (isset($journal['tenant_name'])): ?>
                                                    <br><small class="text-muted">Tenant: <?= htmlspecialchars($journal['tenant_name']) ?></small>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php
                                                $refText = match($journal['reference_type']) {
                                                    'loan' => 'Pinjaman',
                                                    'savings' => 'Simpanan',
                                                    'repayment' => 'Pelunasan',
                                                    'fee' => 'Biaya',
                                                    default => ucfirst($journal['reference_type'] ?? 'Umum')
                                                };
                                                echo $refText;
                                                ?>
                                            </td>
                                            <td class="text-danger">Rp <?= number_format($journal['total_debit'], 0, ',', '.') ?></td>
                                            <td class="text-success">Rp <?= number_format($journal['total_credit'], 0, ',', '.') ?></td>
                                            <td>
                                                <?php
                                                $statusClass = match($journal['status']) {
                                                    'draft' => 'secondary',
                                                    'posted' => 'success',
                                                    'cancelled' => 'danger',
                                                    default => 'warning'
                                                };
                                                $statusText = match($journal['status']) {
                                                    'draft' => 'Draft',
                                                    'posted' => 'Posted',
                                                    'cancelled' => 'Dibatalkan',
                                                    default => 'Unknown'
                                                };
                                                ?>
                                                <span class="badge badge-<?= $statusClass ?>">
                                                    <?= $statusText ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <button class="btn btn-outline-info" onclick="viewJournal(<?= $journal['id'] ?>)">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                    <?php if ($journal['status'] === 'draft'): ?>
                                                        <button class="btn btn-outline-success" onclick="postJournal(<?= $journal['id'] ?>)">
                                                            <i class="fas fa-check"></i>
                                                        </button>
                                                        <button class="btn btn-outline-danger" onclick="cancelJournal(<?= $journal['id'] ?>)">
                                                            <i class="fas fa-times"></i>
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

<!-- Journal Details Modal -->
<div class="modal fade" id="journalModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Detail Jurnal</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body" id="journalContent">
                <!-- Journal details loaded via AJAX -->
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
function viewJournal(journalId) {
    // Load journal details via AJAX
    fetch(`<?= route_url('api/accounting/journal') ?>/${journalId}`)
        .then(response => response.json())
        .then(data => {
            let content = `
                <div class="row mb-3">
                    <div class="col-md-6">
                        <h6>Informasi Jurnal</h6>
                        <table class="table table-sm">
                            <tr><td>No. Jurnal:</td><td><strong>${data.journal_number}</strong></td></tr>
                            <tr><td>Tanggal:</td><td>${new Date(data.transaction_date).toLocaleDateString('id-ID')}</td></tr>
                            <tr><td>Deskripsi:</td><td>${data.description}</td></tr>
                            <tr><td>Referensi:</td><td>${data.reference_type} ${data.reference_id || ''}</td></tr>
                            <tr><td>Status:</td><td><span class="badge badge-${data.status === 'posted' ? 'success' : 'secondary'}">${data.status}</span></td></tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <h6>Summary</h6>
                        <div class="row">
                            <div class="col-6">
                                <div class="card bg-danger text-white">
                                    <div class="card-body text-center">
                                        <h5>Rp ${new Intl.NumberFormat('id-ID').format(data.total_debit)}</h5>
                                        <small>Total Debit</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="card bg-success text-white">
                                    <div class="card-body text-center">
                                        <h5>Rp ${new Intl.NumberFormat('id-ID').format(data.total_credit)}</h5>
                                        <small>Total Kredit</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-12">
                        <h6>Detail Transaksi</h6>
                        <div class="table-responsive">
                            <table class="table table-sm table-striped">
                                <thead>
                                    <tr>
                                        <th>Kode Akun</th>
                                        <th>Nama Akun</th>
                                        <th>Debit</th>
                                        <th>Kredit</th>
                                        <th>Keterangan</th>
                                    </tr>
                                </thead>
                                <tbody>
            `;

            data.lines.forEach(line => {
                content += `
                    <tr>
                        <td>${line.account_code}</td>
                        <td>${line.account_name}</td>
                        <td class="text-danger">${line.debit > 0 ? 'Rp ' + new Intl.NumberFormat('id-ID').format(line.debit) : '-'}</td>
                        <td class="text-success">${line.credit > 0 ? 'Rp ' + new Intl.NumberFormat('id-ID').format(line.credit) : '-'}</td>
                        <td>${line.description || '-'}</td>
                    </tr>
                `;
            });

            content += `
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            `;

            document.getElementById('journalContent').innerHTML = content;
            $('#journalModal').modal('show');
        })
        .catch(error => {
            console.error('Error loading journal details:', error);
            alert('Gagal memuat detail jurnal');
        });
}

function postJournal(journalId) {
    if (confirm('Apakah Anda yakin ingin memposting jurnal ini? Setelah diposting, jurnal tidak dapat diubah.')) {
        fetch(`<?= route_url('api/accounting/post') ?>`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ journal_id: journalId })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Jurnal berhasil diposting');
                location.reload();
            } else {
                alert('Gagal memposting jurnal: ' + (data.error || 'Unknown error'));
            }
        })
        .catch(error => {
            console.error('Error posting journal:', error);
            alert('Terjadi kesalahan saat memposting jurnal');
        });
    }
}

function cancelJournal(journalId) {
    if (confirm('Apakah Anda yakin ingin membatalkan jurnal ini?')) {
        fetch(`<?= route_url('api/accounting/cancel') ?>`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ journal_id: journalId })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Jurnal berhasil dibatalkan');
                location.reload();
            } else {
                alert('Gagal membatalkan jurnal: ' + (data.error || 'Unknown error'));
            }
        })
        .catch(error => {
            console.error('Error canceling journal:', error);
            alert('Terjadi kesalahan saat membatalkan jurnal');
        });
    }
}

// Search functionality
document.getElementById('searchInput').addEventListener('keyup', function() {
    const filter = this.value.toUpperCase();
    const rows = document.querySelectorAll('tbody tr');

    rows.forEach(row => {
        const text = row.textContent.toUpperCase();
        row.style.display = text.includes(filter) ? '' : 'none';
    });
});
</script>

<?php include view_path('layout/footer'); ?>
