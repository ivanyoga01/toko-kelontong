<?php
/**
 * Test Template Download
 */

require_once 'includes/functions.php';
require_once 'vendor/autoload.php';
require_once 'libraries/ExcelHelper.php';

// Test template download
$headers = [
    'kode_barang',
    'barcode',
    'nama_barang',
    'kategori',
    'harga_beli',
    'harga_jual',
    'stok',
    'satuan',
    'deskripsi'
];

$sample_data = [
    ['TEST001', '1234567890123', 'Test Product', 'Test Category', '5000', '7000', '10', 'pcs', 'Test description']
];

$title = 'Template Import Data Barang - Test';
ExcelHelper::generateTemplate($headers, $sample_data, 'test_template.xlsx', $title);
?>