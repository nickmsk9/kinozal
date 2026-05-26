<?php

if (!defined('IN_TRACKER')) {
	die('Hacking attempt!');
}

function kz_statuses_h($value)
{
	return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function kz_statuses_catalog()
{
	return array(
		'patron' => array('status_key' => 'patron', 'title' => 'Меценат', 'icon_class' => 's1', 'sort' => 1, 'auto' => 1),
		'girl' => array('status_key' => 'girl', 'title' => 'Девушка', 'icon_class' => 's_dv', 'sort' => 2, 'auto' => 1),
		'king' => array('status_key' => 'king', 'title' => 'Коро(ль,лева)', 'icon_class' => 's9-10', 'sort' => 3, 'auto' => 0),
		'loyal_seed' => array('status_key' => 'loyal_seed', 'title' => 'Верный сид', 'icon_class' => 's4', 'sort' => 4, 'auto' => 0),
		'rhetoric' => array('status_key' => 'rhetoric', 'title' => 'Риторик', 'icon_class' => 's5', 'sort' => 5, 'auto' => 0),
		'keeper' => array('status_key' => 'keeper', 'title' => 'Хранитель раздач', 'icon_class' => 's6', 'sort' => 6, 'auto' => 0),
		'birthday' => array('status_key' => 'birthday', 'title' => 'День рождения', 'icon_class' => 's_bday', 'sort' => 7, 'auto' => 1),
		'warned' => array('status_key' => 'warned', 'title' => 'Предупрежден', 'icon_class' => 's2', 'sort' => 8, 'auto' => 1),
		'low_ratio' => array('status_key' => 'low_ratio', 'title' => 'Предупрежден 1 Торрент', 'icon_class' => 's7', 'sort' => 9, 'auto' => 1),
		'disabled' => array('status_key' => 'disabled', 'title' => 'Отключен', 'icon_class' => 's_dis', 'sort' => 10, 'auto' => 1),
	);
}

function kz_statuses_ensure_schema()
{
	static $ready = false;

	if ($ready) {
		return;
	}

	sql_query("
		CREATE TABLE IF NOT EXISTS user_statuses (
			status_key VARCHAR(40) NOT NULL,
			title VARCHAR(100) NOT NULL,
			icon_class VARCHAR(40) NOT NULL,
			sort INT UNSIGNED NOT NULL DEFAULT 0,
			active TINYINT UNSIGNED NOT NULL DEFAULT 1,
			auto TINYINT UNSIGNED NOT NULL DEFAULT 0,
			PRIMARY KEY (status_key),
			KEY sort (sort),
			KEY active (active)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
	") or sqlerr(__FILE__, __LINE__);

	sql_query("
		CREATE TABLE IF NOT EXISTS user_status_assignments (
			userid INT UNSIGNED NOT NULL,
			status_key VARCHAR(40) NOT NULL,
			assigned_by INT UNSIGNED NOT NULL DEFAULT 0,
			assigned_at DATETIME NULL DEFAULT NULL,
			PRIMARY KEY (userid, status_key),
			KEY status_key (status_key),
			KEY assigned_at (assigned_at)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
	") or sqlerr(__FILE__, __LINE__);

	kz_statuses_seed_catalog();
	$ready = true;
}

function kz_statuses_seed_catalog()
{
	$values = array();

	foreach (kz_statuses_catalog() as $status) {
		$values[] = '('
			. sqlesc($status['status_key'], true) . ', '
			. sqlesc($status['title'], true) . ', '
			. sqlesc($status['icon_class'], true) . ', '
			. (int)$status['sort'] . ', 1, '
			. (int)$status['auto'] . ')';
	}

	sql_query("
		INSERT INTO user_statuses (status_key, title, icon_class, sort, active, auto)
		VALUES " . implode(', ', $values) . "
		ON DUPLICATE KEY UPDATE
			title = VALUES(title),
			icon_class = VALUES(icon_class),
			sort = VALUES(sort),
			auto = VALUES(auto)
	") or sqlerr(__FILE__, __LINE__);
}

function kz_statuses_all()
{
	kz_statuses_ensure_schema();

	static $rows = null;

	if ($rows !== null) {
		return $rows;
	}

	$rows = array();
	$res = sql_query("
		SELECT status_key, title, icon_class, sort, active, auto
		FROM user_statuses
		ORDER BY sort ASC, status_key ASC
	") or sqlerr(__FILE__, __LINE__);

	while ($row = mysqli_fetch_assoc($res)) {
		$rows[$row['status_key']] = $row;
	}

	return $rows;
}

function kz_statuses_auto_keys($user)
{
	$keys = array();

	if (($user['donor'] ?? 'no') === 'yes') {
		$keys['patron'] = true;
	}

	if ((string)($user['gender'] ?? '') === '2') {
		$keys['girl'] = true;
	}

	$birthday = (string)($user['birthday'] ?? '');
	if ($birthday !== '' && $birthday !== '0000-00-00' && substr($birthday, 5) === date('m-d')) {
		$keys['birthday'] = true;
	}

	if (($user['warned'] ?? 'no') === 'yes') {
		$keys['warned'] = true;
	}

	if (($user['enabled'] ?? 'yes') === 'no') {
		$keys['disabled'] = true;
	}

	$downloaded = (float)($user['downloaded'] ?? 0);
	$uploaded = (float)($user['uploaded'] ?? 0);

	if (($user['enabled'] ?? 'yes') === 'yes' && $downloaded >= 1073741824 && ($downloaded > 0 ? $uploaded / $downloaded : 1) < 0.7) {
		$keys['low_ratio'] = true;
	}

	return $keys;
}

function kz_statuses_load_user($userid)
{
	$userid = (int)$userid;

	if (!is_valid_id($userid)) {
		return null;
	}

	$res = sql_query("
		SELECT id, donor, gender, birthday, warned, enabled, uploaded, downloaded
		FROM users
		WHERE id = $userid
		LIMIT 1
	") or sqlerr(__FILE__, __LINE__);

	$row = mysqli_fetch_assoc($res);
	return $row ?: null;
}

function kz_statuses_for_user($user)
{
	kz_statuses_ensure_schema();

	static $cache = array();

	$userid = isset($user['id']) ? (int)$user['id'] : (isset($user['userid']) ? (int)$user['userid'] : 0);

	if (!is_valid_id($userid)) {
		return array();
	}

	$needed = array('donor', 'gender', 'birthday', 'warned', 'enabled', 'uploaded', 'downloaded');
	foreach ($needed as $field) {
		if (!array_key_exists($field, $user)) {
			if (!isset($cache[$userid]['user'])) {
				$cache[$userid]['user'] = kz_statuses_load_user($userid);
			}
			if (is_array($cache[$userid]['user'])) {
				$user = array_merge($cache[$userid]['user'], $user);
			}
			break;
		}
	}

	$statuses = kz_statuses_all();
	$keys = kz_statuses_auto_keys($user);

	$res = sql_query("
		SELECT status_key
		FROM user_status_assignments
		WHERE userid = $userid
	") or sqlerr(__FILE__, __LINE__);

	while ($row = mysqli_fetch_assoc($res)) {
		$keys[$row['status_key']] = true;
	}

	$result = array();
	foreach ($statuses as $key => $status) {
		if ((int)$status['active'] === 1 && isset($keys[$key])) {
			$result[] = $status;
		}
	}

	return $result;
}

function kz_statuses_user_icons_html($user)
{
	$statuses = kz_statuses_for_user($user);
	$html = '';

	foreach ($statuses as $status) {
		$html .= '<i class="i1 ' . kz_statuses_h($status['icon_class']) . '" title="' . kz_statuses_h($status['title']) . '"></i>';
	}

	return $html;
}

function kz_statuses_manual_keys($userid)
{
	kz_statuses_ensure_schema();

	$userid = (int)$userid;
	$keys = array();

	if (!is_valid_id($userid)) {
		return $keys;
	}

	$res = sql_query("SELECT status_key FROM user_status_assignments WHERE userid = $userid") or sqlerr(__FILE__, __LINE__);

	while ($row = mysqli_fetch_assoc($res)) {
		$keys[$row['status_key']] = true;
	}

	return $keys;
}

function kz_statuses_save_manual($userid, $selected_keys, $admin_id)
{
	kz_statuses_ensure_schema();

	$userid = (int)$userid;
	$admin_id = (int)$admin_id;

	if (!is_valid_id($userid)) {
		return;
	}

	$catalog = kz_statuses_catalog();
	$selected = array();

	foreach ((array)$selected_keys as $key) {
		$key = (string)$key;
		if (isset($catalog[$key]) && (int)$catalog[$key]['auto'] === 0) {
			$selected[$key] = true;
		}
	}

	sql_query("DELETE FROM user_status_assignments WHERE userid = $userid") or sqlerr(__FILE__, __LINE__);

	foreach (array_keys($selected) as $key) {
		sql_query("
			INSERT INTO user_status_assignments (userid, status_key, assigned_by, assigned_at)
			VALUES ($userid, " . sqlesc($key, true) . ", $admin_id, NOW())
		") or sqlerr(__FILE__, __LINE__);
	}
}

function kz_statuses_find_user_by_username($username)
{
	$username = trim((string)$username);

	if ($username === '') {
		return null;
	}

	$res = sql_query("
		SELECT id, username, class
		FROM users
		WHERE username = " . sqlesc($username, true) . "
		LIMIT 1
	") or sqlerr(__FILE__, __LINE__);

	$row = mysqli_fetch_assoc($res);
	return $row ?: null;
}
