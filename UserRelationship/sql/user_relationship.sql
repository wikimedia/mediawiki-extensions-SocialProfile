--
-- Table structure for table `user_relationship`
--

CREATE TABLE IF NOT EXISTS /*_*/user_relationship (
  `r_id` int(11) PRIMARY KEY auto_increment,
  `r_actor` bigint unsigned NOT NULL,
  `r_actor_relation` bigint unsigned NOT NULL,
  `r_type` int(2) default NULL,
  `r_date` datetime default NULL
) /*$wgDBTableOptions*/;

CREATE INDEX /*i*/r_actor          ON /*_*/user_relationship (r_actor);
CREATE INDEX /*i*/r_actor_relation ON /*_*/user_relationship (r_actor_relation);

