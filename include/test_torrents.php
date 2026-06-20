<?php

if (!defined('IN_TRACKER')) {
	die('Прямой вызов запрещён.');
}

/**
 * ВАЖНО:
 * Проверку/создание колонок нельзя гонять на каждой странице.
 *
 * Если нужно один раз обновить структуру БД, временно добавь в config.php:
 *
 * define('KZ_AUTO_MIGRATIONS', true);
 *
 * После обновления верни обратно:
 *
 * define('KZ_AUTO_MIGRATIONS', false);
 */
if (!defined('KZ_AUTO_MIGRATIONS')) {
	define('KZ_AUTO_MIGRATIONS', false);
}

function test_torrents_column_exists($column)
{
	$res = sql_query("SHOW COLUMNS FROM torrents LIKE " . sqlesc($column));
	return $res && mysqli_num_rows($res) > 0;
}

function test_torrents_index_exists($index, $table = 'torrents')
{
	$table = preg_replace('/[^a-zA-Z0-9_]/', '', (string)$table);
	if ($table === '') {
		return false;
	}

	$res = sql_query("SHOW INDEX FROM `$table` WHERE Key_name = " . sqlesc($index));
	return $res && mysqli_num_rows($res) > 0;
}

function test_torrents_ensure_schema()
{
	static $done = false;

	if ($done) {
		return;
	}

	$done = true;

	/*
	 * Не выполняем SHOW COLUMNS / SHOW INDEX на обычных страницах.
	 * Это должно запускаться только вручную при обновлении проекта.
	 */
	if (!defined('KZ_AUTO_MIGRATIONS') || KZ_AUTO_MIGRATIONS !== true) {
		return;
	}

	if (!test_torrents_column_exists('is_test')) {
		sql_query("ALTER TABLE torrents ADD is_test enum('yes','no') NOT NULL DEFAULT 'no' AFTER multitracker") or sqlerr(__FILE__, __LINE__);
	}

	if (!test_torrents_column_exists('test_status')) {
		sql_query("ALTER TABLE torrents ADD test_status enum('pending','checking','changes','approved','rejected') NOT NULL DEFAULT 'pending' AFTER is_test") or sqlerr(__FILE__, __LINE__);
	}

	if (!test_torrents_column_exists('test_approved_at')) {
		sql_query("ALTER TABLE torrents ADD test_approved_at datetime NULL DEFAULT NULL AFTER test_status") or sqlerr(__FILE__, __LINE__);
	}

	if (!test_torrents_column_exists('test_approved_by')) {
		sql_query("ALTER TABLE torrents ADD test_approved_by int(10) unsigned NOT NULL DEFAULT '0' AFTER test_approved_at") or sqlerr(__FILE__, __LINE__);
	}

	if (!test_torrents_column_exists('test_checked_at')) {
		sql_query("ALTER TABLE torrents ADD test_checked_at datetime NULL DEFAULT NULL AFTER test_approved_by") or sqlerr(__FILE__, __LINE__);
	}

	if (!test_torrents_column_exists('test_checked_by')) {
		sql_query("ALTER TABLE torrents ADD test_checked_by int(10) unsigned NOT NULL DEFAULT '0' AFTER test_checked_at") or sqlerr(__FILE__, __LINE__);
	}

	if (!test_torrents_column_exists('test_check_comment')) {
		sql_query("ALTER TABLE torrents ADD test_check_comment text NULL AFTER test_checked_by") or sqlerr(__FILE__, __LINE__);
	}

	if (!test_torrents_column_exists('test_helper_user_id')) {
		sql_query("ALTER TABLE torrents ADD test_helper_user_id int(10) unsigned NOT NULL DEFAULT '0' AFTER test_check_comment") or sqlerr(__FILE__, __LINE__);
	}

	if (!test_torrents_column_exists('test_helper_until')) {
		sql_query("ALTER TABLE torrents ADD test_helper_until datetime NULL DEFAULT NULL AFTER test_helper_user_id") or sqlerr(__FILE__, __LINE__);
	}

	if (!test_torrents_index_exists('is_test_visible')) {
		sql_query("ALTER TABLE torrents ADD KEY is_test_visible (is_test, visible, banned, added)") or sqlerr(__FILE__, __LINE__);
	}

	if (!test_torrents_index_exists('test_helper_until')) {
		sql_query("ALTER TABLE torrents ADD KEY test_helper_until (test_helper_until)") or sqlerr(__FILE__, __LINE__);
	}

	if (!test_torrents_index_exists('test_status_visible')) {
		sql_query("ALTER TABLE torrents ADD KEY test_status_visible (is_test, test_status, visible, banned, added)") or sqlerr(__FILE__, __LINE__);
	}

	if (!test_torrents_index_exists('test_status')) {
		sql_query("ALTER TABLE torrents ADD KEY test_status (test_status, id)") or sqlerr(__FILE__, __LINE__);
	}

	$performance_indexes = array(
		array('torrents', 'browse_main', 'ALTER TABLE torrents ADD KEY browse_main (visible, banned, is_test, not_sticky, added, id)'),
		array('torrents', 'browse_category', 'ALTER TABLE torrents ADD KEY browse_category (category, visible, banned, is_test, not_sticky, added, id)'),
		array('torrents', 'owner_visible_id', 'ALTER TABLE torrents ADD KEY owner_visible_id (owner, visible, banned, id)'),
		array('bookmarks', 'userid_torrentid', 'ALTER TABLE bookmarks ADD KEY userid_torrentid (userid, torrentid)'),
		array('bookmarks', 'torrentid', 'ALTER TABLE bookmarks ADD KEY torrentid (torrentid)'),
		array('peers', 'torrent_id', 'ALTER TABLE peers ADD KEY torrent_id (torrent, id)'),
		array('comments', 'torrent_id', 'ALTER TABLE comments ADD KEY torrent_id (torrent, id)'),
		array('comments', 'user_id', 'ALTER TABLE comments ADD KEY user_id (user, id)'),
		array('snatched', 'userid_completed', 'ALTER TABLE snatched ADD KEY userid_completed (userid, completedat, last_action)'),
		array('snatched', 'userid_finished_completed', 'ALTER TABLE snatched ADD KEY userid_finished_completed (userid, finished, completedat, last_action, id)'),
		array('ratings', 'rating_torrent_user', 'ALTER TABLE ratings ADD KEY rating_torrent_user (torrent, user, id)'),
		array('simpaty', 'profile_wall', 'ALTER TABLE simpaty ADD KEY profile_wall (touserid, respect_time)'),
		array('simpaty', 'touserid_time_id', 'ALTER TABLE simpaty ADD KEY touserid_time_id (touserid, respect_time, id)'),
		array('simpaty', 'fromuserid_time_id', 'ALTER TABLE simpaty ADD KEY fromuserid_time_id (fromuserid, respect_time, id)'),
		array('users', 'status_class_added', 'ALTER TABLE users ADD KEY status_class_added (status, class, added)'),
		array('users', 'status_country_added', 'ALTER TABLE users ADD KEY status_country_added (status, country, added)'),
		array('users', 'status_gender_added', 'ALTER TABLE users ADD KEY status_gender_added (status, gender, added)'),
	);

	foreach ($performance_indexes as $index) {
		if (!test_torrents_index_exists($index[1], $index[0])) {
			sql_query($index[2]) or sqlerr(__FILE__, __LINE__);
		}
	}
}

