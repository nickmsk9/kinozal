<?php

if (!defined('IN_TRACKER') && !defined('ADMIN_FILE')) {
	die('Direct access denied.');
}

require_once __DIR__ . '/groupex.php';

function kz_group_page_ensure_schema()
{
	kz_groups_ensure_schema();

	sql_query("
		CREATE TABLE IF NOT EXISTS group_page_items (
			id int(10) unsigned NOT NULL auto_increment,
			group_id int(10) unsigned NOT NULL default '0',
			sort int(10) unsigned NOT NULL default '0',
			active enum('yes','no') NOT NULL default 'yes',
			added_at datetime NULL DEFAULT NULL,
			PRIMARY KEY (id),
			UNIQUE KEY group_id (group_id),
			KEY active_sort (active, sort)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
	") or sqlerr(__FILE__, __LINE__);
}

function kz_group_page_h($value)
{
	return htmlspecialchars_uni((string)$value);
}

function kz_group_page_rows($active_only = true)
{
	kz_group_page_ensure_schema();

	$where = $active_only ? "WHERE i.active = 'yes' AND g.visible = 'yes'" : '';
	$res = sql_query("
		SELECT i.id AS item_id, i.sort AS page_sort, i.active AS page_active, g.*
		FROM group_page_items AS i
		INNER JOIN groupex_groups AS g ON g.id = i.group_id
		$where
		ORDER BY i.sort ASC, g.name ASC
	") or sqlerr(__FILE__, __LINE__);

	$rows = array();
	while ($row = mysqli_fetch_assoc($res)) {
		$rows[] = $row;
	}

	return $rows;
}

function kz_group_page_public_rows($limit_sql = '')
{
	$rows = kz_group_page_rows(true);
	if ($rows) {
		return $rows;
	}

	$res = sql_query("
		SELECT g.*, 0 AS item_id, 0 AS page_sort, 'yes' AS page_active
		FROM groupex_groups AS g
		WHERE g.visible = 'yes'
		ORDER BY g.torrents_count DESC, g.members_count DESC, g.name ASC
		$limit_sql
	") or sqlerr(__FILE__, __LINE__);

	$rows = array();
	while ($row = mysqli_fetch_assoc($res)) {
		$rows[] = $row;
	}

	return $rows;
}

function kz_group_page_avatar(array $group)
{
	$id = (int)($group['id'] ?? 0);
	$avatar = trim((string)($group['avatar'] ?? ''));
	if ($avatar !== '') {
		return kz_group_page_h($avatar);
	}
	if ($id > 0 && file_exists(ROOT_PATH . 'pic/groupex/' . $id . '.gif')) {
		return '/pic/groupex/' . $id . '.gif';
	}
	return '/pic/default_avatar.gif';
}

function kz_group_page_card(array $group)
{
	global $CURUSER;

	$id = (int)$group['id'];
	$type = (int)($group['type'] ?? 1);
	$members = (int)($group['members_count'] ?? 0);
	$torrents = (int)($group['torrents_count'] ?? 0);
	$hash = function_exists('kz_groups_hash') ? kz_groups_hash() : '';
	$bookmarked = $CURUSER ? kz_groups_is_bookmarked($id, (int)$CURUSER['id']) : false;
	$bookmark_action = $bookmarked ? 'delete' : 'add';
	$bookmark_text = $bookmarked ? 'Убрать из закладок' : 'Добавить в закладки';
	$park = !empty($group['private']) && $group['private'] === 'yes' ? '<img src="/pic/parked.gif" alt=""> ' : '';

	echo '<div class="bx2_0">';
	echo '<table class="tables2 w100p">';
	echo '<tr>';
	echo '<td width="90" valign="top"><a href="/groupex.php?id=' . $id . '"><img src="' . kz_group_page_avatar($group) . '" class="p88x31" alt=""></a></td>';
	echo '<td valign="top">';
	echo $park . '<a href="/groupex.php?id=' . $id . '">' . kz_group_page_h($group['name']) . '</a><br>';
	echo 'Тип группы: <a class="sba" href="/groupexlist.php?action=search&amp;type=' . $type . '">' . kz_group_page_h(kz_groups_type_name($type)) . '</a>';
	echo ', участников: ' . $members;
	if ($torrents > 0) {
		echo ', раздач: ' . $torrents;
	}
	echo '</td>';
	echo '<td valign="top" width="150">';
	echo '<a class="sba" href="/groupexmembers.php?id=' . $id . '">Участники</a>';
	if ($torrents > 0) {
		echo ' | <a class="sba" href="/groupextorrentlist.php?id=' . $id . '">Раздачи</a>';
	}
	if ($CURUSER) {
		echo '<br><a class="sba" href="/bookmarks.php?type=2&amp;' . $bookmark_action . '=' . $id . ($hash !== '' ? '&amp;hash4u=' . $hash : '') . '">' . $bookmark_text . '</a>';
	}
	echo '</td>';
	echo '</tr>';
	echo '</table>';
	echo '</div>';
}

?>
