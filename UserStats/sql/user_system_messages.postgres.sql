-- Postgres version

CREATE TABLE user_system_messages (
  um_id         SERIAL       PRIMARY KEY,
  um_actor      INTEGER      NOT NULL  DEFAULT 0,
  um_message    TEXT         NOT NULL  DEFAULT '',
  um_type       INTEGER                DEFAULT 0,
  um_date       TIMESTAMPTZ  NOT NULL  DEFAULT now()
);

CREATE INDEX social_profile_um_actor ON user_system_messages(um_actor);
