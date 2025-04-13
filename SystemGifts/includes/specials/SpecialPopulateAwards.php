<?php
/**
 * Special:PopulateAwards -- a special page that wraps around
 * SystemGifts#updateSystemGifts()
 *
 * @file
 * @ingroup Extensions
 */

use MediaWiki\Html\Html;

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
		$block = $user->getBlock();
		if ( $block ) {
			throw new UserBlockedError( $block );
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
		$form .= Html::submitButton( $this->msg( 'htmlform-submit' )->text(), [ 'name' => 'wpSubmit' ] );
		$form .= '</form>';
		return $form;
	}

}
