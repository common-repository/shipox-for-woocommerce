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
 *  Shipox Backend Actions
 */
class Shipox_Backend_Actions {

	/**
	 * API_HELPER constructor.
	 */
	public function __construct() {
		add_action( 'woocommerce_order_status_changed', array( $this, 'wing_status_changed_action' ), 10, 3 );
		//        add_action('woocommerce_admin_order_data_after_order_details', array($this, 'wing_order_metabox'));
		//        add_action('woocommerce_process_shop_order_meta', array($this, 'wing_order_save_metabox'), PHP_INT_MAX, 3);

		add_action( 'add_meta_boxes', array( $this, 'add_order_metabox' ) );
		//      add_action( 'save_post', array( $this, 'save_order_metabox' ), 10, 2 );
		//      add_action( 'woocommerce_process_shop_order_meta', array( $this, 'save_order_metabox' ), 10, 2 );

		add_action( 'wp_ajax_shipox_admin_get_order_packages', array( $this, 'get_order_packages' ) );
		add_action( 'wp_ajax_shipox_admin_order_create_awb', array( $this, 'create_order_awb' ) );

	}

	/**
	 * Create Shipox order by Order WC Actions
	 * @param $order_id
	 * @param $from_status
	 * @param $to_status
	 */
	function wing_status_changed_action( $order_id, $from_status, $to_status ) {
		$service_config  = shipox()->wing['options']['service_config'];
		$order_config    = shipox()->wing['options']['order_config'];
		$order           = wc_get_order( $order_id );
		$shipping_method = $order->get_meta( '_wing_order_package' );
		$order_number    = get_post_meta( $order->get_id(), '_wing_order_number', true );

		if ( 'wc-' . $to_status === $service_config['auto_push'] && false === strpos( $shipping_method, 'wing_' ) && empty( $order_number ) ) {
			$shipping_country = $order->get_shipping_country();
			$availability     = shipox()->wing['order-helper']->check_wing_order_create_availability( $order, $shipping_country );

			if ( $availability['success'] ) {
				$is_new_model = shipox()->wing['settings-helper']->is_new_model_enabled();
				if ( $is_new_model ) {
					// New Model
					$price_list       = shipox()->wing['api-helper']->get_wing_packages_v2( $order, $shipping_country );
					$to_lat_lon       = $price_list['data']['lat_lon'];
					$customer_lat_lon = explode( ',', $to_lat_lon );

					if ( $price_list['success'] ) {
						$package_list          = $price_list['data']['list'];
						$auto_selected_package = shipox()->wing['api-helper']->get_proper_package_v2( $order_config['order_default_courier_type'], $package_list );

						if ( $auto_selected_package ) {
							$weight         = $price_list['data']['weight'];
							$is_domestic    = $price_list['data']['is_domestic'];
							$country        = shipox()->wing['api-helper']->get_country_wing_id( $shipping_country );
							$package_string = $auto_selected_package['id'] . '-' . $auto_selected_package['price']['id'] . '-' . $weight . '-' . ( $is_domestic ? '1' : '0' );

							shipox()->wing['api-helper']->push_order_to_wing_with_package_new_model( $order, $package_string, $customer_lat_lon, $country );
						} else {
							$order->add_order_note( esc_html__( 'Shipox: Could not find proper package!', 'shipox' ) );
							shipox()->log->write( $package_list, 'package-error' );
							shipox()->log->write( 'Default Courier Type: ' . $order_config['order_default_courier_type'], 'package-error' );
						}
					} else {
						$order->add_order_note( esc_html( $price_list['message'] ) );
						shipox()->log->write( $price_list, 'package-error' );
						shipox()->log->write( 'Shipping Country: ' . $shipping_country, 'package-error' );
					}
				} else {
					// Old Model

					$packages = shipox()->wing['api-helper']->get_wing_packages( $order, $shipping_country );

					if ( $packages['success'] ) {
						$to_lat_lon       = $packages['data']['lat_lon'];
						$customer_lat_lon = explode( ',', $to_lat_lon );
						$package_list     = $packages['data']['list'];

						$auto_selected_package_id = shipox()->wing['api-helper']->get_proper_package( $order_config['order_default_courier_type'], $package_list );

						if ( intval( $auto_selected_package_id ) > 0 ) {
							shipox()->wing['api-helper']->push_order_to_wing_with_package( $order, $auto_selected_package_id, $customer_lat_lon );
						} else {
							$order->add_order_note( 'Shipox: Could not find proper package!', 0 );
							shipox()->log->write( $package_list, 'package-error' );
							shipox()->log->write( 'Default Courier Type: ' . $order_config['order_default_courier_type'], 'package-error' );
						}
					} else {
						$order->add_order_note( $packages['message'], 0 );
						shipox()->log->write( $packages, 'package-error' );
						shipox()->log->write( 'Shipping Country: ' . $shipping_country, 'package-error' );
					}
				}
			} else {
				$order->add_order_note( $availability['message'], 0 );
				shipox()->log->write( $order, 'availability-error' );
				shipox()->log->write( 'Shipping Country: ' . $shipping_country, 'availability-error' );
			}
		} elseif ( 'cancelled' === strtolower( $to_status ) && ! empty( $order_number ) ) {
			$data = array(
				'note'   => 'Order Cancelled by Customer',
				'reason' => 'Order Cancelled by Customer',
				'status' => 'cancelled',
			);

			$order_id = get_post_meta( $order->get_id(), '_wing_order_id', true );
			$response = shipox()->api->update_order_status( $order_id, $data );

			if ( $response['success'] ) {
				$order->add_order_note( $order_number . ' is cancelled successfully', 1 );
			} else {
				$order->add_order_note( $response['data']['message'] );
				shipox()->log->write( $response, 'order-error' );
			}
		}
	}

