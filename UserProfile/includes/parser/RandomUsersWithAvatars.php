<?php

use MediaWiki\MediaWikiServices;

/**
 * RandomUsersWithAvatars - displays a number of randomly selected users
 * that have uploaded an avatar though [[Special:UploadAvatar]].
 *
 * @file
 * @ingroup Extensions
 * @author David Pean <david.pean@gmail.com>
 * @author Jack Phoenix
 * @license GPL-2.0-or-later
 */

class RandomUsersWithAvatars {

	/**
	 * Callback to UserProfileHooks::onParserFirstCallInit().
	 *
	 * @param string|null $input
	 * @param array $args
	 * @param Parser $parser
	 *
	 * @return string
	 */
	public static function getRandomUsersWithAvatars( $input, array $args, Parser $parser ) {
		global $wgAvatarKey;

		$cache = MediaWikiServices::getInstance()->getMainWANObjectCache();
		$parser->getOutput()->addModuleStyles( [ 'ext.socialprofile.userprofile.randomuserswithavatars.styles' ] );
		$parser->getOutput()->updateCacheExpiry( 0 );

		$count = ( isset( $args['count'] ) && is_numeric( $args['count'] ) ) ? intval( $args['count'] ) : 10;
		$perRow = ( isset( $args['row'] ) && is_numeric( $args['row'] ) ) ? intval( $args['row'] ) : 4;
		$allowedSizes = [ 's', 'm', 'ml', 'l' ];

		if ( isset( $args['size'] ) && in_array( strtolower( $args['size'] ), $allowedSizes ) ) {
			$size = strtolower( $args['size'] );
		} else {
			$size = 'ml';
		}

		// Try cache
		$key = $cache->makeKey( 'users', 'random', 'avatars', $count, $perRow, $size );
		$data = $cache->get( $key );
		if ( !$data ) {
			$backend = new SocialProfileFileBackend( 'avatars' );

			$reSize = preg_quote( $size );
			$files = preg_grep(
				"/^{$wgAvatarKey}_[0-9]+_{$reSize}\.(png|gif|jpe?g)$/i",
				iterator_to_array( $backend->getFileBackend()->getFileList( [
					'dir' => $backend->getContainerStoragePath(),
					'topOnly' => true,
					'adviseStat' => false,
				] ) )
			);

			$cache->set( $key, $files, 60 * 60 );
		} else {
			wfDebug( "Got random users with avatars from cache\n" );
			$files = $data;
		}

		$output = '<div class="random-users-avatars">
		<h2>' . wfMessage( 'random-users-avatars-title' )->parse() . '</h2>';

		$x = 1;

		if ( count( $files ) < $count ) {
			$count = count( $files );
		}
		if ( $count > 0 ) {
			$randomKeys = (array)array_rand( $files, $count );
		} else {
			$randomKeys = [];
		}

		foreach ( $randomKeys as $random ) {
			// Extract user ID out of avatar image name
			$avatarName = basename( $files[$random] );
			preg_match( "/{$wgAvatarKey}_(.*)_/i", $avatarName, $matches );
			$userId = $matches[1];

			if ( $userId ) {
				// Load user
				$user = User::newFromId( $userId );
				$user->loadFromDatabase();
				$username = $user->getName();
				$avatar = new wAvatar( $userId, $size );
				$userLink = Title::makeTitle( NS_USER, $username );

				$output .= '<a href="' . htmlspecialchars( $userLink->getFullURL() ) .
					'" rel="nofollow">' . $avatar->getAvatarURL( [ 'title' => $username ] ) . '</a>';

				if ( $x == $count || $x != 1 && $x % $perRow == 0 ) {
					$output .= '<div class="visualClear"></div>';
				}
				$x++;
			}
		}

		$output .= '<div class="visualClear"></div>
		</div>';

		return $output;
	}

}
