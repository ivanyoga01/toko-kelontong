<?php
/**
 * Search Product AJAX
 * API untuk mencari produk berdasarkan nama atau kode barang
 */

require_once '../includes/functions.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

try {
    $query = sanitizeInput($_GET['q'] ?? '');
    $category_id = intval($_GET['category'] ?? 0);
    $limit = min(30, intval($_GET['limit'] ?? 20)); // Increased limit for better POS experience
    $include_stock_info = isset($_GET['include_stock']) && $_GET['include_stock'] === 'true';

    // Build search conditions
    $where_conditions = ["p.status = 'aktif'"];
    $params = [];

    // Default: exclude out of stock unless specifically requested
    if (!isset($_GET['include_out_of_stock']) || $_GET['include_out_of_stock'] !== 'true') {
        $where_conditions[] = "p.stok > 0";
    }

    if (!empty($query)) {
        $where_conditions[] = "(p.nama_barang LIKE ? OR p.kode_barang LIKE ?)";
        $params[] = "%$query%";
        $params[] = "%$query%";
    }

    if ($category_id > 0) {
        $where_conditions[] = "p.category_id = ?";
        $params[] = $category_id;
    }

    $where_clause = "WHERE " . implode(" AND ", $where_conditions);

    $sql = "SELECT p.*, c.nama_kategori,
            CASE
                WHEN p.stok = 0 THEN 'out'
                WHEN p.stok <= " . CRITICAL_STOCK_THRESHOLD . " THEN 'critical'
                WHEN p.stok <= " . LOW_STOCK_THRESHOLD . " THEN 'low'
                ELSE 'good'
            END as stock_status
            FROM products p
            LEFT JOIN categories c ON p.category_id = c.id
            $where_clause
            ORDER BY p.nama_barang ASC
            LIMIT ?";

    $params[] = $limit;
    $products = fetchAll($sql, $params);

    // Enhance product data for frontend
    foreach ($products as &$product) {
        $product['harga_jual_formatted'] = formatCurrency($product['harga_jual']);

        if ($include_stock_info) {
            $product['stock_info'] = [
                'status' => $product['stock_status'],
                'available' => $product['stok'] > 0,
                'badge_class' => match($product['stock_status']) {
                    'good' => 'badge-success',
                    'low' => 'badge-warning',
                    'critical' => 'badge-danger',
                    'out' => 'badge-secondary',
                    default => 'badge-secondary'
                },
                'badge_text' => match($product['stock_status']) {
                    'good' => 'Tersedia',
                    'low' => 'Stok Menipis',
                    'critical' => 'Stok Kritis',
                    'out' => 'Habis',
                    default => 'Unknown'
                }
            ];
        }

        // Image URL
        if (!empty($product['gambar']) && file_exists(UPLOADS_PATH . 'products/' . $product['gambar'])) {
            $product['image_url'] = UPLOADS_URL . 'products/' . $product['gambar'];
        } else {
            $product['image_url'] = null;
        }
    }

    echo json_encode(['success' => true, 'data' => $products ?: []]);

} catch (Exception $e) {
    error_log("Search product error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Terjadi kesalahan sistem']);
}
?>