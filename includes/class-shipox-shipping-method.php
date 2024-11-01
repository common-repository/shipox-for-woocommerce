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
 *  Shipox_Shipping_Method
 */
class Shipox_Shipping_Method extends WC_Shipping_Method {

	/**
	 * Shipox_Shipping_Method constructor.
	 */
	public function __construct() {
		parent::__construct();

		$this->id           = 'wing';
		$this->method_title = __( 'Shipox', 'shipox' );
		$this->enabled      = $this->get_option( 'enabled' );
		$this->apply_tax    = $this->get_option( 'apply_tax' );
		$this->init();

		//        $this->supports = array(
		//            'shipping-zones'
		//        );

		$this->title = shipox_get_array_string_value( $this->settings, 'title', esc_attr__( 'Shipox Shipping', 'shipox' ) );
	}

	/**
	 *
	 */
	private function init() {
		$this->init_form_fields();
		$this->init_settings();

		add_action( 'woocommerce_update_options_shipping_' . $this->id, array( $this, 'process_admin_options' ) );
		//        add_filter('woocommerce_shipping_methods', array($this, 'shipox_shipping_method'));
	}

	/**
	 * Define settings field for this shipping
	 * @return void
	 */
	function init_form_fields() {
		$this->form_fields = array(

			'enabled'      => array(
				'title'       => __( 'Enable', 'shipox' ),
				'type'        => 'checkbox',
				'description' => __( 'Enable this shipping.', 'shipox' ),
				'default'     => 'yes',
			),

			'title'        => array(
				'title'       => __( 'Title', 'shipox' ),
				'type'        => 'text',
				'description' => __( 'Title to be display on site', 'shipox' ),
				'default'     => __( 'Shipox Shipping', 'shipox' ),
			),

			'weight'       => array(
				'title'       => __( 'Weight (kg)', 'shipox' ),
				'type'        => 'number',
				'description' => __( 'Maximum allowed weight', 'shipox' ),
				'default'     => 100,
			),

			'availability' => array(
				'title'   => __( 'Methods availability', 'shipox' ),
				'type'    => 'select',
				'default' => 'all',
				'class'   => 'availability wc-enhanced-select',
				'options' => array(
					'all'      => __( 'All allowed countries', 'shipox' ),
					'specific' => __( 'Specific Countries', 'shipox' ),
				),
			),

			'countries'    => array(
				'title'             => __( 'Specific Countries', 'shipox' ),
				'type'              => 'multiselect',
				'class'             => 'wc-enhanced-select',
				'css'               => 'width: 450px;',
				'default'           => '',
				'options'           => WC()->countries->get_shipping_countries(),
				'custom_attributes' => array(
					'data-placeholder' => __( 'Select some countries', 'shipox' ),
				),
			),

			'apply_tax'    => array(
				'title'       => __( 'Apply Tax to the Shipping Prices', 'shipox' ),
				'type'        => 'checkbox',
				'description' => __( 'If enabled, tax will be applied to the Shipox Shipping Prices', 'shipox' ),
				'default'     => '',
			),
		);
	}


