<?php
/**
 * Functions Library
 * Kumpulan fungsi-fungsi yang digunakan di seluruh aplikasi
 */

// Include Composer autoloader
require_once __DIR__ . '/../vendor/autoload.php';

// Include configuration
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

// Include new Excel Helper
require_once __DIR__ . '/../libraries/ExcelHelper.php';

/**
 * Start session with security settings
 */
function startSecureSession() {
    if (session_status() == PHP_SESSION_NONE) {
        session_name(SESSION_NAME);
        session_set_cookie_params([
            'lifetime' => SESSION_LIFETIME,
            'path' => '/',
            'domain' => '',
            'secure' => false, // Set to true in production with HTTPS
            'httponly' => true,
            'samesite' => 'Strict'
        ]);
        session_start();
    }
}

/**
 * Check if user is logged in
 */
function isLoggedIn() {
    startSecureSession();
    return isset($_SESSION['user_id']) && isset($_SESSION['username']);
}

/**
 * Get current user data
 */
function getCurrentUser() {
    if (!isLoggedIn()) {
        return false;
    }

    $sql = "SELECT id, username, nama_lengkap, role, status FROM users WHERE id = ? AND status = 'aktif'";
    return fetchOne($sql, [$_SESSION['user_id']]);
}

/**
 * Check if user has specific role
 */
function hasRole($role) {
    $user = getCurrentUser();
    return $user && $user['role'] === $role;
}

/**
 * Check if user is admin
 */
function isAdmin() {
    return hasRole('admin');
}

/**
 * Check if user is kasir
 */
function isKasir() {
    return hasRole('kasir');
}

/**
 * Check if user can access POS system
 * Both admin and kasir can access POS
 */
function canAccessPOS() {
    return isAdmin() || isKasir();
}

/**
 * Login user
 */
function loginUser($username, $password) {
    $sql = "SELECT id, username, password, nama_lengkap, role, status FROM users WHERE username = ? AND status = 'aktif'";
    $user = fetchOne($sql, [$username]);

    if ($user && password_verify($password, $user['password'])) {
        startSecureSession();
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['nama_lengkap'] = $user['nama_lengkap'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['login_time'] = time();

        // Update last login
        $update_sql = "UPDATE users SET updated_at = CURRENT_TIMESTAMP WHERE id = ?";
        executeQuery($update_sql, [$user['id']]);

        return true;
    }

    return false;
}

/**
 * Logout user
 */
function logoutUser() {
    startSecureSession();
    session_destroy();
    return true;
}

/**
 * Generate CSRF token
 */
function generateCSRFToken() {
    startSecureSession();
    if (!isset($_SESSION[CSRF_TOKEN_NAME])) {
        $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
    }
    return $_SESSION[CSRF_TOKEN_NAME];
}

/**
 * Verify CSRF token
 */
function verifyCSRFToken($token) {
    startSecureSession();
    return isset($_SESSION[CSRF_TOKEN_NAME]) && hash_equals($_SESSION[CSRF_TOKEN_NAME], $token);
}

/**
 * Redirect function
 */
function redirect($url) {
    header("Location: $url");
    exit;
}

/**
 * Set flash message
 */
function setFlashMessage($type, $message) {
    startSecureSession();
    $_SESSION['flash_message'] = [
        'type' => $type,
        'message' => $message
    ];
}

/**
 * Get and clear flash message
 */
function getFlashMessage() {
    startSecureSession();
    if (isset($_SESSION['flash_message'])) {
        $message = $_SESSION['flash_message'];
        unset($_SESSION['flash_message']);
        return $message;
    }
    return null;
}

/**
 * Upload file function
 */
function uploadFile($file, $targetDir, $allowedTypes = ALLOWED_IMAGE_TYPES) {
    if (!isset($file['error']) || is_array($file['error'])) {
        return ['success' => false, 'message' => 'Invalid file parameter'];
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'Upload error occurred'];
    }

    if ($file['size'] > MAX_FILE_SIZE) {
        return ['success' => false, 'message' => 'File size exceeds maximum limit'];
    }

    $fileInfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $fileInfo->file($file['tmp_name']);
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

    if (!in_array($extension, $allowedTypes)) {
        return ['success' => false, 'message' => 'File type not allowed'];
    }

    // Create target directory if not exists
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0755, true);
    }

    // Generate unique filename
    $filename = uniqid() . '.' . $extension;
    $targetPath = $targetDir . '/' . $filename;

    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        return ['success' => true, 'filename' => $filename, 'path' => $targetPath];
    }

    return ['success' => false, 'message' => 'Failed to move uploaded file'];
}

