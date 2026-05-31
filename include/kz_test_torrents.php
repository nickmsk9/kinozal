<?php

if (!defined('IN_TRACKER')) {
	die('Прямой вызов запрещён.');
}

function kz_test_torrents_column_exists($column)
{
	$res = sql_query("SHOW COLUMNS FROM torrents LIKE " . sqlesc($column));
	return $res && mysqli_num_rows($res) > 0;
}

function kz_test_torrents_index_exists($index)
{
	$res = sql_query("SHOW INDEX FROM torrents WHERE Key_name = " . sqlesc($index));
	return $res && mysqli_num_rows($res) > 0;
}

function kz_test_torrents_ensure_schema()
{
	static $done = false;

	if ($done) {
		return;
	}

	if (!kz_test_torrents_column_exists('is_test')) {
		sql_query("ALTER TABLE torrents ADD is_test enum('yes','no') NOT NULL DEFAULT 'no' AFTER multitracker") or sqlerr(__FILE__, __LINE__);
	}

	if (!kz_test_torrents_column_exists('test_approved_at')) {
		sql_query("ALTER TABLE torrents ADD test_approved_at datetime NULL DEFAULT NULL AFTER is_test") or sqlerr(__FILE__, __LINE__);
	}

	if (!kz_test_torrents_column_exists('test_approved_by')) {
		sql_query("ALTER TABLE torrents ADD test_approved_by int(10) unsigned NOT NULL DEFAULT '0' AFTER test_approved_at") or sqlerr(__FILE__, __LINE__);
	}

	if (!kz_test_torrents_column_exists('test_helper_user_id')) {
		sql_query("ALTER TABLE torrents ADD test_helper_user_id int(10) unsigned NOT NULL DEFAULT '0' AFTER test_approved_by") or sqlerr(__FILE__, __LINE__);
	}

	if (!kz_test_torrents_column_exists('test_helper_until')) {
		sql_query("ALTER TABLE torrents ADD test_helper_until datetime NULL DEFAULT NULL AFTER test_helper_user_id") or sqlerr(__FILE__, __LINE__);
	}

	if (!kz_test_torrents_index_exists('is_test_visible')) {
		sql_query("ALTER TABLE torrents ADD KEY is_test_visible (is_test, visible, banned, added)") or sqlerr(__FILE__, __LINE__);
	}

	if (!kz_test_torrents_index_exists('test_helper_until')) {
		sql_query("ALTER TABLE torrents ADD KEY test_helper_until (test_helper_until)") or sqlerr(__FILE__, __LINE__);
	}

	$done = true;
}

function kz_test_torrents_h($value)
{
	return htmlspecialchars_uni((string)$value);
}

function kz_test_torrents_can_manage()
{
	return function_exists('get_user_class') && get_user_class() >= UC_UPLOADER;
}

?>
