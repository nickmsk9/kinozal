<?php

if (!defined('ADMIN_FILE')) {
	die('Illegal File Access');
}

require_once('include/multitracker.php');

function mt_admin_where()
{
	$where = array("tt.is_primary = 'no'");
	$status = (string)($_GET['status'] ?? 'all');
	$protocol = trim((string)($_GET['protocol'] ?? ''));
	$host = trim((string)($_GET['host'] ?? ''));
	$q = trim((string)($_GET['q'] ?? ''));
	$torrentid = (int)($_GET['torrentid'] ?? 0);

	if ($torrentid > 0) {
		$where[] = 'tt.torrentid = ' . $torrentid;
	}
	if ($protocol !== '') {
		$where[] = 'tt.protocol = ' . sqlesc($protocol);
	}
	if ($host !== '') {
		$where[] = 'tt.tracker_host = ' . sqlesc($host);
	}
	if ($q !== '') {
		$where[] = '(tt.announce_url LIKE ' . sqlesc('%' . $q . '%') . ' OR t.name LIKE ' . sqlesc('%' . $q . '%') . ')';
	}

	if ($status === 'enabled') {
		$where[] = "tt.enabled = 'yes'";
	} elseif ($status === 'disabled') {
		$where[] = "tt.enabled = 'no'";
	} elseif ($status === 'due') {
		$where[] = "tt.enabled = 'yes'";
		$where[] = '(tt.next_check IS NULL OR tt.next_check <= NOW())';
	} elseif ($status === 'error') {
		$where[] = "tt.last_error <> ''";
	} elseif ($status === 'ok') {
		$where[] = "tt.enabled = 'yes' AND tt.last_error = '' AND tt.seeders IS NOT NULL";
	} elseif ($status === 'manual') {
		$where[] = "tt.announce_url = " . sqlesc(multitracker_manual_stats_url());
	} elseif ($status === 'client') {
		$where[] = "(tt.protocol IN ('ws','wss') OR (tt.seeders IS NULL AND tt.leechers IS NULL AND tt.last_error = ''))";
	}

	return implode(' AND ', $where);
}

function mt_admin_url($admin_file, array $extra = array())
{
	$params = array_merge(array(
		'op' => 'MultitrackerAdmin',
		'status' => (string)($_GET['status'] ?? 'all'),
		'protocol' => (string)($_GET['protocol'] ?? ''),
		'host' => (string)($_GET['host'] ?? ''),
		'torrentid' => (string)($_GET['torrentid'] ?? ''),
		'q' => (string)($_GET['q'] ?? ''),
	), $extra);
	foreach ($params as $key => $value) {
		if ($value === '' || $value === null) {
			unset($params[$key]);
		}
	}
	return multitracker_h($admin_file) . '.php?' . http_build_query($params);
}

