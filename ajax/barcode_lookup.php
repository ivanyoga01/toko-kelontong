<?php
/**
 * Barcode Lookup AJAX
 * API untuk mencari produk berdasarkan barcode untuk POS system
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
    $barcode = sanitizeInput($_GET['barcode'] ?? '');

    if (empty($barcode)) {
        echo json_encode(['success' => false, 'message' => 'Barcode tidak boleh kosong']);
        exit;
    }

    // Clean barcode (remove spaces and special characters)
    $barcode = preg_replace('/[^0-9]/', '', $barcode);

    if (empty($barcode)) {
        echo json_encode(['success' => false, 'message' => 'Format barcode tidak valid']);
        exit;
    }

    // Search product by barcode
    $sql = "SELECT p.*, c.nama_kategori,
            CASE
                WHEN p.stok = 0 THEN 'out'
                WHEN p.stok <= " . CRITICAL_STOCK_THRESHOLD . " THEN 'critical'
                WHEN p.stok <= " . LOW_STOCK_THRESHOLD . " THEN 'low'
                ELSE 'good'
            END as stock_status
            FROM products p
            LEFT JOIN categories c ON p.category_id = c.id
            WHERE p.barcode = ? AND p.status = 'aktif'
            LIMIT 1";

    $product = fetchOne($sql, [$barcode]);

    if ($product) {
        // Enhance product data
        $product['harga_jual_formatted'] = formatCurrency($product['harga_jual']);
        $product['harga_beli_formatted'] = formatCurrency($product['harga_beli']);

        // Stock information
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

        echo json_encode([
            'success' => true,
            'data' => $product,
            'message' => 'Produk ditemukan'
        ]);
    } else {
        // Try to find by kode_barang as fallback
        $fallback_sql = "SELECT p.*, c.nama_kategori,
                        CASE
                            WHEN p.stok = 0 THEN 'out'
                            WHEN p.stok <= " . CRITICAL_STOCK_THRESHOLD . " THEN 'critical'
                            WHEN p.stok <= " . LOW_STOCK_THRESHOLD . " THEN 'low'
                            ELSE 'good'
                        END as stock_status
                        FROM products p
                        LEFT JOIN categories c ON p.category_id = c.id
                        WHERE p.kode_barang = ? AND p.status = 'aktif'
                        LIMIT 1";

        $fallback_product = fetchOne($fallback_sql, [$barcode]);

        if ($fallback_product) {
            // Enhance product data
            $fallback_product['harga_jual_formatted'] = formatCurrency($fallback_product['harga_jual']);
            $fallback_product['harga_beli_formatted'] = formatCurrency($fallback_product['harga_beli']);

            // Stock information
            $fallback_product['stock_info'] = [
                'status' => $fallback_product['stock_status'],
                'available' => $fallback_product['stok'] > 0,
                'badge_class' => match($fallback_product['stock_status']) {
                    'good' => 'badge-success',
                    'low' => 'badge-warning',
                    'critical' => 'badge-danger',
                    'out' => 'badge-secondary',
                    default => 'badge-secondary'
                },
                'badge_text' => match($fallback_product['stock_status']) {
                    'good' => 'Tersedia',
                    'low' => 'Stok Menipis',
                    'critical' => 'Stok Kritis',
                    'out' => 'Habis',
                    default => 'Unknown'
                }
            ];

            // Image URL
            if (!empty($fallback_product['gambar']) && file_exists(UPLOADS_PATH . 'products/' . $fallback_product['gambar'])) {
                $fallback_product['image_url'] = UPLOADS_URL . 'products/' . $fallback_product['gambar'];
            } else {
                $fallback_product['image_url'] = null;
            }

            echo json_encode([
                'success' => true,
                'data' => $fallback_product,
                'message' => 'Produk ditemukan berdasarkan kode barang (fallback)',
                'is_fallback' => true
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Produk dengan barcode "' . $barcode . '" tidak ditemukan',
                'suggestions' => [
                    'Pastikan barcode sudah benar',
                    'Coba scan ulang barcode',
                    'Produk mungkin belum terdaftar di sistem',
                    'Periksa status produk (aktif/nonaktif)'
                ]
            ]);
        }
    }

} catch (Exception $e) {
    error_log("Barcode lookup error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Terjadi kesalahan sistem',
        'debug' => DEVELOPMENT_MODE ? $e->getMessage() : null
    ]);
}
?>