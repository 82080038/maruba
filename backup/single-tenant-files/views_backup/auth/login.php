<?php
ob_start();
?>
<div class="row justify-content-center">
  <div class="col-12 col-sm-10 col-md-8 col-lg-6 col-xl-4">
    <div class="card shadow-sm mt-4">
      <div class="card-body p-4">
        <div class="text-center mb-4">
          <i class="bi bi-building text-primary" style="font-size: 3rem;"></i>
          <h5 class="card-title mt-2">Masuk Aplikasi</h5>
          <p class="text-muted small">Silakan login untuk melanjutkan</p>
        </div>
        <form method="post" action="/maruba/index.php/login" id="loginForm">
          <div class="mb-3" id="usernameField">
            <label class="form-label" for="username">
              <i class="bi bi-person me-1"></i> Nama pengguna
            </label>
            <input type="text" class="form-control" id="username" name="username" required>
            <div class="invalid-feedback" id="usernameError">Nama pengguna wajib diisi.</div>
          </div>
          <div class="mb-3" id="passwordField">
            <label class="form-label" for="password">
              <i class="bi bi-lock me-1"></i> Kata sandi
            </label>
            <div class="input-group">
              <input type="password" class="form-control" id="password" name="password" required>
              <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                <i class="bi bi-eye" id="passwordIcon"></i>
              </button>
            </div>
            <div class="invalid-feedback" id="passwordError">Password wajib diisi.</div>
          </div>
          <div class="d-grid gap-2">
            <button type="submit" class="btn btn-primary" id="loginBtn">
              <i class="bi bi-box-arrow-in-right me-2"></i> Masuk
            </button>
          </div>
        </form>
        <div class="text-center mt-3">
          <small class="text-muted">
            Demo: username <strong>admin</strong>, password <strong>admin</strong>
          </small>
        </div>
      </div>
    </div>
  </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Ensure jQuery is loaded before running scripts
(function($) {
    'use strict';
    
    $(document).ready(function() {
        // Toggle password visibility
        $('#togglePassword').on('click', function() {
            const passwordField = $('#password');
            const passwordIcon = $('#passwordIcon');
            
            if (passwordField.attr('type') === 'password') {
                passwordField.attr('type', 'text');
                passwordIcon.removeClass('bi-eye').addClass('bi-eye-slash');
            } else {
                passwordField.attr('type', 'password');
                passwordIcon.removeClass('bi-eye-slash').addClass('bi-eye');
            }
        });
        
        // Form validation with jQuery
        $('#loginForm').on('submit', function(event) {
            event.preventDefault();
            
            // Clear previous errors
            $('.invalid-feedback').hide();
            $('.form-control').removeClass('is-invalid');
            
            let isValid = true;
            
            // Validate username
            if (!$('#username').val().trim()) {
                $('#username').addClass('is-invalid');
                $('#usernameError').show();
                isValid = false;
            }
            
            // Validate password
            if (!$('#password').val().trim()) {
                $('#password').addClass('is-invalid');
                $('#passwordError').show();
                isValid = false;
            }
            
            if (isValid) {
                // Show loading state
                $('#loginBtn').prop('disabled', true)
                    .html('<span class="spinner-border spinner-border-sm me-2"></span>Masuk...');
                
                // Submit form
                this.submit();
            }
        });
        
        // Clear error on input
        $('#username, #password').on('input', function() {
            $(this).removeClass('is-invalid');
            $(this).siblings('.invalid-feedback').hide();
        });
        
        // Focus on username field
        $('#username').focus();
    });
    
})(jQuery);
</script>
<?php
$content = ob_get_clean();
include view_path('layout');
