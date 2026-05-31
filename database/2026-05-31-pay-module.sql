SET @kz_pay_sql = (
  SELECT IF(COUNT(*) = 0, 'ALTER TABLE `users` ADD COLUMN `pay_votes` int(10) unsigned NOT NULL DEFAULT ''0'' AFTER `bonus`', 'DO 0')
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'pay_votes'
);
PREPARE kz_pay_stmt FROM @kz_pay_sql;
EXECUTE kz_pay_stmt;
DEALLOCATE PREPARE kz_pay_stmt;

SET @kz_pay_sql = (
  SELECT IF(COUNT(*) = 0, 'ALTER TABLE `users` ADD COLUMN `pay_donor_until` datetime NULL DEFAULT NULL AFTER `donor`', 'DO 0')
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'pay_donor_until'
);
PREPARE kz_pay_stmt FROM @kz_pay_sql;
EXECUTE kz_pay_stmt;
DEALLOCATE PREPARE kz_pay_stmt;

SET @kz_pay_sql = (
  SELECT IF(COUNT(*) = 0, 'ALTER TABLE `users` ADD COLUMN `pay_vip_until` datetime NULL DEFAULT NULL AFTER `pay_donor_until`', 'DO 0')
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'pay_vip_until'
);
PREPARE kz_pay_stmt FROM @kz_pay_sql;
EXECUTE kz_pay_stmt;
DEALLOCATE PREPARE kz_pay_stmt;

CREATE TABLE IF NOT EXISTS `pay_settings` (
  `setting_key` varchar(80) NOT NULL,
  `setting_value` text NOT NULL,
  PRIMARY KEY (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `pay_transactions` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `userid` int(10) unsigned NOT NULL DEFAULT '0',
  `username` varchar(40) NOT NULL DEFAULT '',
  `operation` varchar(40) NOT NULL DEFAULT '',
  `bonus_delta` decimal(10,2) NOT NULL DEFAULT '0.00',
  `votes_delta` int(10) NOT NULL DEFAULT '0',
  `uploaded_delta` bigint(20) NOT NULL DEFAULT '0',
  `details` text NOT NULL,
  `ip` varchar(45) NOT NULL DEFAULT '',
  `created_at` datetime NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `userid_created` (`userid`,`created_at`),
  KEY `operation_created` (`operation`,`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `pay_wishes` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `userid` int(10) unsigned NOT NULL DEFAULT '0',
  `username` varchar(40) NOT NULL DEFAULT '',
  `userclass` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `text` text NOT NULL,
  `cost_votes` int(10) unsigned NOT NULL DEFAULT '0',
  `active` enum('yes','no') NOT NULL DEFAULT 'yes',
  `added` datetime NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `active_added` (`active`,`added`),
  KEY `userid` (`userid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `pay_chat` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `tab` tinyint(3) unsigned NOT NULL DEFAULT '1',
  `userid` int(10) unsigned NOT NULL DEFAULT '0',
  `username` varchar(40) NOT NULL DEFAULT '',
  `userclass` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `text` text NOT NULL,
  `ip` varchar(45) NOT NULL DEFAULT '',
  `visible` enum('yes','no') NOT NULL DEFAULT 'yes',
  `added` datetime NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `tab_added` (`tab`,`added`),
  KEY `userid` (`userid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `pay_settings` (`setting_key`, `setting_value`) VALUES
  ('exchange_options', '25:1:25 бонусов - получить 1 голос\n100:5:100 бонусов - получить 5 голосов\n180:10:180 бонусов - получить 10 голосов\n350:25:350 бонусов - получить 25 голосов'),
  ('donor_cost', '75'),
  ('wish_cost', '5'),
  ('reset_counter_cost', '5'),
  ('delete_history_cost', '5'),
  ('vip_cost', '1500'),
  ('vip_enabled', '0'),
  ('reputation_vote_cost', '1'),
  ('home_block_enabled', '1'),
  ('chat_enabled', '1')
ON DUPLICATE KEY UPDATE `setting_value` = `setting_value`;

INSERT INTO `orbital_blocks` (`bkey`, `title`, `content`, `bposition`, `weight`, `active`, `time`, `blockfile`, `view`, `expire`, `action`, `which`, `allow_hide`)
SELECT '', 'Меценаты', '', 'c', 100, 1, '0', 'block-pay.php', 1, '0', 'd', 'ihome,', 'yes'
WHERE NOT EXISTS (SELECT 1 FROM `orbital_blocks` WHERE `blockfile` = 'block-pay.php');
