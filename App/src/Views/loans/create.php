<?php
ob_start();
?>
<div class="row justify-content-center">
  <div class="col-12 col-lg-8">
    <div class="card shadow-sm">
      <div class="card-body">
        <h5 class="card-title mb-4">Pengajuan Pinjaman</h5>
        <form method="post" action="<?= route_url('loans/store') ?>" enctype="multipart/form-data" class="needs-validation" novalidate>
          <div class="mb-3">
            <label class="form-label">Anggota/Nasabah</label>
            <select name="member_id" class="form-select" required>
              <option value="">-- Pilih --</option>
              <?php foreach ($members as $m): ?>
                <option value="<?= $m['id'] ?>"><?= htmlspecialchars($m['name']) ?> (<?= htmlspecialchars($m['nik'] ?? '-') ?>)</option>
              <?php endforeach; ?>
            </select>
            <div class="invalid-feedback">Wajib dipilih.</div>
          </div>
          <div class="mb-3">
            <label class="form-label">Produk Pinjaman</label>
            <select name="product_id" class="form-select" required>
              <option value="">-- Pilih --</option>
              <?php foreach ($products as $p): ?>
                <option value="<?= $p['id'] ?>" data-rate="<?= $p['rate'] ?>" data-tenor="<?= $p['tenor_months'] ?>" data-fee="<?= $p['fee'] ?>">
                  <?= htmlspecialchars($p['name']) ?> (<?= $p['rate'] ?>% / <?= $p['tenor_months'] ?> bln)
                </option>
              <?php endforeach; ?>
            </select>
            <div class="invalid-feedback">Wajib dipilih.</div>
          </div>
          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label">Jumlah Pinjaman (Rp)</label>
              <input type="text" name="amount" class="form-control" placeholder="0" required>
              <div class="invalid-feedback">Wajib diisi.</div>
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label">Jangka Waktu (bulan)</label>
              <input type="number" name="tenor_months" class="form-control" min="1" required>
              <div class="invalid-feedback">Wajib diisi.</div>
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label">Tujuan Penggunaan</label>
            <textarea name="purpose" class="form-control" rows="3" required></textarea>
            <div class="invalid-feedback">Wajib diisi.</div>
          </div>
          <div class="mb-4">
            <h6>Unggah Dokumen (opsional)</h6>
            <div class="row g-2">
              <div class="col-md-6">
                <label class="form-label">KTP</label>
                <input type="file" name="docs[ktp]" class="form-control" accept="image/*,.pdf">
              </div>
              <div class="col-md-6">
                <label class="form-label">KK</label>
                <input type="file" name="docs[kk]" class="form-control" accept="image/*,.pdf">
              </div>
              <div class="col-md-6">
                <label class="form-label">Slip Gaji/Usaha</label>
                <input type="file" name="docs[slip_gaji]" class="form-control" accept="image/*,.pdf">
              </div>
              <div class="col-md-6">
                <label class="form-label">Bukti Usaha</label>
                <input type="file" name="docs[bukti_usaha]" class="form-control" accept="image/*,.pdf">
              </div>
              <div class="col-md-6">
                <label class="form-label">Agunan (jika ada)</label>
                <input type="file" name="docs[agunan]" class="form-control" accept="image/*,.pdf">
              </div>
            </div>
          </div>
          <button type="submit" class="btn btn-primary w-100">Ajukan Pinjaman</button>
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

// Auto-fill tenor/fee when product selected
document.querySelector('select[name=product_id]').addEventListener('change', function () {
  const opt = this.options[this.selectedIndex];
  if (opt.dataset.tenor) {
    document.querySelector('input[name=tenor_months]').value = opt.dataset.tenor;
  }
  if (opt.dataset.fee) {
    // Bisa tampilkan info fee di UI jika perlu
  }
});

// Format currency input
document.querySelector('input[name=amount]').addEventListener('input', function () {
  let val = this.value.replace(/\D/g, '');
  if (val) {
    this.value = IDHelper.formatNumber(val, 0);
  }
});
</script>
<?php
$content = ob_get_clean();
include view_path('layout_admin');
