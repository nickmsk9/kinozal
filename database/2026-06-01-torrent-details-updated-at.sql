ALTER TABLE `torrent_details`
  ADD COLUMN `torrent_file_updated_at` datetime NULL DEFAULT NULL AFTER `rgroup_button`;
