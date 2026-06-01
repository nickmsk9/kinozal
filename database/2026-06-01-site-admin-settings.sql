CREATE TABLE IF NOT EXISTS `site_settings` (
  `setting_key` varchar(80) NOT NULL,
  `setting_value` text NOT NULL,
  PRIMARY KEY (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `site_settings` (`setting_key`, `setting_value`) VALUES
  ('site_online', '1'),
  ('site_name', 'Торрент трекер Кинозал.ТВ'),
  ('site_email', 'noreply@localhost'),
  ('deny_signup', '0'),
  ('allow_invite_signup', '0'),
  ('use_captcha', '1'),
  ('use_blocks', '1'),
  ('allow_guests_details', '0'),
  ('maxusers', '10000'),
  ('max_torrent_size', '1048576')
ON DUPLICATE KEY UPDATE `setting_value` = `setting_value`;
