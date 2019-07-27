<?php
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
	 */
	public static function getNewUsers( $input, $args, $parser ) {
		global $wgMemc;

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
		$key = $wgMemc->makeKey( 'users', 'new', $count );
		$data = $wgMemc->get( $key );

		if ( !$data ) {
			$dbr = wfGetDB( DB_REPLICA );

			if ( $dbr->tableExists( 'user_register_track' ) ) {
				$res = $dbr->select(
					'user_register_track',
					[ 'ur_user_id', 'ur_user_name' ],
					[],
					__METHOD__,
					[ 'ORDER BY' => 'ur_date', 'LIMIT' => $count ]
				);

				$list = [];
				foreach ( $res as $row ) {
					$list[] = [
						'user_id' => $row->ur_user_id,
						'user_name' => $row->ur_user_name
					];
				}
			} else {
				// If user_register_track table doesn't exist, use the core logging
				// table
				$actorQuery = ActorMigration::newMigration()->getJoin( 'log_user' );
				$res = $dbr->select(
					[ 'logging' ] + $actorQuery['tables'],
					$actorQuery['fields'],
					[ 'log_type' => 'newusers' ],
					__METHOD__,
					// DESC to get the *newest* $count users instead of the oldest
					[ 'ORDER BY' => 'log_timestamp DESC', 'LIMIT' => $count ],
					$actorQuery['joins']
				);

				$list = [];
				foreach ( $res as $row ) {
					$list[] = [
						'user_id' => $row->log_user,
						'user_name' => $row->log_user_text
					];
				}
			}

			// Cache in memcached for 10 minutes
			$wgMemc->set( $key, $list, 60 * 10 );
		} else {
			wfDebugLog( 'NewUsersList', 'Got new users from cache' );
			$list = $data;
		}

		$output = '<div class="new-users">';

		if ( !empty( $list ) ) {
			$x = 1;
			foreach ( $list as $user ) {
				$avatar = new wAvatar( $user['user_id'], 'ml' );
				$userLink = Title::makeTitle( NS_USER, $user['user_name'] );

				$output .= '<a href="' . htmlspecialchars( $userLink->getFullURL() ) .
					'" rel="nofollow">' . $avatar->getAvatarURL( [ 'title' => $user['user_name'] ] ) . '</a>';

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
