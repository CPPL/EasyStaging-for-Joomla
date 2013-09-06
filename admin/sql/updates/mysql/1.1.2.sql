# Adds Steps table for those upgrading from early beta's of 1.1.0 without going through 1.1.0 stable.

CREATE TABLE IF NOT EXISTS `#__easystaging_steps` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `runticket` char(128) NOT NULL DEFAULT '',
  `action_type` int(11) DEFAULT NULL,
  `action` mediumtext,
  `state` tinyint(1) DEFAULT NULL,
  `result_text` text,
  `completed` datetime DEFAULT NULL,
  `reported` int(1) DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `runticket` (`runticket`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
