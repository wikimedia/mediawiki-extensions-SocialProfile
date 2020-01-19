--
-- Table structure for table `user_relationship_request`
--

CREATE TABLE IF NOT EXISTS /*_*/user_relationship_request (
  `ur_id` int(11) PRIMARY KEY auto_increment,
  `ur_actor_from` bigint unsigned NOT NULL,
  `ur_actor_to` bigint unsigned NOT NULL,
  `ur_status` int(2) default '0',
  `ur_type` int(2) default NULL,
  `ur_message` varchar(255) default NULL,
  `ur_date` datetime default NULL
) /*$wgDBTableOptions*/;

CREATE INDEX /*i*/ur_actor_from ON /*_*/user_relationship_request (ur_actor_from);
CREATE INDEX /*i*/ur_actor_to   ON /*_*/user_relationship_request (ur_actor_to);
