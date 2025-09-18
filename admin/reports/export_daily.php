<?php
/**
 * Export Daily Report to Excel
 * Exports daily sales report using ExcelHelper
 */

require_once '../../includes/admin_auth.php';

// Get selected date
$selected_date = sanitizeInput($_GET['date'] ?? date('Y-m-d'));

// Validate date
if (!strtotime($selected_date)) {
    $selected_date = date('Y-m-d');
}

// Get daily summary
$summary_sql = "SELECT
                COUNT(*) as total_transaksi,
                COUNT(CASE WHEN status = 'selesai' THEN 1 END) as transaksi_selesai,
                COUNT(CASE WHEN status = 'batal' THEN 1 END) as transaksi_batal,
                COALESCE(SUM(CASE WHEN status = 'selesai' THEN total ELSE 0 END), 0) as total_omzet,
                COALESCE(AVG(CASE WHEN status = 'selesai' THEN total END), 0) as rata_rata_transaksi
                FROM transactions
                WHERE DATE(tanggal_transaksi) = ?";

$summary = fetchOne($summary_sql, [$selected_date]);

// Get daily transactions
$transactions_sql = "SELECT t.kode_transaksi,
                     t.tanggal_transaksi,
                     CASE
                         WHEN t.customer_id IS NOT NULL THEN c.nama_pelanggan
                         WHEN t.customer_name IS NOT NULL THEN CONCAT('Guest: ', t.customer_name)
                         ELSE 'Pelanggan Umum'
                     END as nama_pelanggan,
                     u.nama_lengkap as kasir_nama,
                     t.total,
                     t.status,
                     (SELECT COUNT(*) FROM transaction_details WHERE transaction_id = t.id) as jumlah_item
                     FROM transactions t
                     LEFT JOIN customers c ON t.customer_id = c.id
                     LEFT JOIN users u ON t.user_id = u.id
                     WHERE DATE(t.tanggal_transaksi) = ?
                     ORDER BY t.tanggal_transaksi";

$transactions = fetchAll($transactions_sql, [$selected_date]);

// Get top products
$top_products_sql = "SELECT p.nama_barang, p.satuan, SUM(td.jumlah) as total_terjual,
                     SUM(td.subtotal) as total_pendapatan
                     FROM transaction_details td
                     JOIN transactions t ON td.transaction_id = t.id
                     JOIN products p ON td.product_id = p.id
                     WHERE DATE(t.tanggal_transaksi) = ? AND t.status = 'selesai'
                     GROUP BY p.id
                     ORDER BY total_terjual DESC";

$top_products = fetchAll($top_products_sql, [$selected_date]);

// Prepare export data
$export_data = [];

// Add header information
$export_data[] = ['LAPORAN PENJUALAN HARIAN', '', '', '', '', '', ''];
$export_data[] = ['Tanggal:', formatDate($selected_date), '', '', '', '', ''];
$export_data[] = ['Dibuat:', formatDateTime(date('Y-m-d H:i:s')), '', '', '', '', ''];
$export_data[] = ['Dibuat oleh:', $current_user['nama_lengkap'], '', '', '', '', ''];
$export_data[] = ['', '', '', '', '', '', '']; // Empty row

// Add summary
// $export_data[] = ['=== RINGKASAN ===', '', '', '', '', '', ''];
$export_data[] = ['Total Transaksi:', $summary['total_transaksi'], '', '', '', '', ''];
$export_data[] = ['Transaksi Selesai:', $summary['transaksi_selesai'], '', '', '', '', ''];
$export_data[] = ['Transaksi Batal:', $summary['transaksi_batal'], '', '', '', '', ''];
$export_data[] = ['Total Omzet:', formatCurrency($summary['total_omzet']), '', '', '', '', ''];
$export_data[] = ['Rata-rata Transaksi:', formatCurrency($summary['rata_rata_transaksi']), '', '', '', '', ''];
$export_data[] = ['', '', '', '', '', '', '']; // Empty row

// Add transactions
if (!empty($transactions)) {
    // $export_data[] = ['=== DETAIL TRANSAKSI ===', '', '', '', '', '', ''];

    // Transaction data
    foreach ($transactions as $transaction) {
        $export_data[] = [
            $transaction['kode_transaksi'],
            formatDateTime($transaction['tanggal_transaksi']),
            $transaction['nama_pelanggan'],
            $transaction['kasir_nama'],
            $transaction['jumlah_item'],
            $transaction['total'],
            ucfirst($transaction['status'])
        ];
    }
    $export_data[] = ['', '', '', '', '', '', '']; // Empty row
}

// Add top products
if (!empty($top_products)) {
    // $export_data[] = ['=== PRODUK TERLARIS ===', '', '', '', '', '', ''];

    foreach ($top_products as $product) {
        $export_data[] = [
            $product['nama_barang'],
            $product['satuan'],
            $product['total_terjual'],
            formatCurrency($product['total_pendapatan']),
            '', '', ''
        ];
    }
}

// Headers
$headers = [
    'kode_transaksi',
    'waktu',
    'pelanggan',
    'kasir',
    'jumlah_item',
    'total',
    'status'
];

// Generate filename and title
$filename = 'laporan_harian_' . $selected_date;
$title = 'Laporan Penjualan Harian - ' . APP_NAME;

// Export using ExcelHelper
ExcelHelper::generateTemplate($headers, $export_data, $filename . '.xlsx', $title);
?>