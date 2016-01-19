<?php
/**
 * A special page for viewing open relationship requests for the current
 * logged-in user
 *
 * @file
 * @ingroup Extensions
 * @author David Pean <david.pean@gmail.com>
 * @copyright Copyright © 2007, Wikia Inc.
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License 2.0 or later
 */

class SpecialViewRelationshipRequests extends SpecialPage {

	/**
	 * Constructor
	 */
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
	 * Show the special page
	 *
	 * @param $params Mixed: parameter(s) passed to the page or null
	 */
	public function execute( $params ) {
		$out = $this->getOutput();
		$user = $this->getUser();

		// Set the page title, robot policies, etc.
		$this->setHeaders();

		/**
		 * Redirect anonymous users to the login page
		 * It will automatically return them to the ViewRelationshipRequests page
		 */
		if ( !$user->isLoggedIn() ) {
			$out->setPageTitle( $this->msg( 'ur-error-page-title' )->plain() );
			$login = SpecialPage::getTitleFor( 'Userlogin' );
			$out->redirect(
				$login->getFullURL( 'returnto=Special:ViewRelationshipRequests' )
			);
			return false;
		}

		// Add CSS & JS
		$out->addModuleStyles( array(
			'ext.socialprofile.clearfix',
			'ext.socialprofile.userrelationship.css'
		) );
		$out->addModules( 'ext.socialprofile.userrelationship.js' );

		$rel = new UserRelationship( $user->getName() );
		$friend_request_count = $rel->getOpenRequestCount( $user->getID(), 1 );
		$foe_request_count = $rel->getOpenRequestCount( $user->getID(), 2 );

		if ( $this->getRequest()->wasPosted() && $_SESSION['alreadysubmitted'] == false ) {
			$_SESSION['alreadysubmitted'] = true;
			$rel->addRelationshipRequest(
				$this->user_name_to,
				$this->relationship_type,
				$_POST['message']
			);
			$output = '<br /><span class="title">' .
				$this->msg( 'ur-already-submitted' )->plain() .
				'</span><br /><br />';
			$out->addHTML( $output );
		} else {
			$_SESSION['alreadysubmitted'] = false;
			$output = '';

			$out->setPageTitle( $this->msg( 'ur-requests-title' )->plain() );
			$requests = $rel->getRequestList( 0 );

			if ( $requests ) {
				foreach ( $requests as $request ) {
					$user_from = Title::makeTitle( NS_USER, $request['user_name_from'] );
					$avatar = new wAvatar( $request['user_id_from'], 'l' );
					$avatar_img = $avatar->getAvatarURL();

					if ( $request['type'] == 'Foe' ) {
						$msg = $this->msg(
							'ur-requests-message-foe',
							htmlspecialchars( $user_from->getFullURL() ),
							$request['user_name_from']
						)->text();
					} else {
						$msg = $this->msg(
							'ur-requests-message-friend',
							htmlspecialchars( $user_from->getFullURL() ),
							$request['user_name_from']
						)->text();
					}

					$message = $out->parse( trim( $request['message'] ), false );

					$output .= "<div class=\"relationship-action black-text\" id=\"request_action_{$request['id']}\">
					  	{$avatar_img}" . $msg;
					if ( $request['message'] ) {
						$output .= '<div class="relationship-message">' . $message . '</div>';
					}
					$output .= '<div class="visualClear"></div>
						<div class="relationship-buttons">
							<input type="button" class="site-button" value="' . $this->msg( 'ur-accept' )->plain() . '" data-response="1" />
							<input type="button" class="site-button" value="' . $this->msg( 'ur-reject' )->plain() . '" data-response="-1" />
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