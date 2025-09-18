<?php
/**
 * Admin Stock Movements - Index
 * Halaman riwayat pergerakan stok untuk admin
 */

require_once '../../includes/admin_auth.php';

$page_title = 'Riwayat Pergerakan Stok';
$page_description = 'Monitor dan analisis pergerakan stok barang';

// Pagination and filters
$page = max(1, intval($_GET['page'] ?? 1));
$search = sanitizeInput($_GET['search'] ?? '');
$product_filter = intval($_GET['product'] ?? 0);
$type_filter = sanitizeInput($_GET['type'] ?? '');
$date_from = sanitizeInput($_GET['date_from'] ?? '');
$date_to = sanitizeInput($_GET['date_to'] ?? '');

// Build query conditions
$where_conditions = [];
$params = [];

if (!empty($search)) {
    $where_conditions[] = "(p.nama_barang LIKE ? OR p.kode_barang LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($product_filter > 0) {
    $where_conditions[] = "sm.product_id = ?";
    $params[] = $product_filter;
}

if (!empty($type_filter)) {
    $where_conditions[] = "sm.tipe = ?";
    $params[] = $type_filter;
}

if (!empty($date_from)) {
    $where_conditions[] = "DATE(sm.created_at) >= ?";
    $params[] = $date_from;
}

if (!empty($date_to)) {
    $where_conditions[] = "DATE(sm.created_at) <= ?";
    $params[] = $date_to;
}

$where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

// Count total records
$count_sql = "SELECT COUNT(*) as total
              FROM stock_movements sm
              LEFT JOIN products p ON sm.product_id = p.id
              $where_clause";
$total_records = fetchOne($count_sql, $params)['total'];
$pagination = paginate($total_records, $page, 20);

// Get stock movements data
$sql = "SELECT sm.*,
        p.nama_barang, p.kode_barang, p.satuan,
        t.kode_transaksi,
        u.nama_lengkap as user_name
        FROM stock_movements sm
        LEFT JOIN products p ON sm.product_id = p.id
        LEFT JOIN transactions t ON sm.transaction_id = t.id
        LEFT JOIN users u ON t.user_id = u.id
        $where_clause
        ORDER BY sm.created_at DESC
        LIMIT {$pagination['records_per_page']} OFFSET {$pagination['offset']}";

$movements = fetchAll($sql, $params);

