/**
 * JavaScript for UserRelationship
 * Used on Special:ViewRelationshipRequests
 */
function requestResponse( response, id ){
	YAHOO.widget.Effects.Hide( 'request_action_' + id );
	sajax_request_type = 'POST';
	sajax_do_call( 'wfRelationshipRequestResponse', [ response, id ], function( request ){
		document.getElementById( 'request_action_' + id ).innerHTML = request.responseText;
		YAHOO.widget.Effects.Appear( 'request_action_' + id, { duration:2.0 } );
	} );
}