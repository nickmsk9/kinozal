<?php

if (!defined('KZ_MT_TTL')) {
	define('KZ_MT_TTL', 20 * 60);
}
if (!defined('KZ_MT_HTTP_TIMEOUT')) {
	define('KZ_MT_HTTP_TIMEOUT', 2);
}
if (!defined('KZ_MT_UDP_TIMEOUT')) {
	define('KZ_MT_UDP_TIMEOUT', 3);
}
if (!defined('KZ_MT_MANUAL_BUDGET')) {
	define('KZ_MT_MANUAL_BUDGET', 3);
}

function multitracker_h($value)
{
	return function_exists('htmlspecialchars_uni') ? htmlspecialchars_uni((string)$value) : htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function multitracker_format_checked_at($value)
{
	$value = trim((string)$value);
	if ($value === '') {
		return 'н/д';
	}

	$value = preg_replace('/\.\d+$/', '', $value);
	$time = strtotime($value);
	if ($time === false) {
		return $value;
	}

	return date('Y-m-d H:i:s', $time);
}

function multitracker_is_client_only_error($error)
{
	$error = strtolower(trim((string)$error));
	if ($error === '') {
		return false;
	}

	return (bool)preg_match('/client-only announce|unsupported protocol|websocket/i', $error);
}

function multitracker_primary_announce()
{
	global $announce_urls, $DEFAULTBASEURL;

	if (!empty($announce_urls[0])) {
		return multitracker_normalize_url($announce_urls[0]);
	}

	return multitracker_normalize_url(rtrim((string)$DEFAULTBASEURL, '/') . '/announce.php');
}

function multitracker_normalize_url($url)
{
	$url = trim((string)$url);
	if ($url === '') {
		return '';
	}
	$url = html_entity_decode($url, ENT_QUOTES, 'UTF-8');
	$url = preg_replace('/[\x00-\x20]+/', '', $url);
	if (strlen($url) > 500) {
		$url = substr($url, 0, 500);
	}
	return $url;
}

function multitracker_protocol($url)
{
	if (strtolower(multitracker_normalize_url($url)) === multitracker_manual_stats_url()) {
		return 'manual';
	}
	$scheme = strtolower((string)parse_url(multitracker_normalize_url($url), PHP_URL_SCHEME));
	return in_array($scheme, array('http', 'https', 'udp', 'ws', 'wss', 'manual'), true) ? $scheme : 'unknown';
}

function multitracker_host($url)
{
	if (strtolower(multitracker_normalize_url($url)) === multitracker_manual_stats_url()) {
		return 'manual';
	}
	$host = strtolower(trim((string)parse_url(multitracker_normalize_url($url), PHP_URL_HOST), '[]'));
	return substr($host, 0, 190);
}

function multitracker_valid_announce_url($url)
{
	if ($url === '' || strlen($url) > 500) {
		return false;
	}
	if (!preg_match('#^(https?|udp|wss?)://#i', $url)) {
		return false;
	}
	return (bool)filter_var(preg_replace('#^(udp|wss?)://#i', 'http://', $url), FILTER_VALIDATE_URL);
}

function multitracker_is_server_reachable_url($url)
{
	if (multitracker_is_local_url($url)) {
		return true;
	}

	$parts = @parse_url($url);
	if (!is_array($parts) || empty($parts['host'])) {
		return false;
	}

	$host = strtolower(trim((string)$parts['host'], '[]'));
	if ($host === 'localhost' || substr($host, -6) === '.local') {
		return false;
	}

	if (filter_var($host, FILTER_VALIDATE_IP)) {
		return (bool)filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE);
	}

	return true;
}

function multitracker_url_key($url)
{
	$url = multitracker_normalize_url($url);
	$parts = @parse_url($url);
	if (!is_array($parts) || empty($parts['scheme']) || empty($parts['host'])) {
		return strtolower($url);
	}
	$scheme = strtolower($parts['scheme']);
	$host = strtolower(trim((string)$parts['host'], '[]'));
	if (strpos($host, ':') !== false && filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
		$host = '[' . $host . ']';
	}
	$port = isset($parts['port']) ? ':' . (int)$parts['port'] : '';
	$path = isset($parts['path']) ? $parts['path'] : '';
	$query = isset($parts['query']) ? '?' . $parts['query'] : '';
	return $scheme . '://' . $host . $port . $path . $query;
}

function multitracker_is_local_url($url)
{
	return multitracker_url_key($url) === multitracker_url_key(multitracker_primary_announce());
}

function multitracker_is_local_announce_family($url)
{
	$url_parts = @parse_url(multitracker_normalize_url($url));
	$primary_parts = @parse_url(multitracker_primary_announce());
	if (!is_array($url_parts) || !is_array($primary_parts)) {
		return false;
	}

	$url_host = strtolower($url_parts['host'] ?? '');
	$primary_host = strtolower($primary_parts['host'] ?? '');
	$url_path = $url_parts['path'] ?? '';
	$primary_path = $primary_parts['path'] ?? '';

	return $url_host === $primary_host && $url_path === $primary_path;
}

