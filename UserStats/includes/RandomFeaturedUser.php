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

use MediaWiki\MediaWikiServices;

class RandomFeaturedUser {

	/**
	 * @param string $input The contents of the randomfeatureduser tag.
	 * @param string[] $args The arguments to the randomfeatureduser tag.
	 * @param Parser $parser The parser.
	 * @return string
	 */
	public static function getRandomUser( $input, $args, Parser $parser ) {
		global $wgRandomFeaturedUser;

		$cache = MediaWikiServices::getInstance()->getMainWANObjectCache();
		$parser->getOutput()->updateCacheExpiry( 0 );

		$period = $args['period'] ?? '';
		if ( $period != 'weekly' && $period != 'monthly' ) {
			return '';
		}

		// Add CSS
		$parser->getOutput()->addModuleStyles( 'ext.socialprofile.userstats.randomfeatureduser.styles' );

		$user_list = [];
		$count = 10;

		// Try cache
		$key = $cache->makeKey( 'user_stats', 'top', 'points', 'weekly', $count );
		$data = $cache->get( $key );

		if ( $data != '' ) {
			wfDebug( "Got top $period users by points ({$count}) from cache\n" );
			$user_list = $data;
		} else {
			wfDebug( "Got top $period users by points ({$count}) from DB\n" );

			$user_list = ( new TopUsersListLookup( $count ) )->getListByTimePeriod( $period );

			if ( count( $user_list ) > 0 ) {
				$cache->set( $key, $user_list, 60 * 60 );
			}
		}

		// Make sure we have some data
		if ( !is_array( $user_list ) || count( $user_list ) == 0 ) {
			return '';
		}

		$random_user = $user_list[array_rand( $user_list, 1 )];

		// Make sure we have a user
		if ( !$random_user['actor'] ) {
			return '';
		}

		$output = '<div class="random-featured-user">';

		$user = User::newFromActorId( $random_user['actor'] );

		if ( $wgRandomFeaturedUser['points'] == true ) {
			$stats = new UserStats( $user );
			$stats_data = $stats->getUserStats();
			$points = $stats_data['points'];
		}

		$userPageURL = $user->getUserPage()->getFullURL();

		if ( $wgRandomFeaturedUser['avatar'] == true ) {
			$avatar = new wAvatar( $user->getId(), 'ml' );
			$avatarImage = $avatar->getAvatarURL();

			$output .= '<a href="' . htmlspecialchars( $userPageURL, ENT_QUOTES ) . '">';
			$output .= $avatarImage;
			$output .= '</a>';
		}

		// @phan-suppress-next-line SecurityCheck-DoubleEscaped Fake news, escaping is totally proper here
		$link = Html::rawElement(
			'a',
			[ 'href' => htmlspecialchars( $userPageURL, ENT_QUOTES ) ],
			wordwrap( htmlspecialchars( $random_user['user_name'], ENT_QUOTES ), 12, "<br />\n", true )
		);
		$output .= "<div class=\"random-featured-user-title\">$link<br /> " .
				// For grep: random-user-points-weekly, random-user-points-monthly
				wfMessage( "random-user-points-{$period}", $points )->escaped() .
			"</div>\n\n";

		if ( $wgRandomFeaturedUser['about'] == true ) {
			/**
			 * @TODO: Do we need to instantiate a new parser?
			 * Why can't we use the parser passed through by reference?
			 */
			$p = MediaWikiServices::getInstance()->getParserFactory()->create();
			$profile = new UserProfile( $user );
			$profile_data = $profile->getProfile();
			$about = $profile_data['about'] ?? '';
			// Remove templates
			$about = preg_replace( '@{{.*?}}@si', '', $about );
			if ( !empty( $about ) ) {
				global $wgOut;
				$output .= '<div class="random-featured-user-about-title">' .
					wfMessage( 'random-user-about-me' )->escaped() . '</div>' .
					$p->parse( $about, $parser->getTitle(), $wgOut->parserOptions(), false )->getText();
			}
		}

		$output .= '</div><div class="visualClear"></div>';

		return $output;
	}

}
