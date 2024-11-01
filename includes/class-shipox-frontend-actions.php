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
 *  Class Shipox Frontend Actions
 */
class Shipox_Frontend_Actions {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'woocommerce_review_order_before_cart_contents', array( $this, 'wing_validate_order' ), 10 );
		add_action( 'woocommerce_after_checkout_validation', array( $this, 'wing_validate_order' ), 10 );
		add_filter( 'woocommerce_available_payment_gateways', array( $this, 'wing_available_payment_gateways' ) );
		//        add_action('woocommerce_checkout_order_processed', array($this, 'wing_order_processed'), 10, 1);
		add_action( 'woocommerce_thankyou', array( $this, 'wing_order_processed' ), 10, 1 );
	}

	/**
	 * @param $posted
	 */
	public function wing_validate_order( $posted ) {
		$packages = WC()->shipping->get_packages();

		$chosen_methods = WC()->session->get( 'chosen_shipping_methods', null );

		$is_wing_chosen = false;
		if ( is_array( $chosen_methods ) ) {

			foreach ( $chosen_methods as $method ) {
				if ( strpos( $method, 'wing_' ) !== false ) {
					$is_wing_chosen = true;
					break;
				}
			}
		}

		if ( is_array( $chosen_methods ) && $is_wing_chosen ) {

			foreach ( $packages as $i => $package ) {

				if ( strpos( $chosen_methods[ $i ], 'wing_' ) === false ) {
					continue;
				}

				$shipox_shipping_method = new Shipox_Shipping_Method();
				$weight_limit           = (int) $shipox_shipping_method->settings['weight'];
				$weight                 = 0;

				foreach ( $package['contents'] as $item_id => $values ) {
					$_product = $values['data'];
					$weight   = $weight + (float) $_product->get_weight() * (int) $values['quantity'];
				}

				$weight = wc_get_weight( $weight, 'kg' );

				if ( $weight > $weight_limit ) {
					$message      = sprintf( __( 'Sorry, %1$d kg exceeds the maximum weight of %2$d kg for %3$s', 'shipox' ), $weight, $weight_limit, $shipox_shipping_method->title ); //phpcs:ignore
					$message_type = 'error';
					if ( ! wc_has_notice( $message, $message_type ) ) {
						wc_add_notice( $message, $message_type );
					}
				}
			}
		}
	}

	/**
	 * Remove COD Payment Gateway if order is International
	 * @param $gateways
	 * @return mixed
	 */
	public function wing_available_payment_gateways( $gateways ) {
		if ( isset( WC()->session ) ) {
			$chosen_methods = WC()->session->get( 'chosen_shipping_methods' );

			$is_wing_chosen = false;
			if ( is_array( $chosen_methods ) ) {

				foreach ( $chosen_methods as $method ) {
					if ( strpos( $method, 'wing_' ) !== false ) {
						$is_wing_chosen = true;
						break;
					}
				}
			}

			if ( is_array( $chosen_methods ) && $is_wing_chosen ) {
				$packages = WC()->shipping->get_packages();

				if ( ! empty( $packages ) ) {
					$address = isset( $packages[0]['destination'] ) ? $packages[0]['destination'] : null;

					if ( ! empty( $address['country'] ) ) {
						//  $country = shipox()->wing['api-helper']->get_country_wing_id( $address['country'] );

						if ( ! shipox()->wing['api-helper']->is_domestic_by_code( $address['country'] ) ) {
							unset( $gateways['cod'] );
						}
					}
				}
			}
		}

		return $gateways;
	}


	/**
	 * @param $order_id
	 */
	public function wing_order_processed( $order_id ) {
		if ( ! $order_id ) {
			return;
		}

		$order = wc_get_order( $order_id );

		if ( ! $order ) {
			return;
		}

		if ( ! $order->get_meta( '_thankyou_action_done' ) ) {
			$shipping_country = $order->get_shipping_country();
			$shipping_methods = WC()->session->get( 'chosen_shipping_methods', array() );

			foreach ( $shipping_methods as $chosen_method ) {

				if ( strpos( $chosen_method, 'wing_' ) !== false ) {
					if ( 'processing' === strtolower( $order->get_status() ) ) {
						$shipping_method_data     = explode( '_', $chosen_method );
						$country                  = shipox()->wing['api-helper']->get_country_wing_id( $shipping_country );
						$customer_lat_lon_address = shipox()->wing['api-helper']->get_address_location_by_order( $order );

						if ( count( $shipping_method_data ) > 0 ) {
							$order->update_meta_data( '_wing_order_package', $chosen_method );
							$order->save();

							$is_new_model = shipox()->wing['settings-helper']->is_new_model_enabled();
							if ( $is_new_model ) {
								shipox()->wing['api-helper']->push_order_to_wing_with_package_new_model( $order, $shipping_method_data[1], $customer_lat_lon_address, $country );
							} else {
								shipox()->wing['api-helper']->push_order_to_wing_with_package( $order, $shipping_method_data[1], $customer_lat_lon_address );
							}
						}
					} else {
						$order->update_meta_data( '_wing_order_package', $chosen_method );
						$order->save();
						shipox()->log->write( sprintf( 'Payment Title %s and Order status: %s for Order ID: %s', $order->get_payment_method_title(), $order->get_status(), $order_id ), 'payment-hold' );
						$order->add_order_note( sprintf( 'Shipox: Order is not pushed to Shipox because the Payment (Method: %s) is not paid yet.', $order->get_payment_method_title() ), 0 );
					}
				}
			}

			$order->update_meta_data( '_thankyou_action_done', true );
		}
	}

}

new Shipox_Frontend_Actions();
