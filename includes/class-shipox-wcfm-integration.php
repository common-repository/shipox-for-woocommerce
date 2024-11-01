<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 *  WCFM Integration with Shipox
 */
class Shipox_Wcfm_Integration {

	/**
	 * Construction.
	 */
	public function __construct() {
		add_filter( 'wcfmmp_settings_fields_shipping', array( $this, 'wcfmmp_add_shipox_shipping_fields' ), 20 );
		add_action( 'wcfm_vendor_settings_before_update', array( $this, 'wcfmmp_shipox_vendor_shipping_settings_update' ), 10, 2 );
		add_action( 'wcfm_vendor_shipping_settings_update', array( $this, 'wcfmmp_shipox_vendor_shipping_settings_update' ), 10, 2 );

		add_filter( 'shipox_wcfm_get_sellers_by_product_on_order', array( $this, 'shipox_wcfm_get_sellers_by_product_on_order' ), 10, 2 );
		add_filter( 'shipox_wcfm_get_vendor_pickup_address', array( $this, 'shipox_wcfm_get_vendor_pickup_address' ), 10, 2 );
	}

	/**
	 * Generate Shipox Fields on WCFM
	 * @param $settings_fields_general
	 * @return mixed
	 */
	function wcfmmp_add_shipox_shipping_fields( $settings_fields_general ) {
		global $wp;
		$vendor_id       = 0;
		$vendor_admin_id = 0;

		if ( isset( $wp->query_vars['wcfm-vendors-manage'] ) && ! empty( $wp->query_vars['wcfm-vendors-manage'] ) ) {
			$vendor_id       = $wp->query_vars['wcfm-vendors-manage'];
			$vendor_id       = absint( $vendor_id );
			$vendor_admin_id = $vendor_id;
			//$user_id = apply_filters( 'wcfm_current_vendor_id', get_current_user_id() );

			$wcfmmp_shipox_shipping = get_user_meta( $vendor_id, '_wcfmmp_shipox_settings', true );
			if ( ! $wcfmmp_shipox_shipping ) {
				$wcfmmp_shipox_shipping = get_option( '_wcfmmp_shipox_settings', array() );
			}

			$settings_fields_general['enable_shipox'] = array(
				'label'       => __( 'Enable Shipox', 'shipox' ),
				'type'        => 'checkbox',
				'class'       => 'wcfm-checkbox wcfm_ele',
				'label_class' => 'wcfm_title checkbox_title checkbox-title wcfm_ele',
				'name'        => 'wcfmmp_shipox_settings[_enable_shipox]',
				'value'       => shipox_get_array_bool_value( $wcfmmp_shipox_shipping, '_enable_shipox' ),
				'hints'       => __( 'Enable/Disable Shipox for this Vendor', 'shipox' ),
			);

			$settings_fields_general['shipox_pickup_location'] = array(
				'label'       => __( 'Pickup Location (Latitude & Longitude)', 'shipox' ),
				'type'        => 'text',
				'priority'    => 50,
				'class'       => 'wcfm-text wcfm_ele wcfm_name_input',
				'label_class' => 'wcfm_title wcfm_ele',
				'name'        => 'wcfmmp_shipox_settings[_shipox_pickup_location]',
				'placeholder' => '25.0683252,55.142911',
				'value'       => shipox_get_array_string_value( $wcfmmp_shipox_shipping, '_shipox_pickup_location' ),
				'hints'       => __( 'Add Latitude & Longitude Parameters for Pickup Location of this Vendor. Ex: 25.0683252,55.142911', 'shipox' ),
			);

		}

		return $settings_fields_general;
	}

	/**
	 * Update Meta Fields on WCFM
	 * @param $user_id
	 * @param $wcfm_settings_form
	 */
	public function wcfmmp_shipox_vendor_shipping_settings_update( $user_id, $wcfm_settings_form ) {
		if ( ! apply_filters( 'wcfm_is_allow_store_shipping', true ) || ! apply_filters( 'wcfm_is_allow_vshipping_settings', true ) || ! isset( $wcfm_settings_form['wcfmmp_shipox_settings'] ) ) {
			return;
		}

		update_user_meta( $user_id, '_wcfmmp_shipox_settings', $wcfm_settings_form['wcfmmp_shipox_settings'] );
	}

	/**
	 * @param $seller_products
	 * @param $products
	 * @return array|mixed
	 */
	function shipox_wcfm_get_sellers_by_product_on_order( $seller_products, $products ) {
		// TODO: for now MultiVendor is for Admin Order Creation
		if ( is_checkout() || is_cart() ) {
			return $seller_products;
		}

		foreach ( $products as $product ) {
			$vendor_id                       = get_post_field( 'post_author', $product['product_id'] );
			$seller_products[ $vendor_id ][] = $product;
		}

		if ( $seller_products ) {
			foreach ( $seller_products as $vendor_id => $products ) {
				if ( ! $this->wcfm_shipox_is_shipping_enabled_for_seller( $vendor_id ) ) {
					unset( $seller_products[ $vendor_id ] );
				}
			}
		}

		return $seller_products;
	}

