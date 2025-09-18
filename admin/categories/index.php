<?php
/**
 * Admin Categories Management - List
 * Halaman daftar kategori barang untuk admin
 */

require_once '../../includes/admin_auth.php';

$page_title = 'Kelola Kategori Barang';
$page_description = 'Manajemen kategori barang toko kelontong';

// Pagination
$page = max(1, intval($_GET['page'] ?? 1));
$search = sanitizeInput($_GET['search'] ?? '');

// Count total records
$count_sql = "SELECT COUNT(*) as total FROM categories";
$count_params = [];

if (!empty($search)) {
    $count_sql .= " WHERE nama_kategori LIKE ? OR deskripsi LIKE ?";
    $count_params = ["%$search%", "%$search%"];
}

$total_records = fetchOne($count_sql, $count_params)['total'];
$pagination = paginate($total_records, $page);

// Get categories data
$sql = "SELECT * FROM categories";
if (!empty($search)) {
    $sql .= " WHERE nama_kategori LIKE ? OR deskripsi LIKE ?";
}
$sql .= " ORDER BY nama_kategori ASC LIMIT {$pagination['records_per_page']} OFFSET {$pagination['offset']}";

$categories = fetchAll($sql, $count_params);

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
                                <li class="breadcrumb-item active">Kategori Barang</li>
                            </ol>
                        </div>
                        <h4 class="page-title">Kelola Kategori Barang</h4>
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

            <!-- Categories List -->
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <div class="row align-items-center">
                                <div class="col">
                                    <h4 class="header-title">Daftar Kategori Barang</h4>
                                </div>
                                <div class="col-auto">
                                    <button type="button" class="btn btn-success" data-toggle="modal" data-target="#addCategoryModal">
                                        <i class="mdi mdi-plus"></i> Tambah Kategori
                                    </button>
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
                                                   placeholder="Cari kategori..."
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

                            <!-- Categories Table -->
                            <div class="table-responsive">
                                <table class="table table-striped table-bordered table-hover">
                                    <thead class="thead-dark">
                                        <tr>
                                            <th width="5%">No</th>
                                            <th width="25%">Nama Kategori</th>
                                            <th width="50%">Deskripsi</th>
                                            <th width="15%">Tanggal Dibuat</th>
                                            <th width="15%">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if ($categories): ?>
                                            <?php foreach ($categories as $index => $category): ?>
                                                <tr>
                                                    <td><?php echo $pagination['offset'] + $index + 1; ?></td>
                                                    <td><?php echo htmlspecialchars($category['nama_kategori']); ?></td>
                                                    <td><?php echo htmlspecialchars($category['deskripsi']); ?></td>
                                                    <td><?php echo formatDate($category['created_at']); ?></td>
                                                    <td>
                                                        <div class="btn-group" role="group">
                                                            <button type="button" class="btn btn-sm btn-warning"
                                                                    onclick="editCategory(<?php echo $category['id']; ?>)">
                                                                <i class="fe-edit"></i>
                                                            </button>
                                                            <button type="button" class="btn btn-sm btn-danger"
                                                                    onclick="deleteCategory(<?php echo $category['id']; ?>, '<?php echo htmlspecialchars($category['nama_kategori']); ?>')">
                                                                <i class="fe-trash"></i>
                                                            </button>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="5" class="text-center">
                                                    <?php if (!empty($search)): ?>
                                                        Tidak ada kategori yang ditemukan dengan kata kunci "<?php echo htmlspecialchars($search); ?>"
                                                    <?php else: ?>
                                                        Belum ada kategori yang ditambahkan
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Pagination -->
                            <?php if ($pagination['total_pages'] > 1): ?>
                                <nav aria-label="Categories pagination">
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

<!-- Add Category Modal -->
<div class="modal fade" id="addCategoryModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Tambah Kategori Baru</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form id="addCategoryForm" method="POST" action="save.php">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="nama_kategori">Nama Kategori *</label>
                        <input type="text" class="form-control" id="nama_kategori" name="nama_kategori" required maxlength="100">
                    </div>
                    <div class="form-group">
                        <label for="deskripsi">Deskripsi</label>
                        <textarea class="form-control" id="deskripsi" name="deskripsi" rows="3"></textarea>
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

<!-- Edit Category Modal -->
<div class="modal fade" id="editCategoryModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Kategori</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form id="editCategoryForm" method="POST" action="save.php">
                <input type="hidden" id="edit_id" name="id">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="edit_nama_kategori">Nama Kategori *</label>
                        <input type="text" class="form-control" id="edit_nama_kategori" name="nama_kategori" required maxlength="100">
                    </div>
                    <div class="form-group">
                        <label for="edit_deskripsi">Deskripsi</label>
                        <textarea class="form-control" id="edit_deskripsi" name="deskripsi" rows="3"></textarea>
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
// Edit category function
function editCategory(id) {
    $.ajax({
        url: 'get.php',
        method: 'GET',
        data: { id: id },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                $('#edit_id').val(response.data.id);
                $('#edit_nama_kategori').val(response.data.nama_kategori);
                $('#edit_deskripsi').val(response.data.deskripsi);
                $('#editCategoryModal').modal('show');
            } else {
                alert('Error: ' + response.message);
            }
        },
        error: function() {
            alert('Terjadi kesalahan sistem!');
        }
    });
}

// Delete category function
function deleteCategory(id, name) {
    if (confirm('Apakah Anda yakin ingin menghapus kategori "' + name + '"?\n\nCatatan: Produk yang menggunakan kategori ini akan menjadi tanpa kategori.')) {
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

// Form validation and submission
$('#addCategoryForm, #editCategoryForm').on('submit', function(e) {
    e.preventDefault();

    const namaKategori = $(this).find('input[name="nama_kategori"]').val().trim();

    if (!namaKategori) {
        alert('Nama kategori harus diisi!');
        return false;
    }

    const formData = new FormData(this);
    const submitButton = $(this).find('button[type="submit"]');
    const originalText = submitButton.text();

    // Disable submit button
    submitButton.prop('disabled', true).text('Menyimpan...');

    $.ajax({
        url: 'save.php',
        method: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                // Show success message and redirect
                alert(response.message);
                location.reload();
            } else {
                alert('Error: ' + response.message);
            }
        },
        error: function(xhr, status, error) {
            console.error('AJAX Error:', error);
            alert('Terjadi kesalahan sistem!');
        },
        complete: function() {
            // Re-enable submit button
            submitButton.prop('disabled', false).text(originalText);
        }
    });
});
</script>