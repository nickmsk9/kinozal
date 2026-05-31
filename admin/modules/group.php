<?php

if (!defined('ADMIN_FILE')) {
	die('Illegal File Access');
}

require_once 'include/kz_group_page.php';

function GroupPageAdmin()
{
	global $admin_file;

	kz_group_page_ensure_schema();
	$message = '';

	if ($_SERVER['REQUEST_METHOD'] === 'POST') {
		if (isset($_POST['add_group_id'])) {
			$group_id = (int)$_POST['add_group_id'];
			$sort = max(0, (int)($_POST['sort'] ?? 0));
			$exists = sql_query("SELECT id FROM groupex_groups WHERE id = $group_id LIMIT 1") or sqlerr(__FILE__, __LINE__);
			if ($group_id > 0 && mysqli_fetch_assoc($exists)) {
				sql_query("
					INSERT INTO group_page_items (group_id, sort, active, added_at)
					VALUES ($group_id, $sort, 'yes', NOW())
					ON DUPLICATE KEY UPDATE sort = VALUES(sort), active = 'yes'
				") or sqlerr(__FILE__, __LINE__);
				$message = 'Группа добавлена в витрину.';
			} else {
				$message = 'Группа с таким ID не найдена.';
			}
		} elseif (isset($_POST['delete_item_id'])) {
			$item_id = (int)$_POST['delete_item_id'];
			sql_query("DELETE FROM group_page_items WHERE id = $item_id") or sqlerr(__FILE__, __LINE__);
			$message = 'Группа убрана из витрины.';
		} elseif (isset($_POST['save_items']) && !empty($_POST['items']) && is_array($_POST['items'])) {
			foreach ($_POST['items'] as $item_id => $item) {
				$item_id = (int)$item_id;
				$sort = max(0, (int)($item['sort'] ?? 0));
				$active = !empty($item['active']) ? 'yes' : 'no';
				sql_query("UPDATE group_page_items SET sort = $sort, active = '$active' WHERE id = $item_id") or sqlerr(__FILE__, __LINE__);
			}
			$message = 'Порядок и видимость сохранены.';
		}
	}

	if ($message !== '') {
		stdmsg('Группы', kz_group_page_h($message));
	}

	$items = kz_group_page_rows(false);
	$groups_res = sql_query("
		SELECT g.id, g.name, g.type, g.members_count, g.torrents_count, g.visible
		FROM groupex_groups AS g
		ORDER BY g.name ASC
		LIMIT 500
	") or sqlerr(__FILE__, __LINE__);

	echo '<div class="mn_wrap">';
	echo '<div class="tp1_title"><b>Витрина групп</b></div>';
	echo '<div class="tp1_body">';
	echo '<form method="post" action="' . kz_group_page_h($admin_file) . '.php?op=GroupPageAdmin">';
	echo '<table class="tables2 w100p">';
	echo '<tr><td class="colhead center">Порядок</td><td class="colhead">Группа</td><td class="colhead center">Тип</td><td class="colhead center">Участников</td><td class="colhead center">Раздач</td><td class="colhead center">Показывать</td><td class="colhead center">Действие</td></tr>';

	if (!$items) {
		echo '<tr><td colspan="7" class="center">Витрина еще не настроена. На странице group.php пока показываются все видимые группы.</td></tr>';
	}

	foreach ($items as $row) {
		$item_id = (int)$row['item_id'];
		$group_id = (int)$row['id'];
		echo '<tr>';
		echo '<td class="center"><input type="text" name="items[' . $item_id . '][sort]" value="' . (int)$row['page_sort'] . '" size="5"></td>';
		echo '<td><a href="/groupex.php?id=' . $group_id . '">' . kz_group_page_h($row['name']) . '</a> <span class="small">ID ' . $group_id . '</span></td>';
		echo '<td class="center">' . kz_group_page_h(kz_groups_type_name((int)$row['type'])) . '</td>';
		echo '<td class="center">' . (int)$row['members_count'] . '</td>';
		echo '<td class="center">' . (int)$row['torrents_count'] . '</td>';
		echo '<td class="center"><input type="checkbox" name="items[' . $item_id . '][active]" value="1"' . ($row['page_active'] === 'yes' ? ' checked' : '') . '></td>';
		echo '<td class="center">';
		echo '<button type="submit" class="btn" name="delete_item_id" value="' . $item_id . '" onclick="return confirm(\'Убрать группу из витрины?\');">Убрать</button>';
		echo '</td>';
		echo '</tr>';
	}

	echo '<tr><td colspan="7" class="center"><input type="hidden" name="save_items" value="1"><input type="submit" class="btn" value="Сохранить витрину"></td></tr>';
	echo '</table>';
	echo '</form>';
	echo '</div>';

	echo '<div class="tp1_title"><b>Добавить группу в витрину</b></div>';
	echo '<div class="tp1_body">';
	echo '<form method="post" action="' . kz_group_page_h($admin_file) . '.php?op=GroupPageAdmin">';
	echo '<table class="tables2 w100p">';
	echo '<tr><td class="rowhead w150">Группа</td><td><select name="add_group_id">';
	while ($group = mysqli_fetch_assoc($groups_res)) {
		echo '<option value="' . (int)$group['id'] . '">' . kz_group_page_h($group['name']) . ' / ID ' . (int)$group['id'] . ' / ' . kz_group_page_h(kz_groups_type_name((int)$group['type'])) . '</option>';
	}
	echo '</select></td></tr>';
	echo '<tr><td class="rowhead">Порядок</td><td><input type="text" name="sort" value="100" size="8"></td></tr>';
	echo '<tr><td colspan="2" class="center"><input type="submit" class="btn" value="Добавить"></td></tr>';
	echo '</table>';
	echo '</form>';
	echo '<p class="small">Сами группы создаются в пользовательском модуле <a href="/groupexcreate.php">groupexcreate.php</a>; здесь управляется только витрина <a href="/group.php">group.php</a>.</p>';
	echo '</div>';
	echo '</div>';
}

switch ($op) {
	case 'GroupPageAdmin':
		GroupPageAdmin();
		break;
}

?>
