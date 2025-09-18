<?php
/**
 * Best Seller Products Report
 * Laporan produk terlaris untuk admin
 */

require_once '../../includes/admin_auth.php';

// Page config
$page_title = 'Laporan Produk Terlaris';
$page_description = 'Analisis produk dengan penjualan terbaik';

// Get date range (default last 30 days)
$date_from = sanitizeInput($_GET['date_from'] ?? date('Y-m-d', strtotime('-30 days')));
$date_to = sanitizeInput($_GET['date_to'] ?? date('Y-m-d'));

// Validate dates
if (!strtotime($date_from)) $date_from = date('Y-m-d', strtotime('-30 days'));
if (!strtotime($date_to)) $date_to = date('Y-m-d');

// Get top selling products
$top_products_sql = "SELECT p.id, p.kode_barang, p.nama_barang, p.satuan, c.nama_kategori,
                     p.harga_jual, p.stok,
                     SUM(td.jumlah) as total_terjual,
                     SUM(td.subtotal) as total_pendapatan,
                     COUNT(DISTINCT t.id) as frekuensi_transaksi,
                     AVG(td.jumlah) as rata_rata_per_transaksi,
                     MIN(t.tanggal_transaksi) as pertama_terjual,
                     MAX(t.tanggal_transaksi) as terakhir_terjual
                     FROM transaction_details td
                     JOIN transactions t ON td.transaction_id = t.id
                     JOIN products p ON td.product_id = p.id
                     LEFT JOIN categories c ON p.category_id = c.id
                     WHERE DATE(t.tanggal_transaksi) BETWEEN ? AND ?
                       AND t.status = 'selesai'
                     GROUP BY p.id
                     ORDER BY total_terjual DESC
                     LIMIT 50";

$top_products = fetchAll($top_products_sql, [$date_from, $date_to]);

// Get category performance
$category_performance_sql = "SELECT c.nama_kategori,
                             COUNT(DISTINCT p.id) as jumlah_produk,
                             SUM(td.jumlah) as total_terjual,
                             SUM(td.subtotal) as total_pendapatan,
                             COUNT(DISTINCT t.id) as jumlah_transaksi
                             FROM transaction_details td
                             JOIN transactions t ON td.transaction_id = t.id
                             JOIN products p ON td.product_id = p.id
                             LEFT JOIN categories c ON p.category_id = c.id
                             WHERE DATE(t.tanggal_transaksi) BETWEEN ? AND ?
                               AND t.status = 'selesai'
                             GROUP BY c.id
                             ORDER BY total_pendapatan DESC";

$category_performance = fetchAll($category_performance_sql, [$date_from, $date_to]);

// Get least selling products (warning for slow moving stock)
$slow_moving_sql = "SELECT p.id, p.kode_barang, p.nama_barang, p.satuan, p.stok,
                    COALESCE(SUM(td.jumlah), 0) as total_terjual,
                    COALESCE(SUM(td.subtotal), 0) as total_pendapatan
                    FROM products p
                    LEFT JOIN transaction_details td ON p.id = td.product_id
                    LEFT JOIN transactions t ON td.transaction_id = t.id
                      AND DATE(t.tanggal_transaksi) BETWEEN ? AND ?
                      AND t.status = 'selesai'
                    WHERE p.status = 'aktif'
                    GROUP BY p.id
                    HAVING total_terjual <= 5 OR total_terjual IS NULL
                    ORDER BY total_terjual ASC, p.stok DESC
                    LIMIT 20";

$slow_moving = fetchAll($slow_moving_sql, [$date_from, $date_to]);

// Get overall statistics
$stats_sql = "SELECT
              COUNT(DISTINCT td.product_id) as produk_terjual,
              SUM(td.jumlah) as total_item_terjual,
              SUM(td.subtotal) as total_pendapatan,
              COUNT(DISTINCT t.id) as total_transaksi
              FROM transaction_details td
              JOIN transactions t ON td.transaction_id = t.id
              WHERE DATE(t.tanggal_transaksi) BETWEEN ? AND ?
                AND t.status = 'selesai'";

