<?php
if ( function_exists( 'wfLoadExtension' ) ) {
	wfLoadExtension( 'SocialProfile/UserWelcome' );
	$wgMessagesDirs['UserWelcome'] = __DIR__ . '/i18n';
	wfWarn(
		'Deprecated PHP entry point used for UserWelcome. Please use wfLoadExtension instead, ' .
		'see https://www.mediawiki.org/wiki/Extension_registration for more details.'
	);
	return;
} else {
	die( 'This version of UserWelcome requires MediaWiki 1.29+.' );
}
