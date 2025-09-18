<?php
/**
 * Admin Template Sidebar
 * Reusable sidebar navigation for admin and kasir pages
 */

// Tentukan menu aktif berdasarkan nama file saat ini
$current_page = basename($_SERVER['PHP_SELF']);
$current_user = getCurrentUser();
?>

<!-- ========== Left Sidebar Start ========== -->
<div class="left-side-menu">
    <div class="slimscroll-menu">
        <!--- Sidemenu -->
        <div id="sidebar-menu">
            <?php if ($current_user['role'] === 'admin'): ?>
                <ul class="metismenu" id="side-menu">
                    <li class="menu-title">Main Menu</li>

                    <li class="<?php echo $current_page == 'dashboard.php' ? 'mm-active' : ''; ?>">
                        <a href="<?php echo ADMIN_URL; ?>dashboard.php">
                            <i class="mdi mdi-view-dashboard"></i>
                            <span>Dashboard</span>
                        </a>
                    </li>

                    <li>
                        <a href="javascript: void(0);">
                            <i class="mdi mdi-package-variant"></i>
                            <span>Master Data</span>
                            <span class="menu-arrow"></span>
                        </a>
                        <ul class="nav-second-level" aria-expanded="false">
                            <li><a href="<?php echo ADMIN_URL; ?>categories/">Kategori Barang</a></li>
                            <li><a href="<?php echo ADMIN_URL; ?>products/">Data Barang</a></li>
                            <li><a href="<?php echo ADMIN_URL; ?>customers/">Data Pelanggan</a></li>
                        </ul>
                    </li>

                    <li class="<?php echo $current_page == 'transactions.php' ? 'mm-active' : ''; ?>">
                        <a href="<?php echo ADMIN_URL; ?>transactions/">
                            <i class="mdi mdi-cart"></i>
                            <span>Transaksi</span>
                        </a>
                    </li>

                    <li class="<?php echo $current_page == 'stock.php' ? 'mm-active' : ''; ?>">
                        <a href="<?php echo ADMIN_URL; ?>stock/">
                            <i class="mdi mdi-cube-outline"></i>
                            <span>Pergerakan Stok</span>
                        </a>
                    </li>

                    <li>
                        <a href="javascript: void(0);">
                            <i class="mdi mdi-chart-line"></i>
                            <span>Laporan</span>
                            <span class="menu-arrow"></span>
                        </a>
                        <ul class="nav-second-level" aria-expanded="false">
                            <li><a href="<?php echo ADMIN_URL; ?>reports/daily.php">Laporan Harian</a></li>
                            <li><a href="<?php echo ADMIN_URL; ?>reports/weekly.php">Laporan Mingguan</a></li>
                            <li><a href="<?php echo ADMIN_URL; ?>reports/monthly.php">Laporan Bulanan</a></li>
                            <li><a href="<?php echo ADMIN_URL; ?>reports/bestseller.php">Barang Terlaris</a></li>
                            <li><a href="<?php echo ADMIN_URL; ?>reports/revenue.php">Laporan Omzet</a></li>
                        </ul>
                    </li>

                    <li class="menu-title mt-2">Lainnya</li>

                    <li class="<?php echo $current_page == 'users.php' ? 'mm-active' : ''; ?>">
                        <a href="<?php echo ADMIN_URL; ?>users/">
                            <i class="mdi mdi-account-multiple"></i>
                            <span>Kelola User</span>
                        </a>
                    </li>

                    <li>
                        <a href="<?php echo AUTH_URL; ?>logout.php">
                            <i class="mdi mdi-logout"></i>
                            <span>Logout</span>
                        </a>
                    </li>
                </ul>
            <?php else: ?>
                <!-- Kasir Navigation -->
                <ul class="metismenu" id="side-menu">
                    <li class="menu-title">Menu Kasir</li>

                    <li class="<?php echo $current_page == 'penjualan.php' ? 'mm-active' : ''; ?>">
                        <a href="<?php echo KASIR_URL; ?>penjualan.php">
                            <i class="mdi mdi-cart"></i>
                            <span>Point of Sale</span>
                        </a>
                    </li>

                    <li class="<?php echo $current_page == 'advanced_search.php' ? 'mm-active' : ''; ?>">
                        <a href="<?php echo KASIR_URL; ?>advanced_search.php">
                            <i class="mdi mdi-magnify-plus"></i>
                            <span>Pencarian Produk</span>
                        </a>
                    </li>

                    <li class="<?php echo $current_page == 'riwayat.php' ? 'mm-active' : ''; ?>">
                        <a href="<?php echo KASIR_URL; ?>riwayat.php">
                            <i class="mdi mdi-format-list-bulleted"></i>
                            <span>Riwayat Transaksi</span>
                        </a>
                    </li>

                    <li>
                        <a href="javascript: void(0);">
                            <i class="mdi mdi-account-multiple"></i>
                            <span>Pelanggan</span>
                            <span class="menu-arrow"></span>
                        </a>
                        <ul class="nav-second-level" aria-expanded="false">
                            <li><a href="<?php echo KASIR_URL; ?>customers/search.php">Cari Pelanggan</a></li>
                            <li><a href="<?php echo KASIR_URL; ?>customers/add.php">Tambah Pelanggan</a></li>
                        </ul>
                    </li>

                    <li class="menu-title mt-2">Lainnya</li>

                    <li>
                        <a href="<?php echo AUTH_URL; ?>logout.php">
                            <i class="mdi mdi-logout"></i>
                            <span>Logout</span>
                        </a>
                    </li>
                </ul>
            <?php endif; ?>
        </div>
        <!-- End Sidebar -->
        <div class="clearfix"></div>
    </div>
    <!-- Sidebar -left -->
</div>
<!-- Left Sidebar End -->