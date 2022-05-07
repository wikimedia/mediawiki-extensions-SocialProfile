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

			$response = $request->getInt( 'response' );
			$requestId = $request->getInt( 'id' );

			// This chunk of code handles the no-JS case of accepting/rejecting requests
			if (
				$response &&
				$rel->verifyRelationshipRequest( $requestId ) == true &&
				$user->matchEditToken( $request->getVal( 'token' ) )
			) {
				$requestResponse = self::doAction( $rel, $requestId, $response );

				$output .= '<div class="relationship-action red-text">' .
					$requestResponse['avatar'] .
					// i18n messages used here: ur-requests-added-message-friend, ur-requests-added-message-foe
					// ur-requests-reject-message-friend, ur-requests-reject-message-foe
					$this->msg(
						"ur-requests-{$requestResponse['action']}-message-{$requestResponse['rel_type']}",
						$requestResponse['requester']
					)->escaped() .
					'<div class="visualClear"></div>
				</div>';

				// "Your profile" and "Main page" buttons, for consistency w/ other special pages like
				// AddRelationship, RemoveRelationship, etc.
				// @todo NoJS support for these two buttons
				$output .= "<div class=\"relationship-buttons\">
						<input type=\"button\" class=\"site-button\" value=\"" . $this->msg( 'mainpage' )->escaped() . "\" size=\"20\" onclick=\"window.location='index.php?title=" . $this->msg( 'mainpage' )->inContentLanguage()->escaped() . "'\"/>
						<input type=\"button\" class=\"site-button\" value=\"" . $this->msg( 'ur-your-profile' )->escaped() . "\" size=\"20\" onclick=\"window.location='" . htmlspecialchars( $user->getUserPage()->getFullURL() ) . "'\"/>
					</div>
					<div class=\"visualClear\"></div>";
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

	/**
	 * Perform an action on a relationship request: accept it and add the relationship
	 * or reject it.
	 * Either way, the relationship will be deleted before this method returns a value.
	 *
	 * For users who do _not_ have JS enabled, the PHP code in /this/ class calls this method.
	 * For the vast majority of users, who have JS enabled, this will be called by UserRelationship.js,
	 * which calls ApiRelationshipResponse.php, which in turn is just a quick shim around this function.
	 *
	 * @note This method performs no user right (etc.) checking; sanity checks should be done before!
	 *
	 * @param UserRelationship $rel UserRelationship class instance for the user who is doing stuff
	 * @param int $requestId Relationship request identifier to be acted upon
	 * @param int $response Numeric status code indicating if a request should be accepted (1) or not (any other value)
	 * @return array
	 */
	public static function doAction( $rel, $requestId, $response ) {
		$request = $rel->getRequest( $requestId );
		$actorIdFrom = $request[0]['actor_from'];
		$userFrom = User::newFromActorId( $actorIdFrom );
		$rel_type = strtolower( $request[0]['type'] );

		$rel->updateRelationshipRequestStatus( $requestId, intval( $response ) );

		$avatar = new wAvatar( $userFrom->getId(), 'l' );
		$avatar_img = $avatar->getAvatarURL();

		// If the request was accepted, add the relationship.
		if ( $response == 1 ) {
			$rel->addRelationship( $requestId );
		}

		// Build response array
		$retVal = [
			'avatar' => $avatar_img,
			// 'friend' or 'foe'
			'rel_type' => $rel_type,
			'requester' => $userFrom->getName(),
			// action that was done to the request, will be used to build i18n keys
			// in JS in ../resources/js/UserRelationship.js and in PHP in this file
			'action' => ( $response == 1 ? 'added' : 'reject' )
		];

		// Whatever action was taken, the request's getting deleted either way, that's for sure.
		$rel->deleteRequest( $requestId );

		return $retVal;
	}
}
