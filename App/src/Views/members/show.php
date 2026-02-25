<?php include view_path('layout/header'); ?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title mb-0">Detail Anggota</h4>
                    <div class="card-tools">
                        <a href="<?= route_url('index.php/members') ?>" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Kembali
                        </a>
                        <a href="<?= route_url('index.php/members/edit') ?>?id=<?= $member['id'] ?>" class="btn btn-primary">
                            <i class="fas fa-edit"></i> Edit
                        </a>
                        <a href="<?= route_url('index.php/members/delete') ?>?id=<?= $member['id'] ?>" class="btn btn-danger" 
                           onclick="return confirm('Apakah Anda yakin ingin menghapus anggota ini?')">
                            <i class="fas fa-trash"></i> Hapus
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-8">
                            <h5>Informasi Pribadi</h5>
                            <table class="table table-striped">
                                <tr>
                                    <th width="150">Nama Lengkap</th>
                                    <td><?= htmlspecialchars($member['name']) ?></td>
                                </tr>
                                <tr>
                                    <th>No. KTP</th>
                                    <td><?= htmlspecialchars($member['nik']) ?></td>
                                </tr>
                                <tr>
                                    <th>No. Telepon</th>
                                    <td><?= htmlspecialchars($member['phone']) ?></td>
                                </tr>
                                <tr>
                                    <th>Email</th>
                                    <td><?= htmlspecialchars($member['email'] ?? '-') ?></td>
                                </tr>
                                <tr>
                                    <th>Alamat</th>
                                    <td><?= htmlspecialchars($member['address']) ?></td>
                                </tr>
                                <tr>
                                    <th>Pekerjaan</th>
                                    <td><?= htmlspecialchars($member['occupation'] ?? '-') ?></td>
                                </tr>
                                <tr>
                                    <th>Penghasilan Bulanan</th>
                                    <td>Rp <?= number_format($member['monthly_income'] ?? 0, 0, ',', '.') ?></td>
                                </tr>
                                <tr>
                                    <th>Status</th>
                                    <td>
                                        <span class="badge badge-<?= $member['status'] === 'active' ? 'success' : ($member['status'] === 'inactive' ? 'secondary' : 'warning') ?>">
                                            <?= ucfirst($member['status']) ?>
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Tanggal Bergabung</th>
                                    <td><?= date('d/m/Y', strtotime($member['created_at'])) ?></td>
                                </tr>
                            </table>
                            
                            <h5 class="mt-4">Kontak Darurat</h5>
                            <table class="table table-striped">
                                <tr>
                                    <th width="150">Nama</th>
                                    <td><?= htmlspecialchars($member['emergency_contact_name'] ?? '-') ?></td>
                                </tr>
                                <tr>
                                    <th>Telepon</th>
                                    <td><?= htmlspecialchars($member['emergency_contact_phone'] ?? '-') ?></td>
                                </tr>
                            </table>
                            
                            <h5 class="mt-4">Verifikasi</h5>
                            <table class="table table-striped">
                                <tr>
                                    <th width="150">Status Verifikasi</th>
                                    <td>
                                        <span class="badge badge-<?= $member['verification_status'] === 'verified' ? 'success' : ($member['verification_status'] === 'rejected' ? 'danger' : 'warning') ?>">
                                            <?= ucfirst($member['verification_status']) ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php if ($member['verified_at']): ?>
                                <tr>
                                    <th>Tanggal Verifikasi</th>
                                    <td><?= date('d/m/Y H:i', strtotime($member['verified_at'])) ?></td>
                                </tr>
                                <?php endif; ?>
                                <?php if ($member['verified_by']): ?>
                                <tr>
                                    <th>Diverifikasi Oleh</th>
                                    <td><?= htmlspecialchars($member['verified_by']) ?></td>
                                </tr>
                                <?php endif; ?>
                            </table>
                        </div>
                        
                        <div class="col-md-4">
                            <h5>Lokasi</h5>
                            <div class="mb-3">
                                <div id="map" style="height: 300px; border: 1px solid #ddd;"></div>
                            </div>
                            
                            <table class="table table-sm">
                                <tr>
                                    <th>Latitude</th>
                                    <td><?= $member['latitude'] ?? '-' ?></td>
                                </tr>
                                <tr>
                                    <th>Longitude</th>
                                    <td><?= $member['longitude'] ?? '-' ?></td>
                                </tr>
                            </table>
                            
                            <h5 class="mt-4">Dokumen</h5>
                            <div class="mb-3">
                                <h6>KTP</h6>
                                <?php if ($member['ktp_photo_path']): ?>
                                    <img src="<?= asset_url('uploads/' . $member['ktp_photo_path']) ?>" 
                                         class="img-thumbnail" style="max-width: 100%;">
                                <?php else: ?>
                                    <p class="text-muted">Belum ada dokumen</p>
                                <?php endif; ?>
                            </div>
                            
                            <div class="mb-3">
                                <h6>Kartu Keluarga</h6>
                                <?php if ($member['kk_photo_path']): ?>
                                    <img src="<?= asset_url('uploads/' . $member['kk_photo_path']) ?>" 
                                         class="img-thumbnail" style="max-width: 100%;">
                                <?php else: ?>
                                    <p class="text-muted">Belum ada dokumen</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row mt-4">
                        <div class="col-12">
                            <h5>Riwayat Transaksi</h5>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Tanggal</th>
                                            <th>Jenis</th>
                                            <th>No. Referensi</th>
                                            <th>Jumlah</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (!empty($transactions)): ?>
                                            <?php foreach ($transactions as $transaction): ?>
                                                <tr>
                                                    <td><?= date('d/m/Y H:i', strtotime($transaction['created_at'])) ?></td>
                                                    <td><?= ucfirst($transaction['type']) ?></td>
                                                    <td><?= htmlspecialchars($transaction['reference_number'] ?? '-') ?></td>
                                                    <td>Rp <?= number_format($transaction['amount'], 0, ',', '.') ?></td>
                                                    <td>
                                                        <span class="badge badge-<?= $transaction['status'] === 'completed' ? 'success' : ($transaction['status'] === 'pending' ? 'warning' : 'danger') ?>">
                                                            <?= ucfirst($transaction['status']) ?>
                                                        </span>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="5" class="text-center text-muted">Belum ada transaksi</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Simple map display (you can integrate with Google Maps API)
function initMap() {
    const mapElement = document.getElementById('map');
    const lat = <?= $member['latitude'] ?? '0' ?>;
    const lng = <?= $member['longitude'] ?? '0' ?>;
    
    if (lat && lng) {
        // This is a placeholder for map integration
        // You can integrate with Google Maps, Leaflet, or other mapping services
        mapElement.innerHTML = `
            <div class="text-center p-3">
                <i class="fas fa-map-marker-alt fa-3x text-primary"></i>
                <p class="mt-2">Lokasi: ${lat}, ${lng}</p>
                <small class="text-muted">Map integration can be added here</small>
            </div>
        `;
    } else {
        mapElement.innerHTML = `
            <div class="text-center p-3">
                <i class="fas fa-map-marker-alt fa-3x text-muted"></i>
                <p class="mt-2">Lokasi tidak tersedia</p>
            </div>
        `;
    }
}

// Initialize map on page load
document.addEventListener('DOMContentLoaded', initMap);
</script>

<?php include view_path('layout/footer'); ?>
