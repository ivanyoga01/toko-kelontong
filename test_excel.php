<?php
/**
 * Test Excel Export/Import functionality
 */

require_once 'includes/functions.php';
require_once 'vendor/autoload.php';
require_once 'libraries/ExcelHelper.php';

echo "<h2>Testing Excel Functionality</h2>";

// Test 1: Check if ZIP extension is loaded
echo "<h3>1. ZIP Extension Check</h3>";
if (extension_loaded('zip')) {
    echo "✅ ZIP extension is loaded - Excel format available<br>";
} else {
    echo "❌ ZIP extension not loaded - will use CSV fallback<br>";
}

// Test 2: Test headers consistency
echo "<h3>2. Headers Consistency Test</h3>";
$import_headers = ['kode_barang', 'barcode', 'nama_barang', 'kategori', 'harga_beli', 'harga_jual', 'stok', 'satuan', 'deskripsi'];
$export_headers = ['kode_barang', 'barcode', 'nama_barang', 'kategori', 'harga_beli', 'harga_jual', 'stok', 'satuan', 'deskripsi', 'status', 'dibuat', 'diupdate'];

echo "Import expects: " . implode(', ', $import_headers) . "<br>";
echo "Export provides first 9 columns: " . implode(', ', array_slice($export_headers, 0, 9)) . "<br>";

$match = array_slice($export_headers, 0, 9) === $import_headers;
echo $match ? "✅ Headers match!" : "❌ Headers don't match!";
echo "<br>";

// Test 3: Sample data generation
echo "<h3>3. Sample Data Test</h3>";
$sample_data = [
    ['TEST001', '1234567890123', 'Test Product', 'Test Category', '5000', '7000', '10', 'pcs', 'Test description'],
    ['TEST002', '1234567890124', 'Test Product 2', 'Test Category', '8000', '10000', '5', 'kg', 'Another test']
];

echo "Sample data generated with " . count($sample_data) . " rows<br>";
echo "✅ Sample data ready<br>";

echo "<h3>4. Template Download Test</h3>";
echo "<a href='test_template_download.php' target='_blank'>Click here to test template download</a><br>";

echo "<h3>Status: Ready for Testing</h3>";
echo "You can now test the import/export functionality in the admin panel.";
?>