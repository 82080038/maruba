<?php
ob_start();
?>
<div class="row justify-content-center">
  <div class="col-12 col-lg-8">
    <div class="card shadow-sm">
      <div class="card-body">
        <h5 class="card-title mb-4">Tambah Pengguna</h5>
        <form method="post" action="<?= route_url('users/store') ?>" class="needs-validation" novalidate>
          <div class="mb-3">
            <label class="form-label">Nama</label>
            <input type="text" name="name" class="form-control" required>
            <div class="invalid-feedback">Wajib diisi.</div>
          </div>
          <div class="mb-3">
            <label class="form-label">Nama Pengguna</label>
            <input type="text" name="username" class="form-control" required>
            <div class="invalid-feedback">Wajib diisi.</div>
          </div>
          <div class="mb-3">
            <label class="form-label">Password</label>
            <input type="password" name="password" class="form-control" required>
            <div class="invalid-feedback">Wajib diisi.</div>
          </div>
          <div class="mb-3">
            <label class="form-label">Role</label>
            <select name="role_id" class="form-select" required>
              <option value="">-- Pilih --</option>
              <?php foreach ($roles as $r): ?>
                <option value="<?= $r['id'] ?>"><?= htmlspecialchars($r['name']) ?></option>
              <?php endforeach; ?>
            </select>
            <div class="invalid-feedback">Wajib dipilih.</div>
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
include view_path('layout_dashboard');
