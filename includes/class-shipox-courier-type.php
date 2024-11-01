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
 *  Class Shipox_Courier_Type
 */
class Shipox_Courier_Type {

	/**
	 * Get Service Types
	 *
	 * @return array
	 */
	public function get_service_types() {
		$service_types = shipox()->api->get_all_service_types();

		if ( ! $service_types['success'] ) {
			shipox()->log->write( $service_types['message'], 'service-type-error' );
			return array();
		}
		$response = array();
		$list     = $service_types['data']['list'];

		foreach ( $list as $item ) {
			if ( 'FBS' === $item['code'] ) {
				continue;
			}

			$response[] = array(
				'value' => $item['code'],
				'label' => $item['name'],
			);
		}

		return $response;
	}


	/**
	 * @return array
	 */
	public function to_option_array() {
		return $this->get_service_types();
	}

	/**
	 * @return array
	 */
	public function to_value_array() {
		$result  = array();
		$options = $this->to_option_array();
		foreach ( $options as $option ) {
			$result[] = $option['value'];
		}
		return $result;
	}
}
