<?php
ob_start();
?>
<div class="d-flex justify-content-between align-items-center mb-4">
  <h5>Surat-Surat Koperasi</h5>
</div>

<div class="row g-4">
  <div class="col-md-6 col-lg-4">
    <div class="card h-100 shadow-sm">
      <div class="card-body">
        <div class="d-flex align-items-center mb-3">
          <div class="rounded-circle bg-primary bg-opacity-10 p-3 me-3">
            <i class="bi bi-file-earmark-text text-primary fs-4"></i>
          </div>
          <div>
            <h1 class="h3 mb-4">Generator Surat <?php echo APP_NAME; ?></h1>
            <small class="text-muted">Format surat lamaran karyawan</small>
          </div>
        </div>
        <p class="card-text small">Surat lamaran kerja untuk calon karyawan KSP Lam Gabe Jaya dengan kelengkapan dokumen yang diperlukan.</p>
        <a href="<?= route_url('surat/lamaran-kerja') ?>" class="btn btn-primary btn-sm">
          <i class="bi bi-download"></i> Unduh PDF
        </a>
      </div>
    </div>
  </div>

  <div class="col-md-6 col-lg-4">
    <div class="card h-100 shadow-sm">
      <div class="card-body">
        <div class="d-flex align-items-center mb-3">
          <div class="rounded-circle bg-success bg-opacity-10 p-3 me-3">
            <i class="bi bi-people-fill text-success fs-4"></i>
          </div>
          <div>
            <h6 class="card-title mb-1">Permohonan Anggota</h6>
            <small class="text-muted">Formulir pendaftaran anggota baru</small>
          </div>
        </div>
        <p class="card-text small">Surat permohonan menjadi anggota koperasi beserta persyaratan dan kewajiban anggota.</p>
        <a href="<?= route_url('surat/permohonan-anggota') ?>" class="btn btn-success btn-sm">
          <i class="bi bi-download"></i> Unduh PDF
        </a>
      </div>
    </div>
  </div>

  <div class="col-md-6 col-lg-4">
    <div class="card h-100 shadow-sm">
      <div class="card-body">
        <div class="d-flex align-items-center mb-3">
          <div class="rounded-circle bg-info bg-opacity-10 p-3 me-3">
            <i class="bi bi-list-ul text-info fs-4"></i>
          </div>
          <div>
            <h6 class="card-title mb-1">Daftar Sah Anggota</h6>
            <small class="text-muted">Daftar resmi anggota koperasi</small>
          </div>
        </div>
        <p class="card-text small">Format daftar sah anggota koperasi yang dapat digunakan untuk administrasi internal.</p>
        <a href="<?= route_url('surat/daftar-sah') ?>" class="btn btn-info btn-sm">
          <i class="bi bi-download"></i> Unduh PDF
        </a>
      </div>
    </div>
  </div>

  <div class="col-md-6 col-lg-4">
    <div class="card h-100 shadow-sm">
      <div class="card-body">
        <div class="d-flex align-items-center mb-3">
          <div class="rounded-circle bg-warning bg-opacity-10 p-3 me-3">
            <i class="bi bi-cash-stack text-warning fs-4"></i>
          </div>
          <div>
            <h6 class="card-title mb-1">Permohonan Pinjaman</h6>
            <small class="text-muted">Formulir pengajuan pinjaman</small>
          </div>
        </div>
        <p class="card-text small">Surat permohonan pinjaman dana dengan kelengkapan dokumen dan persyaratan yang diperlukan.</p>
        <a href="<?= route_url('surat/permohonan-pinjaman') ?>" class="btn btn-warning btn-sm">
          <i class="bi bi-download"></i> Unduh PDF
        </a>
      </div>
    </div>
  </div>

  <div class="col-md-6 col-lg-4">
    <div class="card h-100 shadow-sm">
      <div class="card-body">
        <div class="d-flex align-items-center mb-3">
          <div class="rounded-circle bg-danger bg-opacity-10 p-3 me-3">
            <i class="bi bi-handshake text-danger fs-4"></i>
          </div>
          <div>
            <h6 class="card-title mb-1">Surat Kesepakatan Bersama</h6>
            <small class="text-muted">Perjanjian pinjaman nasabah</small>
          </div>
        </div>
        <p class="card-text small">Surat kesepakatan bersama (SKB) antara nasabah dan koperasi untuk perjanjian pinjaman.</p>
        <a href="<?= route_url('surat/skb') ?>" class="btn btn-danger btn-sm">
          <i class="bi bi-download"></i> Unduh PDF
        </a>
      </div>
    </div>
  </div>

  <div class="col-md-6 col-lg-4">
    <div class="card h-100 shadow-sm">
      <div class="card-body">
        <div class="d-flex align-items-center mb-3">
          <div class="rounded-circle bg-secondary bg-opacity-10 p-3 me-3">
            <i class="bi bi-info-circle text-secondary fs-4"></i>
          </div>
          <div>
            <h6 class="card-title mb-1">Informasi</h6>
            <small class="text-muted">Persyaratan dokumen</small>
          </div>
        </div>
        <p class="card-text small">
            <strong>Persyaratan Berkas Lamaran Karyawan:</strong><br>
            • Fotokopi KTP<br>
            • CV/Daftar Riwayat Hidup<br>
            • Ijazah & Transkrip<br>
            • Pas foto 3x4<br>
            • SKCK (jika diperlukan)<br>
            • Surat Keterangan Sehat<br>
            • NPWP (jika ada)
        </p>
      </div>
    </div>
  </div>
</div>

<div class="alert alert-info mt-4">
  <i class="bi bi-info-circle me-2"></i>
  <strong>Catatan:</strong> Semua surat dapat diunduh dalam format PDF dan dicetak sesuai kebutuhan. 
  Pastikan untuk mengisi data yang diperlukan dengan lengkap dan benar.
</div>

<?php
$content = ob_get_clean();
include view_path('layout_admin');
