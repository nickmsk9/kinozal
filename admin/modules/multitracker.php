<?php

if (!defined('ADMIN_FILE')) {
	die('Illegal File Access');
}

require_once('include/kz_multitracker.php');

if (!function_exists('MultitrackerAdmin')) {
	function MultitrackerAdmin()
	{
		global $admin_file;

		kz_mt_ensure_schema();

		if ($_SERVER['REQUEST_METHOD'] === 'POST') {
			$action = (string)($_POST['mt_action'] ?? '');
			if ($action === 'run_due') {
				kz_mt_update_due_trackers((int)($_POST['limit'] ?? 25));
				stdmsg('Мультитрекер', 'Плановое обновление внешних трекеров выполнено.');
			} elseif ($action === 'run_torrent') {
				$torrentid = (int)($_POST['torrentid'] ?? 0);
				$result = kz_mt_update_torrent_trackers($torrentid);
				stdmsg('Мультитрекер', 'Раздача #' . $torrentid . ': успешно ' . (int)$result['success'] . ', ошибок ' . (int)$result['errors'] . '.');
			} elseif ($action === 'toggle_tracker') {
				$tracker_id = (int)($_POST['tracker_id'] ?? 0);
				$enabled = !empty($_POST['enabled']) ? 'yes' : 'no';
				sql_query("UPDATE torrent_trackers SET enabled = " . sqlesc($enabled) . " WHERE id = $tracker_id AND is_primary = 'no'") or sqlerr(__FILE__, __LINE__);
				$row = mysqli_fetch_assoc(sql_query("SELECT torrentid FROM torrent_trackers WHERE id = $tracker_id LIMIT 1"));
				if ($row) {
					kz_mt_sync_torrent_totals((int)$row['torrentid']);
				}
				stdmsg('Мультитрекер', 'Статус трекера обновлен.');
			}
		}

		$stats = mysqli_fetch_assoc(sql_query("
			SELECT COUNT(*) AS trackers,
			       SUM(CASE WHEN is_primary = 'no' THEN 1 ELSE 0 END) AS external_trackers,
			       SUM(CASE WHEN enabled = 'yes' AND is_primary = 'no' THEN 1 ELSE 0 END) AS enabled_external,
			       SUM(CASE WHEN enabled = 'yes' AND is_primary = 'no' AND (last_checked IS NULL OR last_checked < DATE_SUB(NOW(), INTERVAL " . (int)ceil(KZ_MT_TTL / 60) . " MINUTE)) THEN 1 ELSE 0 END) AS due_external
			FROM torrent_trackers
		")) ?: array();

		echo '<div class="mn_wrap">';
		echo '<div class="tp1_title"><b>Мультитрекер</b></div><div class="tp1_body">';
		echo '<table class="tables2 w100p">';
		echo '<tr><td>Всего announce URL</td><td>' . (int)($stats['trackers'] ?? 0) . '</td></tr>';
		echo '<tr><td>Внешних трекеров</td><td>' . (int)($stats['external_trackers'] ?? 0) . '</td></tr>';
		echo '<tr><td>Включено внешних</td><td>' . (int)($stats['enabled_external'] ?? 0) . '</td></tr>';
		echo '<tr><td>Ожидают проверки</td><td>' . (int)($stats['due_external'] ?? 0) . '</td></tr>';
		echo '</table>';

		echo '<form method="post" action="' . kz_mt_h($admin_file) . '.php?op=MultitrackerAdmin" class="pad5x5">';
		echo '<input type="hidden" name="mt_action" value="run_due">';
		echo 'Лимит: <input type="text" name="limit" value="25" size="4"> ';
		echo '<input type="submit" class="buttonS" value="Обновить ожидающие проверки">';
		echo '</form>';

		echo '<form method="post" action="' . kz_mt_h($admin_file) . '.php?op=MultitrackerAdmin" class="pad5x5">';
		echo '<input type="hidden" name="mt_action" value="run_torrent">';
		echo 'ID раздачи: <input type="text" name="torrentid" size="8"> ';
		echo '<input type="submit" class="buttonS" value="Обновить раздачу">';
		echo '</form>';

		$res = sql_query("
			SELECT tt.*, t.name
			FROM torrent_trackers AS tt
			INNER JOIN torrents AS t ON t.id = tt.torrentid
			WHERE tt.is_primary = 'no'
			ORDER BY tt.last_checked IS NULL DESC, tt.last_checked ASC, tt.id DESC
			LIMIT 100
		") or sqlerr(__FILE__, __LINE__);

		echo '<div class="tp1_title"><b>Внешние трекеры</b></div>';
		echo '<table class="tables2 w100p"><tr><td class="colhead">Раздача</td><td class="colhead">URL</td><td class="colhead center">Сиды</td><td class="colhead center">Личи</td><td class="colhead center">Проверен</td><td class="colhead center">Статус</td><td class="colhead center">Вкл.</td></tr>';
		while ($row = mysqli_fetch_assoc($res)) {
			$error = trim((string)$row['last_error']);
			echo '<tr>';
			echo '<td><a href="/details.php?id=' . (int)$row['torrentid'] . '" class="sba">#' . (int)$row['torrentid'] . '</a><br>' . kz_mt_h($row['name']) . '</td>';
			echo '<td>' . kz_mt_h($row['announce_url']) . ($error !== '' ? '<div class="small red">' . kz_mt_h($error) . '</div>' : '') . '</td>';
			echo '<td class="center green b">' . ($row['seeders'] === null ? 'н/д' : (int)$row['seeders']) . '</td>';
			echo '<td class="center red b">' . ($row['leechers'] === null ? 'н/д' : (int)$row['leechers']) . '</td>';
			echo '<td class="center">' . (!empty($row['last_checked']) ? kz_mt_h($row['last_checked']) : 'н/д') . '</td>';
			echo '<td class="center">' . ($error === '' ? 'ok' : 'ошибка') . '</td>';
			echo '<td class="center"><form method="post" action="' . kz_mt_h($admin_file) . '.php?op=MultitrackerAdmin">';
			echo '<input type="hidden" name="mt_action" value="toggle_tracker"><input type="hidden" name="tracker_id" value="' . (int)$row['id'] . '">';
			echo '<input type="checkbox" name="enabled" value="1"' . ($row['enabled'] === 'yes' ? ' checked' : '') . ' onchange="this.form.submit();">';
			echo '</form></td></tr>';
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
