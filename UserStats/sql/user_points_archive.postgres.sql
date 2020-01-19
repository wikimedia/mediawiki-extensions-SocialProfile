DROP SEQUENCE IF EXISTS user_points_archive_up_id_seq CASCADE;
CREATE SEQUENCE user_points_archive_up_id_seq MINVALUE 0 START WITH 0;

CREATE TABLE user_points_archive (
  up_id            INTEGER NOT NULL DEFAULT nextval('user_points_archive_up_id_seq') PRIMARY KEY,
  up_period        SMALLINT  NOT NULL DEFAULT 0,
  up_date          TIMESTAMPTZ  NOT NULL  DEFAULT now(),
  up_actor         INTEGER NOT NULL DEFAULT 0,
  up_points        FLOAT   NOT NULL DEFAULT 0
);

CREATE INDEX upa_actor ON user_points_archive (up_actor);