// Get products for filter
$products = fetchAll("SELECT id, nama_barang, kode_barang FROM products ORDER BY nama_barang ASC");

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
                                <li class="breadcrumb-item active">Pergerakan Stok</li>
                            </ol>
                        </div>
                        <h4 class="page-title">Riwayat Pergerakan Stok</h4>
                    </div>
                </div>
            </div>

            <!-- Flash Messages -->
            <?php $flash = getFlashMessage(); if ($flash): ?>
            <div class="row">
                <div class="col-12">
                    <div class="alert alert-<?= $flash['type'] === 'error' ? 'danger' : $flash['type'] ?> alert-dismissible fade show" role="alert">
                        <strong><?= $flash['type'] === 'error' ? 'Error!' : 'Success!' ?></strong> <?= htmlspecialchars($flash['message']) ?>
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Stock Movements List -->
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <div class="row align-items-center">
                                <div class="col">
                                    <h4 class="header-title">Riwayat Pergerakan Stok</h4>
                                </div>
                                <div class="col-auto">
                                    <div class="btn-group" role="group">
                                        <button type="button" class="btn btn-success" onclick="exportStockMovements()">
                                            <i class="mdi mdi-file-excel"></i> Export Excel
                                        </button>
                                        <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#addStockModal">
                                            <i class="mdi mdi-plus"></i> Penyesuaian Stok
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <!-- Search and Filter Form -->
                            <form method="GET" class="mb-3">
                                <div class="row">
                                    <div class="col-lg-2">
                                        <input type="text" name="search" class="form-control"
                                               placeholder="Cari barang..."
                                               value="<?= htmlspecialchars($search) ?>">
                                    </div>
                                    <div class="col-lg-2">
                                        <select name="product" class="form-control">
                                            <option value="">Semua Barang</option>
                                            <?php foreach ($products as $product): ?>
                                                <option value="<?= $product['id'] ?>"
                                                        <?= $product_filter == $product['id'] ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($product['kode_barang'] . ' - ' . $product['nama_barang']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-lg-2">
                                        <select name="type" class="form-control">
                                            <option value="">Semua Tipe</option>
                                            <option value="masuk" <?= $type_filter === 'masuk' ? 'selected' : '' ?>>Masuk</option>
                                            <option value="keluar" <?= $type_filter === 'keluar' ? 'selected' : '' ?>>Keluar</option>
                                            <option value="penyesuaian" <?= $type_filter === 'penyesuaian' ? 'selected' : '' ?>>Penyesuaian</option>
                                        </select>
                                    </div>
                                    <div class="col-lg-2">
                                        <input type="date" name="date_from" class="form-control"
                                               placeholder="Dari Tanggal"
                                               value="<?= htmlspecialchars($date_from) ?>">
                                    </div>
                                    <div class="col-lg-2">
                                        <input type="date" name="date_to" class="form-control"
                                               placeholder="Sampai Tanggal"
                                               value="<?= htmlspecialchars($date_to) ?>">
                                    </div>
                                    <div class="col-lg-2">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fe-search"></i> Cari
                                        </button>
                                        <a href="index.php" class="btn btn-outline-secondary">Reset</a>
                                    </div>
                                </div>
                            </form>

                            <!-- Stock Movements Table -->
                            <div class="table-responsive">
                                <table class="table table-striped table-bordered table-hover">
                                    <thead class="thead-dark">
                                        <tr>
                                            <th width="5%">No</th>
                                            <th width="12%">Tanggal</th>
                                            <th width="10%">Kode Barang</th>
                                            <th width="15%">Nama Barang</th>
                                            <th width="8%">Tipe</th>
                                            <th width="8%">Jumlah</th>
                                            <th width="10%">Transaksi</th>
                                            <th width="15%">Keterangan</th>
                                            <th width="12%">User/Kasir</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if ($movements): ?>
                                            <?php foreach ($movements as $index => $movement): ?>
                                                <tr>
                                                    <td><?= $pagination['offset'] + $index + 1 ?></td>
                                                    <td><?= formatDateTime($movement['created_at']) ?></td>
                                                    <td><?= htmlspecialchars($movement['kode_barang']) ?></td>
                                                    <td><?= htmlspecialchars($movement['nama_barang']) ?></td>
                                                    <td>
                                                        <?php
                                                        $badge_class = [
                                                            'masuk' => 'badge-success',
                                                            'keluar' => 'badge-danger',
                                                            'penyesuaian' => 'badge-warning'
                                                        ];
                                                        $class = $badge_class[$movement['tipe']] ?? 'badge-secondary';
                                                        ?>
                                                        <span class="badge <?= $class ?>">
                                                            <?= ucfirst($movement['tipe']) ?>
                                                        </span>
                                                    </td>
                                                    <td class="text-center">
                                                        <span class="<?= $movement['tipe'] === 'keluar' ? 'text-danger' : 'text-success' ?>">
                                                            <?= $movement['tipe'] === 'keluar' ? '-' : '+' ?><?= abs($movement['jumlah']) ?> <?= htmlspecialchars($movement['satuan']) ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <?php if ($movement['kode_transaksi']): ?>
                                                            <a href="../transactions/detail.php?id=<?= $movement['transaction_id'] ?>"
                                                               class="text-primary" title="Lihat Detail Transaksi">
                                                                <?= htmlspecialchars($movement['kode_transaksi']) ?>
                                                            </a>
                                                        <?php else: ?>
                                                            <span class="text-muted">-</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td><?= htmlspecialchars($movement['keterangan']) ?></td>
                                                    <td><?= htmlspecialchars($movement['user_name'] ?? 'System') ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="9" class="text-center">
                                                    <?php if (!empty($search) || $product_filter > 0 || !empty($type_filter) || !empty($date_from) || !empty($date_to)): ?>
                                                        Tidak ada pergerakan stok yang ditemukan dengan filter yang dipilih
                                                    <?php else: ?>
                                                        Belum ada pergerakan stok yang tercatat
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Pagination -->
                            <?php if ($pagination['total_pages'] > 1): ?>
                                <nav aria-label="Stock movements pagination">
                                    <ul class="pagination justify-content-center">
                                        <li class="page-item <?= !$pagination['has_previous'] ? 'disabled' : '' ?>">
                                            <a class="page-link" href="?page=<?= $pagination['previous_page'] ?>&search=<?= urlencode($search) ?>&product=<?= $product_filter ?>&type=<?= urlencode($type_filter) ?>&date_from=<?= urlencode($date_from) ?>&date_to=<?= urlencode($date_to) ?>">Previous</a>
                                        </li>

                                        <?php for ($i = 1; $i <= $pagination['total_pages']; $i++): ?>
                                            <li class="page-item <?= $i == $pagination['current_page'] ? 'active' : '' ?>">
                                                <a class="page-link" href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&product=<?= $product_filter ?>&type=<?= urlencode($type_filter) ?>&date_from=<?= urlencode($date_from) ?>&date_to=<?= urlencode($date_to) ?>"><?= $i ?></a>
                                            </li>
                                        <?php endfor; ?>

                                        <li class="page-item <?= !$pagination['has_next'] ? 'disabled' : '' ?>">
                                            <a class="page-link" href="?page=<?= $pagination['next_page'] ?>&search=<?= urlencode($search) ?>&product=<?= $product_filter ?>&type=<?= urlencode($type_filter) ?>&date_from=<?= urlencode($date_from) ?>&date_to=<?= urlencode($date_to) ?>">Next</a>
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

<!-- Add Stock Adjustment Modal -->
<div class="modal fade" id="addStockModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Penyesuaian Stok</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form id="addStockForm" method="POST" action="adjust_stock.php">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="product_id">Pilih Barang *</label>
                        <select class="form-control" id="product_id" name="product_id" required>
                            <option value="">Pilih Barang</option>
                            <?php foreach ($products as $product): ?>
                                <option value="<?= $product['id'] ?>">
                                    <?= htmlspecialchars($product['kode_barang'] . ' - ' . $product['nama_barang']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div id="currentStockInfo" class="alert alert-info" style="display: none;">
                        <strong>Stok Saat Ini: </strong><span id="currentStock">0</span> <span id="currentUnit"></span>
                    </div>

                    <div class="form-group">
                        <label for="adjustment_type">Tipe Penyesuaian *</label>
                        <select class="form-control" id="adjustment_type" name="adjustment_type" required>
                            <option value="">Pilih Tipe</option>
                            <option value="masuk">Tambah Stok (+)</option>
                            <option value="keluar">Kurangi Stok (-)</option>
                            <option value="set">Set Stok Baru</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="jumlah">Jumlah *</label>
                        <input type="number" class="form-control" id="jumlah" name="jumlah"
                               required min="1" placeholder="Masukkan jumlah">
                        <small class="text-muted" id="adjustmentHelper"></small>
                    </div>

                    <div class="form-group">
                        <label for="keterangan">Keterangan *</label>
                        <textarea class="form-control" id="keterangan" name="keterangan" rows="3"
                                  required placeholder="Alasan penyesuaian stok"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan Penyesuaian</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>

<script>
// Get product stock info when product is selected
$('#product_id').change(function() {
    const productId = $(this).val();
    if (productId) {
        $.ajax({
            url: '../products/get_stock.php',
            method: 'GET',
            data: { id: productId },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    $('#currentStock').text(response.data.stok);
                    $('#currentUnit').text(response.data.satuan);
                    $('#currentStockInfo').show();
                } else {
                    $('#currentStockInfo').hide();
                }
            },
            error: function() {
                $('#currentStockInfo').hide();
            }
        });
    } else {
        $('#currentStockInfo').hide();
    }
});

