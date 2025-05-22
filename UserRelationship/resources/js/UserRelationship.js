/**
 * JavaScript for UserRelationship
 * Used on Special:ViewRelationshipRequests
 */
( function () {

	function requestResponse( response, id ) {
		$( '#request_action_' + id ).hide();

		( new mw.Api() ).postWithToken( 'csrf', {
			action: 'socialprofile-request-response',
			format: 'json',
			response: response,
			id: id
		} ).done( ( data ) => {
			const html = '<div class="relationship-action red-text">' +
				data.response.avatar +
				// I have no idea why CI is complaining when the message keys are *clearly*
				// documented below, but this gets rid of that complaint:
				// eslint-disable-next-line mediawiki/msg-doc
				mw.msg(
					// i18n message keys used here:
					// ur-requests-added-message-friend, ur-requests-added-message-foe,
					// ur-requests-reject-message-friend, ur-requests-reject-message-foe
					'ur-requests-' + data.response.action + '-message-' + data.response.rel_type,
					data.response.requester
				) + '<div class="visualClear"></div>' +
				'</div>';
			$( '#request_action_' + id )
				.html( html )
				.fadeIn( 2000 )
				.show();
		} );
	}

	$( () => {
		$( 'div.relationship-buttons input[type="submit"]' ).on( 'click', function ( e ) {
			e.preventDefault();
			requestResponse(
				$( this ).data( 'response' ),
				$( this ).parent().parent().parent().attr( 'id' ).replace( /request_action_/, '' )
			);
		} );
	} );

}() );
