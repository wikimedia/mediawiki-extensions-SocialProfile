CREATE TABLE IF NOT EXISTS /*_*/user_points_archive (
  up_id int(11) NOT NULL PRIMARY KEY auto_increment,
  up_period int(2) NOT NULL default 0,
  up_date datetime default NULL,
  up_actor bigint unsigned NOT NULL,
  up_points float NOT NULL default 0
) /*$wgDBTableOptions*/;

CREATE INDEX /*i*/upa_actor ON /*_*/user_points_archive (up_actor);
