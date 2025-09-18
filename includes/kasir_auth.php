<?php
/**
 * Kasir Authentication Check
 * File untuk memastikan hanya kasir yang bisa akses area kasir
 */

require_once __DIR__ . '/functions.php';

// Start session
startSecureSession();

// Check if user is logged in
if (!isLoggedIn()) {
    redirect(AUTH_URL . 'login.php');
}

// Check if user can access POS system
if (!canAccessPOS()) {
    setFlashMessage('error', 'Anda tidak memiliki akses ke area kasir');
    redirect(ADMIN_URL . 'dashboard.php');
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