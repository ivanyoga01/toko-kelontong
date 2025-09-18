<?php
/**
 * Save User AJAX Handler
 * Handles create and update operations for users
 */

require_once '../../includes/admin_auth.php';
require_once '../../includes/functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

try {
    // Get form data
    $id = sanitizeInput($_POST['id'] ?? '');
    $username = sanitizeInput($_POST['username'] ?? '');
    $nama_lengkap = sanitizeInput($_POST['nama_lengkap'] ?? '');
    $role = sanitizeInput($_POST['role'] ?? '');
    $status = sanitizeInput($_POST['status'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    $errors = [];

    // Validation
    if (empty($username)) {
        $errors['username'] = 'Username wajib diisi';
    } elseif (strlen($username) < 3) {
        $errors['username'] = 'Username minimal 3 karakter';
    } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        $errors['username'] = 'Username hanya boleh huruf, angka, dan underscore';
    }

    if (empty($nama_lengkap)) {
        $errors['namaLengkap'] = 'Nama lengkap wajib diisi';
    } elseif (strlen($nama_lengkap) < 2) {
        $errors['namaLengkap'] = 'Nama lengkap minimal 2 karakter';
    }

    if (empty($role)) {
        $errors['role'] = 'Role wajib dipilih';
    } elseif (!in_array($role, ['admin', 'kasir'])) {
        $errors['role'] = 'Role tidak valid';
    }

    if (empty($status)) {
        $errors['status'] = 'Status wajib dipilih';
    } elseif (!in_array($status, ['aktif', 'nonaktif'])) {
        $errors['status'] = 'Status tidak valid';
    }

    $isEdit = !empty($id);

    // Password validation for new user
    if (!$isEdit) {
        if (empty($password)) {
            $errors['password'] = 'Password wajib diisi';
        } elseif (strlen($password) < 6) {
            $errors['password'] = 'Password minimal 6 karakter';
        }

        if (empty($confirm_password)) {
            $errors['confirmPassword'] = 'Konfirmasi password wajib diisi';
        } elseif ($password !== $confirm_password) {
            $errors['confirmPassword'] = 'Konfirmasi password tidak cocok';
        }
    }

    // Check username uniqueness
    if (!empty($username)) {
        $check_sql = "SELECT id FROM users WHERE username = ?";
        $check_params = [$username];

        if ($isEdit) {
            $check_sql .= " AND id != ?";
            $check_params[] = $id;
        }

        $existing = fetchOne($check_sql, $check_params);
        if ($existing) {
            $errors['username'] = 'Username sudah digunakan';
        }
    }

    if (!empty($errors)) {
        echo json_encode(['success' => false, 'errors' => $errors]);
        exit;
    }

    $pdo = getDatabase();

    if ($isEdit) {
        // Update user
        $sql = "UPDATE users SET username = ?, nama_lengkap = ?, role = ?, status = ?, updated_at = NOW()
                WHERE id = ?";
        $params = [$username, $nama_lengkap, $role, $status, $id];

        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute($params);

        if ($result) {
            echo json_encode(['success' => true, 'message' => 'User berhasil diperbarui']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Gagal memperbarui user']);
        }
    } else {
        // Create new user
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        $sql = "INSERT INTO users (username, password, nama_lengkap, role, status, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, NOW(), NOW())";
        $params = [$username, $hashed_password, $nama_lengkap, $role, $status];

        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute($params);

        if ($result) {
            echo json_encode(['success' => true, 'message' => 'User berhasil ditambahkan']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Gagal menambahkan user']);
        }
    }

} catch (Exception $e) {
    error_log("User save error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Terjadi kesalahan sistem']);
}
?>