<?php
/**
 * Kasir POS System - Penjualan
 * Sistem Point of Sale untuk kasir
 */

require_once '../includes/kasir_auth.php';

$page_title = 'Point of Sale - ' . APP_NAME;

// Get all active products with categories
$products_sql = "SELECT p.*, c.nama_kategori
                FROM products p
                LEFT JOIN categories c ON p.category_id = c.id
                WHERE p.status = 'aktif' AND p.stok > 0
                ORDER BY p.nama_barang ASC";
$products = fetchAll($products_sql);

// Get all categories for filter
$categories_sql = "SELECT * FROM categories ORDER BY nama_kategori ASC";
$categories = fetchAll($categories_sql);

include '../includes/header.php';
include '../includes/sidebar.php';
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
                    <li class="breadcrumb-item"><a href="javascript: void(0);">Kasir</a></li>
                    <li class="breadcrumb-item active">Point of Sale</li>
                </ol>
            </div>
            <h4 class="page-title">Point of Sale (POS)</h4>
        </div>
    </div>
</div>

<div class="row">
    <!-- Product Selection Area -->
    <div class="col-xl-8">
        <div class="card">
            <div class="card-body">
                <h4 class="header-title">Pilih Produk</h4>

                <!-- Search and Filter -->
                <div class="row mb-3">
                    <div class="col-md-4">
                        <input type="text" id="search_product" class="form-control" placeholder="Cari produk...">
                    </div>
                    <div class="col-md-2">
                        <select id="category_filter" class="form-control">
                            <option value="">Semua Kategori</option>
                            <?php foreach ($categories as $category): ?>
                            <option value="<?php echo $category['id']; ?>"><?php echo htmlspecialchars($category['nama_kategori']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="button" class="btn btn-primary btn-block" onclick="refreshProducts()">
                            <i class="fe-refresh-cw"></i>
                        </button>
                    </div>
                    <div class="col-md-2">
                        <a href="advanced_search.php" class="btn btn-info btn-block" title="Pencarian Lanjutan">
                            <i class="fe-search"></i>
                        </a>
                    </div>
                    <div class="col-md-2">
                        <button type="button" class="btn btn-success btn-block" onclick="toggleBarcodeScanner()" title="Barcode Scanner">
                            <i class="mdi mdi-barcode-scan" id="barcode-icon"></i>
                        </button>
                    </div>
                </div>

                <!-- Barcode Scanner Interface -->
                <div class="row mb-3" id="barcode_scanner_area" style="display: none;">
                    <div class="col-12">
                        <div class="card bg-light">
                            <div class="card-body">
                                <h6 class="card-title text-primary">
                                    <i class="mdi mdi-barcode-scan"></i> Barcode Scanner
                                </h6>
                                <div class="input-group">
                                    <input type="text" id="barcode_input" class="form-control form-control-lg"
                                           placeholder="Scan barcode atau ketik manual..." autofocus>
                                    <div class="input-group-append">
                                        <button type="button" class="btn btn-primary" onclick="lookupBarcode()">
                                            <i class="fe-search"></i> Cari
                                        </button>
                                        <button type="button" class="btn btn-outline-secondary" onclick="clearBarcode()">
                                            <i class="fe-x"></i>
                                        </button>
                                    </div>
                                </div>
                                <small class="text-muted">
                                    <i class="fe-info"></i>
                                    Posisikan kursor di kolom input, lalu scan barcode dengan scanner.
                                    Atau ketik barcode manual lalu tekan Enter atau klik Cari.
                                </small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Products Grid -->
                <div class="row" id="products_grid">
                    <?php foreach ($products as $product): ?>
                    <div class="col-lg-3 col-md-4 col-sm-6 mb-3 product-item" data-category="<?php echo $product['category_id']; ?>">
                        <div class="card pos-item" onclick="addToCart(<?php echo $product['id']; ?>)">
                            <div class="card-body text-center p-2">
                                <?php if ($product['gambar']): ?>
                                    <img src="<?php echo UPLOADS_URL; ?>products/<?php echo $product['gambar']; ?>" alt="<?php echo htmlspecialchars($product['nama_barang']); ?>" class="img-fluid mb-2" style="height: 60px; object-fit: cover;">
                                <?php else: ?>
                                    <div class="bg-light d-flex align-items-center justify-content-center mb-2" style="height: 60px;">
                                        <i class="fe-image text-muted" style="font-size: 24px;"></i>
                                    </div>
                                <?php endif; ?>
                                <h6 class="card-title font-14 mb-1"><?php echo htmlspecialchars($product['nama_barang']); ?></h6>
                                <p class="text-muted mb-1 small"><?php echo htmlspecialchars($product['nama_kategori']); ?></p>
                                <p class="text-success font-weight-bold mb-1"><?php echo formatCurrency($product['harga_jual']); ?></p>
                                <small class="text-muted">Stok: <?php echo $product['stok']; ?> <?php echo htmlspecialchars($product['satuan']); ?></small>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Shopping Cart Area -->
    <div class="col-xl-4">
        <div class="card">
            <div class="card-body">
                <h4 class="header-title">Keranjang Belanja</h4>

                <!-- Customer Selection -->
                <div class="form-group">
                    <label>Tipe Pelanggan</label>
                    <div>
                        <div class="custom-control custom-radio custom-control-inline">
                            <input type="radio" id="customer_guest" name="customer_type" value="guest" class="custom-control-input" checked>
                            <label class="custom-control-label" for="customer_guest">Guest/Umum</label>
                        </div>
                        <div class="custom-control custom-radio custom-control-inline">
                            <input type="radio" id="customer_registered" name="customer_type" value="registered" class="custom-control-input">
                            <label class="custom-control-label" for="customer_registered">Pelanggan Terdaftar</label>
                        </div>
                    </div>
                </div>

                <!-- Guest Customer Area -->
                <div id="guest_area" class="form-group">
                    <input type="text" id="guest_name" class="form-control" placeholder="Nama pelanggan (opsional)" maxlength="100">
                    <small class="text-muted">Kosongkan jika pelanggan tidak ingin memberikan nama</small>
                </div>

                <!-- Registered Customer Area -->
                <div id="registered_area" class="form-group" style="display: none;">
                    <input type="text" id="customer_search" class="form-control" placeholder="Cari nama/no HP pelanggan...">
                    <div id="customer_results" class="list-group mt-2" style="max-height: 200px; overflow-y: auto;"></div>
                    <button type="button" class="btn btn-sm btn-outline-primary mt-2" onclick="showAddCustomerModal()">
                        <i class="fe-plus"></i> Tambah Pelanggan Baru
                    </button>
                </div>

                <!-- Selected Customer Display -->
                <div id="selected_customer" class="alert alert-info" style="display: none;">
                    <strong>Pelanggan:</strong> <span id="selected_customer_name"></span>
                    <button type="button" class="close" onclick="clearSelectedCustomer()">
                        <span>&times;</span>
                    </button>
                </div>

                <!-- Cart Items -->
                <div class="cart-items" id="cart_items">
                    <div class="text-center text-muted py-4">
                        <i class="fe-shopping-cart" style="font-size: 48px;"></i>
                        <p>Keranjang kosong</p>
                    </div>
                </div>

                <!-- Cart Total -->
                <div class="border-top pt-3 mt-3">
                    <div class="d-flex justify-content-between">
                        <span>Subtotal:</span>
                        <span id="subtotal" class="font-weight-bold">Rp 0</span>
                    </div>

                    <!-- Discount Section -->
                    <div class="discount-section mt-2" id="discount_section" style="display: none;">
                        <div class="d-flex justify-content-between text-success">
                            <span><i class="mdi mdi-tag"></i> Diskon:</span>
                            <span id="discount_amount" class="font-weight-bold">- Rp 0</span>
                        </div>
                        <small class="text-muted" id="discount_description"></small>
                        <button type="button" class="btn btn-sm btn-outline-danger float-right mt-1" onclick="removeDiscount()">
                            <i class="fe-x"></i> Hapus
                        </button>
                        <div class="clearfix"></div>
                    </div>

                    <div class="d-flex justify-content-between border-top pt-2 mt-2">
                        <span class="h5">Total:</span>
                        <span id="total" class="h5 text-success total-display">Rp 0</span>
                    </div>

                    <!-- Discount Controls -->
                    <!-- <div class="mt-2">
                        <button type="button" class="btn btn-outline-warning btn-block btn-sm" onclick="applyManualDiscount()">
                            <i class="mdi mdi-percent"></i> Terapkan Diskon Manual
                        </button>
                    </div> -->
                </div>

                <!-- Action Buttons -->
                <div class="mt-3">
                    <button type="button" class="btn btn-success btn-block" id="btn_checkout" onclick="processCheckout()" disabled>
                        <i class="fe-credit-card"></i> Proses Checkout
                    </button>
                    <button type="button" class="btn btn-outline-secondary btn-block" onclick="clearCart()">
                        <i class="fe-trash-2"></i> Kosongkan Keranjang
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Customer Modal -->
<div class="modal fade" id="addCustomerModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Tambah Pelanggan Baru</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="addCustomerForm">
                    <div class="form-group">
                        <label>Nama Pelanggan *</label>
                        <input type="text" name="nama_pelanggan" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>No. HP</label>
                        <input type="text" name="no_hp" class="form-control">
                    </div>
                    <div class="form-group">
                        <label>Alamat</label>
                        <textarea name="alamat" class="form-control" rows="2"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-primary" onclick="saveNewCustomer()">Simpan</button>
            </div>
        </div>
    </div>
</div>

        </div> <!-- container-fluid -->
    </div> <!-- content -->
</div> <!-- content-page -->

<?php include '../includes/footer.php'; ?>

<script>
    let cart = [];
    let selectedCustomer = null;

    // Customer type toggle
    $("input[name=customer_type]").change(function() {
        if ($(this).val() === "guest") {
            $("#guest_area").show();
            $("#registered_area").hide();
            clearSelectedCustomer();
        } else {
            $("#guest_area").hide();
            $("#registered_area").show();
        }
    });

    // Search products
    $("#search_product").on("keyup", function() {
        filterProducts();
    });

    $("#category_filter").change(function() {
        filterProducts();
    });

    function filterProducts() {
        let search = $("#search_product").val().toLowerCase();
        let category = $("#category_filter").val();

        $(".product-item").each(function() {
            let text = $(this).text().toLowerCase();
            let itemCategory = $(this).data("category");

            let showText = search === "" || text.includes(search);
            let showCategory = category === "" || itemCategory == category;

            if (showText && showCategory) {
                $(this).show();
            } else {
                $(this).hide();
            }
        });
    }

    // Add to cart
    function addToCart(productId) {
        // Find product data
        let productCard = $(".product-item").find("[onclick*='" + productId + "']");
        let productName = productCard.find(".card-title").text();
        let productPrice = parseFloat(productCard.find(".text-success").text().replace(/[^0-9]/g, ""));

        // Check if already in cart
        let existingItem = cart.find(item => item.id === productId);

        if (existingItem) {
            existingItem.quantity += 1;
        } else {
            cart.push({
                id: productId,
                name: productName,
                price: productPrice,
                quantity: 1
            });
        }

        updateCartDisplay();
    }

    // Update cart display
    function updateCartDisplay() {
        let cartHtml = "";
        let subtotal = 0;

        if (cart.length === 0) {
            cartHtml = '<div class="text-center text-muted py-4">' +
                       '<i class="fe-shopping-cart" style="font-size: 48px;"></i>' +
                       '<p>Keranjang kosong</p>' +
                       '</div>';
            $("#btn_checkout").prop("disabled", true);
        } else {
            cart.forEach(item => {
                let itemTotal = item.price * item.quantity;
                subtotal += itemTotal;

                cartHtml += '<div class="cart-item">' +
                          '<div class="d-flex justify-content-between align-items-center">' +
                          '<div>' +
                          '<h6 class="mb-0">' + item.name + '</h6>' +
                          '<small class="text-muted">' + formatCurrency(item.price) + ' x ' + item.quantity + '</small>' +
                          '</div>' +
                          '<div>' +
                          '<div class="btn-group btn-group-sm">' +
                          '<button class="btn btn-outline-secondary" onclick="updateQuantity(' + item.id + ', -1)">-</button>' +
                          '<span class="btn btn-outline-secondary">' + item.quantity + '</span>' +
                          '<button class="btn btn-outline-secondary" onclick="updateQuantity(' + item.id + ', 1)">+</button>' +
                          '</div>' +
                          '<button class="btn btn-sm btn-outline-danger ml-2" onclick="removeFromCart(' + item.id + ')">' +
                          '<i class="fe-trash"></i>' +
                          '</button>' +
                          '</div>' +
                          '</div>' +
                          '<div class="text-right">' +
                          '<strong>' + formatCurrency(itemTotal) + '</strong>' +
                          '</div>' +
                          '</div>';
            });
            $("#btn_checkout").prop("disabled", false);
        }

        $("#cart_items").html(cartHtml);
        $("#subtotal").text(formatCurrency(subtotal));
        $("#total").text(formatCurrency(subtotal));
    }

    // Update quantity
    function updateQuantity(productId, change) {
        let item = cart.find(item => item.id === productId);
        if (item) {
            item.quantity += change;
            if (item.quantity <= 0) {
                removeFromCart(productId);
            } else {
                updateCartDisplay();
            }
        }
    }

    // Remove from cart
    function removeFromCart(productId) {
        cart = cart.filter(item => item.id !== productId);
        updateCartDisplay();
    }

    // Clear cart
    function clearCart() {
        cart = [];
        removeDiscount();
        updateCartDisplay();
    }

    // Process checkout
    function processCheckout() {
        if (cart.length === 0) {
            alert("Keranjang belanja kosong!");
            return;
        }

        let customerType = $("input[name=customer_type]:checked").val();
        let customerData = {};

        if (customerType === "guest") {
            customerData.type = "guest";
            customerData.name = $("#guest_name").val().trim();
        } else {
            if (!selectedCustomer) {
                alert("Silakan pilih pelanggan terlebih dahulu!");
                return;
            }
            customerData.type = "registered";
            customerData.id = selectedCustomer.id;
            customerData.name = selectedCustomer.name;
        }

        // Submit transaction
        $.ajax({
            url: "../ajax/save_transaction.php",
            method: "POST",
            data: {
                customer: customerData,
                items: cart,
                discount: currentDiscount
            },
            dataType: "json",
            success: function(response) {
                if (response.success) {
                    alert("Transaksi berhasil! Kode: " + response.transaction_code);

                    // Print options
                    if (confirm("Transaksi berhasil!\n\nPilih opsi cetak:\n\nOK = Cetak Struk (HTML)\nCancel = Download Struk (TXT)")) {
                        // Print HTML receipt
                        window.open("print_receipt.php?id=" + response.transaction_id, "_blank");
                    } else {
                        // Download text receipt for thermal printer
                        window.open("print_receipt.php?id=" + response.transaction_id + "&format=text", "_blank");
                    }

                    // Reset form
                    clearCart();
                    removeDiscount();
                    clearSelectedCustomer();
                    $("#guest_name").val("");
                    $("input[name=customer_type][value=guest]").prop("checked", true).trigger("change");
                } else {
                    alert("Error: " + response.message);
                }
            },
            error: function() {
                alert("Terjadi kesalahan sistem!");
            }
        });
    }

    // Customer search
    $("#customer_search").on("keyup", function() {
        let query = $(this).val();
        if (query.length >= 2) {
            $.ajax({
                url: "../ajax/search_customer.php",
                method: "GET",
                data: { q: query },
                dataType: "json",
                success: function(customers) {
                    let html = "";
                    customers.forEach(customer => {
                        html += '<a href="#" class="list-group-item list-group-item-action" onclick="selectCustomer(' + customer.id + ', \'' + customer.nama_pelanggan + '\')">' +
                              '<strong>' + customer.nama_pelanggan + '</strong><br>' +
                              '<small class="text-muted">' + (customer.no_hp || '') + '</small>' +
                              '</a>';
                    });
                    $("#customer_results").html(html);
                }
            });
        } else {
            $("#customer_results").html("");
        }
    });

    // Select customer
    function selectCustomer(id, name) {
        selectedCustomer = { id: id, name: name };
        $("#selected_customer_name").text(name);
        $("#selected_customer").show();
        $("#customer_search").val("");
        $("#customer_results").html("");
    }

    // Clear selected customer
    function clearSelectedCustomer() {
        selectedCustomer = null;
        $("#selected_customer").hide();
    }

    // Show add customer modal
    function showAddCustomerModal() {
        $("#addCustomerModal").modal("show");
    }

    // Save new customer
    function saveNewCustomer() {
        let formData = $("#addCustomerForm").serialize();

        $.ajax({
            url: "customers/save.php",
            method: "POST",
            data: formData,
            dataType: "json",
            success: function(response) {
                if (response.success) {
                    $("#addCustomerModal").modal("hide");
                    selectCustomer(response.customer_id, response.customer_name);
                    $("#addCustomerForm")[0].reset();
                } else {
                    alert("Error: " + response.message);
                }
            },
            error: function() {
                alert("Terjadi kesalahan sistem!");
            }
        });
    }

    // Refresh products
    function refreshProducts() {
        location.reload();
    }

    // Handle product from advanced search
    function handleAdvancedSearchProduct() {
        const urlParams = new URLSearchParams(window.location.search);
        const productId = urlParams.get('add_product');
        const qty = parseInt(urlParams.get('qty')) || 1;

        if (productId) {
            // Add product to cart
            $.ajax({
                url: '../ajax/get_product.php',
                method: 'GET',
                data: { id: productId },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        const product = response.data;

                        // Check if product is available
                        if (product.stok > 0) {
                            // Add to cart with specified quantity
                            for (let i = 0; i < qty && i < product.stok; i++) {
                                addToCart(productId);
                            }

                            // Show success message
                            showNotification(`${product.nama_barang} (${Math.min(qty, product.stok)} pcs) ditambahkan ke keranjang`, 'success');

                            // Clean URL
                            const cleanUrl = window.location.pathname;
                            window.history.replaceState({}, document.title, cleanUrl);
                        } else {
                            showNotification('Produk sudah habis', 'error');
                        }
                    }
                },
                error: function() {
                    showNotification('Gagal memuat produk', 'error');
                }
            });
        }
    }

    // Show notification
    function showNotification(message, type = 'info') {
        const alertClass = type === 'error' ? 'alert-danger' : (type === 'success' ? 'alert-success' : 'alert-info');
        const notification = `
            <div class="alert ${alertClass} alert-dismissible fade show" role="alert">
                ${message}
                <button type="button" class="close" data-dismiss="alert">
                    <span>&times;</span>
                </button>
            </div>
        `;

        // Insert notification at the top of the page
        $('.container-fluid').prepend(notification);

        // Auto remove after 5 seconds
        setTimeout(() => {
            $('.alert').fadeOut();
        }, 5000);
    }

    // Initialize advanced search product handling on page load
    $(document).ready(function() {
        handleAdvancedSearchProduct();

        // Setup barcode input handler
        $('#barcode_input').on('keypress', function(e) {
            if (e.which === 13) { // Enter key
                e.preventDefault();
                lookupBarcode();
            }
        });

        // Auto-focus barcode input when scanner area is visible
        $('#barcode_input').on('focus', function() {
            $(this).select();
        });

        // Discount type change handler
        $('input[name="discount_type"]').change(function() {
            const discountType = $(this).val();
            if (discountType === 'percentage') {
                $('#discount_unit').text('%');
                $('#max_discount_info').text('100%');
            } else {
                $('#discount_unit').text('Rp');
                const subtotal = calculateSubtotal();
                $('#max_discount_info').text(formatCurrency(subtotal));
            }
            updateDiscountPreview();
        });

        // Discount value change handler
        $('#discount_value').on('input', function() {
            updateDiscountPreview();
        });
    });

    // Toggle barcode scanner interface
    function toggleBarcodeScanner() {
        const scannerArea = $('#barcode_scanner_area');
        const icon = $('#barcode-icon');

        if (scannerArea.is(':visible')) {
            scannerArea.slideUp();
            icon.removeClass('text-success').addClass('mdi-barcode-scan');
        } else {
            scannerArea.slideDown();
            icon.addClass('text-success').removeClass('mdi-barcode-scan').addClass('mdi-barcode');
            setTimeout(() => {
                $('#barcode_input').focus();
            }, 300);
        }
    }

    // Clear barcode input
    function clearBarcode() {
        $('#barcode_input').val('').focus();
    }

    // Lookup product by barcode
    function lookupBarcode() {
        const barcode = $('#barcode_input').val().trim();

        if (!barcode) {
            showNotification('Silakan masukkan barcode', 'warning');
            return;
        }

        // Show loading state
        const originalBtn = $('.btn:contains("Cari")');
        originalBtn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Mencari...');

        $.ajax({
            url: '../ajax/barcode_lookup.php',
            method: 'GET',
            data: { barcode: barcode },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    const product = response.data;

                    if (product.stok > 0) {
                        // Add product to cart
                        addToCart(product.id);

                        // Show success message
                        let message = `${product.nama_barang} ditambahkan ke keranjang`;
                        if (response.is_fallback) {
                            message += ' (ditemukan via kode barang)';
                        }
                        showNotification(message, 'success');

                        // Clear and focus barcode input for next scan
                        clearBarcode();

                        // Scroll to cart to show the added item
                        $('html, body').animate({
                            scrollTop: $('#cart_items').offset().top - 100
                        }, 500);
                    } else {
                        showNotification(`${product.nama_barang} stok habis`, 'error');
                        clearBarcode();
                    }
                } else {
                    showNotification(response.message, 'error');
                    $('#barcode_input').select();
                }
            },
            error: function() {
                showNotification('Gagal mencari produk. Periksa koneksi internet.', 'error');
                $('#barcode_input').select();
            },
            complete: function() {
                // Restore button state
                originalBtn.prop('disabled', false).html('<i class="fe-search"></i> Cari');
            }
        });
    }

    // Enhanced notification function with auto-dismiss
    function showBarcodeNotification(message, type = 'info', duration = 3000) {
        const alertClass = type === 'error' ? 'alert-danger' : (type === 'success' ? 'alert-success' : 'alert-warning');
        const iconClass = type === 'error' ? 'fe-x-circle' : (type === 'success' ? 'fe-check-circle' : 'fe-info');

        const notification = `
            <div class="alert ${alertClass} alert-dismissible fade show barcode-notification" role="alert" style="position: fixed; top: 20px; right: 20px; z-index: 9999; min-width: 300px;">
                <i class="${iconClass}"></i> ${message}
                <button type="button" class="close" data-dismiss="alert">
                    <span>&times;</span>
                </button>
            </div>
        `;

        // Remove existing barcode notifications
        $('.barcode-notification').remove();

        // Add new notification
        $('body').append(notification);

        // Auto remove after duration
        setTimeout(() => {
            $('.barcode-notification').fadeOut();
        }, duration);
    }

    // Manual discount functionality
    let currentDiscount = null;

    // Apply manual discount
    function applyManualDiscount() {
        if (cart.length === 0) {
            alert('Keranjang belanja kosong!');
            return;
        }

        // Reset form
        $('#manualDiscountForm')[0].reset();
        $('input[name="discount_type"][value="percentage"]').prop('checked', true);
        $('#discount_unit').text('%');
        $('#max_discount_info').text('100%');

        // Update preview
        updateDiscountPreview();

        // Show modal
        $('#manualDiscountModal').modal('show');
    }

    // Update discount preview
    function updateDiscountPreview() {
        const subtotal = calculateSubtotal();
        const discountType = $('input[name="discount_type"]:checked').val();
        const discountValue = parseFloat($('#discount_value').val()) || 0;

        let discountAmount = 0;

        if (discountType === 'percentage') {
            discountAmount = (subtotal * discountValue) / 100;
            // Cap at 100%
            if (discountValue > 100) {
                $('#discount_value').val(100);
                discountAmount = subtotal;
            }
        } else {
            discountAmount = discountValue;
            // Cap at subtotal
            if (discountAmount > subtotal) {
                $('#discount_value').val(subtotal);
                discountAmount = subtotal;
            }
        }

        const total = subtotal - discountAmount;

        $('#preview_subtotal').text(formatCurrency(subtotal));
        $('#preview_discount').text(formatCurrency(discountAmount));
        $('#preview_total').text(formatCurrency(total));
    }

    // Calculate current subtotal
    function calculateSubtotal() {
        let subtotal = 0;
        cart.forEach(item => {
            subtotal += item.price * item.quantity;
        });
        return subtotal;
    }

    // Confirm manual discount
    function confirmManualDiscount() {
        const discountType = $('input[name="discount_type"]:checked').val();
        const discountValue = parseFloat($('#discount_value').val()) || 0;
        const discountReason = $('#discount_reason').val().trim();

        if (discountValue <= 0) {
            alert('Nilai diskon harus lebih dari 0');
            return;
        }

        const subtotal = calculateSubtotal();
        let discountAmount = 0;
        let description = '';

        if (discountType === 'percentage') {
            if (discountValue > 100) {
                alert('Persentase diskon tidak boleh lebih dari 100%');
                return;
            }
            discountAmount = (subtotal * discountValue) / 100;
            description = `Diskon ${discountValue}%`;
        } else {
            if (discountValue > subtotal) {
                alert('Nominal diskon tidak boleh lebih dari subtotal');
                return;
            }
            discountAmount = discountValue;
            description = `Diskon Rp ${formatCurrency(discountValue)}`;
        }

        if (discountReason) {
            description += ` (${discountReason})`;
        }

        // Apply discount
        currentDiscount = {
            type: discountType,
            value: discountValue,
            amount: discountAmount,
            description: description,
            reason: discountReason
        };

        updateCartDisplay();
        $('#manualDiscountModal').modal('hide');

        showNotification('Diskon berhasil diterapkan', 'success');
    }

    // Remove discount
    function removeDiscount() {
        currentDiscount = null;
        updateCartDisplay();
        showNotification('Diskon berhasil dihapus', 'info');
    }

    // Override the original updateCartDisplay to include discount
    function updateCartDisplayWithDiscountOnly() {
        if (currentDiscount) {
            const subtotal = calculateSubtotal();
            const total = subtotal - currentDiscount.amount;

            $('#discount_section').show();
            $('#discount_amount').text('- ' + formatCurrency(currentDiscount.amount));
            $('#discount_description').text(currentDiscount.description);
            $('#total').text(formatCurrency(total));
        } else {
            $('#discount_section').hide();
        }
    }

    // Update the original updateCartDisplay function
    const originalCartUpdate = updateCartDisplay;
    updateCartDisplay = function() {
        let cartHtml = "";
        let subtotal = 0;

        if (cart.length === 0) {
            cartHtml = '<div class="text-center text-muted py-4">' +
                       '<i class="fe-shopping-cart" style="font-size: 48px;"></i>' +
                       '<p>Keranjang kosong</p>' +
                       '</div>';
            $("#btn_checkout").prop("disabled", true);
        } else {
            cart.forEach(item => {
                let itemTotal = item.price * item.quantity;
                subtotal += itemTotal;

                cartHtml += '<div class="cart-item">' +
                          '<div class="d-flex justify-content-between align-items-center">' +
                          '<div>' +
                          '<h6 class="mb-0">' + item.name + '</h6>' +
                          '<small class="text-muted">' + formatCurrency(item.price) + ' x ' + item.quantity + '</small>' +
                          '</div>' +
                          '<div>' +
                          '<div class="btn-group btn-group-sm">' +
                          '<button class="btn btn-outline-secondary" onclick="updateQuantity(' + item.id + ', -1)">-</button>' +
                          '<span class="btn btn-outline-secondary">' + item.quantity + '</span>' +
                          '<button class="btn btn-outline-secondary" onclick="updateQuantity(' + item.id + ', 1)">+</button>' +
                          '</div>' +
                          '<button class="btn btn-sm btn-outline-danger ml-2" onclick="removeFromCart(' + item.id + ')">' +
                          '<i class="fe-trash"></i>' +
                          '</button>' +
                          '</div>' +
                          '</div>' +
                          '<div class="text-right">' +
                          '<strong>' + formatCurrency(itemTotal) + '</strong>' +
                          '</div>' +
                          '</div>';
            });
            $("#btn_checkout").prop("disabled", false);
        }

        $("#cart_items").html(cartHtml);
        $("#subtotal").text(formatCurrency(subtotal));

        // Apply discount calculation
        let finalTotal = subtotal;
        if (currentDiscount && cart.length > 0) {
            finalTotal = subtotal - currentDiscount.amount;
            $('#discount_section').show();
            $('#discount_amount').text('- ' + formatCurrency(currentDiscount.amount));
            $('#discount_description').text(currentDiscount.description);
        } else {
            $('#discount_section').hide();
        }

        $("#total").text(formatCurrency(finalTotal));
    };
