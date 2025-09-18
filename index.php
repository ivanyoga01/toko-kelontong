<?php
/**
 * Smart Router - Index.php
 * Router utama yang mengarahkan user berdasarkan role
 * Akses: http://localhost/toko-kelontong/
 */

require_once 'includes/functions.php';

// Start secure session
startSecureSession();

// Check if user is logged in
if (!isLoggedIn()) {
    // Jika belum login, redirect ke halaman login
    redirect(AUTH_URL . 'login.php');
}

// Get current user data
$user = getCurrentUser();

if (!$user) {
    // Jika data user tidak ditemukan, logout dan redirect ke login
    logoutUser();
    setFlashMessage('error', 'Data user tidak ditemukan, silakan login kembali');
    redirect(AUTH_URL . 'login.php');
}

// Check user status
if ($user['status'] !== 'aktif') {
    logoutUser();
    setFlashMessage('error', 'Akun Anda tidak aktif, hubungi administrator');
    redirect(AUTH_URL . 'login.php');
}

// Redirect based on user role
switch ($user['role']) {
    case 'admin':
        // Admin diarahkan ke dashboard admin
        redirect(ADMIN_URL . 'dashboard.php');
        break;

    case 'kasir':
        // Kasir diarahkan ke sistem POS
        redirect(KASIR_URL . 'penjualan.php');
        break;

    default:
        // Role tidak dikenal, logout
        logoutUser();
        setFlashMessage('error', 'Role user tidak valid, hubungi administrator');
        redirect(AUTH_URL . 'login.php');
        break;
}
?>