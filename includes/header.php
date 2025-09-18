<?php
/**
 * Admin Template Header
 * Reusable header component for admin and kasir pages
 */

// Pastikan variabel yang diperlukan sudah tersedia
if (!isset($page_title)) $page_title = 'Toko Kelontong';
if (!isset($page_description)) $page_description = 'Sistem Penjualan Toko Kelontong';
if (!isset($current_user)) $current_user = getCurrentUser();

// Load shop information
$shop_info = [
    'name' => APP_NAME,
    'description' => APP_DESCRIPTION
];
?>
<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="utf-8" />
        <title><?php echo htmlspecialchars($page_title); ?> - <?php echo APP_NAME; ?></title>
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta content="<?php echo htmlspecialchars($page_description); ?>" name="description" />
        <meta content="<?php echo htmlspecialchars($shop_info['name']); ?>" name="author" />
        <meta http-equiv="X-UA-Compatible" content="IE=edge" />

        <!-- App favicon -->
        <link rel="shortcut icon" href="<?php echo ASSETS_URL; ?>Adminto_v4.0.0/Vertical/dist/assets/images/favicon.ico">

        <!-- Bootstrap Css -->
        <link href="<?php echo ASSETS_URL; ?>Adminto_v4.0.0/Vertical/dist/assets/css/bootstrap.min.css" id="bootstrap-stylesheet" rel="stylesheet" type="text/css" />
        <!-- Icons Css -->
        <link href="<?php echo ASSETS_URL; ?>Adminto_v4.0.0/Vertical/dist/assets/css/icons.min.css" rel="stylesheet" type="text/css" />
        <!-- App Css-->
        <link href="<?php echo ASSETS_URL; ?>Adminto_v4.0.0/Vertical/dist/assets/css/app.min.css" id="app-stylesheet" rel="stylesheet" type="text/css" />

        <!-- Custom Admin Styles -->
        <style>
            .card {
                border: none;
                box-shadow: 0 2px 15px rgba(0,0,0,0.08);
                border-radius: 8px;
            }
            .card-header {
                background: linear-gradient(45deg, #5369f8, #667eea);
                color: white !important;
                border-radius: 8px 8px 0 0 !important;
            }
            .card-header h4,
            .card-header .header-title {
                color: white !important;
                text-shadow: 1px 1px 2px rgba(0,0,0,0.3);
            }
            .card-header i {
                color: white !important;
            }
            .stats-card {
                background: linear-gradient(45deg, #5369f8, #667eea);
                color: white;
                border-radius: 10px;
            }
            .stats-card .stats-icon {
                font-size: 2.5rem;
                opacity: 0.8;
                color: white !important;
            }
            .stats-card .stats-number {
                font-size: 2.5rem;
                font-weight: bold;
                color: white !important;
                margin: 10px 0;
                text-shadow: 1px 1px 2px rgba(0,0,0,0.3);
            }
            .stats-card .card-body {
                color: white;
            }
            .stats-card h2, .stats-card h3 {
                color: white !important;
                text-shadow: 1px 1px 2px rgba(0,0,0,0.3);
            }
            .stats-card h5 {
                color: white !important;
                text-shadow: 1px 1px 2px rgba(0,0,0,0.3);
            }
            .stats-card small {
                color: rgba(255,255,255,0.9) !important;
                text-shadow: 1px 1px 2px rgba(0,0,0,0.3);
            }
            .stats-card .text-white {
                color: white !important;
                text-shadow: 1px 1px 2px rgba(0,0,0,0.3);
            }
            .stats-card .text-white-50 {
                color: rgba(255,255,255,0.9) !important;
                text-shadow: 1px 1px 2px rgba(0,0,0,0.3);
            }
            .badge-status-pending { background-color: #ffc107; }
            .badge-status-confirmed { background-color: #28a745; }
            .badge-status-cancelled { background-color: #dc3545; }
            .badge-status-completed { background-color: #6c757d; }
            .page-title-box {
                background: #f8f9fa;
                padding: 20px;
                border-radius: 8px;
                margin-bottom: 20px;
            }
            .currency { text-align: right; }
            .text-center { text-align: center; }
            .badge-success { background-color: #1abc9c; }
            .badge-danger { background-color: #e74c3c; }
            .badge-warning { background-color: #f39c12; }
            .pos-item { cursor: pointer; }
            .pos-item:hover { background-color: #f8f9fa; }
            .cart-item { border-bottom: 1px solid #eee; padding: 10px 0; }
            .total-display { font-size: 1.5rem; font-weight: bold; }
        </style>

        <?php if (isset($additional_css)): ?>
        <!-- Additional CSS -->
        <?php echo $additional_css; ?>
        <?php endif; ?>
    </head>

    <body>
        <!-- Begin page -->
        <div id="wrapper">

            <!-- Topbar Start -->
            <div class="navbar-custom">
                <ul class="list-unstyled topnav-menu float-right mb-0">
                    <li class="dropdown notification-list">
                        <a class="nav-link dropdown-toggle nav-user mr-0 waves-effect" href="#" role="button" aria-haspopup="false" aria-expanded="false">
                            <?php if ($current_user['role'] === 'admin'): ?>
                                <img src="<?php echo ASSETS_URL; ?>Adminto_v4.0.0/Vertical/dist/assets/images/users/user-1.jpg" alt="user-image" class="rounded-circle">
                            <?php else: ?>
                                <img src="<?php echo ASSETS_URL; ?>Adminto_v4.0.0/Vertical/dist/assets/images/users/user-2.jpg" alt="user-image" class="rounded-circle">
                            <?php endif; ?>
                            <span class="pro-user-name ml-1">
                                <?php echo htmlspecialchars($current_user['nama_lengkap']); ?>
                            </span>
                        </a>
                        <div class="dropdown-menu dropdown-menu-right profile-dropdown">
                            <!-- item-->
                            <div class="dropdown-header noti-title">
                                <h6 class="text-overflow m-0">Selamat Datang!</h6>
                            </div>

                            <!-- item-->
                            <!-- <?php if ($current_user['role'] === 'admin'): ?>
                                <a href="<?php echo ADMIN_URL; ?>profile.php" class="dropdown-item notify-item">
                                    <i class="mdi mdi-account"></i>
                                    <span>Profil Saya</span>
                                </a>
                            <?php else: ?>
                                <a href="<?php echo KASIR_URL; ?>profile.php" class="dropdown-item notify-item">
                                    <i class="mdi mdi-account"></i>
                                    <span>Profil Saya</span>
                                </a>
                            <?php endif; ?> -->

                            <div class="dropdown-divider"></div>

                            <!-- item-->
                            <a href="<?php echo AUTH_URL; ?>logout.php" class="dropdown-item notify-item">
                                <i class="mdi mdi-logout"></i>
                                <span>Logout</span>
                            </a>
                        </div>
                    </li>
                </ul>

                <!-- LOGO -->
                <div class="logo-box">
                    <?php if ($current_user['role'] === 'admin'): ?>
                        <a href="<?php echo ADMIN_URL; ?>dashboard.php" class="logo logo-dark text-center">
                            <span class="logo-lg">
                                <i class="mdi mdi-store" style="font-size: 20px; color: #4c5667;"></i>
                                <span style="margin-left: 8px; font-weight: 600;"><?php echo APP_NAME; ?></span>
                            </span>
                            <span class="logo-sm">
                                <i class="mdi mdi-store" style="font-size: 24px; color: #4c5667;"></i>
                            </span>
                        </a>
                        <a href="<?php echo ADMIN_URL; ?>dashboard.php" class="logo logo-light text-center">
                            <span class="logo-lg">
                                <i class="mdi mdi-store" style="font-size: 20px; color: #fff;"></i>
                                <span style="margin-left: 8px; font-weight: 600; color: #fff;"><?php echo APP_NAME; ?></span>
                            </span>
                            <span class="logo-sm">
                                <i class="mdi mdi-store" style="font-size: 24px; color: #fff;"></i>
                            </span>
                        </a>
                    <?php else: ?>
                        <a href="<?php echo KASIR_URL; ?>penjualan.php" class="logo logo-dark text-center">
                            <span class="logo-lg">
                                <i class="mdi mdi-cash-register" style="font-size: 20px; color: #4c5667;"></i>
                                <span style="margin-left: 8px; font-weight: 600;"><?php echo APP_NAME; ?> - Kasir</span>
                            </span>
                            <span class="logo-sm">
                                <i class="mdi mdi-cash-register" style="font-size: 24px; color: #4c5667;"></i>
                            </span>
                        </a>
                        <a href="<?php echo KASIR_URL; ?>penjualan.php" class="logo logo-light text-center">
                            <span class="logo-lg">
                                <i class="mdi mdi-cash-register" style="font-size: 20px; color: #fff;"></i>
                                <span style="margin-left: 8px; font-weight: 600; color: #fff;"><?php echo APP_NAME; ?> - Kasir</span>
                            </span>
                            <span class="logo-sm">
                                <i class="mdi mdi-cash-register" style="font-size: 24px; color: #fff;"></i>
                            </span>
                        </a>
                    <?php endif; ?>
                </div>

                <ul class="list-unstyled topnav-menu topnav-menu-left m-0">
                    <li>
                        <button class="button-menu-mobile waves-effect">
                            <i class="mdi mdi-menu"></i>
                        </button>
                    </li>

                    <li>
                        <!-- Mobile menu toggle (sm and below)-->
                        <a class="navbar-toggle nav-link" data-toggle="collapse" data-target="#topnav-menu-content">
                            <div class="lines">
                                <span></span>
                                <span></span>
                                <span></span>
                            </div>
                        </a>
                        <!-- End mobile menu toggle-->
                    </li>
                </ul>
            </div>
            <!-- end Topbar -->