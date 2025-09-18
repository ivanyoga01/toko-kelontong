<?php
/**
 * Check PHP Extensions for Excel Support
 */

echo "<h2>PHP Extensions Status for Excel Support</h2>";

$extensions = [
    'zip' => 'Required for reading/writing Excel files (.xlsx)',
    'xml' => 'Required for XML parsing',
    'xmlreader' => 'Required for reading XML',
    'xmlwriter' => 'Required for writing XML',
    'gd' => 'Optional: For image handling in Excel',
    'fileinfo' => 'Required for file type detection'
];

echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
echo "<tr><th>Extension</th><th>Status</th><th>Description</th></tr>";

foreach ($extensions as $ext => $desc) {
    $loaded = extension_loaded($ext);
    $status = $loaded ? "<span style='color: green;'>✓ Enabled</span>" : "<span style='color: red;'>✗ Disabled</span>";
    echo "<tr><td>$ext</td><td>$status</td><td>$desc</td></tr>";
}

echo "</table>";

if (!extension_loaded('zip')) {
    echo "<div style='background: #ffebee; border: 1px solid #f44336; padding: 10px; margin: 10px 0;'>";
    echo "<h3 style='color: #f44336;'>ZIP Extension Missing</h3>";
    echo "<p>To enable ZIP extension in XAMPP:</p>";
    echo "<ol>";
    echo "<li>Open your XAMPP PHP configuration file (php.ini)</li>";
    echo "<li>Find the line: <code>;extension=zip</code></li>";
    echo "<li>Remove the semicolon to uncomment it: <code>extension=zip</code></li>";
    echo "<li>Restart Apache</li>";
    echo "</ol>";
    echo "<p><strong>PHP.ini location:</strong> " . php_ini_loaded_file() . "</p>";
    echo "</div>";
} else {
    echo "<div style='background: #e8f5e8; border: 1px solid #4caf50; padding: 10px; margin: 10px 0;'>";
    echo "<h3 style='color: #4caf50;'>✓ All Required Extensions Enabled</h3>";
    echo "<p>Your PHP installation supports Excel file operations.</p>";
    echo "</div>";
}

echo "<hr>";
echo "<h3>PHP Configuration</h3>";
echo "<p><strong>PHP Version:</strong> " . PHP_VERSION . "</p>";
echo "<p><strong>PHP.ini File:</strong> " . php_ini_loaded_file() . "</p>";

// Test CSV fallback
echo "<hr>";
echo "<h3>CSV Fallback Test</h3>";
$test_data = [
    ['Name', 'Age', 'City'],
    ['John', '25', 'Jakarta'],
    ['Jane', '30', 'Bandung']
];

echo "<p>CSV Export Test: <a href='?test_csv=1'>Download Test CSV</a></p>";

if (isset($_GET['test_csv'])) {
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="test.csv"');

    // Add BOM for UTF-8
    echo "\xEF\xBB\xBF";

    $output = fopen('php://output', 'w');

    foreach ($test_data as $row) {
        fputcsv($output, $row);
    }

    fclose($output);
    exit;
}
?>