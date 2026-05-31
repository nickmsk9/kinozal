SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS `radio_settings` (
  `setting_key` varchar(64) NOT NULL,
  `setting_value` mediumtext NOT NULL,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `radio_chat` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `tab` tinyint unsigned NOT NULL DEFAULT '11',
  `userid` int unsigned NOT NULL DEFAULT '0',
  `username` varchar(40) NOT NULL DEFAULT '',
  `userclass` tinyint unsigned NOT NULL DEFAULT '0',
  `text` text NOT NULL,
  `ip` varchar(45) NOT NULL DEFAULT '',
  `added` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `tab_added` (`tab`, `added`),
  KEY `userid` (`userid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

