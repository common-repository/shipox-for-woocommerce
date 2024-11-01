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
 *  Class Shipox Options
 */
class Shipox_Options {

	/**
	 * Wing_Options constructor.
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
		add_action( 'admin_init', array( $this, 'init' ) );
	}

	/**
	 *   Add menu to Admin Menu Container
	 */
	public function add_admin_menu() {
		$dir = plugin_dir_url( SHIPOX_PLUGIN_FILE );
		add_menu_page( 'Shipox', 'Shipox', 'manage_options', 'wing', array( $this, 'render_options' ), $dir . 'assets/images/logo.png' );
	}


	/**
	 *   Init Options
	 */
	public function init() {
		$this->init_service_configuration_fields();
		$this->init_merchant_configuration_fields();
		$this->init_merchant_address_fields();
		$this->init_wing_order_configuration_fields();
	}

	/**
	 *  Init Service Configuration Fields
	 */
	public function init_service_configuration_fields() {
		register_setting(
			'service_config_section', // Option group
			'wing_service_config'
		);

		add_settings_section(
			'service_config_section_tab',
			__( 'Service Configuration', 'wing' ),
			array( $this, 'service_config_section_callback' ),
			'wingServiceConfig'
		);

		add_settings_field(
			'instance',
			__( 'Shipox Instance', 'wing' ),
			array( $this, 'shipox_instance_render' ),
			'wingServiceConfig',
			'service_config_section_tab'
		);

		add_settings_field(
			'test_mode',
			__( 'Debug Mode', 'wing' ),
			array( $this, 'test_mode_render' ),
			'wingServiceConfig',
			'service_config_section_tab'
		);

		add_settings_field(
			'auto_push',
			__( 'Auto Push', 'wing' ),
			array( $this, 'auto_push_render' ),
			'wingServiceConfig',
			'service_config_section_tab'
		);

		add_settings_field(
			'next_status',
			__( 'Change Status after the order pushed to Shipox', 'wing' ),
			array( $this, 'auto_select_status_after_push_render' ),
			'wingServiceConfig',
			'service_config_section_tab'
		);
	}

	/**
	 *   Merchant Configuration Fields
	 */
	public function init_merchant_configuration_fields() {
		register_setting(
			'merchant_config_section', // Option group
			'wing_merchant_config'
		);

		add_settings_section(
			'merchant_config_section_tab',
			__( 'Service Configuration', 'wing' ),
			array( $this, 'merchant_config_section_callback' ),
			'page_merchant_config'
		);

		add_settings_field(
			'merchant_username',
			__( 'Merchant Email', 'wing' ),
			array( $this, 'merchant_username_render' ),
			'page_merchant_config',
			'merchant_config_section_tab'
		);

		add_settings_field(
			'merchant_password',
			__( 'Merchant Password', 'wing' ),
			array( $this, 'merchant_password_render' ),
			'page_merchant_config',
			'merchant_config_section_tab'
		);

		add_settings_field(
			'merchant_get_token',
			'',
			array( $this, 'merchant_get_token_render' ),
			'page_merchant_config',
			'merchant_config_section_tab'
		);

		add_settings_field(
			'merchant_token',
			'',
			array( $this, 'merchant_token_render' ),
			'page_merchant_config',
			'merchant_config_section_tab'
		);
	}


