<?php
/**
 * Customer Search for Kasir
 * Halaman pencarian pelanggan untuk kasir
 */

require_once '../../includes/kasir_auth.php';

// Page config
$page_title = 'Cari Pelanggan';
$page_description = 'Pencarian data pelanggan untuk transaksi';

// Handle search
$search = sanitizeInput($_GET['search'] ?? '');
$customers = [];

if (!empty($search) && strlen($search) >= 2) {
    // Search customers
    $search_sql = "SELECT id, nama_pelanggan, no_hp, alamat,
                   (SELECT COUNT(*) FROM transactions WHERE customer_id = customers.id) as total_transaksi,
                   (SELECT COALESCE(SUM(total), 0) FROM transactions WHERE customer_id = customers.id) as total_belanja,
                   (SELECT MAX(tanggal_transaksi) FROM transactions WHERE customer_id = customers.id) as transaksi_terakhir
                   FROM customers
                   WHERE nama_pelanggan LIKE ? OR no_hp LIKE ?
                   ORDER BY nama_pelanggan ASC
                   LIMIT 20";

    $searchTerm = "%$search%";
    $customers = fetchAll($search_sql, [$searchTerm, $searchTerm]);
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
                                <li class="breadcrumb-item"><a href="<?= KASIR_URL ?>penjualan.php">POS</a></li>
                                <li class="breadcrumb-item">Pelanggan</li>
                                <li class="breadcrumb-item active"><?= $page_title ?></li>
                            </ol>
                        </div>
                        <h4 class="page-title"><?= $page_title ?></h4>
                    </div>
                </div>
            </div>

            <!-- Search Form -->
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <form method="GET" class="row g-3 align-items-end">
                                <div class="col-md-6">
                                    <label class="form-label">Cari Pelanggan</label>
                                    <input type="text"
                                           name="search"
                                           class="form-control"
                                           placeholder="Masukkan nama atau nomor HP pelanggan..."
                                           value="<?= htmlspecialchars($search) ?>"
                                           minlength="2"
                                           autocomplete="off">
                                    <small class="text-muted">Minimal 2 karakter untuk pencarian</small>
                                </div>
                                <div class="col-md-3">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="mdi mdi-magnify"></i> Cari
                                    </button>
                                    <?php if ($search): ?>
                                    <a href="search.php" class="btn btn-outline-secondary">
                                        <i class="mdi mdi-close"></i> Reset
                                    </a>
                                    <?php endif; ?>
                                </div>
                                <div class="col-md-3">
                                    <a href="add.php" class="btn btn-success">
                                        <i class="mdi mdi-plus-circle"></i> Tambah Pelanggan Baru
                                    </a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Search Results -->
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <?php if (empty($search)): ?>
                                <div class="text-center py-4">
                                    <i class="mdi mdi-account-search text-muted" style="font-size: 4rem;"></i>
                                    <h4 class="mt-3">Cari Pelanggan</h4>
                                    <p class="text-muted">Masukkan nama atau nomor HP pelanggan untuk memulai pencarian</p>
                                </div>
                            <?php elseif (strlen($search) < 2): ?>
                                <div class="text-center py-4">
                                    <i class="mdi mdi-information text-warning" style="font-size: 3rem;"></i>
                                    <h4 class="mt-3">Kata kunci terlalu pendek</h4>
                                    <p class="text-muted">Masukkan minimal 2 karakter untuk pencarian</p>
                                </div>
                            <?php elseif (empty($customers)): ?>
                                <div class="text-center py-4">
                                    <i class="mdi mdi-account-off text-muted" style="font-size: 3rem;"></i>
                                    <h4 class="mt-3">Pelanggan tidak ditemukan</h4>
                                    <p class="text-muted">
                                        Tidak ada pelanggan yang sesuai dengan "<strong><?= htmlspecialchars($search) ?></strong>"
                                    </p>
                                    <a href="add.php" class="btn btn-success mt-2">
                                        <i class="mdi mdi-plus-circle"></i> Tambah Pelanggan Baru
                                    </a>
                                </div>
                            <?php else: ?>
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h5 class="mb-0">
                                        Hasil Pencarian "<strong><?= htmlspecialchars($search) ?></strong>"
                                        <span class="badge bg-info"><?= count($customers) ?> ditemukan</span>
                                    </h5>
                                </div>

                                <div class="table-responsive">
                                    <table class="table table-centered table-nowrap table-hover mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Nama Pelanggan</th>
                                                <th>No. HP</th>
                                                <th>Alamat</th>
                                                <th>Transaksi</th>
                                                <th>Total Belanja</th>
                                                <th>Terakhir</th>
                                                <th style="width: 100px;">Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($customers as $customer): ?>
                                            <tr>
                                                <td>
                                                    <strong><?= htmlspecialchars($customer['nama_pelanggan']) ?></strong>
                                                </td>
                                                <td>
                                                    <?php if (!empty($customer['no_hp'])): ?>
                                                        <a href="tel:<?= htmlspecialchars($customer['no_hp']) ?>" class="text-decoration-none">
                                                            <?= htmlspecialchars($customer['no_hp']) ?>
                                                        </a>
                                                    <?php else: ?>
                                                        <span class="text-muted">-</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if (!empty($customer['alamat'])): ?>
                                                        <small><?= htmlspecialchars($customer['alamat']) ?></small>
                                                    <?php else: ?>
                                                        <span class="text-muted">-</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <span class="badge bg-info"><?= $customer['total_transaksi'] ?>x</span>
                                                </td>
                                                <td class="currency">
                                                    <?= formatCurrency($customer['total_belanja']) ?>
                                                </td>
                                                <td>
                                                    <?php if ($customer['transaksi_terakhir']): ?>
                                                        <small class="text-muted">
                                                            <?= formatDate($customer['transaksi_terakhir']) ?>
                                                        </small>
                                                    <?php else: ?>
                                                        <span class="text-muted">-</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <button type="button"
                                                            class="btn btn-primary btn-sm select-customer"
                                                            data-id="<?= $customer['id'] ?>"
                                                            data-name="<?= htmlspecialchars($customer['nama_pelanggan']) ?>"
                                                            data-phone="<?= htmlspecialchars($customer['no_hp']) ?>"
                                                            title="Pilih Pelanggan untuk Transaksi">
                                                        <i class="mdi mdi-check"></i> Pilih
                                                    </button>
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

    <?php include '../../includes/footer.php'; ?>

</div>

<!-- bundle -->
<script src="<?= ASSETS_URL ?>Adminto_v4.0.0/Vertical/dist/assets/js/vendor.min.js"></script>
<script src="<?= ASSETS_URL ?>Adminto_v4.0.0/Vertical/dist/assets/js/app.min.js"></script>

<script>
$(document).ready(function() {
    // Auto focus on search input
    $('input[name="search"]').focus();

    // Select customer functionality
    $('.select-customer').click(function() {
        const customerId = $(this).data('id');
        const customerName = $(this).data('name');
        const customerPhone = $(this).data('phone');

        // Store customer data in localStorage for POS system
        const customerData = {
            id: customerId,
            name: customerName,
            phone: customerPhone,
            type: 'registered'
        };

        localStorage.setItem('selected_customer', JSON.stringify(customerData));

        // Show success message
        alert('Pelanggan "' + customerName + '" telah dipilih untuk transaksi');

        // Redirect to POS
        window.location.href = '<?= KASIR_URL ?>penjualan.php';
    });

    // Enter key to search
    $('input[name="search"]').on('keypress', function(e) {
        if (e.which === 13) {
            $(this).closest('form').submit();
        }
    });

    // Real-time search validation
    $('input[name="search"]').on('input', function() {
        const search = $(this).val().trim();
        const submitBtn = $(this).closest('form').find('button[type="submit"]');

        if (search.length < 2) {
            submitBtn.prop('disabled', true);
        } else {
            submitBtn.prop('disabled', false);
        }
    });

    // Initial validation
    const initialSearch = $('input[name="search"]').val().trim();
    if (initialSearch.length < 2) {
        $('button[type="submit"]').prop('disabled', true);
    }
});
</script>

</body>
</html>