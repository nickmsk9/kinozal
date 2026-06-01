ALTER TABLE `torrent_trackers`
  ADD KEY `due_trackers` (`enabled`, `is_primary`, `last_checked`),
  ADD KEY `torrent_active` (`torrentid`, `enabled`, `is_primary`, `last_error`(32));
