<?php
/**
 * Stock Adjustment Handler
 * Handles manual stock adjustments
 */

require_once '../../includes/admin_auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    setFlashMessage('error', 'Method tidak diizinkan');
    redirect('index.php');
}

try {
    $product_id = intval($_POST['product_id'] ?? 0);
    $adjustment_type = sanitizeInput($_POST['adjustment_type'] ?? '');
    $jumlah = intval($_POST['jumlah'] ?? 0);
    $keterangan = sanitizeInput($_POST['keterangan'] ?? '');

    // Validation
    if ($product_id <= 0) {
        throw new Exception('Barang harus dipilih');
    }

    if (!in_array($adjustment_type, ['masuk', 'keluar', 'set'])) {
        throw new Exception('Tipe penyesuaian tidak valid');
    }

    if ($jumlah <= 0) {
        throw new Exception('Jumlah harus lebih dari 0');
    }

    if (empty($keterangan)) {
        throw new Exception('Keterangan harus diisi');
    }

    // Get current product data
    $product_sql = "SELECT * FROM products WHERE id = ?";
    $product = fetchOne($product_sql, [$product_id]);

    if (!$product) {
        throw new Exception('Barang tidak ditemukan');
    }

    $current_stock = $product['stok'];
    $new_stock = $current_stock;
    $movement_qty = 0;
    $movement_type = '';

    // Calculate new stock based on adjustment type
    switch ($adjustment_type) {
        case 'masuk':
            $new_stock = $current_stock + $jumlah;
            $movement_qty = $jumlah;
            $movement_type = 'masuk';
            break;

        case 'keluar':
            if ($jumlah > $current_stock) {
                throw new Exception('Jumlah yang akan dikurangi tidak boleh lebih dari stok saat ini');
            }
            $new_stock = $current_stock - $jumlah;
            $movement_qty = $jumlah; // Store as positive value
            $movement_type = 'keluar';
            break;

        case 'set':
            $difference = $jumlah - $current_stock;
            $new_stock = $jumlah;
            $movement_qty = $difference;
            $movement_type = 'penyesuaian';
            break;
    }

    if ($new_stock < 0) {
        throw new Exception('Stok tidak boleh menjadi negatif');
    }

    // Start transaction
    $pdo = getDatabase();
    $pdo->beginTransaction();

    try {
        // Update product stock
        $update_sql = "UPDATE products SET stok = ?, updated_at = NOW() WHERE id = ?";
        $stmt = $pdo->prepare($update_sql);
        $stmt->execute([$new_stock, $product_id]);

        // Record stock movement
        $movement_sql = "INSERT INTO stock_movements (product_id, tipe, jumlah, keterangan, created_at)
                        VALUES (?, ?, ?, ?, NOW())";
        $stmt = $pdo->prepare($movement_sql);
        $stmt->execute([$product_id, $movement_type, $movement_qty, $keterangan]);

        $pdo->commit();

        // Success message
        $action_text = [
            'masuk' => 'ditambahkan',
            'keluar' => 'dikurangi',
            'set' => 'disesuaikan'
        ];

        setFlashMessage('success', "Stok barang {$product['nama_barang']} berhasil {$action_text[$adjustment_type]}. Stok sekarang: {$new_stock} {$product['satuan']}");

    } catch (Exception $e) {
        $pdo->rollBack();
        throw new Exception('Gagal menyimpan penyesuaian stok: ' . $e->getMessage());
    }

} catch (Exception $e) {
    setFlashMessage('error', $e->getMessage());
}

redirect('index.php');