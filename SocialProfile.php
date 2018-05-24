<?php
/**
 * Protect against register_globals vulnerabilities.
 * This line must be present before any global variable is referenced.
 */
if ( !defined( 'MEDIAWIKI' ) ) {
	die(
		'This is the setup file for the SocialProfile extension to MediaWiki.' .
		'Please see https://www.mediawiki.org/wiki/Extension:SocialProfile for' .
		' more information about this extension.'
	);
}

/**
 * This is the loader file for the SocialProfile extension. You should include
 * this file in your wiki's LocalSettings.php to activate SocialProfile.
 *
 * If you want to use the UserWelcome extension (bundled with SocialProfile),
 * the <topusers /> tag or the user levels feature, there are some other files
 * you will need to include in LocalSettings.php. The online manual has more
 * details about this.
 *
 * For more info about SocialProfile, please see https://www.mediawiki.org/wiki/Extension:SocialProfile.
 */

// Internationalization files
$wgMessagesDirs['SocialProfile'] = __DIR__ . '/i18n';
$wgExtensionMessagesFiles['SocialProfileAlias'] = __DIR__ . '/SocialProfile.alias.php';

$wgMessagesDirs['SocialProfileUserProfile'] = __DIR__ . '/UserProfile/i18n';

$wgExtensionMessagesFiles['SocialProfileNamespaces'] = __DIR__ . '/SocialProfile.namespaces.php';
$wgExtensionMessagesFiles['AvatarMagic'] = __DIR__ . '/UserProfile/includes/avatar/Avatar.i18n.magic.php';

// Classes to be autoloaded
$wgAutoloadClasses['SpecialEditProfile'] = __DIR__ . '/UserProfile/includes/specials/SpecialEditProfile.php';
$wgAutoloadClasses['SpecialPopulateUserProfiles'] = __DIR__ . '/UserProfile/includes/specials/SpecialPopulateExistingUsersProfiles.php';
$wgAutoloadClasses['SpecialToggleUserPage'] = __DIR__ . '/UserProfile/includes/specials/SpecialToggleUserPageType.php';
$wgAutoloadClasses['SpecialUpdateProfile'] = __DIR__ . '/UserProfile/includes/specials/SpecialUpdateProfile.php';
$wgAutoloadClasses['SpecialUploadAvatar'] = __DIR__ . '/UserProfile/includes/specials/SpecialUploadAvatar.php';
$wgAutoloadClasses['UploadAvatar'] = __DIR__ . '/UserProfile/includes/avatar/UploadAvatar.php';
$wgAutoloadClasses['RemoveAvatar'] = __DIR__ . '/UserProfile/includes/specials/SpecialRemoveAvatar.php';
$wgAutoloadClasses['UserProfile'] = __DIR__ . '/UserProfile/includes/UserProfile.php';
$wgAutoloadClasses['UserProfileHooks'] = __DIR__ . '/UserProfile/includes/UserProfileHooks.php';
$wgAutoloadClasses['UserProfilePage'] = __DIR__ . '/UserProfile/includes/UserProfilePage.php';
$wgAutoloadClasses['wAvatar'] = __DIR__ . '/UserProfile/includes/avatar/Avatar.php';
$wgAutoloadClasses['AvatarParserFunction'] = __DIR__ . '/UserProfile/includes/parser/AvatarParserFunction.php';
$wgAutoloadClasses['SPUserSecurity'] = __DIR__ . '/UserProfile/includes/SPUserSecurity.php';
$wgAutoloadClasses['RandomUsersWithAvatars'] = __DIR__ . '/UserProfile/includes/parser/RandomUsersWithAvatars.php';
$wgAutoloadClasses['NewUsersList'] = __DIR__ . '/UserProfile/includes/parser/NewUsersList.php';

// API modules
$wgAutoloadClasses['ApiUserProfilePrivacy'] = __DIR__ . '/UserProfile/includes/api/ApiUserProfilePrivacy.php';
$wgAPIModules['smpuserprivacy'] = 'ApiUserProfilePrivacy';

$wgDefaultUserOptions['echo-subscriptions-web-social-rel'] = true;
$wgDefaultUserOptions['echo-subscriptions-email-social-rel'] = false;

