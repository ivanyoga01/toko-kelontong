<?php
/**
 * Kasir Transaction History
 * Halaman riwayat transaksi untuk kasir
 */

require_once '../includes/kasir_auth.php';

$page_title = 'Riwayat Transaksi - ' . APP_NAME;

// Pagination and filters
$page = max(1, intval($_GET['page'] ?? 1));
$search = sanitizeInput($_GET['search'] ?? '');
$date_from = sanitizeInput($_GET['date_from'] ?? '');
$date_to = sanitizeInput($_GET['date_to'] ?? '');

// Build query conditions for kasir's own transactions
$where_conditions = ["t.user_id = ?"];
$params = [$current_user['id']];

if (!empty($search)) {
    $where_conditions[] = "(t.kode_transaksi LIKE ? OR c.nama_pelanggan LIKE ? OR t.customer_name LIKE ?)";
    $searchTerm = "%$search%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

if (!empty($date_from)) {
    $where_conditions[] = "DATE(t.tanggal_transaksi) >= ?";
    $params[] = $date_from;
}

if (!empty($date_to)) {
    $where_conditions[] = "DATE(t.tanggal_transaksi) <= ?";
    $params[] = $date_to;
}

$where_clause = 'WHERE ' . implode(' AND ', $where_conditions);

// Count total records
$count_sql = "SELECT COUNT(*) as total
              FROM transactions t
              LEFT JOIN customers c ON t.customer_id = c.id
              $where_clause";
$total_records = fetchOne($count_sql, $params)['total'];
$pagination = paginate($total_records, $page, 15);

// Get transactions data
$sql = "SELECT t.*,
        CASE
            WHEN t.customer_id IS NOT NULL THEN c.nama_pelanggan
            WHEN t.customer_name IS NOT NULL THEN CONCAT('Guest: ', t.customer_name)
            ELSE 'Pelanggan Umum'
        END as nama_pelanggan,
        (SELECT COUNT(*) FROM transaction_details WHERE transaction_id = t.id) as jumlah_item
        FROM transactions t
        LEFT JOIN customers c ON t.customer_id = c.id
        $where_clause
        ORDER BY t.tanggal_transaksi DESC
        LIMIT {$pagination['records_per_page']} OFFSET {$pagination['offset']}";

$transactions = fetchAll($sql, $params);

// Today's statistics for kasir
$today = date('Y-m-d');
$today_stats_sql = "SELECT
                    COUNT(*) as total_transactions,
                    COALESCE(SUM(total), 0) as total_sales
                    FROM transactions
                    WHERE user_id = ? AND DATE(tanggal_transaksi) = ? AND status = 'selesai'";
$today_stats = fetchOne($today_stats_sql, [$current_user['id'], $today]);

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
                                <li class="breadcrumb-item"><a href="javascript: void(0);">Kasir</a></li>
                                <li class="breadcrumb-item active">Riwayat Transaksi</li>
                            </ol>
                        </div>
                        <h4 class="page-title">Riwayat Transaksi</h4>
                    </div>
                </div>
            </div>

            <!-- Today's Stats -->
            <div class="row">
                <div class="col-sm-6 col-xl-3">
                    <div class="card widget-flat">
                        <div class="card-body">
                            <div class="float-right">
                                <i class="mdi mdi-cart widget-icon bg-success-lighten text-success"></i>
                            </div>
                            <h5 class="text-muted font-weight-normal mt-0">Transaksi Hari Ini</h5>
                            <h3 class="mt-3 mb-3"><?= $today_stats['total_transactions'] ?></h3>
                            <p class="mb-0 text-muted">
                                <span class="text-nowrap">Total transaksi</span>
                            </p>
                        </div>
                    </div>
                </div>

                <div class="col-sm-6 col-xl-3">
                    <div class="card widget-flat">
                        <div class="card-body">
                            <div class="float-right">
                                <i class="mdi mdi-currency-usd widget-icon bg-primary-lighten text-primary"></i>
                            </div>
                            <h5 class="text-muted font-weight-normal mt-0">Penjualan Hari Ini</h5>
                            <h3 class="mt-3 mb-3"><?= formatCurrency($today_stats['total_sales']) ?></h3>
                            <p class="mb-0 text-muted">
                                <span class="text-nowrap">Total penjualan</span>
                            </p>
                        </div>
                    </div>
                </div>

                <div class="col-sm-6 col-xl-3">
                    <div class="card widget-flat">
                        <div class="card-body">
                            <div class="float-right">
                                <i class="mdi mdi-chart-line widget-icon bg-warning-lighten text-warning"></i>
                            </div>
                            <h5 class="text-muted font-weight-normal mt-0">Rata-rata per Transaksi</h5>
                            <h3 class="mt-3 mb-3">
                                <?= $today_stats['total_transactions'] > 0 ? formatCurrency($today_stats['total_sales'] / $today_stats['total_transactions']) : formatCurrency(0) ?>
                            </h3>
                            <p class="mb-0 text-muted">
                                <span class="text-nowrap">Hari ini</span>
                            </p>
                        </div>
                    </div>
                </div>

                <div class="col-sm-6 col-xl-3">
                    <div class="card widget-flat">
                        <div class="card-body">
                            <div class="float-right">
                                <i class="mdi mdi-account-multiple widget-icon bg-info-lighten text-info"></i>
                            </div>
                            <h5 class="text-muted font-weight-normal mt-0">Total Riwayat</h5>
                            <h3 class="mt-3 mb-3"><?= $total_records ?></h3>
                            <p class="mb-0 text-muted">
                                <span class="text-nowrap">Semua transaksi</span>
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Transactions List -->
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <div class="row align-items-center">
                                <div class="col">
                                    <h4 class="header-title">Daftar Transaksi</h4>
                                </div>
                                <div class="col-auto">
                                    <button type="button" class="btn btn-success" onclick="exportTransactions()">
                                        <i class="mdi mdi-download"></i> Export
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <!-- Search and Filter Form -->
                            <form method="GET" class="mb-3">
                                <div class="row">
                                    <div class="col-lg-3">
                                        <input type="text" name="search" class="form-control"
                                               placeholder="Cari transaksi..."
                                               value="<?= htmlspecialchars($search) ?>">
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
                                    <div class="col-lg-3">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fe-search"></i> Cari
                                        </button>
                                        <a href="riwayat.php" class="btn btn-outline-secondary">Reset</a>
                                    </div>
                                </div>
                            </form>

                            <!-- Transactions Table -->
                            <div class="table-responsive">
                                <table class="table table-striped table-bordered table-hover">
                                    <thead class="thead-dark">
                                        <tr>
                                            <th width="5%">No</th>
                                            <th width="12%">Tanggal</th>
                                            <th width="15%">Kode Transaksi</th>
                                            <th width="20%">Pelanggan</th>
                                            <th width="8%">Jumlah Item</th>
                                            <th width="12%">Total</th>
                                            <th width="8%">Status</th>
                                            <th width="15%">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if ($transactions): ?>
                                            <?php foreach ($transactions as $index => $transaction): ?>
                                                <tr>
                                                    <td><?= $pagination['offset'] + $index + 1 ?></td>
                                                    <td><?= formatDateTime($transaction['tanggal_transaksi']) ?></td>
                                                    <td>
                                                        <strong><?= htmlspecialchars($transaction['kode_transaksi']) ?></strong>
                                                    </td>
                                                    <td><?= htmlspecialchars($transaction['nama_pelanggan']) ?></td>
                                                    <td class="text-center">
                                                        <span class="badge badge-info"><?= $transaction['jumlah_item'] ?></span>
                                                    </td>
                                                    <td class="text-right"><?= formatCurrency($transaction['total']) ?></td>
                                                    <td>
                                                        <span class="badge <?= $transaction['status'] === 'selesai' ? 'badge-success' : 'badge-danger' ?>">
                                                            <?= ucfirst($transaction['status']) ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <div class="btn-group" role="group">
                                                            <button type="button" class="btn btn-sm btn-info"
                                                                    onclick="viewTransactionDetail(<?= $transaction['id'] ?>)"
                                                                    title="Lihat Detail">
                                                                <i class="fe-eye"></i>
                                                            </button>
                                                            <button type="button" class="btn btn-sm btn-primary"
                                                                    onclick="printReceipt(<?= $transaction['id'] ?>)"
                                                                    title="Cetak Ulang Struk">
                                                                <i class="fe-printer"></i>
                                                            </button>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="8" class="text-center">
                                                    <?php if (!empty($search) || !empty($date_from) || !empty($date_to)): ?>
                                                        Tidak ada transaksi yang ditemukan dengan filter yang dipilih
                                                    <?php else: ?>
                                                        Belum ada transaksi yang dilakukan
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Pagination -->
                            <?php if ($pagination['total_pages'] > 1): ?>
                                <nav aria-label="Transactions pagination">
                                    <ul class="pagination justify-content-center">
                                        <li class="page-item <?= !$pagination['has_previous'] ? 'disabled' : '' ?>">
                                            <a class="page-link" href="?page=<?= $pagination['previous_page'] ?>&search=<?= urlencode($search) ?>&date_from=<?= urlencode($date_from) ?>&date_to=<?= urlencode($date_to) ?>">Previous</a>
                                        </li>

                                        <?php for ($i = 1; $i <= $pagination['total_pages']; $i++): ?>
                                            <li class="page-item <?= $i == $pagination['current_page'] ? 'active' : '' ?>">
                                                <a class="page-link" href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&date_from=<?= urlencode($date_from) ?>&date_to=<?= urlencode($date_to) ?>"><?= $i ?></a>
                                            </li>
                                        <?php endfor; ?>

                                        <li class="page-item <?= !$pagination['has_next'] ? 'disabled' : '' ?>">
                                            <a class="page-link" href="?page=<?= $pagination['next_page'] ?>&search=<?= urlencode($search) ?>&date_from=<?= urlencode($date_from) ?>&date_to=<?= urlencode($date_to) ?>">Next</a>
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

<!-- Transaction Detail Modal -->
<div class="modal fade" id="transactionDetailModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Detail Transaksi</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body" id="transactionDetailContent">
                <div class="text-center py-3">
                    <div class="spinner-border" role="status">
                        <span class="sr-only">Loading...</span>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
                <button type="button" class="btn btn-primary" id="printFromDetailBtn">
                    <i class="fe-printer"></i> Cetak Struk
                </button>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

<script>
let currentTransactionId = null;

// View transaction detail
function viewTransactionDetail(transactionId) {
    currentTransactionId = transactionId;
    $('#transactionDetailContent').html('<div class="text-center py-3"><div class="spinner-border" role="status"><span class="sr-only">Loading...</span></div></div>');
    $('#transactionDetailModal').modal('show');

    $.ajax({
        url: 'transaction_detail.php',
        method: 'GET',
        data: { id: transactionId },
        success: function(response) {
            $('#transactionDetailContent').html(response);
        },
        error: function() {
            $('#transactionDetailContent').html('<div class="alert alert-danger">Gagal memuat detail transaksi</div>');
        }
    });
}

// Print receipt
function printReceipt(transactionId) {
    if (confirm('Pilih format cetak:\n\nOK = Cetak Struk (HTML)\nCancel = Download TXT (Thermal Printer)')) {
        // Print HTML receipt
        window.open('print_receipt.php?id=' + transactionId, '_blank');
    } else {
        // Download text receipt for thermal printer
        window.open('print_receipt.php?id=' + transactionId + '&format=text', '_blank');
    }
}

// Print from detail modal
$('#printFromDetailBtn').click(function() {
    if (currentTransactionId) {
        printReceipt(currentTransactionId);
    }
});

// Export transactions
function exportTransactions() {
    const search = '<?= urlencode($search) ?>';
    const dateFrom = '<?= urlencode($date_from) ?>';
    const dateTo = '<?= urlencode($date_to) ?>';

    let url = 'export_transactions.php?';
    if (search) url += 'search=' + search + '&';
    if (dateFrom) url += 'date_from=' + dateFrom + '&';
    if (dateTo) url += 'date_to=' + dateTo + '&';

    window.open(url, '_blank');
}

// Auto refresh stats every 5 minutes
setInterval(function() {
    location.reload();
}, 300000);
</script>

</body>
</html>