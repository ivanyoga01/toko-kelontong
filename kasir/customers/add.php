<?php
/**
 * Add Customer for Kasir
 * Halaman tambah pelanggan baru untuk kasir
 */

require_once '../../includes/kasir_auth.php';

// Page config
$page_title = 'Tambah Pelanggan Baru';
$page_description = 'Tambahkan data pelanggan baru untuk transaksi';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_pelanggan = sanitizeInput($_POST['nama_pelanggan'] ?? '');
    $no_hp = sanitizeInput($_POST['no_hp'] ?? '');
    $alamat = sanitizeInput($_POST['alamat'] ?? '');

    $errors = [];

    // Validation
    if (empty($nama_pelanggan)) {
        $errors[] = 'Nama pelanggan wajib diisi';
    } elseif (strlen($nama_pelanggan) < 2) {
        $errors[] = 'Nama pelanggan minimal 2 karakter';
    }

    if (!empty($no_hp)) {
        if (!preg_match('/^[0-9]{10,15}$/', $no_hp)) {
            $errors[] = 'Nomor HP harus berupa angka 10-15 digit';
        } else {
            // Check if phone number already exists
            $check_phone_sql = "SELECT id FROM customers WHERE no_hp = ?";
            $existing_phone = fetchOne($check_phone_sql, [$no_hp]);
            if ($existing_phone) {
                $errors[] = 'Nomor HP sudah terdaftar dengan pelanggan lain';
            }
        }
    }

    // Check if name already exists
    $check_name_sql = "SELECT id FROM customers WHERE nama_pelanggan = ?";
    $existing_name = fetchOne($check_name_sql, [$nama_pelanggan]);
    if ($existing_name) {
        $errors[] = 'Nama pelanggan sudah terdaftar';
    }

    if (empty($errors)) {
        try {
            $pdo = getDatabase();

            // Insert new customer
            $insert_sql = "INSERT INTO customers (nama_pelanggan, no_hp, alamat, created_at, updated_at)
                          VALUES (?, ?, ?, NOW(), NOW())";

            $stmt = $pdo->prepare($insert_sql);
            $result = $stmt->execute([$nama_pelanggan, $no_hp, $alamat]);

            if ($result) {
                $customer_id = $pdo->lastInsertId();

                // Store customer data for immediate use in POS
                $customer_data = [
                    'id' => $customer_id,
                    'name' => $nama_pelanggan,
                    'phone' => $no_hp,
                    'type' => 'registered'
                ];

                // Set success session
                $_SESSION['success_message'] = "Pelanggan '{$nama_pelanggan}' berhasil ditambahkan";
                $_SESSION['new_customer'] = json_encode($customer_data);

                header('Location: search.php?search=' . urlencode($nama_pelanggan));
                exit;
            } else {
                $errors[] = 'Gagal menyimpan data pelanggan';
            }
        } catch (Exception $e) {
            error_log("Add customer error: " . $e->getMessage());
            $errors[] = 'Terjadi kesalahan sistem';
        }
    }
}

