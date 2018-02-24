--
-- Table structure for table `user_fields_privacy`
--

CREATE TABLE /*_*/user_fields_privacy (
  ufp_user_id varchar(255) NOT NULL,
  ufp_field_key varchar(255) default NULL,
  ufp_privacy varchar(255) default NULL
) /*$wgDBTableOptions*/;