/**
 * JavaScript used on Special:UpdateProfile
 * Displays the "State" dropdown menu if selected country is the United States
 */
/*jshint unused:false*/
var countries = [];
countries[0] = {
	country: 'United States',
	name: 'State',
	sections: [
		'Alabama', 'Alaska', 'Arizona', 'Arkansas', 'California', 'Colorado',
		'Connecticut', 'Delaware', 'Florida', 'Georgia', 'Hawaii', 'Idaho',
		'Illinois', 'Indiana', 'Iowa', 'Kansas', 'Kentucky', 'Louisiana',
		'Maine', 'Maryland', 'Massachusetts', 'Michigan', 'Minnesota',
		'Mississippi', 'Missouri', 'Montana', 'Nebraska', 'Nevada',
		'New Hampshire', 'New Jersey', 'New Mexico', 'New York',
		'North Carolina', 'North Dakota', 'Ohio', 'Oklahoma', 'Oregon',
		'Pennsylvania', 'Puerto Rico', 'Rhode Island', 'South Carolina',
		'South Dakota', 'Tennessee', 'Texas', 'Utah', 'Vermont', 'Virginia',
		'Washington', 'Washington, D.C.', 'West Virginia', 'Wisconsin', 'Wyoming'
	]
};

function displaySection( id, country, section ) {
	var country_id = -1;
	for ( var x = 0; x <= countries.length - 1; x++ ) {
		if ( country === countries[x].country ) {
			country_id = x;
		}
	}

	var section_select = '';
	if ( countries[country_id] ) {
		document.getElementById( id + '_label' ).innerHTML = countries[country_id].name;
		section_select += '<select class="profile-form" name="' + id + '" id="' + id + '"><option></option>';
		for ( x = 0; x <= countries[country_id].sections.length - 1; x++ ) {
			section_select += '<option value="' + countries[country_id].sections[x] + '"' +
				( ( countries[country_id].sections[x] === section ) ? ' selected="selected"' : '' ) + '>' + countries[country_id].sections[x] + '</option>';
		}
		section_select += '</select>';
	}

	document.getElementById( id + '_form' ).innerHTML = section_select;
}

jQuery( function( jQuery ) {
	jQuery( '#birthday' ).datepicker( {
		changeYear: true,
		yearRange: '1930:c',
		dateFormat: jQuery( '#birthday' ).hasClass( 'long-birthday' ) ? 'mm/dd/yy' : 'mm/dd'
	} );
} );

$( function() {
	// US state selector
	displaySection( 'location_state', $( '#location_country' ).val(), $( '#location_state_current' ).val() );
	$( '#location_country' ).on( 'change', function () {
		displaySection( 'location_state', this.value, '' );
	} );

	displaySection( 'hometown_state', $( '#hometown_country' ).val(), $( '#hometown_state_current' ).val() );
	$( '#hometown_country' ).on( 'change', function () {
		displaySection( 'hometown_state', this.value, '' );
	} );

	// Profile visibility stuff
	$( 'body' ).on( 'mouseenter', '.eye-container', function() {
		if ( $( this ).css( 'position' ) !== 'absolute' ) {
			var offset = $( this ).offset();

			$( 'body' ).append( $( this ) );

			$( this ).css( {
				position: 'absolute',
				top: offset.top + 'px',
				left: offset.left + 'px'
			} );
		}

		$( this ).css( {zIndex: 1000} );

		$( this ).animate( {height: 100}, 100 );
	} );
	$( 'body' ).on( 'mouseleave', '.eye-container', function() {
		$( this ).animate( {height: 20}, 100 );
		$( this ).css( {zIndex: 10} );
	} );

	$( 'body' ).on( 'click', '.eye-container > .menu > .item', function() {
		$( this ).parent().parent().css( {height: 20} );

		var field_key = $( this ).parent().parent().attr( 'fieldkey' );
		var priv = $( this ).attr( 'action' );
		var this_element = $( this ).parent().parent();

		$( this_element ).css( {
			opacity: 0.3,
			backgroundImage: 'none',
			backgroundColor: 'lightgray'
		} );

		$( this_element ).find( 'div.title' ).html( '...' );

		( new mw.Api() ).postWithToken( 'csrf', {
			action: 'smpuserprivacy',
			format: 'json',
			method: 'set',
			'field_key': field_key,
			privacy: encodeURIComponent( priv )
		} ).done( function( data ) {
			var offset = $( this_element ).offset();
			$( this_element ).remove();
			var newEl = $( data.smpuserprivacy.replace );

			$( newEl ).css( {
				position: 'absolute',
				top: offset.top + 'px',
				left: offset.left + 'px',
				// Apparently this is set inline, but it's not set anymore here
				// (after the user has changed the value), which makes the button
				// essentially invisible to the user. Fun!
				zIndex: 10
			} );

			$( 'body' ).append( $( newEl ) );
		} );
	} );
} );
