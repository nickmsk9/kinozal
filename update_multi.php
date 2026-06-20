<?php

require_once __DIR__ . '/include/bittorrent.php';
require_once __DIR__ . '/include/multitracker.php';

$old_sessions = $use_sessions;
$use_sessions = 0;
dbconn();
$use_sessions = $old_sessions;

loggedinorreturn();
tracker_require_form_token('GET');

$tid = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($tid <= 0) {
	stderr($tracker_lang['error'], $tracker_lang['invalid_id'] ?? 'Неверный ID.');
}

$row = mysqli_fetch_assoc(sql_query("SELECT id, name, last_mt_update, multitracker FROM torrents WHERE id = $tid LIMIT 1")) or stderr($tracker_lang['error'], $tracker_lang['no_torrent_with_such_id']);
if (($row['multitracker'] ?? 'no') !== 'yes') {
	stderr($tracker_lang['error'], 'У этой раздачи нет внешних трекеров.');
}

$is_moderator = function_exists('get_user_class') && get_user_class() >= UC_MODERATOR;
$last_update = strtotime((string)($row['last_mt_update'] ?? ''));
if (!$is_moderator && $last_update > 0 && $last_update > (TIMENOW - 600)) {
	stderr($tracker_lang['error'], 'Данные мультитрекера уже свежие. Повторите обновление позже.');
}

$result = multitracker_update_torrent_trackers($tid, $is_moderator);
$ajax = (string)($_GET['ajax'] ?? '') === 'yes';

if (!$ajax) {
	header('Location: details.php?id=' . $tid . '&mtupdated=1&mts=' . (int)$result['success'] . '&mte=' . (int)$result['errors'] . '&mtc=' . (int)$result['client_only'] . '&mtk=' . (int)$result['skipped']);
	exit;
}

header('Content-Type: text/html; charset=' . ($tracker_lang['language_charset'] ?? 'UTF-8'));
$trackers = multitracker_get_trackers($tid);
$items = array();
foreach ($trackers as $tracker) {
	if (($tracker['is_primary'] ?? 'no') === 'yes') {
		continue;
	}
	$url = multitracker_h($tracker['announce_url'] ?? '');
	$error = trim((string)($tracker['last_error'] ?? ''));
	$manual = multitracker_is_manual_stats_url($tracker['announce_url'] ?? '');
	$status = $manual ? 'ручной' : (!multitracker_supports_server_scrape_url($tracker['announce_url'] ?? '') ? 'клиентский' : ($error === '' ? 'ok' : 'ошибка'));
	$url = $manual ? 'ручная статистика админки' : $url;
	$seeders = $tracker['seeders'] === null ? 'н/д' : (int)$tracker['seeders'];
	$leechers = $tracker['leechers'] === null ? 'н/д' : (int)$tracker['leechers'];
	$items[] = '<li><b>' . $url . '</b> - ' . multitracker_h($status) . ', сиды: <b>' . $seeders . '</b>, пиры/личи: <b>' . $leechers . '</b>' . ($error !== '' ? '<div class="small red">' . multitracker_h($error) . '</div>' : '') . '</li>';
}

echo '<ul style="margin:0;">' . implode('', $items) . '</ul>';

?>
