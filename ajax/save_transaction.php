<?php
/**
 * Save Transaction AJAX
 * API untuk menyimpan transaksi dari POS
 */

require_once '../includes/functions.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Check if user is kasir or admin
$user = getCurrentUser();
if (!$user || !in_array($user['role'], ['kasir', 'admin'])) {
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit;
}

// Validate request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

try {
    // Get input data
    $customerData = $_POST['customer'] ?? [];
    $items = $_POST['items'] ?? [];

    // Validate items
    if (empty($items)) {
        echo json_encode(['success' => false, 'message' => 'Keranjang belanja kosong']);
        exit;
    }

    // Begin transaction
    $pdo = getDatabase();
    $pdo->beginTransaction();

    // Generate transaction code
    $transactionCode = generateTransactionCode();

    // Prepare customer data
    $customerId = null;
    $customerName = null;

    if ($customerData['type'] === 'registered' && !empty($customerData['id'])) {
        $customerId = $customerData['id'];
    } elseif ($customerData['type'] === 'guest' && !empty($customerData['name'])) {
        $customerName = $customerData['name'];
    }

    // Calculate totals
    $subtotal = 0;
    foreach ($items as $item) {
        $subtotal += $item['price'] * $item['quantity'];
    }
    $total = $subtotal; // Add tax/discount logic here if needed

    // Insert transaction
    $transactionSql = "INSERT INTO transactions
                       (kode_transaksi, customer_id, customer_name, user_id, tanggal_transaksi, subtotal, total, status)
                       VALUES (?, ?, ?, ?, NOW(), ?, ?, 'selesai')";

    $stmt = $pdo->prepare($transactionSql);
    $stmt->execute([
        $transactionCode,
        $customerId,
        $customerName,
        $user['id'],
        $subtotal,
        $total
    ]);

    $transactionId = $pdo->lastInsertId();

    // Insert transaction details and update stock
    $detailSql = "INSERT INTO transaction_details
                  (transaction_id, product_id, jumlah, harga_satuan, subtotal)
                  VALUES (?, ?, ?, ?, ?)";

    $updateStockSql = "UPDATE products SET stok = stok - ? WHERE id = ?";

    $stockMovementSql = "INSERT INTO stock_movements
                         (product_id, transaction_id, tipe, jumlah, keterangan)
                         VALUES (?, ?, 'keluar', ?, ?)";

    $detailStmt = $pdo->prepare($detailSql);
    $stockStmt = $pdo->prepare($updateStockSql);
    $movementStmt = $pdo->prepare($stockMovementSql);

    foreach ($items as $item) {
        $itemSubtotal = $item['price'] * $item['quantity'];

        // Check stock availability
        $checkStockSql = "SELECT stok FROM products WHERE id = ? AND status = 'aktif'";
        $stockCheck = fetchOne($checkStockSql, [$item['id']]);

        if (!$stockCheck || $stockCheck['stok'] < $item['quantity']) {
            throw new Exception("Stok tidak mencukupi untuk produk: " . $item['name']);
        }

        // Insert transaction detail
        $detailStmt->execute([
            $transactionId,
            $item['id'],
            $item['quantity'],
            $item['price'],
            $itemSubtotal
        ]);

        // Update product stock
        $stockStmt->execute([$item['quantity'], $item['id']]);

        // Record stock movement
        $movementStmt->execute([
            $item['id'],
            $transactionId,
            $item['quantity'],
            "Penjualan - {$transactionCode}"
        ]);
    }

    // Commit transaction
    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Transaksi berhasil disimpan',
        'transaction_id' => $transactionId,
        'transaction_code' => $transactionCode,
        'total' => $total
    ]);

} catch (Exception $e) {
    // Rollback transaction
    if (isset($pdo)) {
        $pdo->rollback();
    }

    error_log("Transaction error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>