	/**
	 *  Add Shipox Order Meta Box
	 */
	public function add_order_metabox() {
		$screen = shipox_woo_custom_orders_table_use_enabled() ? wc_get_page_screen_id( 'shop-order' ) : 'shop_order';

		add_meta_box(
			'shipox-order-meta-box',
			__( 'Shipox', 'shipox' ),
			array( $this, 'render_order_metabox' ),
			$screen,
			'advanced',
			'high'
		);

	}

	/**
	 * Render Shipox Order Metabox
	 *
	 * @param $object
	 * @return void
	 */
	public function render_order_metabox( $object ) {
		wp_enqueue_script( 'shipox_admin_order_meta' );

		?>
		<div class="wc-shipox-meta-container" id="wc-shipox-meta-container"><div class="response"></div><div class="inner"></div></div>
		<?php
	}

	/**
	 * Get Order Packages
	 *
	 * @return void
	 */
	public function get_order_packages() {
		check_ajax_referer( 'shipox-wp-admin-meta-load-package-prices', 'security' );

		if ( ! current_user_can( 'edit_shop_orders' ) || ! isset( $_POST['order_id'] ) ) {
			wp_die( -1 );
		}

		$order_id = absint( wp_unslash( $_POST['order_id'] ) );

		if ( 0 === $order_id ) {
			wp_die( -1 );
		}

		$order = wc_get_order( $order_id );

		$shipping_country = $order->get_shipping_country();

		if ( empty( $shipping_country ) ) {
			$error_message = esc_html__( 'Shipping Country is not exists', 'shipox' );

			include_once SHIPOX_ABSPATH . 'views/error.php';
			wp_die();
		}

		$order_number = $order->get_meta( '_wing_order_number' );
		if ( ! empty( $order_number ) ) {
			$order_id        = $order->get_meta( '_wing_order_id' );
			$shipping_method = $order->get_meta( '_wing_order_package' );

			$tracking_url     = shipox()->wing['api-helper']->get_tracking_url() . '/track?id=' . $order_number;
			$airwaybill       = shipox()->wing['api-helper']->get_airwaybill( $order_id );
			$airwaybill_zebra = shipox()->wing['api-helper']->get_airwaybill_zebra( $order_id );

			include_once SHIPOX_ABSPATH . 'views/shipox-order.php';
			wp_die();
		}

		$selected_package      = null;
		$is_packages_available = false;
		$packages              = null;
		$package_options       = array(
			0 => esc_html__( 'Select Package', 'shipox' ),
		);
		$to_lat_lon            = null;
		$error_message         = null;

		$availability = shipox()->wing['order-helper']->check_wing_order_create_availability( $order, $shipping_country );

		if ( ! $availability['success'] ) {
			$error_message = $availability['message'];

			include_once SHIPOX_ABSPATH . 'views/error.php';
			wp_die();
		}

		$is_new_model = shipox()->wing['settings-helper']->is_new_model_enabled();

		if ( $is_new_model ) {
			$price_list = shipox()->wing['api-helper']->get_wing_packages_v2( $order, $shipping_country );

			if ( ! $price_list['success'] ) {
				$error_message = $price_list['message'];

				include_once SHIPOX_ABSPATH . 'views/error.php';
				wp_die();
			}

			$list        = $price_list['data']['list'];
			$weight      = $price_list['data']['weight'];
			$is_domestic = $price_list['data']['is_domestic'];
			$to_lat_lon  = $price_list['data']['lat_lon'];

			foreach ( $list as $list_item ) {
				$price_item = $list_item['price'];

				$name = $list_item['name'];
				if ( isset( $list_item['supplier'] ) ) {
					$name = $list_item['supplier']['name'] . ' - ' . $list_item['name'];
				}

				$method                               = $list_item['id'] . '-' . $price_item['id'] . '-' . $weight . '-' . ( $is_domestic ? '1' : '0' );
				$currency                             = $price_item['currency']['code'];
				$package_options[ 'wing_' . $method ] = $name . ' (' . $price_item['total'] . ' ' . $currency . ')';
			}
		} else {
			$packages = shipox()->wing['api-helper']->get_wing_packages( $order, $shipping_country );

			if ( ! $packages['success'] ) {
				$error_message = $packages['message'];

				include_once SHIPOX_ABSPATH . 'views/error.php';
				wp_die();
			}

			$selected_package = false !== strpos( $shipping_method, 'wing_' ) ? $shipping_method : null;

			$to_lat_lon = $packages['data']['lat_lon'];
			foreach ( $packages['data']['list'] as $list_item ) {
				$package_list = $list_item['packages'];
				$name         = $list_item['name'];

				foreach ( $package_list as $package_item ) {
					$label    = $package_item['delivery_label'];
					$price    = $package_item['price']['total'];
					$currency = $package_item['price']['currency']['code'];
					$package_options[ 'wing_' . $package_item['id'] ] = $name . ' - ' . $label . ' (' . $price . ' ' . $currency . ')';
				}
			}
		}

		include_once SHIPOX_ABSPATH . 'views/package-prices.php';

		wp_die();
	}

