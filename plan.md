# 📋 PLAN PEMBANGUNAN SISTEM PENJUALAN TOKO KELONTONG

## 🎯 OVERVIEW
Sistem penjualan toko kelontong berbasis web menggunakan PHP Native + MySQL dengan template Adminto v4.0.0 (Vertical Layout).

---

## 📊 ANALISIS KEBUTUHAN

### 👥 User Roles
1. **Admin** - Akses penuh ke semua fitur
2. **Kasir** - Akses terbatas untuk transaksi penjualan

### 🔧 Teknologi Stack
- **Frontend**: HTML5, CSS3, JavaScript, Bootstrap 5 (Adminto Template)
- **Backend**: PHP Native (tanpa framework)
- **Database**: MySQL
- **Library Tambahan**:
  - PHPSpreadsheet (untuk Excel import/export)
  - TCPDF/mPDF (untuk PDF export)
  - Chart.js (untuk dashboard analytics)

---

## 🗂️ STRUKTUR DATABASE

### 📋 Tabel yang diperlukan:

#### 1. `users` - Data pengguna sistem
```sql
- id (INT, PRIMARY KEY, AUTO_INCREMENT)
- username (VARCHAR(50), UNIQUE)
- password (VARCHAR(255)) -- Hashed
- nama_lengkap (VARCHAR(100))
- role (ENUM('admin', 'kasir'))
- status (ENUM('aktif', 'nonaktif'))
- created_at (TIMESTAMP)
- updated_at (TIMESTAMP)
```

#### 2. `categories` - Kategori barang
```sql
- id (INT, PRIMARY KEY, AUTO_INCREMENT)
- nama_kategori (VARCHAR(100))
- deskripsi (TEXT)
- created_at (TIMESTAMP)
```

#### 3. `products` - Data barang
```sql
- id (INT, PRIMARY KEY, AUTO_INCREMENT)
- kode_barang (VARCHAR(20), UNIQUE)
- nama_barang (VARCHAR(200))
- category_id (INT, FOREIGN KEY)
- harga_beli (DECIMAL(15,2))
- harga_jual (DECIMAL(15,2))
- stok (INT)
- satuan (VARCHAR(20))
- gambar (VARCHAR(255))
- deskripsi (TEXT)
- status (ENUM('aktif', 'nonaktif'))
- created_at (TIMESTAMP)
- updated_at (TIMESTAMP)
```

#### 4. `customers` - Data pelanggan
```sql
- id (INT, PRIMARY KEY, AUTO_INCREMENT)
- nama_pelanggan (VARCHAR(100))
- no_hp (VARCHAR(15))
- alamat (TEXT)
- created_at (TIMESTAMP)
- updated_at (TIMESTAMP)
```

#### 5. `transactions` - Header transaksi
```sql
- id (INT, PRIMARY KEY, AUTO_INCREMENT)
- kode_transaksi (VARCHAR(20), UNIQUE)
- customer_id (INT, FOREIGN KEY, NULL) -- NULL untuk guest customer
- customer_name (VARCHAR(100), NULL) -- Nama guest customer jika tidak terdaftar
- user_id (INT, FOREIGN KEY) -- Kasir yang melayani
- tanggal_transaksi (DATETIME)
- subtotal (DECIMAL(15,2))
- total (DECIMAL(15,2))
- status (ENUM('selesai', 'batal'))
- created_at (TIMESTAMP)
```

#### 6. `transaction_details` - Detail transaksi
```sql
- id (INT, PRIMARY KEY, AUTO_INCREMENT)
- transaction_id (INT, FOREIGN KEY)
- product_id (INT, FOREIGN KEY)
- jumlah (INT)
- harga_satuan (DECIMAL(15,2))
- subtotal (DECIMAL(15,2))
```

#### 7. `stock_movements` - Riwayat pergerakan stok
```sql
- id (INT, PRIMARY KEY, AUTO_INCREMENT)
- product_id (INT, FOREIGN KEY)
- transaction_id (INT, FOREIGN KEY, NULL)
- tipe (ENUM('masuk', 'keluar', 'penyesuaian'))
- jumlah (INT)
- keterangan (VARCHAR(255))
- created_at (TIMESTAMP)
```

---

## 📁 STRUKTUR FOLDER APLIKASI

