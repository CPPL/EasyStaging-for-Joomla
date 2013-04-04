CREATE TABLE IF NOT EXISTS `#__easystaging_plans` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `asset_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'FK to the #__assets table.',
  `access` int(10) unsigned NOT NULL DEFAULT '0',
  `published` tinyint(1) NOT NULL,
  `checked_out` int(10) NOT NULL,
  `checked_out_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `modified_by` int(10) NOT NULL,
  `created_by` int(10) NOT NULL,
  `modified` datetime NOT NULL,
  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `publish_up` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `publish_down` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `last_run` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `#__easystaging_sites` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `plan_id` int(11) NOT NULL,
  `type` tinyint(1) NOT NULL,
  `site_name` varchar(255) NOT NULL DEFAULT '',
  `site_url` varchar(512) NOT NULL DEFAULT '',
  `take_site_offline` bit(1) NOT NULL,
  `site_path` varchar(1024) NOT NULL DEFAULT '',
  `database_name` varchar(255) NOT NULL DEFAULT '',
  `database_user` varchar(255) NOT NULL DEFAULT '',
  `database_password` varchar(255) NOT NULL DEFAULT '',
  `database_host` varchar(255) NOT NULL DEFAULT '',
  `database_table_prefix` varchar(16) NOT NULL DEFAULT '',
  `rsync_options` varchar(255) NOT NULL DEFAULT '-avr',
  `file_exclusions` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `#__easystaging_tables` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `plan_id` int(11) unsigned NOT NULL,
  `tablename` varchar(128) NOT NULL DEFAULT '',
  `action` int(11) NOT NULL,
  `last` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `lastresult` tinyint(1) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE `#__easystaging_steps` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `runticket` char(128) NOT NULL DEFAULT '',
  `action_type` int(11) DEFAULT NULL,
  `action` varchar(1024) DEFAULT NULL,
  `result` tinyint(1) DEFAULT NULL,
  `result_text` text,
  `completed` datetime DEFAULT NULL,
  `reported` int(1) DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `runticket` (`runticket`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
