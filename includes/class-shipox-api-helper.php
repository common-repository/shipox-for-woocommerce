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
 *  Shipox API Helper Class
 */
class Shipox_Api_Helper {

	/**
	 * API_HELPER constructor.
	 */
	public function __construct() {
		add_action( 'wp_ajax_get_shipox_token', array( $this, 'get_shipox_token' ) );
	}

	/**
	 * @param $country_id
	 * @return bool
	 */
	public function is_domestic( $country_id ) {
		$marketplace_country_id = shipox()->wing['settings-helper']->get_country_id();
		return $country_id === $marketplace_country_id;
	}

	/**
	 * @param $country_code
	 * @return bool
	 */
	public function is_domestic_by_code( $country_code ) {
		$marketplace_country_code = shipox()->wing['settings-helper']->get_country_code();
		return strtolower( $country_code ) === strtolower( $marketplace_country_code );
	}


	/**
	 * @return string
	 */
	public function get_tracking_url() {
		$marketplace_host = shipox()->wing['settings-helper']->get_marketplace_host();
		return 'https://' . $marketplace_host;
	}

	/**
	 * Authenticate to Wing and get Token
	 */
	public function get_shipox_token() {
		$returned_data = array( 'success' => false );

		check_ajax_referer( 'shipox-wp-woocommerse-plugin', 'nonce' );

		$merchant_email    = sanitize_text_field( $_POST['merchantEmail'] );
		$merchant_password = sanitize_text_field( $_POST['merchantPassword'] );

		if ( ! empty( $merchant_email ) && ! empty( $merchant_password ) ) {
			$request = array(
				'username'    => $merchant_email,
				'password'    => $merchant_password,
				'remember_me' => true,
			);

			shipox()->log->write( $request, 'error' );

			$response = shipox()->api->authenticate( $request );

			shipox()->log->write( $response, 'error' );

			if ( $response['success'] ) {
				$options                      = shipox()->wing['options']['merchant_config'];
				$options['merchant_token']    = $response['data']['id_token'];
				$options['merchant_username'] = $merchant_email;
				$options['merchant_password'] = $merchant_password;
				$options['last_login_date']   = time();

				update_option( 'wing_merchant_config', $options );

				$this->update_customer_marketplace();

				$returned_data['success'] = true;
				$returned_data['token']   = $options['merchant_token'];
			} else {
				shipox()->log->write( $response['data']['code'] . ': ' . $response['data']['message'], 'error' );
				$returned_data = array( 'message' => $response['data']['message'] );
			}
		}

		echo wp_json_encode( $returned_data );
		exit;
	}

	/**
	 * Get Country ID From WING
	 * @param $country_code
	 * @return array|bool
	 */
	function get_country_wing_id( $country_code ) {
		$result = shipox()->api->get_countries();

		foreach ( $result['data'] as $country ) {
			if ( is_array( $country ) && strtolower( $country['code'] ) === strtolower( $country_code ) ) {
				return $country;
			}
		}

		return false;
	}

	/**
	 * @param int $total_weight
	 * @param $country_id
	 * @return int
	 */
	function get_package_type( $country_id, $total_weight = 0 ) {
		$marketplace_country_id = shipox()->wing['settings-helper']->get_country_id();

		$request_package = array(
			'from_country_id' => $marketplace_country_id,
			'to_country_id'   => $country_id,
		);

		$result = shipox()->api->get_package_menu_list( '?' . http_build_query( $request_package ) );

		if ( $result['success'] ) {
			$list = $result['data'];
			foreach ( $list['list'] as $package ) {
				if ( $package['weight'] >= $total_weight ) {
					return $package['menu_id'];
				}
			}
		}

		shipox()->log->write( $total_weight, 'package-error' );
		shipox()->log->write( $result, 'package-error' );

		return 0;
	}

	/**
	 * @deprecated
	 * @param $courier_types
	 * @param $package_list
	 * @return int
	 */
	function get_proper_package( $courier_types, $package_list ) {
		foreach ( $package_list as $package_item ) {
			$packages = $package_item['packages'];

			foreach ( $packages as $package ) {
				if ( in_array( $package['courier_type'], $courier_types, true ) ) {
					return $package['id'];
				}
			}
		}
		return 0;
	}

