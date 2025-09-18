<?php
/**
 * Export Monthly Report to Excel
 * Exports monthly sales report using ExcelHelper
 */

require_once '../../includes/admin_auth.php';

// Get selected month (default current month)
$selected_month = sanitizeInput($_GET['month'] ?? date('Y-m'));

// Validate month
if (!preg_match('/^\d{4}-\d{2}$/', $selected_month)) {
    $selected_month = date('Y-m');
}

// Get month summary
$summary_sql = "SELECT
                COUNT(*) as total_transaksi,
                COUNT(CASE WHEN status = 'selesai' THEN 1 END) as transaksi_selesai,
                COUNT(CASE WHEN status = 'batal' THEN 1 END) as transaksi_batal,
                COALESCE(SUM(CASE WHEN status = 'selesai' THEN total ELSE 0 END), 0) as total_omzet,
                COALESCE(AVG(CASE WHEN status = 'selesai' THEN total END), 0) as rata_rata_transaksi
                FROM transactions
                WHERE DATE_FORMAT(tanggal_transaksi, '%Y-%m') = ?";

$summary = fetchOne($summary_sql, [$selected_month]);

// Get daily sales for the month
$daily_sales_sql = "SELECT
                    DATE(tanggal_transaksi) as tanggal,
                    COUNT(*) as jumlah_transaksi,
                    COALESCE(SUM(CASE WHEN status = 'selesai' THEN total ELSE 0 END), 0) as total_penjualan
                    FROM transactions
                    WHERE DATE_FORMAT(tanggal_transaksi, '%Y-%m') = ?
                    GROUP BY DATE(tanggal_transaksi)
                    ORDER BY tanggal";

$daily_sales = fetchAll($daily_sales_sql, [$selected_month]);

// Get top products for the month
$top_products_sql = "SELECT p.nama_barang, p.satuan, SUM(td.jumlah) as total_terjual,
                     SUM(td.subtotal) as total_pendapatan,
                     COUNT(DISTINCT t.id) as frekuensi_transaksi
                     FROM transaction_details td
                     JOIN transactions t ON td.transaction_id = t.id
                     JOIN products p ON td.product_id = p.id
                     WHERE DATE_FORMAT(t.tanggal_transaksi, '%Y-%m') = ? AND t.status = 'selesai'
                     GROUP BY p.id
                     ORDER BY total_terjual DESC
                     LIMIT 25";

$top_products = fetchAll($top_products_sql, [$selected_month]);

// Get top customers
$top_customers_sql = "SELECT
                      CASE
                          WHEN t.customer_id IS NOT NULL THEN c.nama_pelanggan
                          WHEN t.customer_name IS NOT NULL THEN CONCAT('Guest: ', t.customer_name)
                          ELSE 'Pelanggan Umum'
                      END as nama_pelanggan,
                      COUNT(*) as jumlah_transaksi,
                      SUM(t.total) as total_belanja
                      FROM transactions t
                      LEFT JOIN customers c ON t.customer_id = c.id
                      WHERE DATE_FORMAT(t.tanggal_transaksi, '%Y-%m') = ? AND t.status = 'selesai'
                        AND (t.customer_id IS NOT NULL OR t.customer_name IS NOT NULL)
                      GROUP BY t.customer_id, t.customer_name
                      ORDER BY total_belanja DESC
                      LIMIT 15";

$top_customers = fetchAll($top_customers_sql, [$selected_month]);

// Prepare export data
$export_data = [];

// Add header information
$export_data[] = ['LAPORAN PENJUALAN BULANAN', '', '', '', '', '', ''];
$export_data[] = ['Bulan:', date('F Y', strtotime($selected_month . '-01')), '', '', '', '', ''];
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

// Add daily sales summary
if (!empty($daily_sales)) {
    // $export_data[] = ['=== PENJUALAN HARIAN ===', '', '', '', '', '', ''];

    foreach ($daily_sales as $daily) {
        $export_data[] = [
            formatDate($daily['tanggal']),
            $daily['jumlah_transaksi'],
            formatCurrency($daily['total_penjualan']),
            '', '', '', ''
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
            $product['total_terjual'],
            $product['satuan'],
            $product['frekuensi_transaksi'],
            formatCurrency($product['total_pendapatan']),
            '', ''
        ];
    }
    $export_data[] = ['', '', '', '', '', '', '']; // Empty row
}

// Add top customers
if (!empty($top_customers)) {
    // $export_data[] = ['=== PELANGGAN TERBAIK ===', '', '', '', '', '', ''];

    foreach ($top_customers as $customer) {
        $export_data[] = [
            $customer['nama_pelanggan'],
            $customer['jumlah_transaksi'],
            formatCurrency($customer['total_belanja']),
            '', '', '', ''
        ];
    }
}

// Headers
$headers = [
    'item',
    'jumlah',
    'nilai',
    'frekuensi',
    'total',
    'extra1',
    'extra2'
];

// Generate filename and title
$filename = 'laporan_bulanan_' . $selected_month;
$title = 'Laporan Penjualan Bulanan - ' . APP_NAME;

// Export using ExcelHelper
ExcelHelper::generateTemplate($headers, $export_data, $filename . '.xlsx', $title);
?>