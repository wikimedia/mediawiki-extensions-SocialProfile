<?php

use MediaWiki\Logger\LoggerFactory;

class SiteActivityHook {

	/**
	 * Register the <siteactivity> hook with the Parser.
	 *
	 * @param Parser $parser
	 */
	public static function onParserFirstCallInit( &$parser ) {
		$parser->setHook( 'siteactivity', array( __CLASS__, 'getSiteActivity' ) );
	}

	/**
	 * Callback for ParserFirstCallInit hook subscriber
	 *
	 * @param string|null $input
	 * @param array $args
	 * @param Parser $parser
	 */
	public static function getSiteActivity( $input, $args, $parser ) {
		global $wgMemc;

		$parser->getOutput()->updateCacheExpiry( 0 );

		$limit = ( isset( $args['limit'] ) && is_numeric( $args['limit'] ) ) ? $args['limit'] : 10;

		// so that <siteactivity limit=5 /> will return 5 items instead of 4...
		$fixedLimit = $limit + 1;

		$key = $wgMemc->makeKey( 'site_activity', 'all', $fixedLimit );
		$data = $wgMemc->get( $key );
		$logger = LoggerFactory::getInstance( 'SocialProfile' );

		if ( !$data ) {
			$logger->debug( "Got new site activity from DB\n" );

			$rel = new UserActivity( '', 'ALL', $fixedLimit );

			$rel->setActivityToggle( 'show_votes', 0 );
			$activity = $rel->getActivityListGrouped();
			$wgMemc->set( $key, $activity, 60 * 2 );
		} else {
			$logger->debug( "Got site activity from cache\n" );

			$activity = $data;
		}

		$output = '';
		if ( $activity ) {
			$output .= '<div class="mp-site-activity">
			<h2>' . wfMessage( 'useractivity-siteactivity' )->plain() . '</h2>';

			$x = 1;
			foreach ( $activity as $item ) {
				if ( $x < $fixedLimit ) {
					$userActivityIcon = new UserActivityIcon( $item['type'] );
					$icon = $userActivityIcon->getIconHTML();

					$output .= '<div class="mp-activity' . ( ( $x == $fixedLimit ) ? ' mp-activity-border-fix' : '' ) . '">' .
					$icon . $item['data'] .
					'</div>';
					$x++;
				}
			}

			$output .= '</div>';
		}

		return $output;
	}

}