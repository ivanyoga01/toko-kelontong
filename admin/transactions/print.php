<?php
/**
 * Print Receipt - Admin Enhanced
 * Generate printable receipt for admin transactions management
 */

require_once '../../includes/admin_auth.php';
require_once '../../libraries/receipt_helper.php';

$transaction_id = (int)($_GET['id'] ?? 0);

if (!$transaction_id) {
    die('ID transaksi tidak valid');
}

// Get transaction data
$transaction_sql = "SELECT t.*,
                    CASE
                        WHEN t.customer_id IS NOT NULL THEN c.nama_pelanggan
                        WHEN t.customer_name IS NOT NULL THEN t.customer_name
                        ELSE NULL
                    END as nama_pelanggan,
                    u.nama_lengkap as kasir_nama
                    FROM transactions t
                    LEFT JOIN customers c ON t.customer_id = c.id
                    LEFT JOIN users u ON t.user_id = u.id
                    WHERE t.id = ?";

$transaction = fetchOne($transaction_sql, [$transaction_id]);

if (!$transaction) {
    die('Transaksi tidak ditemukan');
}

// Get transaction details
$details_sql = "SELECT td.*, p.nama_barang, p.satuan
                FROM transaction_details td
                JOIN products p ON td.product_id = p.id
                WHERE td.transaction_id = ?
                ORDER BY p.nama_barang";

$details = fetchAll($details_sql, [$transaction_id]);

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