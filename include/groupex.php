<?php

if (!defined('IN_TRACKER')) {
	die('Direct access denied.');
}

function kz_groups_h($value)
{
	return htmlspecialchars_uni((string)$value);
}

function kz_groups_types()
{
	return array(
		1 => 'Клуб',
		2 => 'Группы по интересам',
		3 => 'Тематические группы',
		4 => 'Релиз-группы',
	);
}

function kz_groups_categories()
{
	return array(
		1 => 'Общие интересы',
		2 => 'Фильмы',
		3 => 'Музыка',
		4 => 'Сериалы',
		5 => 'Мультфильмы',
		6 => 'Книги и Аудиокниги',
		8 => 'Программное обеспечение',
		9 => 'Игры',
		10 => 'Образование',
		11 => 'Другое',
	);
}

function kz_groups_subcategories()
{
	return array(
		10001 => array(1, 'Общение'),
		10002 => array(1, 'Коллекции'),
		10003 => array(1, 'Новости и события'),
		10015 => array(1, 'Другое'),
		20001 => array(2, 'Ретро-кино, классика, киноминиатюры'),
		20002 => array(2, 'Мировой кинематограф и его школы'),
		20003 => array(2, 'Авторское кино'),
		20004 => array(2, 'Документальное кино'),
		20005 => array(2, 'Другое'),
		30001 => array(3, 'Исполнители и жанры'),
		30002 => array(3, 'Концерты и клипы'),
		30003 => array(3, 'Другое'),
		40001 => array(4, 'Сериалы по странам'),
		40002 => array(4, 'Жанровые подборки'),
		40003 => array(4, 'Другое'),
		50001 => array(5, 'Анимация'),
		50002 => array(5, 'Аниме'),
		50003 => array(5, 'Другое'),
		60001 => array(6, 'Книги'),
		60002 => array(6, 'Аудиокниги'),
		60003 => array(6, 'Другое'),
		80001 => array(8, 'Windows'),
		80002 => array(8, 'Linux и macOS'),
		80003 => array(8, 'Графика и дизайн'),
		80004 => array(8, 'Другое'),
		90001 => array(9, 'PC'),
		90002 => array(9, 'Консоли'),
		90003 => array(9, 'Другое'),
		100001 => array(10, 'Языки'),
		100002 => array(10, 'Наука и учебные курсы'),
		100003 => array(10, 'Другое'),
		110001 => array(11, 'Другое'),
	);
}

