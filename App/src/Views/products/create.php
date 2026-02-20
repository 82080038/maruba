<?php
ob_start();
?>
<div class="row justify-content-center">
  <div class="col-12 col-lg-8">
    <div class="card shadow-sm">
      <div class="card-body">
        <h5 class="card-title mb-4">Tambah Produk</h5>
        <form method="post" action="<?= route_url('products/store') ?>" class="needs-validation" novalidate>
          <div class="mb-3">
            <label class="form-label">Nama Produk</label>
            <input type="text" name="name" class="form-control" required>
            <div class="invalid-feedback">Wajib diisi.</div>
          </div>
          <div class="mb-3">
            <label class="form-label">Tipe</label>
            <select name="type" class="form-select" required>
              <option value="loan">Pinjaman</option>
              <option value="savings">Simpanan</option>
            </select>
            <div class="invalid-feedback">Wajib dipilih.</div>
          </div>
          <div class="row">
            <div class="col-md-4 mb-3">
              <label class="form-label">Bunga (%)</label>
              <input type="number" step="0.01" name="rate" class="form-control" value="0">
            </div>
            <div class="col-md-4 mb-3">
              <label class="form-label">Tenor (bulan)</label>
              <input type="number" name="tenor_months" class="form-control" value="0">
            </div>
            <div class="col-md-4 mb-3">
              <label class="form-label">Biaya</label>
              <input type="text" name="fee" class="form-control" value="0">
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

document.querySelector('input[name=fee]').addEventListener('input', function () {
  let val = this.value.replace(/\D/g, '');
  if (val) this.value = IDHelper.formatNumber(val, 0);
});
</script>
<?php
$content = ob_get_clean();
include view_path('layout_admin');
