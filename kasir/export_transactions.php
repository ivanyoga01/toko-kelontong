<?php
/**
 * Export Transactions - Kasir Personal
 * Export transactions for current kasir only
 */

require_once '../includes/kasir_auth.php';
require_once '../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

// Get filter parameters
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

// Get transactions data
$sql = "SELECT t.*,
        CASE
            WHEN t.customer_id IS NOT NULL THEN c.nama_pelanggan
            WHEN t.customer_name IS NOT NULL THEN CONCAT('Guest: ', t.customer_name)
            ELSE 'Pelanggan Umum'
        END as nama_pelanggan,
        (SELECT COUNT(*) FROM transaction_details WHERE transaction_id = t.id) as jumlah_item,
        (SELECT SUM(jumlah) FROM transaction_details WHERE transaction_id = t.id) as total_qty
        FROM transactions t
        LEFT JOIN customers c ON t.customer_id = c.id
        $where_clause
        ORDER BY t.tanggal_transaksi DESC";

$transactions = fetchAll($sql, $params);

if (empty($transactions)) {
    die('Tidak ada data transaksi untuk diekspor');
}

// Create spreadsheet
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Set document properties
$spreadsheet->getProperties()
    ->setCreator($current_user['nama_lengkap'])
    ->setTitle('Riwayat Transaksi - ' . $current_user['nama_lengkap'])
    ->setSubject('Export Data Transaksi Kasir')
    ->setDescription('Data transaksi kasir ' . $current_user['nama_lengkap']);

// Header information
$sheet->setCellValue('A1', 'LAPORAN RIWAYAT TRANSAKSI');
$sheet->setCellValue('A2', 'Kasir: ' . $current_user['nama_lengkap']);
$sheet->setCellValue('A3', 'Username: ' . $current_user['username']);
$sheet->setCellValue('A4', 'Tanggal Export: ' . date('d/m/Y H:i:s'));

if (!empty($date_from) || !empty($date_to)) {
    $periode = '';
    if (!empty($date_from)) $periode .= 'Dari: ' . formatDate($date_from);
    if (!empty($date_to)) $periode .= (!empty($periode) ? ' | ' : '') . 'Sampai: ' . formatDate($date_to);
    $sheet->setCellValue('A5', 'Periode: ' . $periode);
    $headerRow = 7;
} else {
    $headerRow = 6;
}

// Merge cells for title
$sheet->mergeCells('A1:H1');
$sheet->mergeCells('A2:H2');
$sheet->mergeCells('A3:H3');
$sheet->mergeCells('A4:H4');
if (!empty($date_from) || !empty($date_to)) {
    $sheet->mergeCells('A5:H5');
}

// Style header
$headerStyle = [
    'font' => ['bold' => true, 'size' => 14],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
    'fill' => [
        'fillType' => Fill::FILL_SOLID,
        'color' => ['rgb' => 'E3F2FD']
    ]
];
$sheet->getStyle('A1:H1')->applyFromArray($headerStyle);

$infoStyle = [
    'font' => ['bold' => true, 'size' => 10],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT]
];
$sheet->getStyle('A2:A5')->applyFromArray($infoStyle);

// Table headers
$headers = [
    'A' => 'No',
    'B' => 'Tanggal',
    'C' => 'Kode Transaksi',
    'D' => 'Pelanggan',
    'E' => 'Jumlah Item',
    'F' => 'Total Qty',
    'G' => 'Total (Rp)',
    'H' => 'Status'
];

foreach ($headers as $col => $header) {
    $sheet->setCellValue($col . $headerRow, $header);
}

// Style table headers
$tableHeaderStyle = [
    'font' => ['bold' => true],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
    'fill' => [
        'fillType' => Fill::FILL_SOLID,
        'color' => ['rgb' => 'BBDEFB']
    ],
    'borders' => [
        'allBorders' => [
            'borderStyle' => Border::BORDER_THIN
        ]
    ]
];
$sheet->getStyle('A' . $headerRow . ':H' . $headerRow)->applyFromArray($tableHeaderStyle);

// Data rows
$row = $headerRow + 1;
$totalAmount = 0;
foreach ($transactions as $index => $transaction) {
    $sheet->setCellValue('A' . $row, $index + 1);
    $sheet->setCellValue('B' . $row, formatDateTime($transaction['tanggal_transaksi']));
    $sheet->setCellValue('C' . $row, $transaction['kode_transaksi']);
    $sheet->setCellValue('D' . $row, $transaction['nama_pelanggan']);
    $sheet->setCellValue('E' . $row, $transaction['jumlah_item']);
    $sheet->setCellValue('F' . $row, $transaction['total_qty']);
    $sheet->setCellValue('G' . $row, $transaction['total']);
    $sheet->setCellValue('H' . $row, ucfirst($transaction['status']));

    $totalAmount += $transaction['total'];
    $row++;
}

// Summary row
$summaryRow = $row + 1;
$sheet->setCellValue('A' . $summaryRow, 'TOTAL');
$sheet->setCellValue('E' . $summaryRow, count($transactions) . ' transaksi');
$sheet->setCellValue('G' . $summaryRow, $totalAmount);

$sheet->mergeCells('A' . $summaryRow . ':D' . $summaryRow);
$sheet->mergeCells('E' . $summaryRow . ':F' . $summaryRow);

// Style summary row
$summaryStyle = [
    'font' => ['bold' => true],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
    'fill' => [
        'fillType' => Fill::FILL_SOLID,
        'color' => ['rgb' => 'E8F5E8']
    ],
    'borders' => [
        'allBorders' => [
            'borderStyle' => Border::BORDER_THICK
        ]
    ]
];
$sheet->getStyle('A' . $summaryRow . ':H' . $summaryRow)->applyFromArray($summaryStyle);

// Style data rows
$dataStyle = [
    'borders' => [
        'allBorders' => [
            'borderStyle' => Border::BORDER_THIN
        ]
    ]
];
$sheet->getStyle('A' . ($headerRow + 1) . ':H' . ($row - 1))->applyFromArray($dataStyle);

// Alignment
$sheet->getStyle('A' . ($headerRow + 1) . ':A' . ($row - 1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
$sheet->getStyle('E' . ($headerRow + 1) . ':F' . ($row - 1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
$sheet->getStyle('G' . ($headerRow + 1) . ':G' . $summaryRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
$sheet->getStyle('H' . ($headerRow + 1) . ':H' . ($row - 1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

// Number format for currency
$sheet->getStyle('G' . ($headerRow + 1) . ':G' . $summaryRow)->getNumberFormat()->setFormatCode('#,##0');

// Auto-size columns
foreach (range('A', 'H') as $column) {
    $sheet->getColumnDimension($column)->setAutoSize(true);
}

// Output
$filename = 'Riwayat_Transaksi_' . $current_user['username'] . '_' . date('Y-m-d_H-i-s') . '.xlsx';

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="' . $filename . '"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;