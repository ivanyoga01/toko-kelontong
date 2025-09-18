<?php
/**
 * Admin Template Footer
 * Reusable footer component for admin and kasir pages
 */

// Get shop info if not already loaded
if (!isset($shop_info)) {
    $shop_info = [
        'name' => APP_NAME,
        'description' => APP_DESCRIPTION
    ];
}
?>
                </div> <!-- container -->
            </div> <!-- content -->

            <!-- Footer Start -->
            <footer class="footer">
                <div class="container-fluid">
                    <div class="row">
                        <div class="col-md-6">
                            <?php echo date('Y'); ?> © <?php echo htmlspecialchars($shop_info['name']); ?> - Admin Panel
                        </div>
                        <div class="col-md-6">
                            <div class="text-md-right footer-links d-none d-md-block">
                                <a href="javascript: void(0);">Support</a>
                            </div>
                        </div>
                    </div>
                </div>
            </footer>
            <!-- end Footer -->

        </div>
        <!-- ==============================================================  -->
        <!-- End Page content -->
        <!-- ============================================================== -->

    </div>
    <!-- END wrapper -->

    <!-- Vendor js -->
    <script src="<?php echo ASSETS_URL; ?>Adminto_v4.0.0/Vertical/dist/assets/js/vendor.min.js"></script>

    <!-- App js -->
    <script src="<?php echo ASSETS_URL; ?>Adminto_v4.0.0/Vertical/dist/assets/js/app.min.js"></script>

    <!-- Custom JS -->
    <script>
        // Format currency function
        function formatCurrency(amount) {
            return 'Rp ' + new Intl.NumberFormat('id-ID').format(amount);
        }

        // Show loading function
        function showLoading() {
            $('body').append('<div class="loading-overlay"><div class="spinner-border text-primary"></div></div>');
        }

        // Hide loading function
        function hideLoading() {
            $('.loading-overlay').remove();
        }

        // Auto hide alerts after 5 seconds
        setTimeout(function() {
            $('.alert').fadeOut();
        }, 5000);

        // Initialize tooltips
        $('[data-toggle="tooltip"]').tooltip();

        // Initialize popovers
        $('[data-toggle="popover"]').popover();
    </script>

    <?php if (isset($additional_js)): ?>
    <!-- Additional JavaScript -->
    <?php echo $additional_js; ?>
    <?php endif; ?>

    <!-- Page specific scripts -->
    <?php if (isset($page_scripts)): ?>
        <?php echo $page_scripts; ?>
    <?php endif; ?>

</body>
</html>