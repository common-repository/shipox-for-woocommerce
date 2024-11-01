<?php
/**
 * Created by Shipox.
 * User: Shipox
 *
 * @since 3.1.0
 * @package shipox
 */
?>
<div class="shipox-package-prices-container">
	<?php
	woocommerce_wp_text_input(
		array(
			'label'         => 'Box Count:',
			'class'         => 'wing-input-class',
			'id'            => 'shipox_box_count',
			'value'         => '1',
			'wrapper_class' => 'form-field-wide',
		)
	);

	woocommerce_wp_select(
		array(
			'id'            => 'wing_package',
			'label'         => 'Packages:',
			'value'         => $selected_package,
			'class'         => 'wing-select-field',
			'options'       => $package_options,
			'wrapper_class' => 'form-field-wide',
		)
	);

	woocommerce_wp_hidden_input(
		array(
			'id'            => 'wing_custom_lat_lon',
			'value'         => $to_lat_lon,
			'wrapper_class' => 'form-field-wide',
		)
	);
	?>
	<div class="wc-order-data-row wc-order-bulk-actions wc-order-data-row-toggle">
		<button type="button" class="button button-primary create-shipox-awb-action"><?php echo __( 'Create Shipox AWB', 'shipox' ); ?></button>
	</div>
</div>