function multitracker_is_loopback_announce_url($url)
{
	$parts = @parse_url(multitracker_normalize_url($url));
	if (!is_array($parts) || empty($parts['host'])) {
		return false;
	}

	$host = strtolower(trim((string)$parts['host'], '[]'));
	$path = strtolower((string)($parts['path'] ?? ''));
	return in_array($host, array('localhost', '127.0.0.1', '::1'), true)
		&& preg_match('%/announce\.php$%i', $path);
}

function multitracker_is_local_announce_alias($url)
{
	return multitracker_is_local_url($url)
		|| multitracker_is_local_announce_family($url)
		|| multitracker_is_loopback_announce_url($url);
}

function multitracker_manual_stats_url()
{
	return 'manual://override';
}

function multitracker_is_manual_stats_url($url)
{
	return multitracker_url_key($url) === multitracker_manual_stats_url();
}

function multitracker_storage_announce_url($url)
{
	$url = multitracker_normalize_url($url);
	return multitracker_is_local_announce_alias($url) ? multitracker_primary_announce() : $url;
}

function multitracker_is_client_only_announce_url($url)
{
	$protocol = multitracker_protocol($url);
	if ($protocol === 'ws' || $protocol === 'wss') {
		return true;
	}
	if (stripos((string)$url, 'udp://') === 0 || multitracker_is_local_announce_alias($url)) {
		return false;
	}
	if (!multitracker_is_server_reachable_url($url)) {
		return true;
	}

	return false;
}

function multitracker_supports_server_scrape_url($url)
{
	if (multitracker_is_manual_stats_url($url)) {
		return true;
	}
	$protocol = multitracker_protocol($url);
	return in_array($protocol, array('http', 'https', 'udp'), true)
		&& multitracker_is_server_reachable_url($url)
		&& !multitracker_is_client_only_announce_url($url);
}

function multitracker_column_exists($table, $column)
{
	$res = sql_query("SHOW COLUMNS FROM `$table` LIKE " . sqlesc($column));
	return $res && mysqli_num_rows($res) > 0;
}

function multitracker_index_exists($table, $index)
{
	$res = sql_query("SHOW INDEX FROM `$table` WHERE Key_name = " . sqlesc($index));
	return $res && mysqli_num_rows($res) > 0;
}