	/**
	 * @param $courier_types
	 * @param $package_list
	 * @return int
	 */
	function get_proper_package_v2( $courier_types, $package_list ) {
		foreach ( $package_list as $package ) {
			$courier_type = $package['courier_type'];

			if ( in_array( $courier_type['type'], $courier_types, true ) ) {
				return $package;
			}
		}
		return null;
	}

	/**
	 * @param $payment_option
	 * @param $price
	 * @return int
	 */
	function get_custom_service( $payment_option, $price ) {
		if ( 'credit_balance' === $payment_option ) {
			return 0;
		}

		return $price;
	}

	/**
	 * Sometimes WC responses STATE/PROVINCEs as their abbrevations, so for UAE we are mapping them
	 * @param $region
	 * @return string
	 */
	function get_province_or_state_by_code( $region ) {
		switch ( strtoupper( $region ) ) {
			case 'SHJ':
				return 'Sharjah';
			break;
			case 'UAQ':
				return 'Umm Al Quwain';
			break;
			case 'AJM':
				return 'Ajman';
			break;
			case 'AUH':
			case 'ABD':
			case 'ADB':
				return 'Abu Dhabi';
			break;
			case 'DXB':
			case 'DWC':
				return 'Dubai';
			break;
			case 'ALN':
				return 'Al Ain';
			break;
			case 'FUJ':
			case 'FJR':
				return 'Fujairah';
			break;
			case 'RKT':
			case 'RAK':
				return 'Ras Al Khaimah';
			break;
			default:
				return $region;
		}
	}

	/**
	 * @param $country
	 * @param $shipping_address
	 * @return null
	 */
	function get_address_location( $country, $shipping_address ) {
		$countries      = WC()->countries->get_allowed_countries();
		$domestic       = $this->is_domestic( $country['id'] );
		$response_array = array();

		$request = array(
			'address'         => $shipping_address['address_1'] . ' ' . $shipping_address['address_2'],
			'city'            => $shipping_address['city'],
			'country'         => $domestic ? $country['code'] : $countries[ $shipping_address['country'] ],
			'provinceOrState' => $this->get_province_or_state_by_code( $shipping_address['state'] ),
			'domestic'        => $domestic,
		);

		$location = shipox()->api->get_location_by_address( $request );
		if ( $location['success'] ) {
			if ( ! is_null( $location['data']['lat'] ) && ! is_null( $location['data']['lon'] ) ) {
				$response_array[0] = $location['data']['lat'];
				$response_array[1] = $location['data']['lon'];
			}
		}

		return $response_array;
	}

	/**
	 * @param $shipping_address
	 * @return array
	 */
	function get_address_location_by_address( $shipping_address ) {
		$domestic       = $this->is_domestic( $shipping_address['country'] );
		$response_array = array();

		$request = array(
			'address'         => $shipping_address['address_1'] . ' ' . $shipping_address['address_2'],
			'city'            => $shipping_address['city'],
			'country'         => $shipping_address['country'],
			'provinceOrState' => $this->get_province_or_state_by_code( $shipping_address['state'] ),
			'domestic'        => $domestic,
		);

		$location = shipox()->api->get_location_by_address( $request );
		if ( $location['success'] ) {
			if ( ! is_null( $location['data']['lat'] ) && ! is_null( $location['data']['lon'] ) ) {
				$response_array[0] = $location['data']['lat'];
				$response_array[1] = $location['data']['lon'];
			}
		}

		return $response_array;
	}

	/**
	 * Get Address Location by Order
	 * @param $order_wc
	 * @return mixed|void
	 */
	function get_address_location_by_order( $order_wc ) {
		$countries        = WC()->countries->get_allowed_countries();
		$shipping_address = apply_filters( 'shipox_get_shipping_address', $order_wc->get_address( 'shipping' ) );
		$domestic         = $this->is_domestic_by_code( $shipping_address['country'] );

		$response_array = array();

		$request = array(
			'address'         => $shipping_address['address_1'] . ' ' . $shipping_address['address_2'],
			'city'            => $shipping_address['city'],
			'country'         => $domestic ? $shipping_address['country'] : $countries[ $shipping_address['country'] ],
			'provinceOrState' => $this->get_province_or_state_by_code( $shipping_address['state'] ),
			'domestic'        => $domestic,
		);

		$location = shipox()->api->get_location_by_address( $request );
		if ( $location['success'] ) {
			if ( ! is_null( $location['data']['lat'] ) && ! is_null( $location['data']['lon'] ) ) {
				$response_array[0] = $location['data']['lat'];
				$response_array[1] = $location['data']['lon'];
			}
		}

		return apply_filters( 'shipox_get_customer_geo_location', $response_array, $order_wc );
	}

