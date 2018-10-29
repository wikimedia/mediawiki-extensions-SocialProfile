CREATE TABLE IF NOT EXISTS /*_*/system_gift (
  `gift_id` int(11) NOT NULL PRIMARY KEY auto_increment,
  `gift_name` varchar(255) NOT NULL default '',
  `gift_description` text,
  `gift_given_count` int(11) default '0',
  `gift_category` int(11) default '0',
  `gift_threshold` int(15) default '0',
  `gift_createdate` datetime default NULL
) /*$wgDBTableOptions*/;
CREATE INDEX /*i*/giftcategoryidx  ON /*_*/system_gift (`gift_category`);
CREATE INDEX /*i*/giftthresholdidx ON /*_*/system_gift (`gift_threshold`);