$stats = fetchOne($stats_sql, [$date_from, $date_to]);

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
                                <li class="breadcrumb-item">Laporan</li>
                                <li class="breadcrumb-item active"><?= $page_title ?></li>
                            </ol>
                        </div>
                        <h4 class="page-title"><?= $page_title ?></h4>
                    </div>
                </div>
            </div>

            <!-- Date Range Filter -->
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <form method="GET" class="row g-3 align-items-end">
                                <div class="col-md-3">
                                    <label class="form-label">Dari Tanggal</label>
                                    <input type="date" name="date_from" class="form-control" value="<?= $date_from ?>" max="<?= date('Y-m-d') ?>">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Sampai Tanggal</label>
                                    <input type="date" name="date_to" class="form-control" value="<?= $date_to ?>" max="<?= date('Y-m-d') ?>">
                                </div>
                                <div class="col-md-3">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="mdi mdi-magnify"></i> Tampilkan
                                    </button>
                                    <button type="button" class="btn btn-success" onclick="exportReport()">
                                        <i class="mdi mdi-file-excel"></i> Export
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Summary Cards -->
            <div class="row">
                <div class="col-xl-3">
                    <div class="card stats-card">
                        <div class="card-body text-center">
                            <i class="mdi mdi-package-variant stats-icon"></i>
                            <h3 class="stats-number"><?= $stats['produk_terjual'] ?></h3>
                            <h5 class="text-white">Produk Terjual</h5>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3">
                    <div class="card stats-card">
                        <div class="card-body text-center">
                            <i class="mdi mdi-cube-outline stats-icon"></i>
                            <h3 class="stats-number"><?= number_format($stats['total_item_terjual']) ?></h3>
                            <h5 class="text-white">Total Item Terjual</h5>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3">
                    <div class="card stats-card">
                        <div class="card-body text-center">
                            <i class="mdi mdi-currency-usd stats-icon"></i>
                            <h3 class="stats-number"><?= formatCurrency($stats['total_pendapatan']) ?></h3>
                            <h5 class="text-white">Total Pendapatan</h5>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3">
                    <div class="card stats-card">
                        <div class="card-body text-center">
                            <i class="mdi mdi-cart stats-icon"></i>
                            <h3 class="stats-number"><?= $stats['total_transaksi'] ?></h3>
                            <h5 class="text-white">Total Transaksi</h5>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <!-- Top Products -->
                <div class="col-xl-8">
                    <div class="card">
                        <div class="card-header">
                            <h4 class="header-title">Produk Terlaris (<?= formatDate($date_from) ?> - <?= formatDate($date_to) ?>)</h4>
                        </div>
                        <div class="card-body">
                            <?php if (empty($top_products)): ?>
                                <div class="text-center py-4">
                                    <i class="mdi mdi-information text-muted" style="font-size: 3rem;"></i>
                                    <h4 class="mt-3">Tidak ada data</h4>
                                    <p class="text-muted">Tidak ada produk terjual pada periode ini</p>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-centered table-nowrap table-hover mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Rank</th>
                                                <th>Produk</th>
                                                <th>Kategori</th>
                                                <th>Terjual</th>
                                                <th>Pendapatan</th>
                                                <th>Frekuensi</th>
                                                <th>Avg/Transaksi</th>
                                                <th>Stok</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($top_products as $index => $product): ?>
                                            <tr>
                                                <td>
                                                    <span class="badge <?= $index < 3 ? 'bg-warning' : 'bg-primary' ?>">
                                                        #<?= $index + 1 ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <div>
                                                        <strong><?= htmlspecialchars($product['nama_barang']) ?></strong>
                                                        <br><small class="text-muted"><?= htmlspecialchars($product['kode_barang']) ?></small>
                                                    </div>
                                                </td>
                                                <td><?= htmlspecialchars($product['nama_kategori'] ?? 'Tanpa Kategori') ?></td>
                                                <td>
                                                    <strong><?= number_format($product['total_terjual']) ?></strong>
                                                    <small class="text-muted"><?= htmlspecialchars($product['satuan']) ?></small>
                                                </td>
                                                <td class="currency"><?= formatCurrency($product['total_pendapatan']) ?></td>
                                                <td><?= $product['frekuensi_transaksi'] ?>x</td>
                                                <td><?= number_format($product['rata_rata_per_transaksi'], 1) ?></td>
                                                <td>
                                                    <span class="badge <?= $product['stok'] <= 10 ? 'bg-danger' : 'bg-success' ?>">
                                                        <?= $product['stok'] ?>
                                                    </span>
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

                <!-- Category Performance -->
                <div class="col-xl-4">
                    <div class="card">
                        <div class="card-header">
                            <h4 class="header-title">Performa Kategori</h4>
                        </div>
                        <div class="card-body" style="max-height: 500px; overflow-y: auto;">
                            <?php if (empty($category_performance)): ?>
                                <div class="text-center py-3">
                                    <i class="mdi mdi-information text-muted" style="font-size: 2rem;"></i>
                                    <p class="text-muted mt-2">Tidak ada data kategori</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($category_performance as $index => $category): ?>
                                <div class="d-flex align-items-center mb-3">
                                    <div class="flex-shrink-0">
                                        <span class="badge bg-info"><?= $index + 1 ?></span>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <h6 class="mb-1"><?= htmlspecialchars($category['nama_kategori'] ?? 'Tanpa Kategori') ?></h6>
                                        <p class="mb-0 text-muted">
                                            <?= $category['jumlah_produk'] ?> produk •
                                            <?= number_format($category['total_terjual']) ?> item<br>
                                            <strong><?= formatCurrency($category['total_pendapatan']) ?></strong>
                                        </p>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Slow Moving Products -->
            <?php if (!empty($slow_moving)): ?>
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h4 class="header-title text-warning">
                                <i class="mdi mdi-alert-circle me-2"></i>
                                Produk Perlu Perhatian (Penjualan Rendah)
                            </h4>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-centered table-nowrap table-hover mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Kode</th>
                                            <th>Nama Produk</th>
                                            <th>Stok</th>
                                            <th>Terjual</th>
                                            <th>Pendapatan</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($slow_moving as $product): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($product['kode_barang']) ?></td>
                                            <td><?= htmlspecialchars($product['nama_barang']) ?></td>
                                            <td>
                                                <span class="badge <?= $product['stok'] > 50 ? 'bg-warning' : ($product['stok'] > 10 ? 'bg-info' : 'bg-success') ?>">
                                                    <?= $product['stok'] ?> <?= htmlspecialchars($product['satuan']) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?= $product['total_terjual'] ?: 0 ?> <?= htmlspecialchars($product['satuan']) ?>
                                            </td>
                                            <td class="currency"><?= formatCurrency($product['total_pendapatan']) ?></td>
                                            <td>
                                                <?php if ($product['total_terjual'] == 0): ?>
                                                    <span class="badge bg-danger">Tidak Terjual</span>
                                                <?php elseif ($product['total_terjual'] <= 2): ?>
                                                    <span class="badge bg-warning">Sangat Lambat</span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">Lambat</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

        </div> <!-- container -->

    </div> <!-- content -->

    <?php include '../../includes/footer.php'; ?>

</div>

<!-- bundle -->
<script src="<?= ASSETS_URL ?>Adminto_v4.0.0/Vertical/dist/assets/js/vendor.min.js"></script>
<script src="<?= ASSETS_URL ?>Adminto_v4.0.0/Vertical/dist/assets/js/app.min.js"></script>

<script>
$(document).ready(function() {
    // Export function
    window.exportReport = function() {
        window.open('export_bestseller.php?date_from=<?= $date_from ?>&date_to=<?= $date_to ?>', '_blank');
    };
});
</script>

</body>
</html>