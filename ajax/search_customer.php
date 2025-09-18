<?php
/**
 * Search Customer AJAX
 * API untuk mencari pelanggan berdasarkan nama atau no HP
 */

require_once '../includes/functions.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isLoggedIn()) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$query = sanitizeInput($_GET['q'] ?? '');

if (strlen($query) < 2) {
    echo json_encode([]);
    exit;
}

// Search customers
$sql = "SELECT id, nama_pelanggan, no_hp, alamat
        FROM customers
        WHERE nama_pelanggan LIKE ? OR no_hp LIKE ?
        ORDER BY nama_pelanggan ASC
        LIMIT 10";

$searchTerm = "%$query%";
$customers = fetchAll($sql, [$searchTerm, $searchTerm]);

echo json_encode($customers ?: []);
?>