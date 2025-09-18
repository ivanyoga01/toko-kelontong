<?php
/**
 * Print Receipt - Enhanced
 * Halaman untuk mencetak struk transaksi dengan dukungan thermal printer
 */

require_once '../includes/functions.php';
require_once '../libraries/receipt_helper.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirect(AUTH_URL . 'login.php');
}

$transaction_id = intval($_GET['id'] ?? 0);

if ($transaction_id <= 0) {
    die('ID transaksi tidak valid');
}

// Get transaction data
$sql = "SELECT t.*, u.nama_lengkap as kasir_nama, c.nama_pelanggan
        FROM transactions t
        LEFT JOIN users u ON t.user_id = u.id
        LEFT JOIN customers c ON t.customer_id = c.id
        WHERE t.id = ?";

$transaction = fetchOne($sql, [$transaction_id]);

if (!$transaction) {
    die('Transaksi tidak ditemukan');
}

// Get transaction details
$detail_sql = "SELECT td.*, p.nama_barang, p.satuan
               FROM transaction_details td
               LEFT JOIN products p ON td.product_id = p.id
               WHERE td.transaction_id = ?
               ORDER BY p.nama_barang ASC";

$details = fetchAll($detail_sql, [$transaction_id]);

// Store information (can be configured)
$store_info = [
    'name' => APP_NAME,
    'address' => 'Alamat Toko Kelontong', // Can be configured in config
    'phone' => 'Telp: (021) 12345678'    // Can be configured in config
];

// Check output format
$format = $_GET['format'] ?? 'html';

if ($format === 'text') {
    // Output as plain text for thermal printers
    header('Content-Type: text/plain; charset=utf-8');
    header('Content-Disposition: attachment; filename="struk_' . $transaction['kode_transaksi'] . '.txt"');
    echo generateReceiptText($transaction, $details, $store_info);
    exit;
}

// Default HTML output
echo generateReceiptHTML($transaction, $details, $store_info);