CREATE TABLE /*_*/user_system_messages (
  `um_id` int(11) NOT NULL PRIMARY KEY auto_increment,
  `um_actor` bigint unsigned NOT NULL,
  `um_message` varchar(255) NOT NULL default '',
  `um_type` int(5) default '0',
  `um_date` datetime default NULL
) /*$wgDBTableOptions*/;

CREATE INDEX /*i*/um_actor ON /*_*/user_system_messages (um_actor);