// New special pages
$wgSpecialPages['EditProfile'] = 'SpecialEditProfile';
$wgSpecialPages['PopulateUserProfiles'] = 'SpecialPopulateUserProfiles';
$wgSpecialPages['RemoveAvatar'] = 'RemoveAvatar';
$wgSpecialPages['ToggleUserPage'] = 'SpecialToggleUserPage';
$wgSpecialPages['UpdateProfile'] = 'SpecialUpdateProfile';
$wgSpecialPages['UploadAvatar'] = 'SpecialUploadAvatar';

// What to display on social profile pages by default?
$wgUserProfileDisplay['board'] = true;
$wgUserProfileDisplay['foes'] = true;
$wgUserProfileDisplay['friends'] = true;
$wgUserProfileDisplay['avatar'] = true; // If set to false, disables both avatar display and upload

// Should we display UserBoard-related things on social profile pages?
$wgUserBoard = true;

// Whether to enable friending or not -- this doesn't do very much actually, so don't rely on it
$wgFriendingEnabled = true;

// Prefix SocialProfile will use to store avatars
// for global avatars on a wikifarm or groups of wikis,
// set this to something static.
$wgAvatarKey = $wgDBname;

// Extension credits that show up on Special:Version
$wgExtensionCredits['other'][] = [
	'path' => __FILE__,
	'name' => 'SocialProfile',
	'author' => [ 'Aaron Wright', 'David Pean', 'Jack Phoenix' ],
	'version' => '1.13',
	'url' => 'https://www.mediawiki.org/wiki/Extension:SocialProfile',
	'descriptionmsg' => 'socialprofile-desc',
];

// Hooked functions
$wgAutoloadClasses['SocialProfileHooks'] = __DIR__ . '/SocialProfileHooks.php';

// Loader files
require_once __DIR__ . '/UserProfile/UserProfile.php'; // Profile page configuration loader file
wfLoadExtensions( [
	'SocialProfile/SystemGifts', // SystemGifts (awards functionality)
	'SocialProfile/UserActivity', // UserActivity - recent social changes
	'SocialProfile/UserBoard',
	'SocialProfile/UserRelationship',
	'SocialProfile/UserStats',
	'SocialProfile/UserGifts',
] );

$wgHooks['BeforePageDisplay'][] = 'SocialProfileHooks::onBeforePageDisplay';
$wgHooks['CanonicalNamespaces'][] = 'SocialProfileHooks::onCanonicalNamespaces';
$wgHooks['LoadExtensionSchemaUpdates'][] = 'SocialProfileHooks::onLoadExtensionSchemaUpdates';
$wgHooks['ParserFirstCallInit'][] = 'AvatarParserFunction::setupAvatarParserFunction';

// For the Renameuser extension
$wgHooks['RenameUserComplete'][] = 'SocialProfileHooks::onRenameUserComplete';

// ResourceLoader module definitions for certain components which do not have
// their own loader file

// General
$wgResourceModules['ext.socialprofile.clearfix'] = [
	'styles' => 'clearfix.css',
	'localBasePath' => __DIR__ . '/shared',
	'remoteExtPath' => 'SocialProfile/shared',
];

$wgResourceModules['ext.socialprofile.responsive'] = [
	'styles' => 'responsive.less',
	'localBasePath' => __DIR__ . '/shared',
	'remoteExtPath' => 'SocialProfile/shared',
];

// General/shared JS modules -- not (necessarily) directly used by SocialProfile,
// but rather by other social tools which depend on SP
// @see https://phabricator.wikimedia.org/T100025
$wgResourceModules['ext.socialprofile.flash'] = [
	'scripts' => 'flash.js',
	'localBasePath' => __DIR__ . '/shared',
	'remoteExtPath' => 'SocialProfile/shared',
];

$wgResourceModules['ext.socialprofile.LightBox'] = [
	'scripts' => 'LightBox.js',
	'localBasePath' => __DIR__ . '/shared',
	'remoteExtPath' => 'SocialProfile/shared',
];

// End ResourceLoader stuff

if ( !defined( 'NS_USER_WIKI' ) ) {
	define( 'NS_USER_WIKI', 200 );
}

if ( !defined( 'NS_USER_WIKI_TALK' ) ) {
	define( 'NS_USER_WIKI_TALK', 201 );
}

if ( !defined( 'NS_USER_PROFILE' ) ) {
	define( 'NS_USER_PROFILE', 202 );
}

if ( !defined( 'NS_USER_PROFILE_TALK' ) ) {
	define( 'NS_USER_PROFILE_TALK', 203 );
}
