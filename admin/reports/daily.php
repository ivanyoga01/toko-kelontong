<?php
/**
 * Daily Sales Report
 * Laporan penjualan harian untuk admin
 */

require_once '../../includes/admin_auth.php';

// Page config
$page_title = 'Laporan Penjualan Harian';
$page_description = 'Laporan detail penjualan per hari';

// Get selected date (default today)
$selected_date = sanitizeInput($_GET['date'] ?? date('Y-m-d'));

// Validate date
if (!strtotime($selected_date)) {
    $selected_date = date('Y-m-d');
}

// Get daily transactions
$transactions_sql = "SELECT t.*,
                     CASE
                         WHEN t.customer_id IS NOT NULL THEN c.nama_pelanggan
                         WHEN t.customer_name IS NOT NULL THEN CONCAT('Guest: ', t.customer_name)
                         ELSE 'Pelanggan Umum'
                     END as nama_pelanggan,
                     u.nama_lengkap as kasir_nama,
                     (SELECT COUNT(*) FROM transaction_details WHERE transaction_id = t.id) as jumlah_item
                     FROM transactions t
                     LEFT JOIN customers c ON t.customer_id = c.id
                     LEFT JOIN users u ON t.user_id = u.id
                     WHERE DATE(t.tanggal_transaksi) = ?
                     ORDER BY t.tanggal_transaksi DESC";

$transactions = fetchAll($transactions_sql, [$selected_date]);

// Get daily summary
$summary_sql = "SELECT
                COUNT(*) as total_transaksi,
                COUNT(CASE WHEN status = 'selesai' THEN 1 END) as transaksi_selesai,
                COUNT(CASE WHEN status = 'batal' THEN 1 END) as transaksi_batal,
                COALESCE(SUM(CASE WHEN status = 'selesai' THEN total ELSE 0 END), 0) as total_omzet,
                COALESCE(AVG(CASE WHEN status = 'selesai' THEN total END), 0) as rata_rata_transaksi
                FROM transactions
                WHERE DATE(tanggal_transaksi) = ?";

$summary = fetchOne($summary_sql, [$selected_date]);

// Get top selling products for this day
$top_products_sql = "SELECT p.nama_barang, p.satuan, SUM(td.jumlah) as total_terjual,
                     SUM(td.subtotal) as total_pendapatan
                     FROM transaction_details td
                     JOIN transactions t ON td.transaction_id = t.id
                     JOIN products p ON td.product_id = p.id
                     WHERE DATE(t.tanggal_transaksi) = ? AND t.status = 'selesai'
                     GROUP BY p.id
                     ORDER BY total_terjual DESC
                     LIMIT 10";

$top_products = fetchAll($top_products_sql, [$selected_date]);

// Get sales by hour
$hourly_sales_sql = "SELECT
                     HOUR(tanggal_transaksi) as jam,
                     COUNT(*) as jumlah_transaksi,
                     COALESCE(SUM(total), 0) as total_penjualan
                     FROM transactions
                     WHERE DATE(tanggal_transaksi) = ? AND status = 'selesai'
                     GROUP BY HOUR(tanggal_transaksi)
                     ORDER BY jam";

