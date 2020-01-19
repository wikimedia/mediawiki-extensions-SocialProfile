DROP SEQUENCE IF EXISTS user_points_weekly_up_id_seq CASCADE;
CREATE SEQUENCE user_points_weekly_up_id_seq MINVALUE 0 START WITH 0;

CREATE TABLE user_points_weekly (
  up_id        INTEGER NOT NULL default nextval('user_points_weekly_up_id_seq')  PRIMARY KEY,
  up_actor     INTEGER NOT NULL default 0,
  up_points    FLOAT   NOT NULL default 0
);

CREATE INDEX upw_actor ON user_points_weekly (up_actor);
