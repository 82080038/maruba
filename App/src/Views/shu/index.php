<?php include view_path('layout/header'); ?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title mb-0">Manajemen SHU (Sisa Hasil Usaha)</h4>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <a href="<?= route_url('shu/calculate') ?>" class="btn btn-primary">
                                <i class="fas fa-calculator"></i> Hitung SHU Baru
                            </a>
                            <button class="btn btn-success ml-2" onclick="exportSHU()">
                                <i class="fas fa-download"></i> Export Laporan
                            </button>
                        </div>
                        <div class="col-md-6 text-right">
                            <div class="input-group">
                                <select class="form-control" id="yearFilter">
                                    <option value="">Semua Tahun</option>
                                    <?php for ($year = date('Y'); $year >= date('Y') - 5; $year--): ?>
                                        <option value="<?= $year ?>" <?= (isset($_GET['year']) && $_GET['year'] == $year) ? 'selected' : '' ?>>
                                            <?= $year ?>
                                        </option>
                                    <?php endfor; ?>
                                </select>
                                <div class="input-group-append">
                                    <button class="btn btn-outline-secondary" onclick="filterByYear()">
                                        <i class="fas fa-filter"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead class="thead-dark">
                                <tr>
                                    <th>Tahun</th>
                                    <th>Total Profit</th>
                                    <th>Total SHU</th>
                                    <th>Persentase SHU</th>
                                    <th>Status</th>
                                    <th>Tanggal Dibuat</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($shu_calculations ?? [])): ?>
                                    <tr>
                                        <td colspan="7" class="text-center text-muted">
                                            <i class="fas fa-calculator fa-2x mb-2"></i><br>
                                            Belum ada perhitungan SHU
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach (($shu_calculations ?? []) as $shu): ?>
                                        <tr>
                                            <td><strong><?= htmlspecialchars($shu['period_year']) ?></strong></td>
                                            <td>Rp <?= number_format($shu['total_profit'], 0, ',', '.') ?></td>
                                            <td class="text-success font-weight-bold">
                                                Rp <?= number_format($shu['total_shu'], 0, ',', '.') ?>
                                            </td>
                                            <td><?= number_format($shu['shu_percentage'], 1) ?>%</td>
                                            <td>
                                                <?php
                                                $statusClass = match($shu['status']) {
                                                    'draft' => 'secondary',
                                                    'approved' => 'success',
                                                    'distributed' => 'primary',
                                                    default => 'warning'
                                                };
                                                $statusText = match($shu['status']) {
                                                    'draft' => 'Draft',
                                                    'approved' => 'Disetujui',
                                                    'distributed' => 'Didistribusikan',
                                                    default => 'Unknown'
                                                };
                                                ?>
                                                <span class="badge badge-<?= $statusClass ?>">
                                                    <?= $statusText ?>
                                                </span>
                                            </td>
                                            <td><?= format_date_id($shu['created_at']) ?></td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <button class="btn btn-outline-info" onclick="viewSHU(<?= $shu['id'] ?>)">
                                                        <i class="fas fa-eye"></i> Detail
                                                    </button>
                                                    <?php if ($shu['status'] === 'draft'): ?>
                                                        <button class="btn btn-outline-success" onclick="approveSHU(<?= $shu['id'] ?>)">
                                                            <i class="fas fa-check"></i> Setuju
                                                        </button>
                                                    <?php endif; ?>
                                                    <?php if ($shu['status'] === 'approved'): ?>
                                                        <a href="<?= route_url('shu/distribute?id=' . $shu['id']) ?>" class="btn btn-outline-primary">
                                                            <i class="fas fa-share"></i> Distribusi
                                                        </a>
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

<!-- SHU Details Modal -->
<div class="modal fade" id="shuDetailModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Detail SHU</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body" id="shuDetailContent">
                <!-- Content loaded via AJAX -->
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
function filterByYear() {
    const year = document.getElementById('yearFilter').value;
    const url = new URL(window.location);
    if (year) {
        url.searchParams.set('year', year);
    } else {
        url.searchParams.delete('year');
    }
    window.location.href = url.toString();
}

function viewSHU(shuId) {
    // Load SHU details via AJAX
    fetch(`<?= route_url('api/shu') ?>/${shuId}`)
        .then(response => response.json())
        .then(data => {
            document.getElementById('shuDetailContent').innerHTML = `
                <div class="row">
                    <div class="col-md-6">
                        <h6>Informasi Umum</h6>
                        <table class="table table-sm">
                            <tr><td>Tahun:</td><td>${data.period_year}</td></tr>
                            <tr><td>Total Profit:</td><td>Rp ${new Intl.NumberFormat('id-ID').format(data.total_profit)}</td></tr>
                            <tr><td>Total SHU:</td><td>Rp ${new Intl.NumberFormat('id-ID').format(data.total_shu)}</td></tr>
                            <tr><td>Persentase:</td><td>${data.shu_percentage}%</td></tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <h6>Distribusi SHU</h6>
                        <div id="distribution-chart"></div>
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col-12">
                        <h6>Detail Distribusi per Anggota</h6>
                        <div class="table-responsive">
                            <table class="table table-sm table-striped">
                                <thead>
                                    <tr>
                                        <th>Nama Anggota</th>
                                        <th>Jumlah SHU</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody id="member-distribution">
                                    <!-- Member distribution data -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            `;
            $('#shuDetailModal').modal('show');
        })
        .catch(error => {
            console.error('Error loading SHU details:', error);
            alert('Gagal memuat detail SHU');
        });
}

function approveSHU(shuId) {
    if (confirm('Apakah Anda yakin ingin menyetujui perhitungan SHU ini?')) {
        // Implement approval logic
        fetch(`<?= route_url('api/shu/approve') ?>`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ shu_id: shuId })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('SHU berhasil disetujui');
                location.reload();
            } else {
                alert('Gagal menyetujui SHU: ' + (data.error || 'Unknown error'));
            }
        })
        .catch(error => {
            console.error('Error approving SHU:', error);
            alert('Terjadi kesalahan saat menyetujui SHU');
        });
    }
}

function exportSHU() {
    const year = document.getElementById('yearFilter').value;
    const url = `<?= route_url('shu/export') ?>${year ? '?year=' + year : ''}`;
    window.open(url, '_blank');
}
</script>

<?php include view_path('layout/footer'); ?>
