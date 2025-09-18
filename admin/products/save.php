<?php
/**
 * Admin Products Management - Save
 * Handler untuk menyimpan/update produk/barang
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
    $kode_barang = sanitizeInput($_POST['kode_barang'] ?? '');
    $barcode = sanitizeInput($_POST['barcode'] ?? '');
    $nama_barang = sanitizeInput($_POST['nama_barang'] ?? '');
    $category_id = !empty($_POST['category_id']) ? intval($_POST['category_id']) : null;
    $harga_beli = floatval($_POST['harga_beli'] ?? 0);
    $harga_jual = floatval($_POST['harga_jual'] ?? 0);
    $stok = intval($_POST['stok'] ?? 0);
    $satuan = sanitizeInput($_POST['satuan'] ?? '');
    $deskripsi = sanitizeInput($_POST['deskripsi'] ?? '');
    $status = sanitizeInput($_POST['status'] ?? 'aktif');

    // Validation
    if (empty($kode_barang)) {
        if ($is_ajax) {
            echo json_encode(['success' => false, 'message' => 'Kode barang harus diisi']);
        } else {
            setFlashMessage('error', 'Kode barang harus diisi');
            header('Location: index.php');
        }
        exit;
    }

    if (empty($nama_barang)) {
        if ($is_ajax) {
            echo json_encode(['success' => false, 'message' => 'Nama barang harus diisi']);
        } else {
            setFlashMessage('error', 'Nama barang harus diisi');
            header('Location: index.php');
        }
        exit;
    }

    if (empty($satuan)) {
        if ($is_ajax) {
            echo json_encode(['success' => false, 'message' => 'Satuan harus diisi']);
        } else {
            setFlashMessage('error', 'Satuan harus diisi');
            header('Location: index.php');
        }
        exit;
    }

    if ($harga_beli < 0) {
        if ($is_ajax) {
            echo json_encode(['success' => false, 'message' => 'Harga beli tidak boleh negatif']);
        } else {
            setFlashMessage('error', 'Harga beli tidak boleh negatif');
            header('Location: index.php');
        }
        exit;
    }

    if ($harga_jual < 0) {
        if ($is_ajax) {
            echo json_encode(['success' => false, 'message' => 'Harga jual tidak boleh negatif']);
        } else {
            setFlashMessage('error', 'Harga jual tidak boleh negatif');
            header('Location: index.php');
        }
        exit;
    }

    if ($stok < 0) {
        if ($is_ajax) {
            echo json_encode(['success' => false, 'message' => 'Stok tidak boleh negatif']);
        } else {
            setFlashMessage('error', 'Stok tidak boleh negatif');
            header('Location: index.php');
        }
        exit;
    }

    if (!in_array($status, ['aktif', 'nonaktif'])) {
        if ($is_ajax) {
            echo json_encode(['success' => false, 'message' => 'Status tidak valid']);
        } else {
            setFlashMessage('error', 'Status tidak valid');
            header('Location: index.php');
        }
        exit;
    }

    // Validate barcode if provided
    if (!empty($barcode)) {
        // Basic barcode validation (numeric and length)
        if (!preg_match('/^\d+$/', $barcode)) {
            if ($is_ajax) {
                echo json_encode(['success' => false, 'message' => 'Barcode harus berupa angka']);
            } else {
                setFlashMessage('error', 'Barcode harus berupa angka');
                header('Location: index.php');
            }
            exit;
        }

        $validLengths = [8, 12, 13, 14]; // Common barcode lengths
        if (!in_array(strlen($barcode), $validLengths)) {
            if ($is_ajax) {
                echo json_encode(['success' => false, 'message' => 'Panjang barcode tidak valid (8, 12, 13, atau 14 digit)']);
            } else {
                setFlashMessage('error', 'Panjang barcode tidak valid (8, 12, 13, atau 14 digit)');
                header('Location: index.php');
            }
            exit;
        }
    }

    // Handle image upload
    $gambar_filename = null;
    if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] === UPLOAD_ERR_OK) {
        $upload_result = uploadFile($_FILES['gambar'], UPLOADS_PATH . 'products', ALLOWED_IMAGE_TYPES);

        if (!$upload_result['success']) {
            if ($is_ajax) {
                echo json_encode(['success' => false, 'message' => 'Upload gambar gagal: ' . $upload_result['message']]);
            } else {
                setFlashMessage('error', 'Upload gambar gagal: ' . $upload_result['message']);
                header('Location: index.php');
            }
            exit;
        }

        $gambar_filename = $upload_result['filename'];
    }

    if ($id > 0) {
        // Update existing product

        // Check if product exists
        $check_sql = "SELECT id, gambar FROM products WHERE id = ?";
        $existing = fetchOne($check_sql, [$id]);

        if (!$existing) {
            if ($is_ajax) {
                echo json_encode(['success' => false, 'message' => 'Produk tidak ditemukan']);
            } else {
                setFlashMessage('error', 'Produk tidak ditemukan');
                header('Location: index.php');
            }
            exit;
        }

        // Check for duplicate code (exclude current record)
        $duplicate_sql = "SELECT id FROM products WHERE kode_barang = ? AND id != ?";
        $duplicate = fetchOne($duplicate_sql, [$kode_barang, $id]);

        if ($duplicate) {
            // Delete uploaded image if any
            if ($gambar_filename) {
                deleteFile(UPLOADS_PATH . 'products/' . $gambar_filename);
            }
            if ($is_ajax) {
                echo json_encode(['success' => false, 'message' => 'Kode barang sudah digunakan']);
            } else {
                setFlashMessage('error', 'Kode barang sudah digunakan');
                header('Location: index.php');
            }
            exit;
        }

        // Check for duplicate barcode if provided (exclude current record)
        if (!empty($barcode)) {
            $barcode_duplicate_sql = "SELECT id FROM products WHERE barcode = ? AND id != ?";
            $barcode_duplicate = fetchOne($barcode_duplicate_sql, [$barcode, $id]);

            if ($barcode_duplicate) {
                // Delete uploaded image if any
                if ($gambar_filename) {
                    deleteFile(UPLOADS_PATH . 'products/' . $gambar_filename);
                }
                if ($is_ajax) {
                    echo json_encode(['success' => false, 'message' => 'Barcode sudah digunakan']);
                } else {
                    setFlashMessage('error', 'Barcode sudah digunakan');
                    header('Location: index.php');
                }
                exit;
            }
        }

        // Prepare update query
        if ($gambar_filename) {
            // Update with new image
            $sql = "UPDATE products SET
                    kode_barang = ?, barcode = ?, nama_barang = ?, category_id = ?,
                    harga_beli = ?, harga_jual = ?, stok = ?, satuan = ?,
                    gambar = ?, deskripsi = ?, status = ?
                    WHERE id = ?";
            $params = [$kode_barang, $barcode ?: null, $nama_barang, $category_id, $harga_beli, $harga_jual,
                      $stok, $satuan, $gambar_filename, $deskripsi, $status, $id];

            // Delete old image
            if (!empty($existing['gambar'])) {
                deleteFile(UPLOADS_PATH . 'products/' . $existing['gambar']);
            }
        } else {
            // Update without changing image
            $sql = "UPDATE products SET
                    kode_barang = ?, barcode = ?, nama_barang = ?, category_id = ?,
                    harga_beli = ?, harga_jual = ?, stok = ?, satuan = ?,
                    deskripsi = ?, status = ?
                    WHERE id = ?";
            $params = [$kode_barang, $barcode ?: null, $nama_barang, $category_id, $harga_beli, $harga_jual,
                      $stok, $satuan, $deskripsi, $status, $id];
        }

        if (executeQuery($sql, $params)) {
            setFlashMessage('success', 'Produk berhasil diupdate');
            if ($is_ajax) {
                echo json_encode(['success' => true, 'message' => 'Produk berhasil diupdate']);
            } else {
                header('Location: index.php');
            }
        } else {
            // Delete uploaded image if update failed
            if ($gambar_filename) {
                deleteFile(UPLOADS_PATH . 'products/' . $gambar_filename);
            }
            if ($is_ajax) {
                echo json_encode(['success' => false, 'message' => 'Gagal update produk']);
            } else {
                setFlashMessage('error', 'Gagal update produk');
                header('Location: index.php');
            }
        }

    } else {
        // Add new product

        // Check for duplicate code
        $duplicate_sql = "SELECT id FROM products WHERE kode_barang = ?";
        $duplicate = fetchOne($duplicate_sql, [$kode_barang]);

        if ($duplicate) {
            // Delete uploaded image if any
            if ($gambar_filename) {
                deleteFile(UPLOADS_PATH . 'products/' . $gambar_filename);
            }
            if ($is_ajax) {
                echo json_encode(['success' => false, 'message' => 'Kode barang sudah digunakan']);
            } else {
                setFlashMessage('error', 'Kode barang sudah digunakan');
                header('Location: index.php');
            }
            exit;
        }

        // Check for duplicate barcode if provided
        if (!empty($barcode)) {
            $barcode_duplicate_sql = "SELECT id FROM products WHERE barcode = ?";
            $barcode_duplicate = fetchOne($barcode_duplicate_sql, [$barcode]);

            if ($barcode_duplicate) {
                // Delete uploaded image if any
                if ($gambar_filename) {
                    deleteFile(UPLOADS_PATH . 'products/' . $gambar_filename);
                }
                if ($is_ajax) {
                    echo json_encode(['success' => false, 'message' => 'Barcode sudah digunakan']);
                } else {
                    setFlashMessage('error', 'Barcode sudah digunakan');
                    header('Location: index.php');
                }
                exit;
            }
        }

        // Insert new product
        $sql = "INSERT INTO products (kode_barang, barcode, nama_barang, category_id, harga_beli, harga_jual,
                stok, satuan, gambar, deskripsi, status)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $params = [$kode_barang, $barcode ?: null, $nama_barang, $category_id, $harga_beli, $harga_jual,
                  $stok, $satuan, $gambar_filename, $deskripsi, $status];

        $product_id = insertData($sql, $params);

        if ($product_id) {
            // Log stock movement for initial stock
            if ($stok > 0) {
                $stock_sql = "INSERT INTO stock_movements (product_id, tipe, jumlah, keterangan)
                             VALUES (?, 'masuk', ?, 'Stok awal')";
                executeQuery($stock_sql, [$product_id, $stok]);
            }

            setFlashMessage('success', 'Produk berhasil ditambahkan');
            if ($is_ajax) {
                echo json_encode(['success' => true, 'message' => 'Produk berhasil ditambahkan', 'id' => $product_id]);
            } else {
                header('Location: index.php');
            }
        } else {
            // Delete uploaded image if insert failed
            if ($gambar_filename) {
                deleteFile(UPLOADS_PATH . 'products/' . $gambar_filename);
            }
            if ($is_ajax) {
                echo json_encode(['success' => false, 'message' => 'Gagal menambahkan produk']);
            } else {
                setFlashMessage('error', 'Gagal menambahkan produk');
                header('Location: index.php');
            }
        }
    }

} catch (Exception $e) {
    error_log("Product save error: " . $e->getMessage());

    // Delete uploaded image if error occurred
    if (isset($gambar_filename) && $gambar_filename) {
        deleteFile(UPLOADS_PATH . 'products/' . $gambar_filename);
    }

    if ($is_ajax) {
        echo json_encode(['success' => false, 'message' => 'Terjadi kesalahan sistem']);
    } else {
        setFlashMessage('error', 'Terjadi kesalahan sistem');
        header('Location: index.php');
    }
}
?>