	/**
	 * Create Order AWB
	 *
	 * @return void
	 */
	public function create_order_awb() {
		check_ajax_referer( 'shipox-wp-admin-meta-order-create', 'security' );

		if ( ! current_user_can( 'edit_shop_orders' ) || ! isset( $_POST['order_id'], $_POST['items'] ) ) {
			wp_die( -1 );
		}

		$order_id = absint( $_POST['order_id'] );

		if ( 0 === $order_id ) {
			wp_send_json_error( $this->render_error_message( __( 'Order ID is mandatory', 'shipox' ) ) );
			wp_die();
		}

		$order = wc_get_order( $order_id );

		if ( ! $order ) {
			wp_send_json_error( $this->render_error_message( __( 'Order has not found', 'shipox' ) ) );
			wp_die();
		}

		$data = array();
		parse_str( wp_unslash( $_POST['items'] ), $data );

		$shipping_method = wc_clean( $data['wing_package'] );
		if ( 0 == $shipping_method ) { // phpcs:ignore WordPress.PHP.StrictComparisons.LooseComparison
			wp_send_json_error( $this->render_error_message( __( 'Package must be selected', 'shipox' ) ) );
			wp_die();
		}

		$box_count        = wc_clean( $data['shipox_box_count'] );
		$shipping_country = $order->get_shipping_country();
		$package_data     = explode( '_', $shipping_method );
		$to_lat_lon       = wc_clean( $data['wing_custom_lat_lon'] );
		$customer_lat_lon = explode( ',', $to_lat_lon );
		$country          = shipox()->wing['api-helper']->get_country_wing_id( $shipping_country );

		if ( ! $country ) {
			wp_send_json_error( $this->render_error_message( __( 'Country has not found', 'shipox' ) ) );
			wp_die();
		}

		$error = null;
		if ( count( $package_data ) > 0 ) {
			$order->update_meta_data( '_wing_order_package', $shipping_method );
			$order->save();

			$is_new_model = shipox()->wing['settings-helper']->is_new_model_enabled();
			if ( $is_new_model ) {
				$error = shipox()->wing['api-helper']->push_order_to_wing_with_package_new_model( $order, $package_data[1], $customer_lat_lon, $country, $box_count );
			} else {
				$error = shipox()->wing['api-helper']->push_order_to_wing_with_package( $order, $package_data[1], $customer_lat_lon );
			}
		}

		if ( null !== $error ) {
			wp_send_json_error( $this->render_error_message( esc_html( $error ) ) );
			wp_die();
		}

		wp_send_json_success();
		wp_die();
	}

