<?php
/**
 * Admin Dashboard
 * Halaman utama untuk administrator
 */

require_once '../includes/admin_auth.php';

$page_title = 'Dashboard Admin - ' . APP_NAME;

// Get dashboard statistics
$today = date('Y-m-d');
$this_month = date('Y-m');

// Today's sales
$sales_today_sql = "SELECT COUNT(*) as total_transactions, COALESCE(SUM(total), 0) as total_sales
                    FROM transactions
                    WHERE DATE(tanggal_transaksi) = ? AND status = 'selesai'";
$sales_today = fetchOne($sales_today_sql, [$today]);

// This month's sales
$sales_month_sql = "SELECT COUNT(*) as total_transactions, COALESCE(SUM(total), 0) as total_sales
                    FROM transactions
                    WHERE DATE_FORMAT(tanggal_transaksi, '%Y-%m') = ? AND status = 'selesai'";
$sales_month = fetchOne($sales_month_sql, [$this_month]);

// Low stock products
$low_stock_sql = "SELECT COUNT(*) as count FROM products WHERE stok <= ? AND status = 'aktif'";
$low_stock = fetchOne($low_stock_sql, [LOW_STOCK_THRESHOLD]);

// Total customers
$total_customers_sql = "SELECT COUNT(*) as count FROM customers";
$total_customers = fetchOne($total_customers_sql);

// Recent transactions
$recent_transactions_sql = "SELECT t.kode_transaksi, t.total, t.tanggal_transaksi,
                           CASE
                               WHEN t.customer_id IS NOT NULL THEN c.nama_pelanggan
                               WHEN t.customer_name IS NOT NULL THEN CONCAT('Guest: ', t.customer_name)
                               ELSE 'Pelanggan Umum'
                           END as nama_pelanggan,
                           u.nama_lengkap as kasir
                           FROM transactions t
                           LEFT JOIN customers c ON t.customer_id = c.id
                           LEFT JOIN users u ON t.user_id = u.id
                           WHERE t.status = 'selesai'
                           ORDER BY t.tanggal_transaksi DESC
                           LIMIT 5";
$recent_transactions = fetchAll($recent_transactions_sql);

// Top selling products this month
$top_products_sql = "SELECT p.nama_barang, p.satuan, SUM(td.jumlah) as total_sold
                     FROM transaction_details td
                     JOIN transactions t ON td.transaction_id = t.id
                     JOIN products p ON td.product_id = p.id
                     WHERE DATE_FORMAT(t.tanggal_transaksi, '%Y-%m') = ? AND t.status = 'selesai'
                     GROUP BY p.id
                     ORDER BY total_sold DESC
                     LIMIT 5";
$top_products = fetchAll($top_products_sql, [$this_month]);

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<style>
.page-title-box {
    position: relative;
}

.page-title-action {
    flex-shrink: 0;
}

@media (max-width: 768px) {
    .page-title-box {
        flex-direction: column !important;
        align-items: flex-start !important;
    }

    .page-title-action {
        margin-top: 15px;
        width: 100%;
    }

    .page-title-action .btn {
        width: 100%;
    }

    /* POS Card Mobile Styling */
    .card.bg-success .d-flex {
        flex-direction: column !important;
        text-align: center;
    }

    .card.bg-success .btn {
        margin-top: 15px;
        width: 100%;
    }
}

@media (max-width: 576px) {
    .page-title-action .btn {
        font-size: 14px;
        padding: 8px 16px;
    }
}
</style>

<!-- Start Content -->
<div class="content-page">
    <div class="content">
        <div class="container-fluid">

<!-- Page Title -->
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-flex justify-content-between align-items-center">
            <div>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="javascript: void(0);">Toko Kelontong</a></li>
                        <li class="breadcrumb-item active">Dashboard</li>
                    </ol>
                </div>
                <h4 class="page-title mb-0">Dashboard Administrator</h4>
            </div>
            <!-- <div class="page-title-action">
                <a href="<?= BASE_URL ?>kasir/penjualan.php" class="btn btn-success btn-lg" target="_blank">
                    <i class="mdi mdi-cash-register"></i> Buka POS (Point of Sale)
                </a>
            </div> -->
        </div>
    </div>
</div>

