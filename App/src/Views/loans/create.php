<?php
ob_start();
?>
<div class="row justify-content-center">
  <div class="col-12 col-lg-8">
    <div class="card shadow-sm">
      <div class="card-body">
        <h5 class="card-title mb-4">Pengajuan Pinjaman</h5>
        <form id="loanCreateForm" method="post" action="<?= route_url('index.php/loans/store') ?>" enctype="multipart/form-data" class="needs-validation" novalidate>
          <?= csrf_field(); ?>
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
              <input id="loanAmount" type="text" name="amount" class="form-control" placeholder="0" required>
              <div id="amountError" class="invalid-feedback">Wajib diisi.</div>
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label">Jangka Waktu (bulan)</label>
              <input id="loanTenor" type="number" name="tenor_months" class="form-control" min="1" required>
              <div id="tenorError" class="invalid-feedback">Wajib diisi.</div>
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
          <button id="loanSubmitBtn" type="submit" class="btn btn-primary w-100">Ajukan Pinjaman</button>
        </form>
      </div>
    </div>
  </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= asset_url('assets/js/helpers-id.js') ?>"></script>
<script src="<?= asset_url('assets/js/dom-helpers.js') ?>"></script>
<script>
// Ensure jQuery is loaded before running scripts
(function($) {
    'use strict';
    
    // Form validation with jQuery
    $('#loanCreateForm').on('submit', function(event) {
        event.preventDefault();
        
        // Clear previous errors
        $('.invalid-feedback').hide();
        $('.form-control').removeClass('is-invalid');
        
        let isValid = true;
        
        // Validate loan amount
        if (!$('#loanAmount').val().trim() || parseFloat($('#loanAmount').val()) <= 0) {
            $('#loanAmount').addClass('is-invalid');
            $('#amountError').show();
            isValid = false;
        }
        
        // Validate tenor
        if (!$('#loanTenor').val().trim() || parseInt($('#loanTenor').val()) <= 0) {
            $('#loanTenor').addClass('is-invalid');
            $('#tenorError').show();
            isValid = false;
        }
        
        if (isValid) {
            // Show loading state
            MarubaDOM.showButtonLoading('loanSubmitBtn', 'Menyimpan...');
            
            // Submit form
            this.submit();
        }
    });
    
    // Format currency input
    MarubaDOM.formatCurrencyInput('loanAmount');
    
})(jQuery);
</script>
<?php
$content = ob_get_clean();
include view_path('layout_dashboard');
