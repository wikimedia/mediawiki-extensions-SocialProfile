<?php

$cfg = require __DIR__ . '/../vendor/mediawiki/mediawiki-phan-config/src/config.php';

$cfg['directory_list'] = array_merge(
	$cfg['directory_list'],
	[
		'.', // our dir
		// We don't actually *depend on* Echo, we merely *support* it, but phan cannot tell the difference.
		'../../extensions/Echo',
		'../../extensions/SpamRegex',
		'../../extensions/SportsTeams',
	]
);

$cfg['exclude_analysis_directory_list'] = array_merge(
	$cfg['exclude_analysis_directory_list'],
	[
		'vendor',
		'../vendor',
		'../../extensions/Echo',
		'../../extensions/SpamRegex',
		'../../extensions/SportsTeams',
	]
);

// Ignored to allow upgrading Phan, to be fixed later.
$cfg['suppress_issue_types'][] = 'MediaWikiNoBaseException';
$cfg['suppress_issue_types'][] = 'MediaWikiNoEmptyIfDefined';
$cfg['suppress_issue_types'][] = 'MediaWikiNoIssetIfDefined';
$cfg['suppress_issue_types'][] = 'PhanThrowTypeAbsent';

// Suppress certain issue types.
$cfg['suppress_issue_types'] = array_merge( $cfg['suppress_issue_types'], [
	# Only 1 instance of this, in UserSystemMessage in UserStats/
	'PhanParamReqAfterOpt',
	# Only 2 instances of this, in TopFansByStat.php and TopUsersListLookup.php
	# in UserStats/
	'PhanTypeVoidAssignment',
	# This is happening because the vendor dir is not getting properly ignored:
	'PhanRedefinedClassReference',
	# Tracked as T183072 (sorta anyway)
	'PhanParamSignatureMismatch',
	# False positive, NS_FANTAG is defined when FanBoxes is defined and it's only called in such a case
	'PhanUndeclaredConstant',
	# Another FanBoxes-related false positive
	'PhanUndeclaredClassMethod',
	# This is just legit noise:
	'PhanUndeclaredVariableDim',
] );

return $cfg;
