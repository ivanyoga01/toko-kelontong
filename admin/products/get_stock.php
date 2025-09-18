<?php
/**
 * Get Product Stock Info - AJAX Handler
 * Returns product stock information for stock adjustment
 */

require_once '../../includes/admin_auth.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

try {
    $product_id = intval($_GET['id'] ?? 0);

    if ($product_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'ID produk tidak valid']);
        exit;
    }

    // Get product data
    $product_sql = "SELECT id, nama_barang, kode_barang, stok, satuan FROM products WHERE id = ?";
    $product = fetchOne($product_sql, [$product_id]);

    if (!$product) {
        echo json_encode(['success' => false, 'message' => 'Produk tidak ditemukan']);
        exit;
    }

    echo json_encode([
        'success' => true,
        'data' => [
            'id' => $product['id'],
            'nama_barang' => $product['nama_barang'],
            'kode_barang' => $product['kode_barang'],
            'stok' => $product['stok'],
            'satuan' => $product['satuan']
        ]
    ]);

} catch (Exception $e) {
    error_log("Get stock error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Terjadi kesalahan saat mengambil data stok'
    ]);
}
?>