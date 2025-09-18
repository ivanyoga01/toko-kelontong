<?php
/**
 * Reset Password AJAX Handler
 * Handles password reset for users
 */

require_once '../../includes/admin_auth.php';
require_once '../../includes/functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

try {
    $user_id = sanitizeInput($_POST['user_id'] ?? '');
    $new_password = $_POST['new_password'] ?? '';
    $confirm_new_password = $_POST['confirm_new_password'] ?? '';

    // Validation
    if (empty($user_id)) {
        echo json_encode(['success' => false, 'message' => 'ID user tidak valid']);
        exit;
    }

    if (empty($new_password)) {
        echo json_encode(['success' => false, 'message' => 'Password baru wajib diisi']);
        exit;
    }

    if (strlen($new_password) < 6) {
        echo json_encode(['success' => false, 'message' => 'Password minimal 6 karakter']);
        exit;
    }

    if ($new_password !== $confirm_new_password) {
        echo json_encode(['success' => false, 'message' => 'Konfirmasi password tidak cocok']);
        exit;
    }

    // Check if user exists
    $check_sql = "SELECT id, username FROM users WHERE id = ?";
    $user = fetchOne($check_sql, [$user_id]);

    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'User tidak ditemukan']);
        exit;
    }

    // Prevent reset own password
    if ($user_id == $current_user['id']) {
        echo json_encode(['success' => false, 'message' => 'Tidak dapat reset password sendiri']);
        exit;
    }

    $pdo = getDatabase();

    // Update password
    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
    $sql = "UPDATE users SET password = ?, updated_at = NOW() WHERE id = ?";

    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute([$hashed_password, $user_id]);

    if ($result) {
        echo json_encode(['success' => true, 'message' => 'Password berhasil direset']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Gagal reset password']);
    }

} catch (Exception $e) {
    error_log("Reset password error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Terjadi kesalahan sistem']);
}
?>