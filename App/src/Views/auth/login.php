<?php
// Ensure bootstrap is loaded
if (!function_exists('current_user')) {
    require_once __DIR__ . '/../../bootstrap.php';
}

$title = 'Login Koperasi';
ob_start();
?>
<style>
    .auth-wrapper {
        min-height: calc(100vh - 120px);
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .card {
        border: none;
        border-radius: 14px;
        box-shadow: 0 15px 35px rgba(0,0,0,0.08);
    }
    .card-body {
        padding: 2rem;
    }
    .btn-primary {
        border: none;
        border-radius: 12px;
        padding: 10px;
    }
    .form-control {
        border-radius: 10px;
        border: 1px solid #ddd;
        padding: 12px;
    }
    .form-control:focus {
        border-color: #3b82f6;
        box-shadow: 0 0 0 0.2rem rgba(59, 130, 246, 0.15);
    }
    .quick-login-btn {
        border-radius: 10px;
    }
</style>

<div class="auth-wrapper">
  <div class="col-12 col-sm-10 col-md-8 col-lg-6 col-xl-5">
    <div class="card shadow-sm mt-3">
      <div class="card-body p-4">
        <div class="text-center mb-4">
          <i class="bi bi-building text-primary" style="font-size: 3rem;"></i>
          <h5 class="card-title mt-2 mb-1">Masuk Aplikasi</h5>
          <p class="text-muted small mb-0">Silakan login untuk melanjutkan</p>
        </div>

        <form method="post" action="<?= route_url('index.php/login') ?>" id="loginForm">
            <?= csrf_field() ?>
            
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-triangle me-2"></i><?= htmlspecialchars($_SESSION['error']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>

          <div class="mb-3">
            <label class="form-label" for="username">
              <i class="bi bi-person me-1"></i> Nama pengguna
            </label>
            <input type="text" class="form-control" id="username" name="username" required>
            <div class="invalid-feedback" id="usernameError">Username wajib diisi.</div>
          </div>

          <div class="mb-3">
            <label class="form-label" for="password">
              <i class="bi bi-lock me-1"></i> Kata sandi
            </label>
            <div class="input-group">
              <input type="password" class="form-control" id="password" name="password" required>
              <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                <i id="passwordIcon" class="bi bi-eye"></i>
              </button>
            </div>
            <div class="invalid-feedback" id="passwordError">Kata sandi wajib diisi.</div>
          </div>

          <div class="mb-3 form-check">
            <input type="checkbox" class="form-check-input" id="remember" name="remember">
            <label class="form-check-label" for="remember">Ingat saya</label>
          </div>

          <div class="d-grid gap-2">
            <button type="submit" class="btn btn-primary" id="loginBtn">
              <i class="bi bi-box-arrow-in-right me-1"></i> Masuk
            </button>
          </div>
        </form>

        <div class="text-center mt-3">
          <small class="text-muted">
            <a href="<?= BASE_URL ?>/auth/forgot">Lupa kata sandi?</a>
          </small>
        </div>

        <hr class="my-4">
        <div class="text-center mb-3">
          <p class="text-muted small mb-2">
            <i class="bi bi-lightning me-1"></i> Quick Login - Klik untuk login otomatis
          </p>
        </div>

        <div class="row g-2">
          <div class="col-6">
            <button class="btn btn-outline-primary btn-sm w-100 quick-login-btn" 
                    data-username="admin" data-password="admin" data-role="Admin">
              <i class="bi bi-person-gear me-1"></i> Admin
            </button>
          </div>
          <div class="col-6">
            <button class="btn btn-outline-success btn-sm w-100 quick-login-btn" 
                    data-username="manajer" data-password="manager123" data-role="Manajer">
              <i class="bi bi-briefcase me-1"></i> Manajer
            </button>
          </div>
          <div class="col-6">
            <button class="btn btn-outline-info btn-sm w-100 quick-login-btn" 
                    data-username="kasir" data-password="kasir123" data-role="Kasir">
              <i class="bi bi-cash-stack me-1"></i> Kasir
            </button>
          </div>
          <div class="col-6">
            <button class="btn btn-outline-warning btn-sm w-100 quick-login-btn" 
                    data-username="teller" data-password="teller123" data-role="Teller">
              <i class="bi bi-person-badge me-1"></i> Teller
            </button>
          </div>
          <div class="col-6">
            <button class="btn btn-outline-secondary btn-sm w-100 quick-login-btn" 
                    data-username="surveyor" data-password="surveyor123" data-role="Surveyor">
              <i class="bi bi-clipboard-check me-1"></i> Surveyor
            </button>
          </div>
          <div class="col-6">
            <button class="btn btn-outline-danger btn-sm w-100 quick-login-btn" 
                    data-username="collector" data-password="collector123" data-role="Collector">
              <i class="bi bi-truck me-1"></i> Collector
            </button>
          </div>
          <div class="col-6">
            <button class="btn btn-outline-dark btn-sm w-100 quick-login-btn" 
                    data-username="akuntansi" data-password="akuntansi123" data-role="Akuntansi">
              <i class="bi bi-calculator me-1"></i> Akuntansi
            </button>
          </div>
          <div class="col-6">
            <button class="btn btn-outline-primary btn-sm w-100 quick-login-btn" 
                    data-username="creator" data-password="creator123" data-role="Creator">
              <i class="bi bi-gear me-1"></i> Creator
            </button>
          </div>
        </div>

        <div class="text-center mt-3 text-muted small">
          <i class="bi bi-shield-check me-1"></i> Keamanan: Quick login hanya untuk development/testing.
        </div>
      </div>
    </div>
  </div>
</div>

<script>
// HSTS Cache Clearing for Development
(function() {
    // Only run in development mode
    const isDevelopment = '<?= APP_ENV ?>' === 'development';
    
    if (isDevelopment) {
        // Check if we're on localhost and using HTTPS when we shouldn't
        const isLocalhost = window.location.hostname === 'localhost' || 
                          window.location.hostname === '127.0.0.1' ||
                          window.location.hostname.includes('localhost');
        
        // If we're on localhost but using HTTPS, redirect to HTTP
        if (isLocalhost && window.location.protocol === 'https:') {
            console.log('🔧 Development mode: Clearing HSTS cache, redirecting to HTTP');
            window.location.replace('http://' + window.location.hostname + window.location.pathname + window.location.search);
            return; // Stop execution
        }
    }
})();
// Basic validation for non-jQuery usage (failsafe)
document.addEventListener('DOMContentLoaded', function() {
    const loginForm = document.getElementById('loginForm');
    loginForm.addEventListener('submit', function(e) {
        const username = document.getElementById('username').value.trim();
        const password = document.getElementById('password').value;
        if (!username || !password) {
            e.preventDefault();
            alert('Username dan password wajib diisi');
            return false;
        }
        
        // Show loading state
        const submitBtn = document.getElementById('loginBtn');
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Masuk...';
        }
    });
});
</script>

