<?php

/**
 * Admin Transactions Management
 * Halaman untuk melihat dan mengelola semua transaksi
 */

require_once '../../includes/admin_auth.php';

// Page config
$page_title = 'Manajemen Transaksi';
$page_description = 'Kelola dan pantau semua transaksi penjualan';

// Handle pagination
$page = (int)($_GET['page'] ?? 1);
$limit = 20;
$offset = ($page - 1) * $limit;

// Handle filters
$search = sanitizeInput($_GET['search'] ?? '');
$date_from = sanitizeInput($_GET['date_from'] ?? '');
$date_to = sanitizeInput($_GET['date_to'] ?? '');
$status_filter = sanitizeInput($_GET['status'] ?? '');

// Build query
$where_conditions = [];
$params = [];

if (!empty($search)) {
  $where_conditions[] = "(t.kode_transaksi LIKE ? OR c.nama_pelanggan LIKE ? OR t.customer_name LIKE ? OR u.nama_lengkap LIKE ?)";
  $searchTerm = "%$search%";
  $params[] = $searchTerm;
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

if (!empty($status_filter)) {
  $where_conditions[] = "t.status = ?";
  $params[] = $status_filter;
}

$where_clause = '';
if (!empty($where_conditions)) {
  $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
}

// Get total count
$count_sql = "SELECT COUNT(*) as total
              FROM transactions t
              LEFT JOIN customers c ON t.customer_id = c.id
              LEFT JOIN users u ON t.user_id = u.id
              $where_clause";

$total_transactions = fetchOne($count_sql, $params)['total'];
$total_pages = ceil($total_transactions / $limit);

// Get transactions data
$sql = "SELECT t.*,
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
        $where_clause
        ORDER BY t.tanggal_transaksi DESC
        LIMIT $limit OFFSET $offset";

$transactions = fetchAll($sql, $params);

// Get summary stats for today
$today = date('Y-m-d');
$today_stats_sql = "SELECT
                    COUNT(*) as total_transactions,
                    COALESCE(SUM(total), 0) as total_revenue,
                    COALESCE(AVG(total), 0) as avg_transaction
                    FROM transactions
                    WHERE DATE(tanggal_transaksi) = ? AND status = 'selesai'";
$today_stats = fetchOne($today_stats_sql, [$today]);

// Success/Error messages
$success_message = $_SESSION['success_message'] ?? '';
$error_message = $_SESSION['error_message'] ?? '';
unset($_SESSION['success_message'], $_SESSION['error_message']);

include '../../includes/header.php';
include '../../includes/sidebar.php';
?>

<style>
  .page-title-box {
    position: relative;
  }

  .page-title-action {
    flex-shrink: 0;
  }

  /* Action buttons styling */
  .btn-group .btn {
    border: 1px solid #dee2e6;
    background-color: #fff;
  }

  .btn-info {
    color: #fff !important;
    background-color: #17a2b8 !important;
    border-color: #17a2b8 !important;
  }

  .btn-primary {
    color: #fff !important;
    background-color: #007bff !important;
    border-color: #007bff !important;
  }

  .btn-warning {
    color: #212529 !important;
    background-color: #ffc107 !important;
    border-color: #ffc107 !important;
  }

  .btn-group .btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
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
  }

  @media (max-width: 576px) {
    .page-title-action .btn {
      font-size: 14px;
      padding: 8px 16px;
    }

    .btn-group {
      display: flex;
      width: 100%;
    }

    .btn-group .btn {
      flex: 1;
      font-size: 12px;
      padding: 4px 8px;
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
                  <li class="breadcrumb-item"><a href="<?= ADMIN_URL ?>dashboard.php">Dashboard</a></li>
                  <li class="breadcrumb-item active"><?= $page_title ?></li>
                </ol>
              </div>
              <h4 class="page-title mb-0"><?= $page_title ?></h4>
            </div>
            <div class="page-title-action">
              <a href="<?= BASE_URL ?>kasir/penjualan.php" class="btn btn-success btn-lg" target="_blank">
                <i class="mdi mdi-cash-register"></i> Buka POS (Point of Sale)
              </a>
            </div>
          </div>
        </div>
      </div>

      <?php if ($success_message): ?>
        <div class="row">
          <div class="col-12">
            <div class="alert alert-success alert-dismissible fade show" role="alert">
              <strong>Berhasil!</strong> <?= htmlspecialchars($success_message) ?>
              <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
              </button>
            </div>
          </div>
        </div>
      <?php endif; ?>

      <?php if ($error_message): ?>
        <div class="row">
          <div class="col-12">
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
              <strong>Error!</strong> <?= htmlspecialchars($error_message) ?>
              <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
              </button>
            </div>
          </div>
        </div>
      <?php endif; ?>

      <!-- Today's Stats -->
      <div class="row">
        <div class="col-xl-4">
          <div class="card stats-card">
            <div class="card-body">
              <div class="text-center">
                <i class="mdi mdi-cart stats-icon"></i>
                <h3 class="stats-number"><?= $today_stats['total_transactions'] ?></h3>
                <h5 class="text-white">Transaksi Hari Ini</h5>
              </div>
            </div>
          </div>
        </div>
        <div class="col-xl-4">
          <div class="card stats-card">
            <div class="card-body">
              <div class="text-center">
                <i class="mdi mdi-currency-usd stats-icon"></i>
                <h3 class="stats-number"><?= formatCurrency($today_stats['total_revenue']) ?></h3>
                <h5 class="text-white">Omzet Hari Ini</h5>
              </div>
            </div>
          </div>
        </div>
        <div class="col-xl-4">
          <div class="card stats-card">
            <div class="card-body">
              <div class="text-center">
                <i class="mdi mdi-calculator stats-icon"></i>
                <h3 class="stats-number"><?= formatCurrency($today_stats['avg_transaction']) ?></h3>
                <h5 class="text-white">Rata-rata Transaksi</h5>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="row">
        <div class="col-12">
          <div class="card">
            <div class="card-body">

              <!-- Filter and Search Form -->
              <form method="GET" class="mb-3">
                <div class="row g-3">
                  <div class="col-md-3">
                    <label class="form-label">Cari Transaksi</label>
                    <input type="text" name="search" class="form-control"
                      placeholder="Kode, pelanggan, kasir..."
                      value="<?= htmlspecialchars($search) ?>">
                  </div>
                  <div class="col-md-2">
                    <label class="form-label">Dari Tanggal</label>
                    <input type="date" name="date_from" class="form-control"
                      value="<?= htmlspecialchars($date_from) ?>">
                  </div>
                  <div class="col-md-2">
                    <label class="form-label">Sampai Tanggal</label>
                    <input type="date" name="date_to" class="form-control"
                      value="<?= htmlspecialchars($date_to) ?>">
                  </div>
                  <div class="col-md-2">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-control">
                      <option value="">Semua Status</option>
                      <option value="selesai" <?= $status_filter === 'selesai' ? 'selected' : '' ?>>Selesai</option>
                      <option value="batal" <?= $status_filter === 'batal' ? 'selected' : '' ?>>Batal</option>
                    </select>
                  </div>
                  <div class="col-md-3">
                    <label class="form-label">&nbsp;</label>
                    <div class="d-grid gap-2 d-md-flex">
                      <button type="submit" class="btn btn-primary">
                        <i class="mdi mdi-magnify"></i> Cari
                      </button>
                      <a href="index.php" class="btn btn-outline-secondary">Reset</a>
                      <button type="button" class="btn btn-success" onclick="exportTransactions()">
                        <i class="mdi mdi-file-excel"></i> Export
                      </button>
                    </div>
                  </div>
                </div>
              </form>

              <?php if (empty($transactions)): ?>
                <div class="text-center py-4">
                  <img src="<?= ASSETS_URL ?>Adminto_v4.0.0/Vertical/dist/assets/images/svg/search-not-found.svg" height="90" alt="Not Found">
                  <h4 class="mt-3">Data tidak ditemukan</h4>
                  <p class="text-muted">
                    <?= !empty($search) || !empty($date_from) || !empty($date_to) || !empty($status_filter)
                      ? 'Tidak ada transaksi yang sesuai dengan filter.'
                      : 'Belum ada data transaksi.' ?>
                  </p>
                </div>
              <?php else: ?>

                <div class="table-responsive">
                  <table class="table table-centered table-nowrap table-hover mb-0">
                    <thead class="table-light">
                      <tr>
                        <th>Kode Transaksi</th>
                        <th>Tanggal</th>
                        <th>Pelanggan</th>
                        <th>Kasir</th>
                        <th>Jumlah Item</th>
                        <th>Total</th>
                        <th>Status</th>
                        <th style="width: 125px;">Aksi</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php foreach ($transactions as $transaction): ?>
                        <tr>
                          <td>
                            <strong><?= htmlspecialchars($transaction['kode_transaksi']) ?></strong>
                          </td>
                          <td><?= formatDateTime($transaction['tanggal_transaksi']) ?></td>
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
                          <td>
                            <div class="btn-group" role="group">
                              <button type="button" class="btn btn-info btn-sm"
                                onclick="viewTransactionDetail(<?= $transaction['id'] ?>)"
                                title="Lihat Detail">
                                <i class="mdi mdi-eye"></i>
                              </button>
                              <button type="button" class="btn btn-primary btn-sm"
                                onclick="printReceipt(<?= $transaction['id'] ?>)"
                                title="Cetak Struk">
                                <i class="mdi mdi-printer"></i>
                              </button>
                              <?php if ($transaction['status'] === 'selesai'): ?>
                                <button type="button" class="btn btn-warning btn-sm"
                                  onclick="cancelTransaction(<?= $transaction['id'] ?>)"
                                  title="Batalkan Transaksi">
                                  <i class="mdi mdi-cancel"></i>
                                </button>
                              <?php endif; ?>
                            </div>
                          </td>
                        </tr>
                      <?php endforeach; ?>
                    </tbody>
                  </table>
                </div>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                  <nav aria-label="Page navigation" class="mt-3">
                    <ul class="pagination justify-content-center">
                      <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                        <a class="page-link" href="?page=<?= $page - 1 ?><?= http_build_query(array_filter($_GET)) ? '&' . http_build_query(array_filter(array_diff_key($_GET, ['page' => '']))) : '' ?>">Previous</a>
                      </li>

                      <?php
                      $start = max(1, $page - 2);
                      $end = min($total_pages, $page + 2);

                      for ($i = $start; $i <= $end; $i++):
                      ?>
                        <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                          <a class="page-link" href="?page=<?= $i ?><?= http_build_query(array_filter($_GET)) ? '&' . http_build_query(array_filter(array_diff_key($_GET, ['page' => '']))) : '' ?>"><?= $i ?></a>
                        </li>
                      <?php endfor; ?>

                      <li class="page-item <?= $page >= $total_pages ? 'disabled' : '' ?>">
                        <a class="page-link" href="?page=<?= $page + 1 ?><?= http_build_query(array_filter($_GET)) ? '&' . http_build_query(array_filter(array_diff_key($_GET, ['page' => '']))) : '' ?>">Next</a>
                      </li>
                    </ul>
                  </nav>
                <?php endif; ?>

              <?php endif; ?>

            </div> <!-- end card body-->
          </div> <!-- end card -->
        </div><!-- end col-->
      </div><!-- end row-->

    </div> <!-- container -->

  </div> <!-- content -->

  <?php include '../../includes/footer.php'; ?>

</div>

<!-- Transaction Detail Modal -->
<div class="modal fade" id="transactionDetailModal" tabindex="-1" aria-labelledby="transactionDetailModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="transactionDetailModalLabel">Detail Transaksi</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body" id="transactionDetailContent">
        <div class="text-center py-3">
          <div class="spinner-border" role="status">
            <span class="visually-hidden">Loading...</span>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
        <div class="btn-group" role="group">
          <button type="button" class="btn btn-primary" onclick="printDetailHTML()">
            <i class="mdi mdi-printer"></i> Cetak HTML
          </button>
          <button type="button" class="btn btn-primary" onclick="printDetailText()">
            <i class="mdi mdi-download"></i> Download TXT
          </button>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Cancel Transaction Modal -->
<div class="modal fade" id="cancelTransactionModal" tabindex="-1" aria-labelledby="cancelTransactionModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="cancelTransactionModalLabel">Batalkan Transaksi</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <div class="text-center">
          <i class="mdi mdi-alert-circle-outline text-warning" style="font-size: 3rem;"></i>
          <h4 class="mt-2">Apakah Anda yakin?</h4>
          <p class="text-muted">Transaksi akan dibatalkan dan stok barang akan dikembalikan.</p>
          <p><strong>Kode Transaksi: <span id="cancelTransactionCode"></span></strong></p>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
        <button type="button" class="btn btn-danger" id="confirmCancelTransaction">
          <span class="spinner-border spinner-border-sm me-1" style="display: none;"></span>
          Ya, Batalkan
        </button>
      </div>
    </div>
  </div>
</div>

<!-- bundle -->
<script src="<?= ASSETS_URL ?>Adminto_v4.0.0/Vertical/dist/assets/js/vendor.min.js"></script>
<script src="<?= ASSETS_URL ?>Adminto_v4.0.0/Vertical/dist/assets/js/app.min.js"></script>

<script>
  $(document).ready(function() {
    let cancelTransactionId = null;
    let currentTransactionId = null;

    // View transaction detail
    window.viewTransactionDetail = function(transactionId) {
      currentTransactionId = transactionId;
      $('#transactionDetailContent').html('<div class="text-center py-3"><div class="spinner-border" role="status"><span class="visually-hidden">Loading...</span></div></div>');
      $('#transactionDetailModal').modal('show');

      $.ajax({
        url: 'detail.php',
        method: 'GET',
        data: {
          id: transactionId
        },
        success: function(response) {
          $('#transactionDetailContent').html(response);
        },
        error: function() {
          $('#transactionDetailContent').html('<div class="alert alert-danger">Gagal memuat detail transaksi</div>');
        }
      });
    };

    // Print detail functions
    window.printDetailHTML = function() {
      if (currentTransactionId) {
        window.open('print.php?id=' + currentTransactionId, '_blank');
      }
    };

    window.printDetailText = function() {
      if (currentTransactionId) {
        window.open('print.php?id=' + currentTransactionId + '&format=text', '_blank');
      }
    };

    // Print receipt with options
    window.printReceipt = function(transactionId) {
      if (confirm('Pilih format cetak:\n\nOK = Cetak Struk (HTML)\nCancel = Download TXT (Thermal Printer)')) {
        // Print HTML receipt
        window.open('print.php?id=' + transactionId, '_blank');
      } else {
        // Download text receipt for thermal printer
        window.open('print.php?id=' + transactionId + '&format=text', '_blank');
      }
    };

    // Cancel transaction
    window.cancelTransaction = function(transactionId) {
      cancelTransactionId = transactionId;

      // Get transaction code
      const row = $(`button[onclick="cancelTransaction(${transactionId})"]`).closest('tr');
      const transactionCode = row.find('td:first strong').text();

      $('#cancelTransactionCode').text(transactionCode);
      $('#cancelTransactionModal').modal('show');
    };

    // Confirm cancel transaction
    $('#confirmCancelTransaction').click(function() {
      const submitBtn = $(this);
      const spinner = submitBtn.find('.spinner-border');

      submitBtn.prop('disabled', true);
      spinner.show();

      $.ajax({
        url: 'cancel.php',
        method: 'POST',
        data: {
          id: cancelTransactionId
        },
        dataType: 'json',
        success: function(response) {
          console.log('Cancel response:', response); // Debug log
          if (response.success) {
            $('#cancelTransactionModal').modal('hide');
            // alert('Transaksi berhasil dibatalkan!');
            location.reload();
          } else {
            alert('Error: ' + response.message);
            if (response.debug) {
              console.error('Debug info:', response.debug);
            }
          }
        },
        error: function(xhr, status, error) {
          console.error('AJAX Error:', {
            status: status,
            error: error,
            response: xhr.responseText
          });
          alert('Gagal membatalkan transaksi. Error: ' + error);
        },
        complete: function() {
          submitBtn.prop('disabled', false);
          spinner.hide();
        }
      });
    });

    // Export transactions
    window.exportTransactions = function() {
      const params = new URLSearchParams();

      // Add current filters to export
      const search = $('input[name="search"]').val();
      const dateFrom = $('input[name="date_from"]').val();
      const dateTo = $('input[name="date_to"]').val();
      const status = $('select[name="status"]').val();

      if (search) params.append('search', search);
      if (dateFrom) params.append('date_from', dateFrom);
      if (dateTo) params.append('date_to', dateTo);
      if (status) params.append('status', status);

      window.open('export.php?' + params.toString(), '_blank');
    };
  });
</script>

</body>

</html>