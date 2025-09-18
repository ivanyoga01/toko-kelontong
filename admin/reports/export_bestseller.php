<?php
/**
 * Export Best Seller Report to Excel
 * Exports best seller report using ExcelHelper
 */

require_once '../../includes/admin_auth.php';

// Get date range (default last 30 days)
$date_from = sanitizeInput($_GET['date_from'] ?? date('Y-m-d', strtotime('-30 days')));
$date_to = sanitizeInput($_GET['date_to'] ?? date('Y-m-d'));

// Validate dates
if (!strtotime($date_from)) $date_from = date('Y-m-d', strtotime('-30 days'));
if (!strtotime($date_to)) $date_to = date('Y-m-d');

// Get top selling products
$top_products_sql = "SELECT p.id, p.kode_barang, p.nama_barang, p.satuan, c.nama_kategori,
                     p.harga_jual, p.stok,
                     SUM(td.jumlah) as total_terjual,
                     SUM(td.subtotal) as total_pendapatan,
                     COUNT(DISTINCT t.id) as frekuensi_transaksi,
                     AVG(td.jumlah) as rata_rata_per_transaksi
                     FROM transaction_details td
                     JOIN transactions t ON td.transaction_id = t.id
                     JOIN products p ON td.product_id = p.id
                     LEFT JOIN categories c ON p.category_id = c.id
                     WHERE DATE(t.tanggal_transaksi) BETWEEN ? AND ?
                       AND t.status = 'selesai'
                     GROUP BY p.id
                     ORDER BY total_terjual DESC
                     LIMIT 50";

$top_products = fetchAll($top_products_sql, [$date_from, $date_to]);

// Get category performance
$category_performance_sql = "SELECT c.nama_kategori,
                             COUNT(DISTINCT p.id) as jumlah_produk,
                             SUM(td.jumlah) as total_terjual,
                             SUM(td.subtotal) as total_pendapatan,
                             COUNT(DISTINCT t.id) as jumlah_transaksi
                             FROM transaction_details td
                             JOIN transactions t ON td.transaction_id = t.id
                             JOIN products p ON td.product_id = p.id
                             LEFT JOIN categories c ON p.category_id = c.id
                             WHERE DATE(t.tanggal_transaksi) BETWEEN ? AND ?
                               AND t.status = 'selesai'
                             GROUP BY c.id
                             ORDER BY total_pendapatan DESC";

$category_performance = fetchAll($category_performance_sql, [$date_from, $date_to]);

// Prepare export data
$export_data = [];

// Add header information
$export_data[] = ['LAPORAN PRODUK TERLARIS', '', '', '', '', '', '', '', ''];
$export_data[] = ['Periode:', formatDate($date_from) . ' - ' . formatDate($date_to), '', '', '', '', '', '', ''];
$export_data[] = ['Dibuat:', formatDateTime(date('Y-m-d H:i:s')), '', '', '', '', '', '', ''];
$export_data[] = ['Dibuat oleh:', $current_user['nama_lengkap'], '', '', '', '', '', '', ''];
$export_data[] = ['', '', '', '', '', '', '', '', '']; // Empty row

// Add top products
if (!empty($top_products)) {
    // $export_data[] = ['=== PRODUK TERLARIS ===', '', '', '', '', '', '', '', ''];

    foreach ($top_products as $index => $product) {
        $export_data[] = [
            $index + 1, // Rank
            $product['kode_barang'],
            $product['nama_barang'],
            $product['nama_kategori'] ?? 'Tanpa Kategori',
            $product['total_terjual'],
            $product['satuan'],
            $product['total_pendapatan'],
            $product['frekuensi_transaksi'],
            $product['stok']
        ];
    }
    $export_data[] = ['', '', '', '', '', '', '', '', '']; // Empty row
}

// Add category performance
if (!empty($category_performance)) {
    // $export_data[] = ['=== PERFORMA KATEGORI ===', '', '', '', '', '', '', '', ''];

    foreach ($category_performance as $category) {
        $export_data[] = [
            $category['nama_kategori'] ?? 'Tanpa Kategori',
            $category['jumlah_produk'],
            $category['total_terjual'],
            $category['total_pendapatan'],
            $category['jumlah_transaksi'],
            '', '', '', ''
        ];
    }
}

// Headers
$headers = [
    'rank',
    'kode_barang',
    'nama_barang',
    'kategori',
    'total_terjual',
    'satuan',
    'total_pendapatan',
    'frekuensi_transaksi',
    'stok'
];

// Generate filename and title
$filename = 'laporan_produk_terlaris_' . $date_from . '_' . $date_to;
$title = 'Laporan Produk Terlaris - ' . APP_NAME;

// Export using ExcelHelper
ExcelHelper::generateTemplate($headers, $export_data, $filename . '.xlsx', $title);
?>