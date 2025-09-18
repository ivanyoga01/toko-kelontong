<?php
/**
 * Export Customers to Excel
 * Exports customer data with filters
 */

require_once '../../includes/admin_auth.php';

// Handle filters
$search = sanitizeInput($_GET['search'] ?? '');

// Build query
$where_conditions = [];
$params = [];

if (!empty($search)) {
    $where_conditions[] = "(nama_pelanggan LIKE ? OR no_hp LIKE ? OR alamat LIKE ?)";
    $searchTerm = "%$search%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

$where_clause = '';
if (!empty($where_conditions)) {
    $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
}

// Get customers data with transaction statistics
$sql = "SELECT c.*,
        (SELECT COUNT(*) FROM transactions WHERE customer_id = c.id) as total_transaksi,
        (SELECT COALESCE(SUM(total), 0) FROM transactions WHERE customer_id = c.id AND status = 'selesai') as total_belanja
        FROM customers c
        $where_clause
        ORDER BY c.nama_pelanggan ASC";

$customers = fetchAll($sql, $params);

// Prepare data for export
$export_data = [];
foreach ($customers as $customer) {
    $export_data[] = [
        $customer['nama_pelanggan'],
        $customer['no_hp'] ?: '-',
        $customer['alamat'] ?: '-',
        $customer['total_transaksi'],
        $customer['total_belanja'],
        formatDateTime($customer['created_at']),
        formatDateTime($customer['updated_at'])
    ];
}

// Headers
$headers = [
    'nama_pelanggan',
    'no_hp',
    'alamat',
    'total_transaksi',
    'total_belanja',
    'terdaftar',
    'diupdate'
];

// Add summary row
// $export_data[] = []; // Empty row
// $export_data[] = ['=== RINGKASAN ==='];
// $export_data[] = ['Total Pelanggan:', count($customers)];

if (!empty($customers)) {
    $total_transactions = array_sum(array_column($customers, 'total_transaksi'));
    $total_spending = array_sum(array_column($customers, 'total_belanja'));
    $active_customers = count(array_filter($customers, function($c) {
        return $c['total_transaksi'] > 0;
    }));

    $export_data[] = ['Pelanggan Aktif:', $active_customers];
    $export_data[] = ['Total Transaksi:', $total_transactions];
    $export_data[] = ['Total Belanja:', formatCurrency($total_spending)];

    if ($total_transactions > 0) {
        $avg_spending = $total_spending / $total_transactions;
        $export_data[] = ['Rata-rata per Transaksi:', formatCurrency($avg_spending)];
    }
}

// $export_data[] = [];
// $export_data[] = ['Diekspor pada:', formatDateTime(date('Y-m-d H:i:s'))];
// $export_data[] = ['Diekspor oleh:', $current_user['nama_lengkap']];

// Generate filename
$filename = 'data_pelanggan_' . date('Y-m-d_H-i-s');
$title = 'Data Pelanggan - ' . APP_NAME;

// Export using new ExcelHelper
ExcelHelper::generateTemplate($headers, $export_data, $filename . '.xlsx', $title);
?>