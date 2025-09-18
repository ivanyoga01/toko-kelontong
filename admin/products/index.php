<?php
/**
 * Admin Products Management - List
 * Halaman daftar produk/barang untuk admin
 */

require_once '../../includes/admin_auth.php';

$page_title = 'Kelola Data Barang';
$page_description = 'Manajemen data barang toko kelontong';

// Pagination and filters
$page = max(1, intval($_GET['page'] ?? 1));
$search = sanitizeInput($_GET['search'] ?? '');
$category_filter = intval($_GET['category'] ?? 0);
$status_filter = sanitizeInput($_GET['status'] ?? '');

// Build query conditions
$where_conditions = [];
$params = [];

if (!empty($search)) {
    $where_conditions[] = "(p.nama_barang LIKE ? OR p.kode_barang LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($category_filter > 0) {
    $where_conditions[] = "p.category_id = ?";
    $params[] = $category_filter;
}

if (!empty($status_filter)) {
    $where_conditions[] = "p.status = ?";
    $params[] = $status_filter;
}

$where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

// Count total records
$count_sql = "SELECT COUNT(*) as total FROM products p $where_clause";
$total_records = fetchOne($count_sql, $params)['total'];
$pagination = paginate($total_records, $page);

// Get products data
$sql = "SELECT p.*, c.nama_kategori
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id
        $where_clause
        ORDER BY p.nama_barang ASC
        LIMIT {$pagination['records_per_page']} OFFSET {$pagination['offset']}";
$products = fetchAll($sql, $params);

