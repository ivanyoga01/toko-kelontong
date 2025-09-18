<?php
/**
 * Monthly Sales Report
 * Laporan penjualan bulanan untuk admin
 */

require_once '../../includes/admin_auth.php';

// Page config
$page_title = 'Laporan Penjualan Bulanan';
$page_description = 'Laporan detail penjualan per bulan';

// Get selected month (default current month)
$selected_month = sanitizeInput($_GET['month'] ?? date('Y-m'));

// Validate month
if (!preg_match('/^\d{4}-\d{2}$/', $selected_month)) {
    $selected_month = date('Y-m');
}

// Get month summary
$summary_sql = "SELECT
                COUNT(*) as total_transaksi,
                COUNT(CASE WHEN status = 'selesai' THEN 1 END) as transaksi_selesai,
                COUNT(CASE WHEN status = 'batal' THEN 1 END) as transaksi_batal,
                COALESCE(SUM(CASE WHEN status = 'selesai' THEN total ELSE 0 END), 0) as total_omzet,
                COALESCE(AVG(CASE WHEN status = 'selesai' THEN total END), 0) as rata_rata_transaksi
                FROM transactions
                WHERE DATE_FORMAT(tanggal_transaksi, '%Y-%m') = ?";

$summary = fetchOne($summary_sql, [$selected_month]);

// Get daily sales for the month
$daily_sales_sql = "SELECT
                    DATE(tanggal_transaksi) as tanggal,
                    COUNT(*) as jumlah_transaksi,
                    COALESCE(SUM(CASE WHEN status = 'selesai' THEN total ELSE 0 END), 0) as total_penjualan
                    FROM transactions
                    WHERE DATE_FORMAT(tanggal_transaksi, '%Y-%m') = ?
                    GROUP BY DATE(tanggal_transaksi)
                    ORDER BY tanggal";

$daily_sales = fetchAll($daily_sales_sql, [$selected_month]);

// Get top products for the month
$top_products_sql = "SELECT p.nama_barang, p.satuan, SUM(td.jumlah) as total_terjual,
                     SUM(td.subtotal) as total_pendapatan,
                     COUNT(DISTINCT t.id) as frekuensi_transaksi
                     FROM transaction_details td
                     JOIN transactions t ON td.transaction_id = t.id
                     JOIN products p ON td.product_id = p.id
                     WHERE DATE_FORMAT(t.tanggal_transaksi, '%Y-%m') = ? AND t.status = 'selesai'
                     GROUP BY p.id
                     ORDER BY total_terjual DESC
                     LIMIT 15";

$top_products = fetchAll($top_products_sql, [$selected_month]);

// Get customer statistics
$customer_stats_sql = "SELECT
                       COUNT(DISTINCT CASE WHEN customer_id IS NOT NULL THEN customer_id END) as pelanggan_terdaftar,
                       COUNT(CASE WHEN customer_id IS NULL AND customer_name IS NOT NULL THEN 1 END) as pelanggan_guest,
                       COUNT(CASE WHEN customer_id IS NULL AND customer_name IS NULL THEN 1 END) as pelanggan_umum
                       FROM transactions
                       WHERE DATE_FORMAT(tanggal_transaksi, '%Y-%m') = ? AND status = 'selesai'";

$customer_stats = fetchOne($customer_stats_sql, [$selected_month]);

// Get top customers
$top_customers_sql = "SELECT
                      CASE
                          WHEN t.customer_id IS NOT NULL THEN c.nama_pelanggan
                          WHEN t.customer_name IS NOT NULL THEN CONCAT('Guest: ', t.customer_name)
                          ELSE 'Pelanggan Umum'
                      END as nama_pelanggan,
                      COUNT(*) as jumlah_transaksi,
                      SUM(t.total) as total_belanja
                      FROM transactions t
                      LEFT JOIN customers c ON t.customer_id = c.id
                      WHERE DATE_FORMAT(t.tanggal_transaksi, '%Y-%m') = ? AND t.status = 'selesai'
                        AND (t.customer_id IS NOT NULL OR t.customer_name IS NOT NULL)
                      GROUP BY t.customer_id, t.customer_name
                      ORDER BY total_belanja DESC
                      LIMIT 10";

