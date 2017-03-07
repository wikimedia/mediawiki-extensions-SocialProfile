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
$wgAutoloadClasses['UserSystemGiftsHooks'] = "{$wgSystemGiftsDirectory}/UserSystemGiftsHooks.php";

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
$wgExtensionMessagesFiles['SystemGiftsAlias'] = __DIR__ . '/SystemGifts.alias.php';

// Register the CSS with ResourceLoader
$wgResourceModules['ext.socialprofile.systemgifts.css'] = array(
	'styles' => 'SystemGift.css',
	'localBasePath' => __DIR__,
	'remoteExtPath' => 'SocialProfile/SystemGifts',
	'position' => 'top'
);

$wgResourceModules['ext.socialprofile.special.removemastersystemgift.css'] = array(
	'styles' => 'SpecialRemoveMasterSystemGift.css',
	'localBasePath' => __DIR__,
	'remoteExtPath' => 'SocialProfile/SystemGifts',
	'position' => 'top'
);

$wgResourceModules['ext.socialprofile.special.systemgiftmanager.css'] = array(
	'styles' => 'SpecialSystemGiftManager.css',
	'localBasePath' => __DIR__,
	'remoteExtPath' => 'SocialProfile/SystemGifts',
	'position' => 'top'
);

$wgResourceModules['ext.socialprofile.special.systemgiftmanagerlogo.css'] = array(
	'styles' => 'SpecialSystemGiftManagerLogo.css',
	'localBasePath' => __DIR__,
	'remoteExtPath' => 'SocialProfile/SystemGifts',
	'position' => 'top'
);

$wgResourceModules['ext.socialprofile.special.topawards.css'] = array(
	'styles' => 'SpecialTopAwards.css',
	'localBasePath' => __DIR__,
	'remoteExtPath' => 'SocialProfile/SystemGifts',
	'position' => 'top'
);

$wgResourceModules['ext.socialprofile.special.viewsystemgift.css'] = array(
	'styles' => 'SpecialViewSystemGift.css',
	'localBasePath' => __DIR__,
	'remoteExtPath' => 'SocialProfile/SystemGifts',
	'position' => 'top'
);

$wgResourceModules['ext.socialprofile.special.viewsystemgifts.css'] = array(
	'styles' => 'SpecialViewSystemGifts.css',
	'localBasePath' => __DIR__,
	'remoteExtPath' => 'SocialProfile/SystemGifts',
	'position' => 'top'
);

// Echo (Notifications) stuff
$wgAutoloadClasses['EchoUserSystemGiftPresentationModel'] = "{$wgSystemGiftsDirectory}/EchoUserSystemGiftPresentationModel.php";

$wgHooks['BeforeCreateEchoEvent'][] = 'UserSystemGiftsHooks::onBeforeCreateEchoEvent';
$wgHooks['EchoGetDefaultNotifiedUsers'][] = 'UserSystemGiftsHooks::onEchoGetDefaultNotifiedUsers';
$wgHooks['EchoGetBundleRules'][] = 'UserSystemGiftsHooks::onEchoGetBundleRules';

$wgDefaultUserOptions['echo-subscriptions-web-social-award'] = true;
$wgDefaultUserOptions['echo-subscriptions-email-social-award'] = false;
