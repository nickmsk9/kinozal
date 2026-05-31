SET NAMES utf8mb4;

ALTER TABLE `torrents`
  ADD COLUMN `is_test` enum('yes','no') NOT NULL DEFAULT 'no' AFTER `multitracker`,
  ADD COLUMN `test_approved_at` datetime NULL DEFAULT NULL AFTER `is_test`,
  ADD COLUMN `test_approved_by` int(10) unsigned NOT NULL DEFAULT '0' AFTER `test_approved_at`,
  ADD COLUMN `test_helper_user_id` int(10) unsigned NOT NULL DEFAULT '0' AFTER `test_approved_by`,
  ADD COLUMN `test_helper_until` datetime NULL DEFAULT NULL AFTER `test_helper_user_id`,
  ADD KEY `is_test_visible` (`is_test`, `visible`, `banned`, `added`),
  ADD KEY `test_helper_until` (`test_helper_until`);
