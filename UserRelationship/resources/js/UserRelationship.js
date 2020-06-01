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
		} ).done( function ( data ) {
			$( '#request_action_' + id )
				.html( data.html )
				.fadeIn( 2000 )
				.show();
		} );
	}

	$( function () {
		$( 'div.relationship-buttons input[type="submit"]' ).on( 'click', function ( e ) {
			e.preventDefault();
			requestResponse(
				$( this ).data( 'response' ),
				$( this ).parent().parent().parent().attr( 'id' ).replace( /request_action_/, '' )
			);
		} );
	} );

}() );
