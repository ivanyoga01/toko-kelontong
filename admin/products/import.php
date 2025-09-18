<?php
/**
 * Import Products from Excel
 * Handles Excel import for products data using PhpSpreadsheet
 */

require_once '../../includes/admin_auth.php';

$page_title = 'Import Data Barang';
$page_description = 'Import data barang dari file Excel/CSV';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['download_template'])) {
        // Download template
        $headers = [
            'kode_barang',
            'barcode',
            'nama_barang',
            'kategori',
            'harga_beli',
            'harga_jual',
            'stok',
            'satuan',
            'deskripsi'
        ];

        $sample_data = [
            ['BRG999', '1234567890123', 'Contoh Barang', 'Makanan & Minuman', '10000', '12000', '50', 'pcs', 'Contoh deskripsi barang']
        ];

        // $title = 'Template Import Data Barang - ' . APP_NAME;
        ExcelHelper::generateTemplate($headers, $sample_data, 'template_barang.xlsx', $title);
        exit;
    }

    if (isset($_FILES['excel_file']) && $_FILES['excel_file']['error'] === UPLOAD_ERR_OK) {
        try {
            $file_path = $_FILES['excel_file']['tmp_name'];
            $filename = $_FILES['excel_file']['name'];

            // Validate file extension
            $allowed_extensions = ['csv', 'xls', 'xlsx'];
            $file_extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

            if (!in_array($file_extension, $allowed_extensions)) {
                throw new Exception('Format file tidak didukung. Gunakan CSV, XLS, atau XLSX');
            }

            // Import data using new ExcelHelper
            $data = ExcelHelper::importFromExcel($file_path);

            // Validate structure
            $required_columns = ['kode_barang', 'nama_barang', 'kategori', 'harga_beli', 'harga_jual', 'stok', 'satuan'];
            $validation_errors = ExcelHelper::validateImportData($data, $required_columns);

            if (!empty($validation_errors)) {
                throw new Exception('Struktur file tidak valid: ' . implode(', ', $validation_errors));
            }

            // Process import
            $results = ExcelHelper::processImportRows($data, function($row_data, $row_number) {
                try {
                    // Validate required fields
                    $kode_barang = trim($row_data['kode_barang']);
                    $barcode = trim($row_data['barcode'] ?? '');
                    $nama_barang = trim($row_data['nama_barang']);
                    $kategori = trim($row_data['kategori']);
                    $harga_beli = floatval($row_data['harga_beli']);
                    $harga_jual = floatval($row_data['harga_jual']);
                    $stok = intval($row_data['stok']);
                    $satuan = trim($row_data['satuan']);
                    $deskripsi = trim($row_data['deskripsi'] ?? '');

                    // Validation
                    if (empty($kode_barang)) {
                        return ['success' => false, 'message' => 'Kode barang tidak boleh kosong'];
                    }

                    if (empty($nama_barang)) {
                        return ['success' => false, 'message' => 'Nama barang tidak boleh kosong'];
                    }

                    if ($harga_beli <= 0) {
                        return ['success' => false, 'message' => 'Harga beli harus lebih dari 0'];
                    }

                    if ($harga_jual <= 0) {
                        return ['success' => false, 'message' => 'Harga jual harus lebih dari 0'];
                    }

                    if ($stok < 0) {
                        return ['success' => false, 'message' => 'Stok tidak boleh negatif'];
                    }

                    if (empty($satuan)) {
                        return ['success' => false, 'message' => 'Satuan tidak boleh kosong'];
                    }

                    // Check if product code already exists
                    $check_sql = "SELECT id FROM products WHERE kode_barang = ?";
                    $existing = fetchOne($check_sql, [$kode_barang]);

                    if ($existing) {
                        return ['success' => false, 'message' => "Kode barang '$kode_barang' sudah ada"];
                    }

                    // Check for duplicate barcode if provided
                    if (!empty($barcode)) {
                        $barcode_duplicate_sql = "SELECT id FROM products WHERE barcode = ?";
                        $barcode_duplicate = fetchOne($barcode_duplicate_sql, [$barcode]);

                        if ($barcode_duplicate) {
                            return ['success' => false, 'message' => "Barcode '$barcode' sudah ada"];
                        }
                    }

                    // Find or create category
                    $category_sql = "SELECT id FROM categories WHERE nama_kategori = ?";
                    $category = fetchOne($category_sql, [$kategori]);

                    if (!$category) {
                        // Create new category
                        $create_category_sql = "INSERT INTO categories (nama_kategori, deskripsi) VALUES (?, ?)";
                        $category_id = insertData($create_category_sql, [$kategori, "Kategori dibuat otomatis dari import"]);
                    } else {
                        $category_id = $category['id'];
                    }

                    // Insert product
                    $insert_sql = "INSERT INTO products (kode_barang, barcode, nama_barang, category_id, harga_beli, harga_jual, stok, satuan, deskripsi, status, created_at, updated_at)
                                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'aktif', NOW(), NOW())";

                    $product_id = insertData($insert_sql, [
                        $kode_barang,
                        $barcode ?: null,
                        $nama_barang,
                        $category_id,
                        $harga_beli,
                        $harga_jual,
                        $stok,
                        $satuan,
                        $deskripsi
                    ]);

                    if ($product_id) {
                        // Log stock movement for initial stock
                        if ($stok > 0) {
                            $stock_sql = "INSERT INTO stock_movements (product_id, tipe, jumlah, keterangan, created_at)
                                         VALUES (?, 'masuk', ?, ?, NOW())";
                            insertData($stock_sql, [$product_id, $stok, "Stok awal dari import - $kode_barang"]);
                        }

                        return ['success' => true, 'message' => 'Berhasil'];
                    } else {
                        return ['success' => false, 'message' => 'Gagal menyimpan data'];
                    }

                } catch (Exception $e) {
                    return ['success' => false, 'message' => $e->getMessage()];
                }
            });

            // Log import activity
            ExcelHelper::logImportActivity('products', $results, $current_user['id'], $filename);

            $import_success = true;
            $import_message = "Import selesai! Berhasil: {$results['success']}, Gagal: {$results['failed']}";

        } catch (Exception $e) {
            $import_success = false;
            $import_message = 'Error: ' . $e->getMessage();
        }
    }
}

