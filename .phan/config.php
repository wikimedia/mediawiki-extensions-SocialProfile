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
	'PhanPluginDuplicateExpressionAssignmentOperation',
	'PhanTypeMismatchArgument',
	'PhanTypeMismatchArgumentProbablyReal',
	'PhanTypeMismatchProperty',
	'PhanTypeMismatchPropertyProbablyReal',
	'PhanUndeclaredVariable',
	'PhanTypeMismatchReturn',
	'PhanTypeMismatchProperty',
	'PhanParamTooMany',
	'PhanRedundantCondition',
	'PhanParamReqAfterOpt',
	'PhanTypeVoidAssignment',
	'PhanTypeMismatchDefault',
	'PhanSuspiciousMagicConstant',
	'PhanTypeArraySuspiciousNullable',
	'PhanUndeclaredTypeProperty',
	'PhanSuspiciousValueComparison',
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
	# Temporary while phan work is ongoing
	# (I want to be able to have the few inline suppressions ready even if
	# I haven't fixed all the issues of a certain issue type)
	'UnusedPluginSuppression',
] );

return $cfg;