	/**
	 *  Merchant Address Fields
	 */
	public function init_merchant_address_fields() {
		register_setting(
			'merchant_address_section', // Option group
			'wing_merchant_address'
		);

		add_settings_section(
			'merchant_address_section_tab',
			__( 'Merchant Address', 'wing' ),
			array( $this, 'merchant_address_section_callback' ),
			'page_merchant_address'
		);

		add_settings_field(
			'merchant_company_name',
			__( 'Company Name', 'wing' ),
			array( $this, 'merchant_company_name_render' ),
			'page_merchant_address',
			'merchant_address_section_tab'
		);

		add_settings_field(
			'merchant_contact_name',
			__( 'Contact Name', 'wing' ),
			array( $this, 'merchant_contact_name_render' ),
			'page_merchant_address',
			'merchant_address_section_tab'
		);

		add_settings_field(
			'merchant_contact_email',
			__( 'Contact Email', 'wing' ),
			array( $this, 'merchant_contact_email_render' ),
			'page_merchant_address',
			'merchant_address_section_tab'
		);

		add_settings_field(
			'merchant_city',
			__( 'City', 'wing' ),
			array( $this, 'merchant_city_render' ),
			'page_merchant_address',
			'merchant_address_section_tab'
		);

		add_settings_field(
			'merchant_postcode',
			__( 'PostCode', 'wing' ),
			array( $this, 'merchant_postcode_render' ),
			'page_merchant_address',
			'merchant_address_section_tab'
		);

		add_settings_field(
			'merchant_street',
			__( 'Street', 'wing' ),
			array( $this, 'merchant_street_render' ),
			'page_merchant_address',
			'merchant_address_section_tab'
		);

		add_settings_field(
			'wing_merchant_address',
			__( 'Address', 'wing' ),
			array( $this, 'merchant_address_render' ),
			'page_merchant_address',
			'merchant_address_section_tab'
		);

		add_settings_field(
			'merchant_phone',
			__( 'Phone', 'wing' ),
			array( $this, 'merchant_phone_render' ),
			'page_merchant_address',
			'merchant_address_section_tab'
		);

		add_settings_field(
			'merchant_lat_long',
			__( 'Latitude & Longitude', 'wing' ),
			array( $this, 'merchant_lat_long_render' ),
			'page_merchant_address',
			'merchant_address_section_tab'
		);

		add_settings_field(
			'merchant_details',
			__( 'Details', 'wing' ),
			array( $this, 'merchant_details_render' ),
			'page_merchant_address',
			'merchant_address_section_tab'
		);
	}


	/**
	 *  Init Configuration Fields
	 */
	public function init_wing_order_configuration_fields() {
		register_setting(
			'wing_order_config_section', // Option group
			'wing_order_config'
		);

		add_settings_section(
			'wing_order_config_section_tab',
			__( 'Order Configuration', 'wing' ),
			array( $this, 'wing_order_config_section_callback' ),
			'page_wing_order_config'
		);

		add_settings_field(
			'order_international_availability',
			__( 'International Order Availability', 'wing' ),
			array( $this, 'order_international_availability_render' ),
			'page_wing_order_config',
			'wing_order_config_section_tab'
		);

		add_settings_field(
			'order_default_weight',
			__( 'Default Weight', 'wing' ),
			array( $this, 'order_default_weight_render' ),
			'page_wing_order_config',
			'wing_order_config_section_tab'
		);

		add_settings_field(
			'order_default_courier_type',
			__( 'Default Courier Type', 'wing' ),
			array( $this, 'order_default_courier_type_render' ),
			'page_wing_order_config',
			'wing_order_config_section_tab'
		);

		add_settings_field(
			'order_default_payment_option',
			__( 'Default Payment Option', 'wing' ),
			array( $this, 'order_default_payment_option_render' ),
			'page_wing_order_config',
			'wing_order_config_section_tab'
		);
	}

	/**
	 *   Service Section
	 */
	public function service_config_section_callback() {
		echo '';
	}

	/**
	 *   Service Test Mode Render
	 */
	function shipox_instance_render() {
		$options      = get_option( 'wing_service_config' );
		$option_value = intval( shipox_get_array_string_value( $options, 'instance', 1 ) );

		?>
		<select name='wing_service_config[instance]' class="wing-input-class" title="<?php echo esc_attr__( 'Shipox Instance', 'shipox' ); ?>">
			<option value='1' <?php selected( $option_value, 1 ); ?>><?php echo esc_html__( 'Instance 1', 'shipox' ); ?></option>
			<option value='2' <?php selected( $option_value, 2 ); ?>><?php echo esc_html__( 'Instance 2', 'shipox' ); ?></option>
			<option value='3' <?php selected( $option_value, 3 ); ?>><?php echo esc_html__( 'Instance 3', 'shipox' ); ?></option>
		</select>
		<?php

	}

