<?php
/**
 * UserActivity extension - shows users' social activity
 *
 * @file
 * @ingroup Extensions
 * @version 1.2
 * @author Aaron Wright <aaron.wright@gmail.com>
 * @author David Pean <david.pean@gmail.com>
 * @author Jack Phoenix <jack@countervandalism.net>
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License 2.0 or later
 */

/**
 * Protect against register_globals vulnerabilities.
 * This line must be present before any global variable is referenced.
 */
if ( !defined( 'MEDIAWIKI' ) ) {
	die( "Not a valid entry point.\n" );
}

// Extension credits that will show up on Special:Version
$wgExtensionCredits['specialpage'][] = array(
	'name' => 'UserActivity',
	'version' => '1.2',
	'author' => array( 'Aaron Wright', 'David Pean', 'Jack Phoenix' ),
	'description' => "Shows users' social activity",
	'url' => 'https://www.mediawiki.org/wiki/Extension:SocialProfile'
);

// Set up the new special page
$wgMessagesDirs['UserActivity'] = __DIR__ . '/i18n';
$wgAutoloadClasses['UserActivity'] = __DIR__ . '/UserActivityClass.php';
$wgAutoloadClasses['UserHome'] = __DIR__ . '/UserActivity.body.php';
$wgSpecialPages['UserActivity'] = 'UserHome';

// Register the CSS with ResourceLoader
$wgResourceModules['ext.socialprofile.useractivity.css'] = array(
	'styles' => 'UserActivity.css',
	'localBasePath' => __DIR__,
	'remoteExtPath' => 'SocialProfile/UserActivity',
	'position' => 'top'
);

// Load <siteactivity> parser hook
require_once( 'SiteActivityHook.php' );
