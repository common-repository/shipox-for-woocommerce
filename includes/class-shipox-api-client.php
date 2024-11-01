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
 *  Api Client Class for Shipox
 */
class Shipox_Api_Client {

	/**
	 * API Host URL
	 *
	 * @var array[]
	 */
	private $api_host_urls = array(
		1 => array(
			'test' => 'https://stagingapi.shipox.com',
			'live' => 'https://prodapi.shipox.com',
		),
		2 => array(
			'test' => 'https://stagingapi.shipox.com',
			'live' => 'https://prodapi.safe-arrival.com',
		),
		3 => array(
			'test' => 'https://stagingapi.thabbit.shipox.com',
			'live' => 'https://prodgw.thabbit.shipox.com',
		),
	);

	/**
	 * @var string
	 */
	private $authenticate_url = '/api/v1/customer/authenticate';

	/**
	 * @var string
	 */
	private $country_list_url = '/api/v1/country/list';

	/**
	 * @var string
	 */
	private $city_list_url = '/api/v1/cities';

	/**
	 * @var string
	 */
	private $city_item_url = '/api/v1/city/';

	/**
	 * @var string
	 */
	private $package_menu_list_url = '/api/v2/package-menu';

	/**
	 * @var string
	 */
	private $price_list_url = '/api/v1/packages/prices';

	/**
	 * @var string
	 */
	private $price_list_url_v2 = '/api/v2/packages/plugin/prices/';

	/**
	 * @var string
	 */
	private $create_order_url = '/api/v1/customer/order';

	/**
	 * @var string
	 */
	private $create_order_v2_url = '/api/v2/customer/order';

	/**
	 * @var string
	 */
	private $marketplace_url = '/api/v1/marketplace';

	/**
	 * @var string
	 */
	private $get_order_details_url = '/api/v1/customer/order/order_number/';

	/**
	 * @var string
	 */
	private $get_city_by_name = '/api/v1/city_by_name';

	/**
	 * @var string
	 */
	private $get_location_by_address = '/api/v1/coordinate_by_address';

	/**
	 * @var string
	 */
	private $get_airwaybill = '/api/v1/customer/order/%s/airwaybill';

	/**
	 * @var string
	 */
	private $get_airwaybill_zebra = '/api/v1/admin/orders/airwaybill-zebra';

	/**
	 * @var string
	 */
	private $update_order_status = '/api/v1/customer/order/{id}/status_update';

	/**
	 * @var string
	 */
	private $get_service_types = '/api/v1/admin/service_types';

	/**
	 * @var array
	 */
	private $service_config = array();

	/**
	 * @var array
	 */
	private $merchant_info = array();

	/**
	 * @var array
	 */
	private $merchant_config = array();

	/**
	 * @var int
	 */
	public $timeout = 10;

	/**
	 * WingApiClient constructor.
	 */
	function __construct() {
		$this->init();
	}

	/**
	 * Initialize
	 */
	private function init() {
		$this->service_config  = get_option( 'wing_service_config' );
		$this->merchant_info   = get_option( 'wing_merchant_address' );
		$this->merchant_config = get_option( 'wing_merchant_config' );
	}

	/**
	 * @return int
	 */
	public function get_timeout() {
		return $this->timeout;
	}

	/**
	 * @return string
	 */
	public function get_api_base_url() {
		$instance = 1;
		if ( isset( $this->service_config['instance'] ) && isset( $this->api_host_urls[ $this->service_config['instance'] ] ) ) {
			$instance = intval( $this->service_config['instance'] );
		}

		// TODO: REMOVED STAGING URL WORKS ONLY WITH PRODUCTION. DEBUG MODE WILL BE USED TO SAVE LOGS
		//      if ( 1 === $this->service_config['test_mode'] ) {
		//          return $this->api_host_urls[ $instance ]['test'];
		//      }

		return $this->api_host_urls[ $instance ]['live'];
	}

