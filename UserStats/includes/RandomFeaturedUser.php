<?php
/**
 * RandomFeaturedUser - adds <randomfeatureduser> parser hook
 * to display a randomly chosen 'featured' user and some info regarding the
 * user, such as their avatar.
 *
 * Make sure to configure either $wgUserStatsTrackWeekly or $wgUserStatsTrackMonthly
 * to true in your wiki's LocalSettings.php, run maintenance/update.php from core,
 * then add <randomfeatureduser/> tag to whichever page you want to.
 *
 * @file
 * @ingroup Extensions
 * @author Aaron Wright <aaron.wright@gmail.com>
 * @author David Pean <david.pean@gmail.com>
 * @author Jack Phoenix
 * @license GPL-2.0-or-later
 */

class RandomFeaturedUser {

	/**
	 * @param string $input The contents of the randomfeatureduser tag.
	 * @param string[] $args The arguments to the randomfeatureduser tag.
	 * @param Parser $parser The parser.
	 * @return string
	 */
	public static function getRandomUser( $input, $args, Parser $parser ) {
		global $wgMemc, $wgRandomFeaturedUser;

		$parser->getOutput()->updateCacheExpiry( 0 );

		$period = $args['period'] ?? '';
		if ( $period != 'weekly' && $period != 'monthly' ) {
			return '';
		}

		// Add CSS
		$parser->getOutput()->addModuleStyles( 'ext.socialprofile.userstats.randomfeatureduser.styles' );

		$user_list = [];
		$count = 20;
		$realCount = 10;

		// Try cache
		$key = $wgMemc->makeKey( 'user_stats', 'top', 'points', 'weekly', $realCount );
		$data = $wgMemc->get( $key );

		if ( $data != '' ) {
			wfDebug( "Got top $period users by points ({$count}) from cache\n" );
			$user_list = $data;
		} else {
			wfDebug( "Got top $period users by points ({$count}) from DB\n" );

			// @TODO: Clean this area of code up to use TopUsersListLookup instead
			$dbr = wfGetDB( DB_REPLICA );
			$res = $dbr->select(
				'user_points_' . $period,
				[ 'up_user_id', 'up_user_name', 'up_points' ],
				[ 'up_user_id <> 0' ],
				__METHOD__,
				[
					'ORDER BY' => 'up_points DESC',
					'LIMIT' => $count
				]
			);
			$loop = 0;
			foreach ( $res as $row ) {
				// Prevent blocked users from appearing
				$user = User::newFromId( $row->up_user_id );
				if ( !$user->isBlocked() ) {
					$user_list[] = [
						'user_id' => $row->up_user_id,
						'user_name' => $row->up_user_name,
						'points' => $row->up_points
					];
					$loop++;
				}
				if ( $loop >= 10 ) {
					break;
				}
			}

			if ( count( $user_list ) > 0 ) {
				$wgMemc->set( $key, $user_list, 60 * 60 );
			}
		}

		// Make sure we have some data
		if ( !is_array( $user_list ) || count( $user_list ) == 0 ) {
			return '';
		}

		$random_user = $user_list[array_rand( $user_list, 1 )];

		// Make sure we have a user
		if ( !$random_user['user_id'] ) {
			return '';
		}

		$output = '<div class="random-featured-user">';

		if ( $wgRandomFeaturedUser['points'] == true ) {
			$stats = new UserStats( $random_user['user_id'], $random_user['user_name'] );
			$stats_data = $stats->getUserStats();
			$points = $stats_data['points'];
		}

		if ( $wgRandomFeaturedUser['avatar'] == true ) {
			$user_title = Title::makeTitle( NS_USER, $random_user['user_name'] );
			$avatar = new wAvatar( $random_user['user_id'], 'ml' );
			$avatarImage = $avatar->getAvatarURL();

			$output .= '<a href="' . htmlspecialchars( $user_title->getFullURL(), ENT_QUOTES ) . '">';
			$output .= $avatarImage;
			$output .= '</a>';
		}

		$link = Html::element(
			'a',
			[ 'href' => $user_title->getFullURL() ],
			wordwrap( $random_user['user_name'], 12, "<br />\n", true )
		);
		$output .= "<div class=\"random-featured-user-title\">$link<br /> " .
				wfMessage( "random-user-points-{$period}", $points )->text() .
			"</div>\n\n";

		if ( $wgRandomFeaturedUser['about'] == true ) {
			/**
			 * @TODO: Do we need to instantiate a new parser?
			 * Why can't we use the parser passed through by reference?
			 */
			$p = new Parser();
			$profile = new UserProfile( $random_user['user_name'] );
			$profile_data = $profile->getProfile();
			$about = $profile_data['about'] ?? '';
			// Remove templates
			$about = preg_replace( '@{{.*?}}@si', '', $about );
			if ( !empty( $about ) ) {
				global $wgOut;
				$output .= '<div class="random-featured-user-about-title">' .
					wfMessage( 'random-user-about-me' )->text() . '</div>' .
					$p->parse( $about, $parser->getTitle(), $wgOut->parserOptions(), false )->getText();
			}
		}

		$output .= '</div><div class="visualClear"></div>';

		return $output;
	}

}