$hourly_sales = fetchAll($hourly_sales_sql, [$selected_date]);

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

            <!-- Date Filter -->
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <form method="GET" class="row g-3 align-items-end">
                                <div class="col-md-3">
                                    <label class="form-label">Pilih Tanggal</label>
                                    <input type="date" name="date" class="form-control" value="<?= $selected_date ?>" max="<?= date('Y-m-d') ?>">
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
                            <i class="mdi mdi-cart stats-icon"></i>
                            <h3 class="stats-number"><?= $summary['total_transaksi'] ?></h3>
                            <h5 class="text-white">Total Transaksi</h5>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3">
                    <div class="card stats-card">
                        <div class="card-body text-center">
                            <i class="mdi mdi-check-circle stats-icon"></i>
                            <h3 class="stats-number"><?= $summary['transaksi_selesai'] ?></h3>
                            <h5 class="text-white">Transaksi Selesai</h5>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3">
                    <div class="card stats-card">
                        <div class="card-body text-center">
                            <i class="mdi mdi-currency-usd stats-icon"></i>
                            <h3 class="stats-number"><?= formatCurrency($summary['total_omzet']) ?></h3>
                            <h5 class="text-white">Total Omzet</h5>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3">
                    <div class="card stats-card">
                        <div class="card-body text-center">
                            <i class="mdi mdi-calculator stats-icon"></i>
                            <h3 class="stats-number"><?= formatCurrency($summary['rata_rata_transaksi']) ?></h3>
                            <h5 class="text-white">Rata-rata Transaksi</h5>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <!-- Hourly Sales Chart -->
                <div class="col-xl-8">
                    <div class="card">
                        <div class="card-header">
                            <h4 class="header-title">Penjualan per Jam</h4>
                        </div>
                        <div class="card-body">
                            <canvas id="hourlySalesChart" width="400" height="200"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Top Products -->
                <div class="col-xl-4">
                    <div class="card">
                        <div class="card-header">
                            <h4 class="header-title">Produk Terlaris</h4>
                        </div>
                        <div class="card-body" style="max-height: 400px; overflow-y: auto;">
                            <?php if (empty($top_products)): ?>
                                <div class="text-center py-3">
                                    <i class="mdi mdi-information text-muted" style="font-size: 2rem;"></i>
                                    <p class="text-muted mt-2">Tidak ada data produk</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($top_products as $index => $product): ?>
                                <div class="d-flex align-items-center mb-3">
                                    <div class="flex-shrink-0">
                                        <span class="badge bg-primary"><?= $index + 1 ?></span>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <h6 class="mb-1"><?= htmlspecialchars($product['nama_barang']) ?></h6>
                                        <p class="mb-0 text-muted">
                                            <?= $product['total_terjual'] ?> <?= htmlspecialchars($product['satuan']) ?> •
                                            <?= formatCurrency($product['total_pendapatan']) ?>
                                        </p>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Transactions Table -->
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h4 class="header-title">Detail Transaksi - <?= formatDate($selected_date) ?></h4>
                        </div>
                        <div class="card-body">
                            <?php if (empty($transactions)): ?>
                                <div class="text-center py-4">
                                    <i class="mdi mdi-calendar-blank text-muted" style="font-size: 3rem;"></i>
                                    <h4 class="mt-3">Tidak ada transaksi</h4>
                                    <p class="text-muted">Belum ada transaksi pada tanggal <?= formatDate($selected_date) ?></p>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-centered table-nowrap table-hover mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Waktu</th>
                                                <th>Kode Transaksi</th>
                                                <th>Pelanggan</th>
                                                <th>Kasir</th>
                                                <th>Item</th>
                                                <th>Total</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($transactions as $transaction): ?>
                                            <tr>
                                                <td><?= date('H:i', strtotime($transaction['tanggal_transaksi'])) ?></td>
                                                <td><?= htmlspecialchars($transaction['kode_transaksi']) ?></td>
                                                <td><?= htmlspecialchars($transaction['nama_pelanggan']) ?></td>
                                                <td><?= htmlspecialchars($transaction['kasir_nama']) ?></td>
                                                <td>
                                                    <span class="badge bg-info"><?= $transaction['jumlah_item'] ?> item</span>
                                                </td>
                                                <td class="currency"><?= formatCurrency($transaction['total']) ?></td>
                                                <td>
                                                    <span class="badge <?= $transaction['status'] === 'selesai' ? 'bg-success' : 'bg-danger' ?>">
                                                        <?= ucfirst($transaction['status']) ?>
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
            </div>

        </div> <!-- container -->

    </div> <!-- content -->

    <?php include '../../includes/footer.php'; ?>

</div>

<!-- bundle -->
<script src="<?= ASSETS_URL ?>Adminto_v4.0.0/Vertical/dist/assets/js/vendor.min.js"></script>
<script src="<?= ASSETS_URL ?>Adminto_v4.0.0/Vertical/dist/assets/js/app.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
$(document).ready(function() {
    // Hourly Sales Chart
    const hourlyData = <?= json_encode($hourly_sales) ?>;

    // Prepare chart data
    const hours = [];
    const sales = [];
    const transactions = [];

    // Fill all hours (0-23)
    for (let i = 0; i < 24; i++) {
        hours.push(i.toString().padStart(2, '0') + ':00');

        // Find data for this hour
        const hourData = hourlyData.find(h => h.jam == i);
        sales.push(hourData ? parseFloat(hourData.total_penjualan) : 0);
        transactions.push(hourData ? parseInt(hourData.jumlah_transaksi) : 0);
    }

    const ctx = document.getElementById('hourlySalesChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: hours,
            datasets: [{
                label: 'Penjualan (Rp)',
                data: sales,
                borderColor: 'rgb(75, 192, 192)',
                backgroundColor: 'rgba(75, 192, 192, 0.2)',
                tension: 0.1,
                yAxisID: 'y'
            }, {
                label: 'Transaksi',
                data: transactions,
                borderColor: 'rgb(255, 99, 132)',
                backgroundColor: 'rgba(255, 99, 132, 0.2)',
                tension: 0.1,
                yAxisID: 'y1'
            }]
        },
        options: {
            responsive: true,
            interaction: {
                mode: 'index',
                intersect: false,
            },
            scales: {
                x: {
                    display: true,
                    title: {
                        display: true,
                        text: 'Jam'
                    }
                },
                y: {
                    type: 'linear',
                    display: true,
                    position: 'left',
                    title: {
                        display: true,
                        text: 'Penjualan (Rp)'
                    }
                },
                y1: {
                    type: 'linear',
                    display: true,
                    position: 'right',
                    title: {
                        display: true,
                        text: 'Jumlah Transaksi'
                    },
                    grid: {
                        drawOnChartArea: false,
                    },
                }
            }
        }
    });

    // Export function
    window.exportReport = function() {
        window.open('export_daily.php?date=<?= $selected_date ?>', '_blank');
    };
});
</script>

</body>
</html>