	/**
	 * @deprecated
	 * @param $order_wc
	 * @param $country
	 * @return array
	 */
	function get_wing_packages( $order_wc, $country ) {
		$order_config     = shipox()->wing['options']['order_config'];
		$merchant_address = shipox()->wing['options']['merchant_address'];

		$response         = array(
			'success' => false,
			'message' => null,
			'data'    => null,
		);
		$shipping_address = $order_wc->get_address( 'shipping' );
		$products         = $order_wc->get_items();

		$weight = 0;
		foreach ( $products as $product ) {

			if ( 0 !== $product['variation_id'] ) {
				$product_obj = new WC_Product_Variation( $product['variation_id'] );
			} else {
				$product_obj = new WC_Product( $product['product_id'] );
			}

			$product_weight = (float) $product_obj->get_weight();

			$quantity = $product['qty'];

			$weight += $product_weight * $quantity;
		}

		$weight = wc_get_weight( $weight, 'kg' );

		if ( $order_config['order_default_weight'] > 0 ) {
			$weight = intval( $order_config['order_default_weight'] );
		}

		$country_object = $this->get_country_wing_id( $country );
		$menu_id        = $this->get_package_type( $country_object['id'], $weight );

		if ( $menu_id > 0 ) {
			$merchant_lat_lon = explode( ',', $merchant_address['merchant_lat_long'] );

			if ( ! empty( $merchant_lat_lon ) ) {
				$customer_lat_lon_address = $this->get_address_location_by_order( $order_wc );

				if ( ! empty( $customer_lat_lon_address ) ) {
					$price_request_data = array(
						'service'  => 'LOGISTICS',
						'from_lat' => trim( $merchant_lat_lon[0] ),
						'to_lat'   => $customer_lat_lon_address[0],
						'from_lon' => trim( $merchant_lat_lon[1] ),
						'to_lon'   => $customer_lat_lon_address[1],
						'menu_id'  => $menu_id,
					);

					$price_list = shipox()->api->get_price_list( '?' . http_build_query( $price_request_data ) );

					if ( $price_list['success'] ) {
						$list = $price_list['data']['list'];

						if ( is_array( $list ) && ! empty( $list ) ) {
							$response['success'] = true;
							$response['data']    = array(
								'list'    => $list,
								'lat_lon' => $customer_lat_lon_address[0] . ',' . $customer_lat_lon_address[1],
							);
						}
					} else {
						$response['message'] = esc_html__( 'Shipox: Error with Pricing Packages', 'shipox' );
					}
				} else {
					$response['message'] = esc_html__( 'Shipox: Shipping City/Province is entered wrong', 'shipox' );
				}
			} else {
				$response['message'] = esc_html__( 'Shipox: Merchant Address Location did not configured properly', 'shipox' );
			}
		} else {
			$response['message'] = esc_html__( 'Shipox: Could not find proper Menu', 'shipox' );
		}

		return $response;
	}


