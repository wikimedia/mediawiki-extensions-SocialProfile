CREATE TABLE IF NOT EXISTS /*_*/user_points_monthly (
  up_id int(11) NOT NULL PRIMARY KEY auto_increment,
  up_actor bigint unsigned NOT NULL,
  up_points float NOT NULL default 0
) /*$wgDBTableOptions*/;

CREATE INDEX /*i*/upm_actor ON /*_*/user_points_monthly (up_actor);