	/**
	 *   Service Test Mode Render
	 */
	function test_mode_render() {
		$options      = get_option( 'wing_service_config' );
		$option_value = intval( shipox_get_array_string_value( $options, 'test_mode', 1 ) );

		?>
		<select name='wing_service_config[test_mode]' class="wing-input-class" title="<?php echo esc_attr__( 'Debug Mode', 'shipox' ); ?>">
			<option value='1' <?php selected( $option_value, 1 ); ?>><?php echo esc_html__( 'Yes', 'shipox' ); ?></option>
			<option value='2' <?php selected( $option_value, 2 ); ?>><?php echo esc_html__( 'No', 'shipox' ); ?></option>
		</select>
		<?php

	}


	/**
	 *  Autopush Render
	 */
	function auto_push_render() {
		$options        = get_option( 'wing_service_config' );
		$order_statuses = wc_get_order_statuses();
		$option_value   = shipox_get_array_string_value( $options, 'auto_push', 0 );

		?>
		<select name='wing_service_config[auto_push]' class="wing-input-class" title="<?php echo esc_attr__( 'Auto Push', 'shipox' ); ?>">
			<option value="0" <?php selected( $option_value, 0 ); ?>><?php echo esc_attr__( 'Off', 'shipox' ); ?></option>
			<?php
			foreach ( $order_statuses as $key => $status ) {
				echo '<option value="' . $key . '" ' . selected( $option_value, $key ) . '>' . esc_html( $status ) . '</option>';
			}
			?>
		</select>
		<p><strong><?php echo esc_html__( 'INFO', 'shipox' ); ?>:</strong> <?php echo esc_html__( 'You can select auto push trigger function on which order status. Off - means auto push is disabled', 'shipox' ); ?></p>
		<?php
	}

	/**
	 *  Autopush Render
	 */
	function auto_select_status_after_push_render() {
		$options        = get_option( 'wing_service_config' );
		$order_statuses = wc_get_order_statuses();
		$option_value   = shipox_get_array_string_value( $options, 'next_status' );

		?>
		<select name='wing_service_config[next_status]' class="wing-input-class" title="<?php echo esc_attr__( 'Next Status', 'shipox' ); ?>">
			<option value="" <?php selected( $option_value, 0 ); ?>><?php echo esc_html__( 'No Action', 'shipox' ); ?></option>
			<?php
			foreach ( $order_statuses as $key => $status ) {
				echo '<option value="' . $key . '" ' . selected( $option_value, $key ) . '>' . esc_html( $status ) . '</option>';
			}
			?>
		</select>
		<p><strong><?php echo esc_html__( 'ATTENTION', 'shipox' ); ?>:</strong> <?php echo esc_html__( 'It might conflict with AUTO PUSH status, so make sure they are not intersection each other', 'shipox' ); ?></p>
		<?php
	}



	/**
	 *   Merchant Section
	 */
	public function merchant_config_section_callback() {
		echo '';
	}

	/**
	 *  Merchant UserName Field
	 */
	public function merchant_username_render() {
		$options      = get_option( 'wing_merchant_config' );
		$option_value = shipox_get_array_string_value( $options, 'merchant_username' );

		?>
		<input id='shipox_merchant_username' class="wing-input-class" type='text' required title="<?php echo esc_attr__( 'Merchant Username', 'shipox' ); ?>" name='wing_merchant_config[merchant_username]' value='<?php echo esc_attr( $option_value ); ?>' />
		<?php
	}

	/**
	 *  Merchant Password Render
	 */
	function merchant_password_render() {
		$options      = get_option( 'wing_merchant_config' );
		$option_value = shipox_get_array_string_value( $options, 'merchant_password' );

		?>
		<input id='shipox_merchant_password' class="wing-input-class" type='password' required title="<?php echo esc_attr__( 'Merchant Password', 'shipox' ); ?>" name='wing_merchant_config[merchant_password]' value='<?php echo esc_attr( $option_value ); ?>' />
		<?php
	}

	/**
	 *  Merchant Get Token Render
	 */
	function merchant_get_token_render() {
		?>
		<input type="hidden" id="shipoxTokenNonce" value="<?php echo wp_create_nonce( 'shipox-wp-woocommerse-plugin' ); ?>">
		<button id="shipoxGetToken" class="button button-primary"><?php echo esc_html__( 'Get Token', 'shipox' ); ?></button>
		<?php
	}

