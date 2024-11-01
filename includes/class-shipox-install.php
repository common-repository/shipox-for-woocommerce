<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 *  Class Shipox Install
 */
class Shipox_Install {

	/**
	 * Constructor.
	 */
	public function __construct() {
	}

	/**
	 * Update Wing version to current.
	 */
	private static function update_wing_version() {
		delete_option( 'shipox_version' );
		add_option( 'shipox_version', shipox()->version );
	}

	/**
	 * Install WC.
	 */
	public static function install() {
		self::create_files();
		self::update_wing_version();
	}

	/**
	 * Create files/directories.
	 */
	private static function create_files() {
		global $wp_filesystem;

		if ( ! function_exists( 'WP_Filesystem' ) ) {
			require_once ABSPATH . '/wp-admin/includes/file.php';
		}

		if ( ! $wp_filesystem && ! WP_Filesystem() ) {
			return;
		}

		// Bypass if filesystem is read-only and/or non-standard upload system is used
		if ( apply_filters( 'woocommerce_install_skip_create_files', false ) ) {
			return;
		}

		$files = array(
			array(
				'base'    => SHIPOX_LOGS,
				'file'    => '.htaccess',
				'content' => 'deny from all',
			),
			array(
				'base'    => SHIPOX_LOGS,
				'file'    => 'index.html',
				'content' => '',
			),
		);

		foreach ( $files as $file ) {
			$base_dir  = $file['base'];
			$file_path = trailingslashit( $base_dir ) . $file['file'];

			// Check if directory exists, create if not
			if ( ! $wp_filesystem->is_dir( $base_dir ) ) {
				if ( ! wp_mkdir_p( $base_dir ) ) {
					continue; // Unable to create directory, move to next file
				}
			}

			// Check if file exists, create if not
			if ( ! $wp_filesystem->exists( $file_path ) ) {
				if ( ! $wp_filesystem->exists( $file_path ) ) {
					$wp_filesystem->put_contents( $file_path, $file['content'], FS_CHMOD_FILE );
				}
			}
		}
	}
}
