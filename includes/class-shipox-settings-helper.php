<?php
/**
 * Created by PhpStorm.
 * User: umidakhm
 * Date: 10/17/2018
 * Time: 3:31 PM
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 *  Class Shipox Settings Helper
 */
class Shipox_Settings_Helper {

	/**
	 * @var int
	 */
	private $country_id = 229;

	/**
	 * @var string
	 */
	private $country_code = 'AE';

	/**
	 * @var string
	 */
	private $country_name = 'United Arab Emirates';

	/**
	 * @var string
	 */
	private $currency = 'AED';

	/**
	 * @var bool
	 */
	private $international_availability = false;

	/**
	 * @var bool
	 */
	private $new_model_enabled = true;

	/**
	 * @var string
	 */
	private $host = 'my.shipox.com';


	/**
	 * @return mixed|void
	 */
	public function get_marketplace_settings() {
		return get_option( 'wing_marketplace_settings' );
	}


	/**
	 *  Get Marketplace Host
	 */
	public function get_country_id() {
		$marketplace_settings = $this->get_marketplace_settings();
		return isset( $marketplace_settings['country']['id'] ) ? $marketplace_settings['country']['id'] : $this->country_id;
	}


	/**
	 *  Get Marketplace Host
	 */
	public function get_country_code() {
		$marketplace_settings = $this->get_marketplace_settings();
		return isset( $marketplace_settings['country']['description'] ) ? $marketplace_settings['country']['description'] : $this->country_code;
	}

	/**
	 *  Get Marketplace Country Code
	 */
	public function get_marketplace_host() {
		$marketplace_settings = $this->get_marketplace_settings();
		return isset( $marketplace_settings['host'] ) ? $marketplace_settings['host'] : $this->host;
	}

	/**
	 *  Get Marketplace Country Code
	 */
	public function get_country_name() {
		$marketplace_settings = $this->get_marketplace_settings();
		return isset( $marketplace_settings['country']['name'] ) ? $marketplace_settings['country']['name'] : $this->country_name;
	}

	/**
	 *  Get Marketplace Currency
	 */
	public function get_currency() {
		$marketplace_settings = $this->get_marketplace_settings();
		return isset( $marketplace_settings['currency'] ) ? $marketplace_settings['currency'] : $this->currency;
	}

	/**
	 *  Get International Availability
	 */
	public function get_international_availability() {
		$marketplace_settings = $this->get_marketplace_settings();
		return isset( $marketplace_settings['disable_international_orders'] ) ? ! $marketplace_settings['disable_international_orders'] : $this->international_availability;
	}

	/**
	 *  Get New Model Enabled
	 */
	public function is_new_model_enabled() {
		$marketplace_settings = $this->get_marketplace_settings();
		return isset( $marketplace_settings['new_model_enabled'] ) ? $marketplace_settings['new_model_enabled'] : $this->new_model_enabled;
	}
}
