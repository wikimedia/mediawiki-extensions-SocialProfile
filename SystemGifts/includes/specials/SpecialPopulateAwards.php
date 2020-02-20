<?php
/**
 * Special:PopulateAwards -- a special page that wraps around
 * SystemGifts#updateSystemGifts()
 *
 * @file
 * @ingroup Extensions
 */

class PopulateAwards extends UnlistedSpecialPage {

	public function __construct() {
		parent::__construct( 'PopulateAwards'/*class*/, 'awardsmanage' /*restriction*/ );
	}

	/**
	 * Show the special page
	 *
	 * @param string|null $gift_category
	 */
	public function execute( $gift_category ) {
		global $wgUserLevels;

		$out = $this->getOutput();
		$request = $this->getRequest();
		$user = $this->getUser();

		// make sure user has the correct permissions
		$this->checkPermissions();

		// Show a message if the database is in read-only mode
		$this->checkReadOnly();

		// If user is blocked, s/he doesn't need to access this page
		if ( $user->isBlocked() ) {
			throw new UserBlockedError( $user->getBlock() );
		}

		// Set the robot policies, etc.
		$out->setArticleRelated( false );
		$out->setRobotPolicy( 'noindex,nofollow' );

		if ( $request->wasPosted() && $user->matchEditToken( $request->getVal( 'wpEditToken' ) ) ) {
			$wgUserLevels = '';

			$g = new SystemGifts();
			$g->updateSystemGifts();
		} else {
			$out->addHTML( $this->displayForm() );
		}
	}

	/**
	 * Render the confirmation form
	 *
	 * @return string HTML
	 */
	private function displayForm() {
		$form = '<form method="post" name="populate-awards-form" action="">';
		$form .= $this->msg( 'ga-awards-populate-confirm' )->escaped();
		$form .= '<br />';
		$form .= Html::hidden( 'wpEditToken', $this->getUser()->getEditToken() );
		// passing null as the 1st argument makes the button use the browser default text
		// (on Firefox 72 with English localization this is "Submit Query" which is good enough,
		// since MW core lacks a generic "submit" message and I don't feel like introducing
		// a new i18n msg just for this button...)
		$form .= Html::submitButton( null, [ 'name' => 'wpSubmit' ] );
		$form .= '</form>';
		return $form;
	}

}
