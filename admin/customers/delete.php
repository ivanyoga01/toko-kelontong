<?php
/**
 * Admin Customers Management - Delete
 * Handler untuk menghapus pelanggan
 */

require_once '../../includes/admin_auth.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    $id = intval($_POST['id'] ?? 0);

    if ($id <= 0) {
        echo json_encode(['success' => false, 'message' => 'ID pelanggan tidak valid']);
        exit;
    }

    // Check if customer exists
    $check_sql = "SELECT id, nama_pelanggan FROM customers WHERE id = ?";
    $customer = fetchOne($check_sql, [$id]);

    if (!$customer) {
        echo json_encode(['success' => false, 'message' => 'Pelanggan tidak ditemukan']);
        exit;
    }

    // Check if customer has transactions
    $usage_sql = "SELECT COUNT(*) as count FROM transactions WHERE customer_id = ?";
    $usage = fetchOne($usage_sql, [$id]);

    if ($usage['count'] > 0) {
        echo json_encode(['success' => false, 'message' => 'Pelanggan tidak dapat dihapus karena memiliki riwayat transaksi']);
        exit;
    }

    // Delete customer
    $delete_sql = "DELETE FROM customers WHERE id = ?";

    if (executeQuery($delete_sql, [$id])) {
        setFlashMessage('success', "Pelanggan '{$customer['nama_pelanggan']}' berhasil dihapus");
        echo json_encode(['success' => true, 'message' => "Pelanggan '{$customer['nama_pelanggan']}' berhasil dihapus"]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Gagal menghapus pelanggan']);
    }

} catch (Exception $e) {
    error_log("Customer delete error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Terjadi kesalahan sistem']);
}
?>