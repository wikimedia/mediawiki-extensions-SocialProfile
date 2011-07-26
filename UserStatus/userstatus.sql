CREATE TABLE /*_*/user_status (
  -- Unique status ID number
  `us_id` int(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
  -- ID number of the user who wrote this status update
  `us_user_id` int(11) NOT NULL default '0',
  -- Timestamp of the status update
  `us_timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  -- The text of the status update
  `us_status` varchar(140) NOT NULL default ''
)/*$wgDBTableOptions*/;

CREATE TABLE /*_*/user_status_history (
  `ush_id` int(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
  `ush_user_id` int(11) NOT NULL default '0',
  `ush_timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `ush_status` varchar(140) NOT NULL default ''
)/*$wgDBTableOptions*/;
