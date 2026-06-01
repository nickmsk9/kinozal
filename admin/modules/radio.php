<?php

if (!defined('ADMIN_FILE')) {
	die('Illegal File Access');
}

require_once 'include/radio.php';

if (!function_exists('RadioAdmin')) {
	function radio_admin_row($label, $name, $value, $size = 80)
	{
		echo '<tr>';
		echo '<td class="rowhead w150">' . radio_h($label) . '</td>';
		echo '<td><input type="text" name="' . radio_h($name) . '" size="' . (int)$size . '" value="' . radio_h($value) . '"></td>';
		echo '</tr>';
	}

	function RadioAdmin()
	{
		global $admin_file;

		radio_ensure_schema();
		$messages = array();

		if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_radio'])) {
			$fields = array(
				'current_song',
				'next_song',
				'listeners',
				'kbps',
				'dj_user_id',
				'dj_name',
				'public_url',
				'stream_url_128',
				'stream_url_320',
				'playlist_url_128',
				'playlist_url_320',
				'order_url',
				'order_image',
				'order_full_url',
				'group_title',
				'group_url',
				'announce_title',
				'announce_text',
				'recruit_contact',
				'rules_text',
			);

			$values = array();
			foreach ($fields as $field) {
				$values[$field] = trim((string)($_POST[$field] ?? ''));
			}
			$values['offline_mode'] = !empty($_POST['offline_mode']) ? '1' : '0';
			$values['chat_enabled'] = !empty($_POST['chat_enabled']) ? '1' : '0';

			radio_save_settings($values);
			$messages[] = 'Настройки радио сохранены.';
		}

		if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_chat_id'])) {
			radio_delete_chat_message((int)$_POST['delete_chat_id']);
			$messages[] = 'Сообщение удалено.';
		}

		if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['clear_chat'])) {
			radio_clear_chat();
			$messages[] = 'Чат очищен.';
		}

		if (!empty($messages)) {
			stdmsg('Радио', implode('<br />', $messages));
		}

		$settings = radio_settings();
		$chat = radio_chat_messages(11, 20);
		$chat = array_merge($chat, radio_chat_messages(12, 20));

		usort($chat, function ($a, $b) {
			return strcmp((string)$b['added'], (string)$a['added']);
		});
		$chat = array_slice($chat, 0, 30);

		echo '<div class="mn_wrap">';
		echo '<div class="tp1_title"><b>Радио Кинозал.ТВ</b></div>';
		echo '<div class="tp1_body">';
		echo '<form method="post" action="' . radio_h($admin_file) . '.php?op=RadioAdmin">';
		echo '<input type="hidden" name="save_radio" value="1">';
		echo '<table class="tables2 w100p">';
		echo '<tr><td class="colhead" colspan="2">Плеер и эфир</td></tr>';
		radio_admin_row('Текущий трек', 'current_song', $settings['current_song']);
		radio_admin_row('Следующий трек', 'next_song', $settings['next_song']);
		radio_admin_row('Слушателей', 'listeners', $settings['listeners'], 15);
		radio_admin_row('Битрейт', 'kbps', $settings['kbps'], 15);
		radio_admin_row('ID ДиДжея', 'dj_user_id', $settings['dj_user_id'], 15);
		radio_admin_row('Имя ДиДжея без привязки к пользователю', 'dj_name', $settings['dj_name']);
		radio_admin_row('Страница радио', 'public_url', $settings['public_url']);
		radio_admin_row('Поток 128', 'stream_url_128', $settings['stream_url_128']);
		radio_admin_row('Поток 320', 'stream_url_320', $settings['stream_url_320']);
		radio_admin_row('Плейлист 128', 'playlist_url_128', $settings['playlist_url_128']);
		radio_admin_row('Плейлист 320', 'playlist_url_320', $settings['playlist_url_320']);
		echo '<tr><td class="rowhead">Локальный режим</td><td><label><input type="checkbox" name="offline_mode" value="1"' . ((string)$settings['offline_mode'] === '1' ? ' checked' : '') . '> использовать /sounds/silent.mp3 вместо внешнего потока</label></td></tr>';
		echo '<tr><td class="rowhead">Чат</td><td><label><input type="checkbox" name="chat_enabled" value="1"' . ((string)$settings['chat_enabled'] === '1' ? ' checked' : '') . '> включить чат на странице радио</label></td></tr>';
		echo '<tr><td class="colhead" colspan="2">Ссылки и баннеры</td></tr>';
		radio_admin_row('Стол заказов', 'order_url', $settings['order_url']);
		radio_admin_row('Картинка стола заказов', 'order_image', $settings['order_image']);
		radio_admin_row('Большая картинка/ссылка', 'order_full_url', $settings['order_full_url']);
		radio_admin_row('Заголовок группы', 'group_title', $settings['group_title']);
		radio_admin_row('Ссылка группы', 'group_url', $settings['group_url']);
		radio_admin_row('Заголовок анонса', 'announce_title', $settings['announce_title']);
		echo '<tr><td class="rowhead">Анонс</td><td><textarea name="announce_text" rows="5" cols="90">' . radio_h($settings['announce_text']) . '</textarea></td></tr>';
		radio_admin_row('Кому отправлять анкету', 'recruit_contact', $settings['recruit_contact']);
		echo '<tr><td class="rowhead">Текст набора</td><td><textarea name="rules_text" rows="22" cols="90">' . radio_h($settings['rules_text']) . '</textarea></td></tr>';
		echo '<tr><td colspan="2" class="center"><input type="submit" class="btn" value="Сохранить настройки"></td></tr>';
		echo '</table>';
		echo '</form>';
		echo '</div>';

		echo '<div class="tp1_title"><b>Последние сообщения чата</b></div>';
		echo '<div class="tp1_body">';
		echo '<form method="post" action="' . radio_h($admin_file) . '.php?op=RadioAdmin" onsubmit="return confirm(\'Очистить весь чат радио?\');">';
		echo '<input type="hidden" name="clear_chat" value="1">';
		echo '<p><input type="submit" class="btn" value="Очистить чат"></p>';
		echo '</form>';
		echo '<table class="tables2 w100p">';
		echo '<tr><td class="colhead center">ID</td><td class="colhead center">Раздел</td><td class="colhead">Пользователь</td><td class="colhead">Текст</td><td class="colhead w120">Дата</td><td class="colhead center">Действие</td></tr>';

		if (!$chat) {
			echo '<tr><td colspan="6" class="center">Сообщений пока нет.</td></tr>';
		}

		foreach ($chat as $row) {
			$tab = (int)$row['tab'] === 12 ? 'Технический' : 'Болталка';
			echo '<tr>';
			echo '<td class="center">' . (int)$row['id'] . '</td>';
			echo '<td class="center">' . $tab . '</td>';
			echo '<td><a href="/userdetails.php?id=' . (int)$row['userid'] . '" class="u' . (int)$row['userclass'] . '">' . radio_h($row['username']) . '</a></td>';
			echo '<td>' . nl2br(radio_h($row['text'])) . '</td>';
			echo '<td>' . radio_h($row['added']) . '</td>';
			echo '<td class="center"><form method="post" action="' . radio_h($admin_file) . '.php?op=RadioAdmin" onsubmit="return confirm(\'Удалить сообщение?\');"><input type="hidden" name="delete_chat_id" value="' . (int)$row['id'] . '"><input type="submit" class="btn" value="Удалить"></form></td>';
			echo '</tr>';
		}

		echo '</table>';
		echo '</div>';
		echo '</div>';
	}
}

switch ($op) {
	case 'RadioAdmin':
		RadioAdmin();
		break;
}

?>
