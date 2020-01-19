-- Postgres version

CREATE TABLE user_relationship (
  r_id                  SERIAL       PRIMARY KEY,
  r_actor               INTEGER      NOT NULL DEFAULT 0,
  r_actor_relation      INTEGER      NOT NULL DEFAULT 0,
  r_type                INTEGER,
  r_date                TIMESTAMPTZ  NOT NULL DEFAULT now()
);

CREATE INDEX social_profile_r_actor ON user_relationship(r_actor);
CREATE INDEX social_profile_r_actor_relation ON user_relationship(r_actor_relation);