```
toko-kelontong/
├── assets/
│   └── Adminto_v4.0.0/Vertical/ (template yang sudah ada)
├── config/
│   ├── database.php
│   └── config.php
├── includes/
│   ├── header_admin.php
│   ├── header_kasir.php
│   ├── sidebar_admin.php
│   ├── sidebar_kasir.php
│   ├── footer.php
│   ├── functions.php
│   ├── admin_auth.php
│   └── kasir_auth.php
├── uploads/
│   ├── products/
│   └── temp/
├── exports/
│   ├── excel/
│   └── pdf/
├── libraries/
│   ├── phpspreadsheet/
│   └── tcpdf/
├── auth/
│   ├── login.php
│   ├── logout.php
│   └── process_login.php
├── admin/                    # Area khusus admin
│   ├── dashboard.php         # Dashboard admin
│   ├── products/
│   │   ├── index.php         # Daftar barang
│   │   ├── add.php           # Tambah barang
│   │   ├── edit.php          # Edit barang
│   │   ├── delete.php        # Hapus barang
│   │   ├── import.php        # Import Excel barang
│   │   └── export.php        # Export Excel barang
│   ├── customers/
│   │   ├── index.php         # Daftar pelanggan
│   │   ├── add.php           # Tambah pelanggan
│   │   ├── edit.php          # Edit pelanggan
│   │   ├── delete.php        # Hapus pelanggan
│   │   ├── import.php        # Import Excel pelanggan
│   │   └── export.php        # Export Excel pelanggan
│   ├── categories/
│   │   ├── index.php         # Daftar kategori
│   │   ├── add.php           # Tambah kategori
│   │   ├── edit.php          # Edit kategori
│   │   └── delete.php        # Hapus kategori
│   ├── transactions/
│   │   ├── index.php         # Daftar transaksi
│   │   ├── detail.php        # Detail transaksi
│   │   ├── print.php         # Cetak struk
│   │   └── export.php        # Export transaksi
│   ├── reports/
│   │   ├── daily.php         # Laporan harian
│   │   ├── weekly.php        # Laporan mingguan
│   │   ├── monthly.php       # Laporan bulanan
│   │   ├── bestseller.php    # Barang terlaris
│   │   └── revenue.php       # Laporan omzet
│   └── users/
│       ├── index.php         # Daftar user
│       ├── add.php           # Tambah user
│       ├── edit.php          # Edit user
│       └── delete.php        # Hapus user
├── kasir/                    # Area khusus kasir
│   ├── penjualan.php         # Halaman utama kasir (POS)
│   ├── riwayat.php           # Riwayat transaksi kasir
│   ├── customers/
│   │   ├── search.php        # Cari pelanggan
│   │   └── add.php           # Tambah pelanggan baru
│   └── reports/
│       └── daily.php         # Laporan penjualan hari ini
├── ajax/
│   ├── get_product.php       # Ambil data barang
│   ├── search_product.php    # Cari barang
│   ├── search_customer.php   # Cari pelanggan
│   └── save_transaction.php  # Simpan transaksi
├── setup.php                 # Setup database & seeder
├── index.php                 # Router utama (redirect berdasarkan role)
└── plan.md
```

---

## 🚀 TAHAPAN PENGEMBANGAN

### Phase 1: Setup & Konfigurasi (1-2 hari)
1. **Setup Database**
   - [x] Buat file `setup.php` untuk instalasi otomatis
   - [x] Script create database dan tabel
   - [x] Insert data seeder (user admin default, kategori dasar)

2. **Konfigurasi Dasar**
   - [x] Setup koneksi database (`config/database.php`)
   - [x] File konfigurasi umum (`config/config.php`)
   - [x] Smart router (`index.php`)
   - [x] Role-based auth helpers (`includes/admin_auth.php`, `includes/kasir_auth.php`)
   - [x] Adaptasi template Adminto untuk struktur role-based

### Phase 2: Sistem Autentikasi (1 hari)
3. **Login System**
   - [x] Halaman login dengan validasi (`auth/login.php`)
   - [x] Session management dengan role-based routing
   - [x] Role-based access control untuk admin dan kasir
   - [x] Logout functionality (`auth/logout.php`)

### Phase 3: Area Admin - Master Data (3-4 hari)
4. **Admin Dashboard**
   - [x] Dashboard analytics lengkap (`admin/dashboard.php`)
   - [x] Sidebar navigation khusus admin

5. **Manajemen Kategori** (`admin/categories/`)
   - [x] CRUD kategori barang
   - [x] Validasi input

6. **Manajemen Barang** (`admin/products/`)
   - [x] CRUD barang (Create, Read, Update, Delete)
   - [x] Upload gambar barang
   - [x] Validasi stok
   - [x] Import barang dari Excel/CSV
   - [x] Export data barang ke Excel

