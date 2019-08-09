<?php

use MediaWiki\MediaWikiServices;

/**
 * NewUsersList parser hook extension -- adds <newusers> parser tag to retrieve
 * the list of new users and their avatars.
 * Works with NewSignupPage extension, i.e. if the user_register_track DB table
 * is present, this extension queries that table, but if it's not, then the
 * core logging table is used instead.
 *
 * @file
 * @ingroup Extensions
 * @author Aaron Wright <aaron.wright@gmail.com>
 * @author David Pean <david.pean@gmail.com>
 * @author Jack Phoenix
 * @license GPL-2.0-or-later
 */

class NewUsersList {

	/**
	 * Callback function for UserProfileHooks::onParserFirstCallInit().
	 *
	 * Queries the user_register_track database table for new users and renders
	 * the list of newest users and their avatars, wrapped in a div with the class
	 * "new-users".
	 * Disables parser cache and caches the database query results in memcached.
	 *
	 * @param string|null $input
	 * @param array $args
	 * @param Parser $parser
	 *
	 * @return string
	 */
	public static function getNewUsers( $input, array $args, Parser $parser ) {
		$cache = MediaWikiServices::getInstance()->getMainWANObjectCache();

		$parser->getOutput()->updateCacheExpiry( 0 );

		$count = 10;
		$per_row = 5;

		if ( isset( $args['count'] ) && is_numeric( $args['count'] ) ) {
			$count = intval( $args['count'] );
		}

		if ( isset( $args['row'] ) && is_numeric( $args['row'] ) ) {
			$per_row = intval( $args['row'] );
		}

		// Try cache
		$key = $cache->makeKey( 'users', 'new', $count );
		$data = $cache->get( $key );

		if ( !$data ) {
			$dbr = wfGetDB( DB_REPLICA );

			if ( $dbr->tableExists( 'user_register_track' ) ) {
				$res = $dbr->select(
					'user_register_track',
					[ 'ur_actor' ],
					[],
					__METHOD__,
					[ 'ORDER BY' => 'ur_date', 'LIMIT' => $count ]
				);

				$list = [];
				foreach ( $res as $row ) {
					$list[] = [
						'actor' => $row->ur_actor
					];
				}
			} else {
				// If user_register_track table doesn't exist, use the core logging table
				$res = $dbr->select(
					'logging',
					[ 'log_actor' ],
					[ 'log_type' => 'newusers' ],
					__METHOD__,
					// DESC to get the *newest* $count users instead of the oldest
					[ 'ORDER BY' => 'log_timestamp DESC', 'LIMIT' => $count ]
				);

				$list = [];
				foreach ( $res as $row ) {
					$list[] = [
						'actor' => $row->log_actor
					];
				}
			}

			// Cache in memcached for 10 minutes
			$cache->set( $key, $list, 60 * 10 );
		} else {
			wfDebugLog( 'NewUsersList', 'Got new users from cache' );
			$list = $data;
		}

		$output = '<div class="new-users">';

		if ( !empty( $list ) ) {
			$x = 1;
			foreach ( $list as $entry ) {
				$user = User::newFromActorId( $entry['actor'] );
				if ( !$user ) {
					continue;
				}

				$avatar = new wAvatar( $user->getId(), 'ml' );
				$output .= '<a href="' . htmlspecialchars( $user->getUserPage()->getFullURL() ) .
					'" rel="nofollow">' . $avatar->getAvatarURL( [ 'title' => $user->getName() ] ) . '</a>';

				if ( ( $x == $count ) || ( $x != 1 ) && ( $x % $per_row == 0 ) ) {
					$output .= '<div class="visualClear"></div>';
				}

				$x++;
			}
		}

		$output .= '<div class="visualClear"></div></div>';

		return $output;
	}

}
