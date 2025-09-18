<?php
/**
 * Revenue Report
 * Laporan pendapatan dan omzet untuk admin
 */

require_once '../../includes/admin_auth.php';

// Page config
$page_title = 'Laporan Pendapatan & Omzet';
$page_description = 'Analisis pendapatan dan tren penjualan';

// Get date range and period type
$period_type = sanitizeInput($_GET['period'] ?? 'daily');
$date_from = sanitizeInput($_GET['date_from'] ?? date('Y-m-d', strtotime('-30 days')));
$date_to = sanitizeInput($_GET['date_to'] ?? date('Y-m-d'));

// Validate inputs
if (!in_array($period_type, ['daily', 'weekly', 'monthly'])) {
    $period_type = 'daily';
}
if (!strtotime($date_from)) $date_from = date('Y-m-d', strtotime('-30 days'));
if (!strtotime($date_to)) $date_to = date('Y-m-d');

// Build revenue query based on period type
switch ($period_type) {
    case 'weekly':
        $group_by = "YEARWEEK(t.tanggal_transaksi, 1)";
        $date_format = "CONCAT(YEAR(t.tanggal_transaksi), '-W', LPAD(WEEK(t.tanggal_transaksi, 1), 2, '0'))";
        break;
    case 'monthly':
        $group_by = "DATE_FORMAT(t.tanggal_transaksi, '%Y-%m')";
        $date_format = "DATE_FORMAT(t.tanggal_transaksi, '%Y-%m')";
        break;
    default: // daily
        $group_by = "DATE(t.tanggal_transaksi)";
        $date_format = "DATE(t.tanggal_transaksi)";
        break;
}

// Get revenue data
$revenue_sql = "SELECT
                $date_format as periode,
                COUNT(*) as jumlah_transaksi,
                SUM(t.total) as total_pendapatan,
                SUM(t.subtotal) as total_subtotal,
                AVG(t.total) as rata_rata_transaksi,
                MIN(t.total) as transaksi_terendah,
                MAX(t.total) as transaksi_tertinggi
                FROM transactions t
                WHERE DATE(t.tanggal_transaksi) BETWEEN ? AND ?
                  AND t.status = 'selesai'
                GROUP BY $group_by
                ORDER BY periode";

$revenue_data = fetchAll($revenue_sql, [$date_from, $date_to]);

// Get overall summary
$summary_sql = "SELECT
                COUNT(*) as total_transaksi,
                SUM(total) as total_pendapatan,
                AVG(total) as rata_rata_transaksi,
                MIN(total) as transaksi_terendah,
                MAX(total) as transaksi_tertinggi
                FROM transactions
                WHERE DATE(tanggal_transaksi) BETWEEN ? AND ?
                  AND status = 'selesai'";

$summary = fetchOne($summary_sql, [$date_from, $date_to]);

// Calculate growth (compare with previous period)
$period_days = (strtotime($date_to) - strtotime($date_from)) / (60 * 60 * 24) + 1;
$prev_date_from = date('Y-m-d', strtotime($date_from . " -{$period_days} days"));
$prev_date_to = date('Y-m-d', strtotime($date_from . " -1 days"));

$prev_summary_sql = "SELECT
                     COUNT(*) as total_transaksi,
                     SUM(total) as total_pendapatan
                     FROM transactions
                     WHERE DATE(tanggal_transaksi) BETWEEN ? AND ?
                       AND status = 'selesai'";

$prev_summary = fetchOne($prev_summary_sql, [$prev_date_from, $prev_date_to]);

// Calculate growth percentages
$revenue_growth = 0;
$transaction_growth = 0;

if ($prev_summary['total_pendapatan'] > 0) {
    $revenue_growth = (($summary['total_pendapatan'] - $prev_summary['total_pendapatan']) / $prev_summary['total_pendapatan']) * 100;
}

if ($prev_summary['total_transaksi'] > 0) {
    $transaction_growth = (($summary['total_transaksi'] - $prev_summary['total_transaksi']) / $prev_summary['total_transaksi']) * 100;
}

// Get payment method analysis (if you have payment_method field)
$payment_analysis_sql = "SELECT
                         'Cash' as metode_pembayaran,
                         COUNT(*) as jumlah_transaksi,
                         SUM(total) as total_nilai
                         FROM transactions
                         WHERE DATE(tanggal_transaksi) BETWEEN ? AND ?
                           AND status = 'selesai'";

