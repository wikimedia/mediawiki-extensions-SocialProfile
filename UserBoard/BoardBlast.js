function toggle_user( user_id ) {
	if( jQuery( '#user-' + user_id ).hasClass( 'blast-friend-selected' ) ) {
		jQuery( '#user-' + user_id ).removeClass( 'blast-friend-selected' ).addClass( 'blast-friend-unselected' );
	} else if( jQuery( '#user-' + user_id ).hasClass( 'blast-friend-unselected' ) ) {
		jQuery( '#user-' + user_id ).removeClass( 'blast-friend-unselected' ).addClass( 'blast-friend-selected' );
	}

	if( jQuery( '#user-' + user_id ).hasClass( 'blast-foe-selected' ) ) {
		jQuery( '#user-' + user_id ).removeClass( 'blast-foe-selected' ).addClass( 'blast-foe-unselected' );
	} else if( jQuery( '#user-' + user_id ).hasClass( 'blast-foe-unselected' ) ) {
		jQuery( '#user-' + user_id ).removeClass( 'blast-foe-unselected' ).addClass( 'blast-foe-selected' );
	}
}

function toggle_type( method, on, off ) {
	list = jQuery( '#blast-friends-list div.' + ( ( method == 1 ) ? off : on ) );

	for( x = 0; x <= list.length - 1; x++ ) {
		el = list[x];
		if( jQuery( el ).hasClass( on ) || jQuery( el ).hasClass( off ) ) {
			if( method == 1 ) {
				jQuery( el ).removeClass( off ).addClass( on );
			} else {
				jQuery( el ).removeClass( on ).addClass( off );
			}
		}
	}
}

function toggle_friends( method ) {
	toggle_type( method, 'blast-friend-selected', 'blast-friend-unselected' );
}

function toggle_foes( method ) {
	toggle_type( method, 'blast-foe-selected', 'blast-foe-unselected' );
}

function select_all() {
	toggle_friends( 1 );
	toggle_foes( 1 );
}
function unselect_all() {
	toggle_friends( 0 );
	toggle_foes( 0 );
}

submitted = 0;
function send_messages() {
	if( submitted == 1 ) {
		return 0;
	}

	submitted = 1;
	selected = 0;
	user_ids_to = '';

	list = jQuery( '#blast-friends-list div.blast-friend-selected' );
	for( x = 0; x <= list.length - 1; x++ ) {
		el = list[x];
		selected++;
		user_id = el.id.replace( 'user-', '' );
		user_ids_to += ( ( user_ids_to ) ? ',' : '' ) + user_id;
	}

	list = jQuery( '#blast-friends-list div.blast-foe-selected' );
	for( x = 0; x <= list.length - 1; x++ ) {
		el = list[x];
		selected++;
		user_id = el.id.replace( 'user-', '' );
		user_ids_to += ( ( user_ids_to ) ? ',' : '' ) + user_id;
	}

	if( selected === 0 ) {
		alert( 'Please select at least one person' );
		submitted = 0;
		return 0;
	}

	if( !document.getElementById( 'message' ).value ) {
		alert( 'Please enter a message' );
		submitted = 0;
		return 0;
	}

	document.getElementById( 'ids' ).value = user_ids_to;

	document.blast.message.style.color = '#ccc';
	document.blast.message.readOnly = true;
	document.getElementById( 'blast-friends-list' ).innerHTML = 'Sending messages...';
	document.blast.submit();
}
