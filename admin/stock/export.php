<?php
/**
 * Export Stock Movements to Excel
 * Exports stock movement data using ExcelHelper
 */

require_once '../../includes/admin_auth.php';

// Handle filters (same as index.php)
$search = sanitizeInput($_GET['search'] ?? '');
$product_filter = intval($_GET['product'] ?? 0);
$type_filter = sanitizeInput($_GET['type'] ?? '');
$date_from = sanitizeInput($_GET['date_from'] ?? '');
$date_to = sanitizeInput($_GET['date_to'] ?? '');

// Build query
$where_conditions = [];
$params = [];

if (!empty($search)) {
    $where_conditions[] = "(p.nama_barang LIKE ? OR p.kode_barang LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($product_filter > 0) {
    $where_conditions[] = "sm.product_id = ?";
    $params[] = $product_filter;
}

if (!empty($type_filter)) {
    $where_conditions[] = "sm.tipe = ?";
    $params[] = $type_filter;
}

if (!empty($date_from)) {
    $where_conditions[] = "DATE(sm.created_at) >= ?";
    $params[] = $date_from;
}

if (!empty($date_to)) {
    $where_conditions[] = "DATE(sm.created_at) <= ?";
    $params[] = $date_to;
}

$where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

// Get stock movements data
$sql = "SELECT sm.*,
        p.nama_barang, p.kode_barang, p.satuan,
        t.kode_transaksi,
        u.nama_lengkap as user_name
        FROM stock_movements sm
        LEFT JOIN products p ON sm.product_id = p.id
        LEFT JOIN transactions t ON sm.transaction_id = t.id
        LEFT JOIN users u ON t.user_id = u.id
        $where_clause
        ORDER BY sm.created_at DESC";

$movements = fetchAll($sql, $params);

// Prepare data for export
$export_data = [];
foreach ($movements as $movement) {
    $export_data[] = [
        formatDateTime($movement['created_at']),
        $movement['kode_barang'],
        $movement['nama_barang'],
        ucfirst($movement['tipe']),
        $movement['jumlah'],
        $movement['satuan'],
        $movement['kode_transaksi'] ?? '-',
        $movement['keterangan'],
        $movement['user_name'] ?? 'System'
    ];
}

// Headers
$headers = [
    'tanggal',
    'kode_barang',
    'nama_barang',
    'tipe_pergerakan',
    'jumlah',
    'satuan',
    'kode_transaksi',
    'keterangan',
    'user_kasir'
];

// Add summary data
if (!empty($movements)) {
    // $export_data[] = []; // Empty row
    // $export_data[] = ['=== RINGKASAN ===', '', '', '', '', '', '', '', ''];
    // $export_data[] = ['Total Pergerakan:', count($movements), '', '', '', '', '', '', ''];

    // Count by type
    $type_counts = [];
    foreach ($movements as $movement) {
        $type = $movement['tipe'];
        $type_counts[$type] = ($type_counts[$type] ?? 0) + 1;
    }

    foreach ($type_counts as $type => $count) {
        $export_data[] = [ucfirst($type) . ':', $count . ' pergerakan', '', '', '', '', '', '', ''];
    }

    $export_data[] = []; // Empty row
    $export_data[] = ['Filter yang digunakan:', '', '', '', '', '', '', '', ''];
    if (!empty($search)) $export_data[] = ['Pencarian:', $search, '', '', '', '', '', '', ''];
    if ($product_filter > 0) $export_data[] = ['Barang:', 'ID ' . $product_filter, '', '', '', '', '', '', ''];
    if (!empty($type_filter)) $export_data[] = ['Tipe:', ucfirst($type_filter), '', '', '', '', '', '', ''];
    if (!empty($date_from)) $export_data[] = ['Dari Tanggal:', $date_from, '', '', '', '', '', '', ''];
    if (!empty($date_to)) $export_data[] = ['Sampai Tanggal:', $date_to, '', '', '', '', '', '', ''];

    $export_data[] = []; // Empty row
    $export_data[] = ['Diekspor pada:', formatDateTime(date('Y-m-d H:i:s')), '', '', '', '', '', '', ''];
    $export_data[] = ['Diekspor oleh:', $current_user['nama_lengkap'], '', '', '', '', '', '', ''];
}

// Generate filename
$filename = 'pergerakan_stok_' . date('Y-m-d_H-i-s');
$title = 'Riwayat Pergerakan Stok - ' . APP_NAME;

// Export using ExcelHelper
ExcelHelper::generateTemplate($headers, $export_data, $filename . '.xlsx', $title);
?>