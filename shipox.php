<?php
/**
 * Plugin Name: Shipox for WooCommerce
 * Plugin URI: https://www.shipox.com
 * Description: Shipox DMS provides you with a complete delivery management software solution for pickup and delivery. Prioritize and assign your drivers with precision and efficiency. Our unique software suits all business types from SMEs to large companies. Shipox features such as a white label app, driver app, and real-time tracking of all delivery personnel and vehicles.
 * Version: 3.3.2
 * Requires at least: 5.6
 * Tested up to: 6.6.2
 * WC requires at least: 3.0.0
 * WC tested up to: 9.3.2
 *
 * Text Domain: shipox
 * Domain Path: /i18n/languages/
 * License: GPL v3 or later
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 *
 * @package shipox
 * @category Core
 * Author: Shipox
 * Author URI: https://shipox.com
 * @internal This file is only used when running as a feature plugin.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

// Define WC_PLUGIN_FILE.
if ( ! defined( 'SHIPOX_PLUGIN_FILE' ) ) {
    $upload_dir = wp_upload_dir( null, false );

    define( 'SHIPOX_PLUGIN_FILE', __FILE__ );
    define( 'SHIPOX_ABSPATH', dirname( SHIPOX_PLUGIN_FILE ) . '/' );
    define( 'SHIPOX_SLUG', 'wing' );
    define( 'SHIPOX_VERSION', '3.3.2' );
    define( 'SHIPOX_ASSETS_URL', plugin_dir_url( SHIPOX_PLUGIN_FILE ) . 'assets/' );
    define( 'SHIPOX_LOGS', $upload_dir['basedir'] . '/shipox-logs/' );
}


if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ), true ) ) {

    // Include the main WooCommerce class.
    if ( ! class_exists( 'Shipox' ) ) {
        include_once dirname( __FILE__ ) . '/includes/class-shipox.php';
    }

    /**
     * Main instance of Shipox.
     *
     * Returns the main instance of Shipox to prevent the need to use globals.
     *
     * @since  2.0
     * @return Shipox
     */
    function shipox() {
        return Shipox::instance();
    }

    // Global for backwards compatibility.
    $GLOBALS['shipox'] = shipox();
}

if ( ! function_exists( 'shipox_check_woocommerce_enabled' ) ) {
    /**
     * Check is Woocommerce Disabled and Show Notice
     *
     * @since 3.3.0
     * @return void
     */
    function shipox_check_woocommerce_enabled() {
        if ( ! class_exists( 'WooCommerce' ) ) {
            add_action( 'admin_notices', 'shipox_show_admin_notice' );
        }
    }
}

add_action( 'plugins_loaded', 'shipox_check_woocommerce_enabled' );

if ( ! function_exists( 'shipox_show_admin_notice' ) ) {
    /**
     * Show Admin Notice
     *
     * @return void
     */
    function shipox_show_admin_notice() {
        echo '<div class="error"><p><strong>' . esc_html__( 'WooCommerce', 'woocommerce' ) . '</strong> ' . esc_html__( 'must be installed and activated to use this', 'shipox' ) . ' <strong>' . esc_html__( 'Shipox', 'shipox' ) . '</strong>.</p></div>';
    }
}
