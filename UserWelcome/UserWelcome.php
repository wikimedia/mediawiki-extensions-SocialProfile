<?php
/**
 * UserWelcome extension
 * Adds <welcomeUser/> tag to display user-specific social information
 *
 * @file
 * @ingroup Extensions
 * @version 1.4.1
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
	'version' => '1.4.1',
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

$wgHooks['ParserFirstCallInit'][] = 'wfWelcomeUser';
/**
 * Register <welcomeUser /> tag with the parser
 *
 * @param $parser Parser
 * @return Boolean: true
 */
function wfWelcomeUser( &$parser ) {
	$parser->setHook( 'welcomeUser', 'getWelcomeUser' );
	return true;
}

$wgMessagesDirs['UserWelcome'] = __DIR__ . '/i18n';

function getWelcomeUser( $input, $args, $parser ) {
	$parser->disableCache();
	$output = getWelcome();

	return $output;
}

function getWelcome() {
	global $wgUser, $wgOut, $wgLang;

	// Add CSS
	$wgOut->addModuleStyles( 'ext.socialprofile.userwelcome.css' );

	// Get stats and user level
	$stats = new UserStats( $wgUser->getID(), $wgUser->getName() );
	$stats_data = $stats->getUserStats();
	$user_level = new UserLevel( $stats_data['points'] );

	// Safe links
	$level_link = Title::makeTitle( NS_HELP, wfMessage( 'mp-userlevels-link' )->inContentLanguage()->plain() );
	$avatar_link = SpecialPage::getTitleFor( 'UploadAvatar' );

	// Make an avatar
	$avatar = new wAvatar( $wgUser->getID(), 'l' );

	// Profile top images/points
	$output = '<div class="mp-welcome-logged-in">
	<h2>' . wfMessage( 'mp-welcome-logged-in', $wgUser->getName() )->parse() . '</h2>
	<div class="mp-welcome-image">
	<a href="' . htmlspecialchars( $wgUser->getUserPage()->getFullURL() ) . '" rel="nofollow">' .
		$avatar->getAvatarURL() . '</a>';
	if ( strpos( $avatar->getAvatarImage(), 'default_' ) !== false ) {
		$uploadOrEditMsg = 'mp-welcome-upload';
	} else {
		$uploadOrEditMsg = 'mp-welcome-edit';
	}
	$output .= '<div><a href="' . htmlspecialchars( $avatar_link->getFullURL() ) . '" rel="nofollow">' .
		wfMessage( $uploadOrEditMsg )->plain() .
	'</a></div>';
	$output .= '</div>';

	global $wgUserLevels;
	if ( $wgUserLevels ) {
		$output .= '<div class="mp-welcome-points">
			<div class="points-and-level">
				<div class="total-points">' .
					wfMessage(
						'mp-welcome-points',
						$wgLang->formatNum( $stats_data['points'] )
					)->parse() . '</div>
				<div class="honorific-level"><a href="' . htmlspecialchars( $level_link->getFullURL() ) .
					'">(' . $user_level->getLevelName() . ')</a></div>
			</div>
			<div class="visualClear"></div>
			<div class="needed-points">
				<br />'
				. wfMessage(
					'mp-welcome-needed-points',
					htmlspecialchars( $level_link->getFullURL() ),
					$user_level->getNextLevelName(),
					$wgLang->formatNum( $user_level->getPointsNeededToAdvance() )
				)->text() .
			'</div>
		</div>';
	}

	$output .= '<div class="visualClear"></div>';
	$output .= getRequests();
	$output .= '</div>';

	return $output;
}

function getRequests() {
	// Get requests
	$requests = getNewMessagesLink() . getRelationshipRequestLink() .
				getNewGiftLink() . getNewSystemGiftLink();

	$output = '';
	if ( $requests ) {
		$output .= '<div class="mp-requests">
			<h3>' . wfMessage( 'mp-requests-title' )->plain() . '</h3>
			<div class="mp-requests-message">
				' . wfMessage( 'mp-requests-message' )->plain() . "
			</div>
			$requests
		</div>";
	}

	return $output;
}

function getRelationshipRequestLink() {
	global $wgUser, $wgExtensionAssetsPath;

	$friend_request_count = UserRelationship::getOpenRequestCount( $wgUser->getID(), 1 );
	$foe_request_count = UserRelationship::getOpenRequestCount( $wgUser->getID(), 2 );
	$relationship_request_link = SpecialPage::getTitleFor( 'ViewRelationshipRequests' );

	$output = '';

	if ( $friend_request_count > 0 ) {
		$output .= '<p>
			<img src="' . $wgExtensionAssetsPath . '/SocialProfile/images/addedFriendIcon.png" alt="" border="0" />
			<span class="profile-on"><a href="' . htmlspecialchars( $relationship_request_link->getFullURL() ) . '" rel="nofollow">'
			. wfMessage( 'mp-request-new-friend', $friend_request_count )->parse() . '</a></span>
		</p>';
	}

	if ( $foe_request_count > 0 ) {
		$output .= '<p>
			<img src="' . $wgExtensionAssetsPath . '/SocialProfile/images/addedFoeIcon.png" alt="" border="0" />
			<span class="profile-on"><a href="' . htmlspecialchars( $relationship_request_link->getFullURL() ) . '" rel="nofollow">'
			. wfMessage( 'mp-request-new-foe', $foe_request_count )->parse() . '</a></span>
		</p>';
	}

	return $output;
}

function getNewGiftLink() {
	global $wgUser, $wgExtensionAssetsPath;

	$gift_count = UserGifts::getNewGiftCount( $wgUser->getID() );
	$gifts_title = SpecialPage::getTitleFor( 'ViewGifts' );
	$output = '';

	if ( $gift_count > 0 ) {
		$output .= '<p>
			<img src="' . $wgExtensionAssetsPath . '/SocialProfile/images/icon_package_get.gif" alt="" border="0" />
			<span class="profile-on"><a href="' . htmlspecialchars( $gifts_title->getFullURL() ) . '" rel="nofollow">'
				. wfMessage( 'mp-request-new-gift', $gift_count )->parse() .
			'</a></span>
		</p>';
	}

	return $output;
}

function getNewSystemGiftLink() {
	global $wgUser, $wgExtensionAssetsPath;

	$gift_count = UserSystemGifts::getNewSystemGiftCount( $wgUser->getID() );
	$gifts_title = SpecialPage::getTitleFor( 'ViewSystemGifts' );
	$output = '';

	if ( $gift_count > 0 ) {
		$output .= '<p>
			<img src="' . $wgExtensionAssetsPath . '/SocialProfile/images/awardIcon.png" alt="" border="0" />
			<span class="profile-on"><a href="' . htmlspecialchars( $gifts_title->getFullURL() ) . '" rel="nofollow">'
				. wfMessage( 'mp-request-new-award', $gift_count )->parse() .
			'</a></span>
		</p>';
	}

	return $output;
}

function getNewMessagesLink() {
	global $wgUser, $wgExtensionAssetsPath;

	$new_messages = UserBoard::getNewMessageCount( $wgUser->getID() );
	$output = '';

	if ( $new_messages > 0 ) {
		$board_link = SpecialPage::getTitleFor( 'UserBoard' );
		$output .= '<p>
			<img src="' . $wgExtensionAssetsPath . '/SocialProfile/images/emailIcon.gif" alt="" border="" />
			<span class="profile-on"><a href="' . htmlspecialchars( $board_link->getFullURL() ) . '" rel="nofollow">'
				. wfMessage( 'mp-request-new-message' )->plain() .
			'</a></span>
		</p>';
	}

	return $output;
}
