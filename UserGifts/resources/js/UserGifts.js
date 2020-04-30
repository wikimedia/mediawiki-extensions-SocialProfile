var UserGifts = {
	selected_gift: 0,

	selectGift: function ( id ) {
		// Un-select previously selected gift
		if ( UserGifts.selected_gift ) {
			$( '#give_gift_' + UserGifts.selected_gift ).removeClass( 'g-give-all-selected' );
		}

		// Select new gift
		$( '#give_gift_' + id ).addClass( 'g-give-all-selected' );

		UserGifts.selected_gift = id;
	},

	highlightGift: function ( id ) {
		$( '#give_gift_' + id ).addClass( 'g-give-all-highlight' );
	},

	unHighlightGift: function ( id ) {
		$( '#give_gift_' + id ).removeClass( 'g-give-all-highlight' );
	},

	sendGift: function () {
		if ( !UserGifts.selected_gift ) {
			window.alert( mw.msg( 'g-select-gift' ) );
			return false;
		}
		document.gift.gift_id.value = UserGifts.selected_gift;
		document.gift.submit();
	},

	chooseFriend: function ( friend ) {
		// Now, this is a rather nasty hack since the original (commented out below) wouldn't work when $wgScriptPath was set
		// window.location = window.location + "&user=" + friend;
		window.location = mw.config.get( 'wgServer' ) + mw.config.get( 'wgScript' ) +
			'?title=Special:GiveGift' + '&user=' + friend;
	}
};

$( function () {
	// "Select a friend" dropdown menu
	$( 'div.g-gift-select select' ).on( 'change', function () {
		UserGifts.chooseFriend( $( this ).val() );
	} );

	// Handlers for individual gift images
	$( 'div[id^=give_gift_]' ).on( {
		'click': function () {
			UserGifts.selectGift(
				$( this ).attr( 'id' ).replace( 'give_gift_', '' )
			);
		},
		'mouseout': function () {
			UserGifts.unHighlightGift(
				$( this ).attr( 'id' ).replace( 'give_gift_', '' )
			);
		},
		'mouseover': function () {
			UserGifts.highlightGift(
				$( this ).attr( 'id' ).replace( 'give_gift_', '' )
			);
		},
	} );

	// "Send gift" button
	$( 'input#send-gift-button' ).on( 'click', function () {
		UserGifts.sendGift();
	} );
} );