// Get categories for filter
$categories = fetchAll("SELECT * FROM categories ORDER BY nama_kategori ASC");

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
                                <li class="breadcrumb-item active">Data Barang</li>
                            </ol>
                        </div>
                        <h4 class="page-title">Kelola Data Barang</h4>
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

            <!-- Products List -->
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <div class="row align-items-center">
                                <div class="col">
                                    <h4 class="header-title">Daftar Barang</h4>
                                </div>
                                <div class="col-auto">
                                    <div class="btn-group" role="group">
                                        <button type="button" class="btn btn-info" onclick="exportProducts()">
                                            <i class="mdi mdi-download"></i> Export
                                        </button>
                                        <a href="import.php" class="btn btn-warning">
                                            <i class="mdi mdi-upload"></i> Import
                                        </a>
                                        <button type="button" class="btn btn-success" data-toggle="modal" data-target="#addProductModal">
                                            <i class="mdi mdi-plus"></i> Tambah Barang
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <!-- Search and Filter Form -->
                            <form method="GET" class="mb-3">
                                <div class="row">
                                    <div class="col-lg-3">
                                        <input type="text" name="search" class="form-control"
                                               placeholder="Cari barang..."
                                               value="<?php echo htmlspecialchars($search); ?>">
                                    </div>
                                    <div class="col-lg-2">
                                        <select name="category" class="form-control">
                                            <option value="">Semua Kategori</option>
                                            <?php foreach ($categories as $category): ?>
                                                <option value="<?php echo $category['id']; ?>"
                                                        <?php echo $category_filter == $category['id'] ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($category['nama_kategori']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-lg-2">
                                        <select name="status" class="form-control">
                                            <option value="">Semua Status</option>
                                            <option value="aktif" <?php echo $status_filter === 'aktif' ? 'selected' : ''; ?>>Aktif</option>
                                            <option value="nonaktif" <?php echo $status_filter === 'nonaktif' ? 'selected' : ''; ?>>Non-aktif</option>
                                        </select>
                                    </div>
                                    <div class="col-lg-3">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fe-search"></i> Cari
                                        </button>
                                        <a href="index.php" class="btn btn-outline-secondary">Reset</a>
                                    </div>
                                </div>
                            </form>

                            <!-- Products Table -->
                            <div class="table-responsive">
                                <table class="table table-striped table-bordered table-hover">
                                    <thead class="thead-dark">
                                        <tr>
                                            <th width="5%">No</th>
                                            <th width="10%">Gambar</th>
                                            <th width="10%">Kode</th>
                                            <th width="20%">Nama Barang</th>
                                            <th width="12%">Kategori</th>
                                            <th width="8%">Stok</th>
                                            <th width="10%">Harga Beli</th>
                                            <th width="10%">Harga Jual</th>
                                            <th width="8%">Status</th>
                                            <th width="12%">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if ($products): ?>
                                            <?php foreach ($products as $index => $product): ?>
                                                <tr>
                                                    <td><?php echo $pagination['offset'] + $index + 1; ?></td>
                                                    <td class="text-center">
                                                        <?php if (!empty($product['gambar'])): ?>
                                                            <img src="<?php echo UPLOADS_URL; ?>products/<?php echo $product['gambar']; ?>"
                                                                 alt="<?php echo htmlspecialchars($product['nama_barang']); ?>"
                                                                 class="img-thumbnail" style="max-width: 50px; max-height: 50px;">
                                                        <?php else: ?>
                                                            <div class="bg-light d-flex align-items-center justify-content-center"
                                                                 style="width: 50px; height: 50px;">
                                                                <i class="fe-image text-muted"></i>
                                                            </div>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td><?php echo htmlspecialchars($product['kode_barang']); ?></td>
                                                    <td><?php echo htmlspecialchars($product['nama_barang']); ?></td>
                                                    <td><?php echo htmlspecialchars($product['nama_kategori'] ?? 'Tanpa Kategori'); ?></td>
                                                    <td>
                                                        <span class="badge badge-<?php echo $product['stok'] <= 10 ? 'danger' : 'success'; ?>">
                                                            <?php echo $product['stok']; ?> <?php echo htmlspecialchars($product['satuan']); ?>
                                                        </span>
                                                    </td>
                                                    <td class="text-right"><?php echo formatCurrency($product['harga_beli']); ?></td>
                                                    <td class="text-right"><?php echo formatCurrency($product['harga_jual']); ?></td>
                                                    <td>
                                                        <span class="badge badge-<?php echo $product['status'] === 'aktif' ? 'success' : 'secondary'; ?>">
                                                            <?php echo ucfirst($product['status']); ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <div class="btn-group" role="group">
                                                            <button type="button" class="btn btn-sm btn-info"
                                                                    onclick="viewProduct(<?php echo $product['id']; ?>)" title="Lihat Detail">
                                                                <i class="fe-eye"></i>
                                                            </button>
                                                            <button type="button" class="btn btn-sm btn-warning"
                                                                    onclick="editProduct(<?php echo $product['id']; ?>)" title="Edit">
                                                                <i class="fe-edit"></i>
                                                            </button>
                                                            <button type="button" class="btn btn-sm btn-danger"
                                                                    onclick="deleteProduct(<?php echo $product['id']; ?>, '<?php echo htmlspecialchars($product['nama_barang']); ?>')" title="Hapus">
                                                                <i class="fe-trash"></i>
                                                            </button>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="10" class="text-center">
                                                    <?php if (!empty($search) || $category_filter > 0 || !empty($status_filter)): ?>
                                                        Tidak ada barang yang ditemukan dengan filter yang dipilih
                                                    <?php else: ?>
                                                        Belum ada barang yang ditambahkan
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Pagination -->
                            <?php if ($pagination['total_pages'] > 1): ?>
                                <nav aria-label="Products pagination">
                                    <ul class="pagination justify-content-center">
                                        <li class="page-item <?php echo !$pagination['has_previous'] ? 'disabled' : ''; ?>">
                                            <a class="page-link" href="?page=<?php echo $pagination['previous_page']; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo $category_filter; ?>&status=<?php echo urlencode($status_filter); ?>">Previous</a>
                                        </li>

                                        <?php for ($i = 1; $i <= $pagination['total_pages']; $i++): ?>
                                            <li class="page-item <?php echo $i == $pagination['current_page'] ? 'active' : ''; ?>">
                                                <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo $category_filter; ?>&status=<?php echo urlencode($status_filter); ?>"><?php echo $i; ?></a>
                                            </li>
                                        <?php endfor; ?>

                                        <li class="page-item <?php echo !$pagination['has_next'] ? 'disabled' : ''; ?>">
                                            <a class="page-link" href="?page=<?php echo $pagination['next_page']; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo $category_filter; ?>&status=<?php echo urlencode($status_filter); ?>">Next</a>
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

<!-- Add Product Modal -->
<div class="modal fade" id="addProductModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Tambah Barang Baru</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form id="addProductForm" method="POST" action="save.php" enctype="multipart/form-data">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="kode_barang">Kode Barang *</label>
                                <input type="text" class="form-control" id="kode_barang" name="kode_barang"
                                       required maxlength="20" placeholder="Contoh: BRG001">
                                <small class="text-muted">Kode unik untuk identifikasi barang</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="barcode">Barcode <small class="text-muted">(Opsional)</small></label>
                                <div class="input-group">
                                    <input type="text" class="form-control" id="barcode" name="barcode"
                                           maxlength="50" placeholder="Scan atau ketik barcode">
                                    <div class="input-group-append">
                                        <button type="button" class="btn btn-outline-primary" onclick="generateEAN13()" title="Auto Generate EAN-13">
                                            <i class="mdi mdi-barcode"></i>
                                        </button>
                                    </div>
                                </div>
                                <small class="text-muted">Format: EAN-13, UPC, atau kode custom</small>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label for="category_id">Kategori</label>
                                <select class="form-control" id="category_id" name="category_id">
                                    <option value="">Pilih Kategori</option>
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?php echo $category['id']; ?>">
                                            <?php echo htmlspecialchars($category['nama_kategori']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="nama_barang">Nama Barang *</label>
                        <input type="text" class="form-control" id="nama_barang" name="nama_barang"
                               required maxlength="200" placeholder="Masukkan nama barang">
                    </div>

                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="harga_beli">Harga Beli *</label>
                                <input type="number" class="form-control" id="harga_beli" name="harga_beli"
                                       required min="0" step="0.01" placeholder="0">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="harga_jual">Harga Jual *</label>
                                <input type="number" class="form-control" id="harga_jual" name="harga_jual"
                                       required min="0" step="0.01" placeholder="0">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="stok">Stok *</label>
                                <input type="number" class="form-control" id="stok" name="stok"
                                       required min="0" placeholder="0">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="satuan">Satuan *</label>
                                <input type="text" class="form-control" id="satuan" name="satuan"
                                       required maxlength="20" placeholder="Contoh: pcs, kg, liter">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="status">Status *</label>
                                <select class="form-control" id="status" name="status" required>
                                    <option value="aktif">Aktif</option>
                                    <option value="nonaktif">Non-aktif</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="gambar">Gambar Barang</label>
                        <input type="file" class="form-control-file" id="gambar" name="gambar"
                               accept="image/*">
                        <small class="text-muted">Format: JPG, PNG, GIF. Maksimal 5MB</small>
                    </div>

                    <div class="form-group">
                        <label for="deskripsi">Deskripsi</label>
                        <textarea class="form-control" id="deskripsi" name="deskripsi" rows="3"
                                  placeholder="Deskripsi barang (opsional)"></textarea>
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

<!-- Edit Product Modal -->
<div class="modal fade" id="editProductModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Barang</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form id="editProductForm" method="POST" action="save.php" enctype="multipart/form-data">
                <input type="hidden" id="edit_id" name="id">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="edit_kode_barang">Kode Barang *</label>
                                <input type="text" class="form-control" id="edit_kode_barang" name="kode_barang"
                                       required maxlength="20">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="edit_barcode">Barcode</label>
                                <input type="text" class="form-control" id="edit_barcode" name="barcode"
                                       maxlength="50">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label for="edit_category_id">Kategori</label>
                                <select class="form-control" id="edit_category_id" name="category_id">
                                    <option value="">Pilih Kategori</option>
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?php echo $category['id']; ?>">
                                            <?php echo htmlspecialchars($category['nama_kategori']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="edit_nama_barang">Nama Barang *</label>
                        <input type="text" class="form-control" id="edit_nama_barang" name="nama_barang"
                               required maxlength="200">
                    </div>

                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="edit_harga_beli">Harga Beli *</label>
                                <input type="number" class="form-control" id="edit_harga_beli" name="harga_beli"
                                       required min="0" step="0.01">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="edit_harga_jual">Harga Jual *</label>
                                <input type="number" class="form-control" id="edit_harga_jual" name="harga_jual"
                                       required min="0" step="0.01">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="edit_stok">Stok *</label>
                                <input type="number" class="form-control" id="edit_stok" name="stok"
                                       required min="0">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="edit_satuan">Satuan *</label>
                                <input type="text" class="form-control" id="edit_satuan" name="satuan"
                                       required maxlength="20">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="edit_status">Status *</label>
                                <select class="form-control" id="edit_status" name="status" required>
                                    <option value="aktif">Aktif</option>
                                    <option value="nonaktif">Non-aktif</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="edit_gambar">Gambar Barang</label>
                        <div id="currentImagePreview" class="mb-2" style="display: none;"></div>
                        <input type="file" class="form-control-file" id="edit_gambar" name="gambar"
                               accept="image/*">
                        <small class="text-muted">Kosongkan jika tidak ingin mengubah gambar</small>
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

<!-- Product Detail Modal -->
<div class="modal fade" id="productDetailModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Detail Barang</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body" id="productDetailContent">
                <div class="text-center py-3">
                    <div class="spinner-border" role="status">
                        <span class="sr-only">Loading...</span>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>

<script>
// Auto generate product code
function generateProductCode() {
    const prefix = 'BRG';
    const timestamp = Date.now().toString().substr(-6);
    return prefix + timestamp;
}

// Generate EAN-13 barcode
function generateEAN13() {
    // Get next product ID from backend (simplified - using timestamp for now)
    const productId = Date.now().toString().substr(-4);

    // Indonesia country code (621) + manufacturer code + product code
    let barcode = '621' + productId.padStart(4, '0') + '00000';

    // Calculate check digit
    const checkDigit = calculateEAN13CheckDigit(barcode);
    barcode += checkDigit;

    $('#barcode').val(barcode);
}

// Calculate EAN-13 check digit
function calculateEAN13CheckDigit(barcode) {
    let sum = 0;
    for (let i = 0; i < 12; i++) {
        const digit = parseInt(barcode[i]);
        if (i % 2 === 0) {
            sum += digit;
        } else {
            sum += digit * 3;
        }
    }
    return (10 - (sum % 10)) % 10;
}

// Validate barcode format
function validateBarcode(barcode) {
    if (!barcode) return true; // Optional field

    // Remove spaces and check length
    barcode = barcode.replace(/\s/g, '');

    // Check if it's numeric and has valid length
    if (!/^\d+$/.test(barcode)) {
        return { valid: false, message: 'Barcode harus berupa angka' };
    }

    // Common barcode lengths
    const validLengths = [8, 12, 13, 14]; // EAN-8, UPC-A, EAN-13, ITF-14
    if (!validLengths.includes(barcode.length)) {
        return { valid: false, message: 'Panjang barcode tidak valid (8, 12, 13, atau 14 digit)' };
    }

    // Validate EAN-13 check digit if length is 13
    if (barcode.length === 13) {
        const checkDigit = calculateEAN13CheckDigit(barcode.substr(0, 12));
        if (parseInt(barcode[12]) !== checkDigit) {
            return { valid: false, message: 'Check digit barcode EAN-13 tidak valid' };
        }
    }

    return { valid: true };
}

// Generate code on modal show
$('#addProductModal').on('show.bs.modal', function() {
    if (!$('#kode_barang').val()) {
        $('#kode_barang').val(generateProductCode());
    }
});

// View product function
function viewProduct(id) {
    $.ajax({
        url: 'get.php',
        method: 'GET',
        data: { id: id },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                showProductDetail(response.data);
            } else {
                alert('Error: ' + response.message);
            }
        },
        error: function() {
            alert('Terjadi kesalahan sistem!');
        }
    });
}

// Edit product function
function editProduct(id) {
    $.ajax({
        url: 'get.php',
        method: 'GET',
        data: { id: id },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                populateEditForm(response.data);
                $('#editProductModal').modal('show');
            } else {
                alert('Error: ' + response.message);
            }
        },
        error: function() {
            alert('Terjadi kesalahan sistem!');
        }
    });
}