function mt_admin_update_host($host, $limit)
{
	$host = trim((string)$host);
	$limit = max(1, min(100, (int)$limit));
	if ($host === '') {
		return array('success' => 0, 'errors' => 0, 'client_only' => 0, 'total' => 0);
	}

	$res = sql_query("
		SELECT tt.id, tt.torrentid, tt.announce_url, tt.external_info_hash, tt.tracker_host, tt.protocol, tt.failures, t.info_hash
		FROM torrent_trackers AS tt
		INNER JOIN torrents AS t ON t.id = tt.torrentid
		WHERE tt.is_primary = 'no' AND tt.enabled = 'yes' AND tt.tracker_host = " . sqlesc($host) . "
		ORDER BY tt.last_checked IS NULL DESC, tt.last_checked ASC
		LIMIT $limit
	") or sqlerr(__FILE__, __LINE__);

	$result = array('success' => 0, 'errors' => 0, 'client_only' => 0, 'total' => 0);
	$torrents = array();
	while ($row = mysqli_fetch_assoc($res)) {
		$status = multitracker_update_tracker_row($row);
		$result['total']++;
		if ($status === 'success') {
			$result['success']++;
		} elseif ($status === 'client_only') {
			$result['client_only']++;
		} else {
			$result['errors']++;
		}
		$torrents[(int)$row['torrentid']] = true;
	}
	multitracker_sync_torrent_totals_bulk(array_keys($torrents));
	return $result;
}

if (!function_exists('MultitrackerAdmin')) {
	function MultitrackerAdmin()
	{
		global $admin_file;

		multitracker_ensure_schema();
		multitracker_prune_unsupported_trackers();

		if ($_SERVER['REQUEST_METHOD'] === 'POST') {
			$action = (string)($_POST['mt_action'] ?? '');
			if ($action === 'run_due') {
				$result = multitracker_update_due_trackers((int)($_POST['limit'] ?? 50), (int)($_POST['per_host'] ?? 4));
				stdmsg('Мультитрекер', 'Плановое обновление: всего ' . (int)$result['total'] . ', успешно ' . (int)$result['success'] . ', клиентских ' . (int)$result['client_only'] . ', ошибок ' . (int)$result['errors'] . '.');
			} elseif ($action === 'run_torrent') {
				$torrentid = (int)($_POST['torrentid'] ?? 0);
				$result = multitracker_update_torrent_trackers($torrentid, true);
				stdmsg('Мультитрекер', 'Раздача #' . $torrentid . ': успешно ' . (int)$result['success'] . ', клиентских ' . (int)$result['client_only'] . ', пропущено ' . (int)$result['skipped'] . ', ошибок ' . (int)$result['errors'] . ', всего проверено ' . (int)$result['total'] . '.');
			} elseif ($action === 'manual_stats') {
				$torrentid = (int)($_POST['torrentid'] ?? 0);
				$seeders = max(0, (int)($_POST['seeders'] ?? 0));
				$leechers = max(0, (int)($_POST['leechers'] ?? 0));
				if ($torrentid > 0 && multitracker_set_manual_stats($torrentid, $seeders, $leechers)) {
					stdmsg('Мультитрекер', 'Ручная статистика раздачи #' . $torrentid . ' сохранена: сиды ' . $seeders . ', пиры/личи ' . $leechers . '.');
				} else {
					stdmsg('Мультитрекер', 'Не удалось сохранить ручную статистику: проверь ID раздачи.');
				}
			} elseif ($action === 'save_torrent') {
				$torrentid = (int)($_POST['torrentid'] ?? 0);
				$urls = multitracker_parse_posted_urls((string)($_POST['external_trackers'] ?? ''));
				multitracker_save_trackers($torrentid, $urls, multitracker_recover_external_info_hash($torrentid));
				if (!empty($_POST['rewrite_file'])) {
					multitracker_rewrite_torrent_file_announces($torrentid, $urls);
				}
				stdmsg('Мультитрекер', 'Список трекеров раздачи #' . $torrentid . ' сохранён.');
			} elseif ($action === 'toggle_tracker') {
				$tracker_id = (int)($_POST['tracker_id'] ?? 0);
				$enabled = !empty($_POST['enabled']) ? 'yes' : 'no';
				sql_query("UPDATE torrent_trackers SET enabled = " . sqlesc($enabled) . " WHERE id = $tracker_id AND is_primary = 'no'") or sqlerr(__FILE__, __LINE__);
				$row = mysqli_fetch_assoc(sql_query("SELECT torrentid FROM torrent_trackers WHERE id = $tracker_id LIMIT 1"));
				if ($row) {
					multitracker_sync_torrent_totals((int)$row['torrentid']);
				}
				stdmsg('Мультитрекер', 'Статус трекера обновлён.');
			} elseif ($action === 'delete_tracker') {
				$tracker_id = (int)($_POST['tracker_id'] ?? 0);
				$row = mysqli_fetch_assoc(sql_query("SELECT torrentid FROM torrent_trackers WHERE id = $tracker_id AND is_primary = 'no' LIMIT 1"));
				sql_query("DELETE FROM torrent_trackers WHERE id = $tracker_id AND is_primary = 'no'") or sqlerr(__FILE__, __LINE__);
				if ($row) {
					multitracker_sync_torrent_totals((int)$row['torrentid']);
				}
				stdmsg('Мультитрекер', 'Трекер удалён.');
			} elseif ($action === 'reset_tracker') {
				$tracker_id = (int)($_POST['tracker_id'] ?? 0);
				sql_query("UPDATE torrent_trackers SET failures = 0, last_error = '', next_check = NULL WHERE id = $tracker_id AND is_primary = 'no'") or sqlerr(__FILE__, __LINE__);
				stdmsg('Мультитрекер', 'Ошибка сброшена, трекер вернулся в очередь.');
			} elseif ($action === 'host_enable' || $action === 'host_disable') {
				$host = trim((string)($_POST['host'] ?? ''));
				$enabled = $action === 'host_enable' ? 'yes' : 'no';
				$torrentids = array();
				$idres = sql_query("SELECT DISTINCT torrentid FROM torrent_trackers WHERE is_primary = 'no' AND tracker_host = " . sqlesc($host)) or sqlerr(__FILE__, __LINE__);
				while ($idrow = mysqli_fetch_assoc($idres)) {
					$torrentids[] = (int)$idrow['torrentid'];
				}
				sql_query("UPDATE torrent_trackers SET enabled = " . sqlesc($enabled) . " WHERE is_primary = 'no' AND tracker_host = " . sqlesc($host)) or sqlerr(__FILE__, __LINE__);
				multitracker_sync_torrent_totals_bulk($torrentids);
				stdmsg('Мультитрекер', 'Host ' . multitracker_h($host) . ' обновлён.');
			} elseif ($action === 'host_refresh') {
				$host = trim((string)($_POST['host'] ?? ''));
				$result = mt_admin_update_host($host, (int)($_POST['limit'] ?? 50));
				stdmsg('Мультитрекер', 'Host ' . multitracker_h($host) . ': всего ' . (int)$result['total'] . ', успешно ' . (int)$result['success'] . ', клиентских ' . (int)$result['client_only'] . ', ошибок ' . (int)$result['errors'] . '.');
			}
		}

		$stats = mysqli_fetch_assoc(sql_query("
			SELECT COUNT(*) AS trackers,
			       SUM(CASE WHEN is_primary = 'no' THEN 1 ELSE 0 END) AS external_trackers,
			       SUM(CASE WHEN enabled = 'yes' AND is_primary = 'no' THEN 1 ELSE 0 END) AS enabled_external,
			       SUM(CASE WHEN enabled = 'yes' AND is_primary = 'no' AND (next_check IS NULL OR next_check <= NOW()) THEN 1 ELSE 0 END) AS due_external,
			       SUM(CASE WHEN is_primary = 'no' AND last_error <> '' THEN 1 ELSE 0 END) AS errors,
			       SUM(CASE WHEN is_primary = 'no' AND seeders IS NULL AND last_error = '' THEN 1 ELSE 0 END) AS client_only,
			       COALESCE(SUM(CASE WHEN enabled = 'yes' AND is_primary = 'no' THEN COALESCE(seeders, 0) ELSE 0 END), 0) AS remote_seeders,
			       COALESCE(SUM(CASE WHEN enabled = 'yes' AND is_primary = 'no' THEN COALESCE(leechers, 0) ELSE 0 END), 0) AS remote_leechers,
			       AVG(last_response_ms) AS avg_ms
			FROM torrent_trackers
		")) ?: array();

		echo '<div class="mn_wrap">';
		echo '<div class="tp1_title"><b>Мультитрекер</b></div><div class="tp1_body">';
		echo '<table class="tables2 w100p">';
		echo '<tr><td>Всего announce URL</td><td>' . (int)($stats['trackers'] ?? 0) . '</td><td>Внешних</td><td>' . (int)($stats['external_trackers'] ?? 0) . '</td></tr>';
		echo '<tr><td>Включено внешних</td><td>' . (int)($stats['enabled_external'] ?? 0) . '</td><td>Ожидают проверки</td><td>' . (int)($stats['due_external'] ?? 0) . '</td></tr>';
		echo '<tr><td>Ошибки</td><td>' . (int)($stats['errors'] ?? 0) . '</td><td>Клиентские URL</td><td>' . (int)($stats['client_only'] ?? 0) . '</td></tr>';
		echo '<tr><td>Внешние сиды</td><td class="green b">' . (int)($stats['remote_seeders'] ?? 0) . '</td><td>Внешние пиры/личи</td><td class="red b">' . (int)($stats['remote_leechers'] ?? 0) . '</td></tr>';
		echo '<tr><td>Средний ответ</td><td colspan="3">' . (int)($stats['avg_ms'] ?? 0) . ' мс</td></tr>';
		echo '</table>';

		echo '<form method="post" action="' . mt_admin_url($admin_file, array()) . '" class="pad5x5">';
		echo '<input type="hidden" name="mt_action" value="run_due">';
		echo 'Лимит: <input type="text" name="limit" value="50" size="4"> ';
		echo 'На host: <input type="text" name="per_host" value="4" size="3"> ';
		echo '<input type="submit" class="buttonS" value="Обновить очередь">';
		echo '</form>';

		echo '<form method="post" action="' . mt_admin_url($admin_file, array()) . '" class="pad5x5">';
		echo '<input type="hidden" name="mt_action" value="run_torrent">';
		echo 'ID раздачи: <input type="text" name="torrentid" size="8"> ';
		echo '<input type="submit" class="buttonS" value="Обновить раздачу">';
		echo '</form>';

		echo '<form method="post" action="' . mt_admin_url($admin_file, array()) . '" class="pad5x5">';
		echo '<input type="hidden" name="mt_action" value="manual_stats">';
		echo 'Ручная статистика: ID <input type="text" name="torrentid" value="' . multitracker_h((string)($_GET['torrentid'] ?? '')) . '" size="8"> ';
		echo 'Сиды <input type="text" name="seeders" size="6"> ';
		echo 'Пиры/личи <input type="text" name="leechers" size="6"> ';
		echo '<input type="submit" class="buttonS" value="Сохранить статистику">';
		echo '</form>';

		echo '<div class="tp1_title"><b>Управление раздачей</b></div>';
		$manage_torrentid = (int)($_GET['torrentid'] ?? 0);
		$manage_urls = $manage_torrentid > 0 ? multitracker_external_textarea_value($manage_torrentid) : '';
		echo '<form method="post" action="' . mt_admin_url($admin_file, array()) . '" class="pad5x5">';
		echo '<input type="hidden" name="mt_action" value="save_torrent">';
		echo 'ID раздачи: <input type="text" name="torrentid" value="' . multitracker_h((string)$manage_torrentid) . '" size="8"> ';
		echo '<label><input type="checkbox" name="rewrite_file" value="1" checked> переписать .torrent</label><br>';
		echo '<textarea name="external_trackers" rows="7" class="w100p">' . multitracker_h($manage_urls) . '</textarea>';
		echo '<div><input type="submit" class="buttonS" value="Сохранить URL"></div>';
		echo '</form>';

		$protocols = array();
		$pres = sql_query("SELECT protocol, COUNT(*) AS cnt FROM torrent_trackers WHERE is_primary = 'no' GROUP BY protocol ORDER BY cnt DESC") or sqlerr(__FILE__, __LINE__);
		while ($p = mysqli_fetch_assoc($pres)) {
			$protocols[] = $p;
		}
		if ($protocols) {
			echo '<div class="tp1_title"><b>Протоколы</b></div><table class="tables2 w100p"><tr>';
			foreach ($protocols as $p) {
				echo '<td><a class="sba" href="' . mt_admin_url($admin_file, array('protocol' => $p['protocol'])) . '">' . multitracker_h($p['protocol'] ?: 'unknown') . '</a>: <b>' . (int)$p['cnt'] . '</b></td>';
			}
			echo '</tr></table>';
		}

		echo '<div class="tp1_title"><b>Фильтр</b></div>';
		echo '<form method="get" action="' . multitracker_h($admin_file) . '.php" class="pad5x5">';
		echo '<input type="hidden" name="op" value="MultitrackerAdmin">';
		$status = (string)($_GET['status'] ?? 'all');
		echo 'Статус: <select name="status">';
		foreach (array('all' => 'все', 'due' => 'очередь', 'ok' => 'ok', 'error' => 'ошибки', 'manual' => 'ручные', 'client' => 'клиентские', 'enabled' => 'включены', 'disabled' => 'выключены') as $value => $label) {
			echo '<option value="' . $value . '"' . ($status === $value ? ' selected' : '') . '>' . $label . '</option>';
		}
		echo '</select> ';
		echo 'Протокол: <input type="text" name="protocol" value="' . multitracker_h((string)($_GET['protocol'] ?? '')) . '" size="6"> ';
		echo 'Host: <input type="text" name="host" value="' . multitracker_h((string)($_GET['host'] ?? '')) . '" size="18"> ';
		echo 'Раздача: <input type="text" name="torrentid" value="' . multitracker_h((string)($_GET['torrentid'] ?? '')) . '" size="8"> ';
		echo 'Поиск: <input type="text" name="q" value="' . multitracker_h((string)($_GET['q'] ?? '')) . '" size="24"> ';
		echo '<input type="submit" class="buttonS" value="Показать">';
		echo '</form>';

		$where = mt_admin_where();
		$res = sql_query("
			SELECT tt.*, t.name
			FROM torrent_trackers AS tt
			INNER JOIN torrents AS t ON t.id = tt.torrentid
			WHERE $where
			ORDER BY tt.next_check IS NULL DESC, tt.next_check ASC, tt.last_checked IS NULL DESC, tt.last_checked ASC, tt.id DESC
			LIMIT 150
		") or sqlerr(__FILE__, __LINE__);

		echo '<div class="tp1_title"><b>Внешние трекеры</b></div>';
		echo '<table class="tables2 w100p"><tr><td class="colhead">Раздача</td><td class="colhead">URL</td><td class="colhead center">Протокол</td><td class="colhead center">Сиды</td><td class="colhead center">Пиры</td><td class="colhead center">Проверен</td><td class="colhead center">След.</td><td class="colhead center">Fail</td><td class="colhead center">Действия</td></tr>';
		while ($row = mysqli_fetch_assoc($res)) {
			$error = trim((string)$row['last_error']);
			$client_only = !multitracker_supports_server_scrape_url($row['announce_url'] ?? '');
			echo '<tr>';
			echo '<td><a href="/details.php?id=' . (int)$row['torrentid'] . '" class="sba">#' . (int)$row['torrentid'] . '</a><br>' . multitracker_h($row['name']) . '</td>';
			echo '<td><b>' . multitracker_h($row['tracker_host'] ?: multitracker_host($row['announce_url'])) . '</b><br>' . multitracker_h($row['announce_url']) . ($error !== '' ? '<div class="small red">' . multitracker_h($error) . '</div>' : ($client_only ? '<div class="small">клиентский announce, сервер не опрашивает</div>' : '')) . '</td>';
			echo '<td class="center">' . multitracker_h($row['protocol'] ?: multitracker_protocol($row['announce_url'])) . '</td>';
			echo '<td class="center green b">' . ($row['seeders'] === null ? 'н/д' : (int)$row['seeders']) . '</td>';
			echo '<td class="center red b">' . ($row['leechers'] === null ? 'н/д' : (int)$row['leechers']) . '</td>';
			echo '<td class="center">' . multitracker_h(multitracker_format_checked_at($row['last_checked'] ?? '')) . '<br>' . (int)($row['last_response_ms'] ?? 0) . ' мс</td>';
			echo '<td class="center">' . multitracker_h(multitracker_format_checked_at($row['next_check'] ?? '')) . '</td>';
			echo '<td class="center">' . (int)$row['failures'] . '</td>';
			echo '<td class="center">';
			echo '<form method="post" action="' . mt_admin_url($admin_file, array()) . '" style="display:inline"><input type="hidden" name="mt_action" value="toggle_tracker"><input type="hidden" name="tracker_id" value="' . (int)$row['id'] . '"><input type="hidden" name="enabled" value="' . ($row['enabled'] === 'yes' ? '0' : '1') . '"><input type="submit" class="buttonS" value="' . ($row['enabled'] === 'yes' ? 'Выкл' : 'Вкл') . '"></form> ';
			echo '<form method="post" action="' . mt_admin_url($admin_file, array()) . '" style="display:inline"><input type="hidden" name="mt_action" value="reset_tracker"><input type="hidden" name="tracker_id" value="' . (int)$row['id'] . '"><input type="submit" class="buttonS" value="Сброс"></form> ';
			echo '<form method="post" action="' . mt_admin_url($admin_file, array()) . '" style="display:inline" onsubmit="return confirm(\'Удалить трекер?\')"><input type="hidden" name="mt_action" value="delete_tracker"><input type="hidden" name="tracker_id" value="' . (int)$row['id'] . '"><input type="submit" class="buttonS" value="Del"></form>';
			echo '</td></tr>';
		}
		echo '</table>';

		$hosts = sql_query("
			SELECT tracker_host, COUNT(*) AS cnt, SUM(enabled = 'yes') AS enabled_cnt, SUM(last_error <> '') AS errors_cnt
			FROM torrent_trackers
			WHERE is_primary = 'no' AND tracker_host <> ''
			GROUP BY tracker_host
			ORDER BY cnt DESC, tracker_host ASC
			LIMIT 20
		") or sqlerr(__FILE__, __LINE__);
		echo '<div class="tp1_title"><b>Top host</b></div><table class="tables2 w100p"><tr><td class="colhead">Host</td><td class="colhead center">URL</td><td class="colhead center">Вкл.</td><td class="colhead center">Ошибки</td><td class="colhead center">Действия</td></tr>';
		while ($host = mysqli_fetch_assoc($hosts)) {
			echo '<tr><td><a class="sba" href="' . mt_admin_url($admin_file, array('host' => $host['tracker_host'])) . '">' . multitracker_h($host['tracker_host']) . '</a></td>';
			echo '<td class="center">' . (int)$host['cnt'] . '</td><td class="center">' . (int)$host['enabled_cnt'] . '</td><td class="center">' . (int)$host['errors_cnt'] . '</td><td class="center">';
			foreach (array('host_refresh' => 'Обновить', 'host_enable' => 'Вкл', 'host_disable' => 'Выкл') as $action => $label) {
				echo '<form method="post" action="' . mt_admin_url($admin_file, array()) . '" style="display:inline"><input type="hidden" name="mt_action" value="' . $action . '"><input type="hidden" name="host" value="' . multitracker_h($host['tracker_host']) . '"><input type="hidden" name="limit" value="50"><input type="submit" class="buttonS" value="' . $label . '"></form> ';
			}
			echo '</td></tr>';
		}
		echo '</table></div></div>';
	}
}

switch ($op) {
	case 'MultitrackerAdmin':
		MultitrackerAdmin();
		break;
}

?>
