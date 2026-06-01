<?php

if (!defined('KZ_MT_TTL')) {
	define('KZ_MT_TTL', 20 * 60);
}

function kz_mt_h($value)
{
	return function_exists('htmlspecialchars_uni') ? htmlspecialchars_uni((string)$value) : htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function kz_mt_primary_announce()
{
	global $announce_urls, $DEFAULTBASEURL;

	if (!empty($announce_urls[0])) {
		return kz_mt_normalize_url($announce_urls[0]);
	}

	return kz_mt_normalize_url(rtrim((string)$DEFAULTBASEURL, '/') . '/announce.php');
}

function kz_mt_normalize_url($url)
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

function kz_mt_valid_announce_url($url)
{
	if ($url === '' || strlen($url) > 500) {
		return false;
	}
	if (!preg_match('#^(https?|udp)://#i', $url)) {
		return false;
	}
	return (bool)filter_var(preg_replace('#^udp://#i', 'http://', $url), FILTER_VALIDATE_URL);
}

function kz_mt_url_key($url)
{
	$url = kz_mt_normalize_url($url);
	$parts = @parse_url($url);
	if (!is_array($parts) || empty($parts['scheme']) || empty($parts['host'])) {
		return strtolower($url);
	}
	$scheme = strtolower($parts['scheme']);
	$host = strtolower($parts['host']);
	$port = isset($parts['port']) ? ':' . (int)$parts['port'] : '';
	$path = isset($parts['path']) ? $parts['path'] : '';
	$query = isset($parts['query']) ? '?' . $parts['query'] : '';
	return $scheme . '://' . $host . $port . $path . $query;
}

function kz_mt_is_local_url($url)
{
	return kz_mt_url_key($url) === kz_mt_url_key(kz_mt_primary_announce());
}

function kz_mt_column_exists($table, $column)
{
	$res = sql_query("SHOW COLUMNS FROM `$table` LIKE " . sqlesc($column));
	return $res && mysqli_num_rows($res) > 0;
}

function kz_mt_ensure_schema()
{
	static $done = false;
	if ($done) {
		return;
	}
	$done = true;

	sql_query("
		CREATE TABLE IF NOT EXISTS torrent_trackers (
			id int(10) unsigned NOT NULL auto_increment,
			torrentid int(10) unsigned NOT NULL,
			announce_url varchar(500) NOT NULL,
			external_info_hash varchar(40) NOT NULL DEFAULT '',
			is_primary enum('yes','no') NOT NULL DEFAULT 'no',
			seeders int(10) unsigned NULL DEFAULT NULL,
			leechers int(10) unsigned NULL DEFAULT NULL,
			completed int(10) unsigned NULL DEFAULT NULL,
			last_checked datetime NULL DEFAULT NULL,
			last_error varchar(255) NOT NULL DEFAULT '',
			enabled enum('yes','no') NOT NULL DEFAULT 'yes',
			PRIMARY KEY (id),
			UNIQUE KEY torrent_url (torrentid, announce_url(191)),
			KEY torrentid (torrentid),
			KEY enabled_checked (enabled, last_checked),
			KEY due_trackers (enabled, is_primary, last_checked),
			KEY torrent_active (torrentid, enabled, is_primary, last_error(32))
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
	") or sqlerr(__FILE__, __LINE__);

	if (!kz_mt_column_exists('torrent_trackers', 'external_info_hash')) {
		sql_query("ALTER TABLE torrent_trackers ADD external_info_hash varchar(40) NOT NULL DEFAULT '' AFTER announce_url") or sqlerr(__FILE__, __LINE__);
	}
}

function kz_mt_normalize_info_hash($hash)
{
	$hash = strtolower(trim((string)$hash));
	return preg_match('/^[a-f0-9]{40}$/', $hash) ? $hash : '';
}

function kz_mt_extract_announces(array $dict)
{
	$primary = kz_mt_primary_announce();
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
		$url = kz_mt_normalize_url($url);
		if (!kz_mt_valid_announce_url($url)) {
			continue;
		}
		$key = kz_mt_url_key($url);
		if (isset($seen[$key])) {
			continue;
		}
		$seen[$key] = true;
		$urls[] = $url;
	}

	return $urls;
}

function kz_mt_apply_announces_to_dict(array $dict, array $urls)
{
	$primary = kz_mt_primary_announce();
	$ordered = kz_mt_extract_announces(array('announce' => $primary, 'announce-list' => array($urls)));
	$dict['announce'] = $primary;

	$tiers = array(array($primary));
	foreach ($ordered as $url) {
		if (!kz_mt_is_local_url($url)) {
			$tiers[] = array($url);
		}
	}
	$dict['announce-list'] = $tiers;

	unset($dict['nodes'], $dict['azureus_properties']);
	return $dict;
}

function kz_mt_recover_external_info_hash($torrentid, $local_info_hash = '')
{
	global $torrent_dir;

	$torrentid = (int)$torrentid;
	$local_info_hash = kz_mt_normalize_info_hash($local_info_hash);
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

	$info = $dict['info'];
	unset($info['private'], $info['source']);
	$hash = kz_mt_normalize_info_hash(sha1(BEncode($info)));
	return $hash !== '' ? $hash : $local_info_hash;
}

function kz_mt_save_trackers($torrentid, array $urls, $external_info_hash = '')
{
	kz_mt_ensure_schema();
	$torrentid = (int)$torrentid;
	if ($torrentid <= 0) {
		return;
	}

	$external_info_hash = kz_mt_normalize_info_hash($external_info_hash);
	if ($external_info_hash === '') {
		$external_info_hash = kz_mt_recover_external_info_hash($torrentid);
	}

	$urls = kz_mt_extract_announces(array('announce-list' => array($urls)));
	$active = array();
	$values = array();
	foreach ($urls as $url) {
		$is_primary = kz_mt_is_local_url($url) ? 'yes' : 'no';
		$row_external_hash = $is_primary === 'yes' ? '' : $external_info_hash;
		$active[] = sqlesc($url);
		$values[] = "($torrentid, " . sqlesc($url) . ", " . sqlesc($row_external_hash) . ", " . sqlesc($is_primary) . ", 'yes')";
	}

	if ($values) {
		sql_query("
			INSERT INTO torrent_trackers (torrentid, announce_url, external_info_hash, is_primary, enabled)
			VALUES " . implode(",\n", $values) . "
			ON DUPLICATE KEY UPDATE external_info_hash = VALUES(external_info_hash), is_primary = VALUES(is_primary), enabled = 'yes'
		") or sqlerr(__FILE__, __LINE__);
	}

	if ($active) {
		sql_query("UPDATE torrent_trackers SET enabled = 'no' WHERE torrentid = $torrentid AND announce_url NOT IN (" . implode(',', $active) . ")") or sqlerr(__FILE__, __LINE__);
	}
	kz_mt_sync_torrent_totals($torrentid);
}

function kz_mt_sync_torrent_totals($torrentid)
{
	$torrentid = (int)$torrentid;
	kz_mt_sync_torrent_totals_bulk(array($torrentid));
}

function kz_mt_sync_torrent_totals_bulk(array $torrentids)
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
				COALESCE(SUM(IF(last_error = '', COALESCE(seeders, 0), 0)), 0) AS seeders,
				COALESCE(SUM(IF(last_error = '', COALESCE(leechers, 0), 0)), 0) AS leechers,
				MAX(IF(last_error = '', last_checked, NULL)) AS last_checked,
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

function kz_mt_sync_local_tracker($torrentid, $seeders, $leechers, $completed)
{
	kz_mt_ensure_schema();
	$torrentid = (int)$torrentid;
	$url = kz_mt_primary_announce();
	sql_query("
		INSERT INTO torrent_trackers (torrentid, announce_url, is_primary, seeders, leechers, completed, last_checked, last_error, enabled)
		VALUES ($torrentid, " . sqlesc($url) . ", 'yes', " . (int)$seeders . ", " . (int)$leechers . ", " . (int)$completed . ", NOW(), '', 'yes')
		ON DUPLICATE KEY UPDATE is_primary = 'yes', seeders = VALUES(seeders), leechers = VALUES(leechers), completed = VALUES(completed), last_checked = NOW(), last_error = '', enabled = 'yes'
	") or sqlerr(__FILE__, __LINE__);
}

function kz_mt_get_trackers($torrentid)
{
	kz_mt_ensure_schema();
	$rows = array();
	$res = sql_query("SELECT * FROM torrent_trackers WHERE torrentid = " . (int)$torrentid . " ORDER BY (is_primary = 'yes') DESC, id ASC") or sqlerr(__FILE__, __LINE__);
	while ($row = mysqli_fetch_assoc($res)) {
		$rows[] = $row;
	}
	return $rows;
}

function kz_mt_external_textarea_value($torrentid)
{
	$urls = array();
	foreach (kz_mt_get_trackers($torrentid) as $row) {
		if ($row['is_primary'] !== 'yes' && $row['enabled'] === 'yes') {
			$urls[] = $row['announce_url'];
		}
	}
	return implode("\n", $urls);
}

function kz_mt_parse_posted_urls($text)
{
	$urls = preg_split('/[\r\n]+/', (string)$text, -1, PREG_SPLIT_NO_EMPTY);
	$out = array(kz_mt_primary_announce());
	foreach ($urls as $url) {
		$url = kz_mt_normalize_url($url);
		if ($url !== '' && kz_mt_valid_announce_url($url)) {
			$out[] = $url;
		}
	}
	return kz_mt_extract_announces(array('announce-list' => array($out)));
}

function kz_mt_rewrite_torrent_file_announces($torrentid, array $urls)
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
	$dict = kz_mt_apply_announces_to_dict($dict, $urls);
	return file_put_contents($path, BEncode($dict)) !== false;
}

function kz_mt_render_details_block($torrentid)
{
	$rows = kz_mt_get_trackers($torrentid);
	if (!$rows) {
		return '';
	}

	$html = '<div class="bx1"><ul class="men"><li class="tp2 b">Трекеры</li><li><table class="tables1 w100p">';
	$html .= '<tr><td class="b">URL</td><td class="b center">Статус</td><td class="b center">Сиды</td><td class="b center">Пиры/личи</td><td class="b center">Проверен</td></tr>';
	foreach ($rows as $row) {
		$is_primary = $row['is_primary'] === 'yes';
		$status = $is_primary ? 'локальный' : ($row['enabled'] === 'yes' ? (trim((string)$row['last_error']) === '' ? 'ok' : 'ошибка') : 'отключен');
		$seeders = $is_primary || $row['seeders'] !== null ? (int)$row['seeders'] : 'н/д';
		$leechers = $is_primary || $row['leechers'] !== null ? (int)$row['leechers'] : 'н/д';
		$checked = !empty($row['last_checked']) ? kz_mt_h($row['last_checked']) : 'н/д';
		$error = (!$is_primary && trim((string)$row['last_error']) !== '') ? '<div class="small red">' . kz_mt_h($row['last_error']) . '</div>' : '';
		$html .= '<tr><td>' . ($is_primary ? '<b>наш трекер</b><br>' : '') . '<span title="' . kz_mt_h($row['announce_url']) . '">' . kz_mt_h($row['announce_url']) . '</span>' . $error . '</td>';
		$html .= '<td class="center">' . kz_mt_h($status) . '</td><td class="center green b">' . $seeders . '</td><td class="center red b">' . $leechers . '</td><td class="center">' . $checked . '</td></tr>';
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

function kz_mt_scrape_tracker($url, $info_hash)
{
	$url = kz_mt_normalize_url($url);
	if (stripos($url, 'udp://') === 0) {
		require_once ROOT_PATH . 'include/scraper/udptscraper.php';
		static $udp_scraper = null;
		if ($udp_scraper === null) {
			$udp_scraper = new udptscraper(10);
		}
		$scraper = $udp_scraper;
	} else {
		require_once ROOT_PATH . 'include/scraper/httptscraper.php';
		static $http_scraper = null;
		if ($http_scraper === null) {
			$http_scraper = new httptscraper(12, 65536);
		}
		$scraper = $http_scraper;
	}
	$result = $scraper->scrape($url, $info_hash);
	return !empty($result[$info_hash]) && is_array($result[$info_hash]) ? $result[$info_hash] : false;
}

function kz_mt_tracker_update_set($info_hash, $stats = null, $error = '')
{
	$set = array();
	$info_hash = kz_mt_normalize_info_hash($info_hash);
	if ($info_hash !== '') {
		$set[] = 'external_info_hash = ' . sqlesc($info_hash);
	}

	if (is_array($stats)) {
		$set[] = 'seeders = ' . (int)$stats['seeders'];
		$set[] = 'leechers = ' . (int)$stats['leechers'];
		$set[] = 'completed = ' . (int)$stats['completed'];
		$set[] = 'last_checked = NOW()';
		$set[] = "last_error = ''";
	} else {
		$set[] = 'last_checked = NOW()';
		$set[] = 'last_error = ' . sqlesc(substr((string)$error, 0, 255));
	}

	return implode(', ', $set);
}

function kz_mt_update_due_trackers($limit = 25)
{
	kz_mt_ensure_schema();
	$limit = max(1, min(100, (int)$limit));
	$res = sql_query("
		SELECT tt.id, tt.torrentid, tt.announce_url, tt.external_info_hash, t.info_hash
		FROM torrent_trackers AS tt
		INNER JOIN torrents AS t ON t.id = tt.torrentid
		WHERE tt.enabled = 'yes'
		  AND tt.is_primary = 'no'
		  AND (tt.last_checked IS NULL OR tt.last_checked < DATE_SUB(NOW(), INTERVAL " . (int)ceil(KZ_MT_TTL / 60) . " MINUTE))
		ORDER BY tt.last_checked IS NULL DESC, tt.last_checked ASC
		LIMIT $limit
	") or sqlerr(__FILE__, __LINE__);

	$seen_torrents = array();
	while ($row = mysqli_fetch_assoc($res)) {
		$tracker_id = (int)$row['id'];
		$torrentid = (int)$row['torrentid'];
		$info_hash = kz_mt_normalize_info_hash($row['external_info_hash'] ?? '');
		if ($info_hash === '') {
			$info_hash = kz_mt_recover_external_info_hash($torrentid, $row['info_hash'] ?? '');
		}
		try {
			if ($info_hash === '') {
				throw new Exception('invalid info hash');
			}
			$stats = kz_mt_scrape_tracker($row['announce_url'], $info_hash);
			if (!$stats) {
				throw new Exception('no scrape data');
			}
			sql_query("UPDATE torrent_trackers SET " . kz_mt_tracker_update_set($info_hash, $stats) . " WHERE id = $tracker_id") or sqlerr(__FILE__, __LINE__);
		} catch (Exception $e) {
			sql_query("UPDATE torrent_trackers SET " . kz_mt_tracker_update_set($info_hash, null, $e->getMessage()) . " WHERE id = $tracker_id") or sqlerr(__FILE__, __LINE__);
		}
		$seen_torrents[$torrentid] = true;
	}

	kz_mt_sync_torrent_totals_bulk(array_keys($seen_torrents));
}

function kz_mt_update_torrent_trackers($torrentid)
{
	kz_mt_ensure_schema();
	$torrentid = (int)$torrentid;
	if ($torrentid <= 0) {
		return array('success' => 0, 'errors' => 0, 'total' => 0);
	}

	$res = sql_query("
		SELECT tt.id, tt.announce_url, tt.external_info_hash, t.info_hash
		FROM torrent_trackers AS tt
		INNER JOIN torrents AS t ON t.id = tt.torrentid
		WHERE tt.torrentid = $torrentid AND tt.enabled = 'yes' AND tt.is_primary = 'no'
		ORDER BY tt.id ASC
	") or sqlerr(__FILE__, __LINE__);

	$success = 0;
	$errors = 0;
	$total = 0;
	while ($row = mysqli_fetch_assoc($res)) {
		$total++;
		$tracker_id = (int)$row['id'];
		$info_hash = kz_mt_normalize_info_hash($row['external_info_hash'] ?? '');
		if ($info_hash === '') {
			$info_hash = kz_mt_recover_external_info_hash($torrentid, $row['info_hash'] ?? '');
		}
		try {
			if ($info_hash === '') {
				throw new Exception('invalid info hash');
			}
			$stats = kz_mt_scrape_tracker($row['announce_url'], $info_hash);
			if (!$stats) {
				throw new Exception('no scrape data');
			}
			sql_query("UPDATE torrent_trackers SET " . kz_mt_tracker_update_set($info_hash, $stats) . " WHERE id = $tracker_id") or sqlerr(__FILE__, __LINE__);
			$success++;
		} catch (Exception $e) {
			sql_query("UPDATE torrent_trackers SET " . kz_mt_tracker_update_set($info_hash, null, $e->getMessage()) . " WHERE id = $tracker_id") or sqlerr(__FILE__, __LINE__);
			$errors++;
		}
	}

	kz_mt_sync_torrent_totals($torrentid);
	return array('success' => $success, 'errors' => $errors, 'total' => $total);
}

?>
