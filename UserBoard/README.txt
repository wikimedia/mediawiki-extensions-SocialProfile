Requirements
=======================
MediaWiki 1.11
YUI
UserStats package

Installation
=======================
This assumes you have copied all the neccessary files into
/extensions/SocialProfile/UserBoard.

If you are installing all extensions part of SocialProfile, there is no
need to follow the instructions below.

Please change any path references if you have installed the foler elsewhere

1) Run "user_board.sql" on db
2) Include the following files in your LocalSettings.php

$wgUserBoardScripts = "/extensions/SocialProfile/UserBoard";
require_once("$IP/extensions/SocialProfile/UserBoard/SpecialUserBoard.php");
require_once("$IP/extensions/SocialProfile/UserBoard/SpecialSendBoardBlast.php");
$wgAutoloadClasses["UserBoard"] = "$IP/extensions/SocialProfile/UserBoard/UserBoardClass.php";
$wgUserProfileDisplay['board'] = true;

*****If YUI js is not already being included******
$wgUseAjax = true;
require_once("$IP/extensions/SocialProfile/YUI/YUI.php");

*****If UserStats is not already registered******
$wgAutoloadClasses["UserStats"] = "$IP/extensions/SocialProfile/UserStats/UserStatsClass.php";

3) Register AJAX functions by editing /includes/AjaxFunctions.php

add the following line (changing path as neccessary)

global $IP;
require_once ("$IP/extensions/SocialProfile/UserBoard/UserBoard_AjaxFunctions.php");
