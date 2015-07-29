<?php
/**
 * A special page for adding friends/foe requests for existing users in the wiki
 *
 * Example URL: index.php?title=Special:AddRelationship&user=Pean&rel_type=1 (for adding as friend)
 * Example URL: index.php?title=Special:AddRelationship&user=Pean&rel_type=2 (for adding as foe)
 *
 * @file
 * @ingroup Extensions
 * @author David Pean <david.pean@gmail.com>
 * @copyright Copyright © 2007, Wikia Inc.
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License 2.0 or later
 */

class SpecialAddRelationship extends UnlistedSpecialPage {

	/**
	 * Constructor -- set up the new special page
	 */
	public function __construct() {
		parent::__construct( 'AddRelationship' );
	}

	/**
	 * Group this special page under the correct header in Special:SpecialPages.
	 *
	 * @return string
	 */
	function getGroupName() {
		return 'users';
	}

	/**
	 * Show the special page
	 *
	 * @param $params Mixed: parameter(s) passed to the page or null
	 */
	public function execute( $params ) {
		$out = $this->getOutput();
		$request = $this->getRequest();
		$currentUser = $this->getUser();

		// Can't use $this->setHeaders(); here because then it'll set the page
		// title to <removerelationship> and we don't want that, we'll be
		// messing with the page title later on in the code
		$out->setArticleRelated( false );
		$out->setRobotPolicy( 'noindex,nofollow' );

		// Add CSS
		$out->addModuleStyles( 'ext.socialprofile.userrelationship.css' );

		$userTitle = Title::newFromDBkey( $request->getVal( 'user' ) );

		if ( !$userTitle ) {
			$out->setPageTitle( $this->msg( 'ur-error-title' ) );
			$out->addWikiMsg( 'ur-add-no-user' );
			return false;
		}

		$user = Title::makeTitle( NS_USER, $userTitle->getText() );

		$this->user_name_to = $userTitle->getText();
		$this->user_id_to = User::idFromName( $this->user_name_to );
		$this->relationship_type = $request->getInt( 'rel_type' );
		if ( !$this->relationship_type || !is_numeric( $this->relationship_type ) ) {
			$this->relationship_type = 1;
		}
		$hasRelationship = UserRelationship::getUserRelationshipByID(
			$this->user_id_to,
			$currentUser->getID()
		);

		if ( ( $currentUser->getID() == $this->user_id_to ) && ( $currentUser->getID() != 0 ) ) {
			$out->setPageTitle( $this->msg( 'ur-error-title' )->plain() );

			$output = '<div class="relationship-error-message">' .
				$this->msg( 'ur-add-error-message-yourself' )->escaped() .
			'</div>
			<div>
				<input type="button" class="site-button" value="' . $this->msg( 'ur-main-page' )->plain() . '" size="20" onclick=\'window.location="index.php?title="' . $this->msg( 'mainpage' )->inContentLanguage()->escaped() . '"\' />';
			if ( $currentUser->isLoggedIn() ) {
				$output .= '<input type="button" class="site-button" value="' . $this->msg( 'ur-your-profile' )->plain() . '" size="20" onclick=\'window.location="' . htmlspecialchars( $currentUser->getUserPage()->getFullURL() ) . '"\' />';
			}
			$output .= '</div>';

			$out->addHTML( $output );

		} elseif ( $currentUser->isBlocked() ) {
			$out->setPageTitle( $this->msg( 'ur-error-title' )->plain() );

			$output = '<div class="relationship-error-message">' .
				$this->msg( 'ur-add-error-message-blocked' )->plain() .
			'</div>
			<div>
				<input type="button" class="site-button" value="' . $this->msg( 'ur-main-page' )->plain() . '" size="20" onclick=\'window.location="index.php?title="' . $this->msg( 'mainpage' )->inContentLanguage()->escaped() . '"\' />';
			if ( $currentUser->isLoggedIn() ) {
				$output .= '<input type="button" class="site-button" value="' . $this->msg( 'ur-your-profile' )->plain() . '" size="20" onclick=\'window.location="' . htmlspecialchars( $currentUser->getUserPage()->getFullURL() ) . '"\' />';
			}
			$output .= '</div>';

			$out->addHTML( $output );

		} elseif ( $this->user_id_to == 0 ) {
			$out->setPageTitle( $this->msg( 'ur-error-title' )->plain() );

			$output = '<div class="relationship-error-message">' .
				$this->msg( 'ur-add-error-message-no-user' )->plain() .
			'</div>
			<div>
				<input type="button" class="site-button" value="' . $this->msg( 'ur-main-page' )->plain() . '" size="20" onclick=\'window.location="index.php?title="' . $this->msg( 'mainpage' )->inContentLanguage()->escaped() . '"\' />';
			if ( $currentUser->isLoggedIn() ) {
				$output .= '<input type="button" class="site-button" value="' . $this->msg( 'ur-your-profile' )->plain() . '" size="20" onclick=\'window.location="' . htmlspecialchars( $currentUser->getUserPage()->getFullURL() ) . '"\' />';
			}
			$output .= '</div>';

			$out->addHTML( $output );

		} elseif ( $hasRelationship >= 1 ) {

			if ( $hasRelationship == 1 ) {
				$error = $this->msg( 'ur-add-error-message-existing-relationship-friend', $this->user_name_to )->parseAsBlock();
			} else {
				$error = $this->msg( 'ur-add-error-message-existing-relationship-foe', $this->user_name_to )->parseAsBlock();
			}

			$avatar = new wAvatar( $this->user_id_to, 'l' );

			$out->setPageTitle( $this->msg( 'ur-error-title' )->plain() );

			$output = "<div class=\"relationship-action\">
				{$avatar->getAvatarURL()}
				" . $error . "
				<div class=\"relationship-buttons\">
					<input type=\"button\" class=\"site-button\" value=\"" . $this->msg( 'ur-main-page' )->plain() . "\" size=\"20\" onclick=\"window.location='index.php?title=" . $this->msg( 'mainpage' )->inContentLanguage()->escaped() . "'\"/>
					<input type=\"button\" class=\"site-button\" value=\"" . $this->msg( 'ur-your-profile' )->plain() . "\" size=\"20\" onclick=\"window.location='" . htmlspecialchars( $currentUser->getUserPage()->getFullURL() ) . "'\"/>
				</div>
				<div class=\"cleared\"></div>
			</div>";

			$out->addHTML( $output );

		} elseif ( UserRelationship::userHasRequestByID( $this->user_id_to, $currentUser->getID() ) == true ) {

			if ( $this->relationship_type == 1 ) {
				$error = $this->msg( 'ur-add-error-message-pending-friend-request', $this->user_name_to )->parseAsBlock();
			} else {
				$error = $this->msg( 'ur-add-error-message-pending-foe-request', $this->user_name_to )->parseAsBlock();
			}

			$avatar = new wAvatar( $this->user_id_to, 'l' );

			$out->setPageTitle( $this->msg( 'ur-add-error-message-pending-request-title' )->plain() );

			$output = "<div class=\"relationship-action\">
				{$avatar->getAvatarURL()}
				" . $error . "
				<div class=\"relationship-buttons\">
					<input type=\"button\" class=\"site-button\" value=\"" . $this->msg( 'ur-main-page' )->plain() . "\" size=\"20\" onclick=\"window.location='index.php?title=" . $this->msg( 'mainpage' )->inContentLanguage()->escaped() . "'\"/>
					<input type=\"button\" class=\"site-button\" value=\"" . $this->msg( 'ur-your-profile' )->plain() . "\" size=\"20\" onclick=\"window.location='" . htmlspecialchars( $currentUser->getUserPage()->getFullURL() ) . "'\"/>
				</div>
				<div class=\"cleared\"></div>
			</div>";

			$out->addHTML( $output );
		} elseif ( UserRelationship::userHasRequestByID( $currentUser->getID(), $this->user_id_to ) == true ) {
			$relationship_request = SpecialPage::getTitleFor( 'ViewRelationshipRequests' );
			$out->redirect( $relationship_request->getFullURL() );
		} elseif ( $currentUser->getID() == 0 ) {
			$login_link = SpecialPage::getTitleFor( 'Userlogin' );

			if ( $this->relationship_type == 1 ) {
				$error = $this->msg( 'ur-add-error-message-not-loggedin-friend' )->escaped();
			} else {
				$error = $this->msg( 'ur-add-error-message-not-loggedin-foe' )->escaped();
			}

			$out->setPageTitle( $this->msg( 'ur-error-title' )->plain() );

			$output = '<div class="relationship-error-message">'
				. $error .
			'</div>
			<div>
				<input type="button" class="site-button" value="' . $this->msg( 'ur-main-page' )->escaped() . '" size="20" onclick=\'window.location="index.php?title="' . $this->msg( 'mainpage' )->inContentLanguage()->escaped() . '"\' />
				<input type="button" class="site-button" value="' . $this->msg( 'ur-login' )->escaped() . '" size="20" onclick="window.location=\'' . htmlspecialchars( $login_link->getFullURL() ) . '\'" />';
			$output .= '</div>';

			$out->addHTML( $output );
		} else {
			$rel = new UserRelationship( $currentUser->getName() );

			if ( $request->wasPosted() && $_SESSION['alreadysubmitted'] == false ) {
				$_SESSION['alreadysubmitted'] = true;
				$rel = $rel->addRelationshipRequest(
					$this->user_name_to,
					$this->relationship_type,
					$request->getVal( 'message' )
				);

				$avatar = new wAvatar( $this->user_id_to, 'l' );

				if ( $this->relationship_type == 1 ) {
					$out->setPageTitle( $this->msg( 'ur-add-sent-title-friend', $this->user_name_to )->parse() );
					$sent = $this->msg( 'ur-add-sent-message-friend', $this->user_name_to )->parseAsBlock();
				} else {
					$out->setPageTitle( $this->msg( 'ur-add-sent-title-foe', $this->user_name_to )->parse() );
					$sent = $this->msg( 'ur-add-sent-message-foe', $this->user_name_to )->parseAsBlock();
				}

				$output = "<div class=\"relationship-action\">
					{$avatar->getAvatarURL()}
					" . $sent . "
					<div class=\"relationship-buttons\">
						<input type=\"button\" class=\"site-button\" value=\"" . $this->msg( 'ur-main-page' )->plain() . "\" size=\"20\" onclick=\"window.location='index.php?title=" . $this->msg( 'mainpage' )->inContentLanguage()->escaped() . "'\"/>
						<input type=\"button\" class=\"site-button\" value=\"" . $this->msg( 'ur-your-profile' )->plain() . "\" size=\"20\" onclick=\"window.location='" . htmlspecialchars( $currentUser->getUserPage()->getFullURL() ) . "'\"/>
					</div>
					<div class=\"cleared\"></div>
				</div>";

				$out->addHTML( $output );
			} else {
				$_SESSION['alreadysubmitted'] = false;
				$out->addHTML( $this->displayForm() );
			}
		}
	}

	/**
	 * Displays the form for adding a friend or a foe
	 *
	 * @return $form Mixed: HTML code for the form
	 */
	function displayForm() {
		$out = $this->getOutput();

		if ( $this->relationship_type == 1 ) {
			$out->setPageTitle( $this->msg( 'ur-add-title-friend', $this->user_name_to )->parse() );
			$add = $this->msg( 'ur-add-message-friend', $this->user_name_to )->parseAsBlock();
			$button = $this->msg( 'ur-add-button-friend' )->plain();
		} else {
			$out->setPageTitle( $this->msg( 'ur-add-title-foe', $this->user_name_to )->parse() );
			$add = $this->msg( 'ur-add-message-foe', $this->user_name_to )->parseAsBlock();
			$button = $this->msg( 'ur-add-button-foe' )->plain();
		}

		$avatar = new wAvatar( $this->user_id_to, 'l' );

		$form = "<form action=\"\" method=\"post\" enctype=\"multipart/form-data\" name=\"form1\">
			<div class=\"relationship-action\">
			{$avatar->getAvatarURL()}
			" . $add .
			'<div class="cleared"></div>
			</div>
			<div class="relationship-textbox-title">' .
				$this->msg( 'ur-add-personal-message' )->plain() .
			'</div>
			<textarea name="message" id="message" rows="3" cols="50"></textarea>
			<div class="relationship-buttons">
				<input type="button" class="site-button" value="' . $button . '" size="20" onclick="document.form1.submit()" />
				<input type="button" class="site-button" value="' . $this->msg( 'ur-cancel' )->plain() . '" size="20" onclick="history.go(-1)" />
			</div>
		</form>';
		return $form;
	}
}
