CREATE TABLE IF NOT EXISTS /*_*/user_system_gift (
  `sg_id` int(11) NOT NULL PRIMARY KEY auto_increment,
  `sg_gift_id` int(5) unsigned NOT NULL default '0',
  `sg_user_id` int(11) unsigned NOT NULL default '0',
  `sg_user_name` varchar(255) NOT NULL default '',
  `sg_status` int(2) default '1',
  `sg_date` datetime default NULL
) /*$wgDBTableOptions*/;
CREATE INDEX /*i*/sg_user_id    ON /*_*/user_system_gift (`sg_user_id`);
CREATE INDEX /*i*/sg_gift_id    ON /*_*/user_system_gift (`sg_gift_id`);
