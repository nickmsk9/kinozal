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
			KEY enabled_checked (enabled, last_checked)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
	") or sqlerr(__FILE__, __LINE__);
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

function kz_mt_save_trackers($torrentid, array $urls)
{
	kz_mt_ensure_schema();
	$torrentid = (int)$torrentid;
	if ($torrentid <= 0) {
		return;
	}

	$urls = kz_mt_extract_announces(array('announce-list' => array($urls)));
	$active = array();
	foreach ($urls as $url) {
		$is_primary = kz_mt_is_local_url($url) ? 'yes' : 'no';
		$active[] = sqlesc($url);
		sql_query("
			INSERT INTO torrent_trackers (torrentid, announce_url, is_primary, enabled)
			VALUES ($torrentid, " . sqlesc($url) . ", " . sqlesc($is_primary) . ", 'yes')
			ON DUPLICATE KEY UPDATE is_primary = VALUES(is_primary), enabled = 'yes'
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
	$res = sql_query("
		SELECT COALESCE(SUM(COALESCE(seeders, 0)), 0) AS seeders,
		       COALESCE(SUM(COALESCE(leechers, 0)), 0) AS leechers,
		       MAX(last_checked) AS last_checked
		FROM torrent_trackers
		WHERE torrentid = $torrentid AND enabled = 'yes' AND is_primary = 'no' AND last_error = ''
	") or sqlerr(__FILE__, __LINE__);
	$row = mysqli_fetch_assoc($res);
	sql_query("
		UPDATE torrents
		SET remote_seeders = " . (int)($row['seeders'] ?? 0) . ",
		    remote_leechers = " . (int)($row['leechers'] ?? 0) . ",
		    last_mt_update = " . (!empty($row['last_checked']) ? sqlesc($row['last_checked']) : "NULL") . ",
		    multitracker = IF((SELECT COUNT(*) FROM torrent_trackers WHERE torrentid = $torrentid AND enabled = 'yes' AND is_primary = 'no') > 0, 'yes', 'no')
		WHERE id = $torrentid
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
	$res = sql_query("SELECT * FROM torrent_trackers WHERE torrentid = " . (int)$torrentid . " ORDER BY is_primary DESC, id ASC") or sqlerr(__FILE__, __LINE__);
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
	return $html . '</table></li></ul></div>';
}

function kz_mt_scrape_tracker($url, $info_hash)
{
	$url = kz_mt_normalize_url($url);
	if (stripos($url, 'udp://') === 0) {
		require_once ROOT_PATH . 'include/scraper/udptscraper.php';
		$scraper = new udptscraper(4);
	} else {
		require_once ROOT_PATH . 'include/scraper/httptscraper.php';
		$scraper = new httptscraper(4, 65536);
	}
	$result = $scraper->scrape($url, $info_hash);
	return !empty($result[$info_hash]) && is_array($result[$info_hash]) ? $result[$info_hash] : false;
}

function kz_mt_update_due_trackers($limit = 25)
{
	kz_mt_ensure_schema();
	$limit = max(1, min(100, (int)$limit));
	$res = sql_query("
		SELECT tt.id, tt.torrentid, tt.announce_url, t.info_hash
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
		$info_hash = strtolower((string)$row['info_hash']);
		try {
			$stats = kz_mt_scrape_tracker($row['announce_url'], $info_hash);
			if (!$stats) {
				throw new Exception('no scrape data');
			}
			sql_query("UPDATE torrent_trackers SET seeders = " . (int)$stats['seeders'] . ", leechers = " . (int)$stats['leechers'] . ", completed = " . (int)$stats['completed'] . ", last_checked = NOW(), last_error = '' WHERE id = $tracker_id") or sqlerr(__FILE__, __LINE__);
		} catch (Exception $e) {
			sql_query("UPDATE torrent_trackers SET last_checked = NOW(), last_error = " . sqlesc(substr($e->getMessage(), 0, 255)) . " WHERE id = $tracker_id") or sqlerr(__FILE__, __LINE__);
		}
		$seen_torrents[$torrentid] = true;
	}

	foreach (array_keys($seen_torrents) as $torrentid) {
		kz_mt_sync_torrent_totals($torrentid);
	}
}

?>
