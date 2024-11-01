<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Shipox WC_REST Controller
 */
class Shipox_Wc_Rest {

	/**
	 * WC Namespace
	 * @var string
	 */
	protected $namespace = 'wc/v3';

	/**
	 * Shipox Status Update Endpoint Path
	 * @var string
	 */
	protected $rest_base = 'shipox/orders/status/update';

	/**
	 *  WC Register Routes
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'update_order_status' ),
				'permission_callback' => array( $this, 'check_endpoint_permission' ),
			)
		);
	}

	/**
	 * Check Shipox Endpoint Permission/Auth
	 *
	 * @param $request
	 * @return bool|WP_Error
	 */
	public function check_endpoint_permission( $request ) {
		if ( ! is_user_logged_in() ) {
			return new WP_Error( 'permission-error', __( 'You must specify a valid username and password.', 'shipox' ), array( 'status' => 403 ) );
		}

		return true;
	}

	/**
	 * Order Status Update from Server
	 * @param $request
	 * @return string[]
	 */
	public function update_order_status( $request ) {
		$params = $request->get_params();

		if ( isset( $params['source_id'] ) && isset( $params['order_number'] ) && isset( $params['status'] ) ) {
			$source_id    = intval( $params['source_id'] );
			$order_number = trim( $params['order_number'] );
			$status       = strtolower( trim( $params['status'] ) );

			if ( 'completed' === $status || 'cancelled' === $status ) {
				$wc_order        = wc_get_order( $source_id );
				$wc_order_number = trim( get_post_meta( $source_id, '_wing_order_number', true ) );

				if ( $wc_order && $order_number === $wc_order_number ) {
					$message = __( 'Shipox: Order Status has been auto updated.', 'shipox' );
					$wc_order->update_status( $status, $message );
				}
			}
		}
		return array( 'success' => true );
	}
}
