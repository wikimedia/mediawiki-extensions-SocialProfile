DROP SEQUENCE IF EXISTS user_profile_up_id_seq CASCADE;
CREATE SEQUENCE user_profile_up_id_seq;

ALTER TABLE user_profile ADD COLUMN up_id INTEGER NOT NULL DEFAULT nextval('user_profile_up_id_seq');
