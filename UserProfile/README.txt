Requirements
=======================

Mediawiki 1.10
YUI
UserStats Package

Installation
=======================

1) Run "user_profile.sql" on db
2) Include the following files in your LocalSettings.php

$wgUserProfileDirectory = "$IP/PATH TO USER PROFILE FILES";
$wgUserProfileScripts = "/extensions/UserProfile";

$wgAutoloadClasses["UserProfile"] = "{$wgUserProfileDirectory}/UserProfileClass.php";
$wgAutoloadClasses["wAvatar"] = "{$wgUserProfileDirectory}/AvatarClass.php";

require_once( "{$wgUserProfileDirectory}/SpecialUpdateProfile.php" );
require_once( "{$wgUserProfileDirectory}/SpecialUploadAvatar.php" );
require_once( "{$wgUserProfileDirectory}/SpecialToggleUserPageType.php" );
require_once( "{$wgUserProfileDirectory}/SpecialPopulateExistingUsersProfiles.php" );
require_once( "{$wgUserProfileDirectory}/UserProfile.php" );
