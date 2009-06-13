<?php
/**
 * Protect against register_globals vulnerabilities.
 * This line must be present before any global variable is referenced.
 */
if ( !defined( 'MEDIAWIKI' ) ) {
	die( "Not a valid entry point.\n" );
}

// Avoid unstubbing $wgParser on setHook() too early on modern (1.12+) MW versions, as per r35980
if ( defined( 'MW_SUPPORTS_PARSERFIRSTCALLINIT' ) ) {
	$wgHooks['ParserFirstCallInit'][] = 'wfSiteActivity';
} else {
	$wgExtensionFunctions[] = 'wfSiteActivity';
}

function wfSiteActivity() {
	global $wgParser;
	$wgParser->setHook( 'siteactivity', 'getSiteActivity' );
	return true;
}

function getSiteActivity( $input, $args, &$parser ) {
	global $wgMemc, $wgScriptPath;

	$parser->disableCache();

	$limit = ( isset( $args['limit'] ) && is_numeric( $args['limit'] ) ) ? $args['limit'] : 10;

	wfLoadExtensionMessages( 'UserActivity' );

	$key = wfMemcKey( 'site_activity', 'all', $limit );
	$data = $wgMemc->get( $key );
	if ( !$data ) {
		wfDebug( "Got site activity from DB\n" );
		$rel = new UserActivity( '', 'ALL', $limit );

		$rel->setActivityToggle( 'show_votes', 0 );
		$rel->setActivityToggle( 'show_network_updates', 0 );
		$activity = $rel->getActivityListGrouped();
		$wgMemc->set( $key, $activity, 60 * 2 );
	} else {
		wfDebug( "Got site activity from cache\n" );
		$activity = $data;
	}

	$output = '';
	if ( $activity ) {

		$output .= '<div class="mp-site-activity"><h2>' . wfMsg( 'useractivity-siteactivity' ) . '</h2>';

		$x = 1;
		foreach ( $activity as $item ) {
			if ( $x < $limit ) {
				$output .= '<div class="mp-activity' . ( ( $x == $limit ) ? ' mp-activity-boarder-fix' : '' ) . '">
				<img src="' . $wgScriptPath . '/extensions/SocialProfile/images/' . UserActivity::getTypeIcon( $item['type'] ) . '" alt="' . UserActivity::getTypeIcon( $item['type'] ) . '" border="0" />'
				. $item['data'] . '</div>';
				$x++;
			}
		}

		$output .= '</div>';
	}

	return $output;
}
