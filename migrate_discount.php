<?php
/**
 * Database Migration: Add Discount/Promotion Support
 * This script adds discount functionality to the POS system
 */

require_once 'config/database.php';
require_once 'includes/functions.php';

echo "<!DOCTYPE html>
<html>
<head>
    <title>Discount System Migration - Toko Kelontong</title>
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
        <h1>💰 Discount & Promotion System Migration</h1>
        <p>Menambahkan sistem diskon dan promosi untuk meningkatkan penjualan</p>
";

try {
    $pdo = getDatabase();

    echo "<h2>🔍 Memeriksa struktur database...</h2>";

    // Check if discount columns already exist in transactions table
    $result = $pdo->query("SHOW COLUMNS FROM transactions LIKE 'discount_%'");
    $discountColumnsExist = $result->rowCount() > 0;

    if ($discountColumnsExist) {
        echo "<div class='warning'>⚠️ Kolom discount sudah ada di tabel transactions</div>";
    } else {
        echo "<div class='info'>📋 Kolom discount belum ada, akan ditambahkan...</div>";

        // Add discount columns to transactions table
        $sql_transactions = "ALTER TABLE transactions
                            ADD COLUMN discount_type ENUM('none', 'percentage', 'fixed', 'item') DEFAULT 'none' AFTER total,
                            ADD COLUMN discount_value DECIMAL(10,2) DEFAULT 0.00 AFTER discount_type,
                            ADD COLUMN discount_amount DECIMAL(15,2) DEFAULT 0.00 AFTER discount_value,
                            ADD COLUMN final_total DECIMAL(15,2) NOT NULL DEFAULT 0.00 AFTER discount_amount";
        $pdo->exec($sql_transactions);
        echo "<div class='success'>✅ Kolom discount berhasil ditambahkan ke tabel transactions</div>";

        // Update existing transactions to set final_total = total for existing records
        $update_sql = "UPDATE transactions SET final_total = total WHERE final_total = 0";
        $pdo->exec($update_sql);
        echo "<div class='success'>✅ Data transaksi existing berhasil diupdate</div>";
    }

    // Create promotions table if not exists
    $promotions_exists = $pdo->query("SHOW TABLES LIKE 'promotions'")->rowCount() > 0;

    if (!$promotions_exists) {
        $sql_promotions = "
        CREATE TABLE promotions (
            id INT PRIMARY KEY AUTO_INCREMENT,
            name VARCHAR(100) NOT NULL,
            description TEXT,
            type ENUM('percentage', 'fixed', 'buy_x_get_y') NOT NULL,
            value DECIMAL(10,2) NOT NULL,
            minimum_purchase DECIMAL(15,2) DEFAULT 0.00,
            maximum_discount DECIMAL(15,2) DEFAULT NULL,
            start_date DATE NOT NULL,
            end_date DATE NOT NULL,
            is_active BOOLEAN DEFAULT TRUE,
            usage_limit INT DEFAULT NULL,
            used_count INT DEFAULT 0,
            created_by INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (created_by) REFERENCES users(id)
        )";
        $pdo->exec($sql_promotions);
        echo "<div class='success'>✅ Tabel promotions berhasil dibuat</div>";

        // Insert sample promotions
        $sample_promotions = [
            ['Member Spesial', 'Diskon 10% untuk member terdaftar', 'percentage', 10.00, 50000.00, 20000.00, '2024-01-01', '2024-12-31'],
            ['Pembelian Grosir', 'Diskon Rp 25.000 untuk pembelian minimal Rp 500.000', 'fixed', 25000.00, 500000.00, NULL, '2024-01-01', '2024-12-31'],
            ['Diskon Hari Ini', 'Diskon 5% untuk semua pembelian hari ini', 'percentage', 5.00, 0.00, 10000.00, date('Y-m-d'), date('Y-m-d')]
        ];

        $stmt = $pdo->prepare("INSERT INTO promotions (name, description, type, value, minimum_purchase, maximum_discount, start_date, end_date, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)");

        foreach ($sample_promotions as $promo) {
            $stmt->execute($promo);
        }
        echo "<div class='success'>✅ Sample promotions berhasil dibuat (3 items)</div>";
    } else {
        echo "<div class='info'>📝 Tabel promotions sudah ada</div>";
    }

    // Create transaction_discounts table if not exists for detailed discount tracking
    $discount_tracking_exists = $pdo->query("SHOW TABLES LIKE 'transaction_discounts'")->rowCount() > 0;

    if (!$discount_tracking_exists) {
        $sql_discount_tracking = "
        CREATE TABLE transaction_discounts (
            id INT PRIMARY KEY AUTO_INCREMENT,
            transaction_id INT NOT NULL,
            promotion_id INT NULL,
            discount_type ENUM('percentage', 'fixed', 'manual') NOT NULL,
            discount_value DECIMAL(10,2) NOT NULL,
            discount_amount DECIMAL(15,2) NOT NULL,
            description VARCHAR(255),
            applied_by INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (transaction_id) REFERENCES transactions(id) ON DELETE CASCADE,
            FOREIGN KEY (promotion_id) REFERENCES promotions(id) ON DELETE SET NULL,
            FOREIGN KEY (applied_by) REFERENCES users(id)
        )";
        $pdo->exec($sql_discount_tracking);
        echo "<div class='success'>✅ Tabel transaction_discounts berhasil dibuat untuk tracking detail diskon</div>";
    } else {
        echo "<div class='info'>📝 Tabel transaction_discounts sudah ada</div>";
    }

    // Show current promotions
    echo "<h2>🎉 Sample Promotions yang Tersedia</h2>";
    $stmt = $pdo->query("SELECT * FROM promotions WHERE is_active = 1 AND end_date >= CURDATE() ORDER BY created_at DESC LIMIT 5");
    $promotions = $stmt->fetchAll();

    if ($promotions) {
        echo "<table border='1' style='width: 100%; border-collapse: collapse; margin: 10px 0;'>";
        echo "<tr style='background: #f8f9fa;'><th>Nama Promosi</th><th>Tipe</th><th>Nilai</th><th>Min. Pembelian</th><th>Berlaku s/d</th></tr>";
        foreach ($promotions as $promo) {
            $value_display = $promo['type'] === 'percentage' ? $promo['value'] . '%' : formatCurrency($promo['value']);
            echo "<tr>";
            echo "<td style='padding: 8px;'>" . htmlspecialchars($promo['name']) . "</td>";
            echo "<td style='padding: 8px;'>" . ucfirst($promo['type']) . "</td>";
            echo "<td style='padding: 8px;'>" . $value_display . "</td>";
            echo "<td style='padding: 8px;'>" . formatCurrency($promo['minimum_purchase']) . "</td>";
            echo "<td style='padding: 8px;'>" . formatDate($promo['end_date']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }

    echo "<div class='success'>🎉 Migration berhasil! Sistem diskon telah diaktifkan.</div>";
    echo "<div class='info'>💡 Fitur baru yang tersedia:
        <ul>
            <li>Diskon manual di POS (persentase atau nominal)</li>
            <li>Sistem promosi otomatis berdasarkan minimum pembelian</li>
            <li>Tracking detail setiap diskon yang diberikan</li>
            <li>Laporan penggunaan diskon dan promosi</li>
            <li>Manajemen promosi dengan periode aktif</li>
        </ul>
    </div>";

} catch (PDOException $e) {
    echo "<div class='error'>❌ Error: " . $e->getMessage() . "</div>";
} catch (Exception $e) {
    echo "<div class='error'>❌ Error: " . $e->getMessage() . "</div>";
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