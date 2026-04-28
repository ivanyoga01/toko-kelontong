# SVG Illustrations

Folder ini berisi ilustrasi SVG untuk berbagai kondisi UI dalam aplikasi Toko Kelontong.

## File yang Tersedia

### 1. search-not-found.svg
**Deskripsi:** Ilustrasi untuk kondisi pencarian tidak menemukan hasil
**Digunakan di:**
- Admin Transactions (index.php)
- Admin Users (index.php)
- Halaman lain dengan fitur pencarian

**Elemen:**
- Ikon kaca pembesar dengan tanda X
- Ikon dokumen
- Teks "Tidak Ada Data"

### 2. empty-cart.svg
**Deskripsi:** Ilustrasi untuk keranjang belanja kosong
**Digunakan di:**
- Kasir POS (penjualan.php)
- Halaman keranjang belanja

**Elemen:**
- Ikon keranjang belanja kosong
- Emoticon sedih
- Teks "Keranjang Kosong"

### 3. no-data.svg
**Deskripsi:** Ilustrasi umum untuk kondisi tidak ada data
**Digunakan di:**
- Halaman master data yang kosong
- Laporan tanpa data

**Elemen:**
- Ikon folder kosong
- Tanda tanya
- Teks "Belum Ada Data"

### 4. error-404.svg
**Deskripsi:** Ilustrasi untuk halaman tidak ditemukan (404)
**Digunakan di:**
- Error page 404
- Halaman yang tidak tersedia

**Elemen:**
- Teks "404" besar
- Ikon rantai putus
- Segitiga peringatan
- Teks "Halaman Tidak Ditemukan"

## Cara Penggunaan

```php
<!-- Contoh penggunaan di PHP -->
<img src="<?= ASSETS_URL ?>Adminto_v4.0.0/Vertical/dist/assets/images/svg/search-not-found.svg" 
     height="90" 
     alt="Not Found">
```

```html
<!-- Contoh penggunaan di HTML -->
<img src="assets/Adminto_v4.0.0/Vertical/dist/assets/images/svg/no-data.svg" 
     height="120" 
     alt="No Data">
```

## Spesifikasi Teknis

- **Format:** SVG (Scalable Vector Graphics)
- **Ukuran Canvas:** 400x300 pixels
- **Warna Palette:**
  - Background: #f8f9fa
  - Primary: #6c757d
  - Secondary: #adb5bd
  - Light: #dee2e6, #e9ecef
  - Danger: #dc3545
  - Warning: #ffc107

## Kustomisasi

Untuk mengubah warna atau ukuran ilustrasi:
1. Buka file SVG dengan text editor
2. Cari atribut `fill` atau `stroke` untuk mengubah warna
3. Ubah `viewBox` untuk mengubah proporsi
4. Simpan dan refresh browser

## Lisensi

File-file SVG ini dibuat khusus untuk aplikasi Toko Kelontong dan dapat dimodifikasi sesuai kebutuhan.
