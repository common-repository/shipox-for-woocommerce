// eslint-disable-next-line max-len
/*global woocommerce_admin_meta_boxes, woocommerce_admin, accounting, woocommerce_admin_meta_boxes_order, wcSetClipboard, wcClearClipboard */
jQuery(
	function ( $ ) {

		// Stand-in wcTracks.recordEvent in case tracks is not available (for any reason).
		window.wcTracks             = window.wcTracks || {};
		window.wcTracks.recordEvent = window.wcTracks.recordEvent || function() { };

		/**
		 * Shipox Order Data Panel
		 */
		var wc_shipox_meta_boxes_order = {
			init: function() {
				var self = this;
				$( document ).ready(
					function() {
						  self.load_shipox_package_prices();
					}
				);

				$( '#shipox-order-meta-box' ).on( 'click', 'button.create-shipox-awb-action', this.create_batch_shipox_order );
			},

			block: function() {
				$( '#shipox-order-meta-box' ).block(
					{
						message: null,
						overlayCSS: {
							background: '#fff',
							opacity: 0.6
						}
					}
				);
			},

			unblock: function() {
				$( '#shipox-order-meta-box' ).unblock();
			},

			load_shipox_package_prices: function() {
				wc_shipox_meta_boxes_order.block();

				var data = $.extend(
					{},
					{
						action:   'shipox_admin_get_order_packages',
						order_id: shipox_order_meta.post_id,
						security: shipox_order_meta.load_package_prices_nonce
					}
				);

				$.ajax(
					{
						url:  shipox_order_meta.ajax_url,
						data: data,
						type: 'POST',
						success: function( response ) {
							$( '#shipox-order-meta-box' ).find( '#wc-shipox-meta-container > .inner' ).empty();
							$( '#shipox-order-meta-box' ).find( '#wc-shipox-meta-container > .inner' ).append( response );

							wc_shipox_meta_boxes_order.unblock();
						},
						complete: function( response ) {
							wc_shipox_meta_boxes_order.unblock();

							window.wcTracks.recordEvent(
								'order_edit_recalc_totals',
								{
									order_id: data.post_id,
									OK_cancel: 'OK',
									status: $( '#order_status' ).val()
								}
							);
						}
					}
				);
			},

			create_batch_shipox_order: function() {
				wc_shipox_meta_boxes_order.block();

				var data = $.extend(
					{},
					{
						action:   'shipox_admin_order_create_awb',
						order_id: shipox_order_meta.post_id,
						items:    $( '#shipox-order-meta-box :input[name]' ).serialize(),
						security: shipox_order_meta.order_create_nonce
					}
				);

				$.ajax(
					{
						url:  shipox_order_meta.ajax_url,
						data: data,
						type: 'POST',
						dataType: 'json',
						success: async function( response ) {
							if (response.success === false) {
								$( '#shipox-order-meta-box' ).find( '#wc-shipox-meta-container > .response' ).html( response.data );
								wc_shipox_meta_boxes_order.unblock();
							} else {
								$( '#shipox-order-meta-box' ).find( '#wc-shipox-meta-container > .response' ).empty();
								// await wc_shipox_meta_boxes_order.load_shipox_package_prices();
								wc_shipox_meta_boxes_order.unblock();
								location.reload();
							}

							// $( '#woocommerce-order-items' ).find( '.inside' ).empty();
							// $( '#woocommerce-order-items' ).find( '.inside' ).append( response );
							// wc_meta_boxes_order_items.reloaded_items();
							// wc_shipox_meta_boxes_order.unblock();

							// $( document.body ).trigger( 'order-totals-recalculate-success', response );
						},
						// complete: function( response ) {
						// 	wc_shipox_meta_boxes_order.unblock();
						// 	// $( document.body ).trigger( 'order-totals-recalculate-complete', response );
						// 	//
						// 	// window.wcTracks.recordEvent( 'order_edit_recalc_totals', {
						// 	//     order_id: data.post_id,
						// 	//     OK_cancel: 'OK',
						// 	//     status: $( '#order_status' ).val()
						// 	// } );
						//
						// }
					}
				);

				return false;
			}
		};

		wc_shipox_meta_boxes_order.init();
	}
);
