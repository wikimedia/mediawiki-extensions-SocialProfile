<?php
if ( !defined( 'MEDIAWIKI' ) ) {
	die();
}

$wgAvailableRights[] = 'giftadmin';
$wgGroupPermissions['staff']['giftadmin'] = true;
$wgGroupPermissions['sysop']['giftadmin'] = true;

$wgUserGiftsDirectory = "$IP/extensions/SocialProfile/UserGifts";

// Special Pages etc.
$wgAutoloadClasses['Gifts'] = "{$wgUserGiftsDirectory}/GiftsClass.php";
$wgAutoloadClasses['UserGifts'] = "{$wgUserGiftsDirectory}/UserGiftsClass.php";
$wgAutoloadClasses['UserGiftsHooks'] = "{$wgUserGiftsDirectory}/UserGiftsHooks.php";

$wgAutoloadClasses['GiveGift'] = "{$wgUserGiftsDirectory}/SpecialGiveGift.php";
$wgSpecialPages['GiveGift'] = 'GiveGift';

$wgAutoloadClasses['ViewGifts'] = "{$wgUserGiftsDirectory}/SpecialViewGifts.php";
$wgSpecialPages['ViewGifts'] = 'ViewGifts';

$wgAutoloadClasses['ViewGift'] = "{$wgUserGiftsDirectory}/SpecialViewGift.php";
$wgSpecialPages['ViewGift'] = 'ViewGift';

$wgAutoloadClasses['GiftManager'] = "{$wgUserGiftsDirectory}/SpecialGiftManager.php";
$wgSpecialPages['GiftManager'] = 'GiftManager';

$wgAutoloadClasses['GiftManagerLogo'] = "{$wgUserGiftsDirectory}/SpecialGiftManagerLogo.php";
$wgSpecialPages['GiftManagerLogo'] = 'GiftManagerLogo';

$wgAutoloadClasses['RemoveMasterGift'] = "{$wgUserGiftsDirectory}/SpecialRemoveMasterGift.php";
$wgSpecialPages['RemoveMasterGift'] = 'RemoveMasterGift';

$wgAutoloadClasses['RemoveGift'] = "{$wgUserGiftsDirectory}/SpecialRemoveGift.php";
$wgSpecialPages['RemoveGift'] = 'RemoveGift';

$wgMessagesDirs['UserGifts'] = __DIR__ . '/i18n';
$wgExtensionMessagesFiles['UserGiftsAlias'] = __DIR__ . '/UserGifts.alias.php';

// Register the CSS & JS with ResourceLoader
$wgResourceModules['ext.socialprofile.usergifts.css'] = array(
	'styles' => 'UserGifts.css',
	'localBasePath' => __DIR__,
	'remoteExtPath' => 'SocialProfile/UserGifts',
	'position' => 'top'
);

$wgResourceModules['ext.socialprofile.special.giftmanager.css'] = array(
	'styles' => 'SpecialGiftManager.css',
	'localBasePath' => __DIR__,
	'remoteExtPath' => 'SocialProfile/UserGifts',
);

$wgResourceModules['ext.socialprofile.special.givegift.css'] = array(
	'styles' => 'SpecialGiveGift.css',
	'localBasePath' => __DIR__,
	'remoteExtPath' => 'SocialProfile/UserGifts',
);

$wgResourceModules['ext.socialprofile.special.viewgift.css'] = array(
	'styles' => 'SpecialViewGift.css',
	'localBasePath' => __DIR__,
	'remoteExtPath' => 'SocialProfile/UserGifts',
);

$wgResourceModules['ext.socialprofile.special.viewgifts.css'] = array(
	'styles' => 'SpecialViewGifts.css',
	'localBasePath' => __DIR__,
	'remoteExtPath' => 'SocialProfile/UserGifts',
);

$wgResourceModules['ext.socialprofile.usergifts.js'] = array(
	'scripts' => 'UserGifts.js',
	'localBasePath' => __DIR__,
	'remoteExtPath' => 'SocialProfile/UserGifts',
);

// Echo (Notifications) stuff
$wgAutoloadClasses['EchoUserGiftPresentationModel'] = "{$wgUserGiftsDirectory}/EchoUserGiftPresentationModel.php";

$wgHooks['BeforeCreateEchoEvent'][] = 'UserGiftsHooks::onBeforeCreateEchoEvent';
$wgHooks['EchoGetDefaultNotifiedUsers'][] = 'UserGiftsHooks::onEchoGetDefaultNotifiedUsers';
$wgHooks['EchoGetBundleRules'][] = 'UserGiftsHooks::onEchoGetBundleRules';

$wgDefaultUserOptions['echo-subscriptions-web-social-gift'] = true;
$wgDefaultUserOptions['echo-subscriptions-email-social-gift'] = false;