	/**
	 *  Merchant Token Hidden Field
	 */
	function merchant_token_render() {
		$options      = get_option( 'wing_merchant_config' );
		$option_value = shipox_get_array_string_value( $options, 'merchant_token' );

		?>
		<textarea id="woocommerce_shipox_token" title="<?php echo esc_attr__( 'Merchant token', 'shipox' ); ?>" style="visibility: hidden" name="wing_merchant_config[merchant_token]"><?php echo $option_value; ?></textarea>
		<?php
	}


	/**
	 *  Merchant Address Section Callback
	 */
	public function merchant_address_section_callback() {
		echo '';
	}

	/**
	 *  Merchant Company Name Render
	 */
	function merchant_company_name_render() {
		$options      = get_option( 'wing_merchant_address' );
		$option_value = shipox_get_array_string_value( $options, 'merchant_company_name' );

		?>
		<input id='wing_woocommerce_company_name' class="wing-input-class" type='text' title="<?php echo esc_attr__( 'Merchant Company Name', 'shipox' ); ?>" name='wing_merchant_address[merchant_company_name]' required value='<?php echo esc_attr( $option_value ); ?>' />
		<?php
	}

	/**
	 *  Merchant Contact Name Render
	 */
	function merchant_contact_name_render() {
		$options      = get_option( 'wing_merchant_address' );
		$option_value = shipox_get_array_string_value( $options, 'merchant_contact_name' );

		?>
		<input id='wing_woocommerce_contact_name' class="wing-input-class" type='text' title="<?php echo esc_attr__( 'Merchant Contact Name', 'shipox' ); ?>" name='wing_merchant_address[merchant_contact_name]' required value='<?php echo esc_attr( $option_value ); ?>' />
		<?php
	}

	/**
	 *  Merchant Contact Email Render
	 */
	function merchant_contact_email_render() {
		$options      = get_option( 'wing_merchant_address' );
		$option_value = shipox_get_array_string_value( $options, 'merchant_contact_email' );

		?>
		<input id='wing_woocommerce_contact_name' class="wing-input-class" type='text' title="<?php echo esc_attr__( 'Merchant Email', 'shipox' ); ?>" name='wing_merchant_address[merchant_contact_email]' required value='<?php echo esc_attr( $option_value ); ?>' />
		<?php
	}

	/**
	 *  Merchant City Render
	 */
	function merchant_city_render() {
		$options      = get_option( 'wing_merchant_address' );
		$option_value = shipox_get_array_string_value( $options, 'merchant_city' );

		?>
		<input id='wing_woocommerce_city' class="wing-input-class" type='text' title="<?php echo esc_attr__( 'Merchant City', 'shipox' ); ?>" name='wing_merchant_address[merchant_city]' required value='<?php echo esc_attr( $option_value ); ?>' />
		<?php
	}

	/**
	 *  Merchant Postcode Render
	 */
	function merchant_postcode_render() {
		$options      = get_option( 'wing_merchant_address' );
		$option_value = shipox_get_array_string_value( $options, 'merchant_postcode' );

		?>
		<input id='wing_woocommerce_postcode' class="wing-input-class" type='text' title="<?php echo esc_attr__( 'Merchant Postcode', 'shipox' ); ?>" name='wing_merchant_address[merchant_postcode]' value='<?php echo esc_attr( $option_value ); ?>' />
		<?php
	}

	/**
	 *  Merchant Street Render
	 */
	function merchant_street_render() {
		$options      = get_option( 'wing_merchant_address' );
		$option_value = shipox_get_array_string_value( $options, 'merchant_street' );

		?>
		<input id='wing_woocommerce_street' class="wing-input-class" type='text' title="<?php echo esc_attr__( 'Merchant Street', 'shipox' ); ?>" name='wing_merchant_address[merchant_street]' value='<?php echo esc_attr( $option_value ); ?>' />
		<?php
	}

