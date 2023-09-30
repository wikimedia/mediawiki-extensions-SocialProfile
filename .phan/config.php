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

// Suppress certain issue types.
$cfg['suppress_issue_types'] = array_merge( $cfg['suppress_issue_types'], [
	# These are caused by the no-JS forms *intentionally* using null instead of an empty string
	# as the submit button "text", which forces browsers to render a default text; an empty
	# string would indeed be rendered as an empty button, i.e. <input type="submit" value="" />
	# and that is NOT what is wanted here!
	# Current known offenders:
	# 1. SystemGifts/includes/specials/SpecialPopulateAwards.php
	# 2. UserProfile/includes/specials/SpecialPopulateExistingUsersProfiles.php
	# 3. UserStats/includes/specials/GenerateTopUsersReport.php
	# 4. UserStats/includes/specials/SpecialUpdateEditCounts.php
	'PhanTypeMismatchArgumentProbablyReal',
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
