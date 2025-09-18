<?php
/**
 * Transaction Detail AJAX
 * Returns transaction detail content for modal display
 */

require_once '../../includes/admin_auth.php';

$transaction_id = (int)($_GET['id'] ?? 0);

if (!$transaction_id) {
    echo '<div class="alert alert-danger">ID transaksi tidak valid</div>';
    exit;
}

// Get transaction data
$transaction_sql = "SELECT t.*,
                    CASE
                        WHEN t.customer_id IS NOT NULL THEN c.nama_pelanggan
                        WHEN t.customer_name IS NOT NULL THEN CONCAT('Guest: ', t.customer_name)
                        ELSE 'Pelanggan Umum'
                    END as nama_pelanggan,
                    c.no_hp as customer_phone,
                    c.alamat as customer_address,
                    u.nama_lengkap as kasir_nama
                    FROM transactions t
                    LEFT JOIN customers c ON t.customer_id = c.id
                    LEFT JOIN users u ON t.user_id = u.id
                    WHERE t.id = ?";

$transaction = fetchOne($transaction_sql, [$transaction_id]);

if (!$transaction) {
    echo '<div class="alert alert-danger">Transaksi tidak ditemukan</div>';
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
        <table class="table table-sm table-borderless">
            <tr>
                <td width="40%"><strong>Kode Transaksi</strong></td>
                <td><?= htmlspecialchars($transaction['kode_transaksi']) ?></td>
            </tr>
            <tr>
                <td><strong>Tanggal</strong></td>
                <td><?= formatDateTime($transaction['tanggal_transaksi']) ?></td>
            </tr>
            <tr>
                <td><strong>Status</strong></td>
                <td>
                    <span class="badge <?= $transaction['status'] === 'selesai' ? 'bg-success' : 'bg-danger' ?>">
                        <?= ucfirst($transaction['status']) ?>
                    </span>
                </td>
            </tr>
            <tr>
                <td><strong>Kasir</strong></td>
                <td><?= htmlspecialchars($transaction['kasir_nama']) ?></td>
            </tr>
        </table>
    </div>
    <div class="col-md-6">
        <h6 class="text-muted mb-2">Informasi Pelanggan</h6>
        <table class="table table-sm table-borderless">
            <tr>
                <td width="40%"><strong>Nama</strong></td>
                <td><?= htmlspecialchars($transaction['nama_pelanggan']) ?></td>
            </tr>
            <?php if (!empty($transaction['customer_phone'])): ?>
            <tr>
                <td><strong>No. HP</strong></td>
                <td><?= htmlspecialchars($transaction['customer_phone']) ?></td>
            </tr>
            <?php endif; ?>
            <?php if (!empty($transaction['customer_address'])): ?>
            <tr>
                <td><strong>Alamat</strong></td>
                <td><?= htmlspecialchars($transaction['customer_address']) ?></td>
            </tr>
            <?php endif; ?>
        </table>
    </div>
</div>

<hr>

<h6 class="text-muted mb-3">Detail Barang</h6>

<div class="table-responsive">
    <table class="table table-sm table-striped">
        <thead class="table-light">
            <tr>
                <th>No</th>
                <th>Kode Barang</th>
                <th>Nama Barang</th>
                <th>Satuan</th>
                <th class="text-center">Qty</th>
                <th class="text-end">Harga Satuan</th>
                <th class="text-end">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($details as $index => $detail): ?>
            <tr>
                <td><?= $index + 1 ?></td>
                <td><?= htmlspecialchars($detail['kode_barang']) ?></td>
                <td><?= htmlspecialchars($detail['nama_barang']) ?></td>
                <td><?= htmlspecialchars($detail['satuan']) ?></td>
                <td class="text-center"><?= $detail['jumlah'] ?></td>
                <td class="text-end"><?= formatCurrency($detail['harga_satuan']) ?></td>
                <td class="text-end"><?= formatCurrency($detail['subtotal']) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot class="table-light">
            <tr>
                <th colspan="6" class="text-end">Subtotal:</th>
                <th class="text-end"><?= formatCurrency($transaction['subtotal']) ?></th>
            </tr>
            <tr>
                <th colspan="6" class="text-end">Total:</th>
                <th class="text-end text-primary"><?= formatCurrency($transaction['total']) ?></th>
            </tr>
        </tfoot>
    </table>
</div>