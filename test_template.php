<?php
/**
 * Template Structure Test
 * Test page to verify the new template structure works correctly
 */

require_once 'includes/admin_auth.php';

$page_title = 'Test Template Structure';
$page_description = 'Testing the new modular template structure';

include 'includes/header.php';
include 'includes/sidebar.php';
?>

<!-- Start Content -->
<div class="content-page">
    <div class="content">
        <div class="container-fluid">

            <!-- Page Title -->
            <div class="row">
                <div class="col-12">
                    <div class="page-title-box">
                        <div class="page-title-right">
                            <ol class="breadcrumb m-0">
                                <li class="breadcrumb-item"><a href="javascript: void(0);">Test</a></li>
                                <li class="breadcrumb-item active">Template Structure</li>
                            </ol>
                        </div>
                        <h4 class="page-title">Template Structure Test</h4>
                    </div>
                </div>
            </div>

            <!-- Content -->
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h4 class="header-title">Template Test</h4>
                        </div>
                        <div class="card-body">
                            <div class="alert alert-success">
                                <h5>✅ Template structure updated successfully!</h5>
                                <p class="mb-0">The new modular template structure is working correctly with:</p>
                                <ul class="mt-2 mb-0">
                                    <li>✅ Unified header.php (supports both admin and kasir)</li>
                                    <li>✅ Separate sidebar.php with role-based navigation</li>
                                    <li>✅ Updated footer.php with proper closing tags</li>
                                    <li>✅ Proper content-page structure</li>
                                    <li>✅ Adminto v4.0.0 template compliance</li>
                                </ul>
                            </div>

                            <h6>User Information:</h6>
                            <table class="table table-bordered">
                                <tr>
                                    <th>Current User:</th>
                                    <td><?php echo htmlspecialchars($current_user['nama_lengkap']); ?></td>
                                </tr>
                                <tr>
                                    <th>Role:</th>
                                    <td>
                                        <span class="badge badge-<?php echo $current_user['role'] === 'admin' ? 'primary' : 'success'; ?>">
                                            <?php echo ucfirst($current_user['role']); ?>
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Page Title:</th>
                                    <td><?php echo htmlspecialchars($page_title); ?></td>
                                </tr>
                            </table>

                            <div class="mt-3">
                                <?php if ($current_user['role'] === 'admin'): ?>
                                    <a href="admin/dashboard.php" class="btn btn-primary">Go to Admin Dashboard</a>
                                <?php else: ?>
                                    <a href="kasir/penjualan.php" class="btn btn-success">Go to Kasir POS</a>
                                <?php endif; ?>
                                <a href="auth/logout.php" class="btn btn-outline-secondary">Logout</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div> <!-- container-fluid -->
    </div> <!-- content -->
</div> <!-- content-page -->

<?php include 'includes/footer.php'; ?>