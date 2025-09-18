<?php
/**
 * Logout Script
 * Script untuk logout dan menghapus session
 */

require_once '../includes/functions.php';

// Logout user
logoutUser();

// Set success message
setFlashMessage('success', 'Anda telah berhasil logout');

// Redirect to login page
redirect(AUTH_URL . 'login.php');
?>