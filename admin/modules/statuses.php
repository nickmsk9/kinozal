<?php

if (!defined('ADMIN_FILE')) {
	die('Illegal File Access');
}

if (!function_exists('UserStatusesAdmin')) {
	function UserStatusesAdmin()
	{
		global $admin_file, $CURUSER;

		kz_statuses_ensure_schema();
		$messages = array();
		$selected_user = null;
		$lookup_name = trim((string)($_POST['username'] ?? $_GET['username'] ?? ''));

		if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_status_settings'])) {
			$active = isset($_POST['active']) && is_array($_POST['active']) ? $_POST['active'] : array();
			foreach (kz_statuses_catalog() as $key => $status) {
				$is_active = isset($active[$key]) ? 1 : 0;
				sql_query("UPDATE user_statuses SET active = $is_active WHERE status_key = " . sqlesc($key, true)) or sqlerr(__FILE__, __LINE__);
			}
			$messages[] = 'Настройки статусов сохранены.';
		}

		if ($lookup_name !== '') {
			$selected_user = kz_statuses_find_user_by_username($lookup_name);
			if (!$selected_user) {
				$messages[] = 'Пользователь "' . kz_statuses_h($lookup_name) . '" не найден.';
			}
		}

		if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_user_statuses']) && $selected_user) {
			$selected = isset($_POST['manual_statuses']) && is_array($_POST['manual_statuses']) ? $_POST['manual_statuses'] : array();
			kz_statuses_save_manual((int)$selected_user['id'], $selected, (int)$CURUSER['id']);
			$messages[] = 'Статусы пользователя ' . kz_statuses_h($selected_user['username']) . ' сохранены.';
		}

		if (!empty($messages)) {
			stdmsg('Пользовательские статусы', implode('<br />', $messages));
		}

		$statuses = kz_statuses_all();

		echo '<div class="mn_wrap">';
		echo '<div class="tp1_title"><b>Пользовательские статусы</b></div>';
		echo '<div class="tp1_body">';
		echo '<form method="post" action="' . kz_statuses_h($admin_file) . '.php?op=UserStatusesAdmin">';
		echo '<input type="hidden" name="save_status_settings" value="1">';
		echo '<table class="tables2 w100p">';
		echo '<tr><td class="colhead center">Вкл.</td><td class="colhead">Статус</td><td class="colhead center">Тип</td></tr>';

		foreach ($statuses as $key => $status) {
			$checked = (int)$status['active'] === 1 ? ' checked' : '';
			$type = (int)$status['auto'] === 1 ? 'Авто' : 'Ручной';
			echo '<tr>';
			echo '<td class="center"><input type="checkbox" name="active[' . kz_statuses_h($key) . ']" value="1"' . $checked . '></td>';
			echo '<td><i class="i1 ' . kz_statuses_h($status['icon_class']) . '"></i> <b>' . kz_statuses_h($status['title']) . '</b></td>';
			echo '<td class="center">' . $type . '</td>';
			echo '</tr>';
		}

		echo '<tr><td colspan="3" class="center"><input type="submit" class="btn" value="Сохранить настройки"></td></tr>';
		echo '</table>';
		echo '</form>';
		echo '</div>';

		echo '<div class="tp1_title"><b>Назначение ручных статусов</b></div>';
		echo '<div class="tp1_body">';
		echo '<form method="get" action="' . kz_statuses_h($admin_file) . '.php">';
		echo '<input type="hidden" name="op" value="UserStatusesAdmin">';
		echo '<table class="tables2 w100p"><tr><td class="w150">Пользователь</td><td><input type="text" name="username" size="35" value="' . kz_statuses_h($lookup_name) . '"> <input type="submit" class="btn" value="Найти"></td></tr></table>';
		echo '</form>';

		if ($selected_user) {
			$manual = kz_statuses_manual_keys((int)$selected_user['id']);
			echo '<form method="post" action="' . kz_statuses_h($admin_file) . '.php?op=UserStatusesAdmin">';
			echo '<input type="hidden" name="save_user_statuses" value="1">';
			echo '<input type="hidden" name="username" value="' . kz_statuses_h($selected_user['username']) . '">';
			echo '<table class="tables2 w100p">';
			echo '<tr><td colspan="2">Пользователь: <a href="/userdetails.php?id=' . (int)$selected_user['id'] . '">' . get_user_class_color((int)$selected_user['class'], kz_statuses_h($selected_user['username'])) . '</a></td></tr>';

			foreach ($statuses as $key => $status) {
				if ((int)$status['auto'] === 1) {
					continue;
				}
				$checked = isset($manual[$key]) ? ' checked' : '';
				echo '<tr><td class="w20 center"><input type="checkbox" name="manual_statuses[]" value="' . kz_statuses_h($key) . '"' . $checked . '></td><td><i class="i1 ' . kz_statuses_h($status['icon_class']) . '"></i> ' . kz_statuses_h($status['title']) . '</td></tr>';
			}

			echo '<tr><td colspan="2" class="center"><input type="submit" class="btn" value="Сохранить статусы пользователя"></td></tr>';
			echo '</table>';
			echo '</form>';
		}

		echo '</div>';
		echo '</div>';
	}
}

switch ($op) {
	case 'UserStatusesAdmin':
		UserStatusesAdmin();
		break;
}

?>
