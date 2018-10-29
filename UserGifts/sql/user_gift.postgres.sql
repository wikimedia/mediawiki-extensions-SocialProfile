-- Postgres version

CREATE TABLE user_gift (
  ug_id              SERIAL       PRIMARY KEY,
  ug_gift_id         INTEGER      NOT NULL  DEFAULT 0,
  ug_user_id_to      INTEGER      NOT NULL  DEFAULT 0,
  ug_user_name_to    TEXT         NOT NULL  DEFAULT '',
  ug_user_id_from    INTEGER      NOT NULL  DEFAULT 0,
  ug_user_name_from  TEXT         NOT NULL  DEFAULT '',
  ug_status          INTEGER                DEFAULT 1,
  ug_type            INTEGER,
  ug_message         TEXT,
  ug_date            TIMESTAMPTZ  NOT NULL  DEFAULT now()
);
CREATE INDEX social_profile_ug_user_id_from ON user_gift(ug_user_id_from);
CREATE INDEX social_profile_ug_user_id_to ON user_gift(ug_user_id_to);
