--
-- Table structure for table `user_board`
--

CREATE TABLE IF NOT EXISTS /*_*/user_board (
  `ub_id` int(11) PRIMARY KEY auto_increment,
  `ub_actor` bigint unsigned NOT NULL,
  `ub_actor_from` bigint unsigned NOT NULL,
  `ub_message` text NOT NULL,
  `ub_type` int(5) default '0',
  `ub_date` datetime default NULL
) /*$wgDBTableOptions*/;

CREATE INDEX /*i*/ub_actor ON      /*_*/user_board (ub_actor);
CREATE INDEX /*i*/ub_actor_from ON /*_*/user_board (ub_actor_from);
CREATE INDEX /*i*/ub_type ON       /*_*/user_board (ub_type);
