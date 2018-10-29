-- Postgres version

CREATE TABLE user_relationship (
  r_id                  SERIAL       PRIMARY KEY,
  r_user_id             INTEGER      NOT NULL DEFAULT 0,
  r_user_name           TEXT         NOT NULL DEFAULT '',
  r_user_id_relation    INTEGER      NOT NULL DEFAULT 0,
  r_user_name_relation  TEXT         NOT NULL DEFAULT '',
  r_type                INTEGER,
  r_date                TIMESTAMPTZ  NOT NULL DEFAULT now()
);
CREATE INDEX social_profile_ur_user_id ON user_relationship(r_user_id);
CREATE INDEX social_profile_ur_user_id_relation ON user_relationship(r_user_id_relation);
