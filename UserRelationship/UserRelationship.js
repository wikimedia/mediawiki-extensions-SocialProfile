/**
 * JavaScript for UserRelationship
 * Used on Special:ViewRelationshipRequests
 */
function requestResponse( response, id ) {
	document.getElementById( 'request_action_' + id ).style.display = 'none';
	document.getElementById( 'request_action_' + id ).style.visibility = 'hidden';

	jQuery.post(
		mw.util.wikiScript(), {
			action: 'ajax',
			rs: 'wfRelationshipRequestResponse',
			rsargs: [response, id]
		},
		function( data ) {
			document.getElementById( 'request_action_' + id ).innerHTML = data;
			jQuery( '#request_action_' + id ).fadeIn( 2000 );
			document.getElementById( 'request_action_' + id ).style.display = 'block';
			document.getElementById( 'request_action_' + id ).style.visibility = 'visible';
		}
	);
}

jQuery( document ).ready( function() {
	jQuery( 'div.relationship-buttons input[type="button"]' ).on( 'click', function() {
		requestResponse(
			jQuery( this ).data( 'response' ),
			jQuery( this ).parent().parent().attr( 'id' ).replace( /request_action_/, '' )
		);
	} );
} );