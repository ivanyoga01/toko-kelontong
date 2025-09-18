<?php
/**
 * Advanced Product Search AJAX
 * Enhanced API for advanced product search with comprehensive filters
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
    // Search parameters
    $search_term = sanitizeInput($_GET['q'] ?? '');
    $category_id = intval($_GET['category'] ?? 0);
    $stock_filter = sanitizeInput($_GET['stock'] ?? '');
    $price_min = floatval($_GET['price_min'] ?? 0);
    $price_max = floatval($_GET['price_max'] ?? 0);
    $sort_by = sanitizeInput($_GET['sort'] ?? 'nama');
    $sort_order = sanitizeInput($_GET['order'] ?? 'asc');
    $limit = min(50, intval($_GET['limit'] ?? 20)); // Max 50 for performance
    $include_out_of_stock = isset($_GET['include_out_of_stock']) && $_GET['include_out_of_stock'] === 'true';

    // Build search conditions
    $where_conditions = ["p.status = 'aktif'"];
    $params = [];

    // Basic search term
    if (!empty($search_term)) {
        $where_conditions[] = "(p.nama_barang LIKE ? OR p.kode_barang LIKE ? OR p.deskripsi LIKE ?)";
        $searchTerm = "%$search_term%";
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
    }

    // Category filter
    if ($category_id > 0) {
        $where_conditions[] = "p.category_id = ?";
        $params[] = $category_id;
    }

    // Stock filter
    if (!empty($stock_filter)) {
        switch ($stock_filter) {
            case 'available':
                $where_conditions[] = "p.stok > 0";
                break;
            case 'low':
                $where_conditions[] = "p.stok <= " . LOW_STOCK_THRESHOLD . " AND p.stok > 0";
                break;
            case 'critical':
                $where_conditions[] = "p.stok <= " . CRITICAL_STOCK_THRESHOLD . " AND p.stok > 0";
                break;
            case 'out':
                $where_conditions[] = "p.stok = 0";
                break;
            case 'good':
                $where_conditions[] = "p.stok > " . LOW_STOCK_THRESHOLD;
                break;
        }
    } elseif (!$include_out_of_stock) {
        // Default: exclude out of stock unless specifically requested
        $where_conditions[] = "p.stok > 0";
    }

    // Price range filter
    if ($price_min > 0) {
        $where_conditions[] = "p.harga_jual >= ?";
        $params[] = $price_min;
    }

    if ($price_max > 0) {
        $where_conditions[] = "p.harga_jual <= ?";
        $params[] = $price_max;
    }

    $where_clause = "WHERE " . implode(" AND ", $where_conditions);

    // Sorting
    $valid_sorts = ['nama', 'harga', 'stok', 'kategori', 'created'];
    $sort_by = in_array($sort_by, $valid_sorts) ? $sort_by : 'nama';
    $sort_order = $sort_order === 'desc' ? 'DESC' : 'ASC';

    $order_clause = match($sort_by) {
        'nama' => "ORDER BY p.nama_barang $sort_order",
        'harga' => "ORDER BY p.harga_jual $sort_order",
        'stok' => "ORDER BY p.stok $sort_order",
        'kategori' => "ORDER BY c.nama_kategori $sort_order, p.nama_barang ASC",
        'created' => "ORDER BY p.created_at $sort_order",
        default => "ORDER BY p.nama_barang ASC"
    };

    // Main query
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
            $order_clause
            LIMIT ?";

    $params[] = $limit;
    $products = fetchAll($sql, $params);

    // Count total without limit for pagination info
    $count_sql = "SELECT COUNT(*) as total FROM products p LEFT JOIN categories c ON p.category_id = c.id $where_clause";
    $count_params = array_slice($params, 0, -1); // Remove limit parameter
    $total_count = fetchOne($count_sql, $count_params)['total'];

    // Add stock status badges and formatted prices
    foreach ($products as &$product) {
        $product['harga_jual_formatted'] = formatCurrency($product['harga_jual']);
        $product['harga_beli_formatted'] = formatCurrency($product['harga_beli']);

        // Stock status info
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

        // Image URL
        if (!empty($product['gambar']) && file_exists(UPLOADS_PATH . 'products/' . $product['gambar'])) {
            $product['image_url'] = UPLOADS_URL . 'products/' . $product['gambar'];
        } else {
            $product['image_url'] = null;
        }
    }

    // Search metadata
    $metadata = [
        'total_count' => $total_count,
        'returned_count' => count($products),
        'has_more' => $total_count > $limit,
        'search_term' => $search_term,
        'filters_applied' => [
            'category' => $category_id > 0,
            'stock' => !empty($stock_filter),
            'price_range' => $price_min > 0 || $price_max > 0,
            'search_term' => !empty($search_term)
        ],
        'sort' => [
            'by' => $sort_by,
            'order' => $sort_order
        ]
    ];

    echo json_encode([
        'success' => true,
        'data' => $products,
        'metadata' => $metadata
    ]);

} catch (Exception $e) {
    error_log("Advanced search error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Terjadi kesalahan sistem',
        'debug' => DEVELOPMENT_MODE ? $e->getMessage() : null
    ]);
}
?>