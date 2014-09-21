<?php

if ( !defined( 'MEDIAWIKI' ) ) {
	die();
}

global $IP, $wgAutoloadClasses, $wgAvailableRights, $wgGroupPermissions,
	$wgSpecialPages;

$wgAvailableRights[] = 'awardsmanage';
$wgGroupPermissions['staff']['awardsmanage'] = true;
$wgGroupPermissions['sysop']['awardsmanage'] = true;

$wgSystemGiftsDirectory = "$IP/extensions/SocialProfile/SystemGifts";

$wgAutoloadClasses['SystemGifts'] = "{$wgSystemGiftsDirectory}/SystemGiftsClass.php";
$wgAutoloadClasses['UserSystemGifts'] = "{$wgSystemGiftsDirectory}/UserSystemGiftsClass.php";

// Special Pages
$wgAutoloadClasses['TopAwards'] = "{$wgSystemGiftsDirectory}/TopAwards.php";
$wgSpecialPages['TopAwards'] = 'TopAwards';

$wgAutoloadClasses['ViewSystemGifts'] = "{$wgSystemGiftsDirectory}/SpecialViewSystemGifts.php";
$wgSpecialPages['ViewSystemGifts'] = 'ViewSystemGifts';

$wgAutoloadClasses['ViewSystemGift'] = "{$wgSystemGiftsDirectory}/SpecialViewSystemGift.php";
$wgSpecialPages['ViewSystemGift'] = 'ViewSystemGift';

$wgAutoloadClasses['SystemGiftManager'] = "{$wgSystemGiftsDirectory}/SpecialSystemGiftManager.php";
$wgSpecialPages['SystemGiftManager'] = 'SystemGiftManager';

$wgAutoloadClasses['SystemGiftManagerLogo'] = "{$wgSystemGiftsDirectory}/SpecialSystemGiftManagerLogo.php";
$wgSpecialPages['SystemGiftManagerLogo'] = 'SystemGiftManagerLogo';

$wgAutoloadClasses['RemoveMasterSystemGift'] = "{$wgSystemGiftsDirectory}/SpecialRemoveMasterSystemGift.php";
$wgSpecialPages['RemoveMasterSystemGift'] = 'RemoveMasterSystemGift';

$wgAutoloadClasses['PopulateAwards'] = "{$wgSystemGiftsDirectory}/SpecialPopulateAwards.php";
$wgSpecialPages['PopulateAwards'] = 'PopulateAwards';

// i18n
$wgMessagesDirs['SystemGifts'] = __DIR__ . '/i18n';

// Register the CSS with ResourceLoader
$wgResourceModules['ext.socialprofile.systemgifts.css'] = array(
	'styles' => 'SystemGift.css',
	'localBasePath' => __DIR__,
	'remoteExtPath' => 'SocialProfile/SystemGifts',
	'position' => 'top'
);