	/**
	 * Get Pricing Packages from Shipox
	 * @param $order_wc
	 * @param $country
	 * @return array
	 */
	function get_wing_packages_v2( $order_wc, $country ) {
		$order_config     = shipox()->wing['options']['order_config'];
		$merchant_address = shipox()->wing['options']['merchant_address'];

		$response         = array(
			'success' => false,
			'message' => null,
			'data'    => null,
		);
		$shipping_address = $order_wc->get_address( 'shipping' );
		$products         = $order_wc->get_items();

		$seller_products = apply_filters( 'shipox_wcfm_get_sellers_by_product_on_order', array(), $products );
		if ( ! empty( $seller_products ) ) {
			$vendor_id = array_keys( $seller_products )[0];
			if ( $vendor_id ) {
				$products         = $seller_products[ $vendor_id ];
				$merchant_address = apply_filters( 'shipox_wcfm_get_vendor_pickup_address', $merchant_address, $vendor_id );
			}
		}

		$weight = 0;
		foreach ( $products as $product ) {

			if ( 0 !== $product['variation_id'] ) {
				$product_obj = new WC_Product_Variation( $product['variation_id'] );
			} else {
				$product_obj = new WC_Product( $product['product_id'] );
			}

			$product_weight = (float) $product_obj->get_weight();

			$quantity = $product['qty'];

			$weight += $product_weight * $quantity;
		}

		$weight = wc_get_weight( $weight, 'kg' );

		if ( $order_config['order_default_weight'] > 0 ) {
			$weight = intval( $order_config['order_default_weight'] );
		}

		$country_object   = $this->get_country_wing_id( $country );
		$merchant_lat_lon = explode( ',', $merchant_address['merchant_lat_long'] );

		if ( ! empty( $merchant_lat_lon ) ) {
			//            $customer_lat_lon_address = $this->get_address_location($country_object, $shipping_address);
			$customer_lat_lon_address = $this->get_address_location_by_order( $order_wc );

			if ( ! empty( $customer_lat_lon_address ) ) {
				$is_domestic = $this->is_domestic_by_code( $country );

				$price_request_data = array(
					'dimensions.domestic' => $is_domestic,
					'dimensions.length'   => 10,
					'dimensions.width'    => 10,
					'dimensions.weight'   => $weight,
					'dimensions.unit'     => 'METRIC',
					'from_country_id'     => shipox()->wing['settings-helper']->get_country_id(),
					'to_country_id'       => $country_object['id'],
					'from_latitude'       => trim( $merchant_lat_lon[0] ),
					'from_longitude'      => trim( $merchant_lat_lon[1] ),
					'to_latitude'         => $customer_lat_lon_address[0],
					'to_longitude'        => $customer_lat_lon_address[1],
					'service_types'       => implode( ',', $order_config['order_default_courier_type'] ),
				);

				$price_list = shipox()->api->get_package_prices_v2( $price_request_data );

				if ( $price_list['success'] ) {
					$list = $price_list['data']['list'];

					if ( is_array( $list ) && ! empty( $list ) ) {
						$response['success'] = true;
						$response['data']    = array(
							'list'        => $list,
							'lat_lon'     => $customer_lat_lon_address[0] . ',' . $customer_lat_lon_address[1],
							'weight'      => $weight,
							'is_domestic' => $is_domestic,
						);
					}
				} else {
					$response['message'] = esc_html__( 'Shipox: Error with Pricing Packages', 'shipox' );
				}
			} else {
				$response['message'] = esc_html__( 'Shipox: Shipping City/Province is entered wrong', 'shipox' );
			}
		} else {
			$response['message'] = esc_html__( 'Shipox: Merchant Address Location did not configured properly', 'shipox' );
		}

		return $response;
	}