<!-- Statistics Cards -->
<div class="row">
    <!-- Quick POS Access Card -->
    <div class="col-xl-12 mb-3">
        <div class="card bg-success text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="text-white mb-1">Akses Cepat Point of Sale (POS)</h5>
                        <p class="text-white-50 mb-0">Buka sistem kasir untuk melakukan transaksi penjualan</p>
                    </div>
                    <div>
                        <a href="<?= BASE_URL ?>kasir/penjualan.php" class="btn btn-light btn-lg" target="_blank">
                            <i class="mdi mdi-cash-register"></i> Buka POS
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-xl-3 col-md-6">
        <div class="card widget-flat">
            <div class="card-body">
                <div class="float-right">
                    <i class="fe-shopping-cart widget-icon bg-success-lighten text-success"></i>
                </div>
                <h5 class="text-muted font-weight-normal mt-0" title="Penjualan Hari Ini">Penjualan Hari Ini</h5>
                <h3 class="mt-3 mb-3"><?php echo formatCurrency($sales_today['total_sales']); ?></h3>
                <p class="mb-0 text-muted">
                    <span class="text-nowrap"><?php echo $sales_today['total_transactions']; ?> transaksi</span>
                </p>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6">
        <div class="card widget-flat">
            <div class="card-body">
                <div class="float-right">
                    <i class="fe-bar-chart-line- widget-icon bg-warning-lighten text-warning"></i>
                </div>
                <h5 class="text-muted font-weight-normal mt-0" title="Penjualan Bulan Ini">Penjualan Bulan Ini</h5>
                <h3 class="mt-3 mb-3"><?php echo formatCurrency($sales_month['total_sales']); ?></h3>
                <p class="mb-0 text-muted">
                    <span class="text-nowrap"><?php echo $sales_month['total_transactions']; ?> transaksi</span>
                </p>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6">
        <div class="card widget-flat">
            <div class="card-body">
                <div class="float-right">
                    <i class="fe-alert-triangle widget-icon bg-danger-lighten text-danger"></i>
                </div>
                <h5 class="text-muted font-weight-normal mt-0" title="Stok Menipis">Stok Menipis</h5>
                <h3 class="mt-3 mb-3"><?php echo $low_stock['count']; ?></h3>
                <p class="mb-0 text-muted">
                    <span class="text-nowrap">Produk (≤ <?= LOW_STOCK_THRESHOLD ?> stok)</span>
                </p>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6">
        <div class="card widget-flat">
            <div class="card-body">
                <div class="float-right">
                    <i class="fe-users widget-icon bg-info-lighten text-info"></i>
                </div>
                <h5 class="text-muted font-weight-normal mt-0" title="Total Pelanggan">Total Pelanggan</h5>
                <h3 class="mt-3 mb-3"><?php echo $total_customers['count']; ?></h3>
                <p class="mb-0 text-muted">
                    <span class="text-nowrap">Pelanggan terdaftar</span>
                </p>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Recent Transactions -->
    <div class="col-xl-8">
        <div class="card">
            <div class="card-body">
                <div class="dropdown float-right">
                    <a href="#" class="dropdown-toggle arrow-none card-drop" data-toggle="dropdown" aria-expanded="false">
                        <i class="mdi mdi-dots-vertical"></i>
                    </a>
                    <div class="dropdown-menu dropdown-menu-right">
                        <a href="transactions/" class="dropdown-item">Lihat Semua</a>
                    </div>
                </div>

                <h4 class="header-title mb-3">Transaksi Terbaru</h4>

                <div class="table-responsive">
                    <table class="table table-borderless table-hover table-nowrap table-centered m-0">
                        <thead class="table-light">
                            <tr>
                                <th>Kode Transaksi</th>
                                <th>Pelanggan</th>
                                <th>Kasir</th>
                                <th>Total</th>
                                <th>Tanggal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($recent_transactions): ?>
                                <?php foreach ($recent_transactions as $transaction): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($transaction['kode_transaksi']); ?></td>
                                    <td><?php echo htmlspecialchars($transaction['nama_pelanggan']); ?></td>
                                    <td><?php echo htmlspecialchars($transaction['kasir']); ?></td>
                                    <td class="currency"><?php echo formatCurrency($transaction['total']); ?></td>
                                    <td><?php echo formatDateTime($transaction['tanggal_transaksi']); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center">Belum ada transaksi</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Top Products -->
    <div class="col-xl-4">
        <div class="card">
            <div class="card-body">
                <div class="dropdown float-right">
                    <a href="#" class="dropdown-toggle arrow-none card-drop" data-toggle="dropdown" aria-expanded="false">
                        <i class="mdi mdi-dots-vertical"></i>
                    </a>
                    <div class="dropdown-menu dropdown-menu-right">
                        <a href="reports/bestseller.php" class="dropdown-item">Lihat Semua</a>
                    </div>
                </div>

                <h4 class="header-title">Top 5 Produk Bulan Ini</h4>

                <div class="mt-3">
                    <?php if ($top_products): ?>
                        <?php foreach ($top_products as $index => $product): ?>
                        <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-2">
                            <div class="media">
                                <div class="avatar-sm bg-light rounded mr-2">
                                    <span class="avatar-title h5 m-0 text-dark"><?php echo $index + 1; ?></span>
                                </div>
                                <div class="media-body">
                                    <h6 class="mt-0 mb-1 font-14"><?php echo htmlspecialchars($product['nama_barang']); ?></h6>
                                </div>
                            </div>
                            <div class="text-right">
                                <span class="badge badge-success"><?php echo $product['total_sold']; ?> <?php echo htmlspecialchars($product['satuan']); ?></span>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="text-muted text-center">Belum ada data penjualan bulan ini</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Low Stock Alert Section -->
