<?php
/**
 * User: Umid Akhmedjanov
 * Date: 12/21/2018
 * Time: 2:15 PM
 */

if ( ! defined( 'ABSPATH' ) ) {
	return;
}

/**
 *  Shipox Cron Job Class
 */
class Shipox_Cron_Job {


	/**
	 * @var int
	 */
	private $interval = 60 * 60;

	/**
	 * @var array
	 */
	private $service_config;

	/**
	 * @var array
	 */
	private $merchant_info;

	/**
	 * @var array
	 */
	private $merchant_config;

	/**
	 * Start the Integration
	 */
	public function __construct() {
		$this->service_config  = get_option( 'wing_service_config' );
		$this->merchant_info   = get_option( 'wing_merchant_address' );
		$this->merchant_config = get_option( 'wing_merchant_config' );

		add_filter( 'cron_schedules', array( $this, 'crawl_every_n_minutes' ) );

		if ( ! wp_next_scheduled( 'crawl_every_n_minutes' ) ) {
			wp_schedule_event( time(), 'every_n_minutes', 'crawl_every_n_minutes' );
		}

		add_action( 'crawl_every_n_minutes', array( $this, 'crawl_feeds' ) );
	}

	/**
	 * @param $schedules
	 * @return mixed
	 */
	public function crawl_every_n_minutes( $schedules ) {
		$schedules['every_n_minutes'] = array(
			'interval' => $this->interval,
			'display'  => __( 'Every N Minutes', 'aur_domain' ),
		);

		return $schedules;
	}


	/**
	 *  Get Live Events of Soccer
	 */
	public function crawl_feeds() {
		shipox()->api->check_token_expired();
	}
}
