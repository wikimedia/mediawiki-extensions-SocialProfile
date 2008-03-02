Requirements
=======================

Mediawiki 1.11
YUI
UserStats Package

Installation
=======================

This assumes you have copied all the neccessary files into /extensions/UserRelationship.

If you are installing all extensions part of SocialProfile, there is no
need to follow the instructions below.

Please change any path references if you have installed the foler elsewhere

1) Run "user_relationship.sql" on db
2) Include the following files in your LocalSettings.php

$wgUserRelationshipScripts = "/extensions/SocialProfile/UserRelationship";
require_once("$IP/extensions/SocialProfile/UserRelationship/SpecialAddRelationship.php");
require_once("$IP/extensions/SocialProfile/UserRelationship/SpecialRemoveRelationship.php");
require_once("$IP/extensions/SocialProfile/UserRelationship/SpecialViewRelationshipRequests.php");
require_once("$IP/extensions/SocialProfile/UserRelationship/SpecialViewRelationships.php");
$wgAutoloadClasses["UserRelationship"] = "$IP/extensions/SocialProfile/UserRelationship/UserRelationshipClass.php";
$wgUserProfileDisplay['friends'] = true;
$wgUserProfileDisplay['foes'] = true;

*****If UserStats is not already registered******
$wgAutoloadClasses["UserStats"] = "$IP/extensions/SocialProfile/UserStats/UserStatsClass.php";

*****If YUI js is not already being included******
$wgUseAjax = true;
require_once("$IP/extensions/SocialProfile/YUI/YUI.php");

3) Register AJAX functions by editing /includes/AjaxFunctions.php

add the following line (changing path as neccessary)

global $IP;
require_once ("$IP/extensions/SocialProfile/UserRelationship/Relationship_AjaxFunctions.php" );

*Please note: If you have installed this to a folder other than /$IP/extensions/SocialProfile/UserRelationship, you will also have to
update a path in "Relationship_AjaxFunctions.php"

require_once ( "$IP/extensions/SocialProfile/UserRelationship/UserRelationship.i18n.php" );