function test_torrents_h($value)
{
	return htmlspecialchars_uni((string)$value);
}

function test_torrents_statuses()
{
	return array(
		'pending' => 'Ожидает проверки',
		'checking' => 'В работе',
		'changes' => 'На доработке',
		'approved' => 'Одобрена',
		'rejected' => 'Отклонена',
	);
}

function test_torrents_status_label($status)
{
	$statuses = test_torrents_statuses();
	$status = (string)$status;
	return $statuses[$status] ?? $statuses['pending'];
}

function test_torrents_status_badge($status)
{
	$status = (string)$status;
	$colors = array(
		'pending' => '#6b7280',
		'checking' => '#2563eb',
		'changes' => '#b45309',
		'approved' => '#15803d',
		'rejected' => '#b91c1c',
	);
	$color = $colors[$status] ?? $colors['pending'];

	return '<span style="color:' . $color . '; font-weight:bold;">' . test_torrents_h(test_torrents_status_label($status)) . '</span>';
}

function test_torrents_review_schema_ready()
{
	static $ready = null;

	if ($ready !== null) {
		return $ready;
	}

	$required = array('test_status', 'test_checked_at', 'test_checked_by', 'test_check_comment');
	foreach ($required as $column) {
		if (!test_torrents_column_exists($column)) {
			$ready = false;
			return false;
		}
	}

	$ready = true;
	return true;
}

function test_torrents_can_help()
{
	return function_exists('get_user_class') && get_user_class() >= UC_UPLOADER;
}

function test_torrents_can_review()
{
	return function_exists('get_user_class') && get_user_class() >= UC_MODERATOR;
}

function test_torrents_can_manage()
{
	return test_torrents_can_help();
}

