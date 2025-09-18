<?php
/**
 * Admin Products Management - Get
 * Handler untuk mengambil data produk/barang
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
        echo json_encode(['success' => false, 'message' => 'ID produk tidak valid']);
        exit;
    }

    $sql = "SELECT p.*, c.nama_kategori
            FROM products p
            LEFT JOIN categories c ON p.category_id = c.id
            WHERE p.id = ?";

    $product = fetchOne($sql, [$id]);

    if ($product) {
        // Get stock movements for this product
        $movements_sql = "SELECT sm.*, t.kode_transaksi, u.nama_lengkap as user_name
                         FROM stock_movements sm
                         LEFT JOIN transactions t ON sm.transaction_id = t.id
                         LEFT JOIN users u ON sm.user_id = u.id
                         WHERE sm.product_id = ?
                         ORDER BY sm.created_at DESC
                         LIMIT 10";
        $movements = fetchAll($movements_sql, [$id]);
        $product['stock_movements'] = $movements;

        echo json_encode(['success' => true, 'data' => $product]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Produk tidak ditemukan']);
    }

} catch (Exception $e) {
    error_log("Product get error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Terjadi kesalahan sistem']);
}
?>