include '../../includes/header.php';
include '../../includes/sidebar.php';
?>

<!-- Start Content -->
<div class="content-page">
    <div class="content">
        <div class="container-fluid">

            <!-- Page Title -->
            <div class="row">
                <div class="col-12">
                    <div class="page-title-box">
                        <div class="page-title-right">
                            <ol class="breadcrumb m-0">
                                <li class="breadcrumb-item"><a href="<?= ADMIN_URL ?>dashboard.php">Dashboard</a></li>
                                <li class="breadcrumb-item"><a href="<?= ADMIN_URL ?>products/">Data Barang</a></li>
                                <li class="breadcrumb-item active"><?= $page_title ?></li>
                            </ol>
                        </div>
                        <h4 class="page-title"><?= $page_title ?></h4>
                    </div>
                </div>
            </div>

            <!-- Import Result -->
            <?php if (isset($import_success)): ?>
            <div class="row">
                <div class="col-12">
                    <div class="alert <?= $import_success ? 'alert-success' : 'alert-danger' ?> alert-dismissible">
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                        <h4 class="alert-heading"><?= $import_success ? 'Berhasil!' : 'Gagal!' ?></h4>
                        <p><?= htmlspecialchars($import_message) ?></p>

                        <?php if ($import_success && !empty($results['errors'])): ?>
                        <hr>
                        <h6>Detail Error:</h6>
                        <ul class="mb-0">
                            <?php foreach (array_slice($results['errors'], 0, 10) as $error): ?>
                            <li><?= htmlspecialchars($error) ?></li>
                            <?php endforeach; ?>
                            <?php if (count($results['errors']) > 10): ?>
                            <li><em>... dan <?= count($results['errors']) - 10 ?> error lainnya</em></li>
                            <?php endif; ?>
                        </ul>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Instructions -->
            <div class="row">
                <div class="col-xl-6">
                    <div class="card">
                        <div class="card-header">
                            <h4 class="header-title">Panduan Import</h4>
                        </div>
                        <div class="card-body">
                            <ol>
                                <li>Download template Excel dengan klik tombol "Download Template"</li>
                                <li>Isi data barang sesuai format yang disediakan</li>
                                <li>Simpan file dalam format CSV, XLS, atau XLSX</li>
                                <li>Upload file menggunakan form di samping</li>
                                <li>Sistem akan memvalidasi dan mengimpor data</li>
                            </ol>

                            <h6 class="mt-4">Format Kolom:</h6>
                            <ul>
                                <li><strong>kode_barang</strong>: Kode unik barang (wajib)</li>
                                <li><strong>barcode</strong>: Barcode barang (opsional)</li>
                                <li><strong>nama_barang</strong>: Nama barang (wajib)</li>
                                <li><strong>kategori</strong>: Nama kategori (akan dibuat otomatis jika belum ada)</li>
                                <li><strong>harga_beli</strong>: Harga beli dalam rupiah (wajib)</li>
                                <li><strong>harga_jual</strong>: Harga jual dalam rupiah (wajib)</li>
                                <li><strong>stok</strong>: Jumlah stok awal (wajib)</li>
                                <li><strong>satuan</strong>: Satuan barang (pcs, kg, liter, dll)</li>
                                <li><strong>deskripsi</strong>: Deskripsi barang (opsional)</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Import Form -->
                <div class="col-xl-6">
                    <div class="card">
                        <div class="card-header">
                            <h4 class="header-title">Import Data Barang</h4>
                        </div>
                        <div class="card-body">
                            <!-- Download Template -->
                            <form method="POST" class="mb-3">
                                <button type="submit" name="download_template" class="btn btn-info btn-block">
                                    <i class="mdi mdi-download"></i> Download Template Excel
                                </button>
                            </form>

                            <!-- Upload Form -->
                            <form method="POST" enctype="multipart/form-data">
                                <div class="form-group">
                                    <label for="excel_file">Pilih File Excel/CSV</label>
                                    <input type="file" class="form-control" id="excel_file" name="excel_file"
                                           accept=".csv,.xls,.xlsx" required>
                                    <small class="form-text text-muted">
                                        Format yang didukung: CSV, XLS, XLSX (Max: 5MB)
                                    </small>
                                </div>

                                <button type="submit" class="btn btn-primary btn-block">
                                    <i class="mdi mdi-upload"></i> Import Data
                                </button>
                            </form>
                        </div>
                    </div>

                    <!-- Import History -->
                    <div class="card">
                        <div class="card-header">
                            <h4 class="header-title">Riwayat Import</h4>
                        </div>
                        <div class="card-body">
                            <?php
                            $history_sql = "SELECT il.*, u.nama_lengkap
                                          FROM import_logs il
                                          LEFT JOIN users u ON il.user_id = u.id
                                          WHERE il.type = 'products'
                                          ORDER BY il.created_at DESC
                                          LIMIT 5";
                            $history = fetchAll($history_sql);
                            ?>

                            <?php if (empty($history)): ?>
                                <p class="text-muted">Belum ada riwayat import</p>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th>Tanggal</th>
                                                <th>File</th>
                                                <th>Hasil</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($history as $log): ?>
                                            <tr>
                                                <td><?= formatDate($log['created_at']) ?></td>
                                                <td>
                                                    <small><?= htmlspecialchars($log['filename']) ?></small><br>
                                                    <small class="text-muted">oleh <?= htmlspecialchars($log['nama_lengkap']) ?></small>
                                                </td>
                                                <td>
                                                    <span class="badge badge-success"><?= $log['success_rows'] ?> berhasil</span>
                                                    <?php if ($log['failed_rows'] > 0): ?>
                                                    <span class="badge badge-danger"><?= $log['failed_rows'] ?> gagal</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

        </div> <!-- container -->
    </div> <!-- content -->
</div>

<?php include '../../includes/footer.php'; ?>

<script>
$(document).ready(function() {
    // File upload validation
    $('#excel_file').change(function() {
        const file = this.files[0];
        const maxSize = 5 * 1024 * 1024; // 5MB

        if (file && file.size > maxSize) {
            alert('Ukuran file terlalu besar! Maksimal 5MB');
            this.value = '';
        }
    });
});
</script>

</body>
</html>