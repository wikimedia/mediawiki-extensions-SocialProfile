<?php

class SpecialUserStatus extends UnlistedSpecialPage {

	/**
	 * Constructor
	 */
	public function __construct() {
		global $wgOut, $wgScriptPath;
		
		parent::__construct( 'UserStatus' );
		$wgOut->addScriptFile( $wgScriptPath . '/extensions/SocialProfile/UserStatus/UserStatus.js' );
	}

	public function execute( $params ) {
		global $wgOut;
		
		$output = "Enter username: <input type=\"text\"  id=\"us-name-input\"> ";
		$output .= "<input type=\"button\" value=\"Find\" onclick=\"javascript:UserStatus.specialGetHistory();\">";
		$output .= "<div id=\"us-special\"> </div>";
		$wgOut->addHTML($output);
		return;
	}
	
}
