CREATE TABLE IF NOT EXISTS `site_settings` (
  `setting_key` varchar(80) NOT NULL,
  `setting_value` text NOT NULL,
  PRIMARY KEY (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `site_settings` (`setting_key`, `setting_value`) VALUES
  ('reputation_daily_limit', '1'),
  ('reputation_signup_value', '1')
ON DUPLICATE KEY UPDATE `setting_value` = `setting_value`;

CREATE TABLE IF NOT EXISTS `simpaty` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `touserid` int(10) unsigned NOT NULL DEFAULT '0',
  `fromuserid` int(10) unsigned NOT NULL DEFAULT '0',
  `fromusername` varchar(40) NOT NULL DEFAULT '',
  `bad` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `good` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `type` varchar(60) NOT NULL DEFAULT '',
  `respect_time` datetime NULL DEFAULT NULL,
  `description` text NOT NULL,
  PRIMARY KEY (`id`),
  KEY `touserid` (`touserid`),
  KEY `fromuserid` (`fromuserid`),
  KEY `fromusername` (`fromusername`),
  KEY `respect_time` (`respect_time`),
  KEY `profile_wall` (`touserid`,`respect_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `users`
  ALTER `simpaty` SET DEFAULT 1;