	/**
	 * @param null $data
	 * @return null
	 */
	public function authenticate( $data = null ) {
		if ( is_null( $data ) ) {
			$data = array(
				'username'    => $this->merchant_config['merchant_username'],
				'password'    => $this->merchant_config['merchant_password'],
				'remember_me' => true,
			);
		}

		return $this->send_request( $this->authenticate_url, 'post', $data, true );
	}

	/**
	 * Check Token is expired or not, if expired reauthorize Wing and refresh Token
	 * @return bool
	 */
	public function check_token_expired() {
		if ( ( time() - $this->merchant_config['last_login_date'] ) > 100 ) {
			if ( is_null( $this->merchant_config['merchant_username'] ) && is_null( $this->merchant_config['merchant_password'] ) ) {
				shipox()->log->write( 'Check Token Expired Function: Merchant option is empty', 'error' );
				return false;
			}

			$time_request = time();
			$response     = $this->authenticate();

			if ( $response['success'] ) {
				$options['merchant_token']    = $response['data']['id_token'];
				$options['merchant_username'] = $this->merchant_config['merchant_username'];
				$options['merchant_password'] = $this->merchant_config['merchant_password'];
				$options['last_login_date']   = $time_request;

				update_option( 'wing_merchant_config', $options );

				$this->init();

				shipox()->wing['api-helper']->update_customer_marketplace();

				return true;
			}

			shipox()->log->write( $response['data']['code'] . ': ' . $response['data']['message'], 'error' );

			return false;
		}

		return true;
	}


	/**
	 * @param $url
	 * @param string $method
	 * @param null $data
	 * @param bool $get_token
	 * @return array
	 */
	public function send_request( $url, $method = 'get', $data = null, $get_token = false ) {
		$response            = array();
		$response['success'] = false;

		if ( ! $get_token ) {
			$is_token_valid = $this->check_token_expired();
			if ( ! $is_token_valid ) {
				$response['data']['code']    = 'error.validation';
				$response['data']['message'] = __( 'Token Expired and cannot re-login to the System', 'wing' );
				return $response;
			}
		}

		$api_url = $this->get_api_base_url();

		$request_url = $api_url . $url;

		$json        = $data ? wp_json_encode( $data ) : '';
		$curl_header = array(
			'x-app-type'     => 'wordpress_plugin',
			'Content-Type'   => 'application/json',
			'Content-Length' => strlen( $json ),
			'RemoteAddr'     => $_SERVER['REMOTE_ADDR'],
		);

		switch ( $method ) {
			case 'post':
				$method = 'POST';
				break;
			case 'put':
				$method = 'PUT';
				break;
			case 'delete':
				$method = 'DELETE';
				break;
			default:
				$method = 'GET';
		}

		$token = $this->merchant_config['merchant_token'];

		if ( ! $get_token ) {
			if ( $token ) {
				$curl_header['Authorization'] = 'Bearer ' . $token;
				$curl_header['Accept']        = 'application/json';
			} else {
				$curl_header['Accept'] = '*/*';
			}
		} else {
			$curl_header['Accept'] = '*/*';
		}

		$response = wp_remote_request(
			$request_url,
			array(
				'method'  => $method,
				'headers' => $curl_header,
				'body'    => $json,
				'timeout' => 30,
			)
		);

		$http_status_code = wp_remote_retrieve_response_code( $response );
		$response_message = wp_remote_retrieve_response_message( $response );

		if ( $http_status_code >= 200 && $http_status_code < 300 && ! empty( $response_message ) ) {
			shipox()->log->write( $request_url, 'curl-error' );
			shipox()->log->write( $response_message, 'curl-error' );
			shipox()->log->write( $json, 'curl-error' );
			shipox()->log->write( $http_status_code, 'curl-error' );
		}

		$json_result = json_decode( wp_remote_retrieve_body( $response ), true );

		shipox()->log->write( $request_url, 'curl-api' );
		shipox()->log->write( $json_result, 'curl-api' );
		shipox()->log->write( $http_status_code, 'curl-api' );

		switch ( intval( $http_status_code ) ) {
			case 200:
			case 201:
				$response['success'] = true;
				if ( 'success' === $json_result['status'] ) {
					$response['data'] = $json_result['data'];
				} else {
					$response['data'] = $json_result;
				}
				break;

			default:
				$response['data'] = $json_result;

				shipox()->log->write( $request_url, 'api-error' );
				shipox()->log->write( $json, 'api-error' );
				shipox()->log->write( $http_status_code, 'api-error' );
				shipox()->log->write( $json_result, 'api-error' );

				break;
		}

		return $response;
	}

