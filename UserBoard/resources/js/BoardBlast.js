var BoardBlast = {
	submitted: 0,

	toggleUser: function ( user_id ) {
		var elem = $( '#user-' + user_id );

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

	toggleType: function ( method, on, off ) {
		var list = $( '#blast-friends-list div.' + ( ( method === 1 ) ? off : on ) );

		for ( var x = 0; x <= list.length - 1; x++ ) {
			var el = list[ x ];
			if ( $( el ).hasClass( on ) || $( el ).hasClass( off ) ) {
				if ( method === 1 ) {
					$( el ).removeClass( off ).addClass( on );
				} else {
					$( el ).removeClass( on ).addClass( off );
				}
			}
		}
	},

	toggleFriends: function ( method ) {
		BoardBlast.toggleType(
			method,
			'blast-friend-selected',
			'blast-friend-unselected'
		);
	},

	toggleFoes: function ( method ) {
		BoardBlast.toggleType(
			method,
			'blast-foe-selected',
			'blast-foe-unselected'
		);
	},

	selectAll: function () {
		BoardBlast.toggleFriends( 1 );
		BoardBlast.toggleFoes( 1 );
	},

	unselectAll: function () {
		BoardBlast.toggleFriends( 0 );
		BoardBlast.toggleFoes( 0 );
	},

	sendMessages: function () {
		if ( BoardBlast.submitted === 1 ) {
			return 0;
		}

		BoardBlast.submitted = 1;
		var selected = 0;
		var user_ids_to = '';

		var list = $( '#blast-friends-list div.blast-friend-selected' );
		var el, user_id;
		for ( var x = 0; x <= list.length - 1; x++ ) {
			el = list[ x ];
			selected++;
			user_id = el.id.replace( 'user-', '' );
			user_ids_to += ( ( user_ids_to ) ? ',' : '' ) + user_id;
		}

		list = $( '#blast-friends-list div.blast-foe-selected' );
		for ( x = 0; x <= list.length - 1; x++ ) {
			el = list[ x ];
			selected++;
			user_id = el.id.replace( 'user-', '' );
			user_ids_to += ( ( user_ids_to ) ? ',' : '' ) + user_id;
		}

		if ( selected === 0 ) {
			window.alert( mw.msg( 'boardblast-error-missing-user' ) );
			BoardBlast.submitted = 0;
			return 0;
		}

		if ( !document.getElementById( 'message' ).value ) {
			window.alert( mw.msg( 'boardblast-error-missing-message' ) );
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

$( function () {
	// "Select/Unselect all" links
	$( 'div.blast-nav-links a.blast-select-all-link' ).on( 'click', function () {
		BoardBlast.selectAll();
	} );

	$( 'div.blast-nav-links a.blast-unselect-all-link' ).on( 'click', function () {
		BoardBlast.unselectAll();
	} );

	// "Select/Unselect friends" links
	$( 'div.blast-nav-links a.blast-select-friends-link' ).on( 'click', function () {
		BoardBlast.toggleFriends( 1 );
	} );

	$( 'div.blast-nav-links a.blast-unselect-friends-link' ).on( 'click', function () {
		BoardBlast.toggleFriends( 0 );
	} );

	// "Select/Unselect foes" links
	$( 'div.blast-nav-links a.blast-select-foes-link' ).on( 'click', function () {
		BoardBlast.toggleFoes( 1 );
	} );

	$( 'div.blast-nav-links a.blast-unselect-foes-link' ).on( 'click', function () {
		BoardBlast.toggleFoes( 0 );
	} );

	// Toggling for an individual user
	$( 'div#blast-friends-list div[id^="user-"]' ).on( 'click', function () {
		BoardBlast.toggleUser( $( this ).attr( 'id' ).replace( /user-/, '' ) );
	} );

	// The submit button
	$( 'div.blast-message-box-button input[type="submit"]' ).on( 'click', function ( e ) {
		e.preventDefault();
		BoardBlast.sendMessages();
	} );
} );
