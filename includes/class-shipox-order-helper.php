<?php
/**
 * Created by PhpStorm.
 * User: umidakhm
 * Date: 10/17/2018
 * Time: 3:31 PM
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class Shipox_Order_Helper
 * @package includes
 */
class Shipox_Order_Helper {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_filter( 'shipox_get_customer_geo_location', array( $this, 'shipox_get_customer_geo_location' ), 10, 2 );
		//        add_filter('shipox_get_shipping_address', array($this, 'shipox_get_shipping_address'), 10, 1);
	}

	/**
	 * @param $order
	 * @param $shipping_country
	 * @return array
	 */
	function check_wing_order_create_availability( $order, $shipping_country ) {
		$base_location                = wc_get_base_location();
		$order_config                 = shipox()->wing['options']['order_config'];
		$marketplace_country          = shipox()->wing['settings-helper']->get_country_code();
		$marketplace_country_name     = shipox()->wing['settings-helper']->get_country_name();
		$marketplace_currency         = shipox()->wing['settings-helper']->get_currency();
		$marketplace_int_availability = shipox()->wing['settings-helper']->get_international_availability();

		$response = array(
			'success' => false,
			'message' => null,
		);

		if ( $base_location['country'] === $marketplace_country ) {
			$currency = $order->get_currency();

			if ( $currency === $marketplace_currency ) {
				if ( 0 === $order_config['order_international_availability'] && ! $marketplace_int_availability && $shipping_country !== $marketplace_country ) {
					shipox()->log->write( $order->get_id() . ' - International is turned off', 'error' );
					$response['message'] = __( 'Shipox: International Order is turned off', 'shipox' );
				} else {
					$response['success'] = true;
				}
			} else {
				shipox()->log->write( $order->get_id() . ' - CURRENCY should be only ' . $marketplace_currency, 'error' );
				$response['message'] = esc_html( 'Shipox: CURRENCY should be only ' . $marketplace_currency );
			}
		} else {
			shipox()->log->write( $order->get_id() . ' - Delivery only for ' . $marketplace_country_name, 'error' );
			$response['message'] = esc_html( 'Shipox: Service only within ' . $marketplace_country_name );
		}

		return $response;
	}

	/**
	 * Update Woocommerce Order Status (If status selected from Shipox Options)
	 * @param $order
	 * @param $message
	 */
	function update_wc_order_status( $order, $message ) {
		$service_config = shipox()->wing['options']['service_config'];

		if ( isset( $service_config['next_status'] ) && ! empty( trim( $service_config['next_status'] ) ) ) {
			$next_status = trim( $service_config['next_status'] );
			$order->update_status( $next_status, $message );
		}

		$order->add_order_note( $message, 1 );
	}

	/**
	 * @param $response_array
	 * @param $order_wc
	 * @return mixed
	 */
	function shipox_get_customer_geo_location( $response_array, $order_wc ) {
		if ( ! is_a( $order_wc, 'WC_Order' ) ) {
			return $response_array;
		}

		$user_lat = $order_wc->get_meta( '_wcfmmp_user_location_lat', true );
		$user_lon = $order_wc->get_meta( '_wcfmmp_user_location_lng', true );

		if ( ! empty( $user_lat ) && ! empty( $user_lon ) ) {
			$response_array = array( $user_lat, $user_lon );
		}

		return $response_array;
	}
}
