<?php include view_path('layout/header'); ?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title mb-0">Edit Anggota</h4>
                    <div class="card-tools">
                        <a href="<?= route_url('members') ?>" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Kembali
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger">
                            <?= htmlspecialchars($error) ?>
                        </div>
                    <?php endif; ?>
                    
                    <form method="post" action="<?= route_url('members/update') ?>" class="needs-validation" novalidate>
                        <input type="hidden" name="id" value="<?= htmlspecialchars($member['id']) ?>">
                        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="name" class="form-label">Nama Lengkap *</label>
                                    <input type="text" class="form-control" id="name" name="name" 
                                           value="<?= htmlspecialchars($member['name']) ?>" required>
                                    <div class="invalid-feedback">Nama wajib diisi.</div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="nik" class="form-label">No. KTP *</label>
                                    <input type="text" class="form-control" id="nik" name="nik" 
                                           value="<?= htmlspecialchars($member['nik']) ?>" required
                                           pattern="[0-9]{16}" maxlength="16">
                                    <div class="invalid-feedback">NIK harus 16 digit angka.</div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="phone" class="form-label">No. Telepon *</label>
                                    <input type="tel" class="form-control" id="phone" name="phone" 
                                           value="<?= htmlspecialchars($member['phone']) ?>" required
                                           pattern="[0-9]{10,13}">
                                    <div class="invalid-feedback">Format telepon tidak valid.</div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="email" class="form-label">Email</label>
                                    <input type="email" class="form-control" id="email" name="email" 
                                           value="<?= htmlspecialchars($member['email'] ?? '') ?>">
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="address" class="form-label">Alamat Lengkap *</label>
                                    <textarea class="form-control" id="address" name="address" rows="3" required><?= htmlspecialchars($member['address']) ?></textarea>
                                    <div class="invalid-feedback">Alamat wajib diisi.</div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="occupation" class="form-label">Pekerjaan</label>
                                    <input type="text" class="form-control" id="occupation" name="occupation" 
                                           value="<?= htmlspecialchars($member['occupation'] ?? '') ?>">
                                </div>
                                
                                <div class="mb-3">
                                    <label for="monthly_income" class="form-label">Penghasilan Bulanan</label>
                                    <input type="number" class="form-control" id="monthly_income" name="monthly_income" 
                                           value="<?= htmlspecialchars($member['monthly_income'] ?? 0) ?>" min="0">
                                </div>
                                
                                <div class="mb-3">
                                    <label for="status" class="form-label">Status</label>
                                    <select class="form-select" id="status" name="status">
                                        <option value="active" <?= $member['status'] === 'active' ? 'selected' : '' ?>>Aktif</option>
                                        <option value="inactive" <?= $member['status'] === 'inactive' ? 'selected' : '' ?>>Tidak Aktif</option>
                                        <option value="pending" <?= $member['status'] === 'pending' ? 'selected' : '' ?>>Menunggu Verifikasi</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="emergency_contact_name" class="form-label">Nama Kontak Darurat</label>
                                    <input type="text" class="form-control" id="emergency_contact_name" name="emergency_contact_name" 
                                           value="<?= htmlspecialchars($member['emergency_contact_name'] ?? '') ?>">
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="emergency_contact_phone" class="form-label">Telepon Kontak Darurat</label>
                                    <input type="tel" class="form-control" id="emergency_contact_phone" name="emergency_contact_phone" 
                                           value="<?= htmlspecialchars($member['emergency_contact_phone'] ?? '') ?>">
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="latitude" class="form-label">Latitude</label>
                                    <input type="text" class="form-control" id="latitude" name="latitude" 
                                           value="<?= htmlspecialchars($member['latitude'] ?? '') ?>" readonly>
                                    <small class="text-muted">Koordinat GPS akan diisi otomatis</small>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="longitude" class="form-label">Longitude</label>
                                    <input type="text" class="form-control" id="longitude" name="longitude" 
                                           value="<?= htmlspecialchars($member['longitude'] ?? '') ?>" readonly>
                                    <small class="text-muted">Koordinat GPS akan diisi otomatis</small>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="verification_status" class="form-label">Status Verifikasi</label>
                                    <select class="form-select" id="verification_status" name="verification_status">
                                        <option value="pending" <?= $member['verification_status'] === 'pending' ? 'selected' : '' ?>>Menunggu Verifikasi</option>
                                        <option value="verified" <?= $member['verification_status'] === 'verified' ? 'selected' : '' ?>>Terverifikasi</option>
                                        <option value="rejected" <?= $member['verification_status'] === 'rejected' ? 'selected' : '' ?>>Ditolak</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="verified_at" class="form-label">Tanggal Verifikasi</label>
                                    <input type="datetime-local" class="form-control" id="verified_at" name="verified_at" 
                                           value="<?= $member['verified_at'] ? date('Y-m-d\TH:i', strtotime($member['verified_at'])) : '' ?>">
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <h5>Dokumen KTP</h5>
                                <?php if ($member['ktp_photo_path']): ?>
                                    <img src="<?= asset_url('uploads/' . $member['ktp_photo_path']) ?>" 
                                         class="img-thumbnail mb-2" style="max-width: 200px;">
                                <?php endif; ?>
                            </div>
                            
                            <div class="col-md-6">
                                <h5>Dokumen KK</h5>
                                <?php if ($member['kk_photo_path']): ?>
                                    <img src="<?= asset_url('uploads/' . $member['kk_photo_path']) ?>" 
                                         class="img-thumbnail mb-2" style="max-width: 200px;">
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-12">
                                <div class="d-flex justify-content-between">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save"></i> Simpan Perubahan
                                    </button>
                                    <a href="<?= route_url('members') ?>" class="btn btn-secondary">
                                        <i class="fas fa-times"></i> Batal
                                    </a>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Form validation
(function() {
    'use strict'
    const forms = document.querySelectorAll('.needs-validation')
    Array.from(forms).forEach(form => {
        form.addEventListener('submit', event => {
            if (!form.checkValidity()) {
                event.preventDefault()
                event.stopPropagation()
            }
        })
    })
    
    // Auto-get location
    function getLocation() {
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(
                function(position) {
                    document.getElementById('latitude').value = position.coords.latitude;
                    document.getElementById('longitude').value = position.coords.longitude;
                },
                function(error) {
                    console.log('Error getting location:', error);
                }
            );
        }
    }
    
    // Get location on page load
    getLocation();
})();
</script>

<?php include view_path('layout/footer'); ?>
