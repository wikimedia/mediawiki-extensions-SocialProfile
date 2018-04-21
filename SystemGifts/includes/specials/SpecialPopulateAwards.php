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

		$wgUserLevels = '';

		$g = new SystemGifts();
		$g->updateSystemGifts();
	}
}
