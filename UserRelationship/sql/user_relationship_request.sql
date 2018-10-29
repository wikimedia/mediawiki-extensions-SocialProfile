--
-- Table structure for table `user_relationship_request`
--

CREATE TABLE IF NOT EXISTS /*_*/user_relationship_request (
  `ur_id` int(11) PRIMARY KEY auto_increment,
  `ur_user_id_from` int(5) unsigned NOT NULL default '0',
  `ur_user_name_from` varchar(255) NOT NULL default '',
  `ur_user_id_to` int(5) unsigned NOT NULL default '0',
  `ur_user_name_to` varchar(255) NOT NULL default '',
  `ur_status` int(2) default '0',
  `ur_type` int(2) default NULL,
  `ur_message` varchar(255) default NULL,
  `ur_date` datetime default NULL
) /*$wgDBTableOptions*/;
CREATE INDEX /*i*/ur_user_id_from ON /*_*/user_relationship_request (`ur_user_id_from`);
CREATE INDEX /*i*/ur_user_id_to   ON /*_*/user_relationship_request (`ur_user_id_to`);
