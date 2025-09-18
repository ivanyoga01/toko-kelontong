<?php
/**
 * Application Configuration
 * Konfigurasi umum aplikasi Toko Kelontong
 */

// Application settings
define('APP_NAME', 'Toko Kelontong');
define('APP_VERSION', '1.0.0');
define('APP_DESCRIPTION', 'Sistem manajemen penjualan untuk toko kelontong');

// URL Configuration
define('BASE_URL', 'http://localhost/toko-kelontong/');
define('ADMIN_URL', BASE_URL . 'admin/');
define('KASIR_URL', BASE_URL . 'kasir/');
define('AUTH_URL', BASE_URL . 'auth/');
define('ASSETS_URL', BASE_URL . 'assets/');
define('UPLOADS_URL', BASE_URL . 'uploads/');

// Path Configuration
define('ROOT_PATH', __DIR__ . '/../');
define('INCLUDES_PATH', ROOT_PATH . 'includes/');
define('UPLOADS_PATH', ROOT_PATH . 'uploads/');
define('EXPORTS_PATH', ROOT_PATH . 'exports/');
define('LIBRARIES_PATH', ROOT_PATH . 'libraries/');

// Session Configuration
define('SESSION_NAME', 'toko_kelontong_session');
define('SESSION_LIFETIME', 3600); // 1 hour

// Security Configuration
define('ENCRYPTION_KEY', 'toko_kelontong_secret_key_2024');
define('CSRF_TOKEN_NAME', 'csrf_token');

// File Upload Configuration
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_IMAGE_TYPES', ['jpg', 'jpeg', 'png', 'gif']);
define('ALLOWED_EXCEL_TYPES', ['xlsx', 'xls', 'csv']);

// Pagination Configuration
define('RECORDS_PER_PAGE', 10);
define('MAX_PAGINATION_LINKS', 5);

// Date and Time Configuration
date_default_timezone_set('Asia/Jakarta');
define('DATE_FORMAT', 'd/m/Y');
define('DATETIME_FORMAT', 'd/m/Y H:i:s');
define('TIME_FORMAT', 'H:i:s');

// Currency Configuration
define('CURRENCY_SYMBOL', 'Rp ');
define('CURRENCY_DECIMAL_PLACES', 0);

// Transaction Configuration
define('TRANSACTION_CODE_PREFIX', 'TRX');
define('PRODUCT_CODE_PREFIX', 'BRG');

// Development Mode Configuration
define('DEVELOPMENT_MODE', true); // Set to false in production

// Low Stock Alert Configuration
define('LOW_STOCK_THRESHOLD', 10); // Default threshold for low stock alerts
define('CRITICAL_STOCK_THRESHOLD', 5); // Critical stock level
define('SHOW_LOW_STOCK_ALERTS', true); // Enable/disable low stock alerts

// Error Reporting (Development)
if (DEVELOPMENT_MODE) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

/**
 * Function untuk format currency
 */
function formatCurrency($amount) {
    return CURRENCY_SYMBOL . number_format($amount, CURRENCY_DECIMAL_PLACES, ',', '.');
}

/**
 * Function untuk format date
 */
function formatDate($date, $format = DATE_FORMAT) {
    if (empty($date)) return '-';
    return date($format, strtotime($date));
}

/**
 * Function untuk format datetime
 */
function formatDateTime($datetime, $format = DATETIME_FORMAT) {
    if (empty($datetime)) return '-';
    return date($format, strtotime($datetime));
}

/**
 * Function untuk generate kode transaksi
 */
function generateTransactionCode() {
    return TRANSACTION_CODE_PREFIX . date('Ymd') . sprintf('%04d', rand(1, 9999));
}

/**
 * Function untuk generate kode barang
 */
function generateProductCode() {
    return PRODUCT_CODE_PREFIX . sprintf('%03d', rand(1, 999));
}

/**
 * Function untuk sanitize input
 */
function sanitizeInput($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

/**
 * Function untuk validasi email
 */
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

/**
 * Function untuk validasi nomor HP
 */
function isValidPhone($phone) {
    return preg_match('/^[0-9]{10,15}$/', $phone);
}
?>