CREATE TABLE IF NOT EXISTS /*_*/user_system_gift (
  `sg_id` int(11) NOT NULL PRIMARY KEY auto_increment,
  `sg_gift_id` int(5) unsigned NOT NULL default '0',
  `sg_actor` bigint unsigned NOT NULL,
  `sg_status` int(2) default '1',
  `sg_date` datetime default NULL
) /*$wgDBTableOptions*/;

CREATE INDEX /*i*/sg_actor    ON /*_*/user_system_gift (`sg_actor`);
CREATE INDEX /*i*/sg_gift_id    ON /*_*/user_system_gift (`sg_gift_id`);