<script>
(function($) {
    'use strict';
    
    $(function() {
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
            // allow native validation to handle required
            // but also add our styling
            $('.invalid-feedback').hide();
            $('.form-control').removeClass('is-invalid');

            let isValid = true;
            if (!$('#username').val().trim()) {
                $('#username').addClass('is-invalid');
                $('#usernameError').show();
                isValid = false;
            }
            if (!$('#password').val().trim()) {
                $('#password').addClass('is-invalid');
                $('#passwordError').show();
                isValid = false;
            }

            if (!isValid) {
                event.preventDefault();
                return false;
            }

            $('#loginBtn').prop('disabled', true)
                .html('<span class="spinner-border spinner-border-sm me-2"></span>Masuk...');
        });

        // Clear error on input
        $('#username, #password').on('input', function() {
            $(this).removeClass('is-invalid');
            $(this).siblings('.invalid-feedback').hide();
        });

        // Focus on username field
        $('#username').focus();

        // Quick Login functionality
        $('.quick-login-btn').on('click', function() {
            const username = $(this).data('username');
            const password = $(this).data('password');
            const role = $(this).data('role');

            // Fill form fields
            $('#username').val(username);
            $('#password').val(password);

            // Clear any previous errors
            $('.alert').remove();
            $('.invalid-feedback').hide();
            $('.form-control').removeClass('is-invalid');

            // Show loading state on login button
            $('#loginBtn').prop('disabled', true)
                .html('<span class="spinner-border spinner-border-sm me-2"></span>Login sebagai ' + role + '...');

            // Submit form after a short delay for visual feedback
            setTimeout(function() {
                $('#loginForm').submit();
            }, 400);
        });
        
        // Auto-focus username field
        $('#username').focus();
        
        // Clear errors on input
        $('#username, #password').on('input', function() {
            $(this).removeClass('is-invalid');
            $(this).siblings('.invalid-feedback').hide();
        });

    });
})(jQuery);
</script>

<?php
$content = ob_get_clean();
include view_path('layout');
?>
