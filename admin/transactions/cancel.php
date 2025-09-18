<?php
/**
 * Cancel Transaction AJAX Handler
 * Handles transaction cancellation and stock restoration
 */

require_once '../../includes/admin_auth.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// Optional CSRF verification (if implemented in other parts)
// Uncomment if CSRF is required:
// if (isset($_POST['csrf_token']) && !verifyCSRFToken($_POST['csrf_token'])) {
//     echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
//     exit;
// }

try {
    $transaction_id = (int)($_POST['id'] ?? 0);

    if (!$transaction_id) {
        echo json_encode(['success' => false, 'message' => 'ID transaksi tidak valid']);
        exit;
    }

    // Check if transaction exists and can be cancelled
    $check_sql = "SELECT id, kode_transaksi, status FROM transactions WHERE id = ?";
    $transaction = fetchOne($check_sql, [$transaction_id]);

    if (!$transaction) {
        echo json_encode(['success' => false, 'message' => 'Transaksi tidak ditemukan']);
        exit;
    }

    if ($transaction['status'] !== 'selesai') {
        echo json_encode(['success' => false, 'message' => 'Hanya transaksi selesai yang dapat dibatalkan']);
        exit;
    }

    // Begin database transaction
    $pdo = getDatabase();
    $pdo->beginTransaction();

    // Log start of cancellation process
    error_log("Starting transaction cancellation for ID: $transaction_id");

    // Get transaction details for stock restoration
    $details_sql = "SELECT product_id, jumlah FROM transaction_details WHERE transaction_id = ?";
    $details_stmt = $pdo->prepare($details_sql);
    $details_stmt->execute([$transaction_id]);
    $details = $details_stmt->fetchAll(PDO::FETCH_ASSOC);

    error_log("Found " . count($details) . " transaction details to restore");

    // Restore stock for each product
    $restore_stock_sql = "UPDATE products SET stok = stok + ? WHERE id = ?";
    $stock_movement_sql = "INSERT INTO stock_movements (product_id, transaction_id, tipe, jumlah, keterangan, created_at) VALUES (?, ?, 'masuk', ?, ?, NOW())";

    $restore_stmt = $pdo->prepare($restore_stock_sql);
    $movement_stmt = $pdo->prepare($stock_movement_sql);

    foreach ($details as $detail) {
        // Restore stock
        $restore_stmt->execute([$detail['jumlah'], $detail['product_id']]);

        // Record stock movement
        $movement_stmt->execute([
            $detail['product_id'],
            $transaction_id,
            $detail['jumlah'],
            "Pembatalan transaksi - {$transaction['kode_transaksi']}"
        ]);
    }

    // Update transaction status
    $cancel_sql = "UPDATE transactions SET status = 'batal' WHERE id = ?";
    $cancel_stmt = $pdo->prepare($cancel_sql);
    $cancel_result = $cancel_stmt->execute([$transaction_id]);

    error_log("Transaction status update result: " . ($cancel_result ? 'success' : 'failed'));

    // Commit transaction
    $pdo->commit();

    error_log("Transaction cancellation completed successfully for ID: $transaction_id");

    echo json_encode([
        'success' => true,
        'message' => 'Transaksi berhasil dibatalkan dan stok telah dikembalikan'
    ]);

} catch (Exception $e) {
    // Rollback transaction
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollback();
    }

    // Log detailed error information
    $error_details = [
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString()
    ];

    error_log("Cancel transaction error: " . json_encode($error_details));

    // In development mode, show detailed error
    if (defined('DEVELOPMENT_MODE') && DEVELOPMENT_MODE) {
        echo json_encode([
            'success' => false,
            'message' => 'Error: ' . $e->getMessage(),
            'debug' => $error_details
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Terjadi kesalahan saat membatalkan transaksi'
        ]);
    }
}
?>