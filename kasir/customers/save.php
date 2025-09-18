<?php
/**
 * Kasir Customer Save
 * Handler untuk menambah pelanggan baru dari area kasir
 */

require_once '../../includes/kasir_auth.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    $nama_pelanggan = sanitizeInput($_POST['nama_pelanggan'] ?? '');
    $no_hp = sanitizeInput($_POST['no_hp'] ?? '');
    $alamat = sanitizeInput($_POST['alamat'] ?? '');

    // Validation
    if (empty($nama_pelanggan)) {
        echo json_encode(['success' => false, 'message' => 'Nama pelanggan harus diisi']);
        exit;
    }

    if (strlen($nama_pelanggan) > 100) {
        echo json_encode(['success' => false, 'message' => 'Nama pelanggan maksimal 100 karakter']);
        exit;
    }

    if (!empty($no_hp) && !isValidPhone($no_hp)) {
        echo json_encode(['success' => false, 'message' => 'Format nomor HP tidak valid (10-15 digit angka)']);
        exit;
    }

    // Check for duplicate phone number
    if (!empty($no_hp)) {
        $duplicate_sql = "SELECT id FROM customers WHERE no_hp = ?";
        $duplicate = fetchOne($duplicate_sql, [$no_hp]);

        if ($duplicate) {
            echo json_encode(['success' => false, 'message' => 'Nomor HP sudah digunakan pelanggan lain']);
            exit;
        }
    }

    // Insert new customer
    $sql = "INSERT INTO customers (nama_pelanggan, no_hp, alamat) VALUES (?, ?, ?)";
    $params = [$nama_pelanggan, $no_hp ?: null, $alamat ?: null];

    $customer_id = insertData($sql, $params);

    if ($customer_id) {
        echo json_encode([
            'success' => true,
            'message' => 'Pelanggan berhasil ditambahkan',
            'customer_id' => $customer_id,
            'customer_name' => $nama_pelanggan
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Gagal menambahkan pelanggan']);
    }

} catch (Exception $e) {
    error_log("Kasir customer save error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Terjadi kesalahan sistem']);
}
?>