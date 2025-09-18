<?php
/**
 * Export Revenue Report to Excel
 * Exports revenue report using ExcelHelper
 */

require_once '../../includes/admin_auth.php';

// Get parameters
$period_type = sanitizeInput($_GET['period'] ?? 'daily');
$date_from = sanitizeInput($_GET['date_from'] ?? date('Y-m-d', strtotime('-30 days')));
$date_to = sanitizeInput($_GET['date_to'] ?? date('Y-m-d'));

// Validate inputs
if (!in_array($period_type, ['daily', 'weekly', 'monthly'])) {
    $period_type = 'daily';
}
if (!strtotime($date_from)) $date_from = date('Y-m-d', strtotime('-30 days'));
if (!strtotime($date_to)) $date_to = date('Y-m-d');

// Build revenue query based on period type
switch ($period_type) {
    case 'weekly':
        $group_by = "YEARWEEK(t.tanggal_transaksi, 1)";
        $date_format = "CONCAT(YEAR(t.tanggal_transaksi), '-W', LPAD(WEEK(t.tanggal_transaksi, 1), 2, '0'))";
        break;
    case 'monthly':
        $group_by = "DATE_FORMAT(t.tanggal_transaksi, '%Y-%m')";
        $date_format = "DATE_FORMAT(t.tanggal_transaksi, '%Y-%m')";
        break;
    default: // daily
        $group_by = "DATE(t.tanggal_transaksi)";
        $date_format = "DATE(t.tanggal_transaksi)";
        break;
}

// Get revenue data
$revenue_sql = "SELECT
                $date_format as periode,
                COUNT(*) as jumlah_transaksi,
                SUM(t.total) as total_pendapatan,
                AVG(t.total) as rata_rata_transaksi,
                MIN(t.total) as transaksi_terendah,
                MAX(t.total) as transaksi_tertinggi
                FROM transactions t
                WHERE DATE(t.tanggal_transaksi) BETWEEN ? AND ?
                  AND t.status = 'selesai'
                GROUP BY $group_by
                ORDER BY periode";

$revenue_data = fetchAll($revenue_sql, [$date_from, $date_to]);

// Get overall summary
$summary_sql = "SELECT
                COUNT(*) as total_transaksi,
                SUM(total) as total_pendapatan,
                AVG(total) as rata_rata_transaksi,
                MIN(total) as transaksi_terendah,
                MAX(total) as transaksi_tertinggi
                FROM transactions
                WHERE DATE(tanggal_transaksi) BETWEEN ? AND ?
                  AND status = 'selesai'";

$summary = fetchOne($summary_sql, [$date_from, $date_to]);

// Prepare export data
$export_data = [];

// Add header information
$export_data[] = ['LAPORAN PENDAPATAN & OMZET', '', '', '', '', '', ''];
$export_data[] = ['Periode:', ucfirst($period_type), '', '', '', '', ''];
$export_data[] = ['Tanggal:', formatDate($date_from) . ' - ' . formatDate($date_to), '', '', '', '', ''];
$export_data[] = ['Dibuat:', formatDateTime(date('Y-m-d H:i:s')), '', '', '', '', ''];
$export_data[] = ['Dibuat oleh:', $current_user['nama_lengkap'], '', '', '', '', ''];
$export_data[] = ['', '', '', '', '', '', '']; // Empty row

// Add summary
// $export_data[] = ['=== RINGKASAN TOTAL ===', '', '', '', '', '', ''];
$export_data[] = ['Total Transaksi:', $summary['total_transaksi'], '', '', '', '', ''];
$export_data[] = ['Total Pendapatan:', formatCurrency($summary['total_pendapatan']), '', '', '', '', ''];
$export_data[] = ['Rata-rata Transaksi:', formatCurrency($summary['rata_rata_transaksi']), '', '', '', '', ''];
$export_data[] = ['Transaksi Terendah:', formatCurrency($summary['transaksi_terendah']), '', '', '', '', ''];
$export_data[] = ['Transaksi Tertinggi:', formatCurrency($summary['transaksi_tertinggi']), '', '', '', '', ''];
$export_data[] = ['', '', '', '', '', '', '']; // Empty row

// Add detailed revenue data
if (!empty($revenue_data)) {
    // $export_data[] = ['=== DETAIL PENDAPATAN PER ' . strtoupper($period_type) . ' ===', '', '', '', '', '', ''];

    foreach ($revenue_data as $data) {
        $period_label = $data['periode'];

        // Format period label based on type
        if ($period_type === 'weekly') {
            $period_label = 'Minggu ' . substr($data['periode'], -2) . ', ' . substr($data['periode'], 0, 4);
        } elseif ($period_type === 'monthly') {
            $period_label = date('F Y', strtotime($data['periode'] . '-01'));
        } else {
            $period_label = formatDate($data['periode']);
        }

        $export_data[] = [
            $period_label,
            $data['jumlah_transaksi'],
            formatCurrency($data['total_pendapatan']),
            formatCurrency($data['rata_rata_transaksi']),
            formatCurrency($data['transaksi_terendah']),
            formatCurrency($data['transaksi_tertinggi'])
        ];
    }
}

// Headers
$headers = [
    'periode',
    'jumlah_transaksi',
    'total_pendapatan',
    'rata_rata_transaksi',
    'transaksi_terendah',
    'transaksi_tertinggi'
];

// Generate filename and title
$filename = 'laporan_pendapatan_' . $period_type . '_' . $date_from . '_' . $date_to;
$title = 'Laporan Pendapatan & Omzet - ' . APP_NAME;

// Export using ExcelHelper
ExcelHelper::generateTemplate($headers, $export_data, $filename . '.xlsx', $title);
?>