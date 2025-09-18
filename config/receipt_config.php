<?php
/**
 * Receipt Configuration
 * Configuration settings for receipt printing system
 */

// Store Information
define('STORE_NAME', APP_NAME);
define('STORE_ADDRESS', 'Jl. Contoh No. 123, Jakarta Barat'); // Configure according to your store
define('STORE_PHONE', 'Telp: (021) 12345678'); // Configure according to your store
define('STORE_EMAIL', 'info@toko-kelontong.com'); // Configure according to your store
define('STORE_WEBSITE', 'www.toko-kelontong.com'); // Configure according to your store

// Receipt Settings
define('RECEIPT_WIDTH', 32); // Characters width for thermal printer
define('RECEIPT_PAPER_WIDTH_MM', 58); // Paper width in mm for thermal printer
define('RECEIPT_LOGO_PATH', ''); // Path to store logo (if any)
define('RECEIPT_FOOTER_MESSAGE', 'Terima kasih atas kunjungan Anda!');
define('RECEIPT_RETURN_POLICY', 'Barang yang sudah dibeli tidak dapat ditukar');

// Thermal Printer Settings
define('THERMAL_PRINTER_ENABLED', true);
define('THERMAL_PRINTER_CUT_PAPER', true); // Enable paper cut command
define('THERMAL_PRINTER_OPEN_DRAWER', false); // Enable cash drawer open command

// Receipt Appearance
define('RECEIPT_SHOW_BARCODE', false); // Show barcode on receipt
define('RECEIPT_SHOW_QR_CODE', false); // Show QR code on receipt
define('RECEIPT_SHOW_STORE_LOGO', false); // Show store logo on receipt
define('RECEIPT_SHOW_ITEM_CODE', true); // Show item codes on receipt
define('RECEIPT_CURRENCY_SYMBOL', 'Rp'); // Currency symbol

// ESC/POS Commands for thermal printers
class ESCPOSCommands {
    // Text formatting
    const NORMAL = "\x1B\x21\x00";
    const BOLD = "\x1B\x21\x08";
    const UNDERLINE = "\x1B\x2D\x01";
    const DOUBLE_HEIGHT = "\x1B\x21\x10";
    const DOUBLE_WIDTH = "\x1B\x21\x20";

    // Alignment
    const ALIGN_LEFT = "\x1B\x61\x00";
    const ALIGN_CENTER = "\x1B\x61\x01";
    const ALIGN_RIGHT = "\x1B\x61\x02";

    // Paper handling
    const FEED_LINE = "\x0A";
    const FEED_3_LINES = "\x0A\x0A\x0A";
    const CUT_PAPER = "\x1D\x56\x41\x10"; // Partial cut
    const CUT_PAPER_FULL = "\x1D\x56\x00"; // Full cut

    // Cash drawer
    const OPEN_DRAWER = "\x1B\x70\x00\x19\x19";
}

/**
 * Get store information as array
 */
function getStoreInfo() {
    return [
        'name' => STORE_NAME,
        'address' => STORE_ADDRESS,
        'phone' => STORE_PHONE,
        'email' => STORE_EMAIL,
        'website' => STORE_WEBSITE
    ];
}

/**
 * Get receipt settings as array
 */
function getReceiptSettings() {
    return [
        'width' => RECEIPT_WIDTH,
        'paper_width_mm' => RECEIPT_PAPER_WIDTH_MM,
        'logo_path' => RECEIPT_LOGO_PATH,
        'footer_message' => RECEIPT_FOOTER_MESSAGE,
        'return_policy' => RECEIPT_RETURN_POLICY,
        'show_barcode' => RECEIPT_SHOW_BARCODE,
        'show_qr_code' => RECEIPT_SHOW_QR_CODE,
        'show_logo' => RECEIPT_SHOW_STORE_LOGO,
        'show_item_code' => RECEIPT_SHOW_ITEM_CODE,
        'currency_symbol' => RECEIPT_CURRENCY_SYMBOL
    ];
}

/**
 * Get thermal printer settings
 */
function getThermalPrinterSettings() {
    return [
        'enabled' => THERMAL_PRINTER_ENABLED,
        'cut_paper' => THERMAL_PRINTER_CUT_PAPER,
        'open_drawer' => THERMAL_PRINTER_OPEN_DRAWER
    ];
}
?>