<?php
/**
 * A special page for viewing open relationship requests for the current
 * logged-in user
 *
 * @file
 * @ingroup Extensions
 * @author David Pean <david.pean@gmail.com>
 * @copyright Copyright © 2007, Wikia Inc.
 * @license GPL-2.0-or-later
 */

class SpecialViewRelationshipRequests extends SpecialPage {

	/**
	 * @var string User name
	 */
	public $user_name_to;

	/**
	 * @var int 1 for friending, any other number for foeing
	 */
	public $relationship_type;

	public function __construct() {
		parent::__construct( 'ViewRelationshipRequests' );
	}

	public function doesWrites() {
		return true;
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
	 * Show this special page on Special:SpecialPages only for registered users
	 *
	 * @return bool
	 */
	function isListed() {
		return (bool)$this->getUser()->isRegistered();
	}

	/**
	 * Show the special page
	 *
	 * @param string|null $params
	 */
	public function execute( $params ) {
		$out = $this->getOutput();
		$user = $this->getUser();
		$request = $this->getRequest();

		/**
		 * Redirect anonymous users to the login page
		 * It will automatically return them to the ViewRelationshipRequests page
		 */
		$this->requireLogin();

		$this->checkReadOnly();

		// Set the page title, robot policies, etc.
		$this->setHeaders();

		// Add CSS & JS
		$out->addModuleStyles( [
			'ext.socialprofile.userrelationship.css'
		] );
		$out->addModules( 'ext.socialprofile.userrelationship.js' );

		$rel = new UserRelationship( $user );
		$output = '';

		if ( $request->wasPosted() ) {
			if ( $_SESSION['alreadysubmitted'] == false && !$request->getInt( 'response' ) ) {
				$_SESSION['alreadysubmitted'] = true;
				$rel->addRelationshipRequest(
					$this->user_name_to,
					$this->relationship_type,
					$request->getVal( 'message' )
				);
				$output = '<br /><span class="title">' .
					$this->msg( 'ur-already-submitted' )->escaped() .
					'</span><br /><br />';
				$out->addHTML( $output );
				return;
			}

			// @todo FIXME: essentially almost the same code as in ../api/ApiRelationshipResponse.php
			$response = $request->getInt( 'response' );
			$requestId = $request->getInt( 'id' );

			// This chunk of code handles the no-JS case of accepting/rejecting requests
			if (
				$response &&
				$rel->verifyRelationshipRequest( $requestId ) == true &&
				$user->matchEditToken( $request->getVal( 'token' ) )
			) {
				$request = $rel->getRequest( $requestId );
				$actorIdFrom = $request[0]['actor_from'];
				$userFrom = User::newFromActorId( $actorIdFrom );
				$rel_type = strtolower( $request[0]['type'] );

				$rel->updateRelationshipRequestStatus( $requestId, intval( $response ) );

				$avatar = new wAvatar( $userFrom->getId(), 'l' );
				$avatar_img = $avatar->getAvatarURL();

				if ( $response == 1 ) {
					$rel->addRelationship( $requestId );
					$performedAction = 'added';
				} else {
					$performedAction = 'reject';
				}

				$output .= "<div class=\"relationship-action red-text\">
					{$avatar_img}" .
					// i18n messages used here: ur-requests-added-message-friend, ur-requests-added-message-foe
					// ur-requests-reject-message-friend, ur-requests-reject-message-foe
					$this->msg( "ur-requests-{$performedAction}-message-{$rel_type}", $userFrom->getName() )->escaped() .
					'<div class="visualClear"></div>
				</div>';

				// "Your profile" and "Main page" buttons, for consistency w/ other special pages like
				// AddRelationship, RemoveRelationship, etc.
				// @todo NoJS support...
				$output .= "<div class=\"relationship-buttons\">
						<input type=\"button\" class=\"site-button\" value=\"" . $this->msg( 'mainpage' )->escaped() . "\" size=\"20\" onclick=\"window.location='index.php?title=" . $this->msg( 'mainpage' )->inContentLanguage()->escaped() . "'\"/>
						<input type=\"button\" class=\"site-button\" value=\"" . $this->msg( 'ur-your-profile' )->escaped() . "\" size=\"20\" onclick=\"window.location='" . htmlspecialchars( $user->getUserPage()->getFullURL() ) . "'\"/>
					</div>
					<div class=\"visualClear\"></div>";

				$rel->deleteRequest( $requestId );
			}

			$out->addHTML( $output );
			return;
		} else {
			$_SESSION['alreadysubmitted'] = false;

			$out->setPageTitle( $this->msg( 'ur-requests-title' )->plain() );

			$listLookup = new RelationshipListLookup( $user );
			$requests = $listLookup->getRequestList( 0 );

			if ( $requests ) {
				foreach ( $requests as $request ) {
					$userFrom = User::newFromActorId( $request['actor_from'] );
					if ( !$userFrom ) {
						continue;
					}

					$avatar = new wAvatar( $userFrom->getId(), 'l' );
					$avatar_img = $avatar->getAvatarURL();

					if ( $request['type'] == 'Foe' ) {
						$msg = $this->msg(
							'ur-requests-message-foe',
							$userFrom->getName()
						)->parse();
					} else {
						$msg = $this->msg(
							'ur-requests-message-friend',
							$userFrom->getName()
						)->parse();
					}

					$reqId = (int)$request['id'];
					$output .= "<div class=\"relationship-action black-text\" id=\"request_action_{$reqId}\">
					  	{$avatar_img}" . $msg;
					if ( isset( $request['message'] ) && $request['message'] ) {
						$message = $out->parseAsContent( trim( $request['message'] ), false );
						$output .= '<div class="relationship-message">' . $message . '</div>';
					}
					$url = htmlspecialchars( $this->getPageTitle()->getFullURL(), ENT_QUOTES );
					$output .= '<div class="visualClear"></div>
						<div class="relationship-buttons">
						<form id="relationship-request-accept-form" action="' . $url . '" method="post">
							<input type="hidden" name="response" value="1" />
							<input type="hidden" name="id" value="' . $reqId . '" />
							<input type="hidden" name="token" value="' . htmlspecialchars( $user->getEditToken(), ENT_QUOTES ) . '" />
							<input type="submit" class="site-button" value="' . $this->msg( 'ur-accept' )->escaped() . '" data-response="1" />
						</form>
						<form id="relationship-request-reject-form" action="' . $url . '" method="post">
							<input type="hidden" name="response" value="-1" />
							<input type="hidden" name="id" value="' . $reqId . '" />
							<input type="hidden" name="token" value="' . htmlspecialchars( $user->getEditToken(), ENT_QUOTES ) . '" />
							<input type="submit" class="site-button" value="' . $this->msg( 'ur-reject' )->escaped() . '" data-response="-1" />
						</form>
						</div>
					</div>';
				}
			} else {
				$output = $this->msg( 'ur-no-requests-message' )->parse();
			}

			$out->addHTML( $output );
		}
	}
}
