
-- Postgres version

CREATE TABLE user_system_gift (
  sg_id        SERIAL   NOT NULL  PRIMARY KEY,
  sg_gift_id   INTEGER  NOT NULL  DEFAULT 0,
  sg_user_id   INTEGER  NOT NULL  DEFAULT 0,
  sg_user_name TEXT     NOT NULL  DEFAULT '',
  sg_status    INTEGER            DEFAULT 1,
  sg_date      DATE     NOT NULL  DEFAULT now()
);
CREATE INDEX sg_gift_id ON user_system_gift(sg_gift_id);
CREATE INDEX sg_user_id ON user_system_gift(sg_user_id);

CREATE TABLE system_gift (
  gift_id           SERIAL  NOT NULL  PRIMARY KEY,
  gift_name         TEXT    NOT NULL  DEFAULT '',
  gift_description  TEXT,
  gift_given_count  INTEGER           DEFAULT 0,
  gift_category     INTEGER           DEFAULT 0,
  gift_threshold    INTEGER           DEFAULT 0,
  gift_createdate   DATE    NOT NULL  DEFAULT now()
);
CREATE INDEX system_gift_category ON system_gift(gift_category);
CREATE INDEX system_gift_threshold ON system_gift(gift_threshold);
