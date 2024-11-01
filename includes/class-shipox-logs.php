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
 *  Class Shipox Logs
 */
class Shipox_Logs {

	/**
	 * @var string
	 */
	public $ext = '.log';

	/**
	 * @var array
	 */
	protected $service_config;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->service_config = get_option( 'wing_service_config' );
	}

	/**
	 * @param $filename
	 * @param $status
	 * @param $date
	 * @return null|string
	 */
	public function check_file_name( $filename, $status, $date ) {
		if ( empty( $filename ) ) {
			return $status . '-' . $date . $this->ext;
		}

		return $filename . '-' . $date . $this->ext;
	}

	/**
	 * Add Custom Wing Log
	 * @param string $content
	 * @param string $status
	 * @param string $filename
	 * @internal param $log
	 */
	public function write( $content, $status = SHIPOX_LOG_STATUS::INFO, $filename = null ) {
		if ( 1 !== intval( $this->service_config['test_mode'] ) ) {
			return;
		}

		$file     = $this->check_file_name( $filename, $status, gmdate( 'Y-m-d' ) );
		$log_time = '[' . gmdate( 'Y-m-d H:i:s' ) . '] - ';

		if ( is_array( $content ) || is_object( $content ) ) {
			error_log( $log_time . print_r( $content, true ) . PHP_EOL, 3, trailingslashit( SHIPOX_LOGS ) . $file );
		} else {
			error_log( $log_time . $content . PHP_EOL, 3, trailingslashit( SHIPOX_LOGS ) . $file );
		}
	}
}