	/**
	 * @param $vendor_id
	 * @return bool
	 */
	function wcfm_shipox_is_shipping_enabled_for_seller( $vendor_id ) {
		$vendor_shipping_details = get_user_meta( $vendor_id, '_wcfmmp_shipping', true );
		if ( ! empty( $vendor_shipping_details ) ) {
			$enabled = $vendor_shipping_details['_wcfmmp_user_shipping_enable'];
			if ( ! empty( $enabled ) && 'yes' === $enabled ) {
				$wcfmmp_shipox_shipping = get_user_meta( $vendor_id, '_wcfmmp_shipox_settings', true );
				if ( ! empty( $wcfmmp_shipox_shipping ) ) {
					$is_shipox_enabled      = isset( $wcfmmp_shipox_shipping['_enable_shipox'] );
					$shipox_pickup_location = $wcfmmp_shipox_shipping['_shipox_pickup_location'];

					if ( $is_shipox_enabled && ! empty( $shipox_pickup_location ) ) {
						return true;
					}
				}
			}
		}
		return false;
	}

	/**
	 * Get Merchant/Vendor Store Address
	 * @param $merchant_address
	 * @param $vendor_id
	 * @return array|mixed
	 */
	function shipox_wcfm_get_vendor_pickup_address( $merchant_address, $vendor_id ) {
		if ( ! $vendor_id ) {
			return $merchant_address;
		}

		if ( ! $this->wcfm_shipox_is_shipping_enabled_for_seller( $vendor_id ) ) {
			return $merchant_address;
		}

		$vendor_data = get_user_meta( $vendor_id, 'wcfmmp_profile_settings', true );
		if ( ! $vendor_data ) {
			return $merchant_address;
		}

		// Address
		$address_data = shipox_get_array_value( $vendor_data, 'address' );
		$street_1     = shipox_get_array_string_value( $address_data, 'street_1' );
		$street_2     = shipox_get_array_string_value( $address_data, 'street_2' );
		$city         = shipox_get_array_string_value( $address_data, 'city' );
		$zip          = shipox_get_array_string_value( $address_data, 'zip' );
		$country      = shipox_get_array_string_value( $address_data, 'country' );
		$state        = shipox_get_array_string_value( $address_data, 'state' );
		$address      = shipox_get_array_string_value( $address_data, 'address' );

		// Location
		$store_location = isset( $vendor_data['store_location'] ) ? esc_attr( $vendor_data['store_location'] ) : '';
		$map_address    = isset( $vendor_data['find_address'] ) ? esc_attr( $vendor_data['find_address'] ) : '';
		$store_lat      = isset( $vendor_data['store_lat'] ) ? esc_attr( $vendor_data['store_lat'] ) : 0;
		$store_lng      = isset( $vendor_data['store_lng'] ) ? esc_attr( $vendor_data['store_lng'] ) : 0;

		$pickup_location = null;
		if ( $store_lat > 0 && $store_lng > 0 ) {
			$pickup_location = $store_lat . ',' . $store_lng;
		}

		if ( ! $pickup_location ) {
			$wcfmmp_shipox_shipping = get_user_meta( $vendor_id, '_wcfmmp_shipox_settings', true );
			if ( ! isset( $wcfmmp_shipox_shipping['_shipox_pickup_location'] ) ) {
				return $merchant_address;
			}

			$pickup_location = $wcfmmp_shipox_shipping['_shipox_pickup_location'];
		}

		$store_name      = wcfm_get_vendor_store_name( $vendor_id );
		$the_vendor_user = get_user_by( 'id', $vendor_id );

		$store_email = isset( $vendor_data['store_email'] ) ? esc_attr( $vendor_data['store_email'] ) : $the_vendor_user->user_email;
		$phone       = isset( $vendor_data['phone'] ) ? esc_attr( $vendor_data['phone'] ) : '';

		$merchant_address = array(
			'merchant_company_name'  => empty( $store_name ) ? $the_vendor_user->display_name : $store_name,
			'merchant_contact_name'  => empty( $store_name ) ? $the_vendor_user->user_nicename : $store_name,
			'merchant_contact_email' => $store_email,
			'merchant_city'          => $city,
			'merchant_postcode'      => $zip,
			'merchant_street'        => $street_1,
			'merchant_address'       => $street_1 . ' ' . $street_2 . ' ' . $address,
			'merchant_phone'         => $phone,
			'merchant_lat_long'      => $pickup_location,
			'merchant_details'       => '',
		);

		return $merchant_address;
	}

}

new Shipox_Wcfm_Integration();
