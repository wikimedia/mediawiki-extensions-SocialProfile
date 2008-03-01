<?php

$dir = dirname(__FILE__) . '/';

$wgExtensionMessagesFiles['SocialProfileUserBoard'] = $dir . 'UserBoard/UserBoard.i18n.php';
$wgExtensionMessagesFiles['SocialProfileUserProfile'] = $dir . 'UserProfile/UserProfile.i18n.php';
$wgExtensionMessagesFiles['SocialProfileUserRelationship'] = $dir . 'UserRelationship/UserRelationship.i18n.php';

$wgAutoloadClasses['SpecialAddRelationship'] = $dir . 'UserRelationship/SpecialAddRelationship.php';
$wgAutoloadClasses['SpecialBoardBlast'] = $dir . 'UserBoard/SpecialSendBoardBlast.php';
$wgAutoloadClasses['SpecialPopulateUserProfiles'] = $dir . 'UserProfile/SpecialPopulateExistingUsersProfiles.php';
$wgAutoloadClasses['SpecialRemoveRelationship'] = $dir . 'UserRelationship/SpecialRemoveRelationship.php';
$wgAutoloadClasses['SpecialToggleUserPage'] = $dir . 'UserProfile/SpecialToggleUserPageType.php';
$wgAutoloadClasses['SpecialUpdateProfile'] = $dir . 'UserProfile/SpecialUpdateProfile.php';
$wgAutoloadClasses['SpecialUploadAvatar'] = $dir . 'UserProfile/SpecialUploadAvatar.php';
$wgAutoloadClasses['SpecialViewRelationshipRequests'] = $dir . 'UserRelationship/SpecialViewRelationshipRequests.php';
$wgAutoloadClasses['SpecialViewRelationships'] = $dir . 'UserRelationship/SpecialViewRelationships.php';
$wgAutoloadClasses['SpecialViewUserBoard'] = $dir . 'UserBoard/SpecialUserBoard.php';

$wgAutoloadClasses["UserBoard"] = $dir . 'UserBoard/UserBoardClass.php';
$wgAutoloadClasses["UserProfile"] = $dir . 'UserProfile/UserProfileClass.php';
$wgAutoloadClasses["UserRelationship"] = $dir . 'UserRelationship/UserRelationshipClass.php';
$wgAutoloadClasses["UserStats"] = $dir . 'UserStats/UserStatsClass.php';
$wgAutoloadClasses["UserStatsTrack"] = $dir . 'UserStats/UserStatsClass.php';
$wgAutoloadClasses["wAvatar"] = $dir . 'UserProfile/AvatarClass.php';

$wgSpecialPages['AddRelationship'] = 'SpecialAddRelationship';
$wgSpecialPages['PopulateUserProfiles'] = 'SpecialPopulateUserProfiles';
$wgSpecialPages['RemoveRelationship'] = 'SpecialRemoveRelationship';
$wgSpecialPages['SendBoardBlast'] = 'SpecialBoardBlast';
$wgSpecialPages['ToggleUserPage'] = 'SpecialToggleUserPage';
$wgSpecialPages['UpdateProfile'] = 'SpecialUpdateProfile';
$wgSpecialPages['UploadAvatar'] = 'SpecialUploadAvatar';
$wgSpecialPages['UserBoard'] = 'SpecialViewUserBoard';
$wgSpecialPages['ViewRelationshipRequests'] = 'SpecialViewRelationshipRequests';
$wgSpecialPages['ViewRelationships'] = 'SpecialViewRelationships';

$wgUserProfileDisplay['board'] = true;
$wgUserProfileDisplay['foes'] = true;
$wgUserProfileDisplay['friends'] = true;

$wgUserProfileDirectory = "$IP/extensions/SocialProfile/UserProfile";

$wgUserBoardScripts = "/extensions/SocialProfile/UserBoard";
$wgUserProfileScripts = "/extensions/SocialProfile/UserProfile";
$wgUserRelationshipScripts = "/extensions/SocialProfile/UserRelationship";

require_once("$IP/extensions/SocialProfile/YUI/YUI.php");
require_once( "{$wgUserProfileDirectory}/UserProfile.php" );
