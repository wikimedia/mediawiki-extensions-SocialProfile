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
 * @license GPL-2.0-or-later
 */

class SpecialAddRelationship extends UnlistedSpecialPage {

	/**
	 * @var User $user_to The user (object) who we are friending/foeing
	 */
	public $user_to;

	/**
	 * @var int $relationship_type 1 for friending, any other number for foeing
	 */
	public $relationship_type;

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
	 * @param string|null $par Name of the user whom to remove as a friend/foe
	 */
	public function execute( $par ) {
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

		$userTitle = Title::newFromDBkey( $request->getVal( 'user', $par ) );

		if ( !$userTitle ) {
			$out->setPageTitle( $this->msg( 'ur-error-title' ) );
			$out->addWikiMsg( 'ur-add-no-user' );
			return false;
		}

		$this->user_to = User::newFromName( $userTitle->getText() );
		$this->relationship_type = $request->getInt( 'rel_type' );
		if ( !$this->relationship_type || !is_numeric( $this->relationship_type ) ) {
			$this->relationship_type = 1;
		}
		$hasRelationship = UserRelationship::getUserRelationshipByID(
			$this->user_to,
			$currentUser
		);

		if ( ( $currentUser->getActorId() == $this->user_to->getActorId() ) && $currentUser->isLoggedIn() ) {
			$out->setPageTitle( $this->msg( 'ur-error-title' )->plain() );

			$output = '<div class="relationship-error-message">' .
				$this->msg( 'ur-add-error-message-yourself' )->escaped() .
			'</div>
			<div>
				<input type="button" class="site-button" value="' . htmlspecialchars( $this->msg( 'mainpage' )->plain() ) . '" size="20" onclick=\'window.location="index.php?title="' . $this->msg( 'mainpage' )->inContentLanguage()->escaped() . '"\' />';
			if ( $currentUser->isLoggedIn() ) {
				$output .= '<input type="button" class="site-button" value="' . htmlspecialchars( $this->msg( 'ur-your-profile' )->plain() ) . '" size="20" onclick=\'window.location="' . htmlspecialchars( $currentUser->getUserPage()->getFullURL() ) . '"\' />';
			}
			$output .= '</div>';

			$out->addHTML( $output );

		} elseif ( $currentUser->isBlocked() ) {
			$out->setPageTitle( $this->msg( 'ur-error-title' )->plain() );

			$output = '<div class="relationship-error-message">' .
				htmlspecialchars( $this->msg( 'ur-add-error-message-blocked' )->plain() ) .
			'</div>
			<div>
				<input type="button" class="site-button" value="' . htmlspecialchars( $this->msg( 'mainpage' )->plain() ) . '" size="20" onclick=\'window.location="index.php?title="' . $this->msg( 'mainpage' )->inContentLanguage()->escaped() . '"\' />';
			if ( $currentUser->isLoggedIn() ) {
				$output .= '<input type="button" class="site-button" value="' . htmlspecialchars( $this->msg( 'ur-your-profile' )->plain() ) . '" size="20" onclick=\'window.location="' . htmlspecialchars( $currentUser->getUserPage()->getFullURL() ) . '"\' />';
			}
			$output .= '</div>';

			$out->addHTML( $output );
		} elseif ( $this->user_to->isAnon() ) {
			$out->setPageTitle( $this->msg( 'ur-error-title' )->plain() );

			$output = '<div class="relationship-error-message">' .
				htmlspecialchars( $this->msg( 'ur-add-error-message-no-user' )->plain() ) .
			'</div>
			<div>
				<input type="button" class="site-button" value="' . htmlspecialchars( $this->msg( 'mainpage' )->plain() ) . '" size="20" onclick=\'window.location="index.php?title="' . $this->msg( 'mainpage' )->inContentLanguage()->escaped() . '"\' />';
			if ( $currentUser->isLoggedIn() ) {
				$output .= '<input type="button" class="site-button" value="' . htmlspecialchars( $this->msg( 'ur-your-profile' )->plain() ) . '" size="20" onclick=\'window.location="' . htmlspecialchars( $currentUser->getUserPage()->getFullURL() ) . '"\' />';
			}
			$output .= '</div>';

			$out->addHTML( $output );
		} elseif ( $hasRelationship >= 1 ) {
			if ( $hasRelationship == 1 ) {
				$error = $this->msg( 'ur-add-error-message-existing-relationship-friend', $this->user_to->getName() )->parseAsBlock();
			} else {
				$error = $this->msg( 'ur-add-error-message-existing-relationship-foe', $this->user_to->getName() )->parseAsBlock();
			}

			$avatar = new wAvatar( $this->user_to->getId(), 'l' );

			$out->setPageTitle( $this->msg( 'ur-error-title' )->plain() );

			$output = "<div class=\"relationship-action\">
				{$avatar->getAvatarURL()}
				" . $error . "
				<div class=\"relationship-buttons\">
					<input type=\"button\" class=\"site-button\" value=\"" . htmlspecialchars( $this->msg( 'mainpage' )->plain() ) . "\" size=\"20\" onclick=\"window.location='index.php?title=" . $this->msg( 'mainpage' )->inContentLanguage()->escaped() . "'\"/>
					<input type=\"button\" class=\"site-button\" value=\"" . htmlspecialchars( $this->msg( 'ur-your-profile' )->plain() ) . "\" size=\"20\" onclick=\"window.location='" . htmlspecialchars( $currentUser->getUserPage()->getFullURL() ) . "'\"/>
				</div>
				<div class=\"visualClear\"></div>
			</div>";

			$out->addHTML( $output );
		} elseif ( UserRelationship::userHasRequestByID( $this->user_to, $currentUser ) == true ) {
			if ( $this->relationship_type == 1 ) {
				$error = $this->msg( 'ur-add-error-message-pending-friend-request', $this->user_to->getName() )->parseAsBlock();
			} else {
				$error = $this->msg( 'ur-add-error-message-pending-foe-request', $this->user_to->getName() )->parseAsBlock();
			}

			$avatar = new wAvatar( $this->user_to->getId(), 'l' );

			$out->setPageTitle( $this->msg( 'ur-add-error-message-pending-request-title' )->plain() );

			$output = "<div class=\"relationship-action\">
				{$avatar->getAvatarURL()}
				" . $error . "
				<div class=\"relationship-buttons\">
					<input type=\"button\" class=\"site-button\" value=\"" . htmlspecialchars( $this->msg( 'mainpage' )->plain() ) . "\" size=\"20\" onclick=\"window.location='index.php?title=" . $this->msg( 'mainpage' )->inContentLanguage()->escaped() . "'\"/>
					<input type=\"button\" class=\"site-button\" value=\"" . htmlspecialchars( $this->msg( 'ur-your-profile' )->plain() ) . "\" size=\"20\" onclick=\"window.location='" . htmlspecialchars( $currentUser->getUserPage()->getFullURL() ) . "'\"/>
				</div>
				<div class=\"visualClear\"></div>
			</div>";

			$out->addHTML( $output );
		} elseif ( UserRelationship::userHasRequestByID( $currentUser, $this->user_to ) == true ) {
			$relationship_request = SpecialPage::getTitleFor( 'ViewRelationshipRequests' );
			$out->redirect( $relationship_request->getFullURL() );
		} elseif ( $currentUser->isAnon() ) {
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
				<input type="button" class="site-button" value="' . $this->msg( 'mainpage' )->escaped() . '" size="20" onclick=\'window.location="index.php?title="' . $this->msg( 'mainpage' )->inContentLanguage()->escaped() . '"\' />
				<input type="button" class="site-button" value="' . $this->msg( 'login' )->escaped() . '" size="20" onclick="window.location=\'' . htmlspecialchars( $login_link->getFullURL() ) . '\'" />';
			$output .= '</div>';

			$out->addHTML( $output );
		} else {
			$rel = new UserRelationship( $currentUser );

			if ( $request->wasPosted() && $_SESSION['alreadysubmitted'] == false ) {
				$_SESSION['alreadysubmitted'] = true;
				$rel = $rel->addRelationshipRequest(
					$this->user_to,
					$this->relationship_type,
					$request->getVal( 'message' )
				);

				$avatar = new wAvatar( $this->user_to->getId(), 'l' );

				if ( $this->relationship_type == 1 ) {
					$out->setPageTitle( $this->msg( 'ur-add-sent-title-friend', $this->user_to->getName() )->parse() );
					$sent = $this->msg( 'ur-add-sent-message-friend', $this->user_to->getName() )->parseAsBlock();
				} else {
					$out->setPageTitle( $this->msg( 'ur-add-sent-title-foe', $this->user_to->getName() )->parse() );
					$sent = $this->msg( 'ur-add-sent-message-foe', $this->user_to->getName() )->parseAsBlock();
				}

				$output = "<div class=\"relationship-action\">
					{$avatar->getAvatarURL()}
					" . $sent . "
					<div class=\"relationship-buttons\">
						<input type=\"button\" class=\"site-button\" value=\"" . htmlspecialchars( $this->msg( 'mainpage' )->plain() ) . "\" size=\"20\" onclick=\"window.location='index.php?title=" . $this->msg( 'mainpage' )->inContentLanguage()->escaped() . "'\"/>
						<input type=\"button\" class=\"site-button\" value=\"" . htmlspecialchars( $this->msg( 'ur-your-profile' )->plain() ) . "\" size=\"20\" onclick=\"window.location='" . htmlspecialchars( $currentUser->getUserPage()->getFullURL() ) . "'\"/>
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
	 * Return an array of subpages beginning with $search that this special page will accept.
	 *
	 * @param string $search Prefix to search for
	 * @param int $limit Maximum number of results to return (usually 10)
	 * @param int $offset Number of results to skip (usually 0)
	 * @return string[] Matching subpages
	 */
	public function prefixSearchSubpages( $search, $limit, $offset ) {
		$user = User::newFromName( $search );
		if ( !$user ) {
			// No prefix suggestion for invalid user
			return [];
		}
		// Autocomplete subpage as user list - public to allow caching
		return UserNamePrefixSearch::search( 'public', $search, $limit, $offset );
	}

	/**
	 * Displays the form for adding a friend or a foe
	 *
	 * @return string HTML
	 */
	function displayForm() {
		$out = $this->getOutput();

		if ( $this->relationship_type == 1 ) {
			$out->setPageTitle( $this->msg( 'ur-add-title-friend', $this->user_to->getName() )->parse() );
			$add = $this->msg( 'ur-add-message-friend', $this->user_to->getName() )->parseAsBlock();
			$button = $this->msg( 'ur-add-button-friend' )->plain();
		} else {
			$out->setPageTitle( $this->msg( 'ur-add-title-foe', $this->user_to->getName() )->parse() );
			$add = $this->msg( 'ur-add-message-foe', $this->user_to->getName() )->parseAsBlock();
			$button = $this->msg( 'ur-add-button-foe' )->plain();
		}

		$avatar = new wAvatar( $this->user_to->getId(), 'l' );

		$form = "<form action=\"\" method=\"post\" enctype=\"multipart/form-data\" name=\"form1\">
			<div class=\"relationship-action\">
			{$avatar->getAvatarURL()}
			" . $add .
			'<div class="visualClear"></div>
			</div>
			<div class="relationship-textbox-title">' .
				htmlspecialchars( $this->msg( 'ur-add-personal-message' )->plain() ) .
			'</div>
			<textarea name="message" id="message" rows="3" cols="50"></textarea>
			<div class="relationship-buttons">
				<input type="button" class="site-button" value="' . htmlspecialchars( $button ) . '" size="20" onclick="document.form1.submit()" />
				<input type="button" class="site-button" value="' . htmlspecialchars( $this->msg( 'cancel' )->plain() ) . '" size="20" onclick="history.go(-1)" />
			</div>
		</form>';
		return $form;
	}
}
