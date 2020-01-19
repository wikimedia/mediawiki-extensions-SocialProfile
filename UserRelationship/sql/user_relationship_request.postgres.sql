-- Postgres version

CREATE TABLE user_relationship_request (
  ur_id              SERIAL       PRIMARY KEY,
  ur_actor_from      INTEGER      NOT NULL  DEFAULT 0,
  ur_actor_to        INTEGER      NOT NULL  DEFAULT 0,
  ur_status          INTEGER                DEFAULT 0,
  ur_type            INTEGER,
  ur_message         TEXT,
  ur_date            TIMESTAMPTZ  NOT NULL  DEFAULT now()
);

CREATE INDEX social_profile_ur_actor_from ON user_relationship_request(ur_actor_from);
CREATE INDEX social_profile_ur_actor_to ON user_relationship_request(ur_actor_to);