	/**
	 * @return null
	 */
	public function get_countries() {
		return $this->send_request( $this->country_list_url );
	}

	/**
	 * @deprecated
	 * @param bool $is_domestic
	 * @return null
	 */
	public function get_city_list( $is_domestic = false ) {
		$data = array(
			'is_uae' => $is_domestic,
		);

		$response = $this->send_request( $this->city_list_url . '?' . http_build_query( $data ), 'get' );

		return $response ? $response['data'] : $response;
	}

	/**
	 * @param $city_id
	 * @return array
	 */
	public function get_city( $city_id ) {
		return $this->send_request( $this->city_item_url . $city_id );
	}

	/**
	 * @param string $params
	 * @return null
	 */
	public function get_package_menu_list( $params = '' ) {
		return $this->send_request( $this->package_menu_list_url . $params );
	}

	/**
	 * @param string $params
	 * @return null
	 */
	public function get_price_list( $params = '' ) {
		return $this->send_request( $this->price_list_url . $params );
	}

	/**
	 * @param $data
	 * @return null
	 */
	public function create_order( $data ) {
		return $this->send_request( $this->create_order_url, 'post', $data );
	}

	/**
	 * @param string $order_number
	 * @return null
	 * @internal param string $params
	 */
	public function get_order_details( $order_number = '' ) {
		return $this->send_request( $this->get_order_details_url . $order_number );
	}

	/**
	 * @param $city_name
	 * @return array
	 */
	public function is_valid_city( $city_name ) {
		$data = array(
			'city_name' => $city_name,
		);
		$url  = $this->get_city_by_name . '?' . http_build_query( $data );

		return $this->send_request( $url );
	}


	/**
	 * Get Order Aiwaybill
	 * @param $order_id
	 * @return array
	 */
	public function get_airwaybill( $order_id ) {
		return $this->send_request( sprintf( $this->get_airwaybill, $order_id ) );
	}

	/**
	 * Get Order Mini Airwaybill
	 * @param $order_id
	 * @return array
	 */
	public function get_airwaybill_zebra( $order_id ) {
		$data = array( 'ids' => $order_id );
		return $this->send_request( $this->get_airwaybill_zebra . '?' . http_build_query( $data ) );
	}

	/**
	 * Get Customer Marketplace Data
	 *
	 * @return null
	 */
	public function get_customer_marketplace() {
		return $this->send_request( $this->marketplace_url );
	}

	/**
	 * Get Shipping Address Latitude & longitude by Address details
	 *
	 * @param $data
	 * @return null
	 */
	public function get_location_by_address( $data ) {
		$url = $this->get_location_by_address . '?' . http_build_query( $data );
		return $this->send_request( $url, 'get' );
	}

	/**
	 * Update Order Status
	 *
	 * @param $order_id
	 * @param null $data
	 * @return array
	 */
	public function update_order_status( $order_id, $data = null ) {
		return $this->send_request( str_replace( '{id}', $order_id, $this->update_order_status ), 'put', $data );
	}

	/**
	 * Get Dynamic Service Types
	 * @return null
	 */
	public function get_all_service_types() {
		return $this->send_request( $this->get_service_types );
	}

	/**
	 * Get Package Prices
	 *
	 * @param $data
	 * @return array
	 */
	public function get_package_prices_v2( $data ) {
		$url = $this->price_list_url_v2 . '?' . http_build_query( $data );
		return $this->send_request( $url, 'get' );
	}

	/**
	 * Create Shipox Order
	 *
	 * @param $data
	 * @return null
	 */
	public function create_order_v2( $data ) {
		return $this->send_request( $this->create_order_v2_url, 'post', $data );
	}
}
