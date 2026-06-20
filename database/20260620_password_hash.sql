-- Password hash storage upgrade.
-- Existing MD5 hashes are kept and migrated lazily after successful login/password change.

ALTER TABLE `users`
  MODIFY `passhash` varchar(255) NOT NULL default '',
  MODIFY `editsecret` varchar(64) NOT NULL default '';
