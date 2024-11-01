<?php
/**
 * Created by Shipox.
 * User: Shipox
 * Date: 11/8/2017
 * Time: 2:41 PM
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 *  Shipox Instance
 */
final class Shipox {


    /**
     * @var string
     */
    public $version = SHIPOX_VERSION;

    /**
     * @var null
     */
    public $wing = null;

    /**
     * @var null
     */
    public $api = null;

    /**
     * @var null
     */
    public $log = null;

    /**
     * @var null
     */
    protected static $instance = null;

    /**
     * @return null|Shipox
     */
    public static function instance() {
        if ( is_null( self::$instance ) ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Shipox constructor.
     */
    public function __construct() {
        $this->includes();
        $this->init_classes();
        $this->init_hooks();
    }

    /**
     *  Initialize Assets
     */
    public function load_admin_scripts() {
        wp_enqueue_style( 'shipox_admin_css', SHIPOX_ASSETS_URL . 'css/admin-style.css', array(), $this->version );

        wp_register_script( 'shipox_admin_ajax', SHIPOX_ASSETS_URL . 'js/shipox-settings.js', array( 'jquery' ), $this->version, true );
        wp_register_script( 'shipox_admin_order_meta', SHIPOX_ASSETS_URL . 'js/shipox-order-meta.js', array( 'jquery' ), $this->version, true );
        wp_localize_script(
            'shipox_admin_ajax',
            'shipoxAjax',
            array(
                'ajax_url'   => admin_url( 'admin-ajax.php' ),
                'ajax_nonce' => wp_create_nonce( 'shipox-wp-woocommerse-plugin' ),
            )
        );

        $post_id = '';
        if ( ! empty( $_GET['page'] ) && 'wc-orders' === $_GET['page'] && ! empty( $_GET['id'] ) ) { //phpcs:ignore WordPress.Security.NonceVerification.Recommended
            $post_id = intval( wp_unslash( $_GET['id'] ) ); //phpcs:ignore WordPress.Security.NonceVerification.Recommended
        }

        if ( empty( $post_id ) ) {
            if ( is_admin() && isset( $_GET['post'] ) && isset( $_GET['action'] ) && 'edit' === $_GET['action'] ) {
                $post_type = get_post_type( $post_id );

                if ( 'shop_order' === $post_type ) {
                    $post_id = intval( wp_unslash( $_GET['post'] ) ); //phpcs:ignore WordPress.Security.NonceVerification.Recommended
                }
            }
        }

        wp_localize_script(
            'shipox_admin_order_meta',
            'shipox_order_meta',
            array(
                'ajax_url'                  => admin_url( 'admin-ajax.php' ),
                'order_create_nonce'        => wp_create_nonce( 'shipox-wp-admin-meta-order-create' ),
                'load_package_prices_nonce' => wp_create_nonce( 'shipox-wp-admin-meta-load-package-prices' ),
                'post_id'                   => $post_id,
                'base_country'              => WC()->countries->get_base_country(),
            )
        );
    }

    /**
     *  Includes
     */
    private function includes() {
        include_once( SHIPOX_ABSPATH . 'helpers/functions.php' );
        include_once( SHIPOX_ABSPATH . 'helpers/woocommerce-functions.php' );
        include_once( SHIPOX_ABSPATH . 'constants/class-shipox-log-status.php' );
        include_once( SHIPOX_ABSPATH . 'includes/class-shipox-logs.php' );
        include_once( SHIPOX_ABSPATH . 'includes/class-shipox-options.php' );
        include_once( SHIPOX_ABSPATH . 'includes/class-shipox-menu-type.php' );
        include_once( SHIPOX_ABSPATH . 'includes/class-shipox-courier-type.php' );
        include_once( SHIPOX_ABSPATH . 'includes/class-shipox-payment-type.php' );
        include_once( SHIPOX_ABSPATH . 'includes/class-shipox-package-type.php' );
        include_once( SHIPOX_ABSPATH . 'includes/class-shipox-shipping-options.php' );
        include_once( SHIPOX_ABSPATH . 'includes/class-shipox-vehicle-type.php' );
        include_once( SHIPOX_ABSPATH . 'includes/class-shipox-status-mapping.php' );
        include_once( SHIPOX_ABSPATH . 'includes/class-shipox-wcfm-integration.php' );
        include_once( SHIPOX_ABSPATH . 'includes/class-shipox-api-helper.php' );
        include_once( SHIPOX_ABSPATH . 'includes/class-shipox-order-helper.php' );
        include_once( SHIPOX_ABSPATH . 'includes/class-shipox-settings-helper.php' );
        include_once( SHIPOX_ABSPATH . 'includes/class-shipox-backend-actions.php' );
        include_once( SHIPOX_ABSPATH . 'includes/class-shipox-frontend-actions.php' );
        include_once( SHIPOX_ABSPATH . 'includes/class-shipox-api-client.php' );
        include_once( SHIPOX_ABSPATH . 'includes/class-shipox-cron-job.php' );
        include_once( SHIPOX_ABSPATH . 'includes/class-shipox-install.php' );
        include_once( SHIPOX_ABSPATH . 'includes/class-shipox-wc-rest.php' );
    }

    /**
     *  Init Classes
     */
    private function init_classes() {
        $this->wing['options']          = $this->init_options();
        $this->log                      = new Shipox_Logs();
        $this->wing['menu-type']        = new Shipox_Menu_Type();
        $this->wing['courier-type']     = new Shipox_Courier_Type();
        $this->wing['payment-type']     = new Shipox_Payment_Type();
        $this->wing['package-type']     = new Shipox_Package_Type();
        $this->wing['shipping-options'] = new Shipox_Shipping_Options();
        $this->wing['statuses']         = new Shipox_Status_Mapping();
        $this->wing['vehicle-type']     = new Shipox_Vehicle_Type();
        $this->api                      = new Shipox_Api_Client();
        $this->wing['api-helper']       = new Shipox_Api_Helper();
        $this->wing['order-helper']     = new Shipox_Order_Helper();
        $this->wing['settings-helper']  = new Shipox_Settings_Helper();

        new Shipox_Cron_Job();
    }

    /**
     * Hook into actions and filters.
     * @since 2.0
     */
    private function init_hooks() {
        register_activation_hook( SHIPOX_PLUGIN_FILE, array( 'Shipox_Install', 'install' ) );

        $this->init();

        add_action( 'admin_enqueue_scripts', array( $this, 'load_admin_scripts' ) );
    }

    /**
     *  Init Function
     */
    public function init() {
        add_action( 'before_woocommerce_init', array( $this, 'enable_woocommerce_hpos_compatibility' ) );
        add_action( 'woocommerce_shipping_init', array( $this, 'init_shipping_method' ) );
        add_action( 'woocommerce_shipping_methods', array( $this, 'add_shipping_method' ) );
        add_filter( 'woocommerce_rest_api_get_rest_namespaces', array( $this, 'init_wc_rest_api' ) );

        $merchant_config = $this->wing['options']['merchant_config'];
        $service_config  = $this->wing['options']['service_config'];
    }

    /**
     *  Enable Woocommerce HPOS Compatibility
     *  Guideline: https://github.com/woocommerce/woocommerce/wiki/High-Performance-Order-Storage-Upgrade-Recipe-Book
     *
     * @return void
     */
    public function enable_woocommerce_hpos_compatibility() {
        if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
            \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', SHIPOX_PLUGIN_FILE );
        }
    }

    /**
     *  Add Shipping Method
     */
    public function init_shipping_method() {
        if ( ! class_exists( 'Shipox_Shipping_Method' ) ) {
            include_once( SHIPOX_ABSPATH . 'includes/class-shipox-shipping-method.php' );
        }
    }

    /**
     * @param $methods
     * @return array
     */
    function add_shipping_method( $methods ) {
        if ( class_exists( 'Shipox_Shipping_Method' ) ) {
            $methods[] = 'Shipox_Shipping_Method';
        }

        return $methods;
    }

    /**
     * Init Wing Options
     * @return array
     */
    private function init_options() {
        return array(
            'service_config'       => get_option( 'wing_service_config' ),
            'merchant_config'      => get_option( 'wing_merchant_config' ),
            'merchant_address'     => get_option( 'wing_merchant_address' ),
            'order_config'         => get_option( 'wing_order_config' ),
            'marketplace_settings' => get_option( 'wing_marketplace_settings' ),
        );
    }


    /**
     * Init Shipox WC Rest Api
     *
     * @param $controllers
     * @return array
     */
    public function init_wc_rest_api( $controllers ) {
        if ( class_exists( 'Shipox_Wc_Rest' ) ) {
            $controllers['wc/v3']['shipox'] = 'Shipox_Wc_Rest';
        }
        return $controllers;
    }
}
