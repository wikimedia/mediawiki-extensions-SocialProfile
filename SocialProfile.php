<?php
/**
 * Protect against register_globals vulnerabilities.
 * This line must be present before any global variable is referenced.
 */
if ( !defined( 'MEDIAWIKI' ) ) {
	die(
		'This is the setup file for the SocialProfile extension to MediaWiki.' .
		'Please see http://www.mediawiki.org/wiki/Extension:SocialProfile for' .
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

$wgMessagesDirs['SocialProfileUserBoard'] = __DIR__ . '/UserBoard/i18n';
$wgMessagesDirs['SocialProfileUserProfile'] = __DIR__ . '/UserProfile/i18n';
$wgMessagesDirs['SocialProfileUserRelationship'] = __DIR__ . '/UserRelationship/i18n';
$wgMessagesDirs['SocialProfileUserStats'] = __DIR__ . '/UserStats/i18n';
$wgExtensionMessagesFiles['SocialProfileNamespaces'] = __DIR__ . '/SocialProfile.namespaces.php';
$wgExtensionMessagesFiles['AvatarMagic'] = __DIR__ . '/UserProfile/Avatar.magic.i18n.php';

// Classes to be autoloaded
$wgAutoloadClasses['GenerateTopUsersReport'] = __DIR__ . '/UserStats/GenerateTopUsersReport.php';

$wgAutoloadClasses['SpecialAddRelationship'] = __DIR__ . '/UserRelationship/SpecialAddRelationship.php';
$wgAutoloadClasses['SpecialBoardBlast'] = __DIR__ . '/UserBoard/SpecialSendBoardBlast.php';
$wgAutoloadClasses['SpecialEditProfile'] = __DIR__ . '/UserProfile/SpecialEditProfile.php';
$wgAutoloadClasses['SpecialPopulateUserProfiles'] = __DIR__ . '/UserProfile/SpecialPopulateExistingUsersProfiles.php';
$wgAutoloadClasses['SpecialRemoveRelationship'] = __DIR__ . '/UserRelationship/SpecialRemoveRelationship.php';
$wgAutoloadClasses['SpecialToggleUserPage'] = __DIR__ . '/UserProfile/SpecialToggleUserPageType.php';
$wgAutoloadClasses['SpecialUpdateProfile'] = __DIR__ . '/UserProfile/SpecialUpdateProfile.php';
$wgAutoloadClasses['SpecialUploadAvatar'] = __DIR__ . '/UserProfile/SpecialUploadAvatar.php';
$wgAutoloadClasses['SpecialViewRelationshipRequests'] = __DIR__ . '/UserRelationship/SpecialViewRelationshipRequests.php';
$wgAutoloadClasses['SpecialViewRelationships'] = __DIR__ . '/UserRelationship/SpecialViewRelationships.php';
$wgAutoloadClasses['SpecialViewUserBoard'] = __DIR__ . '/UserBoard/SpecialUserBoard.php';
$wgAutoloadClasses['RemoveAvatar'] = __DIR__ . '/UserProfile/SpecialRemoveAvatar.php';
$wgAutoloadClasses['UpdateEditCounts'] = __DIR__ . '/UserStats/SpecialUpdateEditCounts.php';
$wgAutoloadClasses['UserBoard'] = __DIR__ . '/UserBoard/UserBoardClass.php';
$wgAutoloadClasses['UserProfile'] = __DIR__ . '/UserProfile/UserProfileClass.php';
$wgAutoloadClasses['UserProfilePage'] = __DIR__ . '/UserProfile/UserProfilePage.php';
$wgAutoloadClasses['UserRelationship'] = __DIR__ . '/UserRelationship/UserRelationshipClass.php';
$wgAutoloadClasses['UserLevel'] = __DIR__ . '/UserStats/UserStatsClass.php';
$wgAutoloadClasses['UserStats'] = __DIR__ . '/UserStats/UserStatsClass.php';
$wgAutoloadClasses['UserStatsTrack'] = __DIR__ . '/UserStats/UserStatsClass.php';
$wgAutoloadClasses['UserSystemMessage'] = __DIR__ . '/UserSystemMessages/UserSystemMessagesClass.php';
$wgAutoloadClasses['TopFansByStat'] = __DIR__ . '/UserStats/TopFansByStat.php';
$wgAutoloadClasses['TopFansRecent'] = __DIR__ . '/UserStats/TopFansRecent.php';
$wgAutoloadClasses['TopUsersPoints'] = __DIR__ . '/UserStats/TopUsers.php';
$wgAutoloadClasses['wAvatar'] = __DIR__ . '/UserProfile/AvatarClass.php';
$wgAutoloadClasses['AvatarParserFunction'] = __DIR__ . '/UserProfile/AvatarParserFunction.php';

// New special pages
$wgSpecialPages['AddRelationship'] = 'SpecialAddRelationship';
$wgSpecialPages['EditProfile'] = 'SpecialEditProfile';
$wgSpecialPages['GenerateTopUsersReport'] = 'GenerateTopUsersReport';
$wgSpecialPages['PopulateUserProfiles'] = 'SpecialPopulateUserProfiles';
$wgSpecialPages['RemoveAvatar'] = 'RemoveAvatar';
$wgSpecialPages['RemoveRelationship'] = 'SpecialRemoveRelationship';
$wgSpecialPages['SendBoardBlast'] = 'SpecialBoardBlast';
$wgSpecialPages['TopFansByStatistic'] = 'TopFansByStat';
$wgSpecialPages['TopUsers'] = 'TopUsersPoints';
$wgSpecialPages['TopUsersRecent'] = 'TopFansRecent';
$wgSpecialPages['ToggleUserPage'] = 'SpecialToggleUserPage';
$wgSpecialPages['UpdateEditCounts'] = 'UpdateEditCounts';
$wgSpecialPages['UpdateProfile'] = 'SpecialUpdateProfile';
$wgSpecialPages['UploadAvatar'] = 'SpecialUploadAvatar';
$wgSpecialPages['UserBoard'] = 'SpecialViewUserBoard';
$wgSpecialPages['ViewRelationshipRequests'] = 'SpecialViewRelationshipRequests';
$wgSpecialPages['ViewRelationships'] = 'SpecialViewRelationships';

// Necessary AJAX functions
require_once( "$IP/extensions/SocialProfile/UserBoard/UserBoard_AjaxFunctions.php" );
require_once( "$IP/extensions/SocialProfile/UserRelationship/Relationship_AjaxFunctions.php" );

// What to display on social profile pages by default?
$wgUserProfileDisplay['board'] = true;
$wgUserProfileDisplay['foes'] = true;
$wgUserProfileDisplay['friends'] = true;

// Should we display UserBoard-related things on social profile pages?
$wgUserBoard = true;

// Whether to enable friending or not -- this doesn't do very much actually, so don't rely on it
$wgFriendingEnabled = true;

// Extension credits that show up on Special:Version
$wgExtensionCredits['other'][] = array(
	'path' => __FILE__,
	'name' => 'SocialProfile',
	'author' => array( 'Aaron Wright', 'David Pean', 'Jack Phoenix' ),
	'version' => '1.7.1',
	'url' => 'https://www.mediawiki.org/wiki/Extension:SocialProfile',
	'descriptionmsg' => 'socialprofile-desc',
);
$wgExtensionCredits['specialpage'][] = array(
	'path' => __FILE__,
	'name' => 'TopUsers',
	'author' => 'David Pean',
	'url' => 'https://www.mediawiki.org/wiki/Extension:SocialProfile',
	'description' => 'Adds a special page for viewing the list of users with the most points.',
);
$wgExtensionCredits['specialpage'][] = array(
	'path' => __FILE__,
	'name' => 'UploadAvatar',
	'author' => 'David Pean',
	'url' => 'https://www.mediawiki.org/wiki/Extension:SocialProfile',
	'description' => 'A special page for uploading Avatars',
);
$wgExtensionCredits['specialpage'][] = array(
	'path' => __FILE__,
	'name' => 'RemoveAvatar',
	'author' => 'David Pean',
	'url' => 'https://www.mediawiki.org/wiki/Extension:SocialProfile',
	'description' => 'A special page for removing users\' avatars',
);
$wgExtensionCredits['specialpage'][] = array(
	'path' => __FILE__,
	'name' => 'PopulateExistingUsersProfiles',
	'author' => 'David Pean',
	'url' => 'https://www.mediawiki.org/wiki/Extension:SocialProfile',
	'description' => 'A special page for initializing social profiles for existing wikis',
);
$wgExtensionCredits['specialpage'][] = array(
	'path' => __FILE__,
	'name' => 'ToggleUserPage',
	'author' => 'David Pean',
	'url' => 'https://www.mediawiki.org/wiki/Extension:SocialProfile',
	'description' => 'A special page for updating a user\'s userpage preference',
);
$wgExtensionCredits['specialpage'][] = array(
	'path' => __FILE__,
	'name' => 'UpdateProfile',
	'author' => 'David Pean',
	'url' => 'https://www.mediawiki.org/wiki/Extension:SocialProfile',
	'description' => 'A special page to allow users to update their social profile',
);
$wgExtensionCredits['specialpage'][] = array(
	'path' => __FILE__,
	'name' => 'SendBoardBlast',
	'author' => 'David Pean',
	'url' => 'https://www.mediawiki.org/wiki/Extension:SocialProfile',
	'description' => 'A special page to allow users to send a mass board message by selecting from a list of their friends and foes',
);
$wgExtensionCredits['specialpage'][] = array(
	'path' => __FILE__,
	'name' => 'UserBoard',
	'author' => 'David Pean',
	'url' => 'https://www.mediawiki.org/wiki/Extension:SocialProfile',
	'description' => 'Display User Board messages for a user',
);
$wgExtensionCredits['specialpage'][] = array(
	'path' => __FILE__,
	'name' => 'AddRelationship',
	'author' => 'David Pean',
	'url' => 'https://www.mediawiki.org/wiki/Extension:SocialProfile',
	'description' => 'A special page for adding friends/foe requests for existing users in the wiki',
);
$wgExtensionCredits['specialpage'][] = array(
	'path' => __FILE__,
	'name' => 'RemoveRelationship',
	'author' => 'David Pean',
	'url' => 'https://www.mediawiki.org/wiki/Extension:SocialProfile',
	'description' => 'A special page for removing existing friends/foes for the current logged in user',
);
$wgExtensionCredits['specialpage'][] = array(
	'path' => __FILE__,
	'name' => 'ViewRelationshipRequests',
	'author' => 'David Pean',
	'url' => 'https://www.mediawiki.org/wiki/Extension:SocialProfile',
	'description' => 'A special page for viewing open relationship requests for the current logged in user',
);
$wgExtensionCredits['specialpage'][] = array(
	'path' => __FILE__,
	'name' => 'ViewRelationships',
	'author' => 'David Pean',
	'url' => 'https://www.mediawiki.org/wiki/Extension:SocialProfile',
	'description' => 'A special page for viewing all relationships by type',
);
$wgExtensionCredits['parserhook'][] = array(
	'path' => __FILE__,
	'name' => 'Avatar',
	'author' => 'Adam Carter',
	'url' => 'https://www.mediawiki.org/wiki/Extension:SocialProfile',
	'description' => 'A parser function to get the avatar of a given user',
);

// Hooked functions
$wgAutoloadClasses['SocialProfileHooks'] = __DIR__ . '/SocialProfileHooks.php';

// Loader files
require_once( "$IP/extensions/SocialProfile/UserProfile/UserProfile.php" ); // Profile page configuration loader file
require_once( "$IP/extensions/SocialProfile/UserGifts/Gifts.php" ); // UserGifts (user-to-user gifting functionality) loader file
require_once( "$IP/extensions/SocialProfile/SystemGifts/SystemGifts.php" ); // SystemGifts (awards functionality) loader file
require_once( "$IP/extensions/SocialProfile/UserActivity/UserActivity.php" ); // UserActivity - recent social changes

$wgHooks['CanonicalNamespaces'][] = 'SocialProfileHooks::onCanonicalNamespaces';
$wgHooks['LoadExtensionSchemaUpdates'][] = 'SocialProfileHooks::onLoadExtensionSchemaUpdates';
$wgHooks['ParserFirstCallInit'][] = 'AvatarParserFunction::setupAvatarParserFunction';

// For the Renameuser extension
$wgHooks['RenameUserComplete'][] = 'SocialProfileHooks::onRenameUserComplete';

// ResourceLoader module definitions for certain components which do not have
// their own loader file

// UserBoard
$wgResourceModules['ext.socialprofile.userboard.js'] = array(
	'scripts' => 'UserBoard.js',
	'messages' => array( 'userboard_confirmdelete' ),
	'localBasePath' => __DIR__ . '/UserBoard',
	'remoteExtPath' => 'SocialProfile/UserBoard',
);

$wgResourceModules['ext.socialprofile.userboard.css'] = array(
	'styles' => 'UserBoard.css',
	'localBasePath' => __DIR__ . '/UserBoard',
	'remoteExtPath' => 'SocialProfile/UserBoard',
	'position' => 'top' // just in case
);

$wgResourceModules['ext.socialprofile.userboard.boardblast.css'] = array(
	'styles' => 'BoardBlast.css',
	'localBasePath' => __DIR__ . '/UserBoard',
	'remoteExtPath' => 'SocialProfile/UserBoard',
	'position' => 'top' // just in case
);

$wgResourceModules['ext.socialprofile.userboard.boardblast.js'] = array(
	'scripts' => 'BoardBlast.js',
	'messages' => array(
		'boardblast-js-sending', 'boardblast-js-error-missing-message',
		'boardblast-js-error-missing-user'
	),
	'localBasePath' => __DIR__ . '/UserBoard',
	'remoteExtPath' => 'SocialProfile/UserBoard',
);

// UserRelationship
$wgResourceModules['ext.socialprofile.userrelationship.css'] = array(
	'styles' => 'UserRelationship.css',
	'localBasePath' => __DIR__ . '/UserRelationship',
	'remoteExtPath' => 'SocialProfile/UserRelationship',
	'position' => 'top' // just in case
);

$wgResourceModules['ext.socialprofile.userrelationship.js'] = array(
	'scripts' => 'UserRelationship.js',
	'localBasePath' => __DIR__ . '/UserRelationship',
	'remoteExtPath' => 'SocialProfile/UserRelationship',
);

// UserStats
$wgResourceModules['ext.socialprofile.userstats.css'] = array(
	'styles' => 'TopList.css',
	'localBasePath' => __DIR__ . '/UserStats',
	'remoteExtPath' => 'SocialProfile/UserStats',
	'position' => 'top' // just in case
);

// End ResourceLoader stuff

if( !defined( 'NS_USER_WIKI' ) ) {
	define( 'NS_USER_WIKI', 200 );
}

if( !defined( 'NS_USER_WIKI_TALK' ) ) {
	define( 'NS_USER_WIKI_TALK', 201 );
}

if( !defined( 'NS_USER_PROFILE' ) ) {
	define( 'NS_USER_PROFILE', 202 );
}

if( !defined( 'NS_USER_PROFILE_TALK' ) ) {
	define( 'NS_USER_PROFILE_TALK', 203 );
}