function kz_groups_ensure_schema()
{
	sql_query("
		CREATE TABLE IF NOT EXISTS groupex_categories (
			id tinyint(3) unsigned NOT NULL,
			name varchar(120) NOT NULL default '',
			sort int(10) unsigned NOT NULL default '0',
			PRIMARY KEY (id),
			KEY sort (sort)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
	") or sqlerr(__FILE__, __LINE__);

	sql_query("
		CREATE TABLE IF NOT EXISTS groupex_subcategories (
			id int(10) unsigned NOT NULL,
			category_id tinyint(3) unsigned NOT NULL default '0',
			name varchar(160) NOT NULL default '',
			sort int(10) unsigned NOT NULL default '0',
			PRIMARY KEY (id),
			KEY category_sort (category_id, sort),
			KEY category_id (category_id)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
	") or sqlerr(__FILE__, __LINE__);

	sql_query("
		CREATE TABLE IF NOT EXISTS groupex_groups (
			id int(10) unsigned NOT NULL auto_increment,
			name varchar(160) NOT NULL default '',
			avatar text NOT NULL,
			private enum('no','yes') NOT NULL default 'no',
			type tinyint(3) unsigned NOT NULL default '1',
			cat tinyint(3) unsigned NOT NULL default '0',
			subcat int(10) unsigned NOT NULL default '0',
			description mediumtext NOT NULL,
			owner_id int(10) unsigned NOT NULL default '0',
			members_count int(10) unsigned NOT NULL default '0',
			torrents_count int(10) unsigned NOT NULL default '0',
			zabor_count int(10) unsigned NOT NULL default '0',
			visible enum('yes','no') NOT NULL default 'yes',
			created_at datetime NULL DEFAULT NULL,
			updated_at datetime NULL DEFAULT NULL,
			PRIMARY KEY (id),
			KEY name (name),
			KEY type (type),
			KEY cat (cat),
			KEY subcat (subcat),
			KEY owner_id (owner_id),
			KEY visible_created (visible, created_at),
			KEY members_count (members_count),
			KEY torrents_count (torrents_count),
			KEY zabor_count (zabor_count)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
	") or sqlerr(__FILE__, __LINE__);

	sql_query("
		CREATE TABLE IF NOT EXISTS groupex_members (
			id int(10) unsigned NOT NULL auto_increment,
			group_id int(10) unsigned NOT NULL default '0',
			userid int(10) unsigned NOT NULL default '0',
			role enum('owner','moderator','member') NOT NULL default 'member',
			status enum('member','pending','invited','blocked') NOT NULL default 'member',
			added_at datetime NULL DEFAULT NULL,
			updated_at datetime NULL DEFAULT NULL,
			PRIMARY KEY (id),
			UNIQUE KEY group_user (group_id, userid),
			KEY userid (userid),
			KEY status (status),
			KEY role (role)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
	") or sqlerr(__FILE__, __LINE__);

	sql_query("
		CREATE TABLE IF NOT EXISTS groupex_torrents (
			id int(10) unsigned NOT NULL auto_increment,
			group_id int(10) unsigned NOT NULL default '0',
			torrent_id int(10) unsigned NOT NULL default '0',
			added_by int(10) unsigned NOT NULL default '0',
			added_at datetime NULL DEFAULT NULL,
			PRIMARY KEY (id),
			UNIQUE KEY group_torrent (group_id, torrent_id),
			KEY torrent_id (torrent_id),
			KEY added_at (added_at)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
	") or sqlerr(__FILE__, __LINE__);

	sql_query("
		CREATE TABLE IF NOT EXISTS groupex_bookmarks (
			id int(10) unsigned NOT NULL auto_increment,
			userid int(10) unsigned NOT NULL default '0',
			group_id int(10) unsigned NOT NULL default '0',
			added_at datetime NULL DEFAULT NULL,
			PRIMARY KEY (id),
			UNIQUE KEY user_group (userid, group_id),
			KEY group_id (group_id)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
	") or sqlerr(__FILE__, __LINE__);

	sql_query("
		CREATE TABLE IF NOT EXISTS groupex_zabor (
			id int(10) unsigned NOT NULL auto_increment,
			group_id int(10) unsigned NOT NULL default '0',
			userid int(10) unsigned NOT NULL default '0',
			text mediumtext NOT NULL,
			ori_text mediumtext NOT NULL,
			added_at datetime NULL DEFAULT NULL,
			PRIMARY KEY (id),
			KEY group_added (group_id, added_at),
			KEY userid (userid)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
	") or sqlerr(__FILE__, __LINE__);

	sql_query("
		CREATE TABLE IF NOT EXISTS groupex_log (
			id int(10) unsigned NOT NULL auto_increment,
			group_id int(10) unsigned NOT NULL default '0',
			userid int(10) unsigned NOT NULL default '0',
			action varchar(40) NOT NULL default '',
			text varchar(255) NOT NULL default '',
			added_at datetime NULL DEFAULT NULL,
			PRIMARY KEY (id),
			KEY group_added (group_id, added_at),
			KEY userid (userid),
			KEY action (action)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
	") or sqlerr(__FILE__, __LINE__);

	$category_values = array();
	foreach (kz_groups_categories() as $id => $name) {
		$category_values[] = '(' . (int)$id . ', ' . sqlesc($name) . ', ' . ((int)$id * 10) . ')';
	}
	if ($category_values) {
		sql_query('INSERT IGNORE INTO groupex_categories (id, name, sort) VALUES ' . implode(',', $category_values)) or sqlerr(__FILE__, __LINE__);
	}

	$sub_values = array();
	$sort = array();
	foreach (kz_groups_subcategories() as $id => $subcat) {
		$cat_id = (int)$subcat[0];
		$sort[$cat_id] = isset($sort[$cat_id]) ? $sort[$cat_id] + 10 : 10;
		$sub_values[] = '(' . (int)$id . ', ' . $cat_id . ', ' . sqlesc($subcat[1]) . ', ' . (int)$sort[$cat_id] . ')';
	}
	if ($sub_values) {
		sql_query('INSERT IGNORE INTO groupex_subcategories (id, category_id, name, sort) VALUES ' . implode(',', $sub_values)) or sqlerr(__FILE__, __LINE__);
	}
}

function kz_groups_type_name($id)
{
	$items = kz_groups_types();
	return $items[(int)$id] ?? 'Клуб';
}

function kz_groups_category_name($id)
{
	$items = kz_groups_categories();
	return $items[(int)$id] ?? 'Не выбрана категория';
}

function kz_groups_subcategory_name($id)
{
	$items = kz_groups_subcategories();
	$id = (int)$id;
	return isset($items[$id]) ? $items[$id][1] : 'Не выбрана категория';
}

function kz_groups_subcategories_for($category_id)
{
	$category_id = (int)$category_id;
	$rows = array();
	foreach (kz_groups_subcategories() as $id => $row) {
		if ((int)$row[0] === $category_id) {
			$rows[$id] = $row[1];
		}
	}
	return $rows;
}

function kz_groups_selected($current, $value)
{
	return (string)$current === (string)$value ? ' selected' : '';
}

function kz_groups_options(array $items, $selected, $placeholder = '')
{
	$html = '';
	if ($placeholder !== '') {
		$html .= '<option value="0"' . kz_groups_selected($selected, 0) . '>' . kz_groups_h($placeholder) . '</option>';
	}
	foreach ($items as $id => $name) {
		$html .= '<option value="' . (int)$id . '"' . kz_groups_selected($selected, $id) . '>' . kz_groups_h($name) . '</option>';
	}
	return $html;
}

function kz_groups_cp1251_urlencode($value)
{
	$value = (string)$value;
	if (function_exists('iconv')) {
		$converted = @iconv('UTF-8', 'Windows-1251//TRANSLIT', $value);
		if ($converted !== false) {
			return str_replace('%20', '+', rawurlencode($converted));
		}
	}
	return str_replace('%20', '+', rawurlencode($value));
}

function kz_groups_request_text($value)
{
	$value = trim((string)$value);
	if ($value === '') {
		return '';
	}

	if (function_exists('mb_check_encoding') && function_exists('iconv') && !mb_check_encoding($value, 'UTF-8')) {
		$converted = @iconv('Windows-1251', 'UTF-8//IGNORE', $value);
		if ($converted !== false) {
			return trim($converted);
		}
	}

	return $value;
}

function kz_groups_hash()
{
	global $CURUSER;
	if (!$CURUSER || !is_array($CURUSER)) {
		return '';
	}
	return kz_groups_h($CURUSER['hash4u'] ?? ($CURUSER['logout_hash'] ?? ''));
}

function kz_groups_avatar(array $group)
{
	$avatar = trim((string)($group['avatar'] ?? ''));
	return $avatar !== '' ? kz_groups_h($avatar) : '/pic/default_avatar.gif';
}

function kz_groups_date($value, $with_time = true)
{
	$value = (string)$value;
	if ($value === '' || $value === '0000-00-00 00:00:00') {
		return '';
	}
	$ts = strtotime($value);
	if (!$ts) {
		return kz_groups_h($value);
	}
	$months = array(
		1 => 'января',
		2 => 'февраля',
		3 => 'марта',
		4 => 'апреля',
		5 => 'мая',
		6 => 'июня',
		7 => 'июля',
		8 => 'августа',
		9 => 'сентября',
		10 => 'октября',
		11 => 'ноября',
		12 => 'декабря',
	);
	$text = (int)date('j', $ts) . ' ' . $months[(int)date('n', $ts)] . ' ' . date('Y', $ts);
	if ($with_time) {
		$text .= ' в ' . date('H:i', $ts);
	}
	return $text;
}

function kz_groups_text($text)
{
	$text = trim((string)$text);
	if ($text === '') {
		return '';
	}
	if (function_exists('format_comment')) {
		return format_comment($text);
	}
	return nl2br(kz_groups_h($text));
}

function kz_groups_cut($text, $width = 40)
{
	$text = trim((string)$text);
	$width = max(1, (int)$width);
	if (function_exists('mb_strimwidth')) {
		return mb_strimwidth($text, 0, $width, '...', 'UTF-8');
	}
	if (strlen($text) <= $width) {
		return $text;
	}
	return substr($text, 0, max(0, $width - 3)) . '...';
}

function kz_groups_user_link($userid, $username, $class = 0, array $user = array())
{
	$userid = (int)$userid;
	$username = (string)$username;
	if ($userid <= 0 || $username === '') {
		return '<i>unknown</i>';
	}
	$icons = function_exists('get_user_icons') ? get_user_icons(array_merge($user, array('id' => $userid, 'class' => $class, 'username' => $username))) : '';
	return '<a href="/userdetails.php?id=' . $userid . '" class="u' . (int)$class . '">' . kz_groups_h($username) . '</a>' . $icons;
}

function kz_groups_fetch($group_id)
{
	kz_groups_ensure_schema();

	$group_id = (int)$group_id;
	if ($group_id <= 0) {
		return null;
	}

	$res = sql_query("
		SELECT g.*, u.username AS owner_username, u.class AS owner_class, u.donor, u.gender, u.birthday, u.warned, u.enabled, u.uploaded, u.downloaded
		FROM groupex_groups AS g
		LEFT JOIN users AS u ON u.id = g.owner_id
		WHERE g.id = $group_id
		  AND g.visible = 'yes'
		LIMIT 1
	") or sqlerr(__FILE__, __LINE__);

	$row = mysqli_fetch_assoc($res);
	return $row ?: null;
}

function kz_groups_member($group_id, $userid)
{
	kz_groups_ensure_schema();

	$group_id = (int)$group_id;
	$userid = (int)$userid;
	if ($group_id <= 0 || $userid <= 0) {
		return null;
	}

	$res = sql_query("SELECT * FROM groupex_members WHERE group_id = $group_id AND userid = $userid LIMIT 1") or sqlerr(__FILE__, __LINE__);
	$row = mysqli_fetch_assoc($res);
	return $row ?: null;
}

function kz_groups_is_member($group_id, $userid = 0)
{
	global $CURUSER;
	if ($userid <= 0 && $CURUSER) {
		$userid = (int)$CURUSER['id'];
	}
	$member = kz_groups_member($group_id, $userid);
	return $member && $member['status'] === 'member';
}

function kz_groups_can_manage(array $group, $userid = 0)
{
	global $CURUSER;
	if (!$CURUSER) {
		return false;
	}
	if ($userid <= 0) {
		$userid = (int)$CURUSER['id'];
	}
	if (function_exists('get_user_class') && get_user_class() >= UC_MODERATOR) {
		return true;
	}
	if ((int)$group['owner_id'] === $userid) {
		return true;
	}
	$member = kz_groups_member((int)$group['id'], $userid);
	return $member && $member['status'] === 'member' && in_array($member['role'], array('owner', 'moderator'), true);
}

function kz_groups_log($group_id, $userid, $action, $text)
{
	$group_id = (int)$group_id;
	$userid = (int)$userid;
	$action = substr((string)$action, 0, 40);
	$text = substr((string)$text, 0, 255);
	if ($group_id <= 0) {
		return;
	}
	sql_query("
		INSERT INTO groupex_log (group_id, userid, action, text, added_at)
		VALUES ($group_id, $userid, " . sqlesc($action) . ', ' . sqlesc($text) . ", NOW())
	") or sqlerr(__FILE__, __LINE__);
}

function kz_groups_refresh_counts($group_id)
{
	$group_id = (int)$group_id;
	if ($group_id <= 0) {
		return;
	}
	sql_query("
		UPDATE groupex_groups
		SET
			members_count = (SELECT COUNT(*) FROM groupex_members WHERE group_id = $group_id AND status = 'member'),
			torrents_count = (SELECT COUNT(*) FROM groupex_torrents WHERE group_id = $group_id),
			zabor_count = (SELECT COUNT(*) FROM groupex_zabor WHERE group_id = $group_id)
		WHERE id = $group_id
	") or sqlerr(__FILE__, __LINE__);
}

function kz_groups_add_bookmark($group_id, $userid)
{
	kz_groups_ensure_schema();

	$group_id = (int)$group_id;
	$userid = (int)$userid;
	if ($group_id <= 0 || $userid <= 0) {
		return false;
	}

	sql_query("
		INSERT IGNORE INTO groupex_bookmarks (userid, group_id, added_at)
		VALUES ($userid, $group_id, NOW())
	") or sqlerr(__FILE__, __LINE__);
	return true;
}

function kz_groups_remove_bookmark($group_id, $userid)
{
	kz_groups_ensure_schema();

	$group_id = (int)$group_id;
	$userid = (int)$userid;
	if ($group_id <= 0 || $userid <= 0) {
		return false;
	}

	sql_query("DELETE FROM groupex_bookmarks WHERE userid = $userid AND group_id = $group_id") or sqlerr(__FILE__, __LINE__);
	return true;
}

function kz_groups_is_bookmarked($group_id, $userid)
{
	kz_groups_ensure_schema();

	$group_id = (int)$group_id;
	$userid = (int)$userid;
	if ($group_id <= 0 || $userid <= 0) {
		return false;
	}

	$res = sql_query("SELECT id FROM groupex_bookmarks WHERE userid = $userid AND group_id = $group_id LIMIT 1") or sqlerr(__FILE__, __LINE__);
	return (bool)mysqli_fetch_assoc($res);
}

function kz_groups_search_href($field, $value)
{
	return '/groupexlist.php?action=search&amp;' . rawurlencode((string)$field) . '=' . rawurlencode((string)$value);
}

function kz_groups_group_card(array $group, $mode = 'list')
{
	global $CURUSER;

	$id = (int)$group['id'];
	$hash = kz_groups_hash();
	$members = (int)($group['members_count'] ?? 0);
	$torrents = (int)($group['torrents_count'] ?? 0);
	$zabor = (int)($group['zabor_count'] ?? 0);
	$avatar = kz_groups_avatar($group);

	echo '<div class="bx5x5">';
	echo '<img class="imgg" src="' . $avatar . '" alt="">';
	echo '<div class="ptable_r">';
	if ($mode === 'mine' && !empty($group['member_status']) && $group['member_status'] === 'pending') {
		echo '<span class="small">Заявка ожидает решения</span><br>';
	}
	if ($mode === 'mine' && !empty($group['member_status']) && $group['member_status'] === 'invited') {
		echo '<span class="small">Приглашение в группу</span><br>';
	}
	echo '<a href="/groupex.php?id=' . $id . '" class="sba">Просмотреть группу</a><br>';
	if ($CURUSER) {
		if (kz_groups_is_bookmarked($id, (int)$CURUSER['id'])) {
			echo '<a href="/bookmarks.php?type=2&amp;delete=' . $id . ($hash !== '' ? '&amp;hash4u=' . $hash : '') . '" class="sba">Убрать из закладок</a>';
		} else {
			echo '<a href="/bookmarks.php?type=2&amp;add=' . $id . ($hash !== '' ? '&amp;hash4u=' . $hash : '') . '" class="sba">Добавить в закладки</a>';
		}
	}
	echo '</div>';
	echo '<div class="ptable"><ul>';
	echo '<li>';
	if (!empty($group['private']) && $group['private'] === 'yes') {
		echo '<span class="s_park i1" title="Закрытая группа"></span> ';
	}
	echo '<a href="/groupex.php?id=' . $id . '">' . kz_groups_h($group['name']) . '</a></li>';
	echo '<li>';
	echo '<a href="' . kz_groups_search_href('type', (int)$group['type']) . '" class="sba">' . kz_groups_h(kz_groups_type_name((int)$group['type'])) . '</a>, ';
	echo '<a href="' . kz_groups_search_href('cat', (int)$group['cat']) . '" class="sba">' . kz_groups_h(kz_groups_category_name((int)$group['cat'])) . '</a>, ';
	echo '<a href="' . kz_groups_search_href('subcatsel', (int)$group['subcat']) . '" class="sba">' . kz_groups_h(kz_groups_subcategory_name((int)$group['subcat'])) . '</a>';
	echo '</li>';
	echo '<li>Участников ' . $members . ', раздач ' . $torrents . ', обсуждений ' . $zabor . '</li>';
	echo '</ul></div>';
	echo '</div>';
}

function kz_groups_subcat_script($selected_map = array())
{
	$selected_json = json_encode($selected_map, JSON_UNESCAPED_UNICODE);
	if ($selected_json === false) {
		$selected_json = '{}';
	}
	echo '<script type="text/javascript">';
	echo 'var kzGroupSelectedSubcats = ' . $selected_json . ';';
	echo 'function kzGroupsSubcatFor(catId, selectId){';
	echo 'var id = $("#" + catId).val();';
	echo 'var selected = kzGroupSelectedSubcats[selectId] || 0;';
	echo 'var target = $("#" + selectId);';
	echo 'target.children().remove();';
	echo 'if (id == 0) { target.append("<option value=\"0\">Не выбрана категория</option>"); return; }';
	echo '$.post("/ajax_groups.php?sid=" + new Date().getTime(), {q:"subcat", index:id}, function(res){';
	echo 'target.children().remove();';
	echo 'target.append("<option value=\"0\">Выберите подкатегорию</option>");';
	echo 'for (var i = 0; i < res.length; i++) {';
	echo 'var sel = (parseInt(res[i].id, 10) === parseInt(selected, 10)) ? " selected" : "";';
	echo 'target.append("<option value=\"" + res[i].id + "\"" + sel + ">" + res[i].name + "</option>");';
	echo '}';
	echo 'kzGroupSelectedSubcats[selectId] = 0;';
	echo '}, "json");';
	echo '}';
	echo 'function kzGroupsInsertCode(id, tag){';
	echo 'var el = document.getElementById(id); if (!el) { return false; }';
	echo 'var start = el.selectionStart || 0, end = el.selectionEnd || 0;';
	echo 'var value = el.value, selected = value.substring(start, end);';
	echo 'el.value = value.substring(0, start) + "[" + tag + "]" + selected + "[/" + tag + "]" + value.substring(end);';
	echo 'el.focus(); return false;';
	echo '}';
	echo '</script>';
}

function kz_groups_search_sidebar($info_text = 'Здесь отображены все группы, которые существуют. Для поиска определенных групп можете воспользоваться формой поиска, размещенной выше.', $show_banner = true)
{
	$name = kz_groups_request_text($_GET['name'] ?? '');
	$userid = (int)($_GET['userid'] ?? 0);
	$type = (int)($_GET['type'] ?? 0);
	$cat = (int)($_GET['cat'] ?? 0);
	$subcat = (int)($_GET['subcatsel'] ?? 0);
	$sort = (int)($_GET['sort'] ?? 0);
	$subcats = $cat > 0 ? kz_groups_subcategories_for($cat) : array();

	echo '<div class="mn3_menu">';
	echo '<form method="get" action="/groupexlist.php"><ul class="men">';
	if ($show_banner) {
		echo '<li class="img"><a href="/groupexlist.php"><img src="/pic/bn/p_groupexlist.jpg" height="75" class="block w200" alt=""></a></li>';
	}
	echo '<li class="tp">Поиск группы<input type="hidden" name="action" value="search"></li>';
	echo '<li class="img"><dl><dt>Название</dt><dd><input type="text" name="name" value="' . kz_groups_h($name) . '" class="w100"></dd></dl></li>';
	echo '<li class="img"><dl><dt>Ид создателя</dt><dd><input type="text" name="userid" value="' . ($userid > 0 ? $userid : '') . '" class="w100"></dd></dl></li>';
	echo '<li class="img"><span class="sw100p"><select class="w100p styled" name="type">' . kz_groups_options(kz_groups_types(), $type, 'Выберите тип группы') . '</select></span></li>';
	echo '<li class="img"><span class="sw100p"><select class="w100p styled" name="cat" id="gsearch_cat" onchange="kzGroupsSubcatFor(\'gsearch_cat\', \'gsearch_subcatsel\');">' . kz_groups_options(kz_groups_categories(), $cat, 'Выберите из списка категорию') . '</select></span></li>';
	echo '<li class="img"><span class="sw100p"><select class="w100p styled" name="subcatsel" id="gsearch_subcatsel">';
	if ($subcats) {
		echo kz_groups_options($subcats, $subcat, 'Выберите подкатегорию');
	} else {
		echo '<option value="0">Не выбрана категория</option>';
	}
	echo '</select></span></li>';
	echo '<li class="img"><span class="sw100p"><select class="w100p styled" name="sort" id="sort">';
	echo '<option value="0"' . kz_groups_selected($sort, 0) . '>Сортировать по добавлению</option>';
	echo '<option value="1"' . kz_groups_selected($sort, 1) . '>Сортировать по участникам</option>';
	echo '<option value="2"' . kz_groups_selected($sort, 2) . '>Сортировать по раздачам</option>';
	echo '<option value="3"' . kz_groups_selected($sort, 3) . '>Сортировать по обсуждениям</option>';
	echo '</select></span></li>';
	echo '<li class="img"><input type="submit" value="Искать" class="w200 buttonS"></li>';
	echo '<li class="tp">Меню</li>';
	echo '<li><span class="bulet"></span><a href="/groupexcreate.php">Создание новой группы</a></li>';
	echo '<li><span class="bulet"></span><a href="/groupexlist.php">Список групп</a></li>';
	echo '<li><span class="bulet"></span><a href="/mygroups.php">Мои группы</a></li>';
	echo '<li><span class="bulet"></span><a href="/bookmarks.php?type=2">Закладки</a></li>';
	echo '<li class="tp">Информация</li>';
	echo '<li class="justify">' . kz_groups_h($info_text) . '</li>';
	echo '</ul></form></div>';
}

function kz_groups_status_text(array $group, $member)
{
	if (!$member) {
		return 'Вы не состоите в этой группе';
	}
	if ($member['status'] === 'pending') {
		return 'Ваша заявка на вступление ожидает решения руководства';
	}
	if ($member['status'] === 'invited') {
		return 'Вас пригласили в эту группу';
	}
	if ($member['role'] === 'owner') {
		return 'Вы руководитель этой группы';
	}
	if ($member['role'] === 'moderator') {
		return 'Вы модератор этой группы';
	}
	return 'Вы участник этой группы';
}

function kz_groups_group_sidebar(array $group, $member = null)
{
	global $CURUSER;

	$id = (int)$group['id'];
	$hash = kz_groups_hash();
	$can_manage = kz_groups_can_manage($group);
	$is_member = $member && $member['status'] === 'member';

	echo '<div class="mn3_menu"><ul class="men w200">';
	echo '<li class="img"><a href="/groupex.php?id=' . $id . '"><img src="' . kz_groups_avatar($group) . '" class="p200" alt=""></a></li>';
	echo '<li class="tp">Меню</li>';
	echo '<li><span class="bulet"></span><a href="/groupextorrents.php?id=' . $id . '">Галерея раздач</a></li>';
	echo '<li><span class="bulet"></span><a href="/groupextorrentlist.php?id=' . $id . '">Список раздач</a></li>';
	echo '<li><span class="bulet"></span><a href="/groupexmembers.php?id=' . $id . '">Участники</a></li>';
	echo '<li><span class="bulet"></span><a href="/groupexlog.php?id=' . $id . '">Журнал группы</a></li>';
	if ($CURUSER) {
		if ($is_member) {
			echo '<li><span class="bulet"></span><a href="/groupexinvite.php?id=' . $id . '&amp;action=leavegroup' . ($hash !== '' ? '&amp;hash4u=' . $hash : '') . '">Покинуть группу</a></li>';
		} elseif ($member && $member['status'] === 'pending') {
			echo '<li><span class="bulet"></span><a href="/groupexinvite.php?id=' . $id . '&amp;action=leavegroup' . ($hash !== '' ? '&amp;hash4u=' . $hash : '') . '">Отменить заявку</a></li>';
		} else {
			echo '<li><span class="bulet"></span><a href="/groupexinvite.php?id=' . $id . '&amp;action=join' . ($hash !== '' ? '&amp;hash4u=' . $hash : '') . '">Вступить в группу</a></li>';
		}
		if (kz_groups_is_bookmarked($id, (int)$CURUSER['id'])) {
			echo '<li><span class="bulet"></span><a href="/bookmarks.php?type=2&amp;delete=' . $id . ($hash !== '' ? '&amp;hash4u=' . $hash : '') . '">Убрать из закладок</a></li>';
		} else {
			echo '<li><span class="bulet"></span><a href="/bookmarks.php?type=2&amp;add=' . $id . ($hash !== '' ? '&amp;hash4u=' . $hash : '') . '">Добавить в закладки</a></li>';
		}
	}
	if ($can_manage) {
		echo '<li><span class="bulet"></span><a href="/groupextorrents.php?id=' . $id . '#addtorrent">Добавить раздачу</a></li>';
	}
	echo '<li class="tp">Доступ к группе</li>';
	echo '<li class="justify">' . ($group['private'] === 'yes' ? 'Это закрытая группа. Вступление происходит по разрешению руководства.' : 'Это открытая группа для всех желающих.') . '</li>';
	echo '<li class="tp">Ваш статус в группе</li>';
	echo '<li class="justify">' . kz_groups_h(kz_groups_status_text($group, $member)) . '</li>';
	echo '<li class="tp">Руководство</li>';

	$leaders = kz_groups_leaders($id);
	if ($leaders) {
		foreach ($leaders as $leader) {
			echo '<li class="justify">' . kz_groups_user_link((int)$leader['id'], $leader['username'], (int)$leader['class'], $leader) . '</li>';
		}
	} elseif (!empty($group['owner_username'])) {
		echo '<li class="justify">' . kz_groups_user_link((int)$group['owner_id'], $group['owner_username'], (int)$group['owner_class'], $group) . '</li>';
	}
	echo '</ul></div>';
}

function kz_groups_leaders($group_id)
{
	$group_id = (int)$group_id;
	$res = sql_query("
		SELECT u.id, u.username, u.class, u.donor, u.gender, u.birthday, u.warned, u.enabled, u.uploaded, u.downloaded, gm.role
		FROM groupex_members AS gm
		INNER JOIN users AS u ON u.id = gm.userid
		WHERE gm.group_id = $group_id
		  AND gm.status = 'member'
		  AND gm.role IN ('owner', 'moderator')
		ORDER BY FIELD(gm.role, 'owner', 'moderator'), u.username
		LIMIT 20
	") or sqlerr(__FILE__, __LINE__);

	$rows = array();
	while ($row = mysqli_fetch_assoc($res)) {
		$rows[] = $row;
	}
	return $rows;
}

function kz_groups_torrent_rows($group_id, $offset = 0, $limit = 0, $sort = 'date')
{
	$group_id = (int)$group_id;
	$offset = max(0, (int)$offset);
	$limit = (int)$limit;
	$order = $sort === 'top' ? 't.times_completed DESC, t.seeders DESC, gt.added_at DESC' : 'gt.added_at DESC, t.added DESC';
	$sql_limit = $limit > 0 ? ' LIMIT ' . $offset . ', ' . $limit : '';

	$res = sql_query("
		SELECT
			t.id, t.name, t.comments, t.size, t.times_completed, t.added, t.seeders, t.remote_seeders, t.leechers, t.remote_leechers,
			t.category, t.free, t.image1, c.name AS cat_name, c.image AS cat_pic, gt.added_at AS group_added
		FROM groupex_torrents AS gt
		INNER JOIN torrents AS t ON t.id = gt.torrent_id
		LEFT JOIN categories AS c ON c.id = t.category
		WHERE gt.group_id = $group_id
		  AND t.visible = 'yes'
		  AND t.banned != 'yes'
		ORDER BY $order
		$sql_limit
	") or sqlerr(__FILE__, __LINE__);

	$rows = array();
	while ($row = mysqli_fetch_assoc($res)) {
		$rows[] = $row;
	}
	return $rows;
}

function kz_groups_torrent_count($group_id)
{
	$group_id = (int)$group_id;
	$res = sql_query("
		SELECT COUNT(*)
		FROM groupex_torrents AS gt
		INNER JOIN torrents AS t ON t.id = gt.torrent_id
		WHERE gt.group_id = $group_id
		  AND t.visible = 'yes'
		  AND t.banned != 'yes'
	") or sqlerr(__FILE__, __LINE__);
	$row = mysqli_fetch_row($res);
	return (int)($row[0] ?? 0);
}

function kz_groups_torrent_table(array $rows, $empty_text = 'Раздачи в группе пока не добавлены.')
{
	echo '<div class="bx2_0"><table class="t_peer w100p">';
	echo '<tr class="mn"><td class="z w90"></td><td></td><td class="z">Комм.</td><td class="z">Размер</td><td class="z">Скач.</td><td class="z">Сидов</td><td class="z">Пиров</td><td class="z">Залит</td></tr>';
	if (!$rows) {
		echo '<tr class="first bg"><td colspan="8" class="center" style="padding:12px 5px;">' . kz_groups_h($empty_text) . '</td></tr>';
	}
	foreach ($rows as $i => $row) {
		$cat = !empty($row['cat_pic']) ? '<img src="/pic/cat/' . kz_groups_h($row['cat_pic']) . '" class="p90x32" alt="' . kz_groups_h($row['cat_name'] ?? '') . '">' : '';
		$link_class = 'r0';
		if (($row['free'] ?? '') === 'yes') {
			$link_class = 'r1';
		} elseif (($row['free'] ?? '') === 'silver') {
			$link_class = 'r2';
		}
		echo '<tr class="' . ($i === 0 ? 'first bg' : 'bg') . '">';
		echo '<td class="bt">' . $cat . '</td>';
		echo '<td class="nam"><a href="/details.php?id=' . (int)$row['id'] . '" class="' . $link_class . '">' . kz_groups_h($row['name']) . '</a></td>';
		echo '<td class="s">' . (int)$row['comments'] . '</td>';
		echo '<td class="s">' . kz_groups_h(mksize((int)$row['size'])) . '</td>';
		echo '<td class="s">' . (int)$row['times_completed'] . '</td>';
		echo '<td class="sl_s">' . ((int)$row['seeders'] + (int)($row['remote_seeders'] ?? 0)) . '</td>';
		echo '<td class="sl_p">' . ((int)$row['leechers'] + (int)($row['remote_leechers'] ?? 0)) . '</td>';
		echo '<td class="s">' . kz_groups_h(date('d.m.Y в H:i', strtotime($row['added']))) . '</td>';
		echo '</tr>';
	}
	echo '</table></div>';
}

function kz_groups_torrent_poster(array $torrent)
{
	$image = trim((string)($torrent['image1'] ?? ''));
	if ($image !== '') {
		return '/thumbnail.php?' . kz_groups_h($image);
	}
	if (!empty($torrent['cat_pic'])) {
		return '/pic/cat/' . kz_groups_h($torrent['cat_pic']);
	}
	return '/pic/default_avatar.gif';
}

function kz_groups_profile_menu_html()
{
	global $CURUSER;

	$user_id = (int)$CURUSER['id'];
	$username = kz_groups_h($CURUSER['username'] ?? '');
	$class = (int)($CURUSER['class'] ?? 0);
	$avatar = trim((string)($CURUSER['avatar'] ?? ''));
	if ($avatar === '') {
		$avatar = '/pic/default_avatar.gif';
	}

	echo '<div class="mn1_menu"><ul class="men u2 w200">';
	echo '<li class="img"><a href="/userdetails.php?id=' . $user_id . '"><img src="' . kz_groups_h($avatar) . '" class="p200" alt=""></a></li>';
	echo '<li class="tp">Меню пользователя</li>';
	echo '<li><span class="bulet"></span><a href="/message.php">Личные сообщения</a></li>';
	echo '<li><span class="bulet"></span><a href="/userdetails.php?id=' . $user_id . '">Мой профиль</a></li>';
	echo '<li><span class="bulet"></span><a href="/my.php">Редактировать профиль</a></li>';
	echo '<li><span class="bulet"></span><a href="/mygroups.php">Мои группы</a></li>';
	echo '<li><span class="bulet"></span><a href="/friends.php?id=' . $user_id . '">Мой список друзей</a></li>';
	echo '<li class="sf"><span class="bulet"></span><a href="/mytorrents.php?id=' . $user_id . '">Мои раздачи</a></li>';
	echo '<li class="tp">Закладки</li>';
	echo '<li><span class="bulet"></span><a href="/bookmarks.php?type=1">Раздачи</a></li>';
	echo '<li><span class="bulet"></span><a href="/bookmarks.php?type=2">Группы</a></li>';
	echo '<li><span class="bulet"></span><a href="/bookmarks.php?type=3">Пользователи</a></li>';
	echo '<li class="sf"><span class="bulet"></span><a href="/bookmarks.php?type=4">Персоны</a></li>';
	echo '<li class="tp">Группы</li>';
	echo '<li><span class="bulet"></span><a href="/groupexlist.php">Список групп</a></li>';
	echo '<li><span class="bulet"></span><a href="/groupexcreate.php">Создать группу</a></li>';
	echo '<li class="justify">Вы вошли как <a href="/userdetails.php?id=' . $user_id . '" class="u' . $class . '">' . $username . '</a>.</li>';
	echo '</ul></div>';
}

?>
