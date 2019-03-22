<?php

$cfg = require __DIR__ . '/../vendor/mediawiki/mediawiki-phan-config/src/config.php';

$cfg['directory_list'] = array_merge(
	$cfg['directory_list'],
	[
		'.', // our dir
		// We don't actually *depend on* Echo, we merely *support* it, but phan cannot tell the difference.
		'../../extensions/Echo',
	]
);

$cfg['exclude_analysis_directory_list'] = array_merge(
	$cfg['exclude_analysis_directory_list'],
	[
		'vendor', // dear gods just no
		'../../extensions/Echo',
	]
);

return $cfg;
