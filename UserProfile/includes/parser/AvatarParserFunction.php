<?php

/**
 * Allows rendering an avatar based on a given
 * username and size through a parser function,
 * {{#avatar:Username}}.
 */
class AvatarParserFunction {

	/**
	 * Setup function for the {{#avatar:Username}} function
	 *
	 * @param Parser $parser
	 */
	public static function setupAvatarParserFunction( Parser $parser ) {
		$parser->setFunctionHook( 'avatar', [ __CLASS__, 'renderAvatarParserFunction' ] );
	}

	/**
	 * Function to render the {{#avatar:Username}} function
	 *
	 * @param Parser $parser
	 * @param string $username Username of user to show avatar for
	 * @param string $givenSize Size of avatar to return (s/m/ml/l), or px value (100px, 10px, etc)
	 * @return array Output of function, and options for the parser
	 */
	public static function renderAvatarParserFunction( $parser, $username = '', $givenSize = 'm' ) {
		$sizes = [ 's', 'm', 'ml', 'l' ];

		// if given size is a code,
		// use code, and leave px value empty
		if ( in_array( $givenSize, $sizes ) ) {
			$size = $givenSize;
			$px = '';

		// given size is a value in px
		} elseif ( substr( $givenSize, -2 ) == 'px' ) {
			// get int value of given px size
			$givenPx = intval( substr( $givenSize, 0, strlen( $givenSize ) - 2 ) );

			// if px value is not int, give default avatar
			// with no px value
			if ( !is_int( $givenPx ) ) {
				$size = 'm';
				$px = '';
			}

			// if given px value is smaller than small,
			// use the small avatar and the given `px` value
			if ( $givenPx <= 16 ) {
				$size = 's';
				$px = $givenSize;

			// if given px value is smaller than medium,
			// use the medium avatar and the given `px` value
			} elseif ( $givenPx <= 30 ) {
				$size = 'm';
				$px = $givenSize;

			// if given px value is smaller than medium-large,
			// use the medium-large avatar and the given `px` value
			} elseif ( $givenPx <= 50 ) {
				$size = 'ml';
				$px = $givenSize;

			// if given px value is bigger then medium large,
			// use the large avatar and the given `px` value
			} else {
				$size = 'l';
				$px = $givenSize;
			}

		// size value is not code or px
		// give default avatar with no px value
		} else {
			$size = 'm';
			$px = '';
		}

		$user = User::newFromName( $username );
		if ( $user instanceof User ) {
			$id = $user->getId();
			$avatar = new wAvatar( $id, $size );
		} else {
			// Fallback for the case where an invalid (nonexistent)
			// user name was supplied...
			// not very nice, but -1 will get the default avatar
			$avatar = new wAvatar( -1, 'm' );
		}

		// if px value needed, set height to it
		if ( $px ) {
			$output = $avatar->getAvatarURL( [ 'height' => $px ] );
		// but if not needed, don't
		} else {
			$output = $avatar->getAvatarURL();
		}

		return [
			$output,
			'noparse' => true,
			'isHTML' => true
		];
	}

}
