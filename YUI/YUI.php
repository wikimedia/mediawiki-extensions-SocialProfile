<?php
$wgExtensionFunctions[] = "wfYUI";

$wgExtensionCredits['other'][] = array(
        'name' => 'Yahoo! User Interface Library',
        'author' => 'Yahoo! Inc.',
        'url' => 'http://www.mediawiki.org/wiki/Extension:SocialProfile',
        'description' => 'A set of utilities and controls, written in JavaScript',
);

function wfYUI() {
	global $wgOut;
	$wgOut->addScript("<script type=\"text/javascript\" src=\"/extensions/SocialProfile/YUI/yui.js\"></script>\n");
}
