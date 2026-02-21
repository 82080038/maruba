<?php
ob_start();
?>
<div class="row justify-content-center">
  <div class="col-12 col-lg-8">
    <div class="card shadow-sm">
      <div class="card-body">
        <h5 class="card-title mb-4">Catat Pembayaran</h5>
        <form method="post" action="<?= route_url('repayments/store') ?>" enctype="multipart/form-data" class="needs-validation" novalidate>
          <?= csrf_field(); ?>
          <div class="mb-3">
            <label class="form-label">Tagihan yang akan dibayar</label>
            <select name="repayment_id" class="form-select" required>
              <option value="">-- Pilih --</option>
              <?php foreach ($due as $d): ?>
                <option value="<?= $d['id'] ?>"><?= htmlspecialchars($d['member_name']) ?> - <?= date('d/m/Y', strtotime($d['due_date'])) ?> - Tagih: <?= format_currency($d['amount_due']) ?></option>
              <?php endforeach; ?>
            </select>
            <div class="invalid-feedback">Wajib dipilih.</div>
          </div>
          <div class="mb-3">
            <label class="form-label">Jumlah Dibayar</label>
            <input type="text" name="amount_paid" class="form-control" required>
            <div class="invalid-feedback">Wajib diisi.</div>
          </div>
          <div class="mb-3">
            <label class="form-label">Metode</label>
            <select name="method" class="form-select" required>
              <option value="tunai">Tunai</option>
              <option value="transfer">Transfer</option>
              <option value="debit">Debit</option>
            </select>
            <div class="invalid-feedback">Wajib dipilih.</div>
          </div>
          <div class="mb-3">
            <label class="form-label">Bukti Pembayaran (opsional)</label>
            <input type="file" name="proof" class="form-control" accept="image/*,.pdf">
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

document.querySelector('input[name=amount_paid]').addEventListener('input', function () {
  let val = this.value.replace(/\D/g, '');
  if (val) this.value = IDHelper.formatNumber(val, 0);
});
</script>
<?php
$content = ob_get_clean();
include view_path('layout_admin');
