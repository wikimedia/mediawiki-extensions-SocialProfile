<?php

class AvatarParserFunction {

	/**
	 * Setup function for the {{#avatar:Username}} function
	 *
	 * @param Parser $parser: MW parser object
	 * @return boolean
	 */
	static function setupAvatarParserFunction( &$parser ) {
		$parser->setFunctionHook( 'avatar', 'AvatarParserFunction::renderAvatarParserFunction' );

		return true;
	}

	/**
	 * Function to render the {{#avatar:Username}} function
	 *
	 * @param Parser $parser: MW parser object
	 * @param string $username: Username of user to show avatar for
	 * @param string $size: Size of avatar to return (s/m/ml/l), or px value (100px, 10px, etc)
	 * @return array: output of function, and options for the parser
	 */
	static function renderAvatarParserFunction( $parser, $username = '', $givenSize = 'm' ) {
		global $wgUploadPath;

		$sizes = array( 's', 'm', 'ml', 'l' );

		if ( in_array( $givenSize, $sizes ) ) { // if given size is a code,
			$size = $givenSize;				   // use code,
			$px = '';						   // and leave px value empty
		} elseif ( substr( $givenSize, -2 ) == 'px' ) { //given size is a value in px
			$givenPx = intval( substr( $givenSize, 0, strlen( $givenSize ) - 2 ) ); //get int value of given px size

			if ( !is_int( $givenPx ) ) { // if px value is not int
				$size = 'm';			 // give default avatar
				$px = '';				 // with no px value
			}

			if ( $givenPx <= 16 ) { // if given px value is smaller than small,
				$size = 's';	   // use the small avatar,
				$px = $givenSize;  // and the given px value
			} elseif ( $givenPx <= 30 ) { // if given px value is smaller than medium,
				$size = 'm';			 // use the medium avatar,
				$px = $givenSize;		 // and the given px value
			} elseif ( $givenPx <= 50 ) { // if given px value is smaller than medium-large,
				$size = 'ml';			 // use the medium-large avatar,
				$px = $givenSize;		 // and the given px value
			} else { 			  // if given px value is bigger then medium large,
				$size = 'l';	  // use the large avatar,
				$px = $givenSize; // and the given px value
			}
		} else { // size value is not code or px
			$size = 'm'; // give default avatar
			$px = '';	 // with no px value
		}

		$user = User::newFromName( $username );
		if ( $user instanceof User ) {
			$id = $user->getId();
			$avatar = new wAvatar( $id, $size );
		} else {
			// Fallback for the case where an invalid (nonexistent) user name
			// was supplied...
			$avatar = new wAvatar( -1 , 'm' ); // not very nice, but -1 will get the default avatar
		}

		if ( $px ) { // if px value needed, set height to it
			$output = $avatar->getAvatarURL( array( 'height' => $px ) );
		} else { // but if not needed, don't
			$output = $avatar->getAvatarURL();
		}

		return array( $output, 'noparse' => true, 'isHTML' => true );
	}

}