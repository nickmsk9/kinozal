SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS `uarch_smiles` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `userid` int unsigned NOT NULL DEFAULT '0',
  `username` varchar(40) NOT NULL DEFAULT '',
  `userclass` tinyint unsigned NOT NULL DEFAULT '0',
  `image_url` text NOT NULL,
  `active` enum('yes','no') NOT NULL DEFAULT 'yes',
  `ip` varchar(45) NOT NULL DEFAULT '',
  `added` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `active_added` (`active`, `added`),
  KEY `userid` (`userid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `users` MODIFY `avatar` varchar(500) NOT NULL DEFAULT '';