<?php if (SHOW_LOW_STOCK_ALERTS): ?>
<?php $low_stock_products = getLowStockProducts(); ?>
<?php $critical_stock_count = getCriticalStockCount(); ?>
<?php if (!empty($low_stock_products)): ?>
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <h4 class="header-title text-danger">
                        <i class="mdi mdi-alert"></i> Peringatan Stok Menipis
                    </h4>
                    <div>
                        <?php if ($critical_stock_count > 0): ?>
                        <span class="badge badge-danger"><?= $critical_stock_count ?> Kritis</span>
                        <?php endif; ?>
                        <span class="badge badge-warning"><?= count($low_stock_products) ?> Total</span>
                    </div>
                </div>

                <div class="table-responsive mt-3">
                    <table class="table table-sm table-striped">
                        <thead>
                            <tr>
                                <th>Kode</th>
                                <th>Nama Barang</th>
                                <th>Kategori</th>
                                <th>Stok Saat Ini</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach (array_slice($low_stock_products, 0, 10) as $product): ?>
                            <tr>
                                <td><?= htmlspecialchars($product['kode_barang']) ?></td>
                                <td><?= htmlspecialchars($product['nama_barang']) ?></td>
                                <td><?= htmlspecialchars($product['nama_kategori'] ?? 'Tanpa Kategori') ?></td>
                                <td>
                                    <span class="badge <?= isCriticalStock($product['stok']) ? 'badge-danger' : 'badge-warning' ?>">
                                        <?= $product['stok'] ?> <?= htmlspecialchars($product['satuan']) ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if (isCriticalStock($product['stok'])): ?>
                                        <span class="badge badge-danger">Kritis</span>
                                    <?php else: ?>
                                        <span class="badge badge-warning">Rendah</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="stock/index.php?product=<?= $product['id'] ?>" class="btn btn-sm btn-outline-primary" title="Kelola Stok">
                                        <i class="mdi mdi-cube-outline"></i>
                                    </a>
                                    <a href="products/index.php?search=<?= urlencode($product['kode_barang']) ?>" class="btn btn-sm btn-outline-secondary" title="Edit Produk">
                                        <i class="mdi mdi-pencil"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <?php if (count($low_stock_products) > 10): ?>
                <div class="text-center mt-3">
                    <a href="stock/index.php" class="btn btn-outline-primary">
                        Lihat Semua Produk Stok Rendah (<?= count($low_stock_products) ?>)
                    </a>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>
<?php endif; ?>

        </div> <!-- container-fluid -->
    </div> <!-- content -->
</div> <!-- content-page -->

<?php
$page_scripts = '
<script>
    // Auto refresh every 5 minutes
    setTimeout(function() {
        location.reload();
    }, 300000);
</script>
';

include '../includes/footer.php';
?>