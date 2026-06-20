<?php

if (!defined('ADMIN_FILE')) {
	die('Illegal File Access');
}

require_once ROOT_PATH . 'include/test_torrents.php';

function test_torrents_admin_h($value)
{
	return htmlspecialchars_uni((string)$value);
}

function test_torrents_admin_date($value)
{
	if (empty($value) || $value === '0000-00-00 00:00:00') {
		return '';
	}

	$ts = strtotime((string)$value);
	return $ts ? date('d.m.Y H:i', $ts) : test_torrents_admin_h($value);
}

function test_torrents_admin_user_link($id, $username, $class = 0)
{
	$id = (int)$id;
	$username = (string)$username;
	if ($id <= 0 || $username === '') {
		return '<i>system</i>';
	}
	return '<a href="/userdetails.php?id=' . $id . '" class="u' . (int)$class . '">' . test_torrents_admin_h($username) . '</a>';
}

function test_torrents_admin_filter_url($status, $id = 0)
{
	global $admin_file;

	$url = test_torrents_admin_h($admin_file) . '.php?op=TestTorrentsAdmin';
	if ($status !== '') {
		$url .= '&amp;status=' . urlencode((string)$status);
	}
	if ((int)$id > 0) {
		$url .= '&amp;id=' . (int)$id;
	}
	return $url;
}

function test_torrents_admin_status_where($status, $has_review_schema)
{
	$id = (int)($_GET['id'] ?? 0);
	if ($id > 0) {
		return 'WHERE t.id = ' . $id;
	}

	if (!$has_review_schema) {
		return "WHERE t.is_test = 'yes'";
	}

	if ($status === 'all') {
		return "WHERE (t.is_test = 'yes' OR t.test_status IN ('approved','rejected'))";
	}

	if ($status === 'approved' || $status === 'rejected') {
		return "WHERE t.test_status = " . sqlesc($status);
	}

	if (isset(test_torrents_statuses()[$status])) {
		return "WHERE t.is_test = 'yes' AND t.test_status = " . sqlesc($status);
	}

	return "WHERE t.is_test = 'yes' AND t.test_status IN ('pending','checking','changes')";
}

