var BoardBlast = {
	submitted: 0,

	toggleUser: function( user_id ) {
		var elem = jQuery( '#user-' + user_id );

		if ( elem.hasClass( 'blast-friend-selected' ) ) {
			elem.removeClass( 'blast-friend-selected' )
				.addClass( 'blast-friend-unselected' );
		} else if ( elem.hasClass( 'blast-friend-unselected' ) ) {
			elem.removeClass( 'blast-friend-unselected' )
				.addClass( 'blast-friend-selected' );
		}

		if ( elem.hasClass( 'blast-foe-selected' ) ) {
			elem.removeClass( 'blast-foe-selected' )
				.addClass( 'blast-foe-unselected' );
		} else if ( elem.hasClass( 'blast-foe-unselected' ) ) {
			elem.removeClass( 'blast-foe-unselected' )
				.addClass( 'blast-foe-selected' );
		}
	},

	toggleType: function( method, on, off ) {
		var list = jQuery( '#blast-friends-list div.' + ( ( method == 1 ) ? off : on ) );

		for ( var x = 0; x <= list.length - 1; x++ ) {
			var el = list[x];
			if ( jQuery( el ).hasClass( on ) || jQuery( el ).hasClass( off ) ) {
				if ( method == 1 ) {
					jQuery( el ).removeClass( off ).addClass( on );
				} else {
					jQuery( el ).removeClass( on ).addClass( off );
				}
			}
		}
	},

	toggleFriends: function( method ) {
		BoardBlast.toggleType(
			method,
			'blast-friend-selected',
			'blast-friend-unselected'
		);
	},

	toggleFoes: function( method ) {
		BoardBlast.toggleType(
			method,
			'blast-foe-selected',
			'blast-foe-unselected'
		);
	},

	selectAll: function() {
		BoardBlast.toggleFriends( 1 );
		BoardBlast.toggleFoes( 1 );
	},

	unselectAll: function() {
		BoardBlast.toggleFriends( 0 );
		BoardBlast.toggleFoes( 0 );
	},

	sendMessages: function() {
		if ( BoardBlast.submitted == 1 ) {
			return 0;
		}

		BoardBlast.submitted = 1;
		var selected = 0;
		var user_ids_to = '';

		var list = jQuery( '#blast-friends-list div.blast-friend-selected' );
		var el, user_id;
		for ( var x = 0; x <= list.length - 1; x++ ) {
			el = list[x];
			selected++;
			user_id = el.id.replace( 'user-', '' );
			user_ids_to += ( ( user_ids_to ) ? ',' : '' ) + user_id;
		}

		list = jQuery( '#blast-friends-list div.blast-foe-selected' );
		for ( x = 0; x <= list.length - 1; x++ ) {
			el = list[x];
			selected++;
			user_id = el.id.replace( 'user-', '' );
			user_ids_to += ( ( user_ids_to ) ? ',' : '' ) + user_id;
		}

		if ( selected === 0 ) {
			alert( mw.msg( 'boardblast-js-error-missing-user' ) );
			BoardBlast.submitted = 0;
			return 0;
		}

		if ( !document.getElementById( 'message' ).value ) {
			alert( mw.msg( 'boardblast-js-error-missing-message' ) );
			BoardBlast.submitted = 0;
			return 0;
		}

		document.getElementById( 'ids' ).value = user_ids_to;

		document.blast.message.style.color = '#ccc';
		document.blast.message.readOnly = true;
		document.getElementById( 'blast-friends-list' ).innerHTML = mw.msg( 'boardblast-js-sending' );
		document.blast.submit();
	}
};

jQuery( document ).ready( function() {
	// "Select/Unselect all" links
	jQuery( 'div.blast-nav-links a.blast-select-all-link' ).on( 'click', function() {
		BoardBlast.selectAll();
	} );

	jQuery( 'div.blast-nav-links a.blast-unselect-all-link' ).on( 'click', function() {
		BoardBlast.unselectAll();
	} );

	// "Select/Unselect friends" links
	jQuery( 'div.blast-nav-links a.blast-select-friends-link' ).on( 'click', function() {
		BoardBlast.toggleFriends( 1 );
	} );

	jQuery( 'div.blast-nav-links a.blast-unselect-friends-link' ).on( 'click', function() {
		BoardBlast.toggleFriends( 0 );
	} );

	// "Select/Unselect foes" links
	jQuery( 'div.blast-nav-links a.blast-select-foes-link' ).on( 'click', function() {
		BoardBlast.toggleFoes( 1 );
	} );

	jQuery( 'div.blast-nav-links a.blast-unselect-foes-link' ).on( 'click', function() {
		BoardBlast.toggleFoes( 0 );
	} );

	// Toggling for an individual user
	jQuery( 'div#blast-friends-list div[id^="user-"]' ).on( 'click', function() {
		BoardBlast.toggleUser( jQuery( this ).attr( 'id' ).replace( /user-/, '' ) );
	} );

	// The submit button
	jQuery( 'div.blast-message-box-button input[type="button"]' ).on( 'click', function() {
		BoardBlast.sendMessages();
	} );
} );