7. **Manajemen Pelanggan** (`admin/customers/`)
   - [x] CRUD pelanggan
   - [x] Import pelanggan dari Excel/CSV
   - [x] Export data pelanggan ke Excel

8. **Manajemen User** (`admin/users/`)
   - [x] CRUD user (kasir)
   - [x] Reset password
   - [x] Aktivasi/deaktivasi user

### Phase 4: Area Kasir - Point of Sale (2-3 hari)
9. **Sistem POS** (`kasir/penjualan.php`)
   - [x] Interface transaksi untuk kasir
   - [x] Pencarian dan pemilihan barang
   - [x] Kalkulasi otomatis subtotal & total
   - [x] **Support guest customer (transaksi tanpa pelanggan)**
     - Radio button: "Pelanggan Terdaftar" vs "Guest/Umum"
     - Jika guest: input nama optional (untuk struk)
     - Jika terdaftar: search dan pilih dari database
   - [x] Validasi stok sebelum transaksi
   - [x] Update stok otomatis setelah transaksi
   - [x] Generate kode transaksi otomatis

10. **Fitur Kasir Lainnya**
    - [x] Riwayat transaksi kasir (`kasir/riwayat.php`)
    - [x] Cari pelanggan (`kasir/customers/search.php`)
    - [x] Tambah pelanggan baru (`kasir/customers/add.php`)
    - [x] Laporan harian (`kasir/reports/daily.php`)

### Phase 5: Area Admin - Transaksi & Laporan (2-3 hari)
11. **Manajemen Transaksi Admin** (`admin/transactions/`)
    - [x] Daftar semua transaksi
    - [x] Detail transaksi
    - [x] Cetak ulang struk
    - [x] Export transaksi

12. **Sistem Laporan Admin** (`admin/reports/`)
    - [x] Laporan harian dengan filter tanggal
    - [x] Laporan mingguan
    - [x] Laporan bulanan
    - [x] Laporan barang terlaris
    - [x] Laporan total omzet
    - [x] Export semua laporan ke Excel/PDF

### Phase 6: Fitur Import/Export Lanjutan (1-2 hari)
13. **Import/Export Advanced**
    - [x] Template Excel untuk import barang
    - [x] Template Excel untuk import pelanggan
    - [x] Validasi data saat import
    - [x] Log import (berhasil/gagal)

### Phase 7: Cetak Struk & Polish (1-2 hari)
14. **Cetak Struk**
    - [x] Generate struk dalam format PDF
    - [x] Template struk yang rapi
    - [x] Cetak dari area admin dan kasir

### Phase 8: Testing & Security (1-2 hari)
15. **Testing & Security**
    - [x] Test semua fitur admin
    - [x] Test semua fitur kasir
    - [x] Test role-based access control
    - [x] Test smart routing
    - [x] Security audit
    - [x] Performance optimization
    - [x] Fix bug yang ditemukan

---

## 🎨 DESAIN INTERFACE

### Layout Utama
- **Template**: Adminto v4.0.0 Vertical
- **Role-based Navigation**:
  - **Admin Area** (`/admin/`):
    - Dashboard (analytics lengkap)
    - Master Data (Barang, Pelanggan, Kategori)
    - Transaksi (semua transaksi)
    - Laporan (lengkap)
    - Manajemen User
  - **Kasir Area** (`/kasir/`):
    - Penjualan (POS utama)
    - Riwayat Transaksi
    - Cari/Tambah Pelanggan
    - Laporan Harian

### Smart Router (`index.php`)
```php
<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

if (!isLoggedIn()) {
    header('Location: auth/login.php');
    exit;
}

$user = getCurrentUser();
if ($user['role'] === 'admin') {
    header('Location: admin/dashboard.php');
} else {
    header('Location: kasir/penjualan.php');
}
exit;
?>
```

### Role-based Security
- **Admin Files**: Require `../includes/admin_auth.php`
- **Kasir Files**: Require `../includes/kasir_auth.php`
- **Automatic Role Verification**: Di setiap halaman
- **Secure Redirects**: Tidak ada akses lintas role

### Fitur UI/UX
- **Responsive Design** - Mobile friendly
- **Dark/Light Mode** - Sesuai template Adminto
- **Search & Filter** - Pada semua tabel data
- **Pagination** - Untuk data yang banyak
- **Modal Dialogs** - Untuk form tambah/edit
- **Toast Notifications** - Feedback user action
- **Loading Indicators** - Saat proses berlangsung

---

## 🔒 KEAMANAN

