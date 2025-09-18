<?php
/**
 * Export Products to Excel
 * Exports products data with filters using PhpSpreadsheet
 */

require_once '../../includes/admin_auth.php';

// Handle filters
$search = sanitizeInput($_GET['search'] ?? '');
$category_filter = sanitizeInput($_GET['category'] ?? '');
$status_filter = sanitizeInput($_GET['status'] ?? '');

// Build query
$where_conditions = [];
$params = [];

if (!empty($search)) {
    $where_conditions[] = "(p.kode_barang LIKE ? OR p.nama_barang LIKE ?)";
    $searchTerm = "%$search%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

if (!empty($category_filter)) {
    $where_conditions[] = "p.category_id = ?";
    $params[] = $category_filter;
}

if (!empty($status_filter)) {
    $where_conditions[] = "p.status = ?";
    $params[] = $status_filter;
}

$where_clause = '';
if (!empty($where_conditions)) {
    $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
}

// Get products data
$sql = "SELECT p.kode_barang,
        p.barcode,
        p.nama_barang,
        c.nama_kategori,
        p.harga_beli,
        p.harga_jual,
        p.stok,
        p.satuan,
        p.deskripsi,
        p.status,
        p.created_at,
        p.updated_at
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id
        $where_clause
        ORDER BY p.nama_barang ASC";

$products = fetchAll($sql, $params);

// Prepare data for export
$export_data = [];
foreach ($products as $product) {
    $export_data[] = [
        $product['kode_barang'],
        $product['barcode'] ?? '',
        $product['nama_barang'],
        $product['nama_kategori'] ?? 'Tanpa Kategori',
        $product['harga_beli'],
        $product['harga_jual'],
        $product['stok'],
        $product['satuan'],
        $product['deskripsi'],
        ucfirst($product['status']),
        formatDateTime($product['created_at']),
        formatDateTime($product['updated_at'])
    ];
}

// Headers
$headers = [
    'kode_barang',
    'barcode',
    'nama_barang',
    'kategori',
    'harga_beli',
    'harga_jual',
    'stok',
    'satuan',
    'deskripsi',
    'status',
    'dibuat',
    'diupdate'
];

// Add summary rows
// $export_data[] = []; // Empty row
// $export_data[] = ['=== RINGKASAN ===', '', '', '', '', '', '', '', '', '', '', ''];
// $export_data[] = ['Total Produk:', count($products), '', '', '', '', '', '', '', '', '', ''];

if (!empty($products)) {
    $total_value = array_sum(array_map(function($p) {
        return $p['stok'] * $p['harga_beli'];
    }, $products));

    $active_products = count(array_filter($products, function($p) {
        return $p['status'] === 'aktif';
    }));

    $export_data[] = ['Produk Aktif:', $active_products, '', '', '', '', '', '', '', '', '', ''];
    $export_data[] = ['Total Nilai Stok:', formatCurrency($total_value), '', '', '', '', '', '', '', '', '', ''];
}

// $export_data[] = ['', '', '', '', '', '', '', '', '', '', '', ''];
// $export_data[] = ['Diekspor pada:', formatDateTime(date('Y-m-d H:i:s')), '', '', '', '', '', '', '', '', '', ''];
// $export_data[] = ['Diekspor oleh:', $current_user['nama_lengkap'], '', '', '', '', '', '', '', '', '', ''];

// Generate filename
$filename = 'data_barang_' . date('Y-m-d_H-i-s');
$title = 'Data Barang - ' . APP_NAME;

// Export using new ExcelHelper
ExcelHelper::exportToExcel($export_data, $filename, $headers, $title);
?>