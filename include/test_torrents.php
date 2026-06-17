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

	if (!test_torrents_column_exists('test_approved_at')) {
		sql_query("ALTER TABLE torrents ADD test_approved_at datetime NULL DEFAULT NULL AFTER is_test") or sqlerr(__FILE__, __LINE__);
	}

	if (!test_torrents_column_exists('test_approved_by')) {
		sql_query("ALTER TABLE torrents ADD test_approved_by int(10) unsigned NOT NULL DEFAULT '0' AFTER test_approved_at") or sqlerr(__FILE__, __LINE__);
	}

	if (!test_torrents_column_exists('test_helper_user_id')) {
		sql_query("ALTER TABLE torrents ADD test_helper_user_id int(10) unsigned NOT NULL DEFAULT '0' AFTER test_approved_by") or sqlerr(__FILE__, __LINE__);
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

function test_torrents_can_manage()
{
	return function_exists('get_user_class') && get_user_class() >= UC_UPLOADER;
}

?>