function test_torrents_fetch($torrent_id)
{
	$torrent_id = (int)$torrent_id;
	if ($torrent_id <= 0) {
		return null;
	}

	$has_review_schema = test_torrents_review_schema_ready();
	$review_fields = $has_review_schema
		? "t.test_status, t.test_checked_at, t.test_checked_by, t.test_check_comment,"
		: "'pending' AS test_status, NULL AS test_checked_at, 0 AS test_checked_by, NULL AS test_check_comment,";
	$review_join = $has_review_schema
		? "LEFT JOIN users AS cu ON cu.id = t.test_checked_by"
		: "LEFT JOIN users AS cu ON cu.id = 0";

	$res = sql_query("
		SELECT t.id, t.name, t.owner, t.visible, t.banned, t.is_test, t.test_approved_at, t.test_approved_by,
		       t.test_helper_user_id, t.test_helper_until,
		       $review_fields
		       u.username, u.class,
		       hu.username AS helper_username, hu.class AS helper_class,
		       cu.username AS checked_username, cu.class AS checked_class
		FROM torrents AS t
		LEFT JOIN users AS u ON u.id = t.owner
		LEFT JOIN users AS hu ON hu.id = t.test_helper_user_id
		$review_join
		WHERE t.id = $torrent_id
		LIMIT 1
	") or sqlerr(__FILE__, __LINE__);

	$row = mysqli_fetch_assoc($res);
	return $row ?: null;
}

function test_torrents_notify_owner(array $torrent, $subject, $body)
{
	$owner = (int)($torrent['owner'] ?? 0);
	if ($owner <= 0 || !function_exists('send_pm')) {
		return;
	}

	send_pm(0, $owner, get_date_time(), (string)$subject, (string)$body);
}

function test_torrents_link($torrent_id)
{
	global $DEFAULTBASEURL;

	$base = isset($DEFAULTBASEURL) ? rtrim((string)$DEFAULTBASEURL, '/') : '';
	return $base . '/details.php?id=' . (int)$torrent_id;
}

function test_torrents_admin_name($admin_id)
{
	global $CURUSER;

	if (is_array($CURUSER) && (int)($CURUSER['id'] ?? 0) === (int)$admin_id && !empty($CURUSER['username'])) {
		return (string)$CURUSER['username'];
	}

	$admin_id = (int)$admin_id;
	if ($admin_id <= 0) {
		return 'Администрация';
	}

	$res = sql_query("SELECT username FROM users WHERE id = $admin_id LIMIT 1") or sqlerr(__FILE__, __LINE__);
	$row = mysqli_fetch_assoc($res);
	return $row ? (string)$row['username'] : 'Администрация';
}

function test_torrents_review_message(array $torrent, $title, $comment = '')
{
	$link = test_torrents_link((int)$torrent['id']);
	$name = (string)($torrent['name'] ?? ('#' . (int)$torrent['id']));
	$body = $title . "\n\nРаздача: " . $name . "\nСсылка: " . $link;
	$comment = trim((string)$comment);
	if ($comment !== '') {
		$body .= "\n\nКомментарий проверки:\n" . $comment;
	}
	return $body;
}

function test_torrents_set_helper($torrent_id, $helper_id, $minutes = 60)
{
	$torrent_id = (int)$torrent_id;
	$helper_id = (int)$helper_id;
	$minutes = max(5, min(1440, (int)$minutes));

	if ($torrent_id <= 0 || $helper_id <= 0) {
		return false;
	}

	$status_set = test_torrents_review_schema_ready()
		? ", test_status = IF(test_status IN ('pending','changes'), 'checking', test_status)"
		: '';

	sql_query("
		UPDATE torrents
		SET test_helper_user_id = $helper_id,
			test_helper_until = DATE_ADD(NOW(), INTERVAL $minutes MINUTE)
			$status_set
		WHERE id = $torrent_id
		  AND is_test = 'yes'
		  AND visible = 'yes'
		  AND banned != 'yes'
		  AND (
			  test_helper_user_id = 0
			  OR test_helper_until IS NULL
			  OR test_helper_until <= NOW()
			  OR test_helper_user_id = $helper_id
		  )
	") or sqlerr(__FILE__, __LINE__);

	return true;
}

function test_torrents_clear_helper($torrent_id, $user_id = 0, $force = false)
{
	$torrent_id = (int)$torrent_id;
	$user_id = (int)$user_id;

	if ($torrent_id <= 0) {
		return false;
	}

	$extra = $force || $user_id <= 0 ? '' : ' AND test_helper_user_id = ' . $user_id;
	sql_query("
		UPDATE torrents
		SET test_helper_user_id = 0,
			test_helper_until = NULL
		WHERE id = $torrent_id
		  AND is_test = 'yes'
		  $extra
	") or sqlerr(__FILE__, __LINE__);

	return true;
}

function test_torrents_review_action($action, $torrent_id, $reviewer_id, $comment = '')
{
	$action = (string)$action;
	$torrent_id = (int)$torrent_id;
	$reviewer_id = (int)$reviewer_id;
	$comment = trim((string)$comment);

	$torrent = test_torrents_fetch($torrent_id);
	if (!$torrent) {
		return 'Тестовая раздача не найдена.';
	}

	if ($action !== 'reopen' && (string)($torrent['is_test'] ?? 'no') !== 'yes') {
		return 'Эта раздача уже не находится в тестовой очереди.';
	}

	$reviewer_name = test_torrents_admin_name($reviewer_id);
	$has_review_schema = test_torrents_review_schema_ready();
	$comment_sql = sqlesc($comment);

	if ($action === 'approve') {
		$review_set = $has_review_schema
			? "test_status = 'approved', test_checked_at = NOW(), test_checked_by = $reviewer_id, test_check_comment = $comment_sql,"
			: '';

		sql_query("
			UPDATE torrents
			SET is_test = 'no',
				$review_set
				test_approved_at = NOW(),
				test_approved_by = $reviewer_id,
				test_helper_user_id = 0,
				test_helper_until = NULL,
				moderated = 'yes',
				moderatedby = $reviewer_id,
				visible = 'yes',
				banned = 'no',
				added = NOW(),
				last_action = NOW()
			WHERE id = $torrent_id
			  AND is_test = 'yes'
		") or sqlerr(__FILE__, __LINE__);

		test_torrents_notify_owner(
			$torrent,
			'Тестовая раздача одобрена',
			test_torrents_review_message($torrent, 'Ваша тестовая раздача одобрена и опубликована.', $comment)
		);
		write_log('Тестовая раздача #' . $torrent_id . ' (' . $torrent['name'] . ') одобрена пользователем ' . $reviewer_name, '5DDB6E', 'torrent');
		return 'Раздача одобрена и опубликована.';
	}

	if (!$has_review_schema) {
		return 'Схема проверки не обновлена. Включите KZ_AUTO_MIGRATIONS и откройте страницу ещё раз.';
	}

	if (($action === 'changes' || $action === 'reject') && $comment === '') {
		return 'Для возврата или отклонения нужно указать комментарий проверки.';
	}

	if ($action === 'changes') {
		sql_query("
			UPDATE torrents
			SET test_status = 'changes',
				test_checked_at = NOW(),
				test_checked_by = $reviewer_id,
				test_check_comment = $comment_sql,
				test_helper_user_id = 0,
				test_helper_until = NULL,
				moderated = 'no'
			WHERE id = $torrent_id
			  AND is_test = 'yes'
		") or sqlerr(__FILE__, __LINE__);

		test_torrents_notify_owner(
			$torrent,
			'Тестовая раздача возвращена на доработку',
			test_torrents_review_message($torrent, 'Ваша тестовая раздача проверена и возвращена на доработку.', $comment)
		);
		write_log('Тестовая раздача #' . $torrent_id . ' (' . $torrent['name'] . ') возвращена на доработку пользователем ' . $reviewer_name, 'F2C94C', 'torrent');
		return 'Раздача возвращена на доработку.';
	}

	if ($action === 'reject') {
		sql_query("
			UPDATE torrents
			SET test_status = 'rejected',
				test_checked_at = NOW(),
				test_checked_by = $reviewer_id,
				test_check_comment = $comment_sql,
				test_helper_user_id = 0,
				test_helper_until = NULL,
				visible = 'no',
				banned = 'yes',
				moderated = 'no'
			WHERE id = $torrent_id
			  AND is_test = 'yes'
		") or sqlerr(__FILE__, __LINE__);

		test_torrents_notify_owner(
			$torrent,
			'Тестовая раздача отклонена',
			test_torrents_review_message($torrent, 'Ваша тестовая раздача отклонена.', $comment)
		);
		write_log('Тестовая раздача #' . $torrent_id . ' (' . $torrent['name'] . ') отклонена пользователем ' . $reviewer_name, 'F25B61', 'torrent');
		return 'Раздача отклонена и скрыта.';
	}

	if ($action === 'reopen') {
		sql_query("
			UPDATE torrents
			SET test_status = 'pending',
				test_checked_at = NULL,
				test_checked_by = 0,
				test_check_comment = '',
				test_helper_user_id = 0,
				test_helper_until = NULL,
				visible = 'yes',
				banned = 'no',
				moderated = 'no',
				is_test = 'yes'
			WHERE id = $torrent_id
		") or sqlerr(__FILE__, __LINE__);

		write_log('Тестовая раздача #' . $torrent_id . ' (' . $torrent['name'] . ') возвращена в очередь пользователем ' . $reviewer_name, '5DDB6E', 'torrent');
		return 'Раздача возвращена в очередь проверки.';
	}

	return 'Неизвестное действие проверки.';
}

?>