function multitracker_ensure_schema()
{
	static $done = false;
	if ($done) {
		return;
	}
	$done = true;

	if (!defined('KZ_AUTO_MIGRATIONS') || KZ_AUTO_MIGRATIONS !== true) {
		return;
	}

	sql_query("
		CREATE TABLE IF NOT EXISTS torrent_trackers (
			id int(10) unsigned NOT NULL auto_increment,
			torrentid int(10) unsigned NOT NULL,
			announce_url varchar(500) NOT NULL,
			external_info_hash varchar(40) NOT NULL DEFAULT '',
			tracker_host varchar(190) NOT NULL DEFAULT '',
			protocol varchar(10) NOT NULL DEFAULT '',
			tier tinyint(3) unsigned NOT NULL DEFAULT 1,
			is_primary enum('yes','no') NOT NULL DEFAULT 'no',
			seeders int(10) unsigned NULL DEFAULT NULL,
			leechers int(10) unsigned NULL DEFAULT NULL,
			completed int(10) unsigned NULL DEFAULT NULL,
			last_checked datetime NULL DEFAULT NULL,
			last_success datetime NULL DEFAULT NULL,
			next_check datetime NULL DEFAULT NULL,
			last_response_ms int(10) unsigned NULL DEFAULT NULL,
			failures tinyint(3) unsigned NOT NULL DEFAULT 0,
			last_error varchar(255) NOT NULL DEFAULT '',
			enabled enum('yes','no') NOT NULL DEFAULT 'yes',
			PRIMARY KEY (id),
			UNIQUE KEY torrent_url (torrentid, announce_url(191)),
			KEY torrentid (torrentid),
			KEY enabled_checked (enabled, last_checked),
			KEY next_check (enabled, is_primary, next_check),
			KEY protocol_host (protocol, tracker_host),
			KEY due_trackers (enabled, is_primary, last_checked),
			KEY torrent_active (torrentid, enabled, is_primary, last_error(32))
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
	") or sqlerr(__FILE__, __LINE__);

	if (!multitracker_column_exists('torrent_trackers', 'external_info_hash')) {
		sql_query("ALTER TABLE torrent_trackers ADD external_info_hash varchar(40) NOT NULL DEFAULT '' AFTER announce_url") or sqlerr(__FILE__, __LINE__);
	}
	if (!multitracker_column_exists('torrent_trackers', 'tracker_host')) {
		sql_query("ALTER TABLE torrent_trackers ADD tracker_host varchar(190) NOT NULL DEFAULT '' AFTER external_info_hash") or sqlerr(__FILE__, __LINE__);
	}
	if (!multitracker_column_exists('torrent_trackers', 'protocol')) {
		sql_query("ALTER TABLE torrent_trackers ADD protocol varchar(10) NOT NULL DEFAULT '' AFTER tracker_host") or sqlerr(__FILE__, __LINE__);
	}
	if (!multitracker_column_exists('torrent_trackers', 'tier')) {
		sql_query("ALTER TABLE torrent_trackers ADD tier tinyint(3) unsigned NOT NULL DEFAULT 1 AFTER protocol") or sqlerr(__FILE__, __LINE__);
	}
	if (!multitracker_column_exists('torrent_trackers', 'last_success')) {
		sql_query("ALTER TABLE torrent_trackers ADD last_success datetime NULL DEFAULT NULL AFTER last_checked") or sqlerr(__FILE__, __LINE__);
	}
	if (!multitracker_column_exists('torrent_trackers', 'next_check')) {
		sql_query("ALTER TABLE torrent_trackers ADD next_check datetime NULL DEFAULT NULL AFTER last_success") or sqlerr(__FILE__, __LINE__);
	}
	if (!multitracker_column_exists('torrent_trackers', 'last_response_ms')) {
		sql_query("ALTER TABLE torrent_trackers ADD last_response_ms int(10) unsigned NULL DEFAULT NULL AFTER next_check") or sqlerr(__FILE__, __LINE__);
	}
	if (!multitracker_column_exists('torrent_trackers', 'failures')) {
		sql_query("ALTER TABLE torrent_trackers ADD failures tinyint(3) unsigned NOT NULL DEFAULT 0 AFTER last_response_ms") or sqlerr(__FILE__, __LINE__);
	}
	if (!multitracker_index_exists('torrent_trackers', 'next_check')) {
		sql_query("ALTER TABLE torrent_trackers ADD KEY next_check (enabled, is_primary, next_check)") or sqlerr(__FILE__, __LINE__);
	}
	if (!multitracker_index_exists('torrent_trackers', 'protocol_host')) {
		sql_query("ALTER TABLE torrent_trackers ADD KEY protocol_host (protocol, tracker_host)") or sqlerr(__FILE__, __LINE__);
	}
}

function multitracker_normalize_info_hash($hash)
{
	$hash = strtolower(trim((string)$hash));
	return preg_match('/^[a-f0-9]{40}$/', $hash) ? $hash : '';
}

function multitracker_extract_announces(array $dict)
{
	$primary = multitracker_primary_announce();
	$out = array($primary);

	if (!empty($dict['announce'])) {
		$out[] = $dict['announce'];
	}

	if (!empty($dict['announce-list']) && is_array($dict['announce-list'])) {
		foreach ($dict['announce-list'] as $tier) {
			if (is_array($tier)) {
				foreach ($tier as $url) {
					$out[] = $url;
				}
			} else {
				$out[] = $tier;
			}
		}
	}

	$seen = array();
	$urls = array();
	foreach ($out as $url) {
		$url = multitracker_storage_announce_url($url);
	if (!multitracker_valid_announce_url($url)) {
		continue;
	}
		$key = multitracker_url_key($url);
		if (isset($seen[$key])) {
			continue;
		}
		$seen[$key] = true;
		$urls[] = $url;
	}

	return $urls;
}

function multitracker_apply_announces_to_dict(array $dict, array $urls)
{
	$primary = multitracker_primary_announce();
	$ordered = multitracker_extract_announces(array('announce' => $primary, 'announce-list' => array($urls)));
	$dict['announce'] = $primary;

	$tiers = array(array($primary));
	foreach ($ordered as $url) {
		if (!multitracker_is_local_announce_alias($url)) {
			$tiers[] = array($url);
		}
	}
	$dict['announce-list'] = $tiers;

	return $dict;
}

function multitracker_recover_external_info_hash($torrentid, $local_info_hash = '')
{
	global $torrent_dir;

	$torrentid = (int)$torrentid;
	$local_info_hash = multitracker_normalize_info_hash($local_info_hash);
	$path = rtrim((string)$torrent_dir, '/\\') . '/' . $torrentid . '.torrent';
	if (!is_file($path) || !is_readable($path)) {
		return $local_info_hash;
	}

	if (!function_exists('bdecode')) {
		require_once ROOT_PATH . 'include/BDecode.php';
	}
	if (!function_exists('BEncode')) {
		require_once ROOT_PATH . 'include/BEncode.php';
	}

	$dict = bdecode((string)file_get_contents($path));
	if (!is_array($dict) || empty($dict['info']) || !is_array($dict['info'])) {
		return $local_info_hash;
	}

	$hash = multitracker_normalize_info_hash(sha1(BEncode($dict['info'])));
	return $hash !== '' ? $hash : $local_info_hash;
}

function multitracker_save_trackers($torrentid, array $urls, $external_info_hash = '', $source_filename = '')
{
	multitracker_ensure_schema();
	$torrentid = (int)$torrentid;
	if ($torrentid <= 0) {
		return;
	}

	$external_info_hash = multitracker_normalize_info_hash($external_info_hash);
	if ($external_info_hash === '') {
		$external_info_hash = multitracker_recover_external_info_hash($torrentid);
	}

	$urls = multitracker_extract_announces(array('announce-list' => array($urls)));
	$active = array();
	$values = array();
	foreach ($urls as $url) {
		$is_primary = multitracker_is_local_url($url) ? 'yes' : 'no';
		$row_external_hash = $is_primary === 'yes' ? '' : $external_info_hash;
		$host = multitracker_host($url);
		$protocol = multitracker_protocol($url);
		$active[] = sqlesc($url);
		$values[] = "($torrentid, " . sqlesc($url) . ", " . sqlesc($row_external_hash) . ", " . sqlesc($host) . ", " . sqlesc($protocol) . ", " . sqlesc($is_primary) . ", 'yes')";
	}

	if ($values) {
		sql_query("
			INSERT INTO torrent_trackers (torrentid, announce_url, external_info_hash, tracker_host, protocol, is_primary, enabled)
			VALUES " . implode(",\n", $values) . "
			ON DUPLICATE KEY UPDATE external_info_hash = VALUES(external_info_hash), tracker_host = VALUES(tracker_host), protocol = VALUES(protocol), is_primary = VALUES(is_primary), enabled = 'yes'
		") or sqlerr(__FILE__, __LINE__);
	}

	if ($active) {
		sql_query("UPDATE torrent_trackers SET enabled = 'no' WHERE torrentid = $torrentid AND announce_url NOT IN (" . implode(',', $active) . ") AND announce_url <> " . sqlesc(multitracker_manual_stats_url())) or sqlerr(__FILE__, __LINE__);
	}
	sql_query("UPDATE torrent_trackers SET is_primary = 'no' WHERE torrentid = $torrentid AND enabled = 'no' AND is_primary = 'yes'") or sqlerr(__FILE__, __LINE__);
	sql_query("UPDATE torrent_trackers SET is_primary = 'no', enabled = 'no' WHERE torrentid = $torrentid AND is_primary = 'yes' AND announce_url <> " . sqlesc(multitracker_primary_announce())) or sqlerr(__FILE__, __LINE__);
	multitracker_sync_torrent_totals($torrentid);
}

function multitracker_prune_unsupported_trackers($torrentid = 0)
{
	$torrentid = (int)$torrentid;
	$where = "is_primary = 'no' AND (enabled = 'yes' OR last_error = 'server stats unsupported')";
	if ($torrentid > 0) {
		sql_query("UPDATE torrent_trackers SET is_primary = 'no' WHERE torrentid = $torrentid AND enabled = 'no' AND is_primary = 'yes'") or sqlerr(__FILE__, __LINE__);
		$where .= " AND torrentid = $torrentid";
	} else {
		sql_query("UPDATE torrent_trackers SET is_primary = 'no' WHERE enabled = 'no' AND is_primary = 'yes'") or sqlerr(__FILE__, __LINE__);
	}

	$res = sql_query("SELECT id, torrentid, announce_url, enabled, seeders, leechers, last_error, next_check, failures FROM torrent_trackers WHERE $where") or sqlerr(__FILE__, __LINE__);
	$changed = array();
	while ($row = mysqli_fetch_assoc($res)) {
		$url = multitracker_normalize_url($row['announce_url']);
		$id = (int)$row['id'];
		$meta = 'tracker_host = ' . sqlesc(multitracker_host($url)) . ', protocol = ' . sqlesc(multitracker_protocol($url));
		$auto_disabled = $row['enabled'] !== 'yes' && $row['last_error'] === 'server stats unsupported';
		if (multitracker_supports_server_scrape_url($url)) {
			sql_query("UPDATE torrent_trackers SET $meta, enabled = 'yes', last_error = IF(last_error = 'server stats unsupported', '', last_error) WHERE id = $id AND (tracker_host = '' OR protocol = '' OR enabled <> 'yes' OR last_error = 'server stats unsupported')") or sqlerr(__FILE__, __LINE__);
			if (($row['seeders'] === null && $row['leechers'] === null && trim((string)$row['last_error']) === '') || $auto_disabled) {
				sql_query("UPDATE torrent_trackers SET next_check = NULL WHERE id = $id") or sqlerr(__FILE__, __LINE__);
			} elseif (trim((string)$row['last_error']) !== '' && trim((string)($row['next_check'] ?? '')) === '') {
				$delay = multitracker_failure_delay(max(1, (int)($row['failures'] ?? 1)));
				sql_query("UPDATE torrent_trackers SET next_check = DATE_ADD(NOW(), INTERVAL $delay SECOND) WHERE id = $id") or sqlerr(__FILE__, __LINE__);
			}
			if ($auto_disabled) {
				$changed[(int)$row['torrentid']] = true;
			}
			continue;
		}

		sql_query("UPDATE torrent_trackers SET $meta, enabled = 'yes', seeders = NULL, leechers = NULL, completed = NULL, failures = 0, last_error = '', last_checked = NOW(), next_check = DATE_ADD(NOW(), INTERVAL 7 DAY) WHERE id = $id AND (enabled <> 'yes' OR seeders IS NOT NULL OR leechers IS NOT NULL OR completed IS NOT NULL OR last_error <> '' OR tracker_host <> " . sqlesc(multitracker_host($url)) . " OR protocol <> " . sqlesc(multitracker_protocol($url)) . " OR next_check IS NULL)") or sqlerr(__FILE__, __LINE__);
		$changed[(int)$row['torrentid']] = true;
	}

	if ($changed) {
		multitracker_sync_torrent_totals_bulk(array_keys($changed));
	}
}

function multitracker_sync_torrent_totals($torrentid)
{
	$torrentid = (int)$torrentid;
	multitracker_sync_torrent_totals_bulk(array($torrentid));
}

function multitracker_sync_torrent_totals_bulk(array $torrentids)
{
	$ids = array();
	foreach ($torrentids as $torrentid) {
		$torrentid = (int)$torrentid;
		if ($torrentid > 0) {
			$ids[$torrentid] = true;
		}
	}

	if (!$ids) {
		return;
	}

	$id_sql = implode(',', array_keys($ids));
	sql_query("
		UPDATE torrents AS t
		LEFT JOIN (
			SELECT
				torrentid,
				COALESCE(SUM(COALESCE(seeders, 0)), 0) AS seeders,
					COALESCE(SUM(COALESCE(leechers, 0)), 0) AS leechers,
					MAX(IF(last_error = '' AND seeders IS NOT NULL AND leechers IS NOT NULL, last_checked, NULL)) AS last_checked,
				COUNT(*) AS tracker_count
			FROM torrent_trackers
			WHERE torrentid IN ($id_sql) AND enabled = 'yes' AND is_primary = 'no'
			GROUP BY torrentid
		) AS mt ON mt.torrentid = t.id
		SET t.remote_seeders = COALESCE(mt.seeders, 0),
		    t.remote_leechers = COALESCE(mt.leechers, 0),
		    t.last_mt_update = mt.last_checked,
		    t.multitracker = IF(COALESCE(mt.tracker_count, 0) > 0, 'yes', 'no')
		WHERE t.id IN ($id_sql)
		") or sqlerr(__FILE__, __LINE__);
	}

function multitracker_sync_local_peer_counts($torrentid)
{
	multitracker_ensure_schema();
	$torrentid = (int)$torrentid;
	if ($torrentid <= 0) {
		return false;
	}
	$cache_key = 'mtlocal:sync:' . $torrentid;
	if (function_exists('tracker_cache_get') && tracker_cache_get($cache_key, false)) {
		return true;
	}

	$res = sql_query("
		SELECT
			t.id,
			t.times_completed,
			COALESCE(SUM(p.seeder = 'yes'), 0) AS seeders,
			COALESCE(SUM(p.seeder <> 'yes'), 0) AS leechers
		FROM torrents AS t
		LEFT JOIN peers AS p ON p.torrent = t.id
		WHERE t.id = $torrentid
		GROUP BY t.id, t.times_completed
		LIMIT 1
	") or sqlerr(__FILE__, __LINE__);
	$row = mysqli_fetch_assoc($res);
	if (!$row) {
		return false;
	}

	$seeders = (int)$row['seeders'];
	$leechers = (int)$row['leechers'];
	sql_query("UPDATE torrents SET seeders = $seeders, leechers = $leechers WHERE id = $torrentid AND (seeders <> $seeders OR leechers <> $leechers)") or sqlerr(__FILE__, __LINE__);
	multitracker_sync_local_tracker($torrentid, $seeders, $leechers, (int)$row['times_completed']);
	if (function_exists('tracker_cache_set')) {
		tracker_cache_set($cache_key, 1, 15);
	}
	return true;
}

function multitracker_sync_local_tracker($torrentid, $seeders, $leechers, $completed)
{
	multitracker_ensure_schema();
	$torrentid = (int)$torrentid;
	$url = multitracker_primary_announce();
	$host = multitracker_host($url);
	$protocol = multitracker_protocol($url);
	sql_query("
		INSERT INTO torrent_trackers (torrentid, announce_url, tracker_host, protocol, is_primary, seeders, leechers, completed, last_checked, last_success, last_error, enabled)
		VALUES ($torrentid, " . sqlesc($url) . ", " . sqlesc($host) . ", " . sqlesc($protocol) . ", 'yes', " . (int)$seeders . ", " . (int)$leechers . ", " . (int)$completed . ", NOW(), NOW(), '', 'yes')
		ON DUPLICATE KEY UPDATE tracker_host = VALUES(tracker_host), protocol = VALUES(protocol), is_primary = 'yes', seeders = VALUES(seeders), leechers = VALUES(leechers), completed = VALUES(completed), last_checked = NOW(), last_success = NOW(), last_error = '', enabled = 'yes'
	") or sqlerr(__FILE__, __LINE__);
}

function multitracker_set_manual_stats($torrentid, $seeders, $leechers, $completed = 0)
{
	multitracker_ensure_schema();
	$torrentid = (int)$torrentid;
	if ($torrentid <= 0) {
		return false;
	}
	$seeders = max(0, (int)$seeders);
	$leechers = max(0, (int)$leechers);
	$completed = max(0, (int)$completed);
	$url = multitracker_manual_stats_url();
	sql_query("
		INSERT INTO torrent_trackers (torrentid, announce_url, tracker_host, protocol, is_primary, seeders, leechers, completed, last_checked, last_success, next_check, failures, last_error, enabled)
		VALUES ($torrentid, " . sqlesc($url) . ", 'manual', 'manual', 'no', $seeders, $leechers, $completed, NOW(), NOW(), DATE_ADD(NOW(), INTERVAL 7 DAY), 0, '', 'yes')
		ON DUPLICATE KEY UPDATE tracker_host = 'manual', protocol = 'manual', is_primary = 'no', seeders = VALUES(seeders), leechers = VALUES(leechers), completed = VALUES(completed), last_checked = NOW(), last_success = NOW(), next_check = VALUES(next_check), failures = 0, last_error = '', enabled = 'yes'
	") or sqlerr(__FILE__, __LINE__);
	multitracker_sync_torrent_totals($torrentid);
	return true;
}

function multitracker_get_trackers($torrentid)
{
	multitracker_ensure_schema();
	multitracker_sync_local_peer_counts($torrentid);
	multitracker_prune_unsupported_trackers($torrentid);
	$rows = array();
	$res = sql_query("SELECT * FROM torrent_trackers WHERE torrentid = " . (int)$torrentid . " ORDER BY (is_primary = 'yes') DESC, id ASC") or sqlerr(__FILE__, __LINE__);
	while ($row = mysqli_fetch_assoc($res)) {
		$rows[] = $row;
	}
	return $rows;
}

function multitracker_external_textarea_value($torrentid)
{
	$urls = array();
	foreach (multitracker_get_trackers($torrentid) as $row) {
		if ($row['is_primary'] !== 'yes' && $row['enabled'] === 'yes' && !multitracker_is_manual_stats_url($row['announce_url'] ?? '')) {
			$urls[] = $row['announce_url'];
		}
	}
	return implode("\n", $urls);
}

function multitracker_parse_posted_urls($text)
{
	$urls = preg_split('/[\r\n]+/', (string)$text, -1, PREG_SPLIT_NO_EMPTY);
	$out = array(multitracker_primary_announce());
	foreach ($urls as $url) {
		$url = multitracker_normalize_url($url);
		if ($url !== '' && multitracker_valid_announce_url($url)) {
			$out[] = $url;
		}
	}
	return multitracker_extract_announces(array('announce-list' => array($out)));
}

function multitracker_rewrite_torrent_file_announces($torrentid, array $urls)
{
	global $torrent_dir;
	require_once ROOT_PATH . 'include/BDecode.php';
	require_once ROOT_PATH . 'include/BEncode.php';

	$path = rtrim((string)$torrent_dir, '/\\') . '/' . (int)$torrentid . '.torrent';
	if (!is_file($path) || !is_readable($path)) {
		return false;
	}
	$dict = bdecode((string)file_get_contents($path));
	if (!is_array($dict) || empty($dict['info'])) {
		return false;
	}
	$dict = multitracker_apply_announces_to_dict($dict, $urls);
	return file_put_contents($path, BEncode($dict)) !== false;
}

function multitracker_render_details_block($torrentid)
{
	return multitracker_render_details_block_from_rows($torrentid, multitracker_get_trackers($torrentid));
}

function multitracker_render_details_block_from_rows($torrentid, array $rows)
{
	foreach ($rows as $idx => $row) {
		if (($row['enabled'] ?? 'yes') !== 'yes') {
			unset($rows[$idx]);
			continue;
		}
		if (($row['is_primary'] ?? 'no') === 'yes') {
			continue;
		}
	}

	if (!$rows) {
		return '';
	}

	$html = '<div class="bx1"><ul class="men"><li class="tp2 b">Трекеры</li><li><table class="tables1 w100p">';
	$html .= '<tr><td class="b">URL</td><td class="b center">Статус</td><td class="b center">Сиды</td><td class="b center">Пиры/личи</td><td class="b center">Проверен</td></tr>';
	foreach ($rows as $row) {
		$is_primary = $row['is_primary'] === 'yes';
		if (!$is_primary && (multitracker_is_client_only_announce_url($row['announce_url'] ?? '') || multitracker_is_client_only_error($row['last_error'] ?? ''))) {
			$row['last_error'] = '';
		}
		$client_only = !$is_primary && !multitracker_supports_server_scrape_url($row['announce_url'] ?? '');
		$manual = !$is_primary && multitracker_is_manual_stats_url($row['announce_url'] ?? '');
		$status = $is_primary ? 'локальный' : ($manual ? 'ручной' : ($client_only ? 'клиентский' : ($row['enabled'] === 'yes' ? (trim((string)$row['last_error']) === '' ? 'ok' : 'ошибка') : 'отключен')));
		$seeders = $is_primary || $row['seeders'] !== null ? (int)$row['seeders'] : 'н/д';
		$leechers = $is_primary || $row['leechers'] !== null ? (int)$row['leechers'] : 'н/д';
		$checked = multitracker_h(multitracker_format_checked_at($row['last_checked'] ?? ''));
		$error = (!$is_primary && trim((string)$row['last_error']) !== '') ? '<div class="small red">' . multitracker_h($row['last_error']) . '</div>' : '';
		$url_label = $manual ? 'ручная статистика админки' : $row['announce_url'];
		$html .= '<tr><td>' . ($is_primary ? '<b>наш трекер</b><br>' : '') . '<span title="' . multitracker_h($row['announce_url']) . '">' . multitracker_h($url_label) . '</span>' . $error . '</td>';
		$html .= '<td class="center">' . multitracker_h($status) . '</td><td class="center green b">' . $seeders . '</td><td class="center red b">' . $leechers . '</td><td class="center">' . $checked . '</td></tr>';
	}
	$html .= '</table></li>';
	if (function_exists('get_user_class') && get_user_class() >= UC_MODERATOR) {
		$html .= '<li class="center"><form method="post" action="details.php?id=' . (int)$torrentid . '">';
		$html .= '<input type="hidden" name="mt_force_update" value="1">';
		$html .= '<input type="submit" class="buttonS" value="Принудительно обновить мультитрекер">';
		$html .= '</form></li>';
	}
	return $html . '</ul></div>';
}

function multitracker_scrape_tracker($url, $info_hash)
{
	$url = multitracker_normalize_url($url);
	if (!multitracker_supports_server_scrape_url($url)) {
		throw new Exception('client-only announce');
	}
	if (stripos($url, 'udp://') === 0) {
		require_once ROOT_PATH . 'include/scraper/udptscraper.php';
		static $udp_scraper = null;
		if ($udp_scraper === null) {
			$udp_scraper = new udptscraper(KZ_MT_UDP_TIMEOUT);
		}
		$scraper = $udp_scraper;
	} else {
		require_once ROOT_PATH . 'include/scraper/httptscraper.php';
		static $http_scraper = null;
		if ($http_scraper === null) {
			$http_scraper = new httptscraper(KZ_MT_HTTP_TIMEOUT, 65536);
		}
		$scraper = $http_scraper;
	}
	$result = $scraper->scrape($url, $info_hash);
	return !empty($result[$info_hash]) && is_array($result[$info_hash]) ? $result[$info_hash] : false;
}

function multitracker_failure_delay($failures)
{
	$failures = max(1, min(8, (int)$failures));
	return min(24 * 3600, KZ_MT_TTL * (1 << ($failures - 1)));
}

function multitracker_tracker_update_set($info_hash, $stats = null, $error = '', $response_ms = null, $current_failures = 0)
{
	$set = array();
	$info_hash = multitracker_normalize_info_hash($info_hash);
	if ($info_hash !== '') {
		$set[] = 'external_info_hash = ' . sqlesc($info_hash);
	}

	if (is_array($stats)) {
		$set[] = 'seeders = ' . (int)$stats['seeders'];
		$set[] = 'leechers = ' . (int)$stats['leechers'];
		$set[] = 'completed = ' . (int)$stats['completed'];
		$set[] = 'last_checked = NOW()';
		$set[] = 'last_success = NOW()';
		$set[] = 'next_check = DATE_ADD(NOW(), INTERVAL ' . (int)KZ_MT_TTL . ' SECOND)';
		$set[] = 'failures = 0';
		$set[] = "last_error = ''";
	} else {
		$failures = min(255, max(0, (int)$current_failures) + 1);
		$set[] = 'last_checked = NOW()';
		$set[] = 'next_check = DATE_ADD(NOW(), INTERVAL ' . multitracker_failure_delay($failures) . ' SECOND)';
		$set[] = 'failures = ' . $failures;
		$set[] = 'last_error = ' . sqlesc(substr((string)$error, 0, 255));
	}
	if ($response_ms !== null) {
		$set[] = 'last_response_ms = ' . max(0, (int)$response_ms);
	}

	return implode(', ', $set);
}

function multitracker_client_only_update_set($info_hash)
{
	$set = array();
	$info_hash = multitracker_normalize_info_hash($info_hash);
	if ($info_hash !== '') {
		$set[] = 'external_info_hash = ' . sqlesc($info_hash);
	}

	$set[] = 'seeders = NULL';
	$set[] = 'leechers = NULL';
	$set[] = 'completed = NULL';
	$set[] = 'last_checked = NOW()';
	$set[] = 'next_check = DATE_ADD(NOW(), INTERVAL 7 DAY)';
	$set[] = 'failures = 0';
	$set[] = 'last_response_ms = NULL';
	$set[] = "last_error = ''";
	return implode(', ', $set);
}

function multitracker_update_tracker_row(array $row)
{
	$tracker_id = (int)$row['id'];
	$torrentid = (int)$row['torrentid'];
	$info_hash = multitracker_normalize_info_hash($row['external_info_hash'] ?? '');
	if ($info_hash === '') {
		$info_hash = multitracker_recover_external_info_hash($torrentid, $row['info_hash'] ?? '');
	}

	if (multitracker_is_manual_stats_url($row['announce_url'] ?? '')) {
		sql_query("UPDATE torrent_trackers SET last_checked = NOW(), last_success = NOW(), next_check = DATE_ADD(NOW(), INTERVAL 7 DAY), failures = 0, last_error = '', enabled = 'yes' WHERE id = $tracker_id") or sqlerr(__FILE__, __LINE__);
		return 'success';
	}

	if (!multitracker_supports_server_scrape_url($row['announce_url'] ?? '')) {
		sql_query("UPDATE torrent_trackers SET " . multitracker_client_only_update_set($info_hash) . " WHERE id = $tracker_id") or sqlerr(__FILE__, __LINE__);
		return 'client_only';
	}

	$started = microtime(true);
	try {
		if ($info_hash === '') {
			throw new Exception('invalid info hash');
		}
		$stats = multitracker_scrape_tracker($row['announce_url'], $info_hash);
		if (!$stats) {
			throw new Exception('no scrape data');
		}
		$response_ms = (int)round((microtime(true) - $started) * 1000);
		sql_query("UPDATE torrent_trackers SET " . multitracker_tracker_update_set($info_hash, $stats, '', $response_ms) . " WHERE id = $tracker_id") or sqlerr(__FILE__, __LINE__);
		return 'success';
	} catch (Exception $e) {
		$response_ms = (int)round((microtime(true) - $started) * 1000);
		if (multitracker_is_client_only_error($e->getMessage())) {
			sql_query("UPDATE torrent_trackers SET " . multitracker_client_only_update_set($info_hash) . " WHERE id = $tracker_id") or sqlerr(__FILE__, __LINE__);
			return 'client_only';
		}
		sql_query("UPDATE torrent_trackers SET " . multitracker_tracker_update_set($info_hash, null, $e->getMessage(), $response_ms, (int)($row['failures'] ?? 0)) . " WHERE id = $tracker_id") or sqlerr(__FILE__, __LINE__);
		return 'error';
	}
}

function multitracker_update_due_trackers($limit = 25, $per_host = 4)
{
	multitracker_ensure_schema();
	multitracker_prune_unsupported_trackers();
	$limit = max(1, min(200, (int)$limit));
	$per_host = max(1, min(25, (int)$per_host));
	$sql_limit = $limit * 4;
	$res = sql_query("
		SELECT tt.id, tt.torrentid, tt.announce_url, tt.external_info_hash, tt.tracker_host, tt.protocol, tt.failures, t.info_hash
		FROM torrent_trackers AS tt
		INNER JOIN torrents AS t ON t.id = tt.torrentid
		WHERE tt.enabled = 'yes'
		  AND tt.is_primary = 'no'
		  AND (tt.next_check IS NULL OR tt.next_check <= NOW())
		  AND (tt.last_checked IS NULL OR tt.last_checked < DATE_SUB(NOW(), INTERVAL 60 SECOND))
		ORDER BY tt.next_check IS NULL DESC, tt.next_check ASC, tt.last_checked IS NULL DESC, tt.last_checked ASC
		LIMIT $sql_limit
	") or sqlerr(__FILE__, __LINE__);

	$seen_torrents = array();
	$seen_hosts = array();
	$result = array('success' => 0, 'errors' => 0, 'client_only' => 0, 'total' => 0);
	while ($row = mysqli_fetch_assoc($res)) {
		if ($result['total'] >= $limit) {
			break;
		}
		$host = (string)($row['tracker_host'] ?: multitracker_host($row['announce_url']));
		if ($host !== '') {
			$seen_hosts[$host] = ($seen_hosts[$host] ?? 0) + 1;
			if ($seen_hosts[$host] > $per_host) {
				continue;
			}
		}
		$status = multitracker_update_tracker_row($row);
		$result['total']++;
		if ($status === 'success') {
			$result['success']++;
		} elseif ($status === 'client_only') {
			$result['client_only']++;
		} else {
			$result['errors']++;
		}
		$seen_torrents[(int)$row['torrentid']] = true;
	}

	multitracker_sync_torrent_totals_bulk(array_keys($seen_torrents));
	return $result;
}

function multitracker_update_torrent_trackers($torrentid, $force = false, $max_seconds = KZ_MT_MANUAL_BUDGET)
{
	multitracker_ensure_schema();
	$torrentid = (int)$torrentid;
	if ($torrentid <= 0) {
		return array('success' => 0, 'errors' => 0, 'client_only' => 0, 'skipped' => 0, 'total' => 0);
	}
	multitracker_sync_local_peer_counts($torrentid);
	multitracker_prune_unsupported_trackers($torrentid);
	$due_sql = $force ? '' : " AND (tt.next_check IS NULL OR tt.next_check <= NOW())";
	$skipped = 0;
	if (!$force) {
		$skip_res = sql_query("
			SELECT COUNT(*) AS c
			FROM torrent_trackers AS tt
			WHERE tt.torrentid = $torrentid
			  AND tt.enabled = 'yes'
			  AND tt.is_primary = 'no'
			  AND tt.next_check IS NOT NULL
			  AND tt.next_check > NOW()
		") or sqlerr(__FILE__, __LINE__);
		$skip_row = mysqli_fetch_assoc($skip_res);
		$skipped = (int)($skip_row['c'] ?? 0);
	}

	$manual_url = sqlesc(multitracker_manual_stats_url());
	$res = sql_query("
		SELECT tt.id, tt.torrentid, tt.announce_url, tt.external_info_hash, tt.tracker_host, tt.protocol, tt.failures, t.info_hash
		FROM torrent_trackers AS tt
		INNER JOIN torrents AS t ON t.id = tt.torrentid
		WHERE tt.torrentid = $torrentid AND tt.enabled = 'yes' AND tt.is_primary = 'no' $due_sql
		ORDER BY (tt.announce_url = $manual_url) DESC, tt.id ASC
	") or sqlerr(__FILE__, __LINE__);

	$success = 0;
	$errors = 0;
	$client_only = 0;
	$total = 0;
	$started = microtime(true);
	$max_seconds = max(1, (int)$max_seconds);
	while ($row = mysqli_fetch_assoc($res)) {
		if ((microtime(true) - $started) >= $max_seconds) {
			$skipped++;
			continue;
		}
		$total++;
		$status = multitracker_update_tracker_row($row);
		if ($status === 'success') {
			$success++;
		} elseif ($status === 'client_only') {
			$client_only++;
		} else {
			$errors++;
		}
	}

	multitracker_sync_torrent_totals($torrentid);
	return array('success' => $success, 'errors' => $errors, 'client_only' => $client_only, 'skipped' => $skipped, 'total' => $total);
}

?>