// Update helper text based on adjustment type
$('#adjustment_type').change(function() {
    const type = $(this).val();
    const currentStock = parseInt($('#currentStock').text());
    const unit = $('#currentUnit').text();

    let helper = '';
    switch(type) {
        case 'masuk':
            helper = 'Akan menambah stok yang ada';
            break;
        case 'keluar':
            helper = `Akan mengurangi dari stok saat ini (${currentStock} ${unit})`;
            break;
        case 'set':
            helper = 'Akan mengatur stok menjadi jumlah yang dimasukkan';
            break;
    }
    $('#adjustmentHelper').text(helper);
});

// Form validation
$('#addStockForm').on('submit', function(e) {
    const adjustmentType = $('#adjustment_type').val();
    const jumlah = parseInt($('#jumlah').val());
    const currentStock = parseInt($('#currentStock').text());

    if (adjustmentType === 'keluar' && jumlah > currentStock) {
        e.preventDefault();
        alert('Jumlah yang akan dikurangi tidak boleh lebih dari stok saat ini!');
        return false;
    }

    if (jumlah <= 0) {
        e.preventDefault();
        alert('Jumlah harus lebih dari 0!');
        return false;
    }
});

// Export stock movements function
function exportStockMovements() {
    const search = '<?= urlencode($search) ?>';
    const product = '<?= $product_filter ?>';
    const type = '<?= urlencode($type_filter) ?>';
    const dateFrom = '<?= urlencode($date_from) ?>';
    const dateTo = '<?= urlencode($date_to) ?>';

    let url = 'export.php?';
    if (search) url += 'search=' + search + '&';
    if (product) url += 'product=' + product + '&';
    if (type) url += 'type=' + type + '&';
    if (dateFrom) url += 'date_from=' + dateFrom + '&';
    if (dateTo) url += 'date_to=' + dateTo + '&';

    window.open(url, '_blank');
}
</script>

</body>
</html>