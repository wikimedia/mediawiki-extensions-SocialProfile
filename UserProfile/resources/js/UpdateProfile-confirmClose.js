/*!
 * JavaScript for Special:UpdateProfile: Enable save button and prevent the window being accidentally
 * closed when any form field is changed.
 *
 * This is a modified version of /resources/src/mediawiki.special/mediawiki.special.preferences.confirmClose.js@REL1_31_0.
 *
 * @see https://phabricator.wikimedia.org/T202289
 */
( function ( mw, $ ) {
	$( () => {

		// Check if all of the form values are unchanged
		function isPrefsChanged() {
			const inputs = $( 'form[name="profile"] :input[name]' ); // CHANGED
			let input, $input, inputType,
				index, optIndex,
				opt;

			for ( index = 0; index < inputs.length; index++ ) {
				input = inputs[ index ];
				$input = $( input );

				// Different types of inputs have different methods for accessing defaults
				if ( $input.is( 'select' ) ) {
					// CHANGED: skip US state selector since its default value is changed by
					// JS, which means that the if ( opt.selected !== opt.defaultSelected )
					// test below will ALWAYS be triggered for that field even if the user
					// never touched that particular field...
					if ( $input.attr( 'id' ) === 'location_state' ) {
						return false;
					}

					// <select> has the property defaultSelected for each option
					for ( optIndex = 0; optIndex < input.options.length; optIndex++ ) {
						opt = input.options[ optIndex ];
						if ( opt.selected !== opt.defaultSelected ) {
							return true;
						}
					}
				} else if ( $input.is( 'input' ) || $input.is( 'textarea' ) ) {
					// <input> has defaultValue or defaultChecked
					inputType = input.type;
					if ( inputType === 'radio' || inputType === 'checkbox' ) {
						if ( input.checked !== input.defaultChecked ) {
							return true;
						}
					} else if ( input.value !== input.defaultValue ) {
						return true;
					}
				}
			}

			return false;
		}

		// Disable the button to save preferences unless preferences have changed
		// Check if preferences have been changed before JS has finished loading
		// CHANGED: Changed all three selectors here
		$( 'input[class="site-button"]' ).prop( 'disabled', !isPrefsChanged() );
		$( 'form[name="profile"] div:not(.visualClear) input,form[name="profile"] div:not(.visualClear) textarea' ).on( 'change keyup mouseup', () => {
			$( 'input[class="site-button"]' ).prop( 'disabled', !isPrefsChanged() );
		} );

		// Set up a message to notify users if they try to leave the page without
		// saving.
		const allowCloseWindow = mw.confirmCloseWindow( {
			test: isPrefsChanged
		} );

		$( 'form[name="profile"]' ).on( 'submit', $.bind( allowCloseWindow, 'release' ) ); // CHANGED
		// CHANGED: We don't have a "restore" link
		// $( '#mw-prefs-restoreprefs' ).click( $.proxy( allowCloseWindow, 'release' ) );
	} );
}( mw, jQuery ) );