// Show product detail in modal
function showProductDetail(product) {
    let detailHtml = `
        <div class="row">
            <div class="col-md-4 text-center">
                ${product.gambar ?
                    `<img src="<?= UPLOADS_URL ?>products/${product.gambar}" alt="${product.nama_barang}" class="img-fluid img-thumbnail" style="max-height: 200px;">` :
                    `<div class="bg-light d-flex align-items-center justify-content-center" style="height: 200px;"><i class="fe-image text-muted" style="font-size: 48px;"></i></div>`
                }
            </div>
            <div class="col-md-8">
                <table class="table table-borderless">
                    <tr><td><strong>Kode Barang:</strong></td><td>${product.kode_barang}</td></tr>
                    <tr><td><strong>Nama Barang:</strong></td><td>${product.nama_barang}</td></tr>
                    <tr><td><strong>Kategori:</strong></td><td>${product.nama_kategori || 'Tanpa Kategori'}</td></tr>
                    <tr><td><strong>Barcode:</strong></td><td>${product.barcode || '-'}</td></tr>
                    <tr><td><strong>Harga Beli:</strong></td><td><?= 'Rp ' ?>${parseInt(product.harga_beli).toLocaleString('id-ID')}</td></tr>
                    <tr><td><strong>Harga Jual:</strong></td><td><?= 'Rp ' ?>${parseInt(product.harga_jual).toLocaleString('id-ID')}</td></tr>
                    <tr><td><strong>Stok:</strong></td><td><span class="badge badge-${product.stok <= 10 ? 'danger' : 'success'}">${product.stok} ${product.satuan}</span></td></tr>
                    <tr><td><strong>Status:</strong></td><td><span class="badge badge-${product.status === 'aktif' ? 'success' : 'secondary'}">${product.status}</span></td></tr>
                </table>
                ${product.deskripsi ? `<p><strong>Deskripsi:</strong><br>${product.deskripsi}</p>` : ''}
            </div>
        </div>
    `;

    if (product.stock_movements && product.stock_movements.length > 0) {
        detailHtml += `
            <hr>
            <h6>Riwayat Pergerakan Stok (10 terakhir):</h6>
            <div class="table-responsive">
                <table class="table table-sm">
                    <thead><tr><th>Tanggal</th><th>Tipe</th><th>Jumlah</th><th>Keterangan</th></tr></thead>
                    <tbody>
        `;
        product.stock_movements.forEach(function(movement) {
            detailHtml += `
                <tr>
                    <td>${new Date(movement.created_at).toLocaleDateString('id-ID')}</td>
                    <td><span class="badge badge-${movement.tipe === 'masuk' ? 'success' : 'warning'}">${movement.tipe}</span></td>
                    <td>${movement.jumlah}</td>
                    <td>${movement.keterangan}</td>
                </tr>
            `;
        });
        detailHtml += '</tbody></table></div>';
    }

    $('#productDetailContent').html(detailHtml);
    $('#productDetailModal').modal('show');
}

