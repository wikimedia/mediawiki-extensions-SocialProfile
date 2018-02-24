<?php
/**
 * Special:PopulateAwards -- basically just a special page that calls
 * SystemGifts' update_system_gifts() function and does nothing else
 *
 * @file
 * @ingroup Extensions
 */

class PopulateAwards extends UnlistedSpecialPage {

	/**
	 * Constructor -- set up the new special page
	 */
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
		$g->update_system_gifts();
	}
}
