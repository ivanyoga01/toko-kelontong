<?php
/**
 * Delete User AJAX Handler
 * Handles user deletion with safety checks
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

    // Check if user exists
    $check_sql = "SELECT id, username, role FROM users WHERE id = ?";
    $user = fetchOne($check_sql, [$id]);

    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'User tidak ditemukan']);
        exit;
    }

    // Prevent delete own account
    if ($id == $current_user['id']) {
        echo json_encode(['success' => false, 'message' => 'Tidak dapat menghapus akun sendiri']);
        exit;
    }

    // Check if user has transactions (kasir)
    if ($user['role'] === 'kasir') {
        $transaction_check = fetchOne("SELECT COUNT(*) as total FROM transactions WHERE user_id = ?", [$id]);
        if ($transaction_check && $transaction_check['total'] > 0) {
            echo json_encode([
                'success' => false,
                'message' => 'Tidak dapat menghapus kasir yang sudah memiliki riwayat transaksi'
            ]);
            exit;
        }
    }

    // Count remaining admins
    if ($user['role'] === 'admin') {
        $admin_count = fetchOne("SELECT COUNT(*) as total FROM users WHERE role = 'admin' AND status = 'aktif'", []);
        if ($admin_count && $admin_count['total'] <= 1) {
            echo json_encode([
                'success' => false,
                'message' => 'Tidak dapat menghapus admin terakhir yang aktif'
            ]);
            exit;
        }
    }

    $pdo = getDatabase();

    // Delete user
    $sql = "DELETE FROM users WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute([$id]);

    if ($result) {
        echo json_encode(['success' => true, 'message' => 'User berhasil dihapus']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Gagal menghapus user']);
    }

} catch (Exception $e) {
    error_log("Delete user error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Terjadi kesalahan sistem']);
}
?>