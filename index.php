<?php

/*
// +--------------------------------------------------------------------------+
// | Project:    TBDevYSE - TBDev Yuna Scatari Edition                        |
// +--------------------------------------------------------------------------+
// | This file is part of TBDevYSE. TBDevYSE is based on TBDev,               |
// | originally by RedBeard of TorrentBits, extensively modified by           |
// | Gartenzwerg.                                                             |
// |                                                                          |
// | TBDevYSE is free software; you can redistribute it and/or modify         |
// | it under the terms of the GNU General Public License as published by     |
// | the Free Software Foundation; either version 2 of the License, or        |
// | (at your option) any later version.                                      |
// |                                                                          |
// | TBDevYSE is distributed in the hope that it will be useful,              |
// | but WITHOUT ANY WARRANTY; without even the implied warranty of           |
// | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the            |
// | GNU General Public License for more details.                             |
// |                                                                          |
// | You should have received a copy of the GNU General Public License        |
// | along with TBDevYSE; if not, write to the Free Software Foundation,      |
// | Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA            |
// +--------------------------------------------------------------------------+
// |                                               Do not remove above lines! |
// +--------------------------------------------------------------------------+
*/

require_once("include/bittorrent.php");
dbconn(true, true);

function index_json_rows($json)
{
	$rows = json_decode((string)$json, true);
	return is_array($rows) ? $rows : array();
}

function index_preload_blocks()
{
	global $already_used, $orbital_blocks;

	$orbital_blocks = tracker_blocks_active_rows();
	$already_used = true;
}

function index_block_visible_here(array $block)
{
	global $CURUSER;

	$module_name = str_replace('.php', '', basename($_SERVER['PHP_SELF'] ?? ''));
	$is_home_like_module = in_array($module_name, array('index', 'radio'), true);
	$which = array_map('trim', explode(',', (string)($block['which'] ?? '')));

	if (
		!in_array($module_name, $which, true)
		&& !in_array('all', $which, true)
		&& !(in_array('ihome', $which, true) && $is_home_like_module)
	) {
		return false;
	}

	$view = (int)($block['view'] ?? 0);

	return $view === 0
		|| ($view === 1 && !empty($CURUSER))
		|| ($view === 2 && get_user_class() >= UC_MODERATOR)
		|| ($view === 3 && (empty($CURUSER) || get_user_class() >= UC_MODERATOR));
}

function index_has_block($blockfile, $position = null)
{
	global $orbital_blocks;

	foreach ((array)$orbital_blocks as $block) {
		if ((string)($block['blockfile'] ?? '') !== $blockfile) {
			continue;
		}

		if ($position !== null && (string)($block['bposition'] ?? '') !== $position) {
			continue;
		}

		if (index_block_visible_here($block)) {
			return true;
		}
	}

	return false;
}