### Measures yang Diterapkan
1. **Input Validation & Sanitization**
2. **SQL Injection Prevention** (Prepared Statements)
3. **XSS Protection**
4. **CSRF Protection**
5. **File Upload Security** (validasi ekstensi & size)
6. **Password Hashing** (bcrypt)
7. **Session Security**
8. **Role-based Access Control**

---

## 📊 SEEDER DATA

### Data Default yang Diinsert:
1. **User Admin**
   - Username: admin
   - Password: admin123
   - Role: admin

2. **User Kasir**
   - Username: kasir1
   - Password: kasir123
   - Role: kasir

3. **Kategori Barang**
   - Makanan & Minuman
   - Sembako
   - Peralatan Rumah Tangga
   - Obat-obatan
   - Lain-lain

4. **Sample Barang** (10-15 item)
5. **Sample Pelanggan** (5-10 pelanggan)

---

## 📋 CHECKLIST FITUR

### ✅ Admin Features (`/admin/`)
- [ ] Login/Logout dengan redirect otomatis
- [ ] Dashboard dengan analytics lengkap
- [ ] CRUD Barang + Upload gambar
- [ ] CRUD Pelanggan
- [ ] CRUD Kategori
- [ ] CRUD User (manajemen kasir)
- [ ] Lihat semua transaksi
- [ ] Laporan lengkap (harian, mingguan, bulanan)
- [ ] Export laporan (Excel/PDF)
- [ ] Import data (Excel/CSV)
- [ ] Manajemen stok
- [ ] Cetak ulang struk

### ✅ Kasir Features (`/kasir/`)
- [ ] Login/Logout dengan redirect otomatis
- [ ] Sistem POS utama (penjualan.php)
- [ ] **Transaksi dengan Guest Customer:**
  - [ ] Pilihan "Pelanggan Terdaftar" atau "Guest/Umum"
  - [ ] Input nama optional untuk guest (tampil di struk)
  - [ ] Transaksi bisa diselesaikan tanpa data pelanggan
  - [ ] Database: customer_id = NULL, customer_name = input nama
- [ ] Transaksi dengan pelanggan terdaftar (search & pilih)
- [ ] Kalkulasi otomatis subtotal & total
- [ ] Cari dan tambah pelanggan baru
- [ ] Cetak struk transaksi (tampilkan nama guest jika ada)
- [ ] Lihat riwayat transaksi pribadi
- [ ] Laporan penjualan harian
- [ ] Update stok otomatis

### ✅ Shared Features
- [ ] Smart routing (`index.php`)
- [ ] Role-based access control
- [ ] Responsive design (Adminto template)
- [ ] Search & filter pada tabel
- [ ] Pagination untuk data banyak
- [ ] Notification system
- [ ] Security measures (SQL injection, XSS protection)
- [ ] Error handling yang baik
- [ ] Session management yang aman

---

## 🛍️ FITUR GUEST CUSTOMER (TRANSAKSI TANPA PELANGGAN)

### 🎯 Konsep
Sistem mendukung 2 jenis transaksi:
1. **Pelanggan Terdaftar** - Menggunakan data dari database customers
2. **Guest/Umum** - Transaksi tanpa perlu data pelanggan lengkap

### 📊 Database Design untuk Guest Customer
```sql
-- Tabel transactions mendukung guest customer
CREATE TABLE transactions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    kode_transaksi VARCHAR(20) UNIQUE,
    customer_id INT NULL,  -- NULL untuk guest customer
    customer_name VARCHAR(100) NULL,  -- Nama guest (optional)
    user_id INT NOT NULL,  -- Kasir yang melayani
    tanggal_transaksi DATETIME,
    subtotal DECIMAL(15,2),
    total DECIMAL(15,2),
    status ENUM('selesai', 'batal'),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (customer_id) REFERENCES customers(id),
    FOREIGN KEY (user_id) REFERENCES users(id)
);
```

### 🖥️ Interface POS untuk Guest Customer

#### Pilihan Tipe Pelanggan (Radio Button):
```html
<div class="customer-type-selection">
    <input type="radio" name="customer_type" value="guest" checked>
    <label>Guest/Umum</label>

    <input type="radio" name="customer_type" value="registered">
    <label>Pelanggan Terdaftar</label>
</div>
```

#### Area Guest Customer:
```html
<div id="guest-area" style="display:block;">
    <input type="text" name="guest_name" placeholder="Nama (Optional)" maxlength="100">
    <small>*Kosongkan jika pelanggan tidak ingin memberikan nama</small>
</div>
```

#### Area Pelanggan Terdaftar:
```html
<div id="registered-area" style="display:none;">
    <input type="text" id="customer_search" placeholder="Cari nama/no HP pelanggan...">
    <div id="customer_results"></div>
    <button type="button" id="add_new_customer">+ Tambah Pelanggan Baru</button>
</div>
```

