<?php
/**
 * Advanced Product Search - Kasir
 * Enhanced product search with comprehensive filters for better POS experience
 */

require_once '../includes/kasir_auth.php';

$page_title = 'Pencarian Produk Lanjutan - ' . APP_NAME;

// Get categories for filter
$categories = fetchAll("SELECT * FROM categories ORDER BY nama_kategori ASC");

// Search parameters
$search_term = sanitizeInput($_GET['search'] ?? '');
$category_filter = intval($_GET['category'] ?? 0);
$stock_filter = sanitizeInput($_GET['stock'] ?? '');
$price_min = floatval($_GET['price_min'] ?? 0);
$price_max = floatval($_GET['price_max'] ?? 0);
$sort_by = sanitizeInput($_GET['sort'] ?? 'nama');
$sort_order = sanitizeInput($_GET['order'] ?? 'asc');

// Pagination
$page = max(1, intval($_GET['page'] ?? 1));
$limit = 12; // Show 12 products per page in grid

// Build search conditions
$where_conditions = ["p.status = 'aktif'"];
$params = [];

if (!empty($search_term)) {
    $where_conditions[] = "(p.nama_barang LIKE ? OR p.kode_barang LIKE ? OR p.deskripsi LIKE ?)";
    $searchTerm = "%$search_term%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

if ($category_filter > 0) {
    $where_conditions[] = "p.category_id = ?";
    $params[] = $category_filter;
}

if (!empty($stock_filter)) {
    switch ($stock_filter) {
        case 'available':
            $where_conditions[] = "p.stok > 0";
            break;
        case 'low':
            $where_conditions[] = "p.stok <= " . LOW_STOCK_THRESHOLD . " AND p.stok > 0";
            break;
        case 'out':
            $where_conditions[] = "p.stok = 0";
            break;
    }
}

if ($price_min > 0) {
    $where_conditions[] = "p.harga_jual >= ?";
    $params[] = $price_min;
}

if ($price_max > 0) {
    $where_conditions[] = "p.harga_jual <= ?";
    $params[] = $price_max;
}

$where_clause = "WHERE " . implode(" AND ", $where_conditions);

// Valid sort options
$valid_sorts = ['nama', 'harga', 'stok', 'kategori'];
$sort_by = in_array($sort_by, $valid_sorts) ? $sort_by : 'nama';
$sort_order = $sort_order === 'desc' ? 'DESC' : 'ASC';

$order_clause = match($sort_by) {
    'nama' => "ORDER BY p.nama_barang $sort_order",
    'harga' => "ORDER BY p.harga_jual $sort_order",
    'stok' => "ORDER BY p.stok $sort_order",
    'kategori' => "ORDER BY c.nama_kategori $sort_order, p.nama_barang ASC",
    default => "ORDER BY p.nama_barang ASC"
};

// Count total records
$count_sql = "SELECT COUNT(*) as total FROM products p LEFT JOIN categories c ON p.category_id = c.id $where_clause";
$total_records = fetchOne($count_sql, $params)['total'];
$total_pages = ceil($total_records / $limit);
$offset = ($page - 1) * $limit;

// Get products
$sql = "SELECT p.*, c.nama_kategori
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id
        $where_clause
        $order_clause
        LIMIT $limit OFFSET $offset";

$products = fetchAll($sql, $params);

include '../includes/header.php';
include '../includes/sidebar.php';
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
                                <li class="breadcrumb-item"><a href="penjualan.php">POS</a></li>
                                <li class="breadcrumb-item active">Pencarian Lanjutan</li>
                            </ol>
                        </div>
                        <h4 class="page-title">Pencarian Produk Lanjutan</h4>
                    </div>
                </div>
            </div>

            <!-- Search Filters -->
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="mdi mdi-filter-variant"></i> Filter Pencarian
                            </h5>
                        </div>
                        <div class="card-body">
                            <form method="GET" id="searchForm">
                                <div class="row">
                                    <div class="col-lg-3 col-md-6 mb-3">
                                        <label for="search">Kata Kunci</label>
                                        <input type="text" class="form-control" id="search" name="search"
                                               placeholder="Nama/kode/deskripsi produk"
                                               value="<?= htmlspecialchars($search_term) ?>">
                                    </div>
                                    <div class="col-lg-2 col-md-6 mb-3">
                                        <label for="category">Kategori</label>
                                        <select class="form-control" id="category" name="category">
                                            <option value="">Semua Kategori</option>
                                            <?php foreach ($categories as $category): ?>
                                                <option value="<?= $category['id'] ?>"
                                                        <?= $category_filter == $category['id'] ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($category['nama_kategori']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-lg-2 col-md-6 mb-3">
                                        <label for="stock">Status Stok</label>
                                        <select class="form-control" id="stock" name="stock">
                                            <option value="">Semua Status</option>
                                            <option value="available" <?= $stock_filter === 'available' ? 'selected' : '' ?>>Tersedia</option>
                                            <option value="low" <?= $stock_filter === 'low' ? 'selected' : '' ?>>Stok Menipis</option>
                                            <option value="out" <?= $stock_filter === 'out' ? 'selected' : '' ?>>Habis</option>
                                        </select>
                                    </div>
                                    <div class="col-lg-2 col-md-6 mb-3">
                                        <label for="price_min">Harga Min</label>
                                        <input type="number" class="form-control" id="price_min" name="price_min"
                                               placeholder="0" min="0" step="1000"
                                               value="<?= $price_min > 0 ? $price_min : '' ?>">
                                    </div>
                                    <div class="col-lg-2 col-md-6 mb-3">
                                        <label for="price_max">Harga Max</label>
                                        <input type="number" class="form-control" id="price_max" name="price_max"
                                               placeholder="0" min="0" step="1000"
                                               value="<?= $price_max > 0 ? $price_max : '' ?>">
                                    </div>
                                    <div class="col-lg-1 col-md-6 mb-3">
                                        <label>&nbsp;</label>
                                        <button type="submit" class="btn btn-primary btn-block">
                                            <i class="fe-search"></i>
                                        </button>
                                    </div>
                                </div>

                                <!-- Sort and Display Options -->
                                <div class="row">
                                    <div class="col-lg-2 col-md-6 mb-3">
                                        <label for="sort">Urutkan</label>
                                        <select class="form-control" id="sort" name="sort" onchange="this.form.submit()">
                                            <option value="nama" <?= $sort_by === 'nama' ? 'selected' : '' ?>>Nama</option>
                                            <option value="harga" <?= $sort_by === 'harga' ? 'selected' : '' ?>>Harga</option>
                                            <option value="stok" <?= $sort_by === 'stok' ? 'selected' : '' ?>>Stok</option>
                                            <option value="kategori" <?= $sort_by === 'kategori' ? 'selected' : '' ?>>Kategori</option>
                                        </select>
                                    </div>
                                    <div class="col-lg-2 col-md-6 mb-3">
                                        <label for="order">Urutan</label>
                                        <select class="form-control" id="order" name="order" onchange="this.form.submit()">
                                            <option value="asc" <?= $sort_order === 'ASC' ? 'selected' : '' ?>>A-Z / Terendah</option>
                                            <option value="desc" <?= $sort_order === 'DESC' ? 'selected' : '' ?>>Z-A / Tertinggi</option>
                                        </select>
                                    </div>
                                    <div class="col-lg-4 col-md-6 mb-3">
                                        <label>&nbsp;</label>
                                        <div class="btn-group btn-block">
                                            <a href="advanced_search.php" class="btn btn-outline-secondary">
                                                <i class="fe-refresh-cw"></i> Reset Filter
                                            </a>
                                            <a href="penjualan.php" class="btn btn-success">
                                                <i class="fe-shopping-cart"></i> Kembali ke POS
                                            </a>
                                        </div>
                                    </div>
                                    <div class="col-lg-4 col-md-6 mb-3">
                                        <label>&nbsp;</label>
                                        <div class="text-right">
                                            <small class="text-muted">
                                                Menampilkan <?= count($products) ?> dari <?= $total_records ?> produk
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Results -->
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="mdi mdi-cube-outline"></i> Hasil Pencarian
                                <?php if (!empty($search_term)): ?>
                                    untuk "<strong><?= htmlspecialchars($search_term) ?></strong>"
                                <?php endif; ?>
                            </h5>
                        </div>
                        <div class="card-body">
                            <?php if ($products): ?>
                                <div class="row">
                                    <?php foreach ($products as $product): ?>
                                        <div class="col-xl-3 col-lg-4 col-md-6 mb-4">
                                            <div class="card h-100 product-card" data-product-id="<?= $product['id'] ?>">
                                                <div class="card-body">
                                                    <!-- Product Image -->
                                                    <div class="text-center mb-3">
                                                        <?php if (!empty($product['gambar']) && file_exists(UPLOADS_PATH . 'products/' . $product['gambar'])): ?>
                                                            <img src="<?= UPLOADS_URL ?>products/<?= $product['gambar'] ?>"
                                                                 class="img-thumbnail"
                                                                 style="height: 80px; width: 80px; object-fit: cover;">
                                                        <?php else: ?>
                                                            <div class="bg-light d-flex align-items-center justify-content-center"
                                                                 style="height: 80px; width: 80px; margin: 0 auto;">
                                                                <i class="mdi mdi-image text-muted" style="font-size: 30px;"></i>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>

                                                    <!-- Product Info -->
                                                    <h6 class="card-title text-truncate" title="<?= htmlspecialchars($product['nama_barang']) ?>">
                                                        <?= htmlspecialchars($product['nama_barang']) ?>
                                                    </h6>

                                                    <div class="text-muted small mb-2">
                                                        <div>Kode: <?= htmlspecialchars($product['kode_barang']) ?></div>
                                                        <div>Kategori: <?= htmlspecialchars($product['nama_kategori'] ?? '-') ?></div>
                                                    </div>

                                                    <!-- Price and Stock -->
                                                    <div class="mb-3">
                                                        <div class="h5 text-primary mb-1">
                                                            <?= formatCurrency($product['harga_jual']) ?>
                                                        </div>
                                                        <div class="small">
                                                            Stok:
                                                            <span class="badge badge-<?= $product['stok'] > LOW_STOCK_THRESHOLD ? 'success' : ($product['stok'] > 0 ? 'warning' : 'danger') ?>">
                                                                <?= $product['stok'] ?> <?= htmlspecialchars($product['satuan']) ?>
                                                            </span>
                                                        </div>
                                                    </div>

                                                    <!-- Action Buttons -->
                                                    <div class="d-grid gap-2">
                                                        <?php if ($product['stok'] > 0): ?>
                                                            <button type="button" class="btn btn-success btn-sm"
                                                                    onclick="addToCart(<?= $product['id'] ?>)">
                                                                <i class="fe-shopping-cart"></i> Tambah ke Keranjang
                                                            </button>
                                                            <button type="button" class="btn btn-outline-primary btn-sm"
                                                                    onclick="quickAdd(<?= $product['id'] ?>)">
                                                                <i class="fe-plus"></i> Tambah Cepat
                                                            </button>
                                                        <?php else: ?>
                                                            <button type="button" class="btn btn-secondary btn-sm" disabled>
                                                                <i class="fe-x"></i> Stok Habis
                                                            </button>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>

                                <!-- Pagination -->
                                <?php if ($total_pages > 1): ?>
                                    <nav aria-label="Product search pagination" class="mt-4">
                                        <ul class="pagination justify-content-center">
                                            <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                                                <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>">Previous</a>
                                            </li>

                                            <?php
                                            $start_page = max(1, $page - 2);
                                            $end_page = min($total_pages, $page + 2);
                                            ?>

                                            <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                                                <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                                                    <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>"><?= $i ?></a>
                                                </li>
                                            <?php endfor; ?>

                                            <li class="page-item <?= $page >= $total_pages ? 'disabled' : '' ?>">
                                                <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>">Next</a>
                                            </li>
                                        </ul>
                                    </nav>
                                <?php endif; ?>

                            <?php else: ?>
                                <div class="text-center py-5">
                                    <i class="mdi mdi-magnify text-muted" style="font-size: 64px;"></i>
                                    <h5 class="text-muted mt-3">Tidak ada produk ditemukan</h5>
                                    <p class="text-muted">Coba ubah filter pencarian atau kata kunci</p>
                                    <a href="advanced_search.php" class="btn btn-outline-primary">
                                        <i class="fe-refresh-cw"></i> Reset Pencarian
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

        </div> <!-- container-fluid -->
    </div> <!-- content -->
</div> <!-- content-page -->

<!-- Quick Add Modal -->
<div class="modal fade" id="quickAddModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Tambah Cepat ke Keranjang</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div id="quickAddProductInfo"></div>
                <div class="form-group">
                    <label for="quickAddQty">Jumlah</label>
                    <input type="number" class="form-control" id="quickAddQty" min="1" value="1">
                    <small class="text-muted">Maksimal sesuai stok tersedia</small>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-success" id="confirmQuickAdd">
                    <i class="fe-shopping-cart"></i> Tambah ke Keranjang
                </button>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

<style>
.product-card {
    transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
    cursor: pointer;
}

.product-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

.product-card .card-body {
    padding: 1rem;
}

.product-card .card-title {
    font-size: 0.9rem;
    font-weight: 600;
    line-height: 1.3;
    min-height: 2.6rem;
}

.d-grid {
    display: grid;
    gap: 0.5rem;
}
</style>

<script>
let selectedProductId = null;
let selectedProductData = null;

// Add to cart with default quantity
function addToCart(productId) {
    // In a real implementation, this would integrate with the main POS cart system
    // For now, we'll show a success message and optionally redirect to POS
    if (confirm('Tambah produk ke keranjang dengan jumlah 1?\n\nKlik OK untuk langsung ke halaman POS.')) {
        // Redirect to POS with product pre-selected
        window.location.href = 'penjualan.php?add_product=' + productId;
    }
}

// Quick add with custom quantity
function quickAdd(productId) {
    selectedProductId = productId;

    // Get product data
    $.ajax({
        url: '../ajax/get_product.php',
        method: 'GET',
        data: { id: productId },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                selectedProductData = response.data;
                showQuickAddModal(response.data);
            } else {
                alert('Error: ' + response.message);
            }
        },
        error: function() {
            alert('Gagal memuat data produk');
        }
    });
}

