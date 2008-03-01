<?php
$wgExtensionFunctions[] = "wfYUI";

function wfYUI() {
	global $wgOut;
	$wgOut->addScript("<script type=\"text/javascript\" src=\"/extensions/SocialProfile/YUI/yui.js\"></script>\n");
}
