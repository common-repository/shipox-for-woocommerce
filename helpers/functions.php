<?php
/**
 * Shipox - Functions
 *
 * @package shipox
 * @version 3.0.0
 * @since   3.0.0
 */

if ( ! function_exists( 'shipox_get_array_string_value' ) ) {
	/**
	 * Get Array Value by Key
	 *
	 * @param $array
	 * @param $key
	 * @param $default_value
	 * @return string
	 */
	function shipox_get_array_string_value( $array, $key, $default_value = '' ) {
		if ( empty( $array[ $key ] ) ) {
			return $default_value;
		}

		return (string) $array[ $key ];
	}
}

if ( ! function_exists( 'shipox_get_array_value' ) ) {
	/**
	 * Get Array Value by Key
	 *
	 * @param $array
	 * @param $key
	 * @return array
	 */
	function shipox_get_array_value( $array, $key ) {
		if ( empty( $array[ $key ] ) || ! is_array( $array[ $key ] ) ) {
			return array();
		}

		return $array[ $key ];
	}
}

if ( ! function_exists( 'shipox_get_array_bool_value' ) ) {
	/**
	 * Get Array Bool Value by Key
	 *
	 * @param $array
	 * @param $key
	 * @param $default_value
	 * @return bool
	 */
	function shipox_get_array_bool_value( $array, $key, $default_value = false ) {
		if ( ! isset( $array[ $key ] ) ) {
			return $default_value;
		}

		return (bool) $array[ $key ];
	}
}
