<?php
/**
 * Admin Categories Management - Save
 * Handler untuk menyimpan/update kategori barang
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
    $nama_kategori = sanitizeInput($_POST['nama_kategori'] ?? '');
    $deskripsi = sanitizeInput($_POST['deskripsi'] ?? '');

    // Validation
    if (empty($nama_kategori)) {
        if ($is_ajax) {
            echo json_encode(['success' => false, 'message' => 'Nama kategori harus diisi']);
        } else {
            setFlashMessage('error', 'Nama kategori harus diisi');
            header('Location: index.php');
        }
        exit;
    }

    if (strlen($nama_kategori) > 100) {
        if ($is_ajax) {
            echo json_encode(['success' => false, 'message' => 'Nama kategori maksimal 100 karakter']);
        } else {
            setFlashMessage('error', 'Nama kategori maksimal 100 karakter');
            header('Location: index.php');
        }
        exit;
    }

    if ($id > 0) {
        // Update existing category

        // Check if category exists
        $check_sql = "SELECT id FROM categories WHERE id = ?";
        $existing = fetchOne($check_sql, [$id]);

        if (!$existing) {
            if ($is_ajax) {
                echo json_encode(['success' => false, 'message' => 'Kategori tidak ditemukan']);
            } else {
                setFlashMessage('error', 'Kategori tidak ditemukan');
                header('Location: index.php');
            }
            exit;
        }

        // Check for duplicate name (exclude current record)
        $duplicate_sql = "SELECT id FROM categories WHERE nama_kategori = ? AND id != ?";
        $duplicate = fetchOne($duplicate_sql, [$nama_kategori, $id]);

        if ($duplicate) {
            if ($is_ajax) {
                echo json_encode(['success' => false, 'message' => 'Nama kategori sudah digunakan']);
            } else {
                setFlashMessage('error', 'Nama kategori sudah digunakan');
                header('Location: index.php');
            }
            exit;
        }

        // Update category
        $sql = "UPDATE categories SET nama_kategori = ?, deskripsi = ? WHERE id = ?";
        $params = [$nama_kategori, $deskripsi, $id];

        if (executeQuery($sql, $params)) {
            setFlashMessage('success', 'Kategori berhasil diupdate');
            if ($is_ajax) {
                echo json_encode(['success' => true, 'message' => 'Kategori berhasil diupdate']);
            } else {
                header('Location: index.php');
            }
        } else {
            if ($is_ajax) {
                echo json_encode(['success' => false, 'message' => 'Gagal update kategori']);
            } else {
                setFlashMessage('error', 'Gagal update kategori');
                header('Location: index.php');
            }
        }

    } else {
        // Add new category

        // Check for duplicate name
        $duplicate_sql = "SELECT id FROM categories WHERE nama_kategori = ?";
        $duplicate = fetchOne($duplicate_sql, [$nama_kategori]);

        if ($duplicate) {
            if ($is_ajax) {
                echo json_encode(['success' => false, 'message' => 'Nama kategori sudah digunakan']);
            } else {
                setFlashMessage('error', 'Nama kategori sudah digunakan');
                header('Location: index.php');
            }
            exit;
        }

        // Insert new category
        $sql = "INSERT INTO categories (nama_kategori, deskripsi) VALUES (?, ?)";
        $params = [$nama_kategori, $deskripsi];

        $category_id = insertData($sql, $params);

        if ($category_id) {
            setFlashMessage('success', 'Kategori berhasil ditambahkan');
            if ($is_ajax) {
                echo json_encode(['success' => true, 'message' => 'Kategori berhasil ditambahkan', 'id' => $category_id]);
            } else {
                header('Location: index.php');
            }
        } else {
            if ($is_ajax) {
                echo json_encode(['success' => false, 'message' => 'Gagal menambahkan kategori']);
            } else {
                setFlashMessage('error', 'Gagal menambahkan kategori');
                header('Location: index.php');
            }
        }
    }

} catch (Exception $e) {
    error_log("Category save error: " . $e->getMessage());
    if ($is_ajax) {
        echo json_encode(['success' => false, 'message' => 'Terjadi kesalahan sistem']);
    } else {
        setFlashMessage('error', 'Terjadi kesalahan sistem');
        header('Location: index.php');
    }
}
?>