Requirements
=======================

Mediawiki 1.11
YUI
UserStats Package

Installation
=======================

If you are installing all extensions part of SocialProfile, there is no
need to follow the instructions below.

1) Run "user_profile.sql" on db
2) Include the following files in your LocalSettings.php

$wgUserProfileDirectory = "$IP/PATH TO USER PROFILE FILES";
$wgUserProfileScripts = "/extensions/SocialProfile/UserProfile";

$wgAutoloadClasses["UserProfile"] = "{$wgUserProfileDirectory}/UserProfileClass.php";
$wgAutoloadClasses["wAvatar"] = "{$wgUserProfileDirectory}/AvatarClass.php";

require_once( "{$wgUserProfileDirectory}/SpecialUpdateProfile.php" );
require_once( "{$wgUserProfileDirectory}/SpecialUploadAvatar.php" );
require_once( "{$wgUserProfileDirectory}/SpecialToggleUserPageType.php" );
require_once( "{$wgUserProfileDirectory}/SpecialPopulateExistingUsersProfiles.php" );
require_once( "{$wgUserProfileDirectory}/UserProfile.php" );