	/**
	 * @deprecated
	 * @param $order_wc
	 * @param $package
	 * @param $customer_lat_lon
	 *
	 * @return null|string
	 */
	function push_order_to_wing_with_package( $order_wc, $package, $customer_lat_lon ) {
		$order_config     = shipox()->wing['options']['order_config'];
		$merchant_address = shipox()->wing['options']['merchant_address'];
		$shipping_address = $order_wc->get_address( 'shipping' );
		$merchant_lat_lon = explode( ',', $merchant_address['merchant_lat_long'] );

		$products = $order_wc->get_items();

		$order_items = null;
		foreach ( $products as $product ) {
			$order_items .= $product['name'] . ' - Qty: ' . $product['qty'] . ', ';
		}

		if ( intval( $package ) > 0 && ! empty( $merchant_lat_lon ) && ! empty( $customer_lat_lon ) ) {

			$request_data = array();

			// Order ID As a Reference
			$request_data['reference_id'] = $order_wc->get_id() . '/' . $order_wc->get_order_number();

			$wing_cod = $order_wc->get_subtotal() + $order_wc->get_total_tax() - $order_wc->get_discount_total();
			if ( 'credit_balance' === $order_config['order_default_payment_option'] ) {
				$wing_cod = $order_wc->get_total();
			}

			foreach ( $order_wc->get_items( 'fee' ) as $item_fee ) {
				$fee_total = $item_fee->get_total();
			}

			if ( 'cod' === $order_wc->get_payment_method() ) {
				$request_data['payment_type'] = 'cash';
				$request_data['payer']        = 'recipient';
				$request_data['parcel_value'] = $order_wc->get_total();

				$request_data['charge_items'] = array(
					array(
						'charge_type' => 'cod',
						'charge'      => $wing_cod, // Round Up the COD by requesting Finance
					),
					array(
						'charge_type' => 'service_custom',
						'charge'      => $this->get_custom_service( $order_config['order_default_payment_option'], $order_wc->get_total() - $wing_cod ),
					),
				);
			} else {
				$request_data['payment_type'] = 'credit_balance';
				$request_data['payer']        = 'sender';

				$request_data['charge_items'] = array(
					array(
						'charge_type' => 'total',
						'charge'      => $order_wc->get_total(),
					),
					array(
						'charge_type' => 'cod',
						'charge'      => 0,
					),
					array(
						'charge_type' => 'service_custom',
						'charge'      => 0,
					),
				);
			}

			//  PickUp Time
			$request_data['pickup_time_now'] = false;

			//  Request Details
			$request_data['request_details'] = $order_items;

			//  PhotoItems
			$request_data['photo_items'] = array();

			//  PackageInfo
			$request_data['package'] = array( 'id' => $package );

			// Must provide this as true to overcome the our cut off times
			$request_data['force_create'] = true;

			//  Locations
			$request_data['locations'][] = array(
				'pickup'         => true,
				'lat'            => trim( $merchant_lat_lon[0] ),
				'lon'            => trim( $merchant_lat_lon[1] ),
				'address'        => substr( $merchant_address['merchant_street'], 0, 145 ) . ' ' . $merchant_address['merchant_address'],
				'details'        => '',
				'phone'          => $merchant_address['merchant_phone'],
				'email'          => $merchant_address['merchant_contact_email'],
				'contact_name'   => $merchant_address['merchant_contact_name'],
				'address_city'   => $merchant_address['merchant_city'],
				'address_street' => substr( $merchant_address['merchant_street'], 0, 145 ),
			);

			$request_data['locations'][] = array(
				'pickup'         => false,
				'lat'            => trim( $customer_lat_lon[0] ),
				'lon'            => trim( $customer_lat_lon[1] ),
				'address'        => $shipping_address['address_1'] . ' ' . $shipping_address['address_2'] . ' ' . $shipping_address['city'] . ' ' . $shipping_address['country'],
				'details'        => $order_wc->get_customer_note(),
				'phone'          => $order_wc->get_billing_phone(),
				'email'          => $order_wc->get_billing_email(),
				'address_city'   => $shipping_address['city'],
				'address_street' => substr( $shipping_address['address_1'] . ' ' . $shipping_address['address_2'], 0, 145 ),
				'contact_name'   => $shipping_address['first_name'] . ' ' . $shipping_address['last_name'],
			);

			//Note
			$request_data['note'] = home_url() . ', ' . $order_items;

			//Payment Type
			//          $request_data['payment_type'] = $order_config['order_default_payment_option'];

			//If Recipient Not Available
			$request_data['recipient_not_available'] = 'do_not_deliver';

			$response = shipox()->api->create_order( $request_data );

			if ( $response['success'] ) {
				$response_data = $response['data'];

				$order_wc->update_meta_data( '_wing_order_number', $response_data['order_number'] );
				$order_wc->update_meta_data( '_wing_order_id', $response_data['id'] );
				$order_wc->update_meta_data( '_wing_status', $response_data['status'] );
				$order_wc->save();

				shipox()->wing['order-helper']->update_wc_order_status( $order_wc, 'Order number is: #' . $response_data['order_number'] );
			} else {
				shipox()->log->write( $request_data, 'order-create-error' );
				shipox()->log->write( $response, 'order-create-error' );
				$order_wc->add_order_note( sprintf( 'Order Creation Error: %s', $response['data']['message'] ), 0 );

				return $response['data']['message'];
			}
		}

		return null;
	}

	/**
	 * Get Order Phone Meta
	 * @param $order_wc
	 * @return mixed
	 */
	function get_order_phone( $order_wc ) {
		$phone = get_post_meta( $order_wc->get_id(), 'shipping_phone', true );

		if ( ! empty( $phone ) ) {
			return $phone;
		}

		return $order_wc->get_billing_phone();
	}

