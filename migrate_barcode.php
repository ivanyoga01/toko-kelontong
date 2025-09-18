<?php
/**
 * Database Migration: Add Barcode Support
 * This script adds barcode field to products table for enhanced POS functionality
 */

require_once 'config/database.php';
require_once 'includes/functions.php';

echo "<!DOCTYPE html>
<html>
<head>
    <title>Barcode Migration - Toko Kelontong</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        .success { color: #28a745; margin: 10px 0; padding: 10px; background: #d4edda; border-radius: 5px; }
        .error { color: #dc3545; margin: 10px 0; padding: 10px; background: #f8d7da; border-radius: 5px; }
        .info { color: #17a2b8; margin: 10px 0; padding: 10px; background: #d1ecf1; border-radius: 5px; }
        .warning { color: #856404; margin: 10px 0; padding: 10px; background: #fff3cd; border-radius: 5px; }
        h1 { text-align: center; color: #333; }
        .btn { padding: 10px 20px; margin: 10px 5px; border: none; border-radius: 5px; cursor: pointer; text-decoration: none; display: inline-block; }
        .btn-primary { background: #007bff; color: white; }
        .btn-success { background: #28a745; color: white; }
    </style>
</head>
<body>
    <div class='container'>
        <h1>🛒 Barcode Support Migration</h1>
        <p>Menambahkan dukungan barcode untuk sistem POS yang lebih efisien</p>
";

try {
    $pdo = getDatabase();

    echo "<h2>🔍 Memeriksa struktur database...</h2>";

    // Check if barcode column already exists
    $result = $pdo->query("SHOW COLUMNS FROM products LIKE 'barcode'");
    $barcodeExists = $result->rowCount() > 0;

    if ($barcodeExists) {
        echo "<div class='warning'>⚠️ Kolom barcode sudah ada di tabel products</div>";
    } else {
        echo "<div class='info'>📋 Kolom barcode belum ada, akan ditambahkan...</div>";

        // Add barcode column
        $sql = "ALTER TABLE products ADD COLUMN barcode VARCHAR(50) NULL UNIQUE AFTER kode_barang";
        $pdo->exec($sql);
        echo "<div class='success'>✅ Kolom barcode berhasil ditambahkan ke tabel products</div>";

        // Add index for better performance
        $sql_index = "ALTER TABLE products ADD INDEX idx_barcode (barcode)";
        $pdo->exec($sql_index);
        echo "<div class='success'>✅ Index barcode berhasil ditambahkan untuk performa pencarian</div>";
    }

    // Generate sample barcodes for existing products
    echo "<h2>🏷️ Generating sample barcodes...</h2>";

    $stmt = $pdo->query("SELECT id, kode_barang FROM products WHERE barcode IS NULL OR barcode = ''");
    $products = $stmt->fetchAll();

    if ($products) {
        $stmt_update = $pdo->prepare("UPDATE products SET barcode = ? WHERE id = ?");

        foreach ($products as $product) {
            // Generate EAN-13 style barcode (13 digits)
            // Start with country code (621 for Indonesia) + manufacturer code + product code
            $barcode = '621' . str_pad($product['id'], 4, '0', STR_PAD_LEFT) . '00000';

            // Calculate check digit for EAN-13
            $checkDigit = calculateEAN13CheckDigit($barcode);
            $barcode .= $checkDigit;

            $stmt_update->execute([$barcode, $product['id']]);
        }

        echo "<div class='success'>✅ " . count($products) . " produk berhasil diberi barcode otomatis</div>";
        echo "<div class='info'>📝 Format barcode: EAN-13 (621XXXX000000X) dimana X adalah ID produk dan check digit</div>";
    } else {
        echo "<div class='info'>📝 Semua produk sudah memiliki barcode</div>";
    }

    // Show sample barcodes
    echo "<h2>📊 Sample Barcodes yang Dibuat</h2>";
    $stmt = $pdo->query("SELECT kode_barang, nama_barang, barcode FROM products ORDER BY id LIMIT 5");
    $samples = $stmt->fetchAll();

    if ($samples) {
        echo "<table border='1' style='width: 100%; border-collapse: collapse; margin: 10px 0;'>";
        echo "<tr style='background: #f8f9fa;'><th>Kode Barang</th><th>Nama Produk</th><th>Barcode</th></tr>";
        foreach ($samples as $sample) {
            echo "<tr>";
            echo "<td style='padding: 8px;'>" . htmlspecialchars($sample['kode_barang']) . "</td>";
            echo "<td style='padding: 8px;'>" . htmlspecialchars($sample['nama_barang']) . "</td>";
            echo "<td style='padding: 8px; font-family: monospace;'>" . htmlspecialchars($sample['barcode']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }

    echo "<div class='success'>🎉 Migration berhasil! Barcode support telah diaktifkan.</div>";
    echo "<div class='info'>💡 Fitur baru yang tersedia:
        <ul>
            <li>Input barcode manual di form produk</li>
            <li>Pencarian produk menggunakan barcode scanner</li>
            <li>Barcode scanning di POS untuk menambah produk ke keranjang</li>
            <li>Auto-generate barcode untuk produk baru</li>
        </ul>
    </div>";

} catch (PDOException $e) {
    echo "<div class='error'>❌ Error: " . $e->getMessage() . "</div>";
} catch (Exception $e) {
    echo "<div class='error'>❌ Error: " . $e->getMessage() . "</div>";
}

/**
 * Calculate EAN-13 check digit
 */
function calculateEAN13CheckDigit($barcode) {
    $sum = 0;
    for ($i = 0; $i < 12; $i++) {
        $digit = intval($barcode[$i]);
        if ($i % 2 == 0) {
            $sum += $digit;
        } else {
            $sum += $digit * 3;
        }
    }
    $checkDigit = (10 - ($sum % 10)) % 10;
    return $checkDigit;
}

echo "
        <div style='text-align: center; margin-top: 30px;'>
            <a href='admin/dashboard.php' class='btn btn-primary'>📊 Ke Dashboard Admin</a>
            <a href='kasir/penjualan.php' class='btn btn-success'>🛒 Ke POS Kasir</a>
        </div>
    </div>
</body>
</html>";
?>