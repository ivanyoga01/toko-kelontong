<?php
/**
 * Admin Customers Management - List
 * Halaman daftar pelanggan untuk admin
 */

require_once '../../includes/admin_auth.php';

$page_title = 'Kelola Data Pelanggan';
$page_description = 'Manajemen data pelanggan toko kelontong';

// Pagination and search
$page = max(1, intval($_GET['page'] ?? 1));
$search = sanitizeInput($_GET['search'] ?? '');

// Count total records
$count_sql = "SELECT COUNT(*) as total FROM customers";
$count_params = [];

if (!empty($search)) {
    $count_sql .= " WHERE nama_pelanggan LIKE ? OR no_hp LIKE ? OR alamat LIKE ?";
    $count_params = ["%$search%", "%$search%", "%$search%"];
}

$total_records = fetchOne($count_sql, $count_params)['total'];
$pagination = paginate($total_records, $page);

// Get customers data
$sql = "SELECT c.*,
        (SELECT COUNT(*) FROM transactions WHERE customer_id = c.id) as total_transaksi,
        (SELECT SUM(total) FROM transactions WHERE customer_id = c.id) as total_belanja
        FROM customers c";

if (!empty($search)) {
    $sql .= " WHERE c.nama_pelanggan LIKE ? OR c.no_hp LIKE ? OR c.alamat LIKE ?";
}

$sql .= " ORDER BY c.nama_pelanggan ASC LIMIT {$pagination['records_per_page']} OFFSET {$pagination['offset']}";

$customers = fetchAll($sql, $count_params);

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
                                <li class="breadcrumb-item"><a href="<?php echo ADMIN_URL; ?>dashboard.php">Dashboard</a></li>
                                <li class="breadcrumb-item active">Data Pelanggan</li>
                            </ol>
                        </div>
                        <h4 class="page-title">Kelola Data Pelanggan</h4>
                    </div>
                </div>
            </div>

            <?php
            $flash = getFlashMessage();
            if ($flash):
            ?>
                <div class="row">
                    <div class="col-12">
                        <div class="alert alert-<?php echo $flash['type'] === 'error' ? 'danger' : $flash['type']; ?> alert-dismissible fade show">
                            <?php echo htmlspecialchars($flash['message']); ?>
                            <button type="button" class="close" data-dismiss="alert">
                                <span>&times;</span>
                            </button>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Customers List -->
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <div class="row align-items-center">
                                <div class="col">
                                    <h4 class="header-title">Daftar Pelanggan</h4>
                                </div>
                                <div class="col-auto">
                                    <div class="btn-group" role="group">
                                        <button type="button" class="btn btn-info" onclick="exportCustomers()">
                                            <i class="mdi mdi-download"></i> Export
                                        </button>
                                        <a href="import.php" class="btn btn-warning">
                                            <i class="mdi mdi-upload"></i> Import
                                        </a>
                                        <button type="button" class="btn btn-success" data-toggle="modal" data-target="#addCustomerModal">
                                            <i class="mdi mdi-plus"></i> Tambah Pelanggan
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <!-- Search Form -->
                            <div class="row mb-3">
                                <div class="col-lg-4">
                                    <form method="GET" class="form-inline">
                                        <div class="form-group">
                                            <input type="text" name="search" class="form-control"
                                                   placeholder="Cari pelanggan..."
                                                   value="<?php echo htmlspecialchars($search); ?>">
                                        </div>
                                        <button type="submit" class="btn btn-primary ml-2">
                                            <i class="fe-search"></i>
                                        </button>
                                        <?php if (!empty($search)): ?>
                                            <a href="index.php" class="btn btn-outline-secondary ml-2">Reset</a>
                                        <?php endif; ?>
                                    </form>
                                </div>
                            </div>

                            <!-- Customers Table -->
                            <div class="table-responsive">
                                <table class="table table-striped table-bordered table-hover">
                                    <thead class="thead-dark">
                                        <tr>
                                            <th width="5%">No</th>
                                            <th width="25%">Nama Pelanggan</th>
                                            <th width="15%">No. HP</th>
                                            <th width="25%">Alamat</th>
                                            <th width="10%">Total Transaksi</th>
                                            <th width="10%">Total Belanja</th>
                                            <th width="10%">Tanggal Daftar</th>
                                            <th width="10%">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if ($customers): ?>
                                            <?php foreach ($customers as $index => $customer): ?>
                                                <tr>
                                                    <td><?php echo $pagination['offset'] + $index + 1; ?></td>
                                                    <td><?php echo htmlspecialchars($customer['nama_pelanggan']); ?></td>
                                                    <td><?php echo htmlspecialchars($customer['no_hp'] ?? '-'); ?></td>
                                                    <td><?php echo htmlspecialchars($customer['alamat'] ?? '-'); ?></td>
                                                    <td class="text-center">
                                                        <span class="badge badge-info"><?php echo $customer['total_transaksi']; ?></span>
                                                    </td>
                                                    <td class="text-right"><?php echo formatCurrency($customer['total_belanja'] ?? 0); ?></td>
                                                    <td><?php echo formatDate($customer['created_at']); ?></td>
                                                    <td>
                                                        <div class="btn-group" role="group">
                                                            <button type="button" class="btn btn-sm btn-warning"
                                                                    onclick="editCustomer(<?php echo $customer['id']; ?>)">
                                                                <i class="fe-edit"></i>
                                                            </button>
                                                            <button type="button" class="btn btn-sm btn-danger"
                                                                    onclick="deleteCustomer(<?php echo $customer['id']; ?>, '<?php echo htmlspecialchars($customer['nama_pelanggan']); ?>')">
                                                                <i class="fe-trash"></i>
                                                            </button>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="8" class="text-center">
                                                    <?php if (!empty($search)): ?>
                                                        Tidak ada pelanggan yang ditemukan dengan kata kunci "<?php echo htmlspecialchars($search); ?>"
                                                    <?php else: ?>
                                                        Belum ada pelanggan yang terdaftar
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Pagination -->
                            <?php if ($pagination['total_pages'] > 1): ?>
                                <nav aria-label="Customers pagination">
                                    <ul class="pagination justify-content-center">
                                        <li class="page-item <?php echo !$pagination['has_previous'] ? 'disabled' : ''; ?>">
                                            <a class="page-link" href="?page=<?php echo $pagination['previous_page']; ?>&search=<?php echo urlencode($search); ?>">Previous</a>
                                        </li>

                                        <?php for ($i = 1; $i <= $pagination['total_pages']; $i++): ?>
                                            <li class="page-item <?php echo $i == $pagination['current_page'] ? 'active' : ''; ?>">
                                                <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>"><?php echo $i; ?></a>
                                            </li>
                                        <?php endfor; ?>

                                        <li class="page-item <?php echo !$pagination['has_next'] ? 'disabled' : ''; ?>">
                                            <a class="page-link" href="?page=<?php echo $pagination['next_page']; ?>&search=<?php echo urlencode($search); ?>">Next</a>
                                        </li>
                                    </ul>
                                </nav>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

        </div> <!-- container-fluid -->
    </div> <!-- content -->
