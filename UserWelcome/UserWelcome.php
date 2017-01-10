<?php
/**
 * UserWelcome extension
 * Adds <welcomeUser/> tag to display user-specific social information
 *
 * @file
 * @ingroup Extensions
 * @version 1.5
 * @author David Pean <david.pean@gmail.com>
 * @author Jack Phoenix <jack@countervandalism.net>
 * @link https://www.mediawiki.org/wiki/Extension:UserWelcome Documentation
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License 2.0 or later
 */

if( !defined( 'MEDIAWIKI' ) ) {
	die( "This is not a valid entry point.\n" );
}

// Extension credits that show up on Special:Version
$wgExtensionCredits['parserhook'][] = array(
	'path' => __FILE__,
	'name' => 'UserWelcome',
	'version' => '1.5',
	'author' => array( 'David Pean', 'Jack Phoenix' ),
	'descriptionmsg' => 'userwelcome-desc',
	'url' => 'https://www.mediawiki.org/wiki/Extension:UserWelcome',
);

// Register the CSS with ResourceLoader
$wgResourceModules['ext.socialprofile.userwelcome.css'] = array(
	'styles' => 'UserWelcome.css',
	'localBasePath' => __DIR__,
	'remoteExtPath' => 'SocialProfile/UserWelcome',
	'position' => 'top'
);

$wgAutoloadClasses['UserWelcome'] = __DIR__ . '/UserWelcomeClass.php';
$wgMessagesDirs['UserWelcome'] = __DIR__ . '/i18n';

$wgHooks['ParserFirstCallInit'][] = 'UserWelcome::onParserFirstCallInit';
