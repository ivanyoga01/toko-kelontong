<?php
/**
 * Receipt Helper Library
 * Functions for generating and formatting receipts
 */

/**
 * Generate receipt content as text format (for thermal printers)
 */
function generateReceiptText($transaction, $details, $store_info = []) {
    $receipt = "";

    // Store header
    $store_name = $store_info['name'] ?? APP_NAME;
    $store_address = $store_info['address'] ?? '';
    $store_phone = $store_info['phone'] ?? '';

    $receipt .= centerText($store_name, 32) . "\n";
    if ($store_address) {
        $receipt .= centerText($store_address, 32) . "\n";
    }
    if ($store_phone) {
        $receipt .= centerText("Telp: " . $store_phone, 32) . "\n";
    }
    $receipt .= str_repeat("=", 32) . "\n";

    // Transaction info
    $receipt .= "Kode: " . $transaction['kode_transaksi'] . "\n";
    $receipt .= "Tanggal: " . formatDateTime($transaction['tanggal_transaksi']) . "\n";
    $receipt .= "Kasir: " . $transaction['kasir_nama'] . "\n";

    // Customer info
    $customer_display = 'Pelanggan Umum';
    if (!empty($transaction['nama_pelanggan'])) {
        if ($transaction['customer_id']) {
            $customer_display = $transaction['nama_pelanggan'];
        } else {
            $customer_display = 'Guest: ' . $transaction['nama_pelanggan'];
        }
    }
    $receipt .= "Pelanggan: " . $customer_display . "\n";

    $receipt .= str_repeat("-", 32) . "\n";

    // Items
    foreach ($details as $detail) {
        $name = $detail['nama_barang'];
        $qty = $detail['jumlah'];
        $unit = $detail['satuan'];
        $price = $detail['harga_satuan'];
        $subtotal = $detail['subtotal'];

        // Product name (wrap if too long)
        $receipt .= wordwrap($name, 32, "\n") . "\n";

        // Quantity, price, subtotal
        $qty_line = $qty . " " . $unit . " x " . formatCurrency($price);
        $subtotal_line = formatCurrency($subtotal);
        $receipt .= justifyText($qty_line, $subtotal_line, 32) . "\n";
    }

    $receipt .= str_repeat("-", 32) . "\n";

    // Totals
    $receipt .= justifyText("Subtotal:", formatCurrency($transaction['subtotal']), 32) . "\n";
    $receipt .= str_repeat("=", 32) . "\n";
    $receipt .= justifyText("TOTAL:", formatCurrency($transaction['total']), 32) . "\n";
    $receipt .= str_repeat("=", 32) . "\n";

    // Footer
    $receipt .= "\n";
    $receipt .= centerText("Terima kasih!", 32) . "\n";
    $receipt .= centerText("Barang yang sudah dibeli", 32) . "\n";
    $receipt .= centerText("tidak dapat ditukar", 32) . "\n";
    $receipt .= "\n";
    $receipt .= centerText(date('d/m/Y H:i:s'), 32) . "\n";

    // Cut paper command for thermal printer (ESC/POS)
    $receipt .= "\n\n\x1D\x56\x41\x10"; // GS V A (partial cut)

    return $receipt;
}

/**
 * Center text within specified width
 */
function centerText($text, $width) {
    $padding = ($width - strlen($text)) / 2;
    return str_repeat(" ", floor($padding)) . $text . str_repeat(" ", ceil($padding));
}

/**
 * Justify text (left and right alignment in same line)
 */
function justifyText($left, $right, $width) {
    $spaces = $width - strlen($left) - strlen($right);
    return $left . str_repeat(" ", max(1, $spaces)) . $right;
}

/**
 * Generate receipt HTML for display/browser printing
 */
