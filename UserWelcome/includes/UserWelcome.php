<?php
/**
 * UserWelcome extension
 * Adds <welcomeUser/> tag to display user-specific social information
 *
 * @file
 * @ingroup Extensions
 * @author David Pean <david.pean@gmail.com>
 * @author Jack Phoenix
 * @link https://www.mediawiki.org/wiki/Extension:UserWelcome Documentation
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License 2.0 or later
 */

class UserWelcome {
	/**
	 * Register <welcomeUser /> tag with the parser
	 *
	 * @param Parser $parser
	 */
	public static function onParserFirstCallInit( &$parser ) {
		$parser->setHook( 'welcomeUser', array( __CLASS__, 'getWelcomeUser' ) );
	}

	public static function getWelcomeUser( $input, $args, $parser ) {
		$parser->getOutput()->updateCacheExpiry( 0 );
		$parser->getOutput()->addModuleStyles( 'ext.socialprofile.userwelcome.css' );
		// This is so stupid. The callback to onParserFirstCallInit() is
		// *always* (assumed to be) static even if you don't declare it as such.
		// So obviously using $this in a static function fails...grumble grumble.
		$uw = new UserWelcome;
		$output = $uw->getWelcome();
		return $output;
	}

	function getWelcome() {
		global $wgUser, $wgLang;

		// Get stats and user level
		$stats = new UserStats( $wgUser->getId(), $wgUser->getName() );
		$stats_data = $stats->getUserStats();
		$user_level = new UserLevel( $stats_data['points'] );

		// Safe links
		$level_link = Title::makeTitle( NS_HELP, wfMessage( 'mp-userlevels-link' )->inContentLanguage()->plain() );
		$avatar_link = SpecialPage::getTitleFor( 'UploadAvatar' );

		// Make an avatar
		$avatar = new wAvatar( $wgUser->getId(), 'l' );

		// Profile top images/points
		$output = '<div class="mp-welcome-logged-in">
		<h2>' . wfMessage( 'mp-welcome-logged-in', $wgUser->getName() )->parse() . '</h2>
		<div class="mp-welcome-image">
		<a href="' . htmlspecialchars( $wgUser->getUserPage()->getFullURL() ) . '" rel="nofollow">' .
			$avatar->getAvatarURL() . '</a>';
		if ( $avatar->isDefault() ) {
			$uploadOrEditMsg = 'mp-welcome-upload';
		} else {
			$uploadOrEditMsg = 'edit';
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
		$output .= $this->getRequests();
		$output .= '</div>';

		return $output;
	}

	function getRequests() {
		// Get requests
		$requests = $this->getNewMessagesLink() . $this->getRelationshipRequestLink() .
					$this->getNewGiftLink() . $this->getNewSystemGiftLink();

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

		$friend_request_count = UserRelationship::getOpenRequestCount( $wgUser->getId(), 1 );
		$foe_request_count = UserRelationship::getOpenRequestCount( $wgUser->getId(), 2 );
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

		$gift_count = UserGifts::getNewGiftCount( $wgUser->getId() );
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

		$gift_count = UserSystemGifts::getNewSystemGiftCount( $wgUser->getId() );
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

		$new_messages = UserBoard::getNewMessageCount( $wgUser->getId() );
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
}
