<?php

$cfg = require __DIR__ . '/../vendor/mediawiki/mediawiki-phan-config/src/config.php';

$cfg['directory_list'] = array_merge(
	$cfg['directory_list'],
	[
		'.', // our dir
		// We don't actually *depend on* Echo, we merely *support* it, but phan cannot tell the difference.
		'../../extensions/Echo',
		'../../extensions/SportsTeams',
	]
);

$cfg['exclude_analysis_directory_list'] = array_merge(
	$cfg['exclude_analysis_directory_list'],
	[
		'vendor',
		'../vendor',
		'../../extensions/Echo',
		'../../extensions/SportsTeams',
	]
);

// Suppress EVERYTHING for now, we only care about running seccheck and fixing legacy code
// suckage is a gigantic task for another day...
$cfg['suppress_issue_types'] = array_merge( $cfg['suppress_issue_types'], [
	'PhanUndeclaredMethod',
	'PhanPluginDuplicateExpressionAssignmentOperation',
	'PhanTypeMismatchArgument',
	'PhanTypeMismatchArgumentProbablyReal',
	'PhanTypeMismatchArgumentNullableInternal',
	'PhanTypeMismatchProperty',
	'PhanTypeMismatchPropertyProbablyReal',
	'PhanPossiblyUndeclaredVariable',
	'PhanUndeclaredVariable',
	'PhanTypeMismatchReturn',
	'PhanTypeMismatchProperty',
	'PhanTypeMismatchArgumentInternal',
	'PhanParamTooMany',
	'PhanRedundantCondition',
	'PhanUndeclaredProperty',
	'PhanParamReqAfterOpt',
	'PhanTypeVoidAssignment',
	'PhanTypeMismatchDefault',
	'PhanSuspiciousMagicConstant',
	'PhanTypeArraySuspiciousNullable',
	'PhanUndeclaredTypeProperty',
	'PhanSuspiciousValueComparison',
	# This is happening locally because the vendor dir is not getting properly ignored:
	'PhanRedefinedClassReference',
	# False positive from main PHP setup file
	'PhanUndeclaredGlobalVariable',
	# 1) Tracked as T198154 (wfImageArchiveDir)
	# 2) false positive (MediaWiki\MediaWikiServices::getActorNormalization calls in various maintenance scripts)
	'PhanUndeclaredFunction',
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
