<?php
$title = 'Login Koperasi';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($title) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
        }
        .card-body {
            padding: 2rem;
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 25px;
            padding: 10px;
        }
        .btn-primary:hover {
            background: linear-gradient(135deg, #764ba2 0%, #667eea 100%);
        }
        .form-control {
            border-radius: 10px;
            border: 1px solid #ddd;
            padding: 12px;
        }
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
    </style>
</head>
<body>
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
          <input type="hidden" name="csrf_token" value="<?= bin2hex(random_bytes(32)) ?>">
          <div class="mb-3">
            <label class="form-label" for="username">
              <i class="bi bi-person me-1"></i> Nama pengguna
            </label>
            <input type="text" class="form-control" id="username" name="username" required>
          </div>
          <div class="mb-3">
            <label class="form-label" for="password">
              <i class="bi bi-lock me-1"></i> Kata sandi
            </label>
            <input type="password" class="form-control" id="password" name="password" required>
          </div>
          <div class="mb-3 form-check">
            <input type="checkbox" class="form-check-input" id="remember" name="remember">
            <label class="form-check-label" for="remember">Ingat saya</label>
          </div>
          <div class="d-grid gap-2">
            <button type="submit" class="btn btn-primary">
              <i class="bi bi-box-arrow-in-right me-1"></i> Masuk
            </button>
          </div>
        </form>
        <div class="text-center mt-3">
          <small class="text-muted">
            <a href="<?= BASE_URL ?>/auth/forgot">Lupa kata sandi?</a>
          </small>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const loginForm = document.getElementById('loginForm');
    
    loginForm.addEventListener('submit', function(e) {
        // Basic validation
        const username = document.getElementById('username').value.trim();
        const password = document.getElementById('password').value;
        
        if (!username || !password) {
            e.preventDefault();
            alert('Username dan password wajib diisi');
            return false;
        }
    });
});
</script>
</body>
</html>
          <div class="text-center mb-3">
            <p class="text-muted small mb-2">
              <i class="bi bi-lightning me-1"></i> Quick Login - Klik untuk login otomatis
            </p>
          </div>
          <div class="row g-2">
            <div class="col-6">
              <button class="btn btn-outline-primary btn-sm w-100 quick-login-btn" 
                      data-username="admin" data-password="admin123" data-role="Admin">
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
        </div>
        <div class="text-center mt-3 text-muted small">
          <i class="bi bi-shield-check me-1"></i> Keamanan: Quick login hanya untuk development/testing.
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
        
        // Quick Login functionality
        $('.quick-login-btn').on('click', function() {
            const username = $(this).data('username');
            const password = $(this).data('password');
            const role = $(this).data('role');
            
            // Show loading state on button
            const originalText = $(this).html();
            $(this).prop('disabled', true)
                .html('<span class="spinner-border spinner-border-sm me-1"></span> Login...');
            
            // Fill form fields
            $('#username').val(username);
            $('#password').val(password);
            
            // Clear any previous errors
            $('.invalid-feedback').hide();
            $('.form-control').removeClass('is-invalid');
            
            // Show loading state on login button
            $('#loginBtn').prop('disabled', true)
                .html('<span class="spinner-border spinner-border-sm me-2"></span>Login sebagai ' + role + '...');
            
            // Submit form after a short delay for visual feedback
            setTimeout(function() {
                $('#loginForm').submit();
            }, 500);
        });
        
        // Add hover effect for quick login buttons
        $('.quick-login-btn').hover(
            function() {
                const role = $(this).data('role');
                $(this).attr('title', 'Login sebagai ' + role + ' (' + $(this).data('username') + ')');
            },
            function() {
                $(this).removeAttr('title');
            }
        );
    });
    
})(jQuery);
</script>
<?php
$content = ob_get_clean();
include view_path('layout');
?>
