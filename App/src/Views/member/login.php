<?php
ob_start();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portal Anggota - KSP LAM GABE JAYA</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            overflow: hidden;
            max-width: 400px;
            width: 100%;
        }
        .login-header {
            background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
            color: white;
            padding: 2rem;
            text-align: center;
        }
        .login-body {
            padding: 2rem;
        }
        .form-control:focus {
            border-color: #4f46e5;
            box-shadow: 0 0 0 0.2rem rgba(79, 70, 229, 0.25);
        }
        .btn-login {
            background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
            border: none;
            padding: 0.75rem 2rem;
            border-radius: 10px;
            font-weight: 600;
            width: 100%;
        }
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(79, 70, 229, 0.3);
        }
        .alert {
            border-radius: 10px;
            border: none;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="login-card">
                    <div class="login-header">
                        <i class="bi bi-shield-lock fs-1 mb-3"></i>
                        <h4 class="mb-1">Portal Anggota</h4>
                        <p class="mb-0 opacity-75"><?php echo APP_NAME; ?></p>
                    </div>
                    <div class="login-body">
                        <?php if (isset($_SESSION['member_login_error'])): ?>
                            <div class="alert alert-danger">
                                <i class="bi bi-exclamation-triangle me-2"></i>
                                <?php echo htmlspecialchars($_SESSION['member_login_error']); ?>
                            </div>
                            <?php unset($_SESSION['member_login_error']); ?>
                        <?php endif; ?>

                        <form method="POST" action="<?php echo route_url('member/authenticate'); ?>">
                            <div class="mb-3">
                                <label for="nik" class="form-label">
                                    <i class="bi bi-person-badge me-1"></i>Nomor Induk Kependudukan (NIK)
                                </label>
                                <input type="text" class="form-control" id="nik" name="nik"
                                       placeholder="Masukkan NIK 16 digit" required maxlength="20">
                            </div>
                            <div class="mb-4">
                                <label for="phone" class="form-label">
                                    <i class="bi bi-phone me-1"></i>Nomor Telepon
                                </label>
                                <input type="tel" class="form-control" id="phone" name="phone"
                                       placeholder="Masukkan nomor telepon" required maxlength="20">
                            </div>
                            <button type="submit" class="btn btn-primary btn-login">
                                <i class="bi bi-box-arrow-in-right me-2"></i>Masuk ke Portal
                            </button>
                        </form>

                        <div class="text-center mt-4">
                            <small class="text-muted">
                                Belum terdaftar sebagai anggota?
                                <a href="<?php echo route_url(''); ?>" class="text-decoration-none">
                                    Hubungi koperasi
                                </a>
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php
$content = ob_get_clean();
echo $content;
?>