function index_preload_right_blocks()
{
	global $CURUSER;

	$today = sqlesc(date('m-d'));

	if (!empty($GLOBALS['hide_right_blocks'])) {
		return;
	}

	$parts = array();

	if (index_has_block('block-top-torrents.php', 'r')) {
		$parts[] = "(SELECT COALESCE(JSON_ARRAYAGG(JSON_OBJECT('id', id, 'name', name, 'seeders', seeders)), JSON_ARRAY())
			 FROM (
				SELECT id, name, seeders + remote_seeders AS seeders
				FROM torrents
				WHERE visible = 'yes'
				  AND banned != 'yes'
				  AND (is_test <> 'yes' OR test_approved_at IS NOT NULL)
				ORDER BY seeders DESC, leechers + remote_leechers DESC, added DESC, id DESC
				LIMIT 10
			 ) AS top_rows) AS top_torrents";
	}

	if (index_has_block('block-stats.php', 'r')) {
		$parts[] = "(SELECT JSON_OBJECT(
				'users_total', COUNT(*),
				'girls_total', SUM(gender = '2'),
				'uploaders_total', SUM(class = " . (int)UC_UPLOADER . ")
			 )
			 FROM users
			 WHERE status = 'confirmed') AS user_stats";
		$parts[] = "(SELECT JSON_OBJECT(
				'torrents_total', COUNT(*),
				'seeders_total', COALESCE(SUM(seeders + remote_seeders), 0),
				'leechers_total', COALESCE(SUM(leechers + remote_leechers), 0)
			 )
			 FROM torrents
			 WHERE visible = 'yes'
			   AND banned != 'yes'
			   AND (is_test <> 'yes' OR test_approved_at IS NOT NULL)) AS torrent_stats";
	}

	if (index_has_block('block-birthday.php', 'r')) {
		$parts[] = "(SELECT COALESCE(JSON_ARRAYAGG(JSON_OBJECT('id', id, 'username', username, 'class', class)), JSON_ARRAY())
			 FROM (
				SELECT id, username, class
				FROM users
				WHERE status = 'confirmed'
				  AND enabled = 'yes'
				  AND birthday IS NOT NULL
				  AND DATE_FORMAT(birthday, '%m-%d') = $today
				ORDER BY class DESC, username ASC
			 ) AS birthday_rows) AS birthdays";
	}

	if (index_has_block('block-cups.php', 'r')) {
		$parts[] = "(SELECT COALESCE(JSON_ARRAYAGG(JSON_OBJECT(
				'cup_id', cup_id, 'cup_key', cup_key, 'title', title, 'profile_title', profile_title, 'icon', icon, 'sort', sort,
				'userid', userid, 'source', source, 'metric', metric, 'assigned_at', assigned_at, 'username', username,
				'class', class, 'donor', donor, 'gender', gender, 'birthday', birthday, 'warned', warned, 'enabled', enabled,
				'parked', parked, 'uploaded', uploaded, 'downloaded', downloaded, 'manual_status_keys', manual_status_keys,
				'flagpic', flagpic
			)), JSON_ARRAY())
			 FROM (
				SELECT c.id AS cup_id, c.cup_key, c.title, c.profile_title, c.icon, c.sort,
				       uc.userid, uc.source, uc.metric, uc.assigned_at,
				       u.username, u.class, u.donor, u.gender, u.birthday, u.warned, u.enabled, u.parked, u.uploaded, u.downloaded,
				       usa.manual_status_keys, co.flagpic
				FROM cups AS c
				LEFT JOIN user_cups AS uc ON uc.cup_id = c.id
				LEFT JOIN users AS u ON u.id = uc.userid
				LEFT JOIN (
					SELECT userid, GROUP_CONCAT(status_key) AS manual_status_keys
					FROM user_status_assignments
					GROUP BY userid
				) AS usa ON usa.userid = u.id
				LEFT JOIN countries AS co ON co.id = u.country
				WHERE c.active = 1
				ORDER BY c.sort ASC, c.id ASC
			 ) AS cup_rows) AS cups";
	}

	$parts[] = "(SELECT COALESCE(JSON_ARRAYAGG(JSON_OBJECT(
				'id', id, 'userid', userid, 'username', username, 'userclass', userclass, 'image_url', image_url,
				'active', active, 'ip', ip, 'added', added, 'real_username', real_username, 'real_class', real_class,
				'country', country, 'gender', gender, 'donor', donor, 'warned', warned, 'enabled', enabled,
				'birthday', birthday, 'uploaded', uploaded, 'downloaded', downloaded, 'manual_status_keys', manual_status_keys
			)), JSON_ARRAY())
			 FROM (
				SELECT s.*, u.username AS real_username, u.class AS real_class, u.country, u.gender, u.donor, u.warned,
				       u.enabled, u.birthday, u.uploaded, u.downloaded, usa.manual_status_keys
				FROM uarch_smiles AS s
				LEFT JOIN users AS u ON u.id = s.userid
				LEFT JOIN (
					SELECT userid, GROUP_CONCAT(status_key) AS manual_status_keys
					FROM user_status_assignments
					GROUP BY userid
				) AS usa ON usa.userid = u.id
				WHERE s.active = 'yes'
				ORDER BY s.added DESC, s.id DESC
				LIMIT 1
			 ) AS uarch_rows) AS uarch";

	$cache_key = 'index:right:' . date('Ymd') . ':' . (!empty($CURUSER) ? get_user_class() : 0);
	$row = function_exists('tracker_cache_remember')
		? tracker_cache_remember($cache_key, 30, function () use ($parts) {
			$res = sql_query("
				SELECT
					" . implode(",\n					", $parts) . "
			") or sqlerr(__FILE__, __LINE__);

			return mysqli_fetch_assoc($res) ?: array();
		})
		: array();

	if (!function_exists('tracker_cache_remember')) {
		$res = sql_query("
			SELECT
				" . implode(",\n				", $parts) . "
		") or sqlerr(__FILE__, __LINE__);

		$row = mysqli_fetch_assoc($res) ?: array();
	}
	if (array_key_exists('top_torrents', $row)) {
		$GLOBALS['index_top_torrents'] = index_json_rows($row['top_torrents']);
	}
	if (array_key_exists('birthdays', $row)) {
		$GLOBALS['index_birthdays'] = index_json_rows($row['birthdays']);
	}
	if (array_key_exists('cups', $row)) {
		$GLOBALS['index_cups_current'] = index_json_rows($row['cups']);
	}
	$GLOBALS['index_uarch_smiles'] = index_json_rows($row['uarch'] ?? '[]');

	if (array_key_exists('user_stats', $row) || array_key_exists('torrent_stats', $row)) {
		$user_stats = json_decode((string)($row['user_stats'] ?? '{}'), true);
		$torrent_stats = json_decode((string)($row['torrent_stats'] ?? '{}'), true);
		$GLOBALS['index_stats'] = array_merge(
			is_array($user_stats) ? $user_stats : array(),
			is_array($torrent_stats) ? $torrent_stats : array()
		);
	}
}

function index_preload_center_blocks()
{
	global $CURUSER;

	$per_page = 10;
	$page = isset($_GET['relpage']) ? max(0, (int)$_GET['relpage']) : 0;
	$offset = $page * $per_page;
	$limit = $per_page + 1;

	$parts = array();

	if (index_has_block('block-news.php', 'c')) {
		$parts[] = "(SELECT COALESCE(JSON_ARRAYAGG(JSON_OBJECT('id', id, 'added', added, 'subject', subject, 'body', body)), JSON_ARRAY())
			 FROM (
				SELECT id, added, subject, body
				FROM news
				WHERE added > DATE_SUB(NOW(), INTERVAL 45 DAY)
				ORDER BY added DESC
				LIMIT 10
			 ) AS news_rows) AS news";
	}

	if (index_has_block('block-releases.php', 'c')) {
		$parts[] = "(SELECT COALESCE(JSON_ARRAYAGG(JSON_OBJECT(
				'id', id, 'name', name, 'descr', descr, 'image1', image1, 'image2', image2, 'image3', image3,
				'image4', image4, 'image5', image5, 'poster_url', poster_url, 'size', size, 'added', added,
				'catid', catid, 'catname', catname, 'catimage', catimage
			)), JSON_ARRAY())
			 FROM (
				SELECT t.id, t.name, t.descr, t.image1, t.image2, t.image3, t.image4, t.image5,
				       td.poster_url, t.size, t.added, c.id AS catid, c.name AS catname, c.image AS catimage
				FROM torrents AS t
				LEFT JOIN categories AS c ON c.id = t.category
				LEFT JOIN torrent_details AS td ON td.tid = t.id
				WHERE t.visible = 'yes'
				  AND (t.banned <> 'yes' OR t.banned IS NULL)
				  AND (t.is_test <> 'yes' OR t.test_approved_at IS NOT NULL)
				ORDER BY t.added DESC, t.id DESC
				LIMIT " . (int)$offset . ", " . (int)$limit . "
			 ) AS release_rows) AS releases";
	}

	if (index_has_block('block-pay.php', 'c')) {
		$parts[] = "(SELECT COALESCE(MAX(setting_value), '1')
			 FROM pay_settings
			 WHERE setting_key = 'home_block_enabled') AS pay_enabled";
		$parts[] = "(SELECT COALESCE(JSON_ARRAYAGG(JSON_OBJECT(
				'userid', userid, 'username', username, 'class', class, 'donor', donor, 'gender', gender,
				'birthday', birthday, 'warned', warned, 'enabled', enabled, 'uploaded', uploaded, 'downloaded', downloaded,
				'manual_status_keys', manual_status_keys, 'last_at', last_at, 'votes_sum', votes_sum, 'ops', ops
			)), JSON_ARRAY())
			 FROM (
				SELECT u.id AS userid, u.username, u.class, u.donor, u.gender, u.birthday, u.warned, u.enabled,
				       u.uploaded, u.downloaded, usa.manual_status_keys, MAX(t.created_at) AS last_at,
				       SUM(t.votes_delta) AS votes_sum, COUNT(*) AS ops
				FROM pay_transactions AS t
				INNER JOIN users AS u ON u.id = t.userid
				LEFT JOIN (
					SELECT userid, GROUP_CONCAT(status_key) AS manual_status_keys
					FROM user_status_assignments
					GROUP BY userid
				) AS usa ON usa.userid = u.id
				WHERE t.operation = 'exchange'
				  AND t.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
				GROUP BY u.id, u.username, u.class, u.donor, u.gender, u.birthday, u.warned, u.enabled,
				         u.uploaded, u.downloaded, usa.manual_status_keys
				ORDER BY last_at DESC
				LIMIT 20
			 ) AS recent_rows) AS pay_recent";
		$parts[] = "(SELECT COALESCE(JSON_ARRAYAGG(JSON_OBJECT(
				'userid', userid, 'username', username, 'class', class, 'donor', donor, 'gender', gender,
				'birthday', birthday, 'warned', warned, 'enabled', enabled, 'uploaded', uploaded, 'downloaded', downloaded,
				'manual_status_keys', manual_status_keys, 'votes_sum', votes_sum, 'ops', ops
			)), JSON_ARRAY())
			 FROM (
				SELECT u.id AS userid, u.username, u.class, u.donor, u.gender, u.birthday, u.warned, u.enabled,
				       u.uploaded, u.downloaded, usa.manual_status_keys,
				       SUM(GREATEST(t.votes_delta, 0)) AS votes_sum, COUNT(*) AS ops
				FROM pay_transactions AS t
				INNER JOIN users AS u ON u.id = t.userid
				LEFT JOIN (
					SELECT userid, GROUP_CONCAT(status_key) AS manual_status_keys
					FROM user_status_assignments
					GROUP BY userid
				) AS usa ON usa.userid = u.id
				WHERE t.operation = 'exchange'
				  AND t.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
				GROUP BY u.id, u.username, u.class, u.donor, u.gender, u.birthday, u.warned, u.enabled,
				         u.uploaded, u.downloaded, usa.manual_status_keys
				ORDER BY votes_sum DESC, ops DESC
				LIMIT 8
			 ) AS best_rows) AS pay_best";
	}

	if (!$parts) {
		return;
	}

	$cache_key = 'index:center:' . $page . ':' . (!empty($CURUSER) ? get_user_class() : 0);
	$row = function_exists('tracker_cache_remember')
		? tracker_cache_remember($cache_key, 30, function () use ($parts) {
			$res = sql_query("
				SELECT
					" . implode(",\n					", $parts) . "
			") or sqlerr(__FILE__, __LINE__);

			return mysqli_fetch_assoc($res) ?: array();
		})
		: array();

	if (!function_exists('tracker_cache_remember')) {
		$res = sql_query("
			SELECT
				" . implode(",\n				", $parts) . "
		") or sqlerr(__FILE__, __LINE__);

		$row = mysqli_fetch_assoc($res) ?: array();
	}
	if (array_key_exists('news', $row)) {
		$GLOBALS['index_news'] = index_json_rows($row['news']);
	}
	if (array_key_exists('releases', $row)) {
		$GLOBALS['index_releases'] = index_json_rows($row['releases']);
	}
	if (array_key_exists('pay_enabled', $row)) {
		$GLOBALS['index_pay_enabled'] = (string)$row['pay_enabled'];
	}
	if (array_key_exists('pay_recent', $row)) {
		$GLOBALS['index_pay_recent'] = index_json_rows($row['pay_recent']);
	}
	if (array_key_exists('pay_best', $row)) {
		$GLOBALS['index_pay_best'] = index_json_rows($row['pay_best']);
	}
}

index_preload_blocks();
index_preload_right_blocks();
index_preload_center_blocks();

stdhead($tracker_lang['homepage']);

stdfoot();
?>
