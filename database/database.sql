SET NAMES utf8mb4;

#
# Structure for the `avps` table :
#

DROP TABLE IF EXISTS `avps`;

CREATE TABLE `avps` (
  `arg` varchar(20) NOT NULL default '',
  `value_s` text NOT NULL,
  `value_i` int(11) NOT NULL default '0',
  `value_u` int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (`arg`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

#
# Structure for the `bans` table :
#

DROP TABLE IF EXISTS `bans`;

CREATE TABLE `bans` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `added` datetime NULL DEFAULT NULL,
  `addedby` int(10) unsigned NOT NULL default '0',
  `comment` varchar(255) NOT NULL default '',
  `first` bigint(11) default NULL,
  `last` bigint(11) default NULL,
  PRIMARY KEY  (`id`),
  KEY `first_last` (`first`,`last`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

#
# Structure for the `blocks` table :
#

DROP TABLE IF EXISTS `blocks`;

CREATE TABLE `blocks` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `userid` int(10) unsigned NOT NULL default '0',
  `blockid` int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (`id`),
  UNIQUE KEY `userfriend` (`userid`,`blockid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

#
# Structure for the `bonus` table :
#

DROP TABLE IF EXISTS `bonus`;

CREATE TABLE `bonus` (
  `id` int(5) NOT NULL auto_increment,
  `name` varchar(50) NOT NULL default '',
  `points` decimal(7,2) NOT NULL default '0.00',
  `description` text NOT NULL,
  `type` varchar(10) NOT NULL default 'traffic',
  `quanity` bigint(20) unsigned NOT NULL default '0',
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

#
# Structure for the `bookmarks` table :
#

DROP TABLE IF EXISTS `bookmarks`;

CREATE TABLE `bookmarks` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `userid` int(10) unsigned NOT NULL default '0',
  `torrentid` int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

#
# Structure for the `user_bookmarks` table :
#

DROP TABLE IF EXISTS `user_bookmarks`;

CREATE TABLE `user_bookmarks` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `userid` int(10) unsigned NOT NULL default '0',
  `target_userid` int(10) unsigned NOT NULL default '0',
  `added_at` datetime NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_target` (`userid`,`target_userid`),
  KEY `target_userid` (`target_userid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

#
# Structure for the `person_bookmarks` table :
#

DROP TABLE IF EXISTS `person_bookmarks`;

CREATE TABLE `person_bookmarks` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `userid` int(10) unsigned NOT NULL default '0',
  `person_id` int(10) unsigned NOT NULL default '0',
  `added_at` datetime NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_person` (`userid`,`person_id`),
  KEY `person_id` (`person_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

#
# Structure for the `categories` table :
#

DROP TABLE IF EXISTS `categories`;

CREATE TABLE `categories` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `sort` int(10) NOT NULL default '0',
  `name` varchar(80) NOT NULL default '',
  `image` varchar(255) NOT NULL default '',
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

#
# Structure for the `checkcomm` table :
#

DROP TABLE IF EXISTS `checkcomm`;

CREATE TABLE `checkcomm` (
  `id` int(11) NOT NULL auto_increment,
  `checkid` int(11) NOT NULL default '0',
  `userid` int(11) NOT NULL default '0',
  `torrent` tinyint(4) NOT NULL default '0',
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

#
# Structure for the `comments` table :
#

DROP TABLE IF EXISTS `comments`;

CREATE TABLE `comments` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `user` int(10) unsigned NOT NULL default '0',
  `torrent` int(10) unsigned NOT NULL default '0',
  `added` datetime NULL DEFAULT NULL,
  `text` text NOT NULL,
  `ori_text` text NOT NULL,
  `editedby` int(10) unsigned NOT NULL default '0',
  `editedat` datetime NULL DEFAULT NULL,
  `ip` varchar(15) NOT NULL default '',
  PRIMARY KEY  (`id`),
  KEY `user` (`user`),
  KEY `torrent` (`torrent`),
  KEY `torrent_id` (`torrent`,`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

#
# Structure for the `comments_parsed` table :
#

DROP TABLE IF EXISTS `comments_parsed`;

CREATE TABLE `comments_parsed` (
  `cid` int(10) unsigned NOT NULL DEFAULT '0',
  `text_hash` varchar(32) NOT NULL DEFAULT '',
  `text_parsed` text NOT NULL,
  PRIMARY KEY (`cid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

#
# Structure for the `cups` table :
#

DROP TABLE IF EXISTS `cups`;

CREATE TABLE `cups` (
  `id` tinyint unsigned NOT NULL,
  `cup_key` varchar(40) NOT NULL,
  `title` varchar(100) NOT NULL,
  `profile_title` varchar(100) NOT NULL,
  `icon` varchar(16) NOT NULL default 'cb1',
  `sort` int unsigned NOT NULL default '0',
  `active` tinyint unsigned NOT NULL default '1',
  PRIMARY KEY (`id`),
  UNIQUE KEY `cup_key` (`cup_key`),
  KEY `sort` (`sort`),
  KEY `active` (`active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

#
# Structure for the `countries` table :
#

DROP TABLE IF EXISTS `countries`;

CREATE TABLE `countries` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `name` varchar(50) default NULL,
  `flagpic` varchar(50) default NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

#
# Structure for the `files` table :
#

DROP TABLE IF EXISTS `files`;

CREATE TABLE `files` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `torrent` int(10) unsigned NOT NULL default '0',
  `filename` varchar(255) NOT NULL default '',
  `size` bigint(20) unsigned NOT NULL default '0',
  PRIMARY KEY  (`id`),
  KEY `torrent` (`torrent`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

#
# Structure for the `friends` table :
#

DROP TABLE IF EXISTS `friends`;

CREATE TABLE `friends` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `userid` int(10) unsigned NOT NULL default '0',
  `friendid` int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (`id`),
  UNIQUE KEY `userfriend` (`userid`,`friendid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

#
# Structure for the `indexreleases` table :
#

DROP TABLE IF EXISTS `indexreleases`;

CREATE TABLE `indexreleases` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `torrentid` int(10) NOT NULL DEFAULT '0',
  `name` text NOT NULL,
  `cat` int(10) NOT NULL DEFAULT '0',
  `poster` text NOT NULL,
  `imdb` text NOT NULL,
  `top` text NOT NULL,
  `center` text NOT NULL,
  `bottom` text NOT NULL,
  `added` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

#
# Structure for the `messages` table :
#

DROP TABLE IF EXISTS `messages`;

CREATE TABLE `messages` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `sender` int(10) unsigned NOT NULL default '0',
  `receiver` int(10) unsigned NOT NULL default '0',
  `added` datetime default NULL,
  `subject` varchar(255) NOT NULL default '',
  `msg` text,
  `unread` enum('yes','no') NOT NULL default 'yes',
  `poster` int(10) unsigned NOT NULL default '0',
  `location` tinyint(1) NOT NULL default '1',
  `saved` enum('no','yes') NOT NULL default 'no',
  PRIMARY KEY  (`id`),
  KEY `receiver` (`receiver`),
  KEY `sender` (`sender`),
  KEY `poster` (`poster`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

#
# Structure for the `news` table :
#

DROP TABLE IF EXISTS `news`;

CREATE TABLE `news` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `userid` int(11) NOT NULL default '0',
  `added` datetime NULL DEFAULT NULL,
  `body` text NOT NULL,
  `subject` text NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `added` (`added`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

#
# Structure for the `notconnectablepmlog` table :
#

DROP TABLE IF EXISTS `notconnectablepmlog`;

CREATE TABLE `notconnectablepmlog` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `user` int(10) unsigned NOT NULL default '0',
  `date` datetime default NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

#
# Structure for the `orbital_blocks` table :
#

DROP TABLE IF EXISTS `orbital_blocks`;

CREATE TABLE `orbital_blocks` (
  `bid` int(10) NOT NULL auto_increment,
  `bkey` varchar(15) NOT NULL default '',
  `title` varchar(60) NOT NULL default '',
  `content` text NOT NULL,
  `bposition` char(1) NOT NULL default '',
  `weight` int(10) NOT NULL default '1',
  `active` int(1) NOT NULL default '1',
  `time` varchar(14) NOT NULL default '0',
  `blockfile` varchar(255) NOT NULL default '',
  `view` int(1) NOT NULL default '0',
  `expire` varchar(14) NOT NULL default '0',
  `action` char(1) NOT NULL default '',
  `which` varchar(255) NOT NULL default '',
  `allow_hide` enum('yes','no') NOT NULL default 'yes',
  PRIMARY KEY  (`bid`),
  KEY `title` (`title`),
  KEY `weight` (`weight`),
  KEY `active` (`active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

#
# Structure for the `peers` table :
#

DROP TABLE IF EXISTS `peers`;

CREATE TABLE `peers` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `torrent` int(10) unsigned NOT NULL default '0',
  `peer_id` varchar(20) NOT NULL default '',
  `ip` varchar(64) NOT NULL default '',
  `port` smallint(5) unsigned NOT NULL default '0',
  `uploaded` bigint(20) unsigned NOT NULL default '0',
  `downloaded` bigint(20) unsigned NOT NULL default '0',
  `uploadoffset` bigint(20) unsigned NOT NULL default '0',
  `downloadoffset` bigint(20) unsigned NOT NULL default '0',
  `to_go` bigint(20) unsigned NOT NULL default '0',
  `seeder` enum('yes','no') NOT NULL default 'no',
  `started` datetime NULL DEFAULT NULL,
  `last_action` datetime NULL DEFAULT NULL,
  `prev_action` datetime NULL DEFAULT NULL,
  `connectable` enum('yes','no') NOT NULL default 'yes',
  `userid` int(10) unsigned NOT NULL default '0',
  `agent` varchar(60) NOT NULL default '',
  `finishedat` int(10) unsigned NOT NULL default '0',
  `passkey` varchar(10) NOT NULL default '',
  PRIMARY KEY  (`id`),
  UNIQUE KEY `torrent_peer_id` (`torrent`,`peer_id`),
  KEY `torrent` (`torrent`),
  KEY `torrent_id` (`torrent`,`id`),
  KEY `torrent_seeder` (`torrent`,`seeder`),
  KEY `last_action` (`last_action`),
  KEY `connectable` (`connectable`),
  KEY `userid` (`userid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

#
# Structure for the `ratings` table :
#

DROP TABLE IF EXISTS `ratings`;

CREATE TABLE `ratings` (
  `id` int(6) NOT NULL auto_increment,
  `torrent` int(10) NOT NULL default '0',
  `user` int(6) NOT NULL default '0',
  `rating` int(2) NOT NULL default '0',
  `added` datetime NULL DEFAULT NULL,
  PRIMARY KEY  (`id`),
  KEY `torrent` (`torrent`),
  KEY `user` (`user`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

#
# Structure for the `readtorrents` table :
#

DROP TABLE IF EXISTS `readtorrents`;

CREATE TABLE `readtorrents` (
  `id` int(11) NOT NULL auto_increment,
  `userid` int(11) NOT NULL default '0',
  `torrentid` int(11) NOT NULL default '0',
  PRIMARY KEY  (`id`),
  UNIQUE KEY `read` (`userid`,`torrentid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

#
# Structure for the `sessions` table :
#

DROP TABLE IF EXISTS `sessions`;

CREATE TABLE `sessions` (
  `sid` varchar(32) NOT NULL default '',
  `uid` int(10) NOT NULL default '0',
  `username` varchar(40) NOT NULL default '',
  `class` tinyint(4) NOT NULL default '0',
  `ip` varchar(40) NOT NULL default '',
  `time` bigint(30) NOT NULL default '0',
  `url` varchar(150) NOT NULL default '',
  `useragent` text,
  PRIMARY KEY  (`sid`),
  KEY `time` (`time`),
  KEY `uid` (`uid`),
  KEY `url` (`url`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

#
# Structure for the `site_settings` table :
#

DROP TABLE IF EXISTS `site_settings`;

CREATE TABLE `site_settings` (
  `setting_key` varchar(80) NOT NULL,
  `setting_value` text NOT NULL,
  PRIMARY KEY (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `site_settings` (`setting_key`, `setting_value`) VALUES
  ('site_online', '1'),
  ('site_name', 'Торрент трекер Кинозал.ТВ'),
  ('site_email', 'noreply@localhost'),
  ('deny_signup', '0'),
  ('use_captcha', '1'),
  ('captcha_length', '5'),
  ('captcha_width', '180'),
  ('captcha_height', '56'),
  ('captcha_front_lines', '2'),
  ('captcha_behind_lines', '4'),
  ('captcha_max_angle', '12'),
  ('captcha_max_offset', '6'),
  ('captcha_distortion', '1'),
  ('use_blocks', '1'),
  ('allow_guests_details', '0'),
  ('maxusers', '10000'),
  ('max_torrent_size', '1048576'),
  ('reputation_daily_limit', '1'),
  ('reputation_signup_value', '0');

#
# Structure for the `pay_settings` table :
#

DROP TABLE IF EXISTS `pay_settings`;

CREATE TABLE `pay_settings` (
  `setting_key` varchar(80) NOT NULL,
  `setting_value` text NOT NULL,
  PRIMARY KEY (`setting_key`)
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
  ('chat_enabled', '1');

#
# Structure for the `pay_transactions` table :
#

DROP TABLE IF EXISTS `pay_transactions`;

CREATE TABLE `pay_transactions` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `userid` int(10) unsigned NOT NULL default '0',
  `username` varchar(40) NOT NULL default '',
  `operation` varchar(40) NOT NULL default '',
  `bonus_delta` decimal(10,2) NOT NULL default '0.00',
  `votes_delta` int(10) NOT NULL default '0',
  `uploaded_delta` bigint(20) NOT NULL default '0',
  `details` text NOT NULL,
  `ip` varchar(45) NOT NULL default '',
  `created_at` datetime NULL DEFAULT NULL,
  PRIMARY KEY  (`id`),
  KEY `userid_created` (`userid`,`created_at`),
  KEY `operation_created` (`operation`,`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

#
# Structure for the `pay_wishes` table :
#

DROP TABLE IF EXISTS `pay_wishes`;

CREATE TABLE `pay_wishes` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `userid` int(10) unsigned NOT NULL default '0',
  `username` varchar(40) NOT NULL default '',
  `userclass` tinyint(3) unsigned NOT NULL default '0',
  `text` text NOT NULL,
  `cost_votes` int(10) unsigned NOT NULL default '0',
  `active` enum('yes','no') NOT NULL default 'yes',
  `added` datetime NULL DEFAULT NULL,
  PRIMARY KEY  (`id`),
  KEY `active_added` (`active`,`added`),
  KEY `userid` (`userid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

#
# Structure for the `pay_chat` table :
#

DROP TABLE IF EXISTS `pay_chat`;

CREATE TABLE `pay_chat` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `tab` tinyint(3) unsigned NOT NULL default '1',
  `userid` int(10) unsigned NOT NULL default '0',
  `username` varchar(40) NOT NULL default '',
  `userclass` tinyint(3) unsigned NOT NULL default '0',
  `text` text NOT NULL,
  `ip` varchar(45) NOT NULL default '',
  `visible` enum('yes','no') NOT NULL default 'yes',
  `added` datetime NULL DEFAULT NULL,
  PRIMARY KEY  (`id`),
  KEY `tab_added` (`tab`,`added`),
  KEY `userid` (`userid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

#
# Structure for the `simpaty` table :
#

DROP TABLE IF EXISTS `simpaty`;

CREATE TABLE `simpaty` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `touserid` int(10) unsigned NOT NULL default '0',
  `fromuserid` int(10) unsigned NOT NULL default '0',
  `fromusername` varchar(40) NOT NULL default '',
  `bad` tinyint(1) unsigned NOT NULL default '0',
  `good` tinyint(1) unsigned NOT NULL default '0',
  `type` varchar(60) NOT NULL default '',
  `respect_time` datetime NULL DEFAULT NULL,
  `description` text NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `touserid` (`touserid`),
  KEY `fromuserid` (`fromuserid`),
  KEY `fromusername` (`fromusername`),
  KEY `respect_time` (`respect_time`),
  KEY `profile_wall` (`touserid`,`respect_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

#
# Structure for the `sitelog` table :
#

DROP TABLE IF EXISTS `sitelog`;

CREATE TABLE `sitelog` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `added` datetime default NULL,
  `color` varchar(11) NOT NULL default 'transparent',
  `txt` text,
  `type` varchar(8) NOT NULL default 'tracker',
  PRIMARY KEY  (`id`),
  KEY `added` (`added`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

#
# Structure for the `snatched` table :
#

DROP TABLE IF EXISTS `snatched`;

CREATE TABLE `snatched` (
  `id` int(11) NOT NULL auto_increment,
  `userid` int(11) default '0',
  `torrent` int(10) unsigned NOT NULL default '0',
  `port` smallint(5) unsigned NOT NULL default '0',
  `uploaded` bigint(20) unsigned NOT NULL default '0',
  `downloaded` bigint(20) unsigned NOT NULL default '0',
  `to_go` bigint(20) unsigned NOT NULL default '0',
  `seeder` enum('yes','no') NOT NULL default 'no',
  `last_action` datetime NULL DEFAULT NULL,
  `startdat` datetime NULL DEFAULT NULL,
  `completedat` datetime NULL DEFAULT NULL,
  `connectable` enum('yes','no') NOT NULL default 'yes',
  `finished` enum('yes','no') NOT NULL default 'no',
  PRIMARY KEY  (`id`),
  KEY `snatch` (`torrent`,`userid`),
  KEY `userid_completed` (`userid`,`completedat`,`last_action`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

#
# Structure for the `thanks` table :
#

DROP TABLE IF EXISTS `thanks`;

CREATE TABLE `thanks` (
  `id` int(11) NOT NULL auto_increment,
  `torrentid` int(11) NOT NULL default '0',
  `userid` int(11) NOT NULL default '0',
  PRIMARY KEY  (`id`),
  UNIQUE KEY `thank` (`torrentid`,`userid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

#
# Structure for the `uarch_smiles` table :
#

DROP TABLE IF EXISTS `uarch_smiles`;

CREATE TABLE `uarch_smiles` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `userid` int unsigned NOT NULL DEFAULT '0',
  `username` varchar(40) NOT NULL DEFAULT '',
  `userclass` tinyint unsigned NOT NULL DEFAULT '0',
  `image_url` text NOT NULL,
  `active` enum('yes','no') NOT NULL DEFAULT 'yes',
  `ip` varchar(45) NOT NULL DEFAULT '',
  `added` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `active_added` (`active`,`added`),
  KEY `userid` (`userid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

#
# Structure for the `torrents` table :
#

DROP TABLE IF EXISTS `torrents`;

CREATE TABLE `torrents` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `info_hash` varbinary(40) NOT NULL default '',
  `name` varchar(255) NOT NULL default '',
  `keywords` varchar(255) NOT NULL default '',
  `description` text NOT NULL,
  `filename` varchar(255) NOT NULL default '',
  `save_as` varchar(255) NOT NULL default '',
  `descr` text NOT NULL,
  `ori_descr` text NOT NULL,
  `image1` text NOT NULL,
  `image2` text NOT NULL,
  `image3` text NOT NULL,
  `image4` text NOT NULL,
  `image5` text NOT NULL,
  `category` int(10) unsigned NOT NULL default '0',
  `size` bigint(20) unsigned NOT NULL default '0',
  `added` datetime NULL DEFAULT NULL,
  `type` enum('single','multi') NOT NULL default 'single',
  `numfiles` int(10) unsigned NOT NULL default '0',
  `comments` int(10) unsigned NOT NULL default '0',
  `views` int(10) unsigned NOT NULL default '0',
  `hits` int(10) unsigned NOT NULL default '0',
  `times_completed` int(10) unsigned NOT NULL default '0',
  `leechers` int(10) unsigned NOT NULL default '0',
  `remote_leechers` int(10) unsigned NOT NULL DEFAULT '0',
  `seeders` int(10) unsigned NOT NULL default '0',
  `remote_seeders` int(10) unsigned NOT NULL DEFAULT '0',
  `last_action` datetime NULL DEFAULT NULL,
  `last_mt_update` datetime NULL DEFAULT NULL,
  `last_reseed` datetime NULL DEFAULT NULL,
  `visible` enum('yes','no') NOT NULL default 'yes',
  `banned` enum('yes','no') NOT NULL default 'no',
  `owner` int(10) unsigned NOT NULL default '0',
  `numratings` int(10) unsigned NOT NULL default '0',
  `ratingsum` int(10) unsigned NOT NULL default '0',
  `free` enum('yes','silver','no') default 'no',
  `not_sticky` enum('yes','no') NOT NULL DEFAULT 'yes',
  `moderated` enum('yes','no') NOT NULL default 'no',
  `moderatedby` int(10) unsigned default '0',
  `multitracker` enum('yes','no') NOT NULL DEFAULT 'no',
  `is_test` enum('yes','no') NOT NULL DEFAULT 'no',
  `test_approved_at` datetime NULL DEFAULT NULL,
  `test_approved_by` int(10) unsigned NOT NULL DEFAULT '0',
  `test_helper_user_id` int(10) unsigned NOT NULL DEFAULT '0',
  `test_helper_until` datetime NULL DEFAULT NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `info_hash` (`info_hash`),
  KEY `owner` (`owner`),
  KEY `visible` (`visible`),
  KEY `category_visible` (`category`,`visible`),
  KEY `browse_main` (`visible`,`banned`,`is_test`,`not_sticky`,`added`,`id`),
  KEY `browse_category` (`category`,`visible`,`banned`,`is_test`,`not_sticky`,`added`,`id`),
  KEY `vnsi` (`visible`, `not_sticky`, `id`),
  KEY `is_test_visible` (`is_test`, `visible`, `banned`, `added`),
  KEY `test_helper_until` (`test_helper_until`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

#
# Structure for the `torrents_descr` table :
#

DROP TABLE IF EXISTS `torrents_descr`;

CREATE TABLE `torrents_descr` (
  `tid` int(10) unsigned NOT NULL DEFAULT '0',
  `descr_hash` varchar(32) NOT NULL DEFAULT '',
  `descr_parsed` text NOT NULL,
  PRIMARY KEY (`tid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

#
# Structure for the `torrent_details` table :
#

DROP TABLE IF EXISTS `torrent_details`;

CREATE TABLE `torrent_details` (
  `tid` int(10) unsigned NOT NULL,
  `release_kind` varchar(20) NOT NULL DEFAULT 'video',
  `poster_url` text NOT NULL,
  `rgroup` int(10) unsigned NOT NULL DEFAULT '0',
  `rgroup_button` varchar(255) NOT NULL DEFAULT '',
  `torrent_file_updated_at` datetime NULL DEFAULT NULL,
  `form_mode` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `section_modes` varchar(20) NOT NULL DEFAULT '0,0,0,0',
  `data` mediumtext NOT NULL,
  `created_at` datetime NULL DEFAULT NULL,
  `updated_at` datetime NULL DEFAULT NULL,
  PRIMARY KEY (`tid`),
  KEY `release_kind` (`release_kind`),
  KEY `rgroup` (`rgroup`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

#
# Structure for the `persons` table :
#

DROP TABLE IF EXISTS `persons`;

CREATE TABLE `persons` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `name` varchar(160) NOT NULL default '',
  `original_name` varchar(160) NOT NULL default '',
  `type` tinyint(3) unsigned NOT NULL default '11',
  `gender` tinyint(1) unsigned NOT NULL default '0',
  `poster_url` text NOT NULL,
  `birth_date` date NULL DEFAULT NULL,
  `birth_text` varchar(120) NOT NULL default '',
  `birth_place` varchar(255) NOT NULL default '',
  `career` varchar(255) NOT NULL default '',
  `genre` varchar(255) NOT NULL default '',
  `height` varchar(40) NOT NULL default '',
  `spouse` varchar(255) NOT NULL default '',
  `biography` mediumtext NOT NULL,
  `trivia` mediumtext NOT NULL,
  `filmography` mediumtext NOT NULL,
  `voice` mediumtext NOT NULL,
  `producer` mediumtext NOT NULL,
  `director` mediumtext NOT NULL,
  `writer` mediumtext NOT NULL,
  `awards` mediumtext NOT NULL,
  `links` mediumtext NOT NULL,
  `source_url` varchar(255) NOT NULL default '',
  `created_by` int(10) unsigned NOT NULL default '0',
  `created_at` datetime NULL DEFAULT NULL,
  `updated_by` int(10) unsigned NOT NULL default '0',
  `updated_at` datetime NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `name` (`name`),
  KEY `original_name` (`original_name`),
  KEY `birth_date` (`birth_date`),
  KEY `type` (`type`),
  KEY `updated_at` (`updated_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

#
# Structure for the `person_photos` table :
#

DROP TABLE IF EXISTS `person_photos`;

CREATE TABLE `person_photos` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `person_id` int(10) unsigned NOT NULL default '0',
  `image_url` text NOT NULL,
  `sort` int(10) unsigned NOT NULL default '0',
  PRIMARY KEY (`id`),
  KEY `person_id` (`person_id`),
  KEY `sort` (`sort`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

#
# Structure for the `torrents_scrape` table :
#

DROP TABLE IF EXISTS `torrents_scrape`;

CREATE TABLE `torrents_scrape` (
  `tid` int(10) unsigned NOT NULL DEFAULT '0',
  `info_hash` varbinary(40) NOT NULL DEFAULT '',
  `url` varchar(100) NOT NULL DEFAULT '',
  `seeders` int(10) unsigned NOT NULL DEFAULT '0',
  `leechers` int(10) unsigned NOT NULL DEFAULT '0',
  `completed` int(10) unsigned NOT NULL DEFAULT '0',
  `last_update` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `state` enum('ok','error') NOT NULL DEFAULT 'ok',
  `error` varchar(100) NOT NULL DEFAULT '',
  PRIMARY KEY (`info_hash`,`url`),
  KEY `tid` (`tid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

#
# Structure for the `torrent_trackers` table :
#

DROP TABLE IF EXISTS `torrent_trackers`;

CREATE TABLE `torrent_trackers` (
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
  KEY `enabled_checked` (`enabled`, `last_checked`),
  KEY `due_trackers` (`enabled`, `is_primary`, `last_checked`),
  KEY `torrent_active` (`torrentid`, `enabled`, `is_primary`, `last_error`(32))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

#
# Structure for the `users` table :
#

DROP TABLE IF EXISTS `users`;

CREATE TABLE `users` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `username` varchar(40) NOT NULL default '',
  `old_password` varchar(40) NOT NULL default '',
  `passhash` varchar(32) NOT NULL default '',
  `secret` varchar(20) NOT NULL default '',
  `email` varchar(80) NOT NULL default '',
  `status` enum('pending','confirmed') NOT NULL default 'pending',
  `added` datetime NULL DEFAULT NULL,
  `last_login` datetime NULL DEFAULT NULL,
  `last_access` datetime NULL DEFAULT NULL,
  `editsecret` varchar(20) NOT NULL default '',
  `privacy` enum('strong','normal','low') NOT NULL default 'normal',
  `theme` varchar(40) NOT NULL default '',
  `info` text,
  `acceptpms` enum('yes','friends','no') NOT NULL default 'yes',
  `ip` varchar(15) NOT NULL default '',
  `class` tinyint(3) unsigned NOT NULL default '0',
  `override_class` tinyint(3) unsigned NOT NULL default '255',
  `support` enum('no','yes') NOT NULL default 'no',
  `supportfor` text,
  `avatar` varchar(500) NOT NULL default '',
  `uploaded` bigint(20) unsigned NOT NULL default '0',
  `downloaded` bigint(20) unsigned NOT NULL default '0',
  `bonus` decimal(7,2) NOT NULL default '0.00',
  `pay_votes` int(10) unsigned NOT NULL DEFAULT '0',
  `title` varchar(30) NOT NULL default '',
  `country` int(10) unsigned NOT NULL default '0',
  `notifs` varchar(100) NOT NULL default '',
  `modcomment` text,
  `enabled` enum('yes','no') NOT NULL default 'yes',
  `parked` enum('yes','no') NOT NULL default 'no',
  `avatars` enum('yes','no') NOT NULL default 'yes',
  `donor` enum('yes','no') NOT NULL default 'no',
  `pay_donor_until` datetime NULL DEFAULT NULL,
  `pay_vip_until` datetime NULL DEFAULT NULL,
  `simpaty` int(10) NOT NULL default '0',
  `warned` enum('yes','no') NOT NULL default 'no',
  `warneduntil` datetime NULL DEFAULT NULL,
  `torrentsperpage` int(3) unsigned NOT NULL default '0',
  `topicsperpage` int(3) unsigned NOT NULL default '0',
  `postsperpage` int(3) unsigned NOT NULL default '0',
  `deletepms` enum('yes','no') NOT NULL default 'yes',
  `savepms` enum('yes','no') NOT NULL default 'no',
  `gender` enum('1','2','3') NOT NULL default '1',
  `birthday` date DEFAULT NULL,
  `city` varchar(100) NOT NULL default '',
  `favorite_movie` varchar(255) NOT NULL default '',
  `favorite_persons` varchar(255) NOT NULL default '',
  `passkey` varchar(10) NOT NULL default '',
  `language` varchar(255) NOT NULL default 'russian',
  `passkey_ip` varchar(15) NOT NULL default '',
  PRIMARY KEY  (`id`),
  UNIQUE KEY `username` (`username`),
  KEY `status_added` (`status`,`added`),
  KEY `ip` (`ip`),
  KEY `uploaded` (`uploaded`),
  KEY `downloaded` (`downloaded`),
  KEY `country` (`country`),
  KEY `last_access` (`last_access`),
  KEY `enabled` (`enabled`),
  KEY `warned` (`warned`),
  KEY `passkey` (`passkey`),
  KEY `user` (`id`,`status`,`enabled`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

#
# Structure for the `user_statuses` table :
#

DROP TABLE IF EXISTS `user_statuses`;

CREATE TABLE `user_statuses` (
  `status_key` varchar(40) NOT NULL,
  `title` varchar(100) NOT NULL,
  `icon_class` varchar(40) NOT NULL,
  `sort` int unsigned NOT NULL default '0',
  `active` tinyint unsigned NOT NULL default '1',
  `auto` tinyint unsigned NOT NULL default '0',
  PRIMARY KEY (`status_key`),
  KEY `sort` (`sort`),
  KEY `active` (`active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

#
# Structure for the `user_status_assignments` table :
#

DROP TABLE IF EXISTS `user_status_assignments`;

CREATE TABLE `user_status_assignments` (
  `userid` int unsigned NOT NULL,
  `status_key` varchar(40) NOT NULL,
  `assigned_by` int unsigned NOT NULL default '0',
  `assigned_at` datetime NULL DEFAULT NULL,
  PRIMARY KEY (`userid`,`status_key`),
  KEY `status_key` (`status_key`),
  KEY `assigned_at` (`assigned_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

#
# Structure for the `user_torrent_downloads` table :
#

DROP TABLE IF EXISTS `user_torrent_downloads`;

CREATE TABLE `user_torrent_downloads` (
  `userid` int unsigned NOT NULL,
  `torrent` int unsigned NOT NULL,
  `download_date` date NOT NULL,
  `downloaded_at` datetime NOT NULL,
  PRIMARY KEY (`userid`,`torrent`,`download_date`),
  KEY `download_date` (`download_date`),
  KEY `torrent` (`torrent`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

#
# Data for the `user_statuses` table :
#

INSERT INTO `user_statuses` (`status_key`, `title`, `icon_class`, `sort`, `active`, `auto`) VALUES
  ('patron','Меценат','s1',1,1,1),
  ('girl','Девушка','s_dv',2,1,1),
  ('king','Коро(ль,лева)','s9-10',3,1,0),
  ('loyal_seed','Верный сид','s4',4,1,0),
  ('rhetoric','Риторик','s5',5,1,0),
  ('keeper','Хранитель раздач','s6',6,1,0),
  ('reviewer','Рецензент','s3',7,1,0),
  ('person_editor','Оформитель персон','s3',8,1,0),
  ('translator','Переводчик','s8',9,1,0),
  ('other','Другие...','s3',10,1,0),
  ('birthday','День рождения','s_bday',11,1,1),
  ('warned','Предупрежден','s2',12,1,1),
  ('low_ratio','Предупрежден 1 Торрент','s7',13,1,1),
  ('disabled','Отключен','s_dis',14,1,1);

#
# Structure for the `user_cups` table :
#

DROP TABLE IF EXISTS `user_cups`;

CREATE TABLE `user_cups` (
  `cup_id` tinyint unsigned NOT NULL,
  `userid` int unsigned NOT NULL,
  `source` enum('auto','manual') NOT NULL default 'auto',
  `metric` bigint unsigned NOT NULL default '0',
  `assigned_by` int unsigned NOT NULL default '0',
  `assigned_at` datetime NULL DEFAULT NULL,
  `note` varchar(255) NOT NULL default '',
  PRIMARY KEY (`cup_id`),
  KEY `userid` (`userid`),
  KEY `source` (`source`),
  KEY `assigned_at` (`assigned_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

#
# Structure for the `users_ban` table :
#

DROP TABLE IF EXISTS `users_ban`;

CREATE TABLE `users_ban` (
  `userid` int(10) unsigned NOT NULL default '0',
  `disuntil` datetime NULL DEFAULT NULL,
  `disby` int(10) unsigned NOT NULL default '0',
  `reason` text NOT NULL,
  UNIQUE KEY `userid` (`userid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

#
# Data for the `bonus` table  (LIMIT 0,100)
#

INSERT INTO `bonus` (`id`, `name`, `points`, `description`, `type`, `quanity`) VALUES
  (1,'1.0GB Uploaded',75,'With enough bonus points acquired, you are able to exchange them for an Upload Credit. The points are then removed from your Bonus Bank and the credit is added to your total uploaded amount.','traffic','1073741824'),
  (2,'2.5GB Uploaded',150,'With enough bonus points acquired, you are able to exchange them for an Upload Credit. The points are then removed from your Bonus Bank and the credit is added to your total uploaded amount.','traffic','2684354560'),
  (3,'5GB Uploaded',250,'With enough bonus points acquired, you are able to exchange them for an Upload Credit. The points are then removed from your Bonus Bank and the credit is added to your total uploaded amount.','traffic','5368709120');

COMMIT;

#
# Data for the `categories` table  (LIMIT 0,100)
#

INSERT INTO `categories` (`id`, `sort`, `name`, `image`) VALUES
  (45,10,'Сериал - Русский','45.gif'),
  (46,20,'Сериал - Буржуйский','46.gif'),
  (8,30,'Кино - Комедия','8.gif'),
  (6,40,'Кино - Боевик / Военный','6.gif'),
  (15,50,'Кино - Триллер / Детектив','15.gif'),
  (17,60,'Кино - Драма','17.gif'),
  (35,70,'Кино - Мелодрама','35.gif'),
  (39,80,'Кино - Индийское','39.gif'),
  (13,90,'Кино - Фантастика','13.gif'),
  (14,100,'Кино - Фэнтези','14.gif'),
  (24,110,'Кино - Ужас / Мистика','24.gif'),
  (11,120,'Кино - Приключения','11.gif'),
  (10,130,'Кино - Наше Кино','10.gif'),
  (9,140,'Кино - Исторический','9.gif'),
  (47,150,'Кино - Азиатский','47.gif'),
  (18,160,'Кино - Документальный','18.gif'),
  (37,170,'Кино - Спорт','37.gif'),
  (12,180,'Кино - Детский / Семейный','12.gif'),
  (7,190,'Кино - Классика','7.gif'),
  (48,200,'Кино - Концерт','48.gif'),
  (49,210,'Кино - Передачи / ТВ-шоу','49.gif'),
  (50,220,'Кино - ТВ-шоу Мир','50.gif'),
  (38,230,'Кино - Театр, Опера, Балет','38.gif'),
  (16,240,'Кино - Эротика','16.gif'),
  (21,250,'Мульт - Буржуйский','21.gif'),
  (22,260,'Мульт - Русский','22.gif'),
  (20,270,'Мульт - Аниме','20.gif'),
  (1,280,'Другое - Видеоклипы','1.gif'),
  (3,290,'Музыка - Буржуйская','3.gif'),
  (4,300,'Музыка - Русская','4.gif'),
  (5,310,'Музыка - Сборники','5.gif'),
  (42,320,'Музыка - Классическая','42.gif'),
  (2,330,'Другое - АудиоКниги','2.gif'),
  (23,340,'Другое - Игры','23.gif'),
  (32,350,'Другое - Программы','32.gif'),
  (40,360,'Другое - Дизайн / Графика','40.gif'),
  (41,370,'Другое - Библиотека','41.gif');

COMMIT;

#
# Data for the `cups` table  (LIMIT 0,100)
#

INSERT INTO `cups` (`id`, `cup_key`, `title`, `profile_title`, `icon`, `sort`, `active`) VALUES
  (1,'best_release','Кубок за лучшую раздачу','За самую лучшую раздачу','cb1',1,1),
  (2,'popular_release','Кубок за популярную раздачу','За популярную раздачу','cb2',2,1),
  (3,'active_seeder','Кубок самому активному раздающему','Самому активному раздающему','cb3',3,1),
  (4,'discussed_release','Кубок за самую обсуждаемую раздачу','За самую обсуждаемую раздачу','cb4',4,1),
  (5,'best_commentator','Кубок лучшему комментатору','Лучшему комментатору','cb5',5,1),
  (6,'active_patron','Кубок активному Меценату','Активному Меценату','cb6',6,1),
  (7,'best_patron','Кубок лучшему Меценату','Лучшему Меценату','cb7',7,1),
  (8,'best_dj','Кубок лучшему ДиДжею','Лучшему ДиДжею','cb8',8,1);

COMMIT;

#
# Data for the 'countries' table  (Records 1 - 100)
#

INSERT INTO `countries` (`id`, `name`, `flagpic`) VALUES
  (87, 'Антигуа и Барбуда', 'antiguabarbuda.gif'),
  (33, 'Белиз', 'belize.gif'),
  (59, 'Буркина Фасо', 'burkinafaso.gif'),
  (10, 'Дания', 'denmark.gif'),
  (91, 'Сенегал', 'senegal.gif'),
  (76, 'Тринидад и Тобаго', 'trinidadandtobago.gif'),
  (20, 'Австралия', 'australia.gif'),
  (36, 'Австрия', 'austria.gif'),
  (27, 'Албания', 'albania.gif'),
  (34, 'Алжир', 'algeria.gif'),
  (12, 'Великобритания', 'uk.gif'),
  (35, 'Ангола', 'angola.gif'),
  (66, 'Андорра', 'andorra.gif'),
  (19, 'Аргентина', 'argentina.gif'),
  (53, 'Афганистан', 'afghanistan.gif'),
  (80, 'Багамы', 'bahamas.gif'),
  (83, 'Барбадос', 'barbados.gif'),
  (16, 'Бельгия', 'belgium.gif'),
  (84, 'Бангладеш', 'bangladesh.gif'),
  (101, 'Болгария', 'bulgaria.gif'),
  (65, 'Босния и Герцеговина', 'bosniaherzegovina.gif'),
  (18, 'Бразилия', 'brazil.gif'),
  (74, 'Вануату', 'vanuatu.gif'),
  (72, 'Венгрия', 'hungary.gif'),
  (71, 'Венесуэла', 'venezuela.gif'),
  (75, 'Вьетнам', 'vietnam.gif'),
  (7, 'Германия', 'germany.gif'),
  (77, 'Гондурас', 'honduras.gif'),
  (32, 'Гонконг', 'hongkong.gif'),
  (41, 'Греция', 'greece.gif'),
  (42, 'Гватемала', 'guatemala.gif'),
  (40, 'Доминиканская Республика', 'dominicanrep.gif'),
  (100, 'Египет', 'egypt.gif'),
  (43, 'Израиль', 'israel.gif'),
  (26, 'Индия', 'india.gif'),
  (13, 'Ирландия', 'ireland.gif'),
  (61, 'Исландия', 'iceland.gif'),
  (102, 'Исла де Муерто', 'jollyroger.gif'),
  (22, 'Испания', 'spain.gif'),
  (9, 'Италия', 'italy.gif'),
  (82, 'Камбоджа', 'cambodia.gif'),
  (5, 'Канада', 'canada.gif'),
  (78, 'Кыргызстан', 'kyrgyzstan.gif'),
  (57, 'Кирибати', 'kiribati.gif'),
  (8, 'Китай', 'china.gif'),
  (52, 'Конго', 'congo.gif'),
  (96, 'Колумбия', 'colombia.gif'),
  (99, 'Коста-Рика', 'costarica.gif'),
  (51, 'Куба', 'cuba.gif'),
  (85, 'Лаос', 'laos.gif'),
  (98, 'Латвия', 'latvia.gif'),
  (97, 'Ливан', 'lebanon.gif'),
  (67, 'Литва', 'lithuania.gif'),
  (31, 'Люксембург', 'luxembourg.gif'),
  (68, 'Македония', 'macedonia.gif'),
  (39, 'Малайзия', 'malaysia.gif'),
  (24, 'Мексика', 'mexico.gif'),
  (62, 'Науру', 'nauru.gif'),
  (60, 'Нигерия', 'nigeria.gif'),
  (69, 'Нидерландские Антиллы', 'nethantilles.gif'),
  (15, 'Нидерланды', 'netherlands.gif'),
  (21, 'Новая Зеландия', 'newzealand.gif'),
  (11, 'Норвегия', 'norway.gif'),
  (44, 'Пакистан', 'pakistan.gif'),
  (88, 'Парагвай', 'paraguay.gif'),
  (81, 'Перу', 'peru.gif'),
  (14, 'Польша', 'poland.gif'),
  (23, 'Португалия', 'portugal.gif'),
  (49, 'Пуэрто-Рико', 'puertorico.gif'),
  (3, 'Россия', 'russia.gif'),
  (73, 'Румыния', 'romania.gif'),
  (93, 'Северная Корея', 'northkorea.gif'),
  (47, 'Сейшельские Острова', 'seychelles.gif'),
  (46, 'Сербия', 'serbia.gif'),
  (25, 'Сингапур', 'singapore.gif'),
  (63, 'Словакия', 'slovenia.gif'),
  (90, 'СССР', 'ussr.gif'),
  (2, 'США', 'usa.gif'),
  (48, 'Тайвань', 'taiwan.gif'),
  (89, 'Таиланд', 'thailand.gif'),
  (92, 'Того', 'togo.gif'),
  (64, 'Туркменистан', 'turkmenistan.gif'),
  (54, 'Турция', 'turkey.gif'),
  (55, 'Узбекистан', 'uzbekistan.gif'),
  (70, 'Украина', 'ukraine.gif'),
  (86, 'Уругвай', 'uruguay.gif'),
  (58, 'Филиппины', 'philippines.gif'),
  (4, 'Финляндия', 'finland.gif'),
  (6, 'Франция', 'france.gif'),
  (94, 'Хорватия', 'croatia.gif'),
  (45, 'Чехия', 'czechrep.gif'),
  (50, 'Чили', 'chile.gif'),
  (56, 'Швейцария', 'switzerland.gif'),
  (1, 'Швеция', 'sweden.gif'),
  (79, 'Эквадор', 'ecuador.gif'),
  (95, 'Эстония', 'estonia.gif'),
  (37, 'Югославия', 'yugoslavia.gif'),
  (28, 'ЮАР', 'southafrica.gif'),
  (29, 'Южная Корея', 'southkorea.gif'),
  (103, 'Молдова', 'moldova.gif');

COMMIT;

#
# Data for the 'countries' table  (Records 101 - 109)
#

INSERT INTO `countries` (`id`, `name`, `flagpic`) VALUES
  (38, 'Самоа', 'westernsamoa.gif'),
  (30, 'Ямайка', 'jamaica.gif'),
  (17, 'Япония', 'japan.gif'),
  (104, 'Беларусь', 'belarus.gif'),
  (105, 'Казахстан', 'kazakhstan.gif'),
  (106, 'Таджикистан', 'tajikistan.gif'),
  (107, 'Грузия', 'georgia.gif'),
  (108, 'Армения', 'armenia.gif'),
  (109, 'Азербайджан', 'azerbaijan.gif');

COMMIT;

#
# Data for the `orbital_blocks` table  (LIMIT 0,100)
#

INSERT INTO `orbital_blocks` (`bid`, `bkey`, `title`, `content`, `bposition`, `weight`, `active`, `time`, `blockfile`, `view`, `expire`, `action`, `which`) VALUES
  (1,'','Администрация','<table border=\"0\"><tr>\r\n<td class=\"block\"><a href=\"/admincp.php\">Админка</a></td>\r\n</tr><tr>\r\n<td class=\"block\"><a href=\"/users.php\">Список пользователей</a></td>\r\n</tr><tr>\r\n<td class=\"block\"><a href=\"/usersearch.php\">Поиск по IP</a></td>\r\n</tr><tr>\r\n<td class=\"block\"><a href=\"/logout.php\">Выйти</a></td>\r\n</tr></table>','r',1,1,'','',2,'0','d','all'),
  (13,'','Топ раздач','','r',0,1,'','block-top-torrents.php',0,'0','d','all'),
  (8,'','Статистика трекера','','r',6,1,'','block-stats.php',0,'0','d','ihome,'),
  (12,'','Переходящие кубки','','r',4,1,'','block-cups.php',0,'0','d','ihome,'),
  (14,'','День рождения','','r',5,1,'','block-birthday.php',0,'0','d','all'),
  (10,'','Напоминание о правилах','<p align=\"jsutify\">Администрация данного сайта - прирожденные садисты и кровопийцы, которые только и ищут повод помучать и поиздеваться над пользователями, используя для этого самые изощренные пытки. Единственный способ избежать этого - не попадаться нам на глаза, то есть спокойно качать и раздавать, поддерживая свой рейтинг как можно ближе к 1, и не делать глупых комментариев к торрентам. И не говорите, что мы вас не предупреждали! (шутка)</p>','c',1,1,'','',0,'0','d','rules,'),
  (2,'','Новости','','c',3,1,'','block-news.php',0,'0','d','ihome,'),
  (3,'','Пользователи','','r',2,0,'','block-online.php',0,'0','d','all'),
  (4,'','Поиск','','r',3,0,'','block-search.php',0,'0','d','all'),
  (6,'','Релизы','','c',5,1,'','block-releases.php',0,'0','d','ihome,'),
  (15,'','Меценаты','','c',100,1,'0','block-pay.php',1,'0','d','ihome,');

COMMIT;
