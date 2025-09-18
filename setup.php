<?php
/**
 * SETUP DATABASE TOKO KELONTONG
 * File untuk inisialisasi database dan seeder data
 * Akses: http://localhost/toko-kelontong/setup.php
 */

// Konfigurasi database
$host = 'localhost';
$dbname = 'toko_kelontong';
$username = 'root';
$password = '';

echo "<!DOCTYPE html>";
echo "<html lang='id'>";
echo "<head>";
echo "<meta charset='UTF-8'>";
echo "<meta name='viewport' content='width=device-width, initial-scale=1.0'>";
echo "<title>Setup Database - Toko Kelontong</title>";
echo "<style>";
echo "body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; }";
echo ".success { color: green; background: #d4edda; padding: 10px; border-radius: 5px; margin: 10px 0; }";
echo ".error { color: red; background: #f8d7da; padding: 10px; border-radius: 5px; margin: 10px 0; }";
echo ".info { color: blue; background: #d1ecf1; padding: 10px; border-radius: 5px; margin: 10px 0; }";
echo "</style>";
echo "</head>";
echo "<body>";

echo "<h1>🏪 Setup Database Toko Kelontong</h1>";
echo "<p>Proses instalasi dan inisialisasi database akan dimulai...</p>";

try {
    // Koneksi ke MySQL (tanpa database)
    $pdo = new PDO("mysql:host=$host", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "<div class='success'>✅ Koneksi ke MySQL berhasil</div>";

    // Drop database jika sudah ada (untuk testing)
    $pdo->exec("DROP DATABASE IF EXISTS $dbname");
    echo "<div class='info'>🗑️ Database lama dihapus (jika ada)</div>";

    // Buat database baru
    $pdo->exec("CREATE DATABASE $dbname CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "<div class='success'>✅ Database '$dbname' berhasil dibuat</div>";

    // Koneksi ke database yang baru dibuat
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 1. Tabel users
    $sql_users = "
    CREATE TABLE users (
        id INT PRIMARY KEY AUTO_INCREMENT,
        username VARCHAR(50) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        nama_lengkap VARCHAR(100) NOT NULL,
        role ENUM('admin', 'kasir') NOT NULL,
        status ENUM('aktif', 'nonaktif') DEFAULT 'aktif',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    $pdo->exec($sql_users);
    echo "<div class='success'>✅ Tabel 'users' berhasil dibuat</div>";

    // 2. Tabel categories
    $sql_categories = "
    CREATE TABLE categories (
        id INT PRIMARY KEY AUTO_INCREMENT,
        nama_kategori VARCHAR(100) NOT NULL,
        deskripsi TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $pdo->exec($sql_categories);
    echo "<div class='success'>✅ Tabel 'categories' berhasil dibuat</div>";

    // 3. Tabel products
    $sql_products = "
    CREATE TABLE products (
        id INT PRIMARY KEY AUTO_INCREMENT,
        kode_barang VARCHAR(20) UNIQUE NOT NULL,
        barcode VARCHAR(20) UNIQUE NULL,
        nama_barang VARCHAR(200) NOT NULL,
        category_id INT,
        harga_beli DECIMAL(15,2) NOT NULL,
        harga_jual DECIMAL(15,2) NOT NULL,
        stok INT DEFAULT 0,
        satuan VARCHAR(20) NOT NULL,
        gambar VARCHAR(255),
        deskripsi TEXT,
        status ENUM('aktif', 'nonaktif') DEFAULT 'aktif',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
    )";
    $pdo->exec($sql_products);
    echo "<div class='success'>✅ Tabel 'products' berhasil dibuat</div>";

    // 4. Tabel customers
    $sql_customers = "
    CREATE TABLE customers (
        id INT PRIMARY KEY AUTO_INCREMENT,
        nama_pelanggan VARCHAR(100) NOT NULL,
        no_hp VARCHAR(15),
        alamat TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    $pdo->exec($sql_customers);
    echo "<div class='success'>✅ Tabel 'customers' berhasil dibuat</div>";

    // 5. Tabel transactions
    $sql_transactions = "
    CREATE TABLE transactions (
        id INT PRIMARY KEY AUTO_INCREMENT,
        kode_transaksi VARCHAR(20) UNIQUE NOT NULL,
        customer_id INT NULL,
        customer_name VARCHAR(100) NULL,
        user_id INT NOT NULL,
        tanggal_transaksi DATETIME NOT NULL,
        subtotal DECIMAL(15,2) NOT NULL,
        total DECIMAL(15,2) NOT NULL,
        status ENUM('selesai', 'batal') DEFAULT 'selesai',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE SET NULL,
        FOREIGN KEY (user_id) REFERENCES users(id)
    )";
    $pdo->exec($sql_transactions);
    echo "<div class='success'>✅ Tabel 'transactions' berhasil dibuat</div>";

    // 6. Tabel transaction_details
    $sql_transaction_details = "
    CREATE TABLE transaction_details (
        id INT PRIMARY KEY AUTO_INCREMENT,
        transaction_id INT NOT NULL,
        product_id INT NOT NULL,
        jumlah INT NOT NULL,
        harga_satuan DECIMAL(15,2) NOT NULL,
        subtotal DECIMAL(15,2) NOT NULL,
        FOREIGN KEY (transaction_id) REFERENCES transactions(id) ON DELETE CASCADE,
        FOREIGN KEY (product_id) REFERENCES products(id)
    )";
    $pdo->exec($sql_transaction_details);
    echo "<div class='success'>✅ Tabel 'transaction_details' berhasil dibuat</div>";

    // 7. Tabel stock_movements
    $sql_stock_movements = "
    CREATE TABLE stock_movements (
        id INT PRIMARY KEY AUTO_INCREMENT,
        product_id INT NOT NULL,
        transaction_id INT NULL,
        tipe ENUM('masuk', 'keluar', 'penyesuaian') NOT NULL,
        jumlah INT NOT NULL,
        keterangan VARCHAR(255),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (product_id) REFERENCES products(id),
        FOREIGN KEY (transaction_id) REFERENCES transactions(id) ON DELETE SET NULL
    )";
    $pdo->exec($sql_stock_movements);
    echo "<div class='success'>✅ Tabel 'stock_movements' berhasil dibuat</div>";

    // 8. Tabel import_logs
    $sql_import_logs = "
    CREATE TABLE import_logs (
        id INT PRIMARY KEY AUTO_INCREMENT,
        type ENUM('products', 'customers') NOT NULL,
        filename VARCHAR(255) NOT NULL,
        total_rows INT NOT NULL,
        success_rows INT NOT NULL,
        failed_rows INT NOT NULL,
        user_id INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id)
    )";
    $pdo->exec($sql_import_logs);
    echo "<div class='success'>✅ Tabel 'import_logs' berhasil dibuat</div>";

    echo "<h2>🌱 Mengisi Data Seeder...</h2>";

    // Insert default users
    $password_admin = password_hash('admin123', PASSWORD_DEFAULT);
    $password_kasir = password_hash('kasir123', PASSWORD_DEFAULT);

    $stmt = $pdo->prepare("
        INSERT INTO users (username, password, nama_lengkap, role) VALUES
        ('admin', ?, 'Administrator', 'admin'),
        ('kasir1', ?, 'Kasir Satu', 'kasir')
    ");
    $stmt->execute([$password_admin, $password_kasir]);
    echo "<div class='success'>✅ Default users berhasil dibuat</div>";

    // Insert categories
    $stmt = $pdo->prepare("
        INSERT INTO categories (nama_kategori, deskripsi) VALUES
        ('Makanan & Minuman', 'Kategori untuk makanan dan minuman'),
        ('Sembako', 'Kebutuhan pokok sehari-hari'),
        ('Peralatan Rumah Tangga', 'Peralatan untuk keperluan rumah tangga'),
        ('Obat-obatan', 'Obat-obatan dan produk kesehatan'),
        ('Lain-lain', 'Kategori produk lainnya')
    ");
    $stmt->execute();
    echo "<div class='success'>✅ Default categories berhasil dibuat</div>";

    // Insert sample products
    $products = [
        ['BRG001', 'Beras Premium 5kg', 1, 45000, 50000, 50, 'kg', 'Beras berkualitas premium'],
        ['BRG002', 'Minyak Goreng 1L', 1, 12000, 15000, 30, 'botol', 'Minyak goreng kelapa sawit'],
        ['BRG003', 'Gula Pasir 1kg', 2, 12000, 14000, 25, 'kg', 'Gula pasir putih'],
        ['BRG004', 'Tepung Terigu 1kg', 2, 8000, 10000, 40, 'kg', 'Tepung terigu protein sedang'],
        ['BRG005', 'Sabun Mandi', 3, 3000, 5000, 60, 'pcs', 'Sabun mandi antibakteri'],
        ['BRG006', 'Shampo Sachet', 3, 500, 1000, 100, 'sachet', 'Shampo dalam kemasan sachet'],
        ['BRG007', 'Paracetamol', 4, 2000, 3000, 20, 'strip', 'Obat penurun demam'],
        ['BRG008', 'Vitamin C', 4, 5000, 7500, 15, 'botol', 'Suplemen vitamin C'],
        ['BRG009', 'Kopi Sachet', 1, 1000, 1500, 80, 'sachet', 'Kopi instan 3 in 1'],
        ['BRG010', 'Teh Celup', 1, 3000, 4000, 35, 'kotak', 'Teh celup kemasan kotak']
    ];

    $stmt = $pdo->prepare("
        INSERT INTO products (kode_barang, barcode, nama_barang, category_id, harga_beli, harga_jual, stok, satuan, deskripsi)
        VALUES (?, NULL, ?, ?, ?, ?, ?, ?, ?)
    ");

    foreach ($products as $product) {
        $stmt->execute($product);
    }
    echo "<div class='success'>✅ Sample products berhasil dibuat (10 items)</div>";

    // Insert sample customers
    $customers = [
        ['Budi Santoso', '081234567890', 'Jl. Merdeka No. 123'],
        ['Siti Nurhaliza', '081234567891', 'Jl. Sudirman No. 456'],
        ['Ahmad Fauzi', '081234567892', 'Jl. Gatot Subroto No. 789'],
        ['Dewi Sartika', '081234567893', 'Jl. Diponegoro No. 321'],
        ['Rudi Hermawan', '081234567894', 'Jl. Ahmad Yani No. 654']
    ];

    $stmt = $pdo->prepare("
        INSERT INTO customers (nama_pelanggan, no_hp, alamat) VALUES (?, ?, ?)
    ");

    foreach ($customers as $customer) {
        $stmt->execute($customer);
    }
    echo "<div class='success'>✅ Sample customers berhasil dibuat (5 customers)</div>";

    echo "<h2>🎉 Setup Berhasil!</h2>";
    echo "<div class='success'>";
    echo "<h3>Informasi Login:</h3>";
    echo "<p><strong>Admin:</strong><br>";
    echo "Username: admin<br>";
    echo "Password: admin123</p>";
    echo "<p><strong>Kasir:</strong><br>";
    echo "Username: kasir1<br>";
    echo "Password: kasir123</p>";
    echo "</div>";

    echo "<div class='info'>";
    echo "<h3>Langkah Selanjutnya:</h3>";
    echo "<ol>";
    echo "<li>Akses: <a href='http://localhost/toko-kelontong/' target='_blank'>http://localhost/toko-kelontong/</a></li>";
    echo "<li>Login menggunakan akun admin atau kasir</li>";
    echo "<li>Mulai mengelola toko kelontong Anda!</li>";
    echo "</ol>";
    echo "</div>";

} catch (PDOException $e) {
    echo "<div class='error'>❌ Error: " . $e->getMessage() . "</div>";
} catch (Exception $e) {
    echo "<div class='error'>❌ Error: " . $e->getMessage() . "</div>";
}

echo "</body>";
echo "</html>";
?>
