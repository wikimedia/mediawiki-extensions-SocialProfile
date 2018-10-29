CREATE TABLE IF NOT EXISTS /*_*/user_gift (
  `ug_id` int(11) PRIMARY KEY auto_increment,
  `ug_gift_id` int(5) unsigned NOT NULL default '0',
  `ug_user_id_to` int(5) unsigned NOT NULL default '0',
  `ug_user_name_to` varchar(255) NOT NULL default '',
  `ug_user_id_from` int(5) unsigned NOT NULL default '0',
  `ug_user_name_from` varchar(255) NOT NULL default '',
  `ug_status` int(2) default '1',
  `ug_type` int(2) default NULL,
  `ug_message` varchar(255) default NULL,
  `ug_date` datetime default NULL
) /*$wgDBTableOptions*/;
CREATE INDEX /*i*/ug_user_id_from ON /*_*/user_gift (`ug_user_id_from`);
CREATE INDEX /*i*/ug_user_id_to   ON /*_*/user_gift (`ug_user_id_to`);
