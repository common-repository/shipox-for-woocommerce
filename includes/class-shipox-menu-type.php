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
 *  Class Shipox_Menu_Type
 */
class Shipox_Menu_Type {

	/**
	 * @return array
	 */
	public function to_option_array() {
		return array(
			array(
				'value' => '0',
				'label' => 'Calculate by Products\' Weight',
			),
			array(
				'value' => '2',
				'label' => 'Up to 2 KG',
			),
			array(
				'value' => '3',
				'label' => 'Up to 3 KG',
			),
			array(
				'value' => '5',
				'label' => 'Up to 5 KG',
			),
			array(
				'value' => '10',
				'label' => 'Up to 10 KG',
			),
			array(
				'value' => '30',
				'label' => 'Up to 30 KG',
			),
			array(
				'value' => '100',
				'label' => 'Up to 100 KG',
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
