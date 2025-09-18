<?php
/**
 * Admin Users Management
 * Halaman untuk mengelola data user kasir dan admin
 */

require_once '../../includes/admin_auth.php';

// Page config
$page_title = 'Manajemen User';
$page_description = 'Kelola data user kasir dan admin sistem';

// Handle pagination
$page = (int)($_GET['page'] ?? 1);
$limit = 10;
$offset = ($page - 1) * $limit;

// Handle search
$search = sanitizeInput($_GET['search'] ?? '');

// Build query
$where_conditions = [];
$params = [];

if (!empty($search)) {
    $where_conditions[] = "(username LIKE ? OR nama_lengkap LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$where_clause = '';
if (!empty($where_conditions)) {
    $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
}

// Get total count
$count_sql = "SELECT COUNT(*) as total FROM users $where_clause";
$total_users = fetchOne($count_sql, $params)['total'];
$total_pages = ceil($total_users / $limit);

// Get users data
$sql = "SELECT * FROM users
        $where_clause
        ORDER BY created_at DESC
        LIMIT $limit OFFSET $offset";

$users = fetchAll($sql, $params);

// Success/Error messages
$success_message = $_SESSION['success_message'] ?? '';
$error_message = $_SESSION['error_message'] ?? '';
unset($_SESSION['success_message'], $_SESSION['error_message']);

include '../../includes/header.php';
include '../../includes/sidebar.php';
?>

<!-- Start Content -->
<div class="content-page">
    <div class="content">

        <div class="container-fluid">

            <!-- start page title -->
            <div class="row">
                <div class="col-12">
                    <div class="page-title-box">
                        <div class="page-title-right">
                            <ol class="breadcrumb m-0">
                                <li class="breadcrumb-item"><a href="<?= ADMIN_URL ?>dashboard.php">Dashboard</a></li>
                                <li class="breadcrumb-item active"><?= $page_title ?></li>
                            </ol>
                        </div>
                        <h4 class="page-title"><?= $page_title ?></h4>
                    </div>
                </div>
            </div>
            <!-- end page title -->

            <?php if ($success_message): ?>
            <div class="row">
                <div class="col-12">
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <strong>Berhasil!</strong> <?= htmlspecialchars($success_message) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($error_message): ?>
            <div class="row">
                <div class="col-12">
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <strong>Error!</strong> <?= htmlspecialchars($error_message) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">

                            <!-- Action buttons -->
                            <div class="row mb-2">
                                <div class="col-sm-4">
                                    <button type="button" class="btn btn-primary mb-2" data-toggle="modal" data-target="#userModal">
                                        <i class="mdi mdi-plus-circle me-2"></i> Tambah User
                                    </button>
                                </div>
                                <div class="col-sm-8">
                                    <div class="text-sm-end">
                                        <form method="GET" class="d-inline-flex">
                                            <div class="input-group">
                                                <input type="text" name="search" class="form-control" placeholder="Cari username atau nama..." value="<?= htmlspecialchars($search) ?>">
                                                <button class="btn btn-outline-secondary" type="submit">
                                                    <i class="mdi mdi-magnify"></i>
                                                </button>
                                                <?php if ($search): ?>
                                                <a href="index.php" class="btn btn-outline-danger">
                                                    <i class="mdi mdi-close"></i>
                                                </a>
                                                <?php endif; ?>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>

                            <?php if (empty($users)): ?>
                            <div class="text-center py-4">
                                <img src="<?= ASSETS_URL ?>Adminto_v4.0.0/Vertical/dist/assets/images/svg/search-not-found.svg" height="90" alt="Not Found">
                                <h4 class="mt-3">Data tidak ditemukan</h4>
                                <p class="text-muted">
                                    <?= $search ? 'Tidak ada user yang sesuai dengan pencarian.' : 'Belum ada data user.' ?>
                                </p>
                            </div>
                            <?php else: ?>

                            <div class="table-responsive">
                                <table class="table table-centered table-nowrap table-hover mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Username</th>
                                            <th>Nama Lengkap</th>
                                            <th>Role</th>
                                            <th>Status</th>
                                            <th>Dibuat</th>
                                            <th style="width: 125px;">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($users as $user): ?>
                                        <tr>
                                            <td>
                                                <strong><?= htmlspecialchars($user['username']) ?></strong>
                                            </td>
                                            <td><?= htmlspecialchars($user['nama_lengkap']) ?></td>
                                            <td>
                                                <span class="badge <?= $user['role'] === 'admin' ? 'bg-danger' : 'bg-info' ?>">
                                                    <?= ucfirst($user['role']) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge <?= $user['status'] === 'aktif' ? 'bg-success' : 'bg-secondary' ?>">
                                                    <?= ucfirst($user['status']) ?>
                                                </span>
                                            </td>
                                            <td><?= date('d/m/Y H:i', strtotime($user['created_at'])) ?></td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <button type="button" class="btn btn-outline-primary btn-sm edit-user"
                                                            data-user-id="<?= $user['id'] ?>" title="Edit User">
                                                        <i class="mdi mdi-pencil"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-outline-warning btn-sm reset-password"
                                                            data-user-id="<?= $user['id'] ?>"
                                                            data-username="<?= htmlspecialchars($user['username']) ?>" title="Reset Password">
                                                        <i class="mdi mdi-lock-reset"></i>
                                                    </button>
                                                    <?php if ($user['id'] !== $current_user['id']): ?>
                                                    <button type="button" class="btn btn-outline-danger btn-sm delete-user"
                                                            data-user-id="<?= $user['id'] ?>"
                                                            data-username="<?= htmlspecialchars($user['username']) ?>" title="Hapus User">
                                                        <i class="mdi mdi-delete"></i>
                                                    </button>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Pagination -->
                            <?php if ($total_pages > 1): ?>
                            <nav aria-label="Page navigation" class="mt-3">
                                <ul class="pagination justify-content-center">
                                    <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                                        <a class="page-link" href="?page=<?= $page - 1 ?><?= $search ? '&search=' . urlencode($search) : '' ?>">Previous</a>
                                    </li>

                                    <?php
                                    $start = max(1, $page - 2);
                                    $end = min($total_pages, $page + 2);

                                    for ($i = $start; $i <= $end; $i++):
                                    ?>
                                    <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                                        <a class="page-link" href="?page=<?= $i ?><?= $search ? '&search=' . urlencode($search) : '' ?>"><?= $i ?></a>
                                    </li>
                                    <?php endfor; ?>

                                    <li class="page-item <?= $page >= $total_pages ? 'disabled' : '' ?>">
                                        <a class="page-link" href="?page=<?= $page + 1 ?><?= $search ? '&search=' . urlencode($search) : '' ?>">Next</a>
                                    </li>
                                </ul>
                            </nav>
                            <?php endif; ?>

                            <?php endif; ?>

                        </div> <!-- end card body-->
                    </div> <!-- end card -->
                </div><!-- end col-->
            </div><!-- end row-->

        </div> <!-- container -->

    </div> <!-- content -->

    <?php include '../../includes/footer.php'; ?>

</div>

    <!-- Add/Edit User Modal -->
    <div class="modal fade" id="userModal" tabindex="-1" aria-labelledby="userModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="userModalLabel">Tambah User</h5>
                    <button type="button" class="btn-close" data-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="userForm">
                    <div class="modal-body">
                        <input type="hidden" id="userId" name="id">

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="username" class="form-label">Username <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="username" name="username" required>
                                    <div class="invalid-feedback"></div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="role" class="form-label">Role <span class="text-danger">*</span></label>
                                    <select class="form-control" id="role" name="role" required>
                                        <option value="">Pilih Role</option>
                                        <option value="admin">Admin</option>
                                        <option value="kasir">Kasir</option>
                                    </select>
                                    <div class="invalid-feedback"></div>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="namaLengkap" class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="namaLengkap" name="nama_lengkap" required>
                            <div class="invalid-feedback"></div>
                        </div>

                        <div class="row" id="passwordFields">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="password" class="form-label">Password <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <input type="password" class="form-control" id="password" name="password" required>
                                        <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                            <i class="mdi mdi-eye-outline"></i>
                                        </button>
                                    </div>
                                    <div class="invalid-feedback"></div>
                                    <small class="text-muted">Minimal 6 karakter</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="confirmPassword" class="form-label">Konfirmasi Password <span class="text-danger">*</span></label>
                                    <input type="password" class="form-control" id="confirmPassword" name="confirm_password" required>
                                    <div class="invalid-feedback"></div>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
                            <select class="form-control" name="status" required>
                                <option value="aktif">Aktif</option>
                                <option value="nonaktif">Non Aktif</option>
                            </select>
                            <div class="invalid-feedback"></div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">
                            <span class="spinner-border spinner-border-sm me-1" style="display: none;"></span>
                            Simpan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Reset Password Modal -->
    <div class="modal fade" id="resetPasswordModal" tabindex="-1" aria-labelledby="resetPasswordModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="resetPasswordModalLabel">Reset Password</h5>
                    <button type="button" class="btn-close" data-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="resetPasswordForm">
                    <div class="modal-body">
                        <input type="hidden" id="resetUserId" name="user_id">

                        <div class="alert alert-info">
                            <strong>Reset Password untuk:</strong> <span id="resetUsername"></span>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="newPassword" class="form-label">Password Baru <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <input type="password" class="form-control" id="newPassword" name="new_password" required>
                                        <button class="btn btn-outline-secondary" type="button" id="toggleNewPassword">
                                            <i class="mdi mdi-eye-outline"></i>
                                        </button>
                                    </div>
                                    <div class="invalid-feedback"></div>
                                    <small class="text-muted">Minimal 6 karakter</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="confirmNewPassword" class="form-label">Konfirmasi Password <span class="text-danger">*</span></label>
                                    <input type="password" class="form-control" id="confirmNewPassword" name="confirm_new_password" required>
                                    <div class="invalid-feedback"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-warning">
                            <span class="spinner-border spinner-border-sm me-1" style="display: none;"></span>
                            Reset Password
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteModalLabel">Konfirmasi Hapus</h5>
                    <button type="button" class="btn-close" data-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="text-center">
                        <i class="mdi mdi-alert-circle-outline text-warning" style="font-size: 3rem;"></i>
                        <h4 class="mt-2">Apakah Anda yakin?</h4>
                        <p class="text-muted">User "<strong id="deleteUsername"></strong>" akan dihapus secara permanen.</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="button" class="btn btn-danger" id="confirmDelete">
                        <span class="spinner-border spinner-border-sm me-1" style="display: none;"></span>
                        Ya, Hapus
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- bundle -->
    <script src="<?= ASSETS_URL ?>Adminto_v4.0.0/Vertical/dist/assets/js/vendor.min.js"></script>
    <script src="<?= ASSETS_URL ?>Adminto_v4.0.0/Vertical/dist/assets/js/app.min.js"></script>

    <script>
    $(document).ready(function() {
        let isEdit = false;
        let deleteUserId = null;

        // Add User Button
        $('[data-target="#userModal"]').click(function() {
            isEdit = false;
            $('#userModalLabel').text('Tambah User');
            $('#userForm')[0].reset();
            $('#userId').val('');
            $('#passwordFields').show();
            $('#password, #confirmPassword').attr('required', true);
            $('.form-control').removeClass('is-invalid');
        });

        // Edit User Button - Use event delegation for dynamic content
        $(document).on('click', '.edit-user', function() {
            const userId = $(this).data('user-id');
            isEdit = true;
            $('#userModalLabel').text('Edit User');
            $('#passwordFields').hide();
            $('#password, #confirmPassword').attr('required', false);
            $('.form-control').removeClass('is-invalid');

            // Load user data
            $.ajax({
                url: 'get_user.php',
                method: 'POST',
                data: { id: userId },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        const user = response.data;
                        $('#userId').val(user.id);
                        $('#username').val(user.username);
                        $('#namaLengkap').val(user.nama_lengkap);
                        $('#role').val(user.role);
                        $('#status').val(user.status);
                        $('#userModal').modal('show');
                    } else {
                        alert('Error: ' + response.message);
                    }
                },
                error: function() {
                    alert('Gagal mengambil data user');
                }
            });
        });

        // Save User Form
        $('#userForm').submit(function(e) {
            e.preventDefault();

            const submitBtn = $(this).find('button[type="submit"]');
            const spinner = submitBtn.find('.spinner-border');

            // Clear previous validation
            $('.form-control').removeClass('is-invalid');

            // Client-side validation
            if (!isEdit) {
                const password = $('#password').val();
                const confirmPassword = $('#confirmPassword').val();

                if (password.length < 6) {
                    $('#password').addClass('is-invalid')
                        .siblings('.invalid-feedback').text('Password minimal 6 karakter');
                    return;
                }

                if (password !== confirmPassword) {
                    $('#confirmPassword').addClass('is-invalid')
                        .siblings('.invalid-feedback').text('Konfirmasi password tidak cocok');
                    return;
                }
            }

            submitBtn.prop('disabled', true);
            spinner.show();

            $.ajax({
                url: 'save.php',
                method: 'POST',
                data: $(this).serialize(),
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        $('#userModal').modal('hide');
                        location.reload();
                    } else {
                        // Handle validation errors
                        if (response.errors) {
                            for (const field in response.errors) {
                                $(`#${field}`).addClass('is-invalid')
                                    .siblings('.invalid-feedback').text(response.errors[field]);
                            }
                        } else {
                            alert('Error: ' + response.message);
                        }
                    }
                },
                error: function() {
                    alert('Gagal menyimpan data user');
                },
                complete: function() {
                    submitBtn.prop('disabled', false);
                    spinner.hide();
                }
            });
        });

        // Reset Password Button - Use event delegation for dynamic content
        $(document).on('click', '.reset-password', function() {
            const userId = $(this).data('user-id');
            const username = $(this).data('username');

            $('#resetUserId').val(userId);
            $('#resetUsername').text(username);
            $('#resetPasswordForm')[0].reset();
            $('.form-control').removeClass('is-invalid');
            $('#resetPasswordModal').modal('show');
        });

        // Reset Password Form
        $('#resetPasswordForm').submit(function(e) {
            e.preventDefault();

            const submitBtn = $(this).find('button[type="submit"]');
            const spinner = submitBtn.find('.spinner-border');

            // Clear previous validation
            $('.form-control').removeClass('is-invalid');

            // Client-side validation
            const newPassword = $('#newPassword').val();
            const confirmNewPassword = $('#confirmNewPassword').val();

            if (newPassword.length < 6) {
                $('#newPassword').addClass('is-invalid')
                    .siblings('.invalid-feedback').text('Password minimal 6 karakter');
                return;
            }

            if (newPassword !== confirmNewPassword) {
                $('#confirmNewPassword').addClass('is-invalid')
                    .siblings('.invalid-feedback').text('Konfirmasi password tidak cocok');
                return;
            }

            submitBtn.prop('disabled', true);
            spinner.show();

            $.ajax({
                url: 'reset_password.php',
                method: 'POST',
                data: $(this).serialize(),
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        $('#resetPasswordModal').modal('hide');
                        alert('Password berhasil direset');
                    } else {
                        alert('Error: ' + response.message);
                    }
                },
                error: function() {
                    alert('Gagal reset password');
                },
                complete: function() {
                    submitBtn.prop('disabled', false);
                    spinner.hide();
                }
            });
        });

        // Delete User Button - Use event delegation for dynamic content
        $(document).on('click', '.delete-user', function() {
            deleteUserId = $(this).data('user-id');
            const username = $(this).data('username');
            $('#deleteUsername').text(username);
            $('#deleteModal').modal('show');
        });

        // Confirm Delete
        $('#confirmDelete').click(function() {
            const submitBtn = $(this);
            const spinner = submitBtn.find('.spinner-border');

            submitBtn.prop('disabled', true);
            spinner.show();

            $.ajax({
                url: 'delete.php',
                method: 'POST',
                data: { id: deleteUserId },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        $('#deleteModal').modal('hide');
                        location.reload();
                    } else {
                        alert('Error: ' + response.message);
                    }
                },
                error: function() {
                    alert('Gagal menghapus user');
                },
                complete: function() {
                    submitBtn.prop('disabled', false);
                    spinner.hide();
                }
            });
        });

        // Toggle Password Visibility
        $('#togglePassword').click(function() {
            const password = $('#password');
            const icon = $(this).find('i');

            if (password.attr('type') === 'password') {
                password.attr('type', 'text');
                icon.removeClass('mdi-eye-outline').addClass('mdi-eye-off-outline');
            } else {
                password.attr('type', 'password');
                icon.removeClass('mdi-eye-off-outline').addClass('mdi-eye-outline');
            }
        });

        $('#toggleNewPassword').click(function() {
            const password = $('#newPassword');
            const icon = $(this).find('i');

            if (password.attr('type') === 'password') {
                password.attr('type', 'text');
                icon.removeClass('mdi-eye-outline').addClass('mdi-eye-off-outline');
            } else {
                password.attr('type', 'password');
                icon.removeClass('mdi-eye-off-outline').addClass('mdi-eye-outline');
            }
        });
    });
    </script>

</body>
</html>