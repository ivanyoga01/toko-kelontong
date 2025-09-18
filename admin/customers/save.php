<?php
/**
 * Admin Customers Management - Save
 * Handler untuk menyimpan/update pelanggan
 */

require_once '../../includes/admin_auth.php';

// Check if this is an AJAX request
$is_ajax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

if ($is_ajax) {
    header('Content-Type: application/json');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    if ($is_ajax) {
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    } else {
        setFlashMessage('error', 'Method not allowed');
        header('Location: index.php');
    }
    exit;
}

try {
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    $nama_pelanggan = sanitizeInput($_POST['nama_pelanggan'] ?? '');
    $no_hp = sanitizeInput($_POST['no_hp'] ?? '');
    $alamat = sanitizeInput($_POST['alamat'] ?? '');

    // Validation
    if (empty($nama_pelanggan)) {
        if ($is_ajax) {
            echo json_encode(['success' => false, 'message' => 'Nama pelanggan harus diisi']);
        } else {
            setFlashMessage('error', 'Nama pelanggan harus diisi');
            header('Location: index.php');
        }
        exit;
    }

    if (strlen($nama_pelanggan) > 100) {
        if ($is_ajax) {
            echo json_encode(['success' => false, 'message' => 'Nama pelanggan maksimal 100 karakter']);
        } else {
            setFlashMessage('error', 'Nama pelanggan maksimal 100 karakter');
            header('Location: index.php');
        }
        exit;
    }

    if (!empty($no_hp)) {
        if (!isValidPhone($no_hp)) {
            if ($is_ajax) {
                echo json_encode(['success' => false, 'message' => 'Format nomor HP tidak valid (10-15 digit angka)']);
            } else {
                setFlashMessage('error', 'Format nomor HP tidak valid (10-15 digit angka)');
                header('Location: index.php');
            }
            exit;
        }
    }

    if ($id > 0) {
        // Update existing customer

        // Check if customer exists
        $check_sql = "SELECT id FROM customers WHERE id = ?";
        $existing = fetchOne($check_sql, [$id]);

        if (!$existing) {
            if ($is_ajax) {
                echo json_encode(['success' => false, 'message' => 'Pelanggan tidak ditemukan']);
            } else {
                setFlashMessage('error', 'Pelanggan tidak ditemukan');
                header('Location: index.php');
            }
            exit;
        }

        // Check for duplicate phone number (exclude current record)
        if (!empty($no_hp)) {
            $duplicate_sql = "SELECT id FROM customers WHERE no_hp = ? AND id != ?";
            $duplicate = fetchOne($duplicate_sql, [$no_hp, $id]);

            if ($duplicate) {
                if ($is_ajax) {
                    echo json_encode(['success' => false, 'message' => 'Nomor HP sudah digunakan pelanggan lain']);
                } else {
                    setFlashMessage('error', 'Nomor HP sudah digunakan pelanggan lain');
                    header('Location: index.php');
                }
                exit;
            }
        }

        // Update customer
        $sql = "UPDATE customers SET nama_pelanggan = ?, no_hp = ?, alamat = ? WHERE id = ?";
        $params = [$nama_pelanggan, $no_hp ?: null, $alamat ?: null, $id];

        if (executeQuery($sql, $params)) {
            setFlashMessage('success', 'Data pelanggan berhasil diupdate');
            if ($is_ajax) {
                echo json_encode(['success' => true, 'message' => 'Data pelanggan berhasil diupdate']);
            } else {
                header('Location: index.php');
            }
        } else {
            if ($is_ajax) {
                echo json_encode(['success' => false, 'message' => 'Gagal update data pelanggan']);
            } else {
                setFlashMessage('error', 'Gagal update data pelanggan');
                header('Location: index.php');
            }
        }

    } else {
        // Add new customer

        // Check for duplicate phone number
        if (!empty($no_hp)) {
            $duplicate_sql = "SELECT id FROM customers WHERE no_hp = ?";
            $duplicate = fetchOne($duplicate_sql, [$no_hp]);

            if ($duplicate) {
                if ($is_ajax) {
                    echo json_encode(['success' => false, 'message' => 'Nomor HP sudah digunakan pelanggan lain']);
                } else {
                    setFlashMessage('error', 'Nomor HP sudah digunakan pelanggan lain');
                    header('Location: index.php');
                }
                exit;
            }
        }

        // Insert new customer
        $sql = "INSERT INTO customers (nama_pelanggan, no_hp, alamat) VALUES (?, ?, ?)";
        $params = [$nama_pelanggan, $no_hp ?: null, $alamat ?: null];

        $customer_id = insertData($sql, $params);

        if ($customer_id) {
            setFlashMessage('success', 'Pelanggan berhasil ditambahkan');
            if ($is_ajax) {
                echo json_encode(['success' => true, 'message' => 'Pelanggan berhasil ditambahkan', 'id' => $customer_id]);
            } else {
                header('Location: index.php');
            }
        } else {
            if ($is_ajax) {
                echo json_encode(['success' => false, 'message' => 'Gagal menambahkan pelanggan']);
            } else {
                setFlashMessage('error', 'Gagal menambahkan pelanggan');
                header('Location: index.php');
            }
        }
    }

} catch (Exception $e) {
    error_log("Customer save error: " . $e->getMessage());
    if ($is_ajax) {
        echo json_encode(['success' => false, 'message' => 'Terjadi kesalahan sistem']);
    } else {
        setFlashMessage('error', 'Terjadi kesalahan sistem');
        header('Location: index.php');
    }
}
?>