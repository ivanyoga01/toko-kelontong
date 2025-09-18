<?php
/**
 * Get User Data AJAX Handler
 * Returns user data for editing
 */

require_once '../../includes/admin_auth.php';
require_once '../../includes/functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

try {
    $id = sanitizeInput($_POST['id'] ?? '');

    if (empty($id)) {
        echo json_encode(['success' => false, 'message' => 'ID user tidak valid']);
        exit;
    }

    $sql = "SELECT id, username, nama_lengkap, role, status FROM users WHERE id = ?";
    $user = fetchOne($sql, [$id]);

    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'User tidak ditemukan']);
        exit;
    }

    echo json_encode(['success' => true, 'data' => $user]);

} catch (Exception $e) {
    error_log("Get user error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Terjadi kesalahan sistem']);
}
?>