function showQuickAddModal(product) {
    const productInfo = `
        <div class="media mb-3">
            <div class="media-body">
                <h6 class="media-heading">${product.nama_barang}</h6>
                <p class="text-muted mb-1">Kode: ${product.kode_barang}</p>
                <p class="text-muted mb-1">Harga: ${formatCurrency(product.harga_jual)}</p>
                <p class="text-muted mb-0">Stok tersedia: ${product.stok} ${product.satuan}</p>
            </div>
        </div>
    `;

    $('#quickAddProductInfo').html(productInfo);
    $('#quickAddQty').attr('max', product.stok).val(1);
    $('#quickAddModal').modal('show');
}

$('#confirmQuickAdd').click(function() {
    const qty = parseInt($('#quickAddQty').val());

    if (!qty || qty < 1) {
        alert('Jumlah tidak valid');
        return;
    }

    if (qty > selectedProductData.stok) {
        alert('Jumlah melebihi stok tersedia');
        return;
    }

    // In a real implementation, this would add to cart session/local storage
    if (confirm(`Tambah ${qty} ${selectedProductData.satuan} ${selectedProductData.nama_barang} ke keranjang?\n\nKlik OK untuk langsung ke halaman POS.`)) {
        // Redirect to POS with product and quantity
        window.location.href = `penjualan.php?add_product=${selectedProductId}&qty=${qty}`;
    }
});

// Format currency
function formatCurrency(amount) {
    return 'Rp ' + new Intl.NumberFormat('id-ID').format(amount);
}

// Auto-submit form on filter change
$('#category, #stock').change(function() {
    $('#searchForm').submit();
});

// Clear price filters
function clearPriceFilter() {
    $('#price_min, #price_max').val('');
    $('#searchForm').submit();
}

// Keyboard shortcuts
$(document).keydown(function(e) {
    // Ctrl + F to focus search
    if (e.ctrlKey && e.keyCode === 70) {
        e.preventDefault();
        $('#search').focus();
    }

    // ESC to clear search
    if (e.keyCode === 27) {
        if ($('#search').val()) {
            $('#search').val('').focus();
        }
    }
});

// Focus search on page load
$(document).ready(function() {
    $('#search').focus();
});
</script>

</body>
</html>