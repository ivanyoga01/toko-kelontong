<?php

/**
 * Login Page
 * Halaman login untuk admin dan kasir
 */

require_once '../includes/functions.php';

// Redirect if already logged in
if (isLoggedIn()) {
  $user = getCurrentUser();
  if ($user['role'] === 'admin') {
    redirect(ADMIN_URL . 'dashboard.php');
  } else {
    redirect(KASIR_URL . 'penjualan.php');
  }
}

$error_message = '';

// Process login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $username = sanitizeInput($_POST['username'] ?? '');
  $password = $_POST['password'] ?? '';

  if (empty($username) || empty($password)) {
    $error_message = 'Username dan password harus diisi';
  } else {
    if (loginUser($username, $password)) {
      // Login successful, redirect based on role
      $user = getCurrentUser();
      if ($user['role'] === 'admin') {
        redirect(ADMIN_URL . 'dashboard.php');
      } else {
        redirect(KASIR_URL . 'penjualan.php');
      }
    } else {
      $error_message = 'Username atau password salah';
    }
  }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="utf-8" />
  <title>Login - <?php echo APP_NAME; ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta content="<?php echo APP_DESCRIPTION; ?>" name="description" />
  <meta content="Toko Kelontong" name="author" />

  <!-- App favicon -->
  <link rel="shortcut icon" href="<?php echo ASSETS_URL; ?>Adminto_v4.0.0/Vertical/dist/assets/images/favicon.ico">

  <!-- App css -->
  <link href="<?php echo ASSETS_URL; ?>Adminto_v4.0.0/Vertical/dist/assets/css/bootstrap.min.css" rel="stylesheet" type="text/css" />

  <!-- App css -->
  <link href="<?php echo ASSETS_URL; ?>Adminto_v4.0.0/Vertical/dist/assets/css/app.min.css" rel="stylesheet" type="text/css" />

  <!-- Icons css -->
  <link href="<?php echo ASSETS_URL; ?>Adminto_v4.0.0/Vertical/dist/assets/css/icons.min.css" rel="stylesheet" type="text/css" />

  <style>
    .account-pages {
      min-height: 100vh;
      display: flex;
      align-items: center;
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    }

    .card {
      box-shadow: 0 0 35px 0 rgba(154, 161, 171, 0.15);
      border-radius: 15px;
      border: 0;
    }

    .login-logo {
      font-size: 2rem;
      color: #6c757d;
      margin-bottom: 1rem;
    }
  </style>
</head>

<body class="account-pages">
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-xl-5 col-lg-6 col-md-8">
        <div class="card">
          <div class="card-body p-4">
            <div class="text-center mb-4">
              <div class="login-logo">
                <i class="fe-shopping-cart"></i>
              </div>
              <h4 class="text-uppercase"><?php echo APP_NAME; ?></h4>
              <p class="text-muted">Masukkan username dan password Anda</p>
            </div>

            <?php if (!empty($error_message)): ?>
              <div class="alert alert-danger" role="alert">
                <i class="fe-alert-circle"></i> <?php echo htmlspecialchars($error_message); ?>
              </div>
            <?php endif; ?>

            <?php
            $flash = getFlashMessage();
            if ($flash):
            ?>
              <div class="alert alert-<?php echo $flash['type'] === 'error' ? 'danger' : $flash['type']; ?>" role="alert">
                <?php echo htmlspecialchars($flash['message']); ?>
              </div>
            <?php endif; ?>

            <form method="POST" action="">
              <div class="form-group">
                <label for="username">Username</label>
                <input class="form-control" type="text" id="username" name="username"
                  value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>"
                  placeholder="Masukkan username" required>
              </div>

              <div class="form-group">
                <label for="password">Password</label>
                <input class="form-control" type="password" id="password" name="password"
                  placeholder="Masukkan password" required>
              </div>

              <div class="form-group text-center">
                <button class="btn btn-primary btn-block" type="submit">
                  <i class="fe-log-in"></i> Masuk
                </button>
              </div>
            </form>

            <div class="row mt-4">
              <div class="col-12 text-center">
                <div class="text-muted">
                  <h6>Akun Demo:</h6>
                  <p class="mb-1"><strong>Admin:</strong> admin / admin123</p>
                  <p class="mb-0"><strong>Kasir:</strong> kasir1 / kasir123</p>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="row mt-3">
          <div class="col-12 text-center">
            <p class="text-white-50">
              © 2024 <?php echo APP_NAME; ?>.
              <span class="d-none d-sm-inline-block">Versi <?php echo APP_VERSION; ?></span>
            </p>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Vendor js -->
  <script src="<?php echo ASSETS_URL; ?>Adminto_v4.0.0/Vertical/dist/assets/js/vendor.min.js"></script>

  <!-- App js -->
  <script src="<?php echo ASSETS_URL; ?>Adminto_v4.0.0/Vertical/dist/assets/js/app.min.js"></script>

  <script>
    // Focus on username field
    document.getElementById('username').focus();

    // Handle form submission
    document.querySelector('form').addEventListener('submit', function(e) {
      const username = document.getElementById('username').value.trim();
      const password = document.getElementById('password').value;

      if (!username || !password) {
        e.preventDefault();
        alert('Username dan password harus diisi!');
        return false;
      }
    });
  </script>
</body>

</html>