	/**
	 * Render Error Message
	 *
	 * @param $error_message
	 * @return string
	 */
	public function render_error_message( $error_message ) {
		ob_start();

		include_once SHIPOX_ABSPATH . 'views/error.php';

		return ob_get_clean();
	}

	/**
	 * @deprecated
	 * Render Shipox Order Metabox
	 * @param $object
	 */
	public function render_order_metabox_old( $object ) {
		$order = ( $object instanceof WP_Post ) ? wc_get_order( $object->ID ) : $object;

		wp_nonce_field( 'shipox_admin_order_save_action', 'shipox_admin_order_save_nonce' );

		$order_number    = $order->get_meta( '_wing_order_number' );
		$order_id        = $order->get_meta( '_wing_order_id' );
		$shipping_method = $order->get_meta( '_wing_order_package' );

		$selected_package      = null;
		$is_packages_available = false;
		$packages              = null;
		$package_options       = array(
			0 => esc_html__( 'Select Package', 'shipox' ),
		);
		$to_lat_lon            = null;
		$error_message         = null;

		if ( empty( $order_number ) ) {
			$shipping_country = $order->get_shipping_country();
			$availability     = shipox()->wing['order-helper']->check_wing_order_create_availability( $order, $shipping_country );

			if ( $availability['success'] ) {
				$is_new_model = shipox()->wing['settings-helper']->is_new_model_enabled();

				if ( $is_new_model ) {
					$price_list = shipox()->wing['api-helper']->get_wing_packages_v2( $order, $shipping_country );

					if ( $price_list['success'] ) {
						$is_packages_available = true;
						$list                  = $price_list['data']['list'];
						$weight                = $price_list['data']['weight'];
						$is_domestic           = $price_list['data']['is_domestic'];
						$to_lat_lon            = $price_list['data']['lat_lon'];

						foreach ( $list as $list_item ) {
							$price_item = $list_item['price'];

							$name = $list_item['name'];
							if ( isset( $list_item['supplier'] ) ) {
								$name = $list_item['supplier']['name'] . ' - ' . $list_item['name'];
							}

							$method                               = $list_item['id'] . '-' . $price_item['id'] . '-' . $weight . '-' . ( $is_domestic ? '1' : '0' );
							$currency                             = $price_item['currency']['code'];
							$package_options[ 'wing_' . $method ] = $name . ' (' . $price_item['total'] . ' ' . $currency . ')';
						}
					} else {
						$error_message = $packages['message'];
					}
				} else {
					$packages = shipox()->wing['api-helper']->get_wing_packages( $order, $shipping_country );

					if ( $packages['success'] ) {
						$is_packages_available = true;
						$selected_package      = false !== strpos( $shipping_method, 'wing_' ) ? $shipping_method : null;

						$to_lat_lon = $packages['data']['lat_lon'];
						foreach ( $packages['data']['list'] as $list_item ) {
							$package_list = $list_item['packages'];
							$name         = $list_item['name'];

							foreach ( $package_list as $package_item ) {
								$label    = $package_item['delivery_label'];
								$price    = $package_item['price']['total'];
								$currency = $package_item['price']['currency']['code'];
								$package_options[ 'wing_' . $package_item['id'] ] = $name . ' - ' . $label . ' (' . $price . ' ' . $currency . ')';
							}
						}
					} else {
						$error_message = $packages['message'];
					}
				}
			} else {
				$error_message = $availability['message'];
			}

			if ( $is_packages_available ) {
				echo '<div class="edit_address">';
				woocommerce_wp_text_input(
					array(
						'label'         => 'Box Count:',
						'class'         => 'wing-input-class',
						'id'            => 'shipox_box_count',
						'value'         => '1',
						'wrapper_class' => 'form-field-wide',
					)
				);
				woocommerce_wp_select(
					array(
						'id'            => 'wing_package',
						'label'         => 'Packages:',
						'value'         => $selected_package,
						'class'         => 'wing-select-field',
						'options'       => $package_options,
						'wrapper_class' => 'form-field-wide',
					)
				);
				woocommerce_wp_hidden_input(
					array(
						'id'            => 'wing_custom_lat_lon',
						'value'         => $to_lat_lon,
						'wrapper_class' => 'form-field-wide',
					)
				);
				echo '</div>';
			} else {
				echo "<div class='wing-error'>" . $error_message . '</div>';
			}
		} else {
			$airwaybill       = shipox()->wing['api-helper']->get_airwaybill( $order_id );
			$airwaybill_zebra = shipox()->wing['api-helper']->get_airwaybill_zebra( $order_id );

			echo '<div class="address">';
			echo esc_html( 'Order Number: #' . $order_number );
			echo '<br class="clear" />';
			echo "<a target=\"_blank\" href='" . shipox()->wing['api-helper']->get_tracking_url() . '/track?id=' . $order_number . "'>" . esc_html__( 'Track Order', 'shipox' ) . '</a>';
			if ( $airwaybill ) {
				echo '<br class="clear" />';
				echo "<a target=\"_blank\" href='" . $airwaybill . "'>" . esc_html__( 'Download Airwaybill', 'shipox' ) . '</a>';
			}
			if ( $airwaybill_zebra ) {
				echo '<br class="clear" />';
				echo "<a target=\"_blank\"  href='" . $airwaybill_zebra . "'>" . esc_html__( 'Download Mini Airwaybill', 'shipox' ) . '</a>';
			}
			echo '</div>';
		}
	}

