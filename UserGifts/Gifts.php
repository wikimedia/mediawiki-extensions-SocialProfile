<?php

$wgAvailableRights[] = 'giftadmin';
$wgGroupPermissions['giftadmin']['giftadmin'] = true;
$wgGroupPermissions['staff']['giftadmin'] = true;
$wgGroupPermissions['sysop']['giftadmin'] = true;

$wgUserGiftsDirectory = "$IP/extensions/SocialProfile/UserGifts";
$wgUserGiftsScripts = "$wgScriptPath/extensions/SocialProfile/UserGifts";

//Special Pages etc.
$wgAutoloadClasses['Gifts'] = "{$wgUserGiftsDirectory}/GiftsClass.php";
$wgAutoloadClasses['UserGifts'] = "{$wgUserGiftsDirectory}/UserGiftsClass.php";

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

$wgExtensionMessagesFiles['UserGifts'] = $wgUserGiftsDirectory . '/UserGifts.i18n.php';

//Credits
$wgExtensionCredits['specialpage'][] = array(
	'name' => 'GiftManager',
	'version' => '1.0',
	'description' => 'Adds a special page to administrate available gifts and add new ones',
	'author' => 'Wikia New York Team',
	'url' => 'http://www.mediawiki.org/wiki/Extension:SocialProfile'
);

$wgExtensionCredits['specialpage'][] = array(
	'name' => 'GiftManagerLogo',
	'version' => '1.0',
	'description' => 'Adds a special page to upload new gift images',
	'author' => 'Wikia New York Team',
	'url' => 'http://www.mediawiki.org/wiki/Extension:SocialProfile'
);

$wgExtensionCredits['specialpage'][] = array(
	'name' => 'GiveGift',
	'version' => '1.0',
	'description' => 'Adds a special page to give out gifts to your friends/foes',
	'author' => 'Wikia New York Team',
	'url' => 'http://www.mediawiki.org/wiki/Extension:SocialProfile'
);

$wgExtensionCredits['specialpage'][] = array(
	'name' => 'RemoveGift',
	'version' => '1.0',
	'description' => 'Adds a special page to remove gifts',
	'author' => 'Wikia New York Team',
	'url' => 'http://www.mediawiki.org/wiki/Extension:SocialProfile'
);
$wgExtensionCredits['specialpage'][] = array(
	'name' => 'RemoveMasterGift',
	'version' => '1.0',
	'description' => 'Adds a special page to delete gifts from the database',
	'author' => 'Wikia New York Team',
	'url' => 'http://www.mediawiki.org/wiki/Extension:SocialProfile'
);
$wgExtensionCredits['specialpage'][] = array(
	'name' => 'ViewGift',
	'version' => '1.0',
	'description' => 'Adds a special page to view given gifts',
	'author' => 'Wikia New York Team',
	'url' => 'http://www.mediawiki.org/wiki/Extension:SocialProfile'
);
$wgExtensionCredits['specialpage'][] = array(
	'name' => 'ViewGifts',
	'version' => '1.0',
	'description' => 'Adds a special page to view given gifts',
	'author' => 'Wikia New York Team',
	'url' => 'http://www.mediawiki.org/wiki/Extension:SocialProfile'
);