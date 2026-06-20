-- P0 audit migration: announce data consistency and IPv6-safe user IP fields.
-- Run on an existing database before deploying the matching announce.php changes.
-- Make a database backup first: MySQL DDL below performs implicit commits.

CREATE TEMPORARY TABLE snatched_rollup AS
SELECT
  MIN(id) AS keep_id,
  torrent,
  userid,
  MAX(port) AS port,
  SUM(uploaded) AS uploaded,
  SUM(downloaded) AS downloaded,
  MIN(to_go) AS to_go,
  IF(SUM(seeder = 'yes') > 0, 'yes', 'no') AS seeder,
  MAX(last_action) AS last_action,
  MIN(startdat) AS startdat,
  MAX(completedat) AS completedat,
  IF(SUM(connectable = 'yes') > 0, 'yes', 'no') AS connectable,
  IF(SUM(finished = 'yes') > 0, 'yes', 'no') AS finished
FROM snatched
GROUP BY torrent, userid;

UPDATE snatched AS s
INNER JOIN snatched_rollup AS r ON r.keep_id = s.id
SET
  s.port = r.port,
  s.uploaded = r.uploaded,
  s.downloaded = r.downloaded,
  s.to_go = r.to_go,
  s.seeder = r.seeder,
  s.last_action = r.last_action,
  s.startdat = r.startdat,
  s.completedat = r.completedat,
  s.connectable = r.connectable,
  s.finished = r.finished;

DELETE s
FROM snatched AS s
LEFT JOIN snatched_rollup AS r ON r.keep_id = s.id
WHERE r.keep_id IS NULL;

DROP TEMPORARY TABLE snatched_rollup;

DELETE p
FROM peers AS p
INNER JOIN (
  SELECT id
  FROM (
    SELECT
      id,
      ROW_NUMBER() OVER (PARTITION BY torrent, userid, peer_id ORDER BY last_action DESC, id DESC) AS rn
    FROM peers
  ) AS ranked
  WHERE rn > 1
) AS d ON d.id = p.id;

ALTER TABLE snatched
  DROP KEY snatch,
  ADD UNIQUE KEY snatched_torrent_user (torrent, userid);

ALTER TABLE peers
  DROP KEY torrent_peer_id,
  ADD UNIQUE KEY torrent_user_peer (torrent, userid, peer_id),
  ADD KEY torrent_seeder_action_id (torrent, seeder, last_action, id),
  ADD KEY userid_seeder_action (userid, seeder, last_action);

ALTER TABLE users
  MODIFY ip varchar(45) NOT NULL DEFAULT '',
  MODIFY passkey_ip varchar(45) NOT NULL DEFAULT '';
