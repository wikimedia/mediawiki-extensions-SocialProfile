<?php

class PopulateAwards extends UnlistedSpecialPage {

	function __construct(){
		parent::__construct('PopulateAwards');
	}

	/**
	 * Show the special page
	 *
	 * @param $gift_category Mixed: parameter passed to the page or null
	 */
	function execute( $gift_category ){
		global $wgUser, $wgOut, $wgMemc; 
		$dbr = wfGetDB( DB_MASTER );

		if( !in_array('staff', ($wgUser->getGroups()) ) ){
			$wgOut->errorpage( 'error', 'badaccess' );
			return false;
		}
 
		global $wgUserLevels;
		$wgUserLevels = "";

		$g = new SystemGifts();
		$g->update_system_gifts();
	}
}