// Populate edit form
function populateEditForm(product) {
    $('#edit_id').val(product.id);
    $('#edit_kode_barang').val(product.kode_barang);
    $('#edit_barcode').val(product.barcode || '');
    $('#edit_nama_barang').val(product.nama_barang);
    $('#edit_category_id').val(product.category_id || '');
    $('#edit_harga_beli').val(product.harga_beli);
    $('#edit_harga_jual').val(product.harga_jual);
    $('#edit_stok').val(product.stok);
    $('#edit_satuan').val(product.satuan);
    $('#edit_status').val(product.status);
    $('#edit_deskripsi').val(product.deskripsi || '');

    // Show current image if exists
    if (product.gambar) {
        $('#currentImagePreview').html(
            `<img src="<?= UPLOADS_URL ?>products/${product.gambar}" alt="Current Image" class="img-thumbnail" style="max-width: 100px; max-height: 100px;">`
        ).show();
    } else {
        $('#currentImagePreview').hide();
    }
}

// Delete product function
function deleteProduct(id, name) {
    if (confirm('Apakah Anda yakin ingin menghapus barang "' + name + '"?')) {
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
$('#addProductForm, #editProductForm').on('submit', function(e) {
    e.preventDefault();

    const kodeBarang = $(this).find('input[name="kode_barang"]').val().trim();
    const namaBarang = $(this).find('input[name="nama_barang"]').val().trim();
    const barcode = $(this).find('input[name="barcode"]').val().trim();
    const hargaBeli = parseFloat($(this).find('input[name="harga_beli"]').val());
    const hargaJual = parseFloat($(this).find('input[name="harga_jual"]').val());
    const stok = parseInt($(this).find('input[name="stok"]').val());
    const satuan = $(this).find('input[name="satuan"]').val().trim();

    if (!kodeBarang || !namaBarang || !satuan) {
        alert('Kode barang, nama barang, dan satuan harus diisi!');
        return false;
    }

    // Validate barcode if provided
    if (barcode) {
        const barcodeValidation = validateBarcode(barcode);
        if (!barcodeValidation.valid) {
            alert('Barcode tidak valid: ' + barcodeValidation.message);
            return false;
        }
    }

    if (isNaN(hargaBeli) || hargaBeli < 0) {
        alert('Harga beli harus berupa angka dan tidak boleh negatif!');
        return false;
    }

    if (isNaN(hargaJual) || hargaJual < 0) {
        alert('Harga jual harus berupa angka dan tidak boleh negatif!');
        return false;
    }

    if (hargaJual <= hargaBeli) {
        if (!confirm('Harga jual lebih rendah atau sama dengan harga beli. Lanjutkan?')) {
            return false;
        }
    }

    if (isNaN(stok) || stok < 0) {
        alert('Stok harus berupa angka dan tidak boleh negatif!');
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

// Export products function
function exportProducts() {
    const search = '<?= urlencode($search) ?>';
    const category = '<?= $category_filter ?>';
    const status = '<?= urlencode($status_filter) ?>';

    let url = 'export.php?';
    if (search) url += 'search=' + search + '&';
    if (category) url += 'category=' + category + '&';
    if (status) url += 'status=' + status + '&';

    window.open(url, '_blank');
}

</script>

</body>
</html>