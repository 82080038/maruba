<?php include view_path('layout/header'); ?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title mb-0">Manajemen Simpanan</h4>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <a href="<?= route_url('savings/create') ?>" class="btn btn-primary">
                                <i class="fas fa-plus"></i> Buka Rekening Baru
                            </a>
                            <a href="<?= route_url('savings/accounts') ?>" class="btn btn-info ml-2">
                                <i class="fas fa-list"></i> Lihat Semua Rekening
                            </a>
                        </div>
                        <div class="col-md-6 text-right">
                            <div class="input-group">
                                <input type="text" class="form-control" placeholder="Cari rekening..." id="searchInput">
                                <div class="input-group-append">
                                    <button class="btn btn-outline-secondary" type="button">
                                        <i class="fas fa-search"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead class="thead-dark">
                                <tr>
                                    <th>No. Rekening</th>
                                    <th>Nama Anggota</th>
                                    <th>Produk</th>
                                    <th>Saldo</th>
                                    <th>Status</th>
                                    <th>Tanggal Dibuat</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($accounts)): ?>
                                    <tr>
                                        <td colspan="7" class="text-center text-muted">
                                            <i class="fas fa-inbox fa-2x mb-2"></i><br>
                                            Belum ada rekening simpanan
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($accounts as $account): ?>
                                        <tr>
                                            <td><strong><?= htmlspecialchars($account['account_number']) ?></strong></td>
                                            <td>
                                                <?= htmlspecialchars($account['member_name']) ?>
                                                <?php if (isset($account['tenant_name'])): ?>
                                                    <br><small class="text-muted"><?= htmlspecialchars($account['tenant_name']) ?></small>
                                                <?php endif; ?>
                                            </td>
                                            <td><?= htmlspecialchars($account['product_name']) ?></td>
                                            <td>
                                                <span class="text-success font-weight-bold">
                                                    Rp <?= number_format($account['balance'], 0, ',', '.') ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge badge-<?= $account['status'] === 'active' ? 'success' : 'secondary' ?>">
                                                    <?= ucfirst($account['status']) ?>
                                                </span>
                                            </td>
                                            <td><?= format_date_id($account['created_at']) ?></td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <button class="btn btn-outline-primary" onclick="viewAccount(<?= $account['id'] ?>)">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                    <button class="btn btn-outline-success" onclick="depositModal(<?= $account['id'] ?>, '<?= htmlspecialchars($account['account_number']) ?>')">
                                                        <i class="fas fa-plus"></i>
                                                    </button>
                                                    <button class="btn btn-outline-warning" onclick="withdrawModal(<?= $account['id'] ?>, '<?= htmlspecialchars($account['account_number']) ?>', <?= $account['balance'] ?>)">
                                                        <i class="fas fa-minus"></i>
                                                    </button>
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

<!-- Deposit Modal -->
<div class="modal fade" id="depositModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Setoran Simpanan</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form method="POST" action="<?= route_url('savings/deposit') ?>">
                <?= csrf_field() ?>
                <div class="modal-body">
                    <input type="hidden" name="account_id" id="depositAccountId">
                    <div class="form-group">
                        <label>Rekening:</label>
                        <input type="text" class="form-control" id="depositAccountNumber" readonly>
                    </div>
                    <div class="form-group">
                        <label>Jumlah Setoran:</label>
                        <input type="number" class="form-control" name="amount" min="1000" required>
                    </div>
                    <div class="form-group">
                        <label>Keterangan:</label>
                        <textarea class="form-control" name="description" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success">Setor</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Withdraw Modal -->
<div class="modal fade" id="withdrawModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Penarikan Simpanan</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form method="POST" action="<?= route_url('savings/withdraw') ?>">
                <?= csrf_field() ?>
                <div class="modal-body">
                    <input type="hidden" name="account_id" id="withdrawAccountId">
                    <div class="form-group">
                        <label>Rekening:</label>
                        <input type="text" class="form-control" id="withdrawAccountNumber" readonly>
                    </div>
                    <div class="form-group">
                        <label>Saldo Tersedia:</label>
                        <input type="text" class="form-control" id="withdrawBalance" readonly>
                    </div>
                    <div class="form-group">
                        <label>Jumlah Penarikan:</label>
                        <input type="number" class="form-control" name="amount" min="1000" id="withdrawAmount" required>
                    </div>
                    <div class="form-group">
                        <label>Keterangan:</label>
                        <textarea class="form-control" name="description" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-warning">Tarik</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function depositModal(accountId, accountNumber) {
    document.getElementById('depositAccountId').value = accountId;
    document.getElementById('depositAccountNumber').value = accountNumber;
    $('#depositModal').modal('show');
}

function withdrawModal(accountId, accountNumber, balance) {
    document.getElementById('withdrawAccountId').value = accountId;
    document.getElementById('withdrawAccountNumber').value = accountNumber;
    document.getElementById('withdrawBalance').value = 'Rp ' + new Intl.NumberFormat('id-ID').format(balance);
    document.getElementById('withdrawAmount').max = balance;
    $('#withdrawModal').modal('show');
}

function viewAccount(accountId) {
    // Implement account details view
    window.location.href = '<?= route_url('savings/accounts') ?>?view=' + accountId;
}
</script>

<?php include view_path('layout/footer'); ?>