/**
 * Delete file function
 */
function deleteFile($filePath) {
    if (file_exists($filePath)) {
        return unlink($filePath);
    }
    return false;
}

/**
 * Log activity
 */
function logActivity($action, $description) {
    $user = getCurrentUser();
    if (!$user) return false;

    $sql = "INSERT INTO activity_logs (user_id, action, description, created_at) VALUES (?, ?, ?, NOW())";
    return executeQuery($sql, [$user['id'], $action, $description]);
}

/**
 * Pagination helper
 */
function paginate($totalRecords, $currentPage, $recordsPerPage = RECORDS_PER_PAGE) {
    $totalPages = ceil($totalRecords / $recordsPerPage);
    $currentPage = max(1, min($currentPage, $totalPages));
    $offset = ($currentPage - 1) * $recordsPerPage;

    return [
        'total_records' => $totalRecords,
        'total_pages' => $totalPages,
        'current_page' => $currentPage,
        'records_per_page' => $recordsPerPage,
        'offset' => $offset,
        'has_previous' => $currentPage > 1,
        'has_next' => $currentPage < $totalPages,
        'previous_page' => max(1, $currentPage - 1),
        'next_page' => min($totalPages, $currentPage + 1)
    ];
}

/**
 * Search and filter helper
 */
function buildSearchQuery($baseQuery, $searchFields, $searchTerm) {
    if (empty($searchTerm) || empty($searchFields)) {
        return $baseQuery;
    }

    $searchConditions = [];
    foreach ($searchFields as $field) {
        $searchConditions[] = "$field LIKE ?";
    }

    $searchQuery = $baseQuery . " AND (" . implode(" OR ", $searchConditions) . ")";
    return $searchQuery;
}

/**
 * Export to CSV
 */
function exportToCSV($data, $filename, $headers = []) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    $output = fopen('php://output', 'w');

    if (!empty($headers)) {
        fputcsv($output, $headers);
    }

    foreach ($data as $row) {
        fputcsv($output, $row);
    }

    fclose($output);
    exit;
}

/**
 * Get low stock products
 */
function getLowStockProducts($threshold = null) {
    if (!SHOW_LOW_STOCK_ALERTS) {
        return [];
    }

    if ($threshold === null) {
        $threshold = LOW_STOCK_THRESHOLD;
    }

    $sql = "SELECT p.*, c.nama_kategori
            FROM products p
            LEFT JOIN categories c ON p.category_id = c.id
            WHERE p.status = 'aktif' AND p.stok <= ?
            ORDER BY p.stok ASC, p.nama_barang ASC";

    return fetchAll($sql, [$threshold]);
}

/**
 * Get critical stock products count
 */
function getCriticalStockCount() {
    if (!SHOW_LOW_STOCK_ALERTS) {
        return 0;
    }

    $sql = "SELECT COUNT(*) as count
            FROM products
            WHERE status = 'aktif' AND stok <= ?";

    $result = fetchOne($sql, [CRITICAL_STOCK_THRESHOLD]);
    return $result['count'];
}

/**
 * Get low stock products count
 */
function getLowStockCount() {
    if (!SHOW_LOW_STOCK_ALERTS) {
        return 0;
    }

    $sql = "SELECT COUNT(*) as count
            FROM products
            WHERE status = 'aktif' AND stok <= ? AND stok > ?";

    $result = fetchOne($sql, [LOW_STOCK_THRESHOLD, CRITICAL_STOCK_THRESHOLD]);
    return $result['count'];
}

/**
 * Check if product has low stock
 */
function isLowStock($stock, $threshold = null) {
    if ($threshold === null) {
        $threshold = LOW_STOCK_THRESHOLD;
    }

    return $stock <= $threshold;
}

/**
 * Check if product has critical stock
 */
function isCriticalStock($stock) {
    return $stock <= CRITICAL_STOCK_THRESHOLD;
}
?>