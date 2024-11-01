<?php
/**
 * Shipox - Functions
 *
 * @package shipox
 * @version 3.0.0
 * @since   3.0.0
 */

use Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController;

if ( ! function_exists( 'shipox_woo_custom_orders_table_use_enabled' ) ) {
	/**
	 * Check if Woocommernce HPOS Custom Order Table is enabled or not?
	 * @return bool
	 */
	function shipox_woo_custom_orders_table_use_enabled() {
		return class_exists( '\Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController' ) && wc_get_container()->get( CustomOrdersTableController::class )->custom_orders_table_usage_is_enabled();
	}
}
