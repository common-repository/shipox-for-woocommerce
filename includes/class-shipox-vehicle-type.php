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
 *  Class Shipox_Vehicle_Type
 */
class Shipox_Vehicle_Type {

	/**
	 * @return array
	 */
	public function to_option_array() {
		return array(
			array(
				'value' => 'bike',
				'label' => 'Bike',
			),
			array(
				'value' => 'sedan',
				'label' => 'Sedan',
			),
			array(
				'value' => 'minivan',
				'label' => 'Minivan',
			),
			array(
				'value' => 'panelvan',
				'label' => 'Panel Van',
			),
			array(
				'value' => 'light_truck',
				'label' => 'Light Truck',
			),
			array(
				'value' => 'refrigerated_truck',
				'label' => 'Refrigerated Truck',
			),
			array(
				'value' => 'towing',
				'label' => 'Towing',
			),
			array(
				'value' => 'relocation',
				'label' => 'Relocation',
			),
		);
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