function TestTorrentsAdmin()
{
	global $admin_file, $CURUSER;

	test_torrents_ensure_schema();
	$has_review_schema = test_torrents_review_schema_ready();
	$status = (string)($_GET['status'] ?? $_POST['status'] ?? 'active');
	$allowed_filters = array_merge(array('active' => true, 'all' => true), array_fill_keys(array_keys(test_torrents_statuses()), true));
	if (!isset($allowed_filters[$status])) {
		$status = 'active';
	}

	if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['test_action'])) {
		$action = (string)$_POST['test_action'];
		$torrent_id = (int)($_POST['id'] ?? 0);
		$comment = trim((string)($_POST['comment'] ?? ''));
		$message = test_torrents_review_action($action, $torrent_id, (int)$CURUSER['id'], $comment);
		stdmsg('Проверка тестовых раздач', test_torrents_admin_h($message));
		$has_review_schema = test_torrents_review_schema_ready();
	}

	if (!$has_review_schema) {
		stdmsg(
			'Проверка тестовых раздач',
			'Для полного workflow нужны новые поля test_status/test_checked_at/test_checked_by/test_check_comment. Включите KZ_AUTO_MIGRATIONS, один раз откройте этот раздел и затем снова выключите миграции.',
			'error'
		);
	}

	$count_map = array('active' => 0, 'all' => 0);
	foreach (array_keys(test_torrents_statuses()) as $key) {
		$count_map[$key] = 0;
	}

	if ($has_review_schema) {
		$res = sql_query("
			SELECT test_status, COUNT(*) AS total
			FROM torrents
			WHERE is_test = 'yes' OR test_status IN ('approved','rejected')
			GROUP BY test_status
		") or sqlerr(__FILE__, __LINE__);
		while ($row = mysqli_fetch_assoc($res)) {
			$key = (string)$row['test_status'];
			if (isset($count_map[$key])) {
				$count_map[$key] = (int)$row['total'];
			}
			$count_map['all'] += (int)$row['total'];
		}
		$count_map['active'] = $count_map['pending'] + $count_map['checking'] + $count_map['changes'];
	} else {
		$res = sql_query("SELECT COUNT(*) AS total FROM torrents WHERE is_test = 'yes'") or sqlerr(__FILE__, __LINE__);
		$row = mysqli_fetch_assoc($res);
		$count_map['active'] = (int)($row['total'] ?? 0);
		$count_map['all'] = $count_map['active'];
		$count_map['pending'] = $count_map['active'];
	}

	$where = test_torrents_admin_status_where($status, $has_review_schema);
	$count_res = sql_query("SELECT COUNT(*) FROM torrents AS t $where") or sqlerr(__FILE__, __LINE__);
	$count_row = mysqli_fetch_row($count_res);
	$count = (int)($count_row[0] ?? 0);
	$perpage = 25;
	list($pagertop, $pagerbottom, $limit) = pager($perpage, $count, test_torrents_admin_filter_url($status) . '&amp;');

	$review_fields = $has_review_schema
		? "t.test_status, t.test_checked_at, t.test_checked_by, t.test_check_comment,"
		: "'pending' AS test_status, NULL AS test_checked_at, 0 AS test_checked_by, NULL AS test_check_comment,";
	$review_join = $has_review_schema
		? "LEFT JOIN users AS cu ON cu.id = t.test_checked_by"
		: "LEFT JOIN users AS cu ON cu.id = 0";

	$res = sql_query("
		SELECT t.id, t.name, t.category, t.size, t.added, t.owner, t.visible, t.banned, t.is_test,
		       (t.seeders + t.remote_seeders) AS seeders,
		       (t.leechers + t.remote_leechers) AS leechers,
		       t.comments, t.test_helper_user_id, t.test_helper_until,
		       $review_fields
		       c.name AS cat_name,
		       u.username, u.class,
		       hu.username AS helper_username, hu.class AS helper_class,
		       cu.username AS checked_username, cu.class AS checked_class
		FROM torrents AS t
		LEFT JOIN categories AS c ON c.id = t.category
		LEFT JOIN users AS u ON u.id = t.owner
		LEFT JOIN users AS hu ON hu.id = t.test_helper_user_id
		$review_join
		$where
		ORDER BY t.added DESC, t.id DESC
		$limit
	") or sqlerr(__FILE__, __LINE__);

	echo '<div class="mn_wrap">';
	echo '<div class="tp1_title"><b>Проверка тестовых раздач</b></div>';
	echo '<div class="tp1_body">';
	echo '<div class="pad0x0x5x0">';
	$filters = array(
		'active' => 'Активные',
		'pending' => test_torrents_status_label('pending'),
		'checking' => test_torrents_status_label('checking'),
		'changes' => test_torrents_status_label('changes'),
		'approved' => test_torrents_status_label('approved'),
		'rejected' => test_torrents_status_label('rejected'),
		'all' => 'Все',
	);
	foreach ($filters as $key => $title) {
		$label = $title . ' (' . (int)($count_map[$key] ?? 0) . ')';
		$class = $key === $status ? ' class="b"' : '';
		echo '<a' . $class . ' href="' . test_torrents_admin_filter_url($key) . '">' . test_torrents_admin_h($label) . '</a> &nbsp; ';
	}
	echo '</div>';
	if ($pagertop) {
		echo '<div class="pad0x0x5x0">' . $pagertop . '</div>';
	}
	echo '<table class="tables2 w100p">';
	echo '<tr><td class="colhead">Раздача</td><td class="colhead">Владелец</td><td class="colhead center">Статус</td><td class="colhead">Проверка</td><td class="colhead center">Сиды/пиры</td><td class="colhead center">Действие</td></tr>';

	if ($count < 1) {
		echo '<tr><td colspan="6" class="center" style="padding:16px;">Раздач для выбранного фильтра нет.</td></tr>';
	}

	while ($row = mysqli_fetch_assoc($res)) {
		$id = (int)$row['id'];
		$status_key = (string)($row['test_status'] ?? 'pending');
		$owner = test_torrents_admin_user_link((int)$row['owner'], $row['username'] ?? '', (int)($row['class'] ?? 0));
		$helper = !empty($row['helper_username'])
			? test_torrents_admin_user_link((int)$row['test_helper_user_id'], $row['helper_username'], (int)$row['helper_class'])
			: '<span class="small">не назначен</span>';
		$checked = !empty($row['checked_username'])
			? test_torrents_admin_user_link((int)$row['test_checked_by'], $row['checked_username'], (int)$row['checked_class']) . '<br><span class="small">' . test_torrents_admin_date($row['test_checked_at']) . '</span>'
			: '<span class="small">ещё не проверялась</span>';
		$comment = trim((string)($row['test_check_comment'] ?? ''));
		$hidden = ((string)$row['visible'] !== 'yes' || (string)$row['banned'] === 'yes') ? '<br><span class="red small">скрыта</span>' : '';

		echo '<tr>';
		echo '<td><a class="sbab" href="/details.php?id=' . $id . '">' . test_torrents_admin_h($row['name']) . '</a>' . $hidden . '<br><span class="small">ID ' . $id . ' · ' . test_torrents_admin_h($row['cat_name'] ?? '') . ' · ' . mksize((int)$row['size']) . ' · ' . test_torrents_admin_date($row['added']) . ' · комм. ' . (int)$row['comments'] . '</span></td>';
		echo '<td>' . $owner . '</td>';
		echo '<td class="center">' . test_torrents_status_badge($status_key) . '</td>';
		echo '<td>Помогает: ' . $helper . '<br>Проверил: ' . $checked;
		if ($comment !== '') {
			echo '<div class="small" style="margin-top:4px;">' . nl2br(test_torrents_admin_h($comment)) . '</div>';
		}
		echo '</td>';
		echo '<td class="center"><span class="green b">' . (int)$row['seeders'] . '</span> / <span class="red b">' . (int)$row['leechers'] . '</span></td>';
		echo '<td class="center">';
		echo '<form method="post" action="' . test_torrents_admin_h($admin_file) . '.php?op=TestTorrentsAdmin&amp;status=' . test_torrents_admin_h($status) . '">';
		echo '<input type="hidden" name="id" value="' . $id . '">';
		echo '<input type="hidden" name="status" value="' . test_torrents_admin_h($status) . '">';
		echo '<textarea name="comment" rows="3" cols="34" placeholder="Комментарий проверки">' . test_torrents_admin_h($comment) . '</textarea><br>';
		echo '<button class="buttonS" type="submit" name="test_action" value="approve">Одобрить</button> ';
		if ($has_review_schema) {
			echo '<button class="buttonS" type="submit" name="test_action" value="changes">На доработку</button> ';
			echo '<button class="buttonS" type="submit" name="test_action" value="reject">Отклонить</button> ';
			echo '<button class="buttonS" type="submit" name="test_action" value="reopen">В очередь</button>';
		}
		echo '</form>';
		echo '</td>';
		echo '</tr>';
	}

	echo '</table>';
	if ($pagerbottom) {
		echo '<div class="pad5x5">' . $pagerbottom . '</div>';
	}
	echo '</div></div>';
}

switch ($op) {
	case 'TestTorrentsAdmin':
		TestTorrentsAdmin();
		break;
}

?>
