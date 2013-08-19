var UserBoard = {
	posted: 0,

	sendMessage: function( perPage ) {
		if ( !perPage ) {
			perPage = 25;
		}
		var message = document.getElementById( 'message' ).value,
			recipient = document.getElementById( 'user_name_to' ).value,
			sender = document.getElementById( 'user_name_from' ).value;
		if ( message && !UserBoard.posted ) {
			UserBoard.posted = 1;
			var encodedName = encodeURIComponent( recipient ),
				encodedMsg = encodeURIComponent( message ),
				messageType = document.getElementById( 'message_type' ).value;
			jQuery.post(
				mw.util.wikiScript(), {
					action: 'ajax',
					rs: 'wfSendBoardMessage',
					rsargs: [encodedName, encodedMsg, messageType, perPage]
				},
				function( data ) {
					UserBoard.posted = 0;
					var user_1, user_2;
					if ( sender ) { // it's a board to board
						user_1 = sender;
						user_2 = recipient;
					} else {
						user_1 = recipient;
						user_2 = '';
					}
					var params = ( user_2 ) ? '&conv=' + user_2 : '';
					var url = mw.config.get( 'wgScriptPath' ) + '/index.php?title=Special:UserBoard&user=' + user_1 + params;
					window.location = url;
				}
			);
		}
	},

	deleteMessage: function( id ) {
		if ( confirm( mw.msg( 'userboard_confirmdelete' ) ) ) {
			jQuery.post(
				mw.util.wikiScript(), {
					action: 'ajax',
					rs: 'wfDeleteBoardMessage',
					rsargs: [id]
				},
				function( data ) {
					//window.location.reload();
					// 1st parent = span.user-board-red
					// 2nd parent = div.user-board-message-links
					// 3rd parent = div.user-board-message = the container of a msg
					jQuery( this ).parent().parent().parent().hide( 100 );
				}
			);
		}
	}
};

jQuery( document ).ready( function() {
	// "Delete" link
	jQuery( 'span.user-board-red a' ).on( 'click', function() {
		UserBoard.deleteMessage( jQuery( this ).data( 'message-id' ) );
	} );

	// Submit button
	jQuery( 'div.user-page-message-box-button input[type="button"]' ).on( 'click', function() {
		UserBoard.sendMessage( jQuery( this ).data( 'per-page' ) );
	} );
} );