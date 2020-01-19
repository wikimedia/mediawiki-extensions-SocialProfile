CREATE TABLE IF NOT EXISTS /*_*/user_points_weekly (
  up_id int(11) NOT NULL PRIMARY KEY auto_increment,
  up_actor bigint unsigned NOT NULL,
  up_points float NOT NULL default 0
) /*$wgDBTableOptions*/;

CREATE INDEX /*i*/upw_actor ON /*_*/user_points_weekly (up_actor);