	/**
	 *  Merchant Address Render
	 */
	function merchant_address_render() {
		$options      = get_option( 'wing_merchant_address' );
		$option_value = shipox_get_array_string_value( $options, 'merchant_address' );

		?>
		<input id='wing_woocommerce_address' class="wing-input-class" type='text' title="<?php echo esc_attr__( 'Merchant Address', 'shipox' ); ?>" name='wing_merchant_address[merchant_address]' required value='<?php echo esc_attr( $option_value ); ?>' />
		<?php
	}

	/**
	 *  Merchant Phone Render
	 */
	function merchant_phone_render() {
		$options      = get_option( 'wing_merchant_address' );
		$option_value = shipox_get_array_string_value( $options, 'merchant_phone' );

		?>
		<input id='wing_woocommerce_phone' class="wing-input-class" type='text'  title="<?php echo esc_attr__( 'Merchant Phone Number', 'shipox' ); ?>" name='wing_merchant_address[merchant_phone]' required value='<?php echo esc_attr( $option_value ); ?>' />
		<?php
	}

	/**
	 *  Merchant Latitude & Longitude Render
	 */
	function merchant_lat_long_render() {
		$options      = get_option( 'wing_merchant_address' );
		$option_value = shipox_get_array_string_value( $options, 'merchant_lat_long' );

		?>
		<input id='wing_woocommerce_lat_long' class="wing-input-class" type='text' title="<?php echo esc_attr__( 'Merchant Latitude & Longitude', 'shipox' ); ?>" name='wing_merchant_address[merchant_lat_long]' required value='<?php echo esc_attr( $option_value ); ?>' />
		<p><strong><?php echo esc_html__( 'Important', 'shipox' ); ?>:</strong> <?php echo esc_html__( 'Merchant Latitude & Longitude is field is required. Latitude & Longitude field should include only numbers and separatd with comma (lat,lon)', 'shipox' ); ?></p>
		<?php
	}

	/**
	 *  Merchant Details Render
	 */
	function merchant_details_render() {
		$options      = get_option( 'wing_merchant_address' );
		$option_value = shipox_get_array_string_value( $options, 'merchant_details' );

		?>
		<textarea id='wing_woocommerce_details' class="wing-input-class" cols="4" rows="8" title="<?php echo esc_attr__( 'Merchant Details', 'shipox' ); ?>" name='wing_merchant_address[merchant_details]'><?php echo esc_attr( $option_value ); ?></textarea>
		<?php
	}


	/**
	 *   Order Configuration Callback
	 */
	public function wing_order_config_section_callback() {
		echo '';
	}


	/**
	 *  International Order Availability
	 */
	public function order_international_availability_render() {
		$options      = get_option( 'wing_order_config' );
		$option_value = intval( shipox_get_array_string_value( $options, 'order_international_availability', 0 ) );

		?>
		<select name='wing_order_config[order_international_availability]' class="wing-input-class"  title="<?php echo esc_attr__( 'Default International Availability', 'shipox' ); ?>">
			<option value='0' <?php selected( $option_value, 0 ); ?>><?php echo esc_html__( 'Not available', 'shipox' ); ?></option>
			<option value='1' <?php selected( $option_value, 1 ); ?>><?php echo esc_html__( 'Available', 'shipox' ); ?></option>
		</select>
		<?php
	}
	/**
	 *  Default Weight
	 */
	public function order_default_weight_render() {
		$options      = get_option( 'wing_order_config' );
		$option_value = intval( shipox_get_array_string_value( $options, 'order_default_weight', 0 ) );

		?>
		<select name='wing_order_config[order_default_weight]' class="wing-input-class"  title="<?php echo esc_attr__( 'Default Weight', 'shipox' ); ?>">
			<?php
			$items = shipox()->wing['menu-type']->to_option_array();
			foreach ( $items as $item ) {
				echo '<option value="' . esc_attr( $item['value'] ) . '" ' . selected( $option_value, $item['value'] ) . '>' . esc_html( $item['label'] ) . '</option>';
			}
			?>
		</select>
		<?php
	}