function generateReceiptHTML($transaction, $details, $store_info = []) {
    $store_name = $store_info['name'] ?? APP_NAME;
    $store_address = $store_info['address'] ?? '';
    $store_phone = $store_info['phone'] ?? '';

    // Customer display logic
    $customer_display = 'Pelanggan Umum';
    if (!empty($transaction['nama_pelanggan'])) {
        if ($transaction['customer_id']) {
            $customer_display = $transaction['nama_pelanggan'];
        } else {
            $customer_display = 'Guest: ' . $transaction['nama_pelanggan'];
        }
    }

    ob_start();
    ?>
    <!DOCTYPE html>
    <html lang="id">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Struk - <?= htmlspecialchars($transaction['kode_transaksi']) ?></title>
        <style>
            @media print {
                body { font-size: 10px; margin: 0; padding: 0; }
                .no-print { display: none !important; }
                .receipt { width: 58mm; margin: 0; }
                @page { margin: 0; }
            }

            body {
                font-family: 'Courier New', monospace;
                font-size: 11px;
                margin: 0;
                padding: 10px;
                background: #f5f5f5;
            }

            .receipt {
                width: 58mm;
                margin: 0 auto;
                background: white;
                padding: 8px;
                border: 1px solid #ddd;
                box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            }

            .header {
                text-align: center;
                margin-bottom: 8px;
                border-bottom: 1px dashed #333;
                padding-bottom: 5px;
            }

            .store-name {
                font-weight: bold;
                font-size: 13px;
                margin-bottom: 2px;
            }

            .store-info {
                font-size: 9px;
                color: #666;
                margin-bottom: 1px;
            }

            .trans-info {
                margin-bottom: 8px;
                border-bottom: 1px dashed #333;
                padding-bottom: 5px;
                font-size: 9px;
            }

            .trans-info div {
                display: flex;
                justify-content: space-between;
                margin-bottom: 1px;
            }

            .items {
                margin-bottom: 8px;
            }

            .item {
                margin-bottom: 5px;
                border-bottom: 1px dotted #ccc;
                padding-bottom: 3px;
            }

            .item-name {
                font-weight: bold;
                font-size: 10px;
                margin-bottom: 1px;
                word-wrap: break-word;
            }

            .item-details {
                display: flex;
                justify-content: space-between;
                font-size: 9px;
            }

            .totals {
                border-top: 1px dashed #333;
                padding-top: 5px;
                margin-bottom: 8px;
            }

            .totals div {
                display: flex;
                justify-content: space-between;
                margin-bottom: 2px;
                font-size: 9px;
            }

            .total-final {
                font-weight: bold;
                font-size: 11px;
                border-top: 1px solid #333;
                padding-top: 3px;
                margin-top: 3px;
            }

            .footer {
                text-align: center;
                font-size: 8px;
                color: #666;
                border-top: 1px dashed #333;
                padding-top: 5px;
                margin-top: 5px;
            }

            .controls {
                text-align: center;
                margin: 15px 0;
                padding: 10px;
            }

            .btn {
                background: #007bff;
                color: white;
                border: none;
                padding: 8px 16px;
                margin: 0 5px;
                cursor: pointer;
                border-radius: 4px;
                font-size: 12px;
            }

            .btn:hover { background: #0056b3; }
            .btn-secondary { background: #6c757d; }
            .btn-secondary:hover { background: #545b62; }
            .btn-success { background: #28a745; }
            .btn-success:hover { background: #1e7e34; }
        </style>
    </head>
    <body>
        <div class="receipt">
            <div class="header">
                <div class="store-name"><?= htmlspecialchars($store_name) ?></div>
                <?php if ($store_address): ?>
                <div class="store-info"><?= htmlspecialchars($store_address) ?></div>
                <?php endif; ?>
                <?php if ($store_phone): ?>
                <div class="store-info">Telp: <?= htmlspecialchars($store_phone) ?></div>
                <?php endif; ?>
            </div>

            <div class="trans-info">
                <div><span>Kode:</span><span><?= htmlspecialchars($transaction['kode_transaksi']) ?></span></div>
                <div><span>Tanggal:</span><span><?= formatDateTime($transaction['tanggal_transaksi']) ?></span></div>
                <div><span>Kasir:</span><span><?= htmlspecialchars($transaction['kasir_nama']) ?></span></div>
                <div><span>Pelanggan:</span><span><?= htmlspecialchars($customer_display) ?></span></div>
            </div>

            <div class="items">
                <?php foreach ($details as $detail): ?>
                <div class="item">
                    <div class="item-name"><?= htmlspecialchars($detail['nama_barang']) ?></div>
                    <div class="item-details">
                        <span><?= $detail['jumlah'] ?> <?= htmlspecialchars($detail['satuan']) ?> × <?= formatCurrency($detail['harga_satuan']) ?></span>
                        <span><?= formatCurrency($detail['subtotal']) ?></span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <div class="totals">
                <div><span>Subtotal:</span><span><?= formatCurrency($transaction['subtotal']) ?></span></div>
                <div class="total-final"><span>TOTAL:</span><span><?= formatCurrency($transaction['total']) ?></span></div>
            </div>

            <div class="footer">
                <div>Terima kasih atas kunjungan Anda!</div>
                <div>Barang yang sudah dibeli tidak dapat ditukar</div>
                <div style="margin-top: 5px;"><?= formatDateTime(date('Y-m-d H:i:s')) ?></div>
            </div>
        </div>

        <div class="controls no-print">
            <button onclick="window.print()" class="btn">🖨️ Cetak</button>
            <button onclick="downloadTextReceipt()" class="btn btn-success">💾 Download TXT</button>
            <button onclick="window.close()" class="btn btn-secondary">❌ Tutup</button>
        </div>

        <script>
            function downloadTextReceipt() {
                const receiptText = `<?= addslashes(generateReceiptText($transaction, $details, $store_info)) ?>`;
                const blob = new Blob([receiptText], { type: 'text/plain' });
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = 'struk_<?= $transaction['kode_transaksi'] ?>.txt';
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                window.URL.revokeObjectURL(url);
            }

            // Auto-print option (uncomment if needed)
            // window.onload = function() { window.print(); };
        </script>
    </body>
    </html>
    <?php
    return ob_get_clean();
}

/**
 * Format currency for receipt (shorter format)
 */
function formatReceiptCurrency($amount) {
    return 'Rp ' . number_format($amount, 0, ',', '.');
}

/**
 * Generate QR Code data for receipt (optional)
 */
function generateReceiptQRData($transaction) {
    return json_encode([
        'transaction_id' => $transaction['id'],
        'code' => $transaction['kode_transaksi'],
        'total' => $transaction['total'],
        'date' => $transaction['tanggal_transaksi']
    ]);
}
?>