### 📝 Flow Transaksi Guest Customer

1. **Kasir memilih "Guest/Umum"**
2. **Input nama (optional)** - bisa dikosongkan
3. **Pilih barang dan jumlah** - seperti biasa
4. **Hitung total** - sistem kalkulasi otomatis
5. **Simpan transaksi:**
   ```php
   $customer_id = null;  // Guest customer
   $customer_name = trim($_POST['guest_name']) ?: null;
   ```
6. **Cetak struk** - tampilkan nama guest jika ada, atau "Pelanggan Umum"

### 📊 Laporan Guest Customer

#### Dalam Laporan Transaksi:
- **customer_id = NULL** → Tampilkan sebagai "Guest/Umum"
- **customer_name ada** → Tampilkan "Guest: [Nama]"
- **customer_name kosong** → Tampilkan "Pelanggan Umum"

#### Query Example:
```sql
SELECT
    t.kode_transaksi,
    CASE
        WHEN t.customer_id IS NOT NULL THEN c.nama_pelanggan
        WHEN t.customer_name IS NOT NULL THEN CONCAT('Guest: ', t.customer_name)
        ELSE 'Pelanggan Umum'
    END as nama_pelanggan,
    t.total,
    t.tanggal_transaksi
FROM transactions t
LEFT JOIN customers c ON t.customer_id = c.id
WHERE DATE(t.tanggal_transaksi) = CURDATE();
```

### 📝 Template Struk untuk Guest Customer

```
================================
      TOKO KELONTONG SEJAHTERA
================================
Kode Transaksi: TRX001
Tanggal: 09/09/2025 14:30
Kasir: Siti (kasir1)

Pelanggan: Guest: Budi
// atau
Pelanggan: Pelanggan Umum

--------------------------------
Barang                 Qty  Total
--------------------------------
Beras 5kg              2   50.000
Minyak Goreng         1   15.000
--------------------------------
Subtotal:                65.000
Total:                   65.000

Terima kasih atas kunjungan Anda!
================================
```

### ⚙️ Keunggulan Fitur Guest Customer

✅ **Fleksibilitas Tinggi** - Kasir tidak perlu memaksa pelanggan daftar
✅ **Proses Cepat** - Transaksi lebih cepat untuk pembeli sekali
✅ **Data Tetap Tercatat** - Semua transaksi masuk laporan
✅ **Optional Info** - Bisa simpan nama guest untuk reference
✅ **User Friendly** - Interface sederhana dengan radio button
✅ **Laporan Lengkap** - Guest customer tetap muncul di laporan

---

## 🎯 KEUNGGULAN ARSITEKTUR ROLE-BASED

### 🛡️ Security Benefits
- **Physical Separation**: Admin dan kasir files terpisah folder
- **Role Isolation**: Kasir tidak bisa akses URL admin
- **Defense in Depth**: Multiple layers of access control
- **Easier Auditing**: Clear boundaries untuk security review

### 🚀 User Experience Benefits
- **Zero Confusion**: User langsung masuk ke area yang sesuai role
- **Optimized Interface**: Setiap area disesuaikan dengan kebutuhan role
- **Faster Navigation**: Tidak ada menu/fitur yang tidak relevan
- **Intuitive Structure**: Struktur folder mengikuti mental model user

### 🔧 Development Benefits
- **Clear Ownership**: Team bisa fokus pada area spesifik
- **Easier Testing**: Test admin dan kasir secara terpisah
- **Simpler Maintenance**: Bug fix tidak affect area lain
- **Scalable Architecture**: Mudah tambah role baru (manager, owner, dll)

### 📈 Business Benefits
- **Better Analytics**: Track usage per role
- **Targeted Training**: Training materials per role
- **Compliance Ready**: Easier untuk audit dan compliance
- **Future-proof**: Architecture ready untuk growth

---

## ⚙️ TARGET TIMELINE

**Total Estimasi: 14-18 hari kerja**

- **Week 1**: Setup + Auth + Master Data
- **Week 2**: Transaksi + Laporan
- **Week 3**: Import/Export + Testing + Polish

---

## 📞 NEXT STEPS

1. **Jalankan setup.php** untuk membuat database
2. **Mulai development** sesuai phase yang sudah ditentukan
3. **Testing berkala** setiap selesai 1 phase
4. **Documentation** untuk user manual

---

*Plan ini dapat disesuaikan berdasarkan feedback dan kebutuhan yang berkembang selama development.*