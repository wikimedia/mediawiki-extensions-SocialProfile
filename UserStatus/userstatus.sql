CREATE TABLE /*_*/user_status (
  -- Unique status ID number
  `us_id` int(11) NOT NULL  DEFAULT 0 PRIMARY KEY,
  -- ID number of the user who wrote this status update
  `us_user_id` int(11) NOT NULL default '0',
  -- Timestamp of the status update
  `us_timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  -- Name of the person who wrote the status update
  `us_user_text` varchar(255) NOT NULL default '',
  -- The text of the status update
  `us_status` varchar(140) NOT NULL default ''
)/*$wgDBTableOptions*/;

CREATE TABLE /*_*/user_status_history (
  `ush_id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `ush_user_id` int(11) NOT NULL default '0',
  `ush_timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `ush_status` varchar(140) NOT NULL default ''
)/*$wgDBTableOptions*/;