// Success/Error messages
$success_message = $_SESSION['success_message'] ?? '';
$error_message = $_SESSION['error_message'] ?? '';
unset($_SESSION['success_message'], $_SESSION['error_message']);

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
                                <li class="breadcrumb-item"><a href="<?= KASIR_URL ?>penjualan.php">POS</a></li>
                                <li class="breadcrumb-item"><a href="search.php">Cari Pelanggan</a></li>
                                <li class="breadcrumb-item active"><?= $page_title ?></li>
                            </ol>
                        </div>
                        <h4 class="page-title"><?= $page_title ?></h4>
                    </div>
                </div>
            </div>

            <?php if ($success_message): ?>
            <div class="row">
                <div class="col-12">
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <strong>Berhasil!</strong> <?= htmlspecialchars($success_message) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <?php if (!empty($errors)): ?>
            <div class="row">
                <div class="col-12">
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <strong>Error!</strong>
                        <ul class="mb-0 mt-2">
                            <?php foreach ($errors as $error): ?>
                                <li><?= htmlspecialchars($error) ?></li>
                            <?php endforeach; ?>
                        </ul>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Add Customer Form -->
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-header">
                            <h4 class="header-title">
                                <i class="mdi mdi-account-plus me-2"></i>
                                Form Tambah Pelanggan
                            </h4>
                        </div>
                        <div class="card-body">
                            <form method="POST" id="addCustomerForm">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="nama_pelanggan" class="form-label">
                                                Nama Pelanggan <span class="text-danger">*</span>
                                            </label>
                                            <input type="text"
                                                   class="form-control"
                                                   id="nama_pelanggan"
                                                   name="nama_pelanggan"
                                                   value="<?= htmlspecialchars($_POST['nama_pelanggan'] ?? '') ?>"
                                                   placeholder="Masukkan nama lengkap pelanggan"
                                                   required
                                                   autocomplete="off">
                                            <div class="invalid-feedback"></div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="no_hp" class="form-label">
                                                Nomor HP <span class="text-muted">(Opsional)</span>
                                            </label>
                                            <input type="tel"
                                                   class="form-control"
                                                   id="no_hp"
                                                   name="no_hp"
                                                   value="<?= htmlspecialchars($_POST['no_hp'] ?? '') ?>"
                                                   placeholder="Contoh: 081234567890"
                                                   pattern="[0-9]{10,15}"
                                                   autocomplete="off">
                                            <div class="invalid-feedback"></div>
                                            <small class="text-muted">Format: 10-15 digit angka tanpa spasi atau tanda hubung</small>
                                        </div>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="alamat" class="form-label">
                                        Alamat <span class="text-muted">(Opsional)</span>
                                    </label>
                                    <textarea class="form-control"
                                              id="alamat"
                                              name="alamat"
                                              rows="3"
                                              placeholder="Masukkan alamat lengkap pelanggan"><?= htmlspecialchars($_POST['alamat'] ?? '') ?></textarea>
                                    <div class="invalid-feedback"></div>
                                </div>

                                <div class="d-flex justify-content-between">
                                    <div>
                                        <a href="search.php" class="btn btn-secondary">
                                            <i class="mdi mdi-arrow-left"></i> Kembali ke Pencarian
                                        </a>
                                    </div>
                                    <div>
                                        <button type="reset" class="btn btn-outline-secondary me-2">
                                            <i class="mdi mdi-refresh"></i> Reset
                                        </button>
                                        <button type="submit" class="btn btn-success">
                                            <i class="mdi mdi-content-save"></i> Simpan Pelanggan
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Info Card -->
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="card border-info">
                        <div class="card-body">
                            <div class="d-flex align-items-start">
                                <div class="flex-shrink-0">
                                    <i class="mdi mdi-information text-info" style="font-size: 1.5rem;"></i>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <h6 class="text-info mb-2">Informasi Penting</h6>
                                    <ul class="mb-0 text-muted">
                                        <li>Hanya <strong>Nama Pelanggan</strong> yang wajib diisi</li>
                                        <li>Nomor HP akan memudahkan pencarian pelanggan di masa depan</li>
                                        <li>Setelah pelanggan berhasil ditambahkan, Anda dapat langsung memilihnya untuk transaksi</li>
                                        <li>Data pelanggan akan tersimpan dan dapat digunakan untuk transaksi selanjutnya</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div> <!-- container -->

    </div> <!-- content -->

    <?php include '../../includes/footer.php'; ?>

</div>

<!-- bundle -->
<script src="<?= ASSETS_URL ?>Adminto_v4.0.0/Vertical/dist/assets/js/vendor.min.js"></script>
<script src="<?= ASSETS_URL ?>Adminto_v4.0.0/Vertical/dist/assets/js/app.min.js"></script>

<script>
$(document).ready(function() {
    // Auto focus on name input
    $('#nama_pelanggan').focus();

    // Phone number formatting and validation
    $('#no_hp').on('input', function() {
        let value = $(this).val().replace(/\D/g, ''); // Remove non-digits
        $(this).val(value);

        // Validation feedback
        const feedback = $(this).siblings('.invalid-feedback');
        if (value.length > 0 && (value.length < 10 || value.length > 15)) {
            $(this).addClass('is-invalid');
            feedback.text('Nomor HP harus 10-15 digit');
        } else {
            $(this).removeClass('is-invalid');
            feedback.text('');
        }
    });

    // Form validation
    $('#addCustomerForm').on('submit', function(e) {
        let isValid = true;

        // Reset previous validation
        $('.form-control').removeClass('is-invalid');
        $('.invalid-feedback').text('');

        // Validate name
        const name = $('#nama_pelanggan').val().trim();
        if (name.length < 2) {
            $('#nama_pelanggan').addClass('is-invalid')
                .siblings('.invalid-feedback').text('Nama pelanggan minimal 2 karakter');
            isValid = false;
        }

        // Validate phone if provided
        const phone = $('#no_hp').val().trim();
        if (phone.length > 0 && (phone.length < 10 || phone.length > 15 || !/^\d+$/.test(phone))) {
            $('#no_hp').addClass('is-invalid')
                .siblings('.invalid-feedback').text('Nomor HP harus 10-15 digit angka');
            isValid = false;
        }

        if (!isValid) {
            e.preventDefault();
            // Focus on first invalid field
            $('.is-invalid').first().focus();
        }
    });

    // Auto-capitalize name
    $('#nama_pelanggan').on('blur', function() {
        const value = $(this).val();
        if (value) {
            // Capitalize each word
            const capitalized = value.toLowerCase().replace(/\b\w/g, l => l.toUpperCase());
            $(this).val(capitalized);
        }
    });

    // Check for new customer data from session
    <?php if (isset($_SESSION['new_customer'])): ?>
    const newCustomerData = <?= $_SESSION['new_customer'] ?>;

    // Show option to use for transaction
    if (newCustomerData && confirm('Pelanggan baru berhasil ditambahkan. Gunakan untuk transaksi sekarang?')) {
        localStorage.setItem('selected_customer', JSON.stringify(newCustomerData));
        window.location.href = '<?= KASIR_URL ?>penjualan.php';
    }

    <?php unset($_SESSION['new_customer']); ?>
    <?php endif; ?>
});
</script>

</body>
</html>