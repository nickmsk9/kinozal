CREATE TABLE IF NOT EXISTS `torrent_trackers` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `torrentid` int(10) unsigned NOT NULL,
  `announce_url` varchar(500) NOT NULL,
  `external_info_hash` varchar(40) NOT NULL DEFAULT '',
  `is_primary` enum('yes','no') NOT NULL DEFAULT 'no',
  `seeders` int(10) unsigned NULL DEFAULT NULL,
  `leechers` int(10) unsigned NULL DEFAULT NULL,
  `completed` int(10) unsigned NULL DEFAULT NULL,
  `last_checked` datetime NULL DEFAULT NULL,
  `last_error` varchar(255) NOT NULL DEFAULT '',
  `enabled` enum('yes','no') NOT NULL DEFAULT 'yes',
  PRIMARY KEY (`id`),
  UNIQUE KEY `torrent_url` (`torrentid`, `announce_url`(191)),
  KEY `torrentid` (`torrentid`),
  KEY `enabled_checked` (`enabled`, `last_checked`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
