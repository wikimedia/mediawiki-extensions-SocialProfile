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

mediaWiki.loader.using( 'jquery.ui.datepicker', function() {
	jQuery( function( jQuery ) {
		jQuery( '#birthday' ).datepicker( {
			changeYear: true,
			yearRange: '1930:c',
			dateFormat: jQuery( '#birthday' ).hasClass( 'long-birthday' ) ? 'mm/dd/yy' : 'mm/dd'
		} );
	} );
} );

$( function() {
	$( '.eye-container' ).on( {
		'mouseenter': function() {
			if ( $( this ).css( 'position' ) !== 'absolute' ) {
				var offset = $( this ).offset();

				$( this ).attr( 'link', $( this ).parent() );

				$( 'body' ).append( $( this ) );

				$( this ).css( {
					position: 'absolute',
					top: offset.top + 'px',
					left: offset.left + 'px'
				} );
			}

			$( this ).css( {zIndex: 1000} );

			$( this ).animate( {height: 100}, 100 );
		},
		'mouseleave': function() {
			$( this ).animate( {height: 20}, 100 );
			$( this ).css( {zIndex: 10} );
		}
	} );

	$( '.eye-container > .menu > .item' ).on( 'click', function() {
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

		$.ajax( {
			type: 'GET',
			url: mediaWiki.util.wikiScript( 'api' ),
			data: {
				action: 'smpuserprivacy',
				format: 'json',
				method: 'set',
				'field_key': field_key,
				privacy: encodeURIComponent( priv )
			}
		} ).done( function( data ) {
			var offset = $( this_element ).offset();
			$( this_element ).remove();
			var newEl = $( data.smpuserprivacy.replace );

			$( newEl ).css( {
				position: 'absolute',
				top: offset.top + 'px',
				left: offset.left + 'px'
			} );

			$( 'body' ).append( $( newEl ) );
		} );
	} );
} );
