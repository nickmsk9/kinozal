<?php

if (!defined('IN_TRACKER')) {
	die('Direct access denied.');
}

function kz_rep_h($value)
{
	return htmlspecialchars_uni((string)$value);
}

function kz_rep_table_exists($table)
{
	$table = trim((string)$table);
	if ($table === '' || !preg_match('/^[a-zA-Z0-9_]+$/', $table)) {
		return false;
	}

	$res = sql_query("SHOW TABLES LIKE " . sqlesc($table, true)) or sqlerr(__FILE__, __LINE__);
	return mysqli_num_rows($res) > 0;
}

function kz_reputation_install_schema()
{
	sql_query("
		CREATE TABLE IF NOT EXISTS site_settings (
			setting_key VARCHAR(80) NOT NULL,
			setting_value TEXT NOT NULL,
			PRIMARY KEY (setting_key)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
	") or sqlerr(__FILE__, __LINE__);

	sql_query("
		CREATE TABLE IF NOT EXISTS simpaty (
			id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
			touserid INT(10) UNSIGNED NOT NULL DEFAULT 0,
			fromuserid INT(10) UNSIGNED NOT NULL DEFAULT 0,
			fromusername VARCHAR(40) NOT NULL DEFAULT '',
			bad TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
			good TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
			type VARCHAR(60) NOT NULL DEFAULT '',
			respect_time DATETIME NULL DEFAULT NULL,
			description TEXT NOT NULL,
			PRIMARY KEY (id),
			KEY touserid (touserid),
			KEY fromuserid (fromuserid),
			KEY respect_time (respect_time),
			KEY profile_wall (touserid, respect_time)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
	") or sqlerr(__FILE__, __LINE__);

	sql_query("
		INSERT INTO site_settings (setting_key, setting_value)
		VALUES ('reputation_daily_limit', '1'), ('reputation_signup_value', '1')
		ON DUPLICATE KEY UPDATE setting_value = setting_value
	") or sqlerr(__FILE__, __LINE__);
}

function kz_reputation_setting($key, $default)
{
	$key = (string)$key;
	if (!kz_rep_table_exists('site_settings')) {
		return $default;
	}

	$res = sql_query("SELECT setting_value FROM site_settings WHERE setting_key = " . sqlesc($key, true) . " LIMIT 1") or sqlerr(__FILE__, __LINE__);
	$row = mysqli_fetch_assoc($res);

	return $row ? $row['setting_value'] : $default;
}

function kz_reputation_set_setting($key, $value)
{
	kz_reputation_install_schema();
	sql_query("
		INSERT INTO site_settings (setting_key, setting_value)
		VALUES (" . sqlesc($key, true) . ", " . sqlesc((string)$value, true) . ")
		ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)
	") or sqlerr(__FILE__, __LINE__);
}

function kz_reputation_daily_limit()
{
	return max(0, (int)kz_reputation_setting('reputation_daily_limit', 1));
}

function kz_reputation_signup_value()
{
	return max(0, (int)kz_reputation_setting('reputation_signup_value', 1));
}

function kz_reputation_given_today($userid)
{
	$userid = (int)$userid;
	if (!is_valid_id($userid) || !kz_rep_table_exists('simpaty')) {
		return 0;
	}

	$res = sql_query("
		SELECT COUNT(*)
		FROM simpaty
		WHERE fromuserid = $userid
		  AND respect_time >= CURDATE()
		  AND respect_time < DATE_ADD(CURDATE(), INTERVAL 1 DAY)
	") or sqlerr(__FILE__, __LINE__);
	$row = mysqli_fetch_row($res);

	return (int)($row[0] ?? 0);
}

function kz_reputation_left_today($userid)
{
	return max(0, kz_reputation_daily_limit() - kz_reputation_given_today((int)$userid));
}

function kz_reputation_date($value)
{
	if (empty($value) || $value === '0000-00-00 00:00:00') {
		return '';
	}

	$ts = strtotime((string)$value);
	if (!$ts) {
		return kz_rep_h($value);
	}

	return date('d.m.Y', $ts) . ' &#1074; ' . date('H:i', $ts);
}

function kz_reputation_topic($type)
{
	$type = (string)$type;

	if ($type === '' || $type === 'profile') {
		return '&#1042; &#1087;&#1088;&#1086;&#1092;&#1080;&#1083;&#1077; &#1087;&#1086;&#1083;&#1100;&#1079;&#1086;&#1074;&#1072;&#1090;&#1077;&#1083;&#1103;';
	}

	if (preg_match('/^torrent([0-9]+)$/', $type, $m)) {
		return '<a href="/details.php?id=' . (int)$m[1] . '" class="sba">&#1056;&#1072;&#1079;&#1076;&#1072;&#1095;&#1072;</a>';
	}

	return kz_rep_h($type);
}

function kz_reputation_description($value)
{
	$text = html_entity_decode((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
	return nl2br(kz_rep_h($text));
}

function kz_reputation_user_link($user)
{
	$userid = (int)($user['user_id'] ?? $user['id'] ?? 0);
	$username = kz_rep_h($user['username'] ?? '');
	$class = (int)($user['class'] ?? UC_USER);
	$icons = function_exists('get_user_icons') ? get_user_icons(array_merge($user, array('id' => $userid))) : '';

	if (!is_valid_id($userid) || $username === '') {
		return '<i>unknown</i>';
	}

	return '<a href="/userdetails.php?id=' . $userid . '" class="u' . $class . '">' . $username . '</a>' . $icons;
}

function kz_reputation_rows($userid, $type = 1, $limit = 0)
{
	$userid = (int)$userid;
	$type = (int)$type;
	$limit = (int)$limit;

	if (!is_valid_id($userid) || !kz_rep_table_exists('simpaty')) {
		return array();
	}

	$where = $type === 2 ? "s.fromuserid = $userid" : "s.touserid = $userid";
	$joinField = $type === 2 ? 's.touserid' : 's.fromuserid';
	$limitSql = $limit > 0 ? " LIMIT $limit" : '';

	$res = sql_query("
		SELECT
			s.id AS rep_id,
			s.touserid,
			s.fromuserid,
			s.fromusername,
			s.bad,
			s.good,
			s.type,
			s.respect_time,
			s.description,
			u.id AS user_id,
			u.username,
			u.class,
			u.donor,
			u.gender,
			u.birthday,
			u.warned,
			u.enabled,
			u.uploaded,
			u.downloaded
		FROM simpaty AS s
		LEFT JOIN users AS u ON u.id = $joinField
		WHERE $where
		ORDER BY s.respect_time DESC, s.id DESC
		$limitSql
	") or sqlerr(__FILE__, __LINE__);

	$rows = array();
	while ($row = mysqli_fetch_assoc($res)) {
		$rows[] = $row;
	}

	return $rows;
}

function kz_reputation_count($userid, $type = 1)
{
	$userid = (int)$userid;
	$type = (int)$type;
	if (!is_valid_id($userid) || !kz_rep_table_exists('simpaty')) {
		return 0;
	}

	$where = $type === 2 ? "fromuserid = $userid" : "touserid = $userid";
	$res = sql_query("SELECT COUNT(*) FROM simpaty WHERE $where") or sqlerr(__FILE__, __LINE__);
	$row = mysqli_fetch_row($res);

	return (int)($row[0] ?? 0);
}

function kz_reputation_table_html($rows, $profile_class, $type = 1, $latest = false)
{
	if (!$rows) {
		return '';
	}

	$type = (int)$type;
	$fromTitle = $type === 2
		? '&#1050;&#1086;&#1084;&#1091;'
		: '&#1054;&#1090; &#1082;&#1086;&#1075;&#1086;';
	$reviewTitle = $latest
		? '&#1055;&#1086;&#1089;&#1083;&#1077;&#1076;&#1085;&#1080;&#1077; &#1086;&#1090;&#1079;&#1099;&#1074;&#1099; &#1082; &#1088;&#1077;&#1087;&#1091;&#1090;&#1072;&#1094;&#1080;&#1080;'
		: '&#1054;&#1090;&#1079;&#1099;&#1074; &#1082; &#1088;&#1077;&#1087;&#1091;&#1090;&#1072;&#1094;&#1080;&#1080;';

	$html = "<div class='bx2_0'><table class='w100p brd'>\n";
	$html .= "<tr><th class='w150 " . kz_rep_h($profile_class) . "'>$fromTitle</th><th class='" . kz_rep_h($profile_class) . "'>$reviewTitle</th><th class='w150 " . kz_rep_h($profile_class) . "'>&#1058;&#1077;&#1084;&#1072;</th></tr>";

	foreach ($rows as $row) {
		$mark = ((int)$row['bad'] === 1) ? '<b class="red">-</b> ' : '<b class="green">+</b> ';
		$html .= '<tr>';
		$html .= '<td>' . kz_reputation_user_link($row) . '<br>' . kz_reputation_date($row['respect_time']) . '</td>';
		$html .= "<td class=''>" . $mark . kz_reputation_description($row['description']) . '</td>';
		$html .= '<td>' . kz_reputation_topic($row['type']) . '</td>';
		$html .= '</tr>';
	}

	$html .= '</table></div>';
	return $html;
}

function kz_profile_menu_html($user, $viewer)
{
	$id = (int)$user['id'];
	$class = 'u' . (int)$user['class'];
	$avatar = !empty($user['avatar']) ? kz_rep_h($user['avatar']) : '/pic/default_avatar.gif';
	$reputation = (int)($user['simpaty'] ?? 0);
	$bonus = isset($viewer['bonus']) ? number_format((float)$viewer['bonus'], 0, '.', ' ') : 0;
	$isOwn = !empty($viewer['id']) && (int)$viewer['id'] === $id;
	$hash = kz_rep_h($viewer['hash4u'] ?? ($viewer['logout_hash'] ?? ''));

	$html = '<ul class="men ' . $class . ' w200">';
	$html .= '<li class="img"><a href="/userdetails.php?id=' . $id . '"><img src="' . $avatar . '" class="p200" alt=""></a></li>';
	$html .= '<li class="tp">&#1052;&#1077;&#1085;&#1102; &#1087;&#1086;&#1083;&#1100;&#1079;&#1086;&#1074;&#1072;&#1090;&#1077;&#1083;&#1103;</li>';

	if ($isOwn) {
		$html .= '<li><span class="bulet"></span><a href="/message.php">&#1051;&#1080;&#1095;&#1085;&#1099;&#1077; &#1089;&#1086;&#1086;&#1073;&#1097;&#1077;&#1085;&#1080;&#1103;</a></li>';
		$html .= '<li><span class="bulet"></span><a href="/userdetails.php?id=' . $id . '">&#1052;&#1086;&#1081; &#1087;&#1088;&#1086;&#1092;&#1080;&#1083;&#1100;</a></li>';
		$html .= '<li><span class="bulet"></span><a href="/my.php">&#1056;&#1077;&#1076;&#1072;&#1082;&#1090;&#1080;&#1088;&#1086;&#1074;&#1072;&#1090;&#1100; &#1087;&#1088;&#1086;&#1092;&#1080;&#1083;&#1100;</a></li>';
		$html .= '<li><span class="bulet"></span><a href="/mygroups.php">&#1052;&#1086;&#1080; &#1075;&#1088;&#1091;&#1087;&#1087;&#1099;</a></li>';
		$html .= '<li><span class="bulet"></span><a href="/friends.php?id=' . $id . '">&#1052;&#1086;&#1081; &#1089;&#1087;&#1080;&#1089;&#1086;&#1082; &#1076;&#1088;&#1091;&#1079;&#1077;&#1081;</a></li>';
		$html .= '<li class="sf"><span class="bulet"></span><a href="/mytorrents.php?id=' . $id . '">&#1052;&#1086;&#1080; &#1088;&#1072;&#1079;&#1076;&#1072;&#1095;&#1080;</a></li>';
	} else {
		$html .= '<li><span class="bulet"></span><a href="/message.php?action=sendmessage&amp;receiver=' . $id . '">&#1054;&#1090;&#1087;&#1088;&#1072;&#1074;&#1080;&#1090;&#1100; &#1089;&#1086;&#1086;&#1073;&#1097;&#1077;&#1085;&#1080;&#1077;</a></li>';
		$html .= '<li><span class="bulet"></span><a href="/userdetails.php?id=' . $id . '">&#1055;&#1088;&#1086;&#1092;&#1080;&#1083;&#1100; &#1087;&#1086;&#1083;&#1100;&#1079;&#1086;&#1074;&#1072;&#1090;&#1077;&#1083;&#1103;</a></li>';
		$html .= '<li class="sf"><span class="bulet"></span><a href="/mytorrents.php?userid=' . $id . '">&#1056;&#1072;&#1079;&#1076;&#1072;&#1095;&#1080; &#1087;&#1086;&#1083;&#1100;&#1079;&#1086;&#1074;&#1072;&#1090;&#1077;&#1083;&#1103;</a></li>';
	}

	$html .= '<li class="tp">&#1056;&#1077;&#1087;&#1091;&#1090;&#1072;&#1094;&#1080;&#1103;<span class="floatright"><a href="/pay_mode_b.php?userid=' . $id . '&amp;vote=minus" title="&#1055;&#1086;&#1085;&#1080;&#1079;&#1080;&#1090;&#1100; &#1088;&#1077;&#1087;&#1091;&#1090;&#1072;&#1094;&#1080;&#1102;"><img border="0" src="/pic/minus.gif" alt=""></a> <b>' . $reputation . '</b> <a href="/pay_mode_b.php?userid=' . $id . '&amp;vote=plus" title="&#1055;&#1086;&#1074;&#1099;&#1089;&#1080;&#1090;&#1100; &#1088;&#1077;&#1087;&#1091;&#1090;&#1072;&#1094;&#1080;&#1102;"><img border="0" src="/pic/plus.gif" alt=""></a></span></li>';

	if (!$isOwn) {
		$html .= '<li><span class="bulet"></span><a href="/pay_mode_b.php?userid=' . $id . '">&#1054;&#1089;&#1090;&#1072;&#1074;&#1080;&#1090;&#1100; &#1086;&#1090;&#1079;&#1099;&#1074;</a></li>';
	}

	$html .= '<li><span class="bulet"></span><a href="/user_reputation.php?id=' . $id . '">&#1055;&#1086;&#1083;&#1091;&#1095;&#1077;&#1085;&#1085;&#1099;&#1077; &#1086;&#1090;&#1079;&#1099;&#1074;&#1099;</a></li>';
	$html .= '<li><span class="bulet"></span><a href="/user_reputation.php?id=' . $id . '&amp;type=2">&#1054;&#1090;&#1076;&#1072;&#1085;&#1085;&#1099;&#1077; &#1086;&#1090;&#1079;&#1099;&#1074;&#1099;</a></li>';

	if ($isOwn) {
		$html .= '<li class="tp">&#1047;&#1072;&#1082;&#1083;&#1072;&#1076;&#1082;&#1080;</li>';
		$html .= '<li><span class="bulet"></span><a href="/bookmarks.php?type=1">&#1056;&#1072;&#1079;&#1076;&#1072;&#1095;&#1080;</a></li>';
		$html .= '<li><span class="bulet"></span><a href="/bookmarks.php?type=2">&#1043;&#1088;&#1091;&#1087;&#1087;&#1099;</a></li>';
		$html .= '<li><span class="bulet"></span><a href="/bookmarks.php?type=3">&#1055;&#1086;&#1083;&#1100;&#1079;&#1086;&#1074;&#1072;&#1090;&#1077;&#1083;&#1080;</a></li>';
		$html .= '<li class="sf"><span class="bulet"></span><a href="/bookmarks.php?type=4">&#1055;&#1077;&#1088;&#1089;&#1086;&#1085;&#1099;</a></li>';
	}

	$html .= '<li class="tp">&#1048;&#1089;&#1090;&#1086;&#1088;&#1080;&#1103;</li>';
	if ($isOwn) {
		$html .= '<li><span class="bulet"></span><a href="/hytorrents.php?id=' . $id . '">&#1057;&#1082;&#1072;&#1095;&#1072;&#1085;&#1085;&#1086;&#1075;&#1086;</a></li>';
	}
	$html .= '<li><span class="bulet"></span><a href="/userhistory.php?id=' . $id . '">&#1050;&#1086;&#1084;&#1084;&#1077;&#1085;&#1090;&#1072;&#1088;&#1080;&#1077;&#1074;</a></li>';
	$html .= '<li class="sf"><span class="bulet"></span><a href="/uservotes.php?id=' . $id . '">&#1043;&#1086;&#1083;&#1086;&#1089;&#1086;&#1074;&#1072;&#1085;&#1080;&#1081;</a></li>';

	if ($isOwn) {
		$html .= '<li class="tp">&#1043;&#1086;&#1083;&#1086;&#1089;&#1072;<span class="floatright b">' . $bonus . '</span></li>';
		$html .= '<li><span class="bulet"></span><a href="/pay.php">&#1055;&#1086;&#1083;&#1091;&#1095;&#1080;&#1090;&#1100; &#1075;&#1086;&#1083;&#1086;&#1089;&#1072;</a></li>';
		$html .= '<li><span class="bulet"></span><a href="/pay_mode.php">&#1059;&#1087;&#1088;&#1072;&#1074;&#1083;&#1077;&#1085;&#1080;&#1077; &#1075;&#1086;&#1083;&#1086;&#1089;&#1072;&#1084;&#1080;</a></li>';
		$html .= '<li><span class="bulet"></span><a href="/pay_mode.php">&#1054;&#1089;&#1090;&#1072;&#1074;&#1080;&#1090;&#1100; &#1087;&#1086;&#1078;&#1077;&#1083;&#1072;&#1085;&#1080;&#1077;</a></li>';
		$html .= '<li><span class="bulet"></span><a href="/pay_mode.php">&#1054;&#1073;&#1085;&#1091;&#1083;&#1080;&#1090;&#1100; &#1089;&#1095;&#1077;&#1090;&#1095;&#1080;&#1082; &#1089;&#1082;&#1072;&#1095;&#1080;&#1074;&#1072;&#1085;&#1080;&#1081;</a></li>';
	} else {
		$html .= '<li class="tp">&#1044;&#1077;&#1081;&#1089;&#1090;&#1074;&#1080;&#1103;</li>';
		$html .= '<li><span class="bulet"></span><a href="/bookmarks.php?type=3&amp;add=' . $id . '&amp;hash4u=' . $hash . '">&#1042;&#1085;&#1077;&#1089;&#1090;&#1080; &#1074; &#1079;&#1072;&#1082;&#1083;&#1072;&#1076;&#1082;&#1080;</a></li>';
		$html .= '<li><span class="bulet"></span><a href="/friends.php?action=add&amp;type=friend&amp;targetid=' . $id . '">&#1042;&#1085;&#1077;&#1089;&#1090;&#1080; &#1074; &#1076;&#1088;&#1091;&#1079;&#1100;&#1103;</a></li>';
		$html .= '<li><span class="bulet"></span><a href="/friends.php?action=add&amp;type=block&amp;targetid=' . $id . '">&#1042;&#1085;&#1077;&#1089;&#1090;&#1080; &#1074; &#1080;&#1075;&#1085;&#1086;&#1088;</a></li>';
		$html .= '<li class="tp">&#1043;&#1086;&#1083;&#1086;&#1089;&#1072;</li>';
		$html .= '<li><span class="bulet"></span><a href="/pay_mode_b.php?userid=' . $id . '">&#1059;&#1087;&#1088;&#1072;&#1074;&#1083;&#1077;&#1085;&#1080;&#1077; &#1075;&#1086;&#1083;&#1086;&#1089;&#1072;&#1084;&#1080;</a></li>';
		$html .= '<li><span class="bulet"></span><a href="/pay_mode_b.php?userid=' . $id . '&amp;vote=plus">&#1055;&#1086;&#1076;&#1072;&#1088;&#1080;&#1090;&#1100; &#1088;&#1077;&#1081;&#1090;&#1080;&#1085;&#1075;</a></li>';
		$html .= '<li><span class="bulet"></span><a href="/pay_mode_b.php?userid=' . $id . '&amp;vote=plus">&#1055;&#1086;&#1076;&#1072;&#1088;&#1080;&#1090;&#1100; &#1075;&#1086;&#1083;&#1086;&#1089;&#1072;</a></li>';
	}

	$html .= '</ul>';
	return $html;
}

function kz_reputation_add($targetid, $direction, $description)
{
	global $CURUSER;

	kz_reputation_install_schema();

	$targetid = (int)$targetid;
	$direction = $direction === 'minus' ? 'minus' : 'plus';
	$description = trim((string)$description);

	if (!is_valid_id($targetid)) {
		stderr('&#1054;&#1096;&#1080;&#1073;&#1082;&#1072;', '&#1053;&#1077;&#1074;&#1077;&#1088;&#1085;&#1099;&#1081; ID.');
	}

	if (empty($CURUSER['id']) || (int)$CURUSER['id'] === $targetid) {
		stderr('&#1054;&#1096;&#1080;&#1073;&#1082;&#1072;', '&#1053;&#1077;&#1083;&#1100;&#1079;&#1103; &#1080;&#1079;&#1084;&#1077;&#1085;&#1103;&#1090;&#1100; &#1088;&#1077;&#1087;&#1091;&#1090;&#1072;&#1094;&#1080;&#1102; &#1089;&#1072;&#1084;&#1086;&#1084;&#1091; &#1089;&#1077;&#1073;&#1077;.');
	}

	if (($CURUSER['warned'] ?? 'no') === 'yes') {
		stderr('&#1054;&#1096;&#1080;&#1073;&#1082;&#1072;', '&#1055;&#1086;&#1083;&#1100;&#1079;&#1086;&#1074;&#1072;&#1090;&#1077;&#1083;&#1080; &#1089; &#1087;&#1088;&#1077;&#1076;&#1091;&#1087;&#1088;&#1077;&#1078;&#1076;&#1077;&#1085;&#1080;&#1077;&#1084; &#1085;&#1077; &#1084;&#1086;&#1075;&#1091;&#1090; &#1089;&#1090;&#1072;&#1074;&#1080;&#1090;&#1100; &#1088;&#1077;&#1087;&#1091;&#1090;&#1072;&#1094;&#1080;&#1102;.');
	}

	if ($description === '') {
		stderr('&#1054;&#1096;&#1080;&#1073;&#1082;&#1072;', '&#1054;&#1090;&#1079;&#1099;&#1074; &#1085;&#1077; &#1084;&#1086;&#1078;&#1077;&#1090; &#1073;&#1099;&#1090;&#1100; &#1087;&#1091;&#1089;&#1090;&#1099;&#1084;.');
	}

	if (mb_strlen($description, 'UTF-8') > 1000) {
		stderr('&#1054;&#1096;&#1080;&#1073;&#1082;&#1072;', '&#1054;&#1090;&#1079;&#1099;&#1074; &#1089;&#1083;&#1080;&#1096;&#1082;&#1086;&#1084; &#1076;&#1083;&#1080;&#1085;&#1085;&#1099;&#1081;.');
	}

	$res = sql_query("SELECT id FROM users WHERE id = $targetid LIMIT 1") or sqlerr(__FILE__, __LINE__);
	if (mysqli_num_rows($res) === 0) {
		stderr('&#1054;&#1096;&#1080;&#1073;&#1082;&#1072;', '&#1055;&#1086;&#1083;&#1100;&#1079;&#1086;&#1074;&#1072;&#1090;&#1077;&#1083;&#1100; &#1085;&#1077; &#1085;&#1072;&#1081;&#1076;&#1077;&#1085;.');
	}

	$left = kz_reputation_left_today((int)$CURUSER['id']);
	if ($left <= 0 && get_user_class() < UC_ADMINISTRATOR) {
		stderr('&#1054;&#1096;&#1080;&#1073;&#1082;&#1072;', '&#1057;&#1091;&#1090;&#1086;&#1095;&#1085;&#1099;&#1081; &#1083;&#1080;&#1084;&#1080;&#1090; &#1086;&#1090;&#1079;&#1099;&#1074;&#1086;&#1074; &#1080;&#1089;&#1095;&#1077;&#1088;&#1087;&#1072;&#1085;.');
	}

	$good = $direction === 'plus' ? 1 : 0;
	$bad = $direction === 'minus' ? 1 : 0;
	$deltaSql = $direction === 'plus' ? 'simpaty + 1' : 'simpaty - 1';

	sql_query("
		INSERT INTO simpaty (touserid, fromuserid, fromusername, bad, good, type, respect_time, description)
		VALUES (
			$targetid,
			" . (int)$CURUSER['id'] . ",
			" . sqlesc($CURUSER['username'], true) . ",
			$bad,
			$good,
			'profile',
			NOW(),
			" . sqlesc($description, true) . "
		)
	") or sqlerr(__FILE__, __LINE__);

	sql_query("UPDATE users SET simpaty = $deltaSql WHERE id = $targetid") or sqlerr(__FILE__, __LINE__);

	$msg = html_entity_decode('&#1055;&#1086;&#1083;&#1100;&#1079;&#1086;&#1074;&#1072;&#1090;&#1077;&#1083;&#1100;', ENT_QUOTES, 'UTF-8')
		. ' [url=userdetails.php?id=' . (int)$CURUSER['id'] . ']' . $CURUSER['username'] . '[/url] '
		. html_entity_decode('&#1086;&#1089;&#1090;&#1072;&#1074;&#1080;&#1083; &#1086;&#1090;&#1079;&#1099;&#1074; &#1082; &#1074;&#1072;&#1096;&#1077;&#1081; &#1088;&#1077;&#1087;&#1091;&#1090;&#1072;&#1094;&#1080;&#1080;:', ENT_QUOTES, 'UTF-8')
		. "\n[quote]" . $description . '[/quote]';
	$subject = html_entity_decode('&#1054;&#1090;&#1079;&#1099;&#1074; &#1082; &#1088;&#1077;&#1087;&#1091;&#1090;&#1072;&#1094;&#1080;&#1080;', ENT_QUOTES, 'UTF-8');
	send_pm(0, $targetid, get_date_time(), $subject, $msg);
}

?>