$payment_analysis = fetchAll($payment_analysis_sql, [$date_from, $date_to]);

// Get hourly revenue pattern
$hourly_pattern_sql = "SELECT
                       HOUR(tanggal_transaksi) as jam,
                       COUNT(*) as jumlah_transaksi,
                       SUM(total) as total_pendapatan,
                       AVG(total) as rata_rata_transaksi
                       FROM transactions
                       WHERE DATE(tanggal_transaksi) BETWEEN ? AND ?
                         AND status = 'selesai'
                       GROUP BY HOUR(tanggal_transaksi)
                       ORDER BY jam";

$hourly_pattern = fetchAll($hourly_pattern_sql, [$date_from, $date_to]);

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

            <!-- Filter Controls -->
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <form method="GET" class="row g-3 align-items-end">
                                <div class="col-md-2">
                                    <label class="form-label">Periode</label>
                                    <select name="period" class="form-control">
                                        <option value="daily" <?= $period_type === 'daily' ? 'selected' : '' ?>>Harian</option>
                                        <option value="weekly" <?= $period_type === 'weekly' ? 'selected' : '' ?>>Mingguan</option>
                                        <option value="monthly" <?= $period_type === 'monthly' ? 'selected' : '' ?>>Bulanan</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Dari Tanggal</label>
                                    <input type="date" name="date_from" class="form-control" value="<?= $date_from ?>" max="<?= date('Y-m-d') ?>">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Sampai Tanggal</label>
                                    <input type="date" name="date_to" class="form-control" value="<?= $date_to ?>" max="<?= date('Y-m-d') ?>">
                                </div>
                                <div class="col-md-4">
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
                            <i class="mdi mdi-currency-usd stats-icon"></i>
                            <h3 class="stats-number"><?= formatCurrency($summary['total_pendapatan']) ?></h3>
                            <h5 class="text-white">Total Pendapatan</h5>
                            <small class="text-white-50">
                                <?= $revenue_growth >= 0 ? '+' : '' ?><?= number_format($revenue_growth, 1) ?>% dari periode sebelumnya
                            </small>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3">
                    <div class="card stats-card">
                        <div class="card-body text-center">
                            <i class="mdi mdi-cart stats-icon"></i>
                            <h3 class="stats-number"><?= number_format($summary['total_transaksi']) ?></h3>
                            <h5 class="text-white">Total Transaksi</h5>
                            <small class="text-white-50">
                                <?= $transaction_growth >= 0 ? '+' : '' ?><?= number_format($transaction_growth, 1) ?>% dari periode sebelumnya
                            </small>
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
                            <i class="mdi mdi-trending-up stats-icon"></i>
                            <h3 class="stats-number"><?= formatCurrency($summary['transaksi_tertinggi']) ?></h3>
                            <h5 class="text-white">Transaksi Tertinggi</h5>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <!-- Revenue Trend Chart -->
                <div class="col-xl-8">
                    <div class="card">
                        <div class="card-header">
                            <h4 class="header-title">Tren Pendapatan (<?= ucfirst($period_type) ?>)</h4>
                        </div>
                        <div class="card-body">
                            <canvas id="revenueTrendChart" width="400" height="200"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Hourly Revenue Pattern -->
                <div class="col-xl-4">
                    <div class="card">
                        <div class="card-header">
                            <h4 class="header-title">Pola Pendapatan per Jam</h4>
                        </div>
                        <div class="card-body">
                            <canvas id="hourlyPatternChart" width="200" height="200"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Revenue Detail Table -->
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h4 class="header-title">Detail Pendapatan per <?= ucfirst($period_type) ?></h4>
                        </div>
                        <div class="card-body">
                            <?php if (empty($revenue_data)): ?>
                                <div class="text-center py-4">
                                    <i class="mdi mdi-information text-muted" style="font-size: 3rem;"></i>
                                    <h4 class="mt-3">Tidak ada data</h4>
                                    <p class="text-muted">Tidak ada transaksi pada periode ini</p>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-centered table-nowrap table-hover mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Periode</th>
                                                <th>Transaksi</th>
                                                <th>Total Pendapatan</th>
                                                <th>Rata-rata Transaksi</th>
                                                <th>Terendah</th>
                                                <th>Tertinggi</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($revenue_data as $data): ?>
                                            <tr>
                                                <td>
                                                    <strong>
                                                        <?php
                                                        if ($period_type === 'weekly') {
                                                            echo 'Minggu ' . substr($data['periode'], -2) . ', ' . substr($data['periode'], 0, 4);
                                                        } elseif ($period_type === 'monthly') {
                                                            echo date('F Y', strtotime($data['periode'] . '-01'));
                                                        } else {
                                                            echo formatDate($data['periode']);
                                                        }
                                                        ?>
                                                    </strong>
                                                </td>
                                                <td><?= number_format($data['jumlah_transaksi']) ?></td>
                                                <td class="currency"><?= formatCurrency($data['total_pendapatan']) ?></td>
                                                <td class="currency"><?= formatCurrency($data['rata_rata_transaksi']) ?></td>
                                                <td class="currency"><?= formatCurrency($data['transaksi_terendah']) ?></td>
                                                <td class="currency"><?= formatCurrency($data['transaksi_tertinggi']) ?></td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                        <tfoot class="table-light">
                                            <tr>
                                                <th>TOTAL</th>
                                                <th><?= number_format($summary['total_transaksi']) ?></th>
                                                <th class="currency"><?= formatCurrency($summary['total_pendapatan']) ?></th>
                                                <th class="currency"><?= formatCurrency($summary['rata_rata_transaksi']) ?></th>
                                                <th class="currency"><?= formatCurrency($summary['transaksi_terendah']) ?></th>
                                                <th class="currency"><?= formatCurrency($summary['transaksi_tertinggi']) ?></th>
                                            </tr>
                                        </tfoot>
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
    // Revenue Trend Chart
    const revenueData = <?= json_encode($revenue_data) ?>;

    const periods = [];
    const revenues = [];
    const transactions = [];

    revenueData.forEach(data => {
        let periodLabel = data.periode;

        // Format period label based on type
        const periodType = '<?= $period_type ?>';
        if (periodType === 'weekly') {
            periodLabel = 'W' + data.periode.substr(-2);
        } else if (periodType === 'monthly') {
            const date = new Date(data.periode + '-01');
            periodLabel = date.toLocaleDateString('id-ID', { month: 'short', year: 'numeric' });
        } else {
            const date = new Date(data.periode);
            periodLabel = date.toLocaleDateString('id-ID', { day: 'numeric', month: 'short' });
        }

        periods.push(periodLabel);
        revenues.push(parseFloat(data.total_pendapatan));
        transactions.push(parseInt(data.jumlah_transaksi));
    });

    const ctx1 = document.getElementById('revenueTrendChart').getContext('2d');
    new Chart(ctx1, {
        type: 'line',
        data: {
            labels: periods,
            datasets: [{
                label: 'Pendapatan (Rp)',
                data: revenues,
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
                        text: 'Periode'
                    }
                },
                y: {
                    type: 'linear',
                    display: true,
                    position: 'left',
                    title: {
                        display: true,
                        text: 'Pendapatan (Rp)'
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

    // Hourly Pattern Chart
    const hourlyData = <?= json_encode($hourly_pattern) ?>;

    const hours = [];
    const hourlyRevenues = [];

    // Fill all hours (6-22 for business hours)
    for (let i = 6; i <= 22; i++) {
        hours.push(i.toString().padStart(2, '0') + ':00');

        // Find data for this hour
        const hourData = hourlyData.find(h => h.jam == i);
        hourlyRevenues.push(hourData ? parseFloat(hourData.total_pendapatan) : 0);
    }

    const ctx2 = document.getElementById('hourlyPatternChart').getContext('2d');
    new Chart(ctx2, {
        type: 'bar',
        data: {
            labels: hours,
            datasets: [{
                label: 'Pendapatan per Jam',
                data: hourlyRevenues,
                backgroundColor: 'rgba(54, 162, 235, 0.6)',
                borderColor: 'rgba(54, 162, 235, 1)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Pendapatan (Rp)'
                    }
                },
                x: {
                    title: {
                        display: true,
                        text: 'Jam'
                    }
                }
            }
        }
    });

    // Export function
    window.exportReport = function() {
        const params = new URLSearchParams({
            period: '<?= $period_type ?>',
            date_from: '<?= $date_from ?>',
            date_to: '<?= $date_to ?>'
        });
        window.open('export_revenue.php?' + params.toString(), '_blank');
    };
});
</script>

</body>
</html>