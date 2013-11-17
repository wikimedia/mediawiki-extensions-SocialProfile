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
$dir = dirname( __FILE__ ) . '/';

// Internationalization files
$wgExtensionMessagesFiles['SocialProfileUserBoard'] = $dir . 'UserBoard/UserBoard.i18n.php';
$wgExtensionMessagesFiles['SocialProfileUserProfile'] = $dir . 'UserProfile/UserProfile.i18n.php';
$wgExtensionMessagesFiles['SocialProfileUserRelationship'] = $dir . 'UserRelationship/UserRelationship.i18n.php';
$wgExtensionMessagesFiles['SocialProfileUserStats'] = $dir . 'UserStats/UserStats.i18n.php';

$wgExtensionMessagesFiles['SocialProfileNamespaces'] = $dir . 'SocialProfile.namespaces.php';
$wgExtensionMessagesFiles['SocialProfileAlias'] = $dir . 'SocialProfile.alias.php';

$wgExtensionMessagesFiles['AvatarMagic'] = $dir . 'UserProfile/Avatar.magic.i18n.php';

// Classes to be autoloaded
$wgAutoloadClasses['GenerateTopUsersReport'] = $dir . 'UserStats/GenerateTopUsersReport.php';

$wgAutoloadClasses['SpecialAddRelationship'] = $dir . 'UserRelationship/SpecialAddRelationship.php';
$wgAutoloadClasses['SpecialBoardBlast'] = $dir . 'UserBoard/SpecialSendBoardBlast.php';
$wgAutoloadClasses['SpecialEditProfile'] = $dir . 'UserProfile/SpecialEditProfile.php';
$wgAutoloadClasses['SpecialPopulateUserProfiles'] = $dir . 'UserProfile/SpecialPopulateExistingUsersProfiles.php';
$wgAutoloadClasses['SpecialRemoveRelationship'] = $dir . 'UserRelationship/SpecialRemoveRelationship.php';
$wgAutoloadClasses['SpecialToggleUserPage'] = $dir . 'UserProfile/SpecialToggleUserPageType.php';
$wgAutoloadClasses['SpecialUpdateProfile'] = $dir . 'UserProfile/SpecialUpdateProfile.php';
$wgAutoloadClasses['SpecialUploadAvatar'] = $dir . 'UserProfile/SpecialUploadAvatar.php';
$wgAutoloadClasses['SpecialViewRelationshipRequests'] = $dir . 'UserRelationship/SpecialViewRelationshipRequests.php';
$wgAutoloadClasses['SpecialViewRelationships'] = $dir . 'UserRelationship/SpecialViewRelationships.php';
$wgAutoloadClasses['SpecialViewUserBoard'] = $dir . 'UserBoard/SpecialUserBoard.php';
$wgAutoloadClasses['RemoveAvatar'] = $dir . 'UserProfile/SpecialRemoveAvatar.php';
$wgAutoloadClasses['UpdateEditCounts'] = $dir . 'UserStats/SpecialUpdateEditCounts.php';
$wgAutoloadClasses['UserBoard'] = $dir . 'UserBoard/UserBoardClass.php';
$wgAutoloadClasses['UserProfile'] = $dir . 'UserProfile/UserProfileClass.php';
$wgAutoloadClasses['UserProfilePage'] = $dir . 'UserProfile/UserProfilePage.php';
$wgAutoloadClasses['UserRelationship'] = $dir . 'UserRelationship/UserRelationshipClass.php';
$wgAutoloadClasses['UserLevel'] = $dir . 'UserStats/UserStatsClass.php';
$wgAutoloadClasses['UserStats'] = $dir . 'UserStats/UserStatsClass.php';
$wgAutoloadClasses['UserStatsTrack'] = $dir . 'UserStats/UserStatsClass.php';
$wgAutoloadClasses['UserSystemMessage'] = $dir . 'UserSystemMessages/UserSystemMessagesClass.php';
$wgAutoloadClasses['TopFansByStat'] = $dir . 'UserStats/TopFansByStat.php';
$wgAutoloadClasses['TopFansRecent'] = $dir . 'UserStats/TopFansRecent.php';
$wgAutoloadClasses['TopUsersPoints'] = $dir . 'UserStats/TopUsers.php';
$wgAutoloadClasses['wAvatar'] = $dir . 'UserProfile/AvatarClass.php';
$wgAutoloadClasses['AvatarParserFunction'] = $dir . 'UserProfile/AvatarParserFunction.php';

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

