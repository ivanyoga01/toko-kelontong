<?php
/**
 * Export Transactions to Excel
 * Exports transaction data based on filters using ExcelHelper
 */

require_once '../../includes/admin_auth.php';

// Handle filters (same as index.php)
$search = sanitizeInput($_GET['search'] ?? '');
$date_from = sanitizeInput($_GET['date_from'] ?? '');
$date_to = sanitizeInput($_GET['date_to'] ?? '');
$status_filter = sanitizeInput($_GET['status'] ?? '');

// Build query
$where_conditions = [];
$params = [];

if (!empty($search)) {
    $where_conditions[] = "(t.kode_transaksi LIKE ? OR c.nama_pelanggan LIKE ? OR t.customer_name LIKE ? OR u.nama_lengkap LIKE ?)";
    $searchTerm = "%$search%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

if (!empty($date_from)) {
    $where_conditions[] = "DATE(t.tanggal_transaksi) >= ?";
    $params[] = $date_from;
}

if (!empty($date_to)) {
    $where_conditions[] = "DATE(t.tanggal_transaksi) <= ?";
    $params[] = $date_to;
}

if (!empty($status_filter)) {
    $where_conditions[] = "t.status = ?";
    $params[] = $status_filter;
}

$where_clause = '';
if (!empty($where_conditions)) {
    $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
}

// Get transactions data
$sql = "SELECT t.kode_transaksi,
        t.tanggal_transaksi,
        CASE
            WHEN t.customer_id IS NOT NULL THEN c.nama_pelanggan
            WHEN t.customer_name IS NOT NULL THEN CONCAT('Guest: ', t.customer_name)
            ELSE 'Pelanggan Umum'
        END as nama_pelanggan,
        u.nama_lengkap as kasir_nama,
        t.subtotal,
        t.total,
        t.status,
        (SELECT COUNT(*) FROM transaction_details WHERE transaction_id = t.id) as jumlah_item
        FROM transactions t
        LEFT JOIN customers c ON t.customer_id = c.id
        LEFT JOIN users u ON t.user_id = u.id
        $where_clause
        ORDER BY t.tanggal_transaksi DESC";

$transactions = fetchAll($sql, $params);

// Prepare data for export
$export_data = [];
foreach ($transactions as $transaction) {
    $export_data[] = [
        $transaction['kode_transaksi'],
        formatDateTime($transaction['tanggal_transaksi']),
        $transaction['nama_pelanggan'],
        $transaction['kasir_nama'],
        $transaction['jumlah_item'],
        $transaction['subtotal'],
        $transaction['total'],
        ucfirst($transaction['status'])
    ];
}

// Headers
$headers = [
    'kode_transaksi',
    'tanggal',
    'pelanggan',
    'kasir',
    'jumlah_item',
    'subtotal',
    'total',
    'status'
];

// Add summary rows
// $export_data[] = []; // Empty row
// $export_data[] = ['=== RINGKASAN ===', '', '', '', '', '', '', ''];
// $export_data[] = ['Total Transaksi:', count($transactions), '', '', '', '', '', ''];

if (!empty($transactions)) {
    $total_revenue = array_sum(array_column($transactions, 'total'));
    $avg_transaction = $total_revenue / count($transactions);

    $export_data[] = ['Total Omzet:', formatCurrency($total_revenue), '', '', '', '', '', ''];
    $export_data[] = ['Rata-rata Transaksi:', formatCurrency($avg_transaction), '', '', '', '', '', ''];
}

$export_data[] = ['', '', '', '', '', '', '', ''];
$export_data[] = ['Diekspor pada:', formatDateTime(date('Y-m-d H:i:s')), '', '', '', '', '', ''];
$export_data[] = ['Diekspor oleh:', $current_user['nama_lengkap'], '', '', '', '', '', ''];

// Generate filename
$filename = 'transaksi_' . date('Y-m-d_H-i-s');
$title = 'Data Transaksi - ' . APP_NAME;

// Export using new ExcelHelper
ExcelHelper::generateTemplate($headers, $export_data, $filename . '.xlsx', $title);