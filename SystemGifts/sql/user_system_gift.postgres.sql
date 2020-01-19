-- Postgres version

CREATE TABLE user_system_gift (
  sg_id        SERIAL       PRIMARY KEY,
  sg_gift_id   INTEGER      NOT NULL  DEFAULT 0,
  sg_actor     INTEGER      NOT NULL  DEFAULT 0,
  sg_status    INTEGER                DEFAULT 1,
  sg_date      TIMESTAMPTZ  NOT NULL  DEFAULT now()
);

CREATE INDEX social_profile_usg_gift_id ON user_system_gift(sg_gift_id);
CREATE INDEX social_profile_usg_actor ON user_system_gift(sg_actor);