$top_customers = fetchAll($top_customers_sql, [$selected_month]);

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

            <!-- Month Filter -->
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <form method="GET" class="row g-3 align-items-end">
                                <div class="col-md-3">
                                    <label class="form-label">Pilih Bulan</label>
                                    <input type="month" name="month" class="form-control" value="<?= $selected_month ?>" max="<?= date('Y-m') ?>">
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
                <div class="col-xl-3">
                    <div class="card stats-card">
                        <div class="card-body text-center">
                            <i class="mdi mdi-account-multiple stats-icon"></i>
                            <h3 class="stats-number"><?= $customer_stats['pelanggan_terdaftar'] + $customer_stats['pelanggan_guest'] ?></h3>
                            <h5 class="text-white">Pelanggan Unik</h5>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <!-- Daily Sales Chart -->
                <div class="col-xl-8">
                    <div class="card">
                        <div class="card-header">
                            <h4 class="header-title">Penjualan Harian - <?= date('F Y', strtotime($selected_month . '-01')) ?></h4>
                        </div>
                        <div class="card-body">
                            <canvas id="dailySalesChart" width="400" height="200"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Customer Statistics -->
                <div class="col-xl-4">
                    <div class="card">
                        <div class="card-header">
                            <h4 class="header-title">Statistik Pelanggan</h4>
                        </div>
                        <div class="card-body">
                            <div class="text-center">
                                <canvas id="customerStatsChart" width="200" height="200"></canvas>
                            </div>
                            <div class="mt-3">
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Terdaftar:</span>
                                    <strong><?= $customer_stats['pelanggan_terdaftar'] ?></strong>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Guest:</span>
                                    <strong><?= $customer_stats['pelanggan_guest'] ?></strong>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span>Umum:</span>
                                    <strong><?= $customer_stats['pelanggan_umum'] ?></strong>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <!-- Top Products -->
                <div class="col-xl-6">
                    <div class="card">
                        <div class="card-header">
                            <h4 class="header-title">Produk Terlaris</h4>
                        </div>
                        <div class="card-body" style="max-height: 500px; overflow-y: auto;">
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
                                            <?= $product['frekuensi_transaksi'] ?> transaksi<br>
                                            <strong><?= formatCurrency($product['total_pendapatan']) ?></strong>
                                        </p>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Top Customers -->
                <div class="col-xl-6">
                    <div class="card">
                        <div class="card-header">
                            <h4 class="header-title">Pelanggan Terbaik</h4>
                        </div>
                        <div class="card-body" style="max-height: 500px; overflow-y: auto;">
                            <?php if (empty($top_customers)): ?>
                                <div class="text-center py-3">
                                    <i class="mdi mdi-information text-muted" style="font-size: 2rem;"></i>
                                    <p class="text-muted mt-2">Tidak ada data pelanggan</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($top_customers as $index => $customer): ?>
                                <div class="d-flex align-items-center mb-3">
                                    <div class="flex-shrink-0">
                                        <span class="badge bg-success"><?= $index + 1 ?></span>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <h6 class="mb-1"><?= htmlspecialchars($customer['nama_pelanggan']) ?></h6>
                                        <p class="mb-0 text-muted">
                                            <?= $customer['jumlah_transaksi'] ?> transaksi<br>
                                            <strong><?= formatCurrency($customer['total_belanja']) ?></strong>
                                        </p>
                                    </div>
                                </div>
                                <?php endforeach; ?>
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
    // Daily Sales Chart
    const dailyData = <?= json_encode($daily_sales) ?>;
    const month = '<?= $selected_month ?>';

    // Generate all days for the month
    const year = parseInt(month.split('-')[0]);
    const monthNum = parseInt(month.split('-')[1]);
    const daysInMonth = new Date(year, monthNum, 0).getDate();

    const dates = [];
    const sales = [];
    const transactions = [];

    for (let i = 1; i <= daysInMonth; i++) {
        const date = `${year}-${monthNum.toString().padStart(2, '0')}-${i.toString().padStart(2, '0')}`;
        dates.push(i.toString());

        // Find data for this date
        const dayData = dailyData.find(d => d.tanggal === date);
        sales.push(dayData ? parseFloat(dayData.total_penjualan) : 0);
        transactions.push(dayData ? parseInt(dayData.jumlah_transaksi) : 0);
    }

    const ctx = document.getElementById('dailySalesChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: dates,
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
                        text: 'Tanggal'
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

    // Customer Statistics Pie Chart
    const customerStats = <?= json_encode($customer_stats) ?>;
    const ctx2 = document.getElementById('customerStatsChart').getContext('2d');
    new Chart(ctx2, {
        type: 'doughnut',
        data: {
            labels: ['Terdaftar', 'Guest', 'Umum'],
            datasets: [{
                data: [
                    customerStats.pelanggan_terdaftar,
                    customerStats.pelanggan_guest,
                    customerStats.pelanggan_umum
                ],
                backgroundColor: [
                    'rgb(54, 162, 235)',
                    'rgb(255, 205, 86)',
                    'rgb(255, 99, 132)'
                ]
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'bottom',
                }
            }
        }
    });

    // Export function
    window.exportReport = function() {
        window.open('export_monthly.php?month=<?= $selected_month ?>', '_blank');
    };
});
</script>

</body>
</html>