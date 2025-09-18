<?php
/**
 * Transaction Detail - AJAX Endpoint for Kasir
 * Show transaction details in modal for kasir area
 */

require_once '../includes/kasir_auth.php';

$transaction_id = intval($_GET['id'] ?? 0);

if ($transaction_id <= 0) {
    echo '<div class="alert alert-danger">ID transaksi tidak valid</div>';
    exit;
}

// Get transaction data (only for current kasir)
$transaction_sql = "SELECT t.*,
                    CASE
                        WHEN t.customer_id IS NOT NULL THEN c.nama_pelanggan
                        WHEN t.customer_name IS NOT NULL THEN t.customer_name
                        ELSE NULL
                    END as nama_pelanggan,
                    c.no_hp, c.alamat
                    FROM transactions t
                    LEFT JOIN customers c ON t.customer_id = c.id
                    WHERE t.id = ? AND t.user_id = ?";

$transaction = fetchOne($transaction_sql, [$transaction_id, $current_user['id']]);

if (!$transaction) {
    echo '<div class="alert alert-danger">Transaksi tidak ditemukan atau Anda tidak memiliki akses</div>';
    exit;
}

// Get transaction details
$details_sql = "SELECT td.*, p.nama_barang, p.satuan, p.kode_barang
                FROM transaction_details td
                JOIN products p ON td.product_id = p.id
                WHERE td.transaction_id = ?
                ORDER BY p.nama_barang";

$details = fetchAll($details_sql, [$transaction_id]);
?>

<div class="row">
    <div class="col-md-6">
        <h6 class="text-muted mb-2">Informasi Transaksi</h6>
        <table class="table table-sm">
            <tr>
                <td style="width: 40%"><strong>Kode Transaksi:</strong></td>
                <td><?= htmlspecialchars($transaction['kode_transaksi']) ?></td>
            </tr>
            <tr>
                <td><strong>Tanggal:</strong></td>
                <td><?= formatDateTime($transaction['tanggal_transaksi']) ?></td>
            </tr>
            <tr>
                <td><strong>Status:</strong></td>
                <td>
                    <span class="badge <?= $transaction['status'] === 'selesai' ? 'badge-success' : 'badge-danger' ?>">
                        <?= ucfirst($transaction['status']) ?>
                    </span>
                </td>
            </tr>
            <tr>
                <td><strong>Kasir:</strong></td>
                <td><?= htmlspecialchars($current_user['nama_lengkap']) ?></td>
            </tr>
        </table>
    </div>
    <div class="col-md-6">
        <h6 class="text-muted mb-2">Informasi Pelanggan</h6>
        <table class="table table-sm">
            <?php if ($transaction['customer_id']): ?>
                <tr>
                    <td style="width: 40%"><strong>Nama:</strong></td>
                    <td><?= htmlspecialchars($transaction['nama_pelanggan']) ?></td>
                </tr>
                <tr>
                    <td><strong>No. HP:</strong></td>
                    <td><?= htmlspecialchars($transaction['no_hp'] ?? '-') ?></td>
                </tr>
                <tr>
                    <td><strong>Alamat:</strong></td>
                    <td><?= htmlspecialchars($transaction['alamat'] ?? '-') ?></td>
                </tr>
            <?php elseif ($transaction['customer_name']): ?>
                <tr>
                    <td style="width: 40%"><strong>Tipe:</strong></td>
                    <td>Guest Customer</td>
                </tr>
                <tr>
                    <td><strong>Nama:</strong></td>
                    <td><?= htmlspecialchars($transaction['customer_name']) ?></td>
                </tr>
            <?php else: ?>
                <tr>
                    <td style="width: 40%"><strong>Tipe:</strong></td>
                    <td>Pelanggan Umum</td>
                </tr>
            <?php endif; ?>
        </table>
    </div>
</div>

<div class="row mt-3">
    <div class="col-12">
        <h6 class="text-muted mb-2">Detail Barang</h6>
        <div class="table-responsive">
            <table class="table table-striped table-sm">
                <thead class="thead-light">
                    <tr>
                        <th>No</th>
                        <th>Kode Barang</th>
                        <th>Nama Barang</th>
                        <th>Satuan</th>
                        <th class="text-right">Qty</th>
                        <th class="text-right">Harga Satuan</th>
                        <th class="text-right">Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($details): ?>
                        <?php foreach ($details as $index => $detail): ?>
                            <tr>
                                <td><?= $index + 1 ?></td>
                                <td><?= htmlspecialchars($detail['kode_barang']) ?></td>
                                <td><?= htmlspecialchars($detail['nama_barang']) ?></td>
                                <td><?= htmlspecialchars($detail['satuan']) ?></td>
                                <td class="text-right"><?= number_format($detail['jumlah']) ?></td>
                                <td class="text-right"><?= formatCurrency($detail['harga_satuan']) ?></td>
                                <td class="text-right"><?= formatCurrency($detail['subtotal']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
                <tfoot class="thead-light">
                    <tr>
                        <th colspan="6" class="text-right">Total:</th>
                        <th class="text-right"><?= formatCurrency($transaction['total']) ?></th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

<div class="row mt-3">
    <div class="col-12">
        <h6 class="text-muted mb-2">Ringkasan</h6>
        <div class="row">
            <div class="col-md-4">
                <div class="card bg-light">
                    <div class="card-body text-center">
                        <h5 class="card-title">Total Item</h5>
                        <h3 class="text-primary"><?= count($details) ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card bg-light">
                    <div class="card-body text-center">
                        <h5 class="card-title">Total Qty</h5>
                        <h3 class="text-success"><?= array_sum(array_column($details, 'jumlah')) ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card bg-light">
                    <div class="card-body text-center">
                        <h5 class="card-title">Total Pembayaran</h5>
                        <h3 class="text-info"><?= formatCurrency($transaction['total']) ?></h3>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>