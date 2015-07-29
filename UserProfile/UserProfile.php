<?php
// Global profile namespace reference
define( 'NS_USER_PROFILE', 202 );
define( 'NS_USER_WIKI', 200 );

/**
 * If you want to require users to have a certain number of certain things, like
 * five edits or three friends or two comments or whatever (is supported by
 * SocialProfile/the user_stats DB table) before they can use Special:UpdateProfile,
 * use this global.
 *
 * For example, to require a user to have five edits before they're allowed to access
 * Special:UpdateProfile, set:
 * @code
 * $wgUserProfileThresholds = array( 'edits' => 5 );
 * @endcode
 *
 * To require both ten edits *and* three friends, set:
 * @code
 * $wgUserProfileThresholds = array( 'edits' => 10, 'friend-count' => 3 );
 * @endcode
 */
$wgUserProfileThresholds = array(
/**
 * All currently "supported" options (supported meaning that there is i18n support):
 * edits // normal edits in the namespaces that earn you points ($wgNamespacesForEditPoints)
 * votes // [[mw:Extension:VoteNY]] votes
 * comments // [[mw:Extension:Comments]] comments
 * comment-score-plus // [[mw:Extension:Comments]] upvoted comments
 * comment-score-minus // [[mw:Extension:Comments]] downvoted comments
 * recruits // recruits; see [[mw:Extension:NewSignupPage]]
 * friend-count // friends
 * foe-count // foes
 * weekly-wins // @see /UserStats/GenerateTopUsersReport.php
 * monthly-wins // @see /UserStats/GenerateTopUsersReport.php
 * poll-votes // [[mw:Extension:PollNY]] votes
 * picture-game-votes // [[mw:Extension:PictureGame]] votes
 * quiz-created // [[mw:Extension:QuizGame]] created quizzes
 * quiz-answered // [[mw:Extension:QuizGame]] answered quizzes in total
 * quiz-correct // [[mw:Extension:QuizGame]] correctly answered quizzes
 * quiz-points // [[mw:Extension:QuizGame]] points in total
*/
);

// Default setup for displaying sections
$wgUserPageChoice = true;

$wgUserProfileDisplay['friends'] = false;
$wgUserProfileDisplay['foes'] = false;
$wgUserProfileDisplay['gifts'] = true;
$wgUserProfileDisplay['awards'] = true;
$wgUserProfileDisplay['profile'] = true;
$wgUserProfileDisplay['board'] = false;
$wgUserProfileDisplay['stats'] = false; // Display statistics on user profile pages?
$wgUserProfileDisplay['interests'] = true;
$wgUserProfileDisplay['custom'] = true;
$wgUserProfileDisplay['personal'] = true;
$wgUserProfileDisplay['activity'] = false; // Display recent social activity?
$wgUserProfileDisplay['userboxes'] = false; // If FanBoxes extension is installed, setting this to true will display the user's fanboxes on their profile page
$wgUserProfileDisplay['games'] = false; // Display casual games created by the user on their profile? This requires three separate social extensions: PictureGame, PollNY and QuizGame

$wgUpdateProfileInRecentChanges = false; // Show a log entry in recent changes whenever a user updates their profile?
$wgUploadAvatarInRecentChanges = false; // Same as above, but for avatar uploading

$wgAvailableRights[] = 'avatarremove';
$wgAvailableRights[] = 'editothersprofiles';
$wgGroupPermissions['sysop']['avatarremove'] = true;
$wgGroupPermissions['staff']['editothersprofiles'] = true;

// ResourceLoader support for MediaWiki 1.17+
$wgResourceModules['ext.socialprofile.userprofile.css'] = array(
	'styles' => 'UserProfile.css',
	'localBasePath' => __DIR__,
	'remoteExtPath' => 'SocialProfile/UserProfile',
	'position' => 'top'
);

$wgResourceModules['ext.socialprofile.userprofile.js'] = array(
	'scripts' => 'UserProfilePage.js',
	'localBasePath' => __DIR__,
	'remoteExtPath' => 'SocialProfile/UserProfile',
);

// Modules for Special:EditProfile/Special:UpdateProfile
$wgResourceModules['ext.userProfile.updateProfile'] = array(
	'scripts' => 'UpdateProfile.js',
	'localBasePath' => __DIR__,
	'remoteExtPath' => 'SocialProfile/UserProfile',
	'position' => 'top'
);

# Add new log types for profile edits and avatar uploads
global $wgLogTypes, $wgLogNames, $wgLogHeaders, $wgLogActions;
$wgLogTypes[]                    = 'profile';
$wgLogNames['profile']           = 'profilelogpage';
$wgLogHeaders['profile']         = 'profilelogpagetext';
$wgLogActions['profile/profile'] = 'profilelogentry';

$wgLogTypes[]                    = 'avatar';
$wgLogNames['avatar']            = 'avatarlogpage';
$wgLogHeaders['avatar']          = 'avatarlogpagetext';
$wgLogActions['avatar/avatar'] = 'avatarlogentry';

$wgHooks['ArticleFromTitle'][] = 'wfUserProfileFromTitle';

/**
 * Called by ArticleFromTitle hook
 * Calls UserProfilePage instead of standard article
 *
 * @param &$title Title object
 * @param &$article Article object
 * @return true
 */
function wfUserProfileFromTitle( &$title, &$article ) {
	global $wgRequest, $wgOut, $wgHooks, $wgUserPageChoice;

	if ( strpos( $title->getText(), '/' ) === false &&
		( NS_USER == $title->getNamespace() || NS_USER_PROFILE == $title->getNamespace() )
	) {
		$show_user_page = false;
		if ( $wgUserPageChoice ) {
			$profile = new UserProfile( $title->getText() );
			$profile_data = $profile->getProfile();

			// If they want regular page, ignore this hook
			if ( isset( $profile_data['user_id'] ) && $profile_data['user_id'] && $profile_data['user_page_type'] == 0 ) {
				$show_user_page = true;
			}
		}

		if ( !$show_user_page ) {
			// Prevents editing of userpage
			if ( $wgRequest->getVal( 'action' ) == 'edit' ) {
				$wgOut->redirect( $title->getFullURL() );
			}
		} else {
			$wgOut->enableClientCache( false );
			$wgHooks['ParserLimitReport'][] = 'wfUserProfileMarkUncacheable';
		}

		$wgOut->addModuleStyles( array(
			'ext.socialprofile.clearfix',
			'ext.socialprofile.userprofile.css'
		) );

		$article = new UserProfilePage( $title );
	}
	return true;
}

/**
 * Mark page as uncacheable
 *
 * @param $parser Parser object
 * @param &$limitReport String: unused
 * @return true
 */
function wfUserProfileMarkUncacheable( $parser, &$limitReport ) {
	$parser->disableCache();
	return true;
}