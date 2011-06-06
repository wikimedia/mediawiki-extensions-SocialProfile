CREATE TABLE /*_*/`user_status` (
  `us_id` int(11) NOT NULL  DEFAULT 0 PRIMARY KEY,
  `us_user_id` int(11) NOT NULL default '0',
  `us_timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `us_user_text` varchar(255) NOT NULL default '',
  `us_status` varchar(140) NOT NULL default ''
)/*$wgDBTableOptions*/;