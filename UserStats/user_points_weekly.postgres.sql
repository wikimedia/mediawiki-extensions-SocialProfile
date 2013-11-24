DROP SEQUENCE IF EXISTS user_points_weekly_up_id_seq CASCADE;
CREATE SEQUENCE user_points_weekly_up_id_seq MINVALUE 0 START WITH 0;

CREATE TABLE user_points_weekly (
  up_id        INT(11) NOT NULL default nextval('user_points_weekly_up_id_seq')  PRIMARY KEY,
  up_user_id   INT(11) NOT NULL default 0,
  up_user_name TEXT    NOT NULL default '',
  up_points    FLOAT   NOT NULL default 0
);

CREATE INDEX up_user_id ON user_points_weekly (up_user_id);