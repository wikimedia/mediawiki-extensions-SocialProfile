CREATE TABLE IF NOT EXISTS /*_*/gift (
  `gift_id` int(11) UNSIGNED PRIMARY KEY auto_increment,
  `gift_access` int(5) NOT NULL default '0',
  `gift_creator_actor` bigint unsigned NOT NULL,
  `gift_name` varchar(255) NOT NULL default '',
  `gift_description` text,
  `gift_given_count` int(5) default '0',
  `gift_createdate` datetime default NULL
) /*$wgDBTableOptions*/;
