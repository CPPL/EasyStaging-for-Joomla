# Re-add 1.1.0 update SQL due to missing semi-colon in first statement causing update to fail.

DROP TABLE IF EXISTS `#__easystaging_upshot`;

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

CREATE TABLE IF NOT EXISTS `#__easystaging_rsyncs` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `plan_id` int(11) unsigned DEFAULT NULL,
  `direction` tinyint(1) DEFAULT NULL,
  `label` varchar(255) DEFAULT NULL,
  `source_path` varchar(1024) DEFAULT NULL,
  `target_path` varchar(1024) DEFAULT NULL,
  `last` datetime DEFAULT '0000-00-00 00:00:00',
  `last_result` tinyint(1) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
