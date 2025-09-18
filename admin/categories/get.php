<?php
/**
 * Admin Categories Management - Get
 * Handler untuk mengambil data kategori berdasarkan ID
 */

require_once '../../includes/admin_auth.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    $id = intval($_GET['id'] ?? 0);

    if ($id <= 0) {
        echo json_encode(['success' => false, 'message' => 'ID kategori tidak valid']);
        exit;
    }

    $sql = "SELECT * FROM categories WHERE id = ?";
    $category = fetchOne($sql, [$id]);

    if ($category) {
        echo json_encode(['success' => true, 'data' => $category]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Kategori tidak ditemukan']);
    }

} catch (Exception $e) {
    error_log("Category get error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Terjadi kesalahan sistem']);
}
?>