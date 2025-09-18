# 🏪 Toko Kelontong - Sistem Penjualan POS

![Version](https://img.shields.io/badge/version-1.0.0-blue.svg)
![PHP](https://img.shields.io/badge/PHP-8.1+-777BB4.svg)
![MySQL](https://img.shields.io/badge/MySQL-5.7+-4479A1.svg)
![License](https://img.shields.io/badge/license-MIT-green.svg)

Sistem manajemen penjualan modern untuk toko kelontong dengan fitur Point of Sale (POS), manajemen inventori, dan laporan penjualan yang lengkap.

## 📋 Daftar Isi

- [Fitur Utama](#-fitur-utama)
- [Teknologi](#-teknologi)
- [Persyaratan Sistem](#-persyaratan-sistem)
- [Instalasi](#-instalasi)
- [Konfigurasi](#-konfigurasi)
- [Penggunaan](#-penggunaan)
- [Struktur Database](#-struktur-database)
- [API Endpoints](#-api-endpoints)
- [Kontribusi](#-kontribusi)
- [Lisensi](#-lisensi)

## ✨ Fitur Utama

### 🔐 Sistem Autentikasi
- Login multi-role (Admin & Kasir)
- Session management yang aman
- Password hashing dengan bcrypt

### 👨‍💼 Panel Admin
- **Dashboard Analytics**: Statistik penjualan real-time
- **Manajemen Produk**: CRUD produk dengan kategori
- **Manajemen Pelanggan**: Database pelanggan terdaftar
- **Manajemen User**: Kelola akun kasir dan admin
- **Laporan Lengkap**:
  - Laporan harian, mingguan, bulanan
  - Laporan produk terlaris
  - Laporan pendapatan
  - Export ke Excel dan PDF

### 🛒 Sistem POS (Point of Sale)
- Interface kasir yang user-friendly
- Pencarian produk real-time
- Barcode scanning support
- Sistem diskon (persentase & nominal)
- Manajemen pelanggan guest
- Print receipt otomatis
- Riwayat transaksi

### 📊 Manajemen Inventori
- Tracking stok real-time
- Alert stok menipis
- Riwayat pergerakan stok
- Import/Export data produk via Excel
- Adjustment stok manual

### 📈 Sistem Laporan
- Dashboard dengan grafik interaktif
- Laporan penjualan per periode
- Analisis produk terlaris
- Laporan pelanggan
- Export laporan ke Excel/PDF

## 🛠 Teknologi

### Backend
- **PHP 8.1+** - Server-side scripting
- **MySQL 5.7+** - Database management
- **PDO** - Database abstraction layer

### Frontend
- **HTML5 & CSS3** - Markup dan styling
- **Bootstrap 5** - CSS framework
- **JavaScript (ES6+)** - Client-side scripting
- **jQuery** - DOM manipulation
- **Chart.js** - Data visualization
- **DataTables** - Advanced table features

### Libraries & Dependencies
- **PHPSpreadsheet** - Excel import/export
- **Adminto Template v4.0.0** - Admin dashboard template
- **Font Awesome** - Icons
- **SweetAlert2** - Beautiful alerts

## 📋 Persyaratan Sistem

### Server Requirements
- **PHP**: 8.1 atau lebih tinggi
- **MySQL**: 5.7 atau lebih tinggi
- **Apache/Nginx**: Web server
- **Composer**: Dependency manager

### PHP Extensions
- `pdo_mysql`
- `mbstring`
- `zip`
- `xml`
- `gd` (untuk manipulasi gambar)
- `curl`

### Browser Support
- Chrome 90+
- Firefox 88+
- Safari 14+
- Edge 90+

## 🚀 Instalasi

### 1. Clone Repository
```bash
git clone https://github.com/username/toko-kelontong.git
cd toko-kelontong
```

### 2. Install Dependencies
```bash
composer install
```

### 3. Konfigurasi Database
1. Buat database MySQL baru:
```sql
CREATE DATABASE toko_kelontong CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

2. Edit file `config/database.php`:
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'toko_kelontong');
define('DB_USER', 'your_username');
define('DB_PASS', 'your_password');
```

### 4. Setup Database
Jalankan setup database melalui browser:
```
http://localhost/toko-kelontong/setup.php
```

### 5. Konfigurasi Aplikasi
Edit file `config/config.php` sesuai kebutuhan:
```php
define('BASE_URL', 'http://localhost/toko-kelontong/');
define('APP_NAME', 'Toko Kelontong Anda');
```

### 6. Set Permissions
```bash
chmod -R 755 uploads/
chmod -R 755 exports/
```

## ⚙️ Konfigurasi

### Default Login
Setelah setup, gunakan akun default:

**Admin:**
- Username: `admin`
- Password: `admin123`

**Kasir:**
- Username: `kasir`
- Password: `kasir123`

> ⚠️ **Penting**: Segera ubah password default setelah login pertama!

### Konfigurasi Tambahan

#### Upload Settings
```php
// config/config.php
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_IMAGE_TYPES', ['jpg', 'jpeg', 'png', 'gif']);
```

#### Stock Alerts
```php
// config/config.php
define('LOW_STOCK_THRESHOLD', 10);
define('CRITICAL_STOCK_THRESHOLD', 5);
```

## 📖 Penggunaan

### Untuk Admin
1. **Login** ke sistem dengan akun admin
2. **Dashboard**: Lihat ringkasan penjualan dan statistik
3. **Kelola Produk**: Tambah, edit, hapus produk
4. **Kelola Kategori**: Organisir produk berdasarkan kategori
5. **Kelola Pelanggan**: Database pelanggan terdaftar
6. **Laporan**: Analisis penjualan dan performa toko
7. **Kelola User**: Tambah kasir baru atau edit akun

### Untuk Kasir
1. **Login** ke sistem dengan akun kasir
2. **POS System**: Langsung diarahkan ke sistem penjualan
3. **Cari Produk**: Gunakan search atau scan barcode
4. **Tambah ke Keranjang**: Klik produk atau input manual
5. **Proses Pembayaran**: Input pembayaran dan print receipt
6. **Riwayat**: Lihat transaksi yang telah dilakukan

### Fitur Khusus

#### Import Data Produk
1. Download template Excel dari menu Produk
2. Isi data produk sesuai format
3. Upload file melalui menu Import
4. Sistem akan validasi dan import data

#### Sistem Diskon
1. Di POS, klik tombol "Diskon"
2. Pilih jenis diskon (persentase/nominal)
3. Input nilai diskon
4. Diskon akan diterapkan ke total transaksi

## 🗄️ Struktur Database

### Tabel Utama

#### `users` - Data Pengguna
```sql
id, username, password, nama_lengkap, role, status, created_at, updated_at
```

#### `products` - Data Produk
```sql
id, kode_barang, barcode, nama_barang, category_id, harga_beli, harga_jual, 
stok, satuan, gambar, deskripsi, status, created_at, updated_at
```

#### `transactions` - Header Transaksi
```sql
id, kode_transaksi, customer_id, customer_name, user_id, tanggal_transaksi,
subtotal, total, discount_type, discount_value, discount_amount, final_total, status
```

#### `transaction_details` - Detail Transaksi
```sql
id, transaction_id, product_id, jumlah, harga_satuan, subtotal
```

#### `categories` - Kategori Produk
```sql
id, nama_kategori, deskripsi, created_at
```

#### `customers` - Data Pelanggan
```sql
id, nama_pelanggan, no_hp, alamat, created_at, updated_at
```

#### `stock_movements` - Riwayat Stok
```sql
id, product_id, type, quantity, keterangan, user_id, created_at
```

## 🔌 API Endpoints

### Product Search
```
GET /ajax/search_product.php?q={query}&category={id}&limit={number}
```

### Customer Search
```
GET /ajax/search_customer.php?q={query}
```

### Save Transaction
```
POST /ajax/save_transaction.php
```

### Barcode Lookup
```
GET /ajax/barcode_lookup.php?barcode={code}
```

## 📁 Struktur Folder

```
toko-kelontong/
├── admin/                 # Panel admin
│   ├── categories/        # Manajemen kategori
│   ├── customers/         # Manajemen pelanggan
│   ├── products/          # Manajemen produk
│   ├── reports/           # Laporan penjualan
│   ├── stock/             # Manajemen stok
│   ├── transactions/      # Riwayat transaksi
│   └── users/             # Manajemen user
├── ajax/                  # API endpoints
├── assets/                # Template dan assets
├── auth/                  # Sistem autentikasi
├── config/                # Konfigurasi aplikasi
├── exports/               # File export (Excel/PDF)
├── includes/              # File include umum
├── kasir/                 # Panel kasir (POS)
├── libraries/             # Custom libraries
├── uploads/               # Upload files
└── vendor/                # Composer dependencies
```

## 🤝 Kontribusi

Kami menyambut kontribusi dari komunitas! Untuk berkontribusi:

1. Fork repository ini
2. Buat branch fitur baru (`git checkout -b feature/AmazingFeature`)
3. Commit perubahan (`git commit -m 'Add some AmazingFeature'`)
4. Push ke branch (`git push origin feature/AmazingFeature`)
5. Buat Pull Request

### Guidelines
- Ikuti coding standards PHP PSR-12
- Tulis komentar yang jelas
- Test fitur sebelum submit
- Update dokumentasi jika diperlukan

## 🐛 Bug Reports

Jika menemukan bug, silakan buat issue dengan informasi:
- Deskripsi bug yang jelas
- Langkah untuk reproduce
- Screenshot (jika ada)
- Environment details (PHP version, browser, dll)

## 📝 Changelog

### v1.0.0 (2024-01-15)
- ✨ Initial release
- 🔐 Sistem autentikasi multi-role
- 🛒 POS system dengan barcode support
- 📊 Dashboard analytics
- 📈 Sistem laporan lengkap
- 💾 Import/Export Excel
- 🎨 Responsive UI dengan Adminto template

## 📄 Lisensi

Project ini dilisensikan under MIT License - lihat file [LICENSE](LICENSE) untuk detail.

## 👨‍💻 Author

**Toko Kelontong POS System**
- Website: [Your Website](https://yourwebsite.com)
- Email: your.email@example.com

## 🙏 Acknowledgments

- [Adminto Template](https://coderthemes.com/adminto/) - Admin dashboard template
- [PHPSpreadsheet](https://phpspreadsheet.readthedocs.io/) - Excel processing
- [Bootstrap](https://getbootstrap.com/) - CSS framework
- [Chart.js](https://www.chartjs.org/) - Data visualization

---

⭐ **Jika project ini membantu, jangan lupa berikan star!** ⭐

📞 **Butuh bantuan?** Buat issue atau hubungi kami melalui email.

🚀 **Happy Coding!**