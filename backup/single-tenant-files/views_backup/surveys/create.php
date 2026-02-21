<?php
ob_start();
?>
<div class="row justify-content-center">
  <div class="col-12 col-lg-8">
    <div class="card shadow-sm">
      <div class="card-body">
        <h5 class="card-title mb-4">Tambah Survei</h5>
        <form method="post" action="<?= route_url('surveys/store') ?>" class="needs-validation" novalidate>
          <div class="mb-3">
            <label class="form-label">Pinjaman (status draft/survey)</label>
            <select name="loan_id" class="form-select" required>
              <option value="">-- Pilih --</option>
              <?php foreach ($loans as $l): ?>
                <option value="<?= $l['id'] ?>"><?= htmlspecialchars($l['member_name']) ?> - <?= format_currency($l['amount']) ?></option>
              <?php endforeach; ?>
            </select>
            <div class="invalid-feedback">Wajib dipilih.</div>
          </div>
          <div class="mb-3">
            <label class="form-label">Hasil Survei</label>
            <textarea name="result" class="form-control" rows="4" required></textarea>
            <div class="invalid-feedback">Wajib diisi.</div>
          </div>
          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label">Skor (0-100)</label>
              <input type="number" name="score" class="form-control" min="0" max="100" required>
              <div class="invalid-feedback">Skor 0-100.</div>
            </div>
            <div class="col-md-3 mb-3">
              <label class="form-label">Latitude</label>
              <input type="number" step="any" name="lat" class="form-control">
            </div>
            <div class="col-md-3 mb-3">
              <label class="form-label">Longitude</label>
              <input type="number" step="any" name="lng" class="form-control">
            </div>
          </div>
          <div class="mb-3">
            <button type="button" class="btn btn-outline-secondary btn-sm" id="getLocation">Ambil Lokasi GPS</button>
            <div id="locationStatus" class="mt-2 text-muted small"></div>
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

// Geolocation
document.getElementById('getLocation')?.addEventListener('click', function () {
  const statusEl = document.getElementById('locationStatus');
  if (!navigator.geolocation) {
    statusEl.textContent = 'Geolocation tidak didukung browser ini.';
    return;
  }
  statusEl.textContent = 'Mengambil lokasi...';
  navigator.geolocation.getCurrentPosition(
    function (pos) {
      const lat = pos.coords.latitude;
      const lng = pos.coords.longitude;
      document.querySelector('input[name=lat]').value = lat;
      document.querySelector('input[name=lng]').value = lng;
      statusEl.textContent = `Lokasi diperoleh: ${lat.toFixed(7)}, ${lng.toFixed(7)}`;
    },
    function (err) {
      statusEl.textContent = 'Gagal mendapatkan lokasi: ' + err.message;
    },
    { enableHighAccuracy: true, timeout: 10000 }
  );
});
</script>
<?php
$content = ob_get_clean();
include view_path('layout_admin');
?>
