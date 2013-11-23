DROP SEQUENCE IF EXISTS user_points_archive_up_id_seq CASCADE;
CREATE SEQUENCE user_points_archive_up_id_seq MINVALUE 0 START WITH 0;

CREATE TABLE user_points_archive (
  up_id            INT(11) NOT NULL DEFAULT nextval('user_points_archive_up_id_seq') PRIMARY KEY,
  up_period        INT(2)  NOT NULL DEFAULT 0,
  up_date datetime DEFAULT NULL,
  up_user_id       INT(11) NOT NULL DEFAULT 0,
  up_user_name     TEXT    NOT NULL,
  up_points        FLOAT   NOT NULL DEFAULT 0
);

CREATE INDEX up_user_id ON user_points_archive (up_user_id);