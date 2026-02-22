<?php
ob_start();
?>
<div class="row justify-content-center">
  <div class="col-12 col-lg-10 col-xl-8">
    <div class="card shadow-sm mt-4">
      <div class="card-body p-4">
        <div class="text-center mb-4">
          <i class="bi bi-people-fill text-primary" style="font-size: 3rem;"></i>
          <h5 class="card-title mt-2">Daftar Pengguna</h5>
          <p class="text-muted small">Pilih koperasi berdasarkan provinsi/kota/kecamatan atau daftarkan koperasi baru</p>
        </div>
        <div id="register-config" data-coops-url="<?= route_url('/api/register/cooperatives') ?>"></div>
        <form method="post" action="<?= route_url('register') ?>" id="registerForm">
          <?= csrf_field(); ?>
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label" for="name">Nama Lengkap</label>
              <input type="text" class="form-control" id="name" name="name" required>
            </div>
            <div class="col-md-6">
              <label class="form-label" for="username">Username</label>
              <input type="text" class="form-control" id="username" name="username" required>
            </div>
            <div class="col-md-6">
              <label class="form-label" for="password">Kata Sandi</label>
              <input type="password" class="form-control" id="password" name="password" required>
            </div>
            <div class="col-md-6">
              <label class="form-label" for="password_confirm">Konfirmasi Kata Sandi</label>
              <input type="password" class="form-control" id="password_confirm" name="password_confirm" required>
            </div>
          </div>

          <hr class="my-4">
          <div class="row g-3">
            <div class="col-md-4">
              <label class="form-label" for="province">Provinsi</label>
              <select class="form-select" id="province">
                <option value="">Pilih Provinsi</option>
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-label" for="city">Kota/Kabupaten</label>
              <select class="form-select" id="city" disabled>
                <option value="">Pilih Kota/Kabupaten</option>
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-label" for="district">Kecamatan</label>
              <select class="form-select" id="district" disabled>
                <option value="">Pilih Kecamatan</option>
              </select>
            </div>
          </div>

          <div class="mt-3">
            <label class="form-label" for="cooperative_id">Pilih Koperasi</label>
            <select class="form-select" id="cooperative_id" name="cooperative_id" required>
              <option value="">Pilih koperasi</option>
              <option value="__new">Tidak ada di kecamatan saya - Daftarkan koperasi baru</option>
            </select>
            <div class="form-text">Jika koperasi belum terdaftar di lokasi Anda, pilih opsi daftar koperasi baru.</div>
          </div>

          <div class="d-grid gap-2 mt-4">
            <button type="submit" class="btn btn-primary">
              <i class="bi bi-person-plus me-2"></i> Daftar
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="<?= asset_url('js/register.js') ?>"></script>
<?php
$content = ob_get_clean();
include view_path('layout');
