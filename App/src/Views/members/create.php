<?php
ob_start();
?>
<div class="row justify-content-center" id="memberFormContainer">
  <div class="col-12 col-lg-8" id="memberFormColumn">
    <div class="card shadow-sm" id="memberFormCard">
      <div class="card-body" id="memberFormBody">
        <h5 class="card-title mb-4" id="memberFormTitle">Tambah Anggota</h5>
        <form method="post" action="<?= route_url('members/store') ?>" class="needs-validation" id="memberCreateForm" novalidate>
          <div class="mb-3" id="nameField">
            <label class="form-label" for="memberName">Nama</label>
            <input type="text" name="name" class="form-control" id="memberName" required>
            <div class="invalid-feedback" id="nameError">Wajib diisi.</div>
          </div>
          <div class="mb-3" id="nikField">
            <label class="form-label" for="memberNik">NIK</label>
            <input type="text" name="nik" class="form-control" id="memberNik" required>
            <div class="invalid-feedback" id="nikError">Wajib diisi.</div>
          </div>
          <div class="mb-3" id="phoneField">
            <label class="form-label" for="memberPhone">Telepon</label>
            <input type="text" name="phone" class="form-control" id="memberPhone" required>
            <div class="invalid-feedback" id="phoneError">Wajib diisi.</div>
          </div>
          <div class="mb-3" id="addressField">
            <label class="form-label" for="memberAddress">Alamat</label>
            <textarea name="address" class="form-control" id="memberAddress" rows="3" required></textarea>
            <div class="invalid-feedback" id="addressError">Wajib diisi.</div>
          </div>
          <div class="row" id="coordinatesRow">
            <div class="col-md-6 mb-3" id="latField">
              <label class="form-label" for="memberLat">Lintang</label>
              <input type="number" step="any" name="lat" class="form-control" id="memberLat">
            </div>
            <div class="col-md-6 mb-3" id="lngField">
              <label class="form-label" for="memberLng">Bujur</label>
              <input type="number" step="any" name="lng" class="form-control" id="memberLng">
            </div>
          </div>
          <button type="submit" class="btn btn-primary w-100" id="memberSubmitBtn">Simpan</button>
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
    $('#memberCreateForm').on('submit', function(event) {
        event.preventDefault();
        
        // Clear previous errors
        $('.invalid-feedback').hide();
        $('.form-control').removeClass('is-invalid');
        
        let isValid = true;
        
        // Validate name
        if (!$('#memberName').val().trim()) {
            $('#memberName').addClass('is-invalid');
            $('#nameError').show();
            isValid = false;
        }
        
        // Validate NIK
        if (!$('#memberNik').val().trim()) {
            $('#memberNik').addClass('is-invalid');
            $('#nikError').show();
            isValid = false;
        }
        
        // Validate phone
        if (!$('#memberPhone').val().trim()) {
            $('#memberPhone').addClass('is-invalid');
            $('#phoneError').show();
            isValid = false;
        }
        
        // Validate address
        if (!$('#memberAddress').val().trim()) {
            $('#memberAddress').addClass('is-invalid');
            $('#addressError').show();
            isValid = false;
        }
        
        if (isValid) {
            // Show loading state
            MarubaDOM.showButtonLoading('memberSubmitBtn', 'Menyimpan...');
            
            // Submit form
            this.submit();
        }
    });
    
    // Auto-format phone number
    MarubaDOM.formatPhoneInput('memberPhone');
    
    // Auto-format NIK (16 digits)
    MarubaDOM.formatNikInput('memberNik');
    
    // Get current location
    function getCurrentLocation() {
        if (navigator.geolocation) {
            $('#memberLat, #memberLng').prop('readonly', true).val('Mendapatkan lokasi...');
            
            navigator.geolocation.getCurrentPosition(
                function(position) {
                    $('#memberLat').val(position.coords.latitude);
                    $('#memberLng').val(position.coords.longitude);
                    $('#memberLat, #memberLng').prop('readonly', false);
                },
                function(error) {
                    $('#memberLat, #memberLng').val('').prop('readonly', false);
                    MarubaDOM.showAlert('Tidak dapat mendapatkan lokasi. Pastikan GPS aktif.', 'danger');
                }
            );
        } else {
            MarubaDOM.showAlert('Browser tidak mendukung geolocation.', 'danger');
        }
    }
    
    // Add location button (optional enhancement)
    $('#coordinatesRow').append('<button type="button" class="btn btn-outline-secondary btn-sm mt-2" onclick="getCurrentLocation()"><i class="bi bi-geo-alt"></i> Gunakan Lokasi Saat Ini</button>');
    
    // Focus on name field
    $('#memberName').focus();
    
})(jQuery);
</script>
<?php
$content = ob_get_clean();
include view_path('layout_dashboard');
