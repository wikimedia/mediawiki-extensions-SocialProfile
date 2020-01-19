-- Postgres version

CREATE TABLE user_gift (
  ug_id              SERIAL       PRIMARY KEY,
  ug_gift_id         INTEGER      NOT NULL  DEFAULT 0,
  ug_actor_to        INTEGER      NOT NULL  DEFAULT 0,
  ug_actor_from      INTEGER      NOT NULL  DEFAULT 0,
  ug_status          INTEGER                DEFAULT 1,
  ug_type            INTEGER,
  ug_message         TEXT,
  ug_date            TIMESTAMPTZ  NOT NULL  DEFAULT now()
);

CREATE INDEX social_profile_ug_actor_from ON user_gift(ug_actor_from);
CREATE INDEX social_profile_ug_actor_to ON user_gift(ug_actor_to);