</div> <!-- content-page -->

<!-- Add Customer Modal -->
<div class="modal fade" id="addCustomerModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Tambah Pelanggan Baru</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form id="addCustomerForm" method="POST" action="save.php">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="nama_pelanggan">Nama Pelanggan *</label>
                        <input type="text" class="form-control" id="nama_pelanggan" name="nama_pelanggan"
                               required maxlength="100" placeholder="Masukkan nama pelanggan">
                    </div>
                    <div class="form-group">
                        <label for="no_hp">No. HP</label>
                        <input type="text" class="form-control" id="no_hp" name="no_hp"
                               maxlength="15" placeholder="Contoh: 081234567890">
                        <small class="text-muted">Format: 10-15 digit angka</small>
                    </div>
                    <div class="form-group">
                        <label for="alamat">Alamat</label>
                        <textarea class="form-control" id="alamat" name="alamat" rows="3"
                                  placeholder="Alamat lengkap pelanggan"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Customer Modal -->
<div class="modal fade" id="editCustomerModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Pelanggan</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form id="editCustomerForm" method="POST" action="save.php">
                <input type="hidden" id="edit_id" name="id">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="edit_nama_pelanggan">Nama Pelanggan *</label>
                        <input type="text" class="form-control" id="edit_nama_pelanggan" name="nama_pelanggan"
                               required maxlength="100" placeholder="Masukkan nama pelanggan">
                    </div>
                    <div class="form-group">
                        <label for="edit_no_hp">No. HP</label>
                        <input type="text" class="form-control" id="edit_no_hp" name="no_hp"
                               maxlength="15" placeholder="Contoh: 081234567890">
                        <small class="text-muted">Format: 10-15 digit angka</small>
                    </div>
                    <div class="form-group">
                        <label for="edit_alamat">Alamat</label>
                        <textarea class="form-control" id="edit_alamat" name="alamat" rows="3"
                                  placeholder="Alamat lengkap pelanggan"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-warning">Update</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>

<script>
// Edit customer function
function editCustomer(id) {
    $.ajax({
        url: 'get.php',
        method: 'GET',
        data: { id: id },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                $('#edit_id').val(response.data.id);
                $('#edit_nama_pelanggan').val(response.data.nama_pelanggan);
                $('#edit_no_hp').val(response.data.no_hp);
                $('#edit_alamat').val(response.data.alamat);
                $('#editCustomerModal').modal('show');
            } else {
                alert('Error: ' + response.message);
            }
        },
        error: function() {
            alert('Terjadi kesalahan sistem!');
        }
    });
}

// Delete customer function
function deleteCustomer(id, name) {
    if (confirm('Apakah Anda yakin ingin menghapus pelanggan "' + name + '"?\n\nCatatan: Data transaksi pelanggan ini akan tetap ada namun ditandai sebagai pelanggan yang dihapus.')) {
        $.ajax({
            url: 'delete.php',
            method: 'POST',
            data: { id: id },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert('Error: ' + response.message);
                }
            },
            error: function() {
                alert('Terjadi kesalahan sistem!');
            }
        });
    }
}

// Phone number validation
function validatePhone(phone) {
    const phoneRegex = /^[0-9]{10,15}$/;
    return phoneRegex.test(phone);
}

// Form validation
$('#addCustomerForm, #editCustomerForm').on('submit', function(e) {
    const namaPelanggan = $(this).find('input[name="nama_pelanggan"]').val().trim();
    const noHp = $(this).find('input[name="no_hp"]').val().trim();

    if (!namaPelanggan) {
        e.preventDefault();
        alert('Nama pelanggan harus diisi!');
        return false;
    }

    if (noHp && !validatePhone(noHp)) {
        e.preventDefault();
        alert('Format nomor HP tidak valid! Gunakan 10-15 digit angka.');
        return false;
    }
});

// Auto format phone number
$('#no_hp, #edit_no_hp').on('input', function() {
    // Remove non-numeric characters
    this.value = this.value.replace(/[^0-9]/g, '');
});

// Export customers function
function exportCustomers() {
    const search = '<?= urlencode($search) ?>';

    let url = 'export.php?';
    if (search) url += 'search=' + search + '&';

    window.open(url, '_blank');
}
</script>