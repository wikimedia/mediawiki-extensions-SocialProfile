-- Postgres version

CREATE TABLE system_gift (
  gift_id           SERIAL       PRIMARY KEY,
  gift_name         TEXT         NOT NULL  DEFAULT '',
  gift_description  TEXT,
  gift_given_count  INTEGER                DEFAULT 0,
  gift_category     INTEGER                DEFAULT 0,
  gift_threshold    INTEGER                DEFAULT 0,
  gift_createdate   TIMESTAMPTZ  NOT NULL  DEFAULT now()
);
CREATE INDEX social_profile_sg_category ON system_gift(gift_category);
CREATE INDEX social_profile_sg_threshold ON system_gift(gift_threshold);
