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
 *  Class Shipox_Payment_Type
 */
class Shipox_Payment_Type {

	/**
	 * @return array[]
	 */
	public function to_option_array() {
		return array(
			array(
				'value' => 'cash',
				'label' => 'Cash',
			),
			array(
				'value' => 'credit_balance',
				'label' => 'Credit Balance',
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
