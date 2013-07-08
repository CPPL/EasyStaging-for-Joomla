DROP TABLE `#__easystaging_upshot`

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
