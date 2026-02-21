<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pendaftaran Koperasi - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .registration-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 3rem 0;
            margin-bottom: 2rem;
        }
        .registration-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .form-section {
            border-bottom: 1px solid #e9ecef;
            padding: 2rem;
        }
        .form-section:last-child {
            border-bottom: none;
        }
        .section-title {
            color: #495057;
            font-weight: 600;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
        }
        .section-title i {
            margin-right: 0.5rem;
            color: #667eea;
        }
        .document-upload {
            border: 2px dashed #dee2e6;
            border-radius: 10px;
            padding: 2rem;
            text-align: center;
            transition: all 0.3s ease;
            background: #f8f9fa;
        }
        .document-upload:hover {
            border-color: #667eea;
            background: #f0f2ff;
        }
        .document-upload.dragover {
            border-color: #667eea;
            background: #e7e9ff;
        }
        .file-list {
            margin-top: 1rem;
        }
        .file-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0.5rem;
            background: white;
            border-radius: 5px;
            margin-bottom: 0.5rem;
        }
        .progress-indicator {
            display: flex;
            justify-content: space-between;
            margin-bottom: 2rem;
        }
        .progress-step {
            flex: 1;
            text-align: center;
            position: relative;
        }
        .progress-step:not(:last-child)::after {
            content: '';
            position: absolute;
            top: 15px;
            left: 50%;
            width: 100%;
            height: 2px;
            background: #dee2e6;
            z-index: 1;
        }
        .progress-step.completed:not(:last-child)::after {
            background: #28a745;
        }
        .progress-circle {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background: #dee2e6;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 0.5rem;
            font-size: 0.8rem;
            position: relative;
            z-index: 2;
        }
        .progress-step.completed .progress-circle {
            background: #28a745;
        }
        .progress-step.active .progress-circle {
            background: #667eea;
        }
    </style>
