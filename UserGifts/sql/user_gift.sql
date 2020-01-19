CREATE TABLE IF NOT EXISTS /*_*/user_gift (
  `ug_id` int(11) PRIMARY KEY auto_increment,
  `ug_gift_id` int(5) unsigned NOT NULL default '0',
  `ug_actor_to` bigint unsigned NOT NULL,
  `ug_actor_from` bigint unsigned NOT NULL,
  `ug_status` int(2) default '1',
  `ug_type` int(2) default NULL,
  `ug_message` varchar(255) default NULL,
  `ug_date` datetime default NULL
) /*$wgDBTableOptions*/;

CREATE INDEX /*i*/ug_actor_from ON /*_*/user_gift (ug_actor_from);
CREATE INDEX /*i*/ug_actor_to ON /*_*/user_gift (ug_actor_to);