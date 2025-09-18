<?php
/**
 * Export Weekly Report to Excel
 * Exports weekly sales report using ExcelHelper
 */

require_once '../../includes/admin_auth.php';

// Get selected week (default current week)
$selected_week = sanitizeInput($_GET['week'] ?? date('Y-\WW'));

// Validate week format
if (!preg_match('/^\d{4}-W\d{2}$/', $selected_week)) {
    $selected_week = date('Y-\WW');
}

// Calculate week start and end dates
$year = substr($selected_week, 0, 4);
$week = substr($selected_week, -2);
$week_start = date('Y-m-d', strtotime($year . 'W' . $week . '1')); // Monday
$week_end = date('Y-m-d', strtotime($year . 'W' . $week . '7')); // Sunday

// Get week summary
$summary_sql = "SELECT
                COUNT(*) as total_transaksi,
                COUNT(CASE WHEN status = 'selesai' THEN 1 END) as transaksi_selesai,
                COUNT(CASE WHEN status = 'batal' THEN 1 END) as transaksi_batal,
                COALESCE(SUM(CASE WHEN status = 'selesai' THEN total ELSE 0 END), 0) as total_omzet,
                COALESCE(AVG(CASE WHEN status = 'selesai' THEN total END), 0) as rata_rata_transaksi
                FROM transactions
                WHERE DATE(tanggal_transaksi) BETWEEN ? AND ?";

$summary = fetchOne($summary_sql, [$week_start, $week_end]);

// Get daily sales for the week
$daily_sales_sql = "SELECT
                    DATE(tanggal_transaksi) as tanggal,
                    DAYNAME(tanggal_transaksi) as hari,
                    COUNT(*) as jumlah_transaksi,
                    COALESCE(SUM(CASE WHEN status = 'selesai' THEN total ELSE 0 END), 0) as total_penjualan
                    FROM transactions
                    WHERE DATE(tanggal_transaksi) BETWEEN ? AND ?
                    GROUP BY DATE(tanggal_transaksi)
                    ORDER BY tanggal";

$daily_sales = fetchAll($daily_sales_sql, [$week_start, $week_end]);

// Get top products for the week
$top_products_sql = "SELECT p.nama_barang, p.satuan, SUM(td.jumlah) as total_terjual,
                     SUM(td.subtotal) as total_pendapatan
                     FROM transaction_details td
                     JOIN transactions t ON td.transaction_id = t.id
                     JOIN products p ON td.product_id = p.id
                     WHERE DATE(t.tanggal_transaksi) BETWEEN ? AND ? AND t.status = 'selesai'
                     GROUP BY p.id
                     ORDER BY total_terjual DESC
                     LIMIT 20";

$top_products = fetchAll($top_products_sql, [$week_start, $week_end]);

// Prepare export data
$export_data = [];

// Add header information
$export_data[] = ['LAPORAN PENJUALAN MINGGUAN', '', '', '', '', ''];
$export_data[] = ['Periode:', formatDate($week_start) . ' - ' . formatDate($week_end), '', '', '', ''];
$export_data[] = ['Minggu:', $week . ', ' . $year, '', '', '', ''];
$export_data[] = ['Dibuat:', formatDateTime(date('Y-m-d H:i:s')), '', '', '', ''];
$export_data[] = ['Dibuat oleh:', $current_user['nama_lengkap'], '', '', '', ''];
$export_data[] = ['', '', '', '', '', '']; // Empty row

// Add summary
// $export_data[] = ['=== RINGKASAN ===', '', '', '', '', ''];
$export_data[] = ['Total Transaksi:', $summary['total_transaksi'], '', '', '', ''];
$export_data[] = ['Transaksi Selesai:', $summary['transaksi_selesai'], '', '', '', ''];
$export_data[] = ['Transaksi Batal:', $summary['transaksi_batal'], '', '', '', ''];
$export_data[] = ['Total Omzet:', formatCurrency($summary['total_omzet']), '', '', '', ''];
$export_data[] = ['Rata-rata Transaksi:', formatCurrency($summary['rata_rata_transaksi']), '', '', '', ''];
$export_data[] = ['', '', '', '', '', '']; // Empty row

// Add daily sales
if (!empty($daily_sales)) {
    // $export_data[] = ['=== PENJUALAN HARIAN ===', '', '', '', '', ''];

    foreach ($daily_sales as $daily) {
        $export_data[] = [
            formatDate($daily['tanggal']),
            $daily['hari'],
            $daily['jumlah_transaksi'],
            formatCurrency($daily['total_penjualan']),
            '', ''
        ];
    }
    $export_data[] = ['', '', '', '', '', '']; // Empty row
}

// Add top products
if (!empty($top_products)) {
    // $export_data[] = ['=== PRODUK TERLARIS ===', '', '', '', '', ''];

    foreach ($top_products as $product) {
        $export_data[] = [
            $product['nama_barang'],
            $product['satuan'],
            $product['total_terjual'],
            formatCurrency($product['total_pendapatan']),
            '', ''
        ];
    }
}

// Headers
$headers = [
    'tanggal_hari',
    'info',
    'jumlah_transaksi',
    'total_penjualan',
    'extra1',
    'extra2'
];

// Generate filename and title
$filename = 'laporan_mingguan_' . $selected_week;
$title = 'Laporan Penjualan Mingguan - ' . APP_NAME;

// Export using ExcelHelper
ExcelHelper::generateTemplate($headers, $export_data, $filename . '.xlsx', $title);
?>