</head>
<body class="bg-light">
    <!-- Header -->
    <div class="registration-header">
        <div class="container">
            <div class="row">
                <div class="col-lg-8 mx-auto text-center">
                    <h1 class="display-4 fw-bold mb-3">
                        <i class="fas fa-building me-3"></i>Pendaftaran Koperasi
                    </h1>
                    <p class="lead mb-0">
                        Bergabunglah dengan <?php echo APP_NAME; ?> dan kelola koperasi Anda secara digital
                    </p>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <!-- Progress Indicator -->
        <div class="progress-indicator">
            <div class="progress-step completed">
                <div class="progress-circle">1</div>
                <div class="step-label">Informasi Dasar</div>
            </div>
            <div class="progress-step active">
                <div class="progress-circle">2</div>
                <div class="step-label">Dokumen & Verifikasi</div>
            </div>
            <div class="progress-step">
                <div class="progress-circle">3</div>
                <div class="step-label">Review & Submit</div>
            </div>
        </div>

        <!-- Registration Form -->
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="registration-card">
                    <form id="registrationForm" enctype="multipart/form-data">
                        <!-- Basic Information -->
                        <div class="form-section">
                            <h3 class="section-title">
                                <i class="fas fa-info-circle"></i>Informasi Koperasi
                            </h3>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="cooperative_name" class="form-label">Nama Koperasi *</label>
                                    <input type="text" class="form-control" id="cooperative_name" name="cooperative_name" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="legal_type" class="form-label">Jenis Koperasi *</label>
                                    <select class="form-select" id="legal_type" name="legal_type" required>
                                        <option value="">Pilih jenis koperasi</option>
                                        <option value="koperasi_simpan_pinjam">Koperasi Simpan Pinjam</option>
                                        <option value="koperasi_serba_usaha">Koperasi Serba Usaha</option>
                                        <option value="koperasi_konsumen">Koperasi Konsumen</option>
                                        <option value="koperasi_produsen">Koperasi Produsen</option>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="registration_number" class="form-label">Nomor Registrasi</label>
                                    <input type="text" class="form-control" id="registration_number" name="registration_number">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="established_date" class="form-label">Tanggal Berdiri</label>
                                    <input type="date" class="form-control" id="established_date" name="established_date">
                                </div>
                                <div class="col-12 mb-3">
                                    <label for="description" class="form-label">Deskripsi Koperasi</label>
                                    <textarea class="form-control" id="description" name="description" rows="3" placeholder="Jelaskan tentang koperasi Anda..."></textarea>
                                </div>
                            </div>
                        </div>

                        <!-- Address Information -->
                        <div class="form-section">
                            <h3 class="section-title">
                                <i class="fas fa-map-marker-alt"></i>Alamat & Kontak
                            </h3>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="province" class="form-label">Provinsi *</label>
                                    <input type="text" class="form-control" id="province" name="province" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="city" class="form-label">Kota/Kabupaten *</label>
                                    <input type="text" class="form-control" id="city" name="city" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="postal_code" class="form-label">Kode Pos *</label>
                                    <input type="text" class="form-control" id="postal_code" name="postal_code" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="phone" class="form-label">Nomor Telepon *</label>
                                    <input type="tel" class="form-control" id="phone" name="phone" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="email" class="form-label">Email *</label>
                                    <input type="email" class="form-control" id="email" name="email" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="website" class="form-label">Website</label>
                                    <input type="url" class="form-control" id="website" name="website" placeholder="https://">
                                </div>
                                <div class="col-12 mb-3">
                                    <label for="address" class="form-label">Alamat Lengkap *</label>
                                    <textarea class="form-control" id="address" name="address" rows="3" required></textarea>
                                </div>
                            </div>
                        </div>

                        <!-- Leadership Information -->
                        <div class="form-section">
                            <h3 class="section-title">
                                <i class="fas fa-users"></i>Pimpinan Koperasi
                            </h3>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="chairman_name" class="form-label">Nama Ketua *</label>
                                    <input type="text" class="form-control" id="chairman_name" name="chairman_name" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="chairman_phone" class="form-label">Telepon Ketua *</label>
                                    <input type="tel" class="form-control" id="chairman_phone" name="chairman_phone" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="chairman_email" class="form-label">Email Ketua</label>
                                    <input type="email" class="form-control" id="chairman_email" name="chairman_email">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="manager_name" class="form-label">Nama Manajer *</label>
                                    <input type="text" class="form-control" id="manager_name" name="manager_name" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="manager_phone" class="form-label">Telepon Manajer *</label>
                                    <input type="tel" class="form-control" id="manager_phone" name="manager_phone" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="manager_email" class="form-label">Email Manajer</label>
                                    <input type="email" class="form-control" id="manager_email" name="manager_email">
                                </div>
                            </div>
                        </div>

                        <!-- Statistics -->
                        <div class="form-section">
                            <h3 class="section-title">
                                <i class="fas fa-chart-bar"></i>Data & Statistik
                            </h3>
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label for="total_members" class="form-label">Jumlah Anggota</label>
                                    <input type="number" class="form-control" id="total_members" name="total_members" min="1">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="total_assets" class="form-label">Total Aset (Rp)</label>
                                    <input type="number" class="form-control" id="total_assets" name="total_assets" min="0">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="subscription_plan" class="form-label">Paket Berlangganan *</label>
                                    <select class="form-select" id="subscription_plan" name="subscription_plan" required>
                                        <option value="starter">Starter (Rp 500rb/bulan)</option>
                                        <option value="professional">Professional (Rp 1.5jt/bulan)</option>
                                        <option value="enterprise">Enterprise (Rp 3jt/bulan)</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Document Upload -->
                        <div class="form-section">
                            <h3 class="section-title">
                                <i class="fas fa-file-upload"></i>Upload Dokumen
                            </h3>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                <strong>Persyaratan Dokumen:</strong> Semua dokumen wajib diupload dalam format PDF atau gambar (maksimal 10MB per file)
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-4">
                                    <div class="document-upload" onclick="document.getElementById('akta_pendirian').click()">
                                        <i class="fas fa-file-contract fa-3x text-primary mb-3"></i>
                                        <h6>AKTA Pendirian Koperasi</h6>
                                        <p class="text-muted small">Upload salinan akta pendirian</p>
                                        <input type="file" id="akta_pendirian" name="akta_pendirian" accept=".pdf,.jpg,.jpeg,.png" style="display: none;">
                                    </div>
                                    <div id="akta_pendirian_list" class="file-list"></div>
                                </div>

                                <div class="col-md-6 mb-4">
                                    <div class="document-upload" onclick="document.getElementById('sk_menkumham').click()">
                                        <i class="fas fa-stamp fa-3x text-success mb-3"></i>
                                        <h6>SK Menkumham</h6>
                                        <p class="text-muted small">Upload surat keputusan pengesahan</p>
                                        <input type="file" id="sk_menkumham" name="sk_menkumham" accept=".pdf,.jpg,.jpeg,.png" style="display: none;">
                                    </div>
                                    <div id="sk_menkumham_list" class="file-list"></div>
                                </div>

                                <div class="col-md-6 mb-4">
                                    <div class="document-upload" onclick="document.getElementById('anggaran_dasar').click()">
                                        <i class="fas fa-book fa-3x text-warning mb-3"></i>
                                        <h6>Anggaran Dasar & ART</h6>
                                        <p class="text-muted small">Upload AD/ART koperasi</p>
                                        <input type="file" id="anggaran_dasar" name="anggaran_dasar" accept=".pdf,.jpg,.jpeg,.png" style="display: none;">
                                    </div>
                                    <div id="anggaran_dasar_list" class="file-list"></div>
                                </div>

                                <div class="col-md-6 mb-4">
                                    <div class="document-upload" onclick="document.getElementById('ktp_ketua').click()">
                                        <i class="fas fa-id-card fa-3x text-info mb-3"></i>
                                        <h6>KTP Ketua</h6>
                                        <p class="text-muted small">Upload KTP ketua koperasi</p>
                                        <input type="file" id="ktp_ketua" name="ktp_ketua" accept=".pdf,.jpg,.jpeg,.png" style="display: none;">
                                    </div>
                                    <div id="ktp_ketua_list" class="file-list"></div>
                                </div>

                                <div class="col-md-6 mb-4">
                                    <div class="document-upload" onclick="document.getElementById('ktp_manajer').click()">
                                        <i class="fas fa-id-card fa-3x text-secondary mb-3"></i>
                                        <h6>KTP Manajer</h6>
                                        <p class="text-muted small">Upload KTP manajer koperasi</p>
                                        <input type="file" id="ktp_manajer" name="ktp_manajer" accept=".pdf,.jpg,.jpeg,.png" style="display: none;">
                                    </div>
                                    <div id="ktp_manajer_list" class="file-list"></div>
                                </div>
                            </div>
                        </div>

                        <!-- Terms and Submit -->
                        <div class="form-section">
                            <div class="row">
                                <div class="col-12">
                                    <div class="form-check mb-4">
                                        <input class="form-check-input" type="checkbox" id="terms_agreement" required>
                                        <label class="form-check-label" for="terms_agreement">
                                            Saya menyetujui <a href="#" target="_blank">syarat dan ketentuan</a> penggunaan <?php echo APP_NAME; ?> dan menyatakan bahwa semua informasi yang saya berikan adalah benar dan akurat.
                                        </label>
                                    </div>

                                    <div class="d-flex justify-content-between">
                                        <button type="button" class="btn btn-secondary" onclick="saveDraft()">
                                            <i class="fas fa-save me-2"></i>Simpan Draft
                                        </button>
                                        <button type="submit" class="btn btn-primary btn-lg">
                                            <i class="fas fa-paper-plane me-2"></i>Ajukan Pendaftaran
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let draftId = null;

        // Handle file uploads
        document.querySelectorAll('input[type="file"]').forEach(input => {
            input.addEventListener('change', function(e) {
                handleFileSelect(e.target);
            });
        });

        function handleFileSelect(input) {
            const file = input.files[0];
            if (!file) return;

            const fileList = document.getElementById(input.id + '_list');

            // Validate file size (10MB)
            if (file.size > 10 * 1024 * 1024) {
                alert('File terlalu besar. Maksimal 10MB.');
                input.value = '';
                return;
            }

            // Validate file type
            const allowedTypes = ['application/pdf', 'image/jpeg', 'image/png'];
            if (!allowedTypes.includes(file.type)) {
                alert('Format file tidak didukung. Gunakan PDF, JPG, atau PNG.');
                input.value = '';
                return;
            }

            // Show file in list
            fileList.innerHTML = `
                <div class="file-item">
                    <div>
                        <i class="fas fa-file me-2"></i>
                        ${file.name}
                        <small class="text-muted">(${(file.size / 1024 / 1024).toFixed(2)} MB)</small>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeFile('${input.id}')">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            `;
        }

        function removeFile(inputId) {
            document.getElementById(inputId).value = '';
            document.getElementById(inputId + '_list').innerHTML = '';
        }

        // Save draft
        function saveDraft() {
            const formData = new FormData(document.getElementById('registrationForm'));

            fetch('/cooperative/save-draft', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    draftId = data.registration_id;
                    alert('Draft berhasil disimpan!');
                } else {
                    alert('Error: ' + data.error);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Terjadi kesalahan saat menyimpan draft.');
            });
        }

        // Handle form submission
        document.getElementById('registrationForm').addEventListener('submit', function(e) {
            e.preventDefault();

            const formData = new FormData(this);

            fetch('/cooperative/submit', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    window.location.href = '/cooperative/register?success=1';
                } else {
                    alert('Error: ' + (data.errors ? data.errors.join('\n') : data.error));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Terjadi kesalahan saat mengirim pendaftaran.');
            });
        });

        // Check for success parameter
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('success') === '1') {
            alert('Pendaftaran berhasil diajukan! Kami akan memverifikasi dalam 3-5 hari kerja.');
        }
    </script>
</body>
</html>