	/**
	 * Push the order to the Shipox with Selected Package
	 *
	 * @param $order_wc
	 * @param $package
	 * @param $customer_lat_lon
	 * @param $to_country
	 * @param int $box_count
	 *
	 * @return null|string
	 */
	function push_order_to_wing_with_package_new_model( $order_wc, $package, $customer_lat_lon, $to_country, $box_count = 1 ) {
		$order_config     = shipox()->wing['options']['order_config'];
		$merchant_address = shipox()->wing['options']['merchant_address'];
		$shipping_address = apply_filters( 'shipox_get_shipping_address', $order_wc->get_address( 'shipping' ) );
		$package_price    = explode( '-', $package );

		$products        = $order_wc->get_items();
		$seller_products = apply_filters( 'shipox_wcfm_get_sellers_by_product_on_order', array(), $products );
		if ( ! empty( $seller_products ) ) {
			$vendor_id = array_keys( $seller_products )[0];
			if ( $vendor_id ) {
				$products         = $seller_products[ $vendor_id ];
				$merchant_address = apply_filters( 'shipox_wcfm_get_vendor_pickup_address', $merchant_address, $vendor_id );
			}
		}

		$merchant_lat_lon = explode( ',', $merchant_address['merchant_lat_long'] );

		$order_items = null;
		foreach ( $products as $product ) {
			$order_items .= $product['name'] . ' - SKU: ' . $product['sku'] . ', ' . ' - Qty: ' . $product['qty'] . ', ';
		}

		if ( intval( $package ) > 0 && ! empty( $merchant_lat_lon ) && ! empty( $customer_lat_lon ) ) {
			$request_data = array();

			// Order ID As a Reference
			$request_data['reference_id'] = $order_wc->get_id() . '_' . $order_wc->get_order_number();

			// WC Order ID as Source ID on Shipox
			$request_data['source_id'] = $order_wc->get_id();

			$request_data['source_type'] = 'WOO_COMMERCE';

			$wing_cod = $order_wc->get_subtotal() + $order_wc->get_total_tax() - $order_wc->get_discount_total();
			if ( 'credit_balance' === $order_config['order_default_payment_option'] ) {
				$wing_cod = $order_wc->get_total();
			}

			foreach ( $order_wc->get_items( 'fee' ) as $item_fee ) {
				$fee_total = $item_fee->get_total();
			}

			if ( 'cod' === strtolower( $order_wc->get_payment_method() ) ) {
				$request_data['payment_type'] = 'cash';
				$request_data['payer']        = 'recipient';
				$request_data['parcel_value'] = $order_wc->get_total();

				$request_data['charge_items'] = array(
					array(
						'charge_type' => 'cod',
						'charge'      => $wing_cod, // Round Up the COD by requesting Finance
					),
					array(
						'charge_type' => 'service_custom',
						'charge'      => $this->get_custom_service( $order_config['order_default_payment_option'], $order_wc->get_total() - $wing_cod ),
					),
				);
			} else {
				$request_data['payment_type'] = 'credit_balance';
				$request_data['payer']        = 'sender';

				$request_data['charge_items'] = array(
					array(
						'charge_type' => 'total',
						'charge'      => $order_wc->get_total(),
					),
					array(
						'charge_type' => 'cod',
						'charge'      => 0,
					),
					array(
						'charge_type' => 'service_custom',
						'charge'      => 0,
					),
				);
			}

			//  PickUp Time
			$request_data['pickup_time_now'] = false;

			//  Request Details
			$request_data['request_details'] = $order_items;

			//  PhotoItems
			$request_data['photo_items'] = array();

			$request_data['package_type'] = array(
				'id'            => $package_price[0],

				'package_price' => array(
					'id' => $package_price[1],
				),
			);

			// Must provide this as true to overcome the our cut off times
			$request_data['force_create'] = true;

			// Piece Count
			$request_data['piece_count'] = $box_count;

			//  Locations
			$request_data['sender_data'] = array(
				'address_type' => 'business',
				'name'         => $merchant_address['merchant_contact_name'],
				'email'        => $merchant_address['merchant_contact_email'],
				'phone'        => $merchant_address['merchant_phone'],
				'address'      => $merchant_address['merchant_address'],
				'details'      => '',
				'country'      => array( 'id' => shipox()->wing['settings-helper']->get_country_id() ),
				'city'         => array( 'name' => $merchant_address['merchant_city'] ),
				'street'       => substr( $merchant_address['merchant_street'], 0, 145 ),
				'lat'          => trim( $merchant_lat_lon[0] ),
				'lon'          => trim( $merchant_lat_lon[1] ),
			);

			$request_data['recipient_data'] = array(
				'address_type' => 'residential',
				'name'         => $shipping_address['first_name'] . ' ' . $shipping_address['last_name'],
				'phone'        => $this->get_order_phone( $order_wc ),
				'email'        => $order_wc->get_billing_email(),

				'address'      => $shipping_address['address_1'] . ' ' . $shipping_address['address_2'] . ' ' . $shipping_address['city'] . ' ' . $shipping_address['country'],
				'details'      => $order_wc->get_customer_note(),
				'country'      => array( 'id' => $to_country['id'] ),
				'city'         => array( 'name' => $shipping_address['city'] ),
				'street'       => substr( $shipping_address['address_1'] . ' ' . $shipping_address['address_2'], 0, 145 ),

				'lat'          => trim( $customer_lat_lon[0] ),
				'lon'          => trim( $customer_lat_lon[1] ),
			);

			$request_data['dimensions'] = array(
				'width'    => 10,
				'length'   => 10,
				'height'   => 10,
				'weight'   => $package_price[2],
				'unit'     => 'METRIC',
				'domestic' => 1 === $package_price[3],
			);

			//Note
			$request_data['note'] = $order_items;

			//Payment Type
			//          $request_data['payment_type'] = $order_config['order_default_payment_option'];

			//If Recipient Not Available
			$request_data['recipient_not_available'] = 'do_not_deliver';

			$response = shipox()->api->create_order_v2( $request_data );

			if ( $response['success'] ) {
				$response_data = $response['data'];

				$order_wc->update_meta_data( '_wing_order_number', $response_data['order_number'] );
				$order_wc->update_meta_data( '_wing_order_id', $response_data['id'] );
				$order_wc->update_meta_data( '_wing_status', $response_data['status'] );
				$order_wc->save();

				shipox()->wing['order-helper']->update_wc_order_status( $order_wc, 'Order number is: #' . $response_data['order_number'] );
			} else {
				shipox()->log->write( $request_data, 'order-create-error' );
				shipox()->log->write( $response, 'order-create-error' );
				$order_wc->add_order_note( sprintf( 'Order Creation Error: %s', $response['data']['message'] ), 0 );

				return $response['data']['message'];
			}
		}

		return null;
	}


