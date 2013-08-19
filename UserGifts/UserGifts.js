var UserGifts = {
	selected_gift: 0,

	selectGift: function( id ) {
		// Un-select previously selected gift
		if ( selected_gift ) {
			jQuery( '#give_gift_' + selected_gift ).removeClass( 'g-give-all-selected' );
		}

		// Select new gift
		jQuery( '#give_gift_' + id ).addClass( 'g-give-all-selected' );

		UserGifts.selected_gift = id;
	},

	highlightGift: function( id ) {
		jQuery( '#give_gift_' + id ).addClass( 'g-give-all-highlight' );
	},

	unHighlightGift: function( id ) {
		jQuery( '#give_gift_' + id ).removeClass( 'g-give-all-highlight' );
	},

	sendGift: function() {
		if ( !UserGifts.selected_gift ) {
			alert( 'Please select a gift' );
			return false;
		}
		document.gift.gift_id.value = UserGifts.selected_gift;
		document.gift.submit();
	},

	chooseFriend: function( friend ) {
		// Now, this is a rather nasty hack since the original (commented out below) wouldn't work when $wgScriptPath was set
		//window.location = window.location + "&user=" + friend;
		window.location = mw.config.get( 'wgServer' ) + mw.config.get( 'wgScript' ) +
			'?title=Special:GiveGift' + '&user=' + friend;
	}
};

jQuery( document ).ready( function() {
	jQuery( 'div.g-gift-select select' ).on( 'change', function() {
		UserGifts.chooseFriend( jQuery( this ).val() );
	} );
} );