</script>
<div class="modal fade" id="manualDiscountModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Terapkan Diskon Manual</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="manualDiscountForm">
                    <div class="form-group">
                        <label>Tipe Diskon</label>
                        <div>
                            <div class="custom-control custom-radio custom-control-inline">
                                <input type="radio" id="discount_percentage" name="discount_type" value="percentage" class="custom-control-input" checked>
                                <label class="custom-control-label" for="discount_percentage">Persentase (%)</label>
                            </div>
                            <div class="custom-control custom-radio custom-control-inline">
                                <input type="radio" id="discount_fixed" name="discount_type" value="fixed" class="custom-control-input">
                                <label class="custom-control-label" for="discount_fixed">Nominal (Rp)</label>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="discount_value">Nilai Diskon</label>
                        <div class="input-group">
                            <input type="number" class="form-control" id="discount_value" min="0" step="0.01" placeholder="0" required>
                            <div class="input-group-append">
                                <span class="input-group-text" id="discount_unit">%</span>
                            </div>
                        </div>
                        <small class="text-muted">Maksimal diskon: <span id="max_discount_info">100%</span></small>
                    </div>
                    <div class="form-group">
                        <label for="discount_reason">Alasan Diskon (Opsional)</label>
                        <input type="text" class="form-control" id="discount_reason" maxlength="100" placeholder="Contoh: Pelanggan VIP, Barang cacat">
                    </div>
                    <div class="alert alert-info">
                        <small>
                            <strong>Preview:</strong><br>
                            Subtotal: <span id="preview_subtotal">Rp 0</span><br>
                            Diskon: <span id="preview_discount">Rp 0</span><br>
                            <strong>Total: <span id="preview_total">Rp 0</span></strong>
                        </small>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-warning" onclick="confirmManualDiscount()">Terapkan Diskon</button>
            </div>
        </div>
    </div>
</div>

</body>
</html>