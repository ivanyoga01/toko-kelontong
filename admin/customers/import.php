<?php
/**
 * Import Customers from Excel
 * Handles Excel import for customer data
 */

require_once '../../includes/admin_auth.php';

$page_title = 'Import Data Pelanggan';
$page_description = 'Import data pelanggan dari file Excel/CSV';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['download_template'])) {
        // Download template
        $headers = [
            'nama_pelanggan',
            'no_hp',
            'alamat'
        ];

        $sample_data = [
            ['John Doe', '081234567890', 'Jl. Contoh No. 123, Jakarta'],
            ['Jane Smith', '081234567891', 'Jl. Sample No. 456, Bandung']
        ];

        // $title = 'Template Import Data Pelanggan - ' . APP_NAME;
        ExcelHelper::generateTemplate($headers, $sample_data, 'template_pelanggan.xlsx', $title);
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
            $required_columns = ['nama_pelanggan'];
            $validation_errors = ExcelHelper::validateImportData($data, $required_columns);

            if (!empty($validation_errors)) {
                throw new Exception('Struktur file tidak valid: ' . implode(', ', $validation_errors));
            }

            // Process import
            $results = ExcelHelper::processImportRows($data, function($row_data, $row_number) {
                try {
                    // Validate required fields
                    $nama_pelanggan = trim($row_data['nama_pelanggan']);
                    $no_hp = trim($row_data['no_hp'] ?? '');
                    $alamat = trim($row_data['alamat'] ?? '');

                    // Validation
                    if (empty($nama_pelanggan)) {
                        return ['success' => false, 'message' => 'Nama pelanggan tidak boleh kosong'];
                    }

                    if (strlen($nama_pelanggan) > 100) {
                        return ['success' => false, 'message' => 'Nama pelanggan maksimal 100 karakter'];
                    }

                    // Validate phone number if provided
                    if (!empty($no_hp) && !isValidPhone($no_hp)) {
                        return ['success' => false, 'message' => 'Format nomor HP tidak valid (10-15 digit angka)'];
                    }

                    // Check for duplicate phone number
                    if (!empty($no_hp)) {
                        $duplicate_sql = "SELECT id FROM customers WHERE no_hp = ?";
                        $duplicate = fetchOne($duplicate_sql, [$no_hp]);

                        if ($duplicate) {
                            return ['success' => false, 'message' => "Nomor HP '$no_hp' sudah digunakan pelanggan lain"];
                        }
                    }

                    // Check for duplicate name (optional warning, but still allow)
                    $name_check_sql = "SELECT id FROM customers WHERE nama_pelanggan = ?";
                    $name_exists = fetchOne($name_check_sql, [$nama_pelanggan]);

                    if ($name_exists) {
                        // Allow duplicate names but with different phone numbers
                        if (empty($no_hp)) {
                            return ['success' => false, 'message' => "Pelanggan dengan nama '$nama_pelanggan' sudah ada. Berikan nomor HP untuk membedakan"];
                        }
                    }

                    // Insert customer
                    $insert_sql = "INSERT INTO customers (nama_pelanggan, no_hp, alamat, created_at, updated_at)
                                  VALUES (?, ?, ?, NOW(), NOW())";

                    $customer_id = insertData($insert_sql, [
                        $nama_pelanggan,
                        $no_hp ?: null,
                        $alamat ?: null
                    ]);

                    if ($customer_id) {
                        return ['success' => true, 'message' => 'Berhasil'];
                    } else {
                        return ['success' => false, 'message' => 'Gagal menyimpan data'];
                    }

                } catch (Exception $e) {
                    return ['success' => false, 'message' => $e->getMessage()];
                }
            });

            // Log import activity
            ExcelHelper::logImportActivity('customers', $results, $current_user['id'], $filename);

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
                                <li class="breadcrumb-item"><a href="<?= ADMIN_URL ?>customers/">Data Pelanggan</a></li>
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
                                <li>Isi data pelanggan sesuai format yang disediakan</li>
                                <li>Simpan file dalam format CSV, XLS, atau XLSX</li>
                                <li>Upload file menggunakan form di samping</li>
                                <li>Sistem akan memvalidasi dan mengimpor data</li>
                            </ol>

                            <h6 class="mt-4">Format Kolom:</h6>
                            <ul>
                                <li><strong>nama_pelanggan</strong>: Nama lengkap pelanggan (wajib, max 100 karakter)</li>
                                <li><strong>no_hp</strong>: Nomor HP pelanggan (opsional, 10-15 digit)</li>
                                <li><strong>alamat</strong>: Alamat lengkap pelanggan (opsional)</li>
                            </ul>

                            <div class="alert alert-info mt-3">
                                <strong>Catatan:</strong>
                                <ul class="mb-0">
                                    <li>Nomor HP yang sama tidak diperbolehkan</li>
                                    <li>Nama pelanggan boleh sama jika nomor HP berbeda</li>
                                    <li>Kolom yang kosong akan diabaikan</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Import Form -->
                <div class="col-xl-6">
                    <div class="card">
                        <div class="card-header">
                            <h4 class="header-title">Import Data Pelanggan</h4>
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
                                          WHERE il.type = 'customers'
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