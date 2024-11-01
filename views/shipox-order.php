<?php
/**
 * Created by Shipox.
 * User: Shipox
 *
 * @since 3.1.0
 * @package shipox
 */

if ( empty( $order_number ) ) {
	return;
}

?>
<table class="shipox_woocommerce_package_prices">
	<tbody id="shipox-order-details">
		<tr class="item">
			<td class="name">
				<div class="wc-order-item-option shipox-margin"><strong><?php echo __( 'Order Number', 'shipox' ); ?>: </strong> <a href="<?php echo esc_url( $tracking_url ); ?>" target="_blank"><?php echo '#' . $order_number; ?></a></div>
			</td>
		</tr>

		<?php if ( ! empty( $airwaybill ) ) : ?>
			<tr class="item">
				<td class="name">
					<div class="wc-order-item-option shipox-margin"><a href="<?php echo esc_url( $airwaybill ); ?>" target="_blank"><?php echo __( 'Download Airwaybill', 'shipox' ); ?></a></div>
				</td>
			</tr>
		<?php endif; ?>

		<?php if ( ! empty( $airwaybill_zebra ) ) : ?>
			<tr class="item">
				<td class="name">
					<div class="wc-order-item-option shipox-margin"><a href="<?php echo esc_url( $airwaybill_zebra ); ?>" target="_blank"><?php echo __( 'Download Mini Airwaybill', 'shipox' ); ?></a></div>
				</td>
			</tr>
		<?php endif; ?>
	</tbody>
</table>