	/**
	 *  Default Courier Type
	 */
	public function order_default_courier_type_render() {
		$options      = get_option( 'wing_order_config' );
		$option_value = shipox_get_array_value( $options, 'order_default_courier_type' );

		?>
		<select name=wing_order_config[order_default_courier_type][]' class="wing-input-class"  title="<?php echo esc_attr__( 'Default Courier Type', 'shipox' ); ?>" multiple>
			<?php
			$items = shipox()->wing['courier-type']->to_option_array();
			foreach ( $items as $item ) {
				$is_selected = in_array( $item['value'], $option_value, true );
				echo '<option value="' . esc_attr( $item['value'] ) . '" ' . selected( $is_selected ) . '>' . esc_html( $item['label'] ) . '</option>';
			}
			?>
		</select>
		<?php
	}

	/**
	 *  Default Payment Type
	 */
	public function order_default_payment_option_render() {
		$options      = get_option( 'wing_order_config' );
		$option_value = shipox_get_array_string_value( $options, 'order_default_payment_option' );

		?>
		<select name='wing_order_config[order_default_payment_option]' class="wing-input-class" title="<?php echo esc_attr__( 'Default Payment Option', 'shipox' ); ?>">
			<?php
			$items = shipox()->wing['payment-type']->to_option_array();
			foreach ( $items as $item ) {
				echo '<option value="' . esc_attr( $item['value'] ) . '" ' . selected( $option_value, $item['value'] ) . '>' . esc_html( $item['label'] ) . '</option>';
			}
			?>
		</select>
		<p><strong><?php echo esc_html__( 'Important', 'shipox' ); ?>:</strong> <?php echo esc_html__( 'If you are Credit Balance Customer, your public charge will be 0 when pushing order to the Shipox', 'shipox' ); ?></p>
		<?php
	}

	/**
	 *  Rendering Options Fields
	 */
	public function render_options() {
		wp_enqueue_script( 'shipox_admin_ajax' );

		if ( isset( $_GET['tab'] ) ) { //phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$active_tab = sanitize_text_field( $_GET['tab'] ); //phpcs:ignore WordPress.Security.NonceVerification.Recommended
		} else {
			$active_tab = 'service_config';
		}
		?>
		<form action='options.php' method='post'>
			<h1><?php esc_html__( 'Shipox Merchant Settings', 'shipox' ); ?></h1>
			<hr/>
			<h2 class="nav-tab-wrapper">
				<a href="?page=<?php echo SHIPOX_SLUG; ?>" class="nav-tab <?php echo 'service_config' === $active_tab ? 'nav-tab-active' : ''; ?>">
					1.<?php echo esc_html__( 'Service Configuration', 'shipox' ); ?>
				</a>
				<a href="?page=<?php echo SHIPOX_SLUG; ?>&tab=merchant_info" class="nav-tab <?php echo 'merchant_info' === $active_tab ? 'nav-tab-active' : ''; ?>">
					2.<?php echo esc_html__( 'Merchant Credentials', 'shipox' ); ?>
				</a>
				<a href="?page=<?php echo SHIPOX_SLUG; ?>&tab=merchant_address" class="nav-tab <?php echo 'merchant_address' === $active_tab ? 'nav-tab-active' : ''; ?>">
					3.<?php echo esc_html__( 'Merchant Address Details', 'shipox' ); ?>
				</a>
				<a href="?page=<?php echo SHIPOX_SLUG; ?>&tab=order_settings" class="nav-tab <?php echo 'order_settings' === $active_tab ? 'nav-tab-active' : ''; ?>">
					4.<?php echo esc_html__( 'Order Settings', 'shipox' ); ?>
				</a>
			</h2>
			<?php
			if ( 'service_config' === $active_tab ) {
				settings_fields( 'service_config_section' );
				do_settings_sections( 'wingServiceConfig' );
			} elseif ( 'merchant_info' === $active_tab ) {
				settings_fields( 'merchant_config_section' );
				do_settings_sections( 'page_merchant_config' );
			} elseif ( 'merchant_address' === $active_tab ) {
				settings_fields( 'merchant_address_section' );
				do_settings_sections( 'page_merchant_address' );
			} elseif ( 'order_settings' === $active_tab ) {
				settings_fields( 'wing_order_config_section' );
				do_settings_sections( 'page_wing_order_config' );
			}
			submit_button();
			?>
		</form>
		<?php
	}
}

new Shipox_Options();
