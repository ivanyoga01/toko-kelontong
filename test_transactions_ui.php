<?php
/**
 * Test Transactions UI Elements
 * Quick test to verify action buttons visibility and modal functionality
 */

require_once 'includes/functions.php';

echo "<!DOCTYPE html>";
echo "<html>";
echo "<head>";
echo "<title>Test Transactions UI</title>";
echo "<link href='" . ASSETS_URL . "Adminto_v4.0.0/Vertical/dist/assets/css/bootstrap.min.css' rel='stylesheet'>";
echo "<link href='" . ASSETS_URL . "Adminto_v4.0.0/Vertical/dist/assets/css/icons.min.css' rel='stylesheet'>";
echo "</head>";
echo "<body class='p-4'>";

echo "<h2>Transactions UI Test</h2>";

echo "<h3>1. Action Buttons Test</h3>";
echo "<div class='btn-group' role='group'>";
echo "<button type='button' class='btn btn-info btn-sm' title='Lihat Detail'>";
echo "<i class='mdi mdi-eye'></i>";
echo "</button>";
echo "<button type='button' class='btn btn-primary btn-sm' title='Cetak Struk'>";
echo "<i class='mdi mdi-printer'></i>";
echo "</button>";
echo "<button type='button' class='btn btn-warning btn-sm' title='Batalkan Transaksi'>";
echo "<i class='mdi mdi-cancel'></i>";
echo "</button>";
echo "</div>";

echo "<p class='mt-3'>✅ Action buttons should be clearly visible with proper colors</p>";

echo "<h3>2. Modal Test</h3>";
echo "<button type='button' class='btn btn-success' data-toggle='modal' data-target='#testModal'>Open Test Modal</button>";

echo "<div class='modal fade' id='testModal' tabindex='-1' aria-labelledby='testModalLabel' aria-hidden='true'>";
echo "<div class='modal-dialog'>";
echo "<div class='modal-content'>";
echo "<div class='modal-header'>";
echo "<h5 class='modal-title' id='testModalLabel'>Test Modal</h5>";
echo "<button type='button' class='close' data-dismiss='modal' aria-label='Close'>";
echo "<span aria-hidden='true'>&times;</span>";
echo "</button>";
echo "</div>";
echo "<div class='modal-body'>";
echo "<p>This modal should open and close properly.</p>";
echo "</div>";
echo "<div class='modal-footer'>";
echo "<button type='button' class='btn btn-secondary' data-dismiss='modal'>Close</button>";
echo "</div>";
echo "</div>";
echo "</div>";
echo "</div>";

echo "<p class='mt-3'>✅ Modal should open when clicking the button and close with the X button</p>";

echo "<h3>3. Bootstrap Version Check</h3>";
echo "<p>Using Bootstrap 4 syntax: <code>data-dismiss</code> instead of <code>data-bs-dismiss</code></p>";
echo "<p>✅ Compatible with Adminto v4.0.0 template</p>";

echo "<script src='" . ASSETS_URL . "Adminto_v4.0.0/Vertical/dist/assets/js/vendor.min.js'></script>";
echo "<script src='" . ASSETS_URL . "Adminto_v4.0.0/Vertical/dist/assets/js/app.min.js'></script>";
echo "</body>";
echo "</html>";
?>