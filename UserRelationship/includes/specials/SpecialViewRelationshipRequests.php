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
	 * @var string $user_name_to User name
	 */
	public $user_name_to;

	/**
	 * @var int $relationship_type 1 for friending, any other number for foeing
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
		return (bool)$this->getUser()->isLoggedIn();
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

		// Set the page title, robot policies, etc.
		$this->setHeaders();

		// Add CSS & JS
		$out->addModuleStyles( [
			'ext.socialprofile.userrelationship.css'
		] );
		$out->addModules( 'ext.socialprofile.userrelationship.js' );

		$rel = new UserRelationship( $user );

		if ( $request->wasPosted() && $_SESSION['alreadysubmitted'] == false ) {
			$_SESSION['alreadysubmitted'] = true;
			$rel->addRelationshipRequest(
				$this->user_name_to,
				$this->relationship_type,
				$request->getVal( 'message' )
			);
			$output = '<br /><span class="title">' .
				htmlspecialchars( $this->msg( 'ur-already-submitted' )->plain() ) .
				'</span><br /><br />';
			$out->addHTML( $output );
		} else {
			$_SESSION['alreadysubmitted'] = false;
			$output = '';

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
						// FIXME: Message should be escaped, but uses raw HTML
						$msg = $this->msg(
							'ur-requests-message-foe',
							htmlspecialchars( $userFrom->getUserPage()->getFullURL() ),
							$userFrom->getName()
						)->text();
					} else {
						// FIXME: Message should be escaped, but uses raw HTML
						$msg = $this->msg(
							'ur-requests-message-friend',
							htmlspecialchars( $userFrom->getUserPage()->getFullURL() ),
							$userFrom->getName()
						)->text();
					}

					$message = $out->parseAsContent( trim( $request['message'] ), false );

					$output .= "<div class=\"relationship-action black-text\" id=\"request_action_{$request['id']}\">
					  	{$avatar_img}" . $msg;
					if ( $request['message'] ) {
						$output .= '<div class="relationship-message">' . $message . '</div>';
					}
					$output .= '<div class="visualClear"></div>
						<div class="relationship-buttons">
							<input type="button" class="site-button" value="' . htmlspecialchars( $this->msg( 'ur-accept' )->plain() ) . '" data-response="1" />
							<input type="button" class="site-button" value="' . htmlspecialchars( $this->msg( 'ur-reject' )->plain() ) . '" data-response="-1" />
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
