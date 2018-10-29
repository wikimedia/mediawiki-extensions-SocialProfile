-- Postgres version

CREATE TABLE user_relationship_request (
  ur_id              SERIAL       PRIMARY KEY,
  ur_user_id_from    INTEGER      NOT NULL  DEFAULT 0,
  ur_user_name_from  TEXT         NOT NULL  DEFAULT '',
  ur_user_id_to      INTEGER      NOT NULL  DEFAULT 0,
  ur_user_name_to    TEXT         NOT NULL  DEFAULT '',
  ur_status          INTEGER                DEFAULT 0,
  ur_type            INTEGER,
  ur_message         TEXT,
  ur_date            TIMESTAMPTZ  NOT NULL  DEFAULT now()
);
CREATE INDEX social_profile_urr_user_id_from ON user_relationship_request(ur_user_id_from);
CREATE INDEX social_profile_urr_user_id_to ON user_relationship_request(ur_user_id_to);