	/**
	 *  Calculate Shipping
	 * @param array $package
	 */
	public function calculate_shipping( $package = array() ) {
		global $WCFMmp; //phpcs:ignore WordPress.NamingConventions.ValidVariableName.VariableNotSnakeCase

		if ( ! $this->enabled ) {
			return;
		}

		// TODO if WCFM is active, hide shipping method on Checkout Page
		if ( $WCFMmp ) { //phpcs:ignore WordPress.NamingConventions.ValidVariableName.VariableNotSnakeCase
			return;
		}

		$base_location = wc_get_base_location();

		$marketplace_country  = shipox()->wing['settings-helper']->get_country_code();
		$marketplace_currency = shipox()->wing['settings-helper']->get_currency();

		if ( get_woocommerce_currency() !== $marketplace_currency || $base_location['country'] !== $marketplace_country ) {
			return;
		}

		$order_config     = shipox()->wing['options']['order_config'];
		$merchant_address = shipox()->wing['options']['merchant_address'];

		$address = apply_filters( 'shipox_get_shipping_address', $package['destination'] );

		if ( 'specific' === $this->get_option( 'availability' ) && ! in_array( $address['country'], $this->get_option( 'countries' ), true ) ) {
			return;
		}

		$weight = 0;
		foreach ( $package['contents'] as $item_id => $values ) {
			if ( $values['quantity'] > 0 && $values['data']->needs_shipping() ) {
				$weight += intval( $values['quantity'] ) * floatval( $values['data']->get_weight() );
			}
		}

		$weight = wc_get_weight( $weight, 'kg' );

		if ( $order_config['order_default_weight'] > 0 ) {
			$weight = intval( $order_config['order_default_weight'] );
		}

		$country                      = shipox()->wing['api-helper']->get_country_wing_id( $address['country'] );
		$marketplace_int_availability = shipox()->wing['settings-helper']->get_international_availability();
		$is_domestic                  = shipox()->wing['api-helper']->is_domestic_by_code( $address['country'] );

		if ( 0 === $order_config['order_international_availability'] && ! $marketplace_int_availability && ! $is_domestic ) {
			return;
		}

		$merchant_lat_lon = explode( ',', $merchant_address['merchant_lat_long'] );

		$price_array = array();
		if ( ! empty( $merchant_lat_lon ) ) {
			$customer_lat_lon_address = shipox()->wing['api-helper']->get_address_location_by_address( $address );

			if ( ! empty( $customer_lat_lon_address ) ) {
				// New Model
				$price_request_data = array(
					'dimensions.domestic' => $is_domestic,
					'dimensions.length'   => 10,
					'dimensions.width'    => 10,
					'dimensions.weight'   => $weight,
					'dimensions.unit'     => 'METRIC',
					'from_country_id'     => shipox()->wing['settings-helper']->get_country_id(),
					'to_country_id'       => $country['id'],
					'from_latitude'       => trim( $merchant_lat_lon[0] ),
					'from_longitude'      => trim( $merchant_lat_lon[1] ),
					'to_latitude'         => $customer_lat_lon_address[0],
					'to_longitude'        => $customer_lat_lon_address[1],
					'service_types'       => implode( ',', $order_config['order_default_courier_type'] ),
				);

				$price_list = shipox()->api->get_package_prices_v2( $price_request_data );

				if ( $price_list['success'] ) {
					$list = $price_list['data']['list'];

					foreach ( $list as $list_item ) {
						$price_item = $list_item['price'];
						$name       = $list_item['name'];
						if ( isset( $list_item['supplier'] ) ) {
							$name = $list_item['supplier']['name'] . ' - ' . $list_item['name'];
						}
						$method                 = $list_item['id'] . '-' . $price_item['id'] . '-' . $weight . '-' . ( $is_domestic ? '1' : '0' );
						$price_array[ $method ] = array(
							'label'  => $name,
							'amount' => $price_item['total'],
						);
					}
				}
			}
		}

		if ( isset( $price_array ) && ! empty( $price_array ) ) {
			foreach ( $price_array as $key => $value ) {
				$rate = array(
					'id'       => $this->id . '_' . $key,
					'label'    => $value['label'],
					'cost'     => $value['amount'],
					'taxes'    => 'yes' === $this->apply_tax ? '' : false,
					'calc_tax' => 'per_order',
				);
				$this->add_rate( $rate );
			}
		}
	}

	/**
	 * @param $methods
	 * @return mixed
	 */
	public function shipox_shipping_method( $methods ) {
		$methods['wing'] = esc_attr( 'Shipox_Shipping_Method' );
		return $methods;
	}
}

if ( class_exists( 'WC_Shipping_Method' ) ) {
	new Shipox_Shipping_Method();
}