// Special page groups for MW 1.13+
$wgSpecialPageGroups['AddRelationship'] = 'users';
$wgSpecialPageGroups['RemoveAvatar'] = 'users';
$wgSpecialPageGroups['RemoveRelationship'] = 'users';
$wgSpecialPageGroups['UserBoard'] = 'users';
$wgSpecialPageGroups['ViewRelationshipRequests'] = 'users';
$wgSpecialPageGroups['ViewRelationships'] = 'users';

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
	'version' => '1.6.1',
	'url' => 'https://www.mediawiki.org/wiki/Extension:SocialProfile',
	'description' => 'A set of Social Tools for MediaWiki',
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
	'description' => ' A special page to allow users to send a mass board message by selecting from a list of their friends and foes',
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
// This has to be either here or even earlier on because the loader files mess
// with the $dir variable...
$wgAutoloadClasses['SocialProfileHooks'] = $dir . 'SocialProfileHooks.php';

// Loader files
require_once( "$IP/extensions/SocialProfile/UserProfile/UserProfile.php" ); // Profile page configuration loader file
require_once( "$IP/extensions/SocialProfile/UserGifts/Gifts.php" ); // UserGifts (user-to-user gifting functionality) loader file
require_once( "$IP/extensions/SocialProfile/SystemGifts/SystemGifts.php" ); // SystemGifts (awards functionality) loader file
require_once( "$IP/extensions/SocialProfile/UserActivity/UserActivity.php" ); // UserActivity - recent social changes

$wgHooks['CanonicalNamespaces'][] = 'SocialProfileHooks::onCanonicalNamespaces';
$wgHooks['LoadExtensionSchemaUpdates'][] = 'SocialProfileHooks::onLoadExtensionSchemaUpdates';
$wgHooks['ParserFirstCallInit'][] = 'AvatarParserFunction::setupAvatarParserFunction';

// For the Renameuser extension
//$wgHooks['RenameUserSQL'][] = 'SocialProfileHooks::onRenameUserSQL';

// ResourceLoader module definitions for certain components which do not have
// their own loader file

// UserBoard
$wgResourceModules['ext.socialprofile.userboard.js'] = array(
	'scripts' => 'UserBoard.js',
	'messages' => array( 'userboard_confirmdelete' ),
	'localBasePath' => dirname( __FILE__ ) . '/UserBoard',
	'remoteExtPath' => 'SocialProfile/UserBoard',
);

$wgResourceModules['ext.socialprofile.userboard.css'] = array(
	'styles' => 'UserBoard.css',
	'localBasePath' => dirname( __FILE__ ) . '/UserBoard',
	'remoteExtPath' => 'SocialProfile/UserBoard',
	'position' => 'top' // just in case
);

$wgResourceModules['ext.socialprofile.userboard.boardblast.css'] = array(
	'styles' => 'BoardBlast.css',
	'localBasePath' => dirname( __FILE__ ) . '/UserBoard',
	'remoteExtPath' => 'SocialProfile/UserBoard',
	'position' => 'top' // just in case
);

$wgResourceModules['ext.socialprofile.userboard.boardblast.js'] = array(
	'scripts' => 'BoardBlast.js',
	'messages' => array(
		'boardblast-js-sending', 'boardblast-js-error-missing-message',
		'boardblast-js-error-missing-user'
	),
	'localBasePath' => dirname( __FILE__ ) . '/UserBoard',
	'remoteExtPath' => 'SocialProfile/UserBoard',
);

// UserRelationship
$wgResourceModules['ext.socialprofile.userrelationship.css'] = array(
	'styles' => 'UserRelationship.css',
	'localBasePath' => dirname( __FILE__ ) . '/UserRelationship',
	'remoteExtPath' => 'SocialProfile/UserRelationship',
	'position' => 'top' // just in case
);

$wgResourceModules['ext.socialprofile.userrelationship.js'] = array(
	'scripts' => 'UserRelationship.js',
	'localBasePath' => dirname( __FILE__ ) . '/UserRelationship',
	'remoteExtPath' => 'SocialProfile/UserRelationship',
);

// UserStats
$wgResourceModules['ext.socialprofile.userstats.css'] = array(
	'styles' => 'TopList.css',
	'localBasePath' => dirname( __FILE__ ) . '/UserStats',
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