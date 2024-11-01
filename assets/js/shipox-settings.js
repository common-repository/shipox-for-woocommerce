(function($){
	$( '#shipoxGetToken' ).on(
		'click',
		function ($e) {
			$e.preventDefault();

			let $data                 = [];
			$data['merchantEmail']    = $( '#shipox_merchant_username' ).val();
			$data['merchantPassword'] = $( '#shipox_merchant_password' ).val();
			$data['nonce']            = $( '#shipoxTokenNonce' ).val();

			$.ajax(
				{
					method: "post",
					url: shipoxAjax.ajax_url,
					data: {
						action: 'get_shipox_token',
						merchantEmail: $data['merchantEmail'],
						merchantPassword: $data['merchantPassword'],
						nonce: shipoxAjax.ajax_nonce
					}
				}
			).success(
				function (response) {
					let json = $.parseJSON( response );
					if (json['success']) {
						$( '#woocommerce_shipox_token' ).val( json['token'] );
						alert( 'The Customer successfully authorized' );
					} else {
						alert( json['message'] ? json['message'] : 'Process incomplete. Please check your credentials and try again.' );
					}
				}
			)
		}
	)
})( jQuery );
