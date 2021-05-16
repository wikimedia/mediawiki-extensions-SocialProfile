<?php

use MediaWiki\Logger\LoggerFactory;
use MediaWiki\MediaWikiServices;

class SiteActivityHook {

	/**
	 * Register the <siteactivity> hook with the Parser.
	 *
	 * @param Parser $parser
	 */
	public static function onParserFirstCallInit( Parser $parser ) {
		$parser->setHook( 'siteactivity', [ __CLASS__, 'getSiteActivity' ] );
	}

	/**
	 * Callback for ParserFirstCallInit hook subscriber
	 *
	 * @suppress SecurityCheck-XSS Technically a valid issue but not fixable here, the
	 *   real fix is to make the i18n msgs not use raw HTML (T30617, sorta)
	 *
	 * @param string|null $input
	 * @param array $args
	 * @param Parser $parser
	 *
	 * @return string
	 */
	public static function getSiteActivity( $input, array $args, Parser $parser ) {
		$parser->getOutput()->updateCacheExpiry( 0 );

		$limit = ( isset( $args['limit'] ) && is_numeric( $args['limit'] ) ) ? $args['limit'] : 10;

		// so that <siteactivity limit=5 /> will return 5 items instead of 4...
		$fixedLimit = $limit + 1;

		$cache = MediaWikiServices::getInstance()->getMainWANObjectCache();
		$key = $cache->makeKey( 'site_activity', 'all', $fixedLimit );
		$data = $cache->get( $key );
		$logger = LoggerFactory::getInstance( 'SocialProfile' );

		if ( !$data ) {
			$logger->debug( "Got new site activity from DB\n" );

			$rel = new UserActivity( '', 'ALL', $fixedLimit );

			$rel->setActivityToggle( 'show_votes', 0 );
			$activity = $rel->getActivityListGrouped();
			$cache->set( $key, $activity, 60 * 2 );
		} else {
			$logger->debug( "Got site activity from cache\n" );

			$activity = $data;
		}

		$output = '';
		if ( $activity ) {
			$output .= '<div class="mp-site-activity">
			<h2>' . wfMessage( 'useractivity-siteactivity' )->escaped() . '</h2>';

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
