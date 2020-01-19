-- Postgres version

CREATE TABLE user_board (
  ub_id             SERIAL       PRIMARY KEY,
  ub_actor          INTEGER      NOT NULL  DEFAULT 0,
  ub_actor_from     INTEGER      NOT NULL  DEFAULT 0,
  ub_message        TEXT         NOT NULL,
  ub_type           INTEGER                DEFAULT 0,
  ub_date           TIMESTAMPTZ  NOT NULL  DEFAULT now()
);

CREATE INDEX social_profile_ub_actor ON user_board(ub_actor);
CREATE INDEX social_profile_ub_actor_from ON user_board(ub_actor_from);
CREATE INDEX social_profile_ub_type ON user_board(ub_type);
