<?php
ob_start();
?>
<div class="row justify-content-center">
  <div class="col-12 col-lg-8">
    <div class="card shadow-sm">
      <div class="card-body">
        <h5 class="card-title mb-4">Pencairan Pinjaman</h5>
        <p><strong>Anggota:</strong> <?= htmlspecialchars($loan['member_name']) ?></p>
        <p><strong>Produk:</strong> <?= htmlspecialchars($loan['product_name']) ?></p>
        <p><strong>Pinjaman:</strong> <span class="currency text-end d-inline-block"><?= format_currency($loan['amount']) ?></span></p>
        <p><strong>Tenor:</strong> <?= $loan['tenor_months'] ?> bulan</p>
        <hr>
        <form method="post" action="<?= route_url('disbursement/store') ?>" enctype="multipart/form-data" class="needs-validation" novalidate>
          <input type="hidden" name="loan_id" value="<?= $loan['id'] ?>">
          <div class="mb-3">
            <label class="form-label">Tanggal Pencairan</label>
            <input type="date" name="disbursed_at" class="form-control" required>
            <div class="invalid-feedback">Wajib diisi.</div>
          </div>
          <div class="mb-3">
            <label class="form-label">Catatan (opsional)</label>
            <textarea name="notes" class="form-control" rows="3"></textarea>
          </div>
          <div class="mb-3">
            <label class="form-label">Bukti Pencairan (opsional)</label>
            <input type="file" name="proof" class="form-control" accept="image/*,.pdf">
          </div>
          <button type="submit" class="btn btn-primary w-100">Cairkan</button>
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