	/**
	 * @deprecated
	 * Shipox Save Order Meta Box
	 * @param $post_id
	 * @param $post
	 */
	public function save_order_metabox( $post_id, $post ) {
		// Add nonce for security and authentication.
		$nonce_name   = isset( $_POST['shipox_admin_order_save_nonce'] ) ? $_POST['shipox_admin_order_save_nonce'] : ''; //phpcs:ignore WordPress.Security.NonceVerification.Missing
		$nonce_action = 'shipox_admin_order_save_action';

		// Check if nonce is valid.
		if ( ! wp_verify_nonce( $nonce_name, $nonce_action ) ) {
			return;
		}

		// Check if user has permissions to save data.
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		// Check if not an autosave.
		if ( wp_is_post_autosave( $post_id ) ) {
			return;
		}

		// Check if not a revision.
		if ( wp_is_post_revision( $post_id ) ) {
			return;
		}

		$order = wc_get_order( $post_id );

		$shipping_method  = wc_clean( $_POST['wing_package'] );
		$box_count        = wc_clean( $_POST['shipox_box_count'] );
		$shipping_country = $order->get_shipping_country();
		$package_data     = explode( '_', $shipping_method );
		$to_lat_lon       = wc_clean( $_POST['wing_custom_lat_lon'] );
		$customer_lat_lon = explode( ',', $to_lat_lon );
		$country          = shipox()->wing['api-helper']->get_country_wing_id( $shipping_country );

		if ( count( $package_data ) > 0 ) {
			$order->update_meta_data( '_wing_order_package', $shipping_method );
			$order->save();

			$is_new_model = shipox()->wing['settings-helper']->is_new_model_enabled();
			if ( $is_new_model ) {
				shipox()->wing['api-helper']->push_order_to_wing_with_package_new_model( $order, $package_data[1], $customer_lat_lon, $country, $box_count );
			} else {
				shipox()->wing['api-helper']->push_order_to_wing_with_package( $order, $package_data[1], $customer_lat_lon );
			}
		}
	}

}

new Shipox_Backend_Actions();
