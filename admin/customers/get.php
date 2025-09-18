<?php
/**
 * Admin Customers Management - Get
 * Handler untuk mengambil data pelanggan berdasarkan ID
 */

require_once '../../includes/admin_auth.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    $id = intval($_GET['id'] ?? 0);

    if ($id <= 0) {
        echo json_encode(['success' => false, 'message' => 'ID pelanggan tidak valid']);
        exit;
    }

    $sql = "SELECT * FROM customers WHERE id = ?";
    $customer = fetchOne($sql, [$id]);

    if ($customer) {
        echo json_encode(['success' => true, 'data' => $customer]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Pelanggan tidak ditemukan']);
    }

} catch (Exception $e) {
    error_log("Customer get error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Terjadi kesalahan sistem']);
}
?>