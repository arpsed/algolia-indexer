/* global ajaxurl */

jQuery( $ => {
	$( '#sendToAlgolia' ).on( 'click', e => {
		const ppp = document.querySelector( '[name="ppp"]' ).value,
			$progress = $( '.send-products-progress' ),
			$button = $( 'button' );
		let page = 0,
			max = 0;

		$button.prop( 'disabled', true );
		$progress.append( $( '<li>' ).text( window.texts.start ) );
		doSend();

		function doSend() {
			$.ajax({
				method: 'POST',
				url: ajaxurl,
				dataType: 'json',
				data: {
					action: 'gg_send_products',
					nonce: document.getElementById( 'send_nonce' ).value,
					ppp: ppp,
					page: page,
				},
				success: payload => {
					page = payload.data.page;
					max  = payload.data.max;

					$progress.append( $( '<li>' ).text( `${page} of ${max}` ) );

					if ( page < max ) {
						doSend();
					} else {
						$progress.append( $( '<li>' ).text( window.texts.end ) );
						$button.prop( 'disabled', false );
					}
				},
				error: ( xhr, status, error ) => {
					$button.prop( 'disabled', false );

					if ( xhr.responseJSON !== undefined ) {
						if ( xhr.responseJSON.data !== undefined && xhr.responseJSON.data.message !== undefined ) {
							$progress.append( $( '<li>' ).text( xhr.responseJSON.data.message ) );
						} else {
							$progress.append( $( '<li>' ).text( error ) );
						}
					} else {
						$progress.append( $( '<li>' ).text( error ) );
					}
				},
			});
		}
	});
});
