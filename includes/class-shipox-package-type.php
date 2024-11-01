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
 *  Class Shipox_Package_Type
 */
class Shipox_Package_Type {

	/**
	 * @return array
	 */
	public function to_option_array() {
		$arr[] = array(
			'value' => 'd2',
			'label' => 'D2',
		);

		$arr[] = array(
			'value' => 'p3',
			'label' => 'P3',
		);
		$arr[] = array(
			'value' => 'p5',
			'label' => 'P5',
		);
		$arr[] = array(
			'value' => 'p10',
			'label' => 'P10',
		);
		$arr[] = array(
			'value' => 'p30',
			'label' => 'P30',
		);
		$arr[] = array(
			'value' => 'p100',
			'label' => 'P100',
		);

		$arr[] = array(
			'value' => '0.5KG',
			'label' => '0.5 Kg',
		);
		$arr[] = array(
			'value' => '1KG',
			'label' => '1 Kg',
		);
		$arr[] = array(
			'value' => '1.5KG',
			'label' => '1.5 Kg',
		);
		$arr[] = array(
			'value' => '2KG',
			'label' => '2 Kg',
		);
		$arr[] = array(
			'value' => '2.5KG',
			'label' => '2.5 Kg',
		);
		$arr[] = array(
			'value' => '3KG',
			'label' => '3 Kg',
		);
		$arr[] = array(
			'value' => '3.5KG',
			'label' => '3.5 Kg',
		);
		$arr[] = array(
			'value' => '4KG',
			'label' => '4 Kg',
		);
		$arr[] = array(
			'value' => '4.5KG',
			'label' => '4.5 Kg',
		);
		$arr[] = array(
			'value' => '5KG',
			'label' => '5 Kg',
		);
		$arr[] = array(
			'value' => '5.5KG',
			'label' => '5.5 Kg',
		);
		$arr[] = array(
			'value' => '6KG',
			'label' => '6 Kg',
		);
		$arr[] = array(
			'value' => '6.5KG',
			'label' => '6.5 Kg',
		);
		$arr[] = array(
			'value' => '7KG',
			'label' => '7 Kg',
		);
		$arr[] = array(
			'value' => '7.5KG',
			'label' => '7.5 Kg',
		);
		$arr[] = array(
			'value' => '8KG',
			'label' => '8 Kg',
		);
		$arr[] = array(
			'value' => '8.5KG',
			'label' => '8.5 Kg',
		);
		$arr[] = array(
			'value' => '9KG',
			'label' => '9 Kg',
		);
		$arr[] = array(
			'value' => '9.5KG',
			'label' => '9.5 Kg',
		);
		$arr[] = array(
			'value' => '10KG',
			'label' => '10 Kg',
		);

		return $arr;
	}

	/**
	 * @return array
	 */
	public function to_key_array() {
		$result  = array();
		$options = $this->to_option_array();
		foreach ( $options as $option ) {
			$result[ $option['value'] ] = $option['label'];
		}
		return $result;
	}

}
