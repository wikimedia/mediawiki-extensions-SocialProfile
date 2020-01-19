-- Postgres version

CREATE TABLE gift (
  gift_id                 SERIAL       PRIMARY KEY,
  gift_access             INTEGER      NOT NULL  DEFAULT 0,
  gift_creator_actor      INTEGER      NOT NULL  DEFAULT 0,
  gift_name               TEXT         NOT NULL  DEFAULT '',
  gift_description        TEXT,
  gift_given_count        INTEGER                DEFAULT 0,
  gift_createdate         TIMESTAMPTZ  NOT NULL  DEFAULT now()
);
