<?php
/**
 * Admin Authentication Check
 * File untuk memastikan hanya admin yang bisa akses area admin
 */

require_once __DIR__ . '/functions.php';

// Start session
startSecureSession();

// Check if user is logged in
if (!isLoggedIn()) {
    redirect(AUTH_URL . 'login.php');
}

// Check if user is admin
if (!isAdmin()) {
    setFlashMessage('error', 'Anda tidak memiliki akses ke area admin');
    redirect(KASIR_URL . 'penjualan.php');
}

// Check session timeout
if (isset($_SESSION['login_time']) && (time() - $_SESSION['login_time'] > SESSION_LIFETIME)) {
    logoutUser();
    setFlashMessage('warning', 'Session telah berakhir, silakan login kembali');
    redirect(AUTH_URL . 'login.php');
}

// Get current user data
$current_user = getCurrentUser();
if (!$current_user) {
    logoutUser();
    redirect(AUTH_URL . 'login.php');
}
?>