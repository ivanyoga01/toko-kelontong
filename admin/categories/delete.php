<?php
/**
 * Admin Categories Management - Delete
 * Handler untuk menghapus kategori barang
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
        echo json_encode(['success' => false, 'message' => 'ID kategori tidak valid']);
        exit;
    }

    // Check if category exists
    $check_sql = "SELECT id, nama_kategori FROM categories WHERE id = ?";
    $category = fetchOne($check_sql, [$id]);

    if (!$category) {
        echo json_encode(['success' => false, 'message' => 'Kategori tidak ditemukan']);
        exit;
    }

    // Check if category is being used by products
    $usage_sql = "SELECT COUNT(*) as count FROM products WHERE category_id = ?";
    $usage = fetchOne($usage_sql, [$id]);

    if ($usage['count'] > 0) {
        // Don't delete, just set products to NULL category
        $update_products_sql = "UPDATE products SET category_id = NULL WHERE category_id = ?";
        executeQuery($update_products_sql, [$id]);
    }

    // Delete category
    $delete_sql = "DELETE FROM categories WHERE id = ?";

    if (executeQuery($delete_sql, [$id])) {
        $message = "Kategori '{$category['nama_kategori']}' berhasil dihapus";
        if ($usage['count'] > 0) {
            $message .= ". {$usage['count']} produk yang menggunakan kategori ini telah diubah menjadi tanpa kategori";
        }

        setFlashMessage('success', $message);
        echo json_encode(['success' => true, 'message' => $message]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Gagal menghapus kategori']);
    }

} catch (Exception $e) {
    error_log("Category delete error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Terjadi kesalahan sistem']);
}
?>