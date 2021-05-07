<?php
/**
 * A special page for removing existing friends/foes for the current logged in user
 *
 * Example URL: /index.php?title=Special:RemoveRelationship&user=Awrigh01
 *
 * @file
 * @ingroup Extensions
 * @author David Pean <david.pean@gmail.com>
 * @copyright Copyright © 2007, Wikia Inc.
 * @license GPL-2.0-or-later
 */

class SpecialRemoveRelationship extends UnlistedSpecialPage {

	/**
	 * @var User User (object) who we are unfriending/unfoeing
	 */
	public $user_to;

	/**
	 * @var int 1 for friending, any other number for foeing
	 */
	public $relationship_type;

	public function __construct() {
		parent::__construct( 'RemoveRelationship' );
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
	 * @param string|null $par User name of the target user (friend or foe) with whom you want to end the relationship
	 */
	public function execute( $par ) {
		$out = $this->getOutput();
		$request = $this->getRequest();
		$user = $this->getUser();

		// Can't use $this->setHeaders(); here because then it'll set the page
		// title to <removerelationship> and we don't want that, we'll be
		// messing with the page title later on in the code
		$out->setArticleRelated( false );
		$out->setRobotPolicy( 'noindex,nofollow' );

		// Add CSS
		$out->addModuleStyles( 'ext.socialprofile.userrelationship.css' );

		// Support for friendly-by-default URLs (T191157)
		$usertitle = Title::makeTitleSafe( NS_USER, $request->getVal( 'user', $par ) );
		if ( !$usertitle ) {
			$out->setPageTitle( $this->msg( 'ur-error-title' )->plain() );
			$out->addWikiMsg( 'ur-add-no-user' );
			return;
		}

		$this->user_to = User::newFromName( $usertitle->getText() );
		$this->relationship_type = UserRelationship::getUserRelationshipByID( $this->user_to, $user );

		if ( $this->relationship_type == 1 ) {
			$confirmTitle = $this->msg( 'ur-remove-relationship-title-confirm-friend', $this->user_to->getName() )->parse();
			$confirmMsg = $this->msg( 'ur-remove-relationship-message-confirm-friend', $this->user_to->getName() )->parseAsBlock();
			$error = htmlspecialchars( $this->msg( 'ur-remove-error-not-loggedin-friend' )->plain() );
			$pending = $this->msg( 'ur-remove-error-message-pending-friend-request', $this->user_to->getName() )->parse();
		} else {
			$confirmTitle = $this->msg( 'ur-remove-relationship-title-confirm-foe', $this->user_to->getName() )->parse();
			$confirmMsg = $this->msg( 'ur-remove-relationship-message-confirm-foe', $this->user_to->getName() )->parseAsBlock();
			$error = htmlspecialchars( $this->msg( 'ur-remove-error-not-loggedin-foe' )->plain() );
			$pending = $this->msg( 'ur-remove-error-message-pending-foe-request', $this->user_to->getName() )->parse();
		}

		$output = '';
		if ( $user->getActorId() == $this->user_to->getActorId() ) {
			$out->setPageTitle( $this->msg( 'ur-error-title' )->plain() );

			$output .= '<div class="relationship-error-message">' .
				htmlspecialchars( $this->msg( 'ur-remove-error-message-remove-yourself' )->plain() ) .
			'</div>
			<div>
				<input type="button" class="site-button" value="' . htmlspecialchars( $this->msg( 'mainpage' )->plain() ) . '" size="20" onclick=\'window.location="index.php?title=' . $this->msg( 'mainpage' )->inContentLanguage()->escaped() . '"\' />';
			if ( $user->isRegistered() ) {
				$output .= '<input type="button" class="site-button" value="' . htmlspecialchars( $this->msg( 'ur-your-profile' )->plain() ) . '" size="20" onclick=\'window.location="' . htmlspecialchars( $user->getUserPage()->getFullURL() ) . '"\' />';
			}
			$output .= '</div>';

			$out->addHTML( $output );
		} elseif ( $this->relationship_type == false ) {
			$out->setPageTitle( $this->msg( 'ur-error-title' )->plain() );

			$output = '<div class="relationship-error-message">' .
				$this->msg( 'ur-remove-error-message-no-relationship', $this->user_to->getName() )->parse() .
			'</div>
			<div>
				<input type="button" class="site-button" value="' . htmlspecialchars( $this->msg( 'mainpage' )->plain() ) . '" size="20" onclick=\'window.location="index.php?title="' . $this->msg( 'mainpage' )->inContentLanguage()->escaped() . '"\' />';
			if ( $user->isRegistered() ) {
				$output .= '<input type="button" class="site-button" value="' . htmlspecialchars( $this->msg( 'ur-your-profile' )->plain() ) . '" size="20" onclick=\'window.location="' . htmlspecialchars( $user->getUserPage()->getFullURL() ) . '"\' />';
			}
			$output .= '</div>';

			$out->addHTML( $output );
		} elseif ( UserRelationship::userHasRequestByID( $this->user_to, $user ) == true ) {
			$out->setPageTitle( $this->msg( 'ur-error-title' )->plain() );

			$output = '<div class="relationship-error-message">' .
				$pending .
				'</div>
			<div>
				<input type="button" class="site-button" value="' . htmlspecialchars( $this->msg( 'mainpage' )->plain() ) . '" size="20" onclick=\'window.location="index.php?title="' . $this->msg( 'mainpage' )->inContentLanguage()->escaped() . '"\' />';
			if ( $user->isRegistered() ) {
				$output .= '<input type="button" class="site-button" value="' . htmlspecialchars( $this->msg( 'ur-your-profile' )->plain() ) . '" size="20" onclick=\'window.location="' . htmlspecialchars( $user->getUserPage()->getFullURL() ) . '"\' />';
			}
			$output .= '</div>';

			$out->addHTML( $output );
		} elseif ( $user->isAnon() ) {
			$out->setPageTitle( $this->msg( 'ur-error-title' )->plain() );
			$output = '<div class="relationship-error-message">' .
				$error .
			'</div>
			<div>
				<input type="button" class="site-button" value="' . htmlspecialchars( $this->msg( 'mainpage' )->plain() ) . '" size="20" onclick=\'window.location="index.php?title="' . $this->msg( 'mainpage' )->inContentLanguage()->escaped() . '"\' />';
			if ( $user->isRegistered() ) {
				$output .= '<input type="button" class="site-button" value="' . htmlspecialchars( $this->msg( 'ur-your-profile' )->plain() ) . '" size="20" onclick=\'window.location="' . htmlspecialchars( $user->getUserPage()->getFullURL() ) . '"\' />';
			}
			$output .= '</div>';

			$out->addHTML( $output );
		} else {
			$rel = new UserRelationship( $user );
			if (
				$this->getRequest()->wasPosted() &&
				$user->matchEditToken( $request->getVal( 'wpEditToken' ) ) &&
				$_SESSION['alreadysubmitted'] == false
			) {
				$_SESSION['alreadysubmitted'] = true;
				$rel->removeRelationship( $this->user_to, $user );
				$rel->sendRelationshipRemoveEmail(
					$this->user_to,
					$this->relationship_type
				);
				$avatar = new wAvatar( $this->user_to->getId(), 'l' );

				$out->setPageTitle( $confirmTitle );

				$output = "<div class=\"relationship-action\">
					{$avatar->getAvatarURL()}" .
					$confirmMsg .
					"<div class=\"relationship-buttons\">
						<input type=\"button\" class=\"site-button\" value=\"" . htmlspecialchars( $this->msg( 'mainpage' )->plain() ) . "\" size=\"20\" onclick=\"window.location='index.php?title=" . $this->msg( 'mainpage' )->inContentLanguage()->escaped() . "'\"/>
						<input type=\"button\" class=\"site-button\" value=\"" . htmlspecialchars( $this->msg( 'ur-your-profile' )->plain() ) . "\" size=\"20\" onclick=\"window.location='" . htmlspecialchars( $user->getUserPage()->getFullURL() ) . "'\"/>
					</div>
					<div class=\"visualClear\"></div>
					</div>";

				$out->addHTML( $output );
			} else {
				$_SESSION['alreadysubmitted'] = false;
				$out->addHTML( $this->displayForm() );
			}

		}
	}

	/**
	 * Displays the form for removing a friend or a foe
	 *
	 * @return string HTML code for the form
	 */
	function displayForm() {
		$avatar = new wAvatar( $this->user_to->getId(), 'l' );

		if ( $this->relationship_type == 1 ) {
			$title = $this->msg(
				'ur-remove-relationship-title-friend',
				$this->user_to->getName()
			)->parse();
			$remove = $this->msg(
				'ur-remove-relationship-message-friend',
				$this->user_to->getName(),
				$this->msg( 'ur-remove' )->plain()
			)->parseAsBlock();
		} else {
			$title = $this->msg(
				'ur-remove-relationship-title-foe',
				$this->user_to->getName()
			)->parse();
			$remove = $this->msg(
				'ur-remove-relationship-message-foe',
				$this->user_to->getName(),
				$this->msg( 'ur-remove' )->plain()
			)->parseAsBlock();
		}

		$this->getOutput()->setPageTitle( $title );

		$form = "<form action=\"\" method=\"post\" enctype=\"multipart/form-data\" name=\"form1\">
			<div class=\"relationship-action\">
			{$avatar->getAvatarURL()}" .
			$remove .
			'<div class="relationship-buttons">
				<input type="hidden" name="user" value="' . htmlspecialchars( $this->user_to->getName() ) . '" />
				<input type="submit" class="site-button" value="' . $this->msg( 'ur-remove' )->escaped() . '" size="20" onclick="document.form1.submit()" />
				<input type="button" class="site-button" value="' . $this->msg( 'cancel' )->escaped() . '" size="20" onclick="history.go(-1)" />
			</div>
			<div class="visualClear"></div>
			</div>
			<input type="hidden" name="wpEditToken" value="' . htmlspecialchars( $this->getUser()->getEditToken(), ENT_QUOTES ) . '" />
		</form>';

		return $form;
	}
}
