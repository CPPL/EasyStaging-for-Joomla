CREATE TABLE IF NOT EXISTS `#__easystaging` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) DEFAULT NULL,
  `description` text,
  `published` tinyint(1) DEFAULT NULL,
  `checked_out` int(10) DEFAULT NULL,
  `checked_out_time` datetime DEFAULT NULL,
  `modified_by` int(10) DEFAULT NULL,
  `created_by` int(10) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created` datetime DEFAULT NULL,
  `publish_up` datetime DEFAULT NULL,
  `publish_down` datetime DEFAULT NULL,
  `last_run` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `#__easystaging_schedules` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `push_plan` int(11) DEFAULT NULL,
  `type` tinyint(1) DEFAULT NULL,
  `next_start` datetime DEFAULT NULL,
  `frequency_quanta` tinyint(1) DEFAULT NULL,
  `frequency_value` int(11) DEFAULT NULL,
  `last` datetime DEFAULT NULL,
  `file_result` text,
  `data_result` text,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `#__easystaging_target_sites` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `type` tinyint(1) DEFAULT NULL,
  `site_name` varchar(255) DEFAULT NULL,
  `site_url` varchar(512) DEFAULT NULL,
  `take_site_offline` bit(1) DEFAULT NULL,
  `site_path` varchar(1024) DEFAULT NULL,
  `live_database_name` varchar(1024) DEFAULT NULL,
  `live_database_user` varchar(1024) DEFAULT NULL,
  `live_database_password` varchar(1024) DEFAULT NULL,
  `live_database_host` varchar(255) DEFAULT NULL,
  `live_database_table_prefix` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `#__easystaging_target_tables` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `targetsiteid` int(11) unsigned NOT NULL,
  `targettablename` varchar(128) DEFAULT NULL,
  `action` int(11) DEFAULT NULL,
  `last` date DEFAULT NULL,
  `lastresult` tinyint(1) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `#__easystaging_targets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `push_plan` int(11) DEFAULT NULL,
  `target_site` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
