<?php
ob_start();
?>
<div class="row justify-content-center">
  <div class="col-12 col-lg-8">
    <div class="card shadow-sm">
      <div class="card-body">
        <h5 class="card-title mb-4">Tambah Anggota</h5>
        <form method="post" action="<?= route_url('members/store') ?>" class="needs-validation" novalidate>
          <div class="mb-3">
            <label class="form-label">Nama</label>
            <input type="text" name="name" class="form-control" required>
            <div class="invalid-feedback">Wajib diisi.</div>
          </div>
          <div class="mb-3">
            <label class="form-label">NIK</label>
            <input type="text" name="nik" class="form-control" required>
            <div class="invalid-feedback">Wajib diisi.</div>
          </div>
          <div class="mb-3">
            <label class="form-label">Telepon</label>
            <input type="text" name="phone" class="form-control" required>
            <div class="invalid-feedback">Wajib diisi.</div>
          </div>
          <div class="mb-3">
            <label class="form-label">Alamat</label>
            <textarea name="address" class="form-control" rows="3" required></textarea>
            <div class="invalid-feedback">Wajib diisi.</div>
          </div>
          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label">Latitude</label>
              <input type="number" step="any" name="lat" class="form-control">
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label">Longitude</label>
              <input type="number" step="any" name="lng" class="form-control">
            </div>
          </div>
          <button type="submit" class="btn btn-primary w-100">Simpan</button>
        </form>
      </div>
    </div>
  </div>
</div>
<script>
(() => {
  'use strict'
  const forms = document.querySelectorAll('.needs-validation')
  Array.from(forms).forEach(form => {
    form.addEventListener('submit', event => {
      if (!form.checkValidity()) {
        event.preventDefault()
        event.stopPropagation()
      }
      form.classList.add('was-validated')
    }, false)
  })
})()
</script>
<?php
$content = ob_get_clean();
include view_path('layout_admin');
