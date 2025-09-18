<?php
/**
 * Admin Products Management - Delete
 * Handler untuk menghapus produk/barang
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
        echo json_encode(['success' => false, 'message' => 'ID produk tidak valid']);
        exit;
    }

    // Check if product exists
    $check_sql = "SELECT id, nama_barang, gambar FROM products WHERE id = ?";
    $product = fetchOne($check_sql, [$id]);

    if (!$product) {
        echo json_encode(['success' => false, 'message' => 'Produk tidak ditemukan']);
        exit;
    }

    // Check if product is being used in transactions
    $usage_sql = "SELECT COUNT(*) as count FROM transaction_details WHERE product_id = ?";
    $usage = fetchOne($usage_sql, [$id]);

    if ($usage['count'] > 0) {
        echo json_encode(['success' => false, 'message' => 'Produk tidak dapat dihapus karena sudah digunakan dalam transaksi']);
        exit;
    }

    // Start transaction
    global $pdo;
    $pdo->beginTransaction();

    try {
        // Delete stock movements first
        $delete_movements_sql = "DELETE FROM stock_movements WHERE product_id = ?";
        executeQuery($delete_movements_sql, [$id]);

        // Delete product
        $delete_sql = "DELETE FROM products WHERE id = ?";
        if (executeQuery($delete_sql, [$id])) {
            // Delete image file if exists
            if (!empty($product['gambar'])) {
                deleteFile(UPLOADS_PATH . 'products/' . $product['gambar']);
            }

            $pdo->commit();

            setFlashMessage('success', "Produk '{$product['nama_barang']}' berhasil dihapus");
            echo json_encode(['success' => true, 'message' => "Produk '{$product['nama_barang']}' berhasil dihapus"]);
        } else {
            $pdo->rollback();
            echo json_encode(['success' => false, 'message' => 'Gagal menghapus produk']);
        }

    } catch (Exception $e) {
        $pdo->rollback();
        throw $e;
    }

} catch (Exception $e) {
    error_log("Product delete error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Terjadi kesalahan sistem']);
}
?>