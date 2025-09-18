<?php
/**
 * Weekly Sales Report
 * Laporan penjualan mingguan untuk admin
 */

require_once '../../includes/admin_auth.php';

// Page config
$page_title = 'Laporan Penjualan Mingguan';
$page_description = 'Laporan detail penjualan per minggu';

// Get selected week (default current week)
$selected_week = sanitizeInput($_GET['week'] ?? date('Y-\WW'));

// Validate week format
if (!preg_match('/^\d{4}-W\d{2}$/', $selected_week)) {
    $selected_week = date('Y-\WW');
}

// Calculate week start and end dates
$year = substr($selected_week, 0, 4);
$week = substr($selected_week, -2);
$week_start = date('Y-m-d', strtotime($year . 'W' . $week . '1')); // Monday
$week_end = date('Y-m-d', strtotime($year . 'W' . $week . '7')); // Sunday

// Get week summary
$summary_sql = "SELECT
                COUNT(*) as total_transaksi,
                COUNT(CASE WHEN status = 'selesai' THEN 1 END) as transaksi_selesai,
                COUNT(CASE WHEN status = 'batal' THEN 1 END) as transaksi_batal,
                COALESCE(SUM(CASE WHEN status = 'selesai' THEN total ELSE 0 END), 0) as total_omzet,
                COALESCE(AVG(CASE WHEN status = 'selesai' THEN total END), 0) as rata_rata_transaksi
                FROM transactions
                WHERE DATE(tanggal_transaksi) BETWEEN ? AND ?";

$summary = fetchOne($summary_sql, [$week_start, $week_end]);

// Get daily sales for the week
$daily_sales_sql = "SELECT
                    DATE(tanggal_transaksi) as tanggal,
                    DAYNAME(tanggal_transaksi) as hari,
                    COUNT(*) as jumlah_transaksi,
                    COALESCE(SUM(CASE WHEN status = 'selesai' THEN total ELSE 0 END), 0) as total_penjualan
                    FROM transactions
                    WHERE DATE(tanggal_transaksi) BETWEEN ? AND ?
                    GROUP BY DATE(tanggal_transaksi)
                    ORDER BY tanggal";

$daily_sales = fetchAll($daily_sales_sql, [$week_start, $week_end]);

// Get top products for the week
$top_products_sql = "SELECT p.nama_barang, p.satuan, SUM(td.jumlah) as total_terjual,
                     SUM(td.subtotal) as total_pendapatan
                     FROM transaction_details td
                     JOIN transactions t ON td.transaction_id = t.id
                     JOIN products p ON td.product_id = p.id
                     WHERE DATE(t.tanggal_transaksi) BETWEEN ? AND ? AND t.status = 'selesai'
                     GROUP BY p.id
                     ORDER BY total_terjual DESC
                     LIMIT 10";

$top_products = fetchAll($top_products_sql, [$week_start, $week_end]);

// Get transactions for the week
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
                     WHERE DATE(t.tanggal_transaksi) BETWEEN ? AND ?
                     ORDER BY t.tanggal_transaksi DESC";

$transactions = fetchAll($transactions_sql, [$week_start, $week_end]);

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

            <!-- Week Filter -->
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <form method="GET" class="row g-3 align-items-end">
                                <div class="col-md-3">
                                    <label class="form-label">Pilih Minggu</label>
                                    <input type="week" name="week" class="form-control" value="<?= $selected_week ?>" max="<?= date('Y-\WW') ?>">
                                </div>
                                <div class="col-md-3">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="mdi mdi-magnify"></i> Tampilkan
                                    </button>
                                    <button type="button" class="btn btn-success" onclick="exportReport()">
                                        <i class="mdi mdi-file-excel"></i> Export
                                    </button>
                                </div>
                                <div class="col-md-6">
                                    <div class="text-muted">
                                        <strong>Periode:</strong> <?= formatDate($week_start) ?> - <?= formatDate($week_end) ?>
                                    </div>
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
                <!-- Daily Sales Chart -->
                <div class="col-xl-8">
                    <div class="card">
                        <div class="card-header">
                            <h4 class="header-title">Penjualan Harian - Minggu <?= $week ?>, <?= $year ?></h4>
                        </div>
                        <div class="card-body" style="height: 400px;">
                            <canvas id="dailySalesChart"></canvas>
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
                            <h4 class="header-title">Detail Transaksi - Minggu <?= $week ?>, <?= $year ?></h4>
                        </div>
                        <div class="card-body">
                            <?php if (empty($transactions)): ?>
                                <div class="text-center py-4">
                                    <i class="mdi mdi-calendar-blank text-muted" style="font-size: 3rem;"></i>
                                    <h4 class="mt-3">Tidak ada transaksi</h4>
                                    <p class="text-muted">Belum ada transaksi pada minggu ini</p>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-centered table-nowrap table-hover mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Tanggal</th>
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
                                                <td><?= formatDate($transaction['tanggal_transaksi']) ?></td>
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
// Export function - moved outside document ready for global access
function exportReport() {
    const week = '<?= $selected_week ?>';
    window.open('export_weekly.php?week=' + encodeURIComponent(week), '_blank');
}

$(document).ready(function() {
    // Daily Sales Chart - Add error handling
    let dailyDataRaw = <?= json_encode($daily_sales) ?>;
    console.log('Daily Sales Data Raw:', dailyDataRaw);

    // Ensure dailyData is always an array
    const dailyData = Array.isArray(dailyDataRaw) ? dailyDataRaw : [];
    console.log('Daily Sales Data (processed):', dailyData);

    // Days of week in order (using Indonesian day names directly)
    const dayLabels = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'];
    const englishDays = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];

    const sales = [];
    const transactions = [];

    // Generate data for each day of the week
    for (let i = 0; i < 7; i++) {
        const englishDay = englishDays[i];
        const dayData = dailyData.length > 0 ? dailyData.find(d => d && d.hari === englishDay) : null;

        if (dayData) {
            sales.push(parseFloat(dayData.total_penjualan) || 0);
            transactions.push(parseInt(dayData.jumlah_transaksi) || 0);
        } else {
            sales.push(0);
            transactions.push(0);
        }
    }

    console.log('Sales data:', sales);
    console.log('Transactions data:', transactions);

    // Check if canvas element exists
    const canvas = document.getElementById('dailySalesChart');
    if (!canvas) {
        console.error('Canvas element not found!');
        return;
    }

    try {
        const ctx = canvas.getContext('2d');

        // Simplified chart with single y-axis for sales
        const chart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: dayLabels,
                datasets: [{
                    label: 'Penjualan (Rp)',
                    data: sales,
                    backgroundColor: [
                        'rgba(54, 162, 235, 0.8)',
                        'rgba(75, 192, 192, 0.8)',
                        'rgba(255, 205, 86, 0.8)',
                        'rgba(255, 99, 132, 0.8)',
                        'rgba(153, 102, 255, 0.8)',
                        'rgba(255, 159, 64, 0.8)',
                        'rgba(199, 199, 199, 0.8)'
                    ],
                    borderColor: [
                        'rgba(54, 162, 235, 1)',
                        'rgba(75, 192, 192, 1)',
                        'rgba(255, 205, 86, 1)',
                        'rgba(255, 99, 132, 1)',
                        'rgba(153, 102, 255, 1)',
                        'rgba(255, 159, 64, 1)',
                        'rgba(199, 199, 199, 1)'
                    ],
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    title: {
                        display: true,
                        text: 'Penjualan Harian Minggu ini'
                    },
                    legend: {
                        display: true,
                        position: 'top'
                    }
                },
                scales: {
                    x: {
                        display: true,
                        title: {
                            display: true,
                            text: 'Hari dalam Minggu'
                        }
                    },
                    y: {
                        display: true,
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Penjualan (Rp)'
                        },
                        ticks: {
                            callback: function(value) {
                                return 'Rp ' + value.toLocaleString('id-ID');
                            }
                        }
                    }
                }
            }
        });

        console.log('Chart created successfully:', chart);

    } catch (error) {
        console.error('Error creating chart:', error);
        // Show error message in the chart area
        const chartContainer = canvas.parentElement;
        chartContainer.innerHTML = '<div class="alert alert-danger">Error loading chart: ' + error.message + '</div>';
    }
});
</script>

</body>
</html>