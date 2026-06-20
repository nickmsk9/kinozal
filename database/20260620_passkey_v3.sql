-- Expand tracker passkeys for newly generated and manually rotated keys.
-- Existing 10-character passkeys remain valid so users do not need to re-download torrents immediately.

ALTER TABLE `users`
  MODIFY `passkey` varchar(64) NOT NULL default '';

ALTER TABLE `peers`
  MODIFY `passkey` varchar(64) NOT NULL default '';