	/**
	 * @param $order_id
	 * @return bool
	 */
	function get_airwaybill( $order_id ) {
		$response = shipox()->api->get_airwaybill( $order_id );

		if ( $response['success'] ) {
			return $response['data']['value'];
		}

		return false;
	}

	/**
	 * @param $order_id
	 * @return bool
	 */
	function get_airwaybill_zebra( $order_id ) {
		$response = shipox()->api->get_airwaybill_zebra( $order_id );

		if ( $response['success'] ) {
			return $response['data']['value'];
		}

		return false;
	}


	/**
	 * @return void
	 */
	public function update_customer_marketplace() {
		$response = shipox()->api->get_customer_marketplace();

		$options = array();
		if ( $response['success'] ) {
			$marketplace                             = $response['data'];
			$options['currency']                     = $marketplace['currency'];
			$options['custom']                       = $marketplace['custom'];
			$options['decimal_point']                = isset( $marketplace['setting']['settings']['decimalPoint'] ) ? $marketplace['setting']['settings']['decimalPoint'] : 2;
			$options['disable_international_orders'] = isset( $marketplace['setting']['settings']['disableInternationalOrders'] ) ? $marketplace['setting']['settings']['disableInternationalOrders'] : false;
			$options['new_model_enabled']            = isset( $marketplace['setting']['settings']['newModelEnabled'] ) ? $marketplace['setting']['settings']['newModelEnabled'] : false;
			$options['host']                         = isset( $marketplace['setting']['settings']['customerDomain'] ) ? $marketplace['setting']['settings']['customerDomain'] : 'my.shipox.com';
			$options['country']                      = $marketplace['country'];

			update_option( 'wing_marketplace_settings', $options );
		}
	}
}
