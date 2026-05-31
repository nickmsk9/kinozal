<?php

if (!defined('ADMIN_FILE')) {
	die('Illegal File Access');
}

require_once 'include/kz_pay.php';

function PayAdmin()
{
	global $admin_file;

	kz_pay_ensure_schema();
	$messages = array();

	if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_pay_settings'])) {
		kz_pay_set_setting('exchange_options', trim((string)($_POST['exchange_options'] ?? '')));
		kz_pay_set_setting('donor_cost', max(0, (int)($_POST['donor_cost'] ?? 75)));
		kz_pay_set_setting('wish_cost', max(0, (int)($_POST['wish_cost'] ?? 5)));
		kz_pay_set_setting('reset_counter_cost', max(0, (int)($_POST['reset_counter_cost'] ?? 5)));
		kz_pay_set_setting('delete_history_cost', max(0, (int)($_POST['delete_history_cost'] ?? 5)));
		kz_pay_set_setting('vip_cost', max(0, (int)($_POST['vip_cost'] ?? 1500)));
		kz_pay_set_setting('reputation_vote_cost', max(0, (int)($_POST['reputation_vote_cost'] ?? 1)));
		kz_pay_set_setting('vip_enabled', !empty($_POST['vip_enabled']) ? '1' : '0');
		kz_pay_set_setting('home_block_enabled', !empty($_POST['home_block_enabled']) ? '1' : '0');
		kz_pay_set_setting('chat_enabled', !empty($_POST['chat_enabled']) ? '1' : '0');
		$messages[] = 'Настройки сохранены.';
	}

	if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['credit_votes'])) {
		$userid = (int)($_POST['userid'] ?? 0);
		$votes = (int)($_POST['votes'] ?? 0);
		$note = trim((string)($_POST['note'] ?? 'Ручная корректировка голосов'));
		$res = sql_query("SELECT id FROM users WHERE id = $userid LIMIT 1") or sqlerr(__FILE__, __LINE__);
		if ($userid > 0 && mysqli_fetch_assoc($res) && $votes !== 0) {
			kz_pay_credit_votes($userid, $votes, $note);
			$messages[] = 'Баланс голосов пользователя обновлен.';
		} else {
			$messages[] = 'Пользователь не найден или указано нулевое количество голосов.';
		}
	}

	if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_wish_id'])) {
		$id = (int)$_POST['toggle_wish_id'];
		$active = (string)($_POST['active'] ?? 'no') === 'yes' ? 'yes' : 'no';
		sql_query("UPDATE pay_wishes SET active = '$active' WHERE id = $id") or sqlerr(__FILE__, __LINE__);
		$messages[] = 'Пожелание обновлено.';
	}

	if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_chat_id'])) {
		$id = (int)$_POST['delete_chat_id'];
		sql_query("UPDATE pay_chat SET visible = 'no' WHERE id = $id") or sqlerr(__FILE__, __LINE__);
		$messages[] = 'Сообщение скрыто.';
	}

	if ($messages) {
		stdmsg('Меценаты', implode('<br>', array_map('kz_pay_h', $messages)));
	}

	echo '<div class="mn_wrap">';
	echo '<div class="tp1_title"><b>Раздел Меценатов и ВИП</b></div>';
	echo '<div class="tp1_body">';
	echo '<form method="post" action="' . kz_pay_h($admin_file) . '.php?op=PayAdmin">';
	echo '<input type="hidden" name="save_pay_settings" value="1">';
	echo '<table class="tables2 w100p">';
	echo '<tr><td class="colhead" colspan="2">Настройки обмена и стоимости</td></tr>';
	echo '<tr><td class="rowhead w250">Пакеты обмена</td><td><textarea name="exchange_options" rows="7" cols="90">' . kz_pay_h(kz_pay_setting('exchange_options', '')) . '</textarea><br><span class="small">Формат: бонусы:голоса:подпись, каждый пакет с новой строки.</span></td></tr>';
	echo '<tr><td class="rowhead">Статус Меценат</td><td><input type="text" name="donor_cost" value="' . kz_pay_int_setting('donor_cost', 75) . '" size="8"> голосов</td></tr>';
	echo '<tr><td class="rowhead">Пожелание</td><td><input type="text" name="wish_cost" value="' . kz_pay_int_setting('wish_cost', 5) . '" size="8"> голосов</td></tr>';
	echo '<tr><td class="rowhead">Обнулить счетчик</td><td><input type="text" name="reset_counter_cost" value="' . kz_pay_int_setting('reset_counter_cost', 5) . '" size="8"> голосов</td></tr>';
	echo '<tr><td class="rowhead">Удалить историю</td><td><input type="text" name="delete_history_cost" value="' . kz_pay_int_setting('delete_history_cost', 5) . '" size="8"> голосов</td></tr>';
	echo '<tr><td class="rowhead">Отзыв к репутации</td><td><input type="text" name="reputation_vote_cost" value="' . kz_pay_int_setting('reputation_vote_cost', 1) . '" size="8"> голосов</td></tr>';
	echo '<tr><td class="rowhead">Звание ВИП</td><td><input type="text" name="vip_cost" value="' . kz_pay_int_setting('vip_cost', 1500) . '" size="8"> голосов &nbsp; <label><input type="checkbox" name="vip_enabled" value="1"' . (kz_pay_setting('vip_enabled', '0') === '1' ? ' checked' : '') . '> включить</label></td></tr>';
	echo '<tr><td class="rowhead">Главный блок</td><td><label><input type="checkbox" name="home_block_enabled" value="1"' . (kz_pay_setting('home_block_enabled', '1') === '1' ? ' checked' : '') . '> показывать блок меценатов на главной</label></td></tr>';
	echo '<tr><td class="rowhead">Чат</td><td><label><input type="checkbox" name="chat_enabled" value="1"' . (kz_pay_setting('chat_enabled', '1') === '1' ? ' checked' : '') . '> включить чат раздела</label></td></tr>';
	echo '<tr><td colspan="2" class="center"><input type="submit" class="buttonS" value="Сохранить настройки"></td></tr>';
	echo '</table>';
	echo '</form>';
	echo '</div>';

	echo '<div class="tp1_title"><b>Ручное управление голосами</b></div>';
	echo '<div class="tp1_body">';
	echo '<form method="post" action="' . kz_pay_h($admin_file) . '.php?op=PayAdmin">';
	echo '<input type="hidden" name="credit_votes" value="1">';
	echo '<table class="tables2 w100p">';
	echo '<tr><td class="rowhead w250">ID пользователя</td><td><input type="text" name="userid" size="10"></td></tr>';
	echo '<tr><td class="rowhead">Голоса</td><td><input type="text" name="votes" size="10"> <span class="small">Можно отрицательное число.</span></td></tr>';
	echo '<tr><td class="rowhead">Комментарий</td><td><input type="text" name="note" size="70" value="Ручная корректировка голосов"></td></tr>';
	echo '<tr><td colspan="2" class="center"><input type="submit" class="buttonS" value="Применить"></td></tr>';
	echo '</table>';
	echo '</form>';
	echo '</div>';

	$res = sql_query("
		SELECT t.*, u.class
		FROM pay_transactions AS t
		LEFT JOIN users AS u ON u.id = t.userid
		ORDER BY t.created_at DESC, t.id DESC
		LIMIT 50
	") or sqlerr(__FILE__, __LINE__);

	echo '<div class="tp1_title"><b>Последние операции</b></div><div class="tp1_body">';
	echo '<table class="tables2 w100p"><tr><td class="colhead">Дата</td><td class="colhead">Пользователь</td><td class="colhead">Операция</td><td class="colhead center">Бонусы</td><td class="colhead center">Голоса</td><td class="colhead">Описание</td></tr>';
	while ($row = mysqli_fetch_assoc($res)) {
		echo '<tr><td>' . kz_pay_h($row['created_at']) . '</td><td>' . kz_pay_user_link($row) . '</td><td>' . kz_pay_h($row['operation']) . '</td><td class="center">' . number_format((float)$row['bonus_delta'], 2, '.', ' ') . '</td><td class="center">' . (int)$row['votes_delta'] . '</td><td>' . kz_pay_h($row['details']) . '</td></tr>';
	}
	echo '</table></div>';

	$wishes = sql_query("
		SELECT *
		FROM pay_wishes
		ORDER BY added DESC, id DESC
		LIMIT 30
	") or sqlerr(__FILE__, __LINE__);
	echo '<div class="tp1_title"><b>Пожелания</b></div><div class="tp1_body">';
	echo '<table class="tables2 w100p"><tr><td class="colhead">Дата</td><td class="colhead">Пользователь</td><td class="colhead">Текст</td><td class="colhead center">Статус</td><td class="colhead center">Действие</td></tr>';
	while ($row = mysqli_fetch_assoc($wishes)) {
		echo '<tr><td>' . kz_pay_h($row['added']) . '</td><td>' . kz_pay_user_link($row) . '</td><td>' . nl2br(kz_pay_h($row['text'])) . '</td><td class="center">' . ($row['active'] === 'yes' ? 'показано' : 'скрыто') . '</td><td class="center"><form method="post" action="' . kz_pay_h($admin_file) . '.php?op=PayAdmin"><input type="hidden" name="toggle_wish_id" value="' . (int)$row['id'] . '"><input type="hidden" name="active" value="' . ($row['active'] === 'yes' ? 'no' : 'yes') . '"><input type="submit" class="btn" value="' . ($row['active'] === 'yes' ? 'Скрыть' : 'Показать') . '"></form></td></tr>';
	}
	echo '</table></div>';

	$chat = sql_query("
		SELECT *
		FROM pay_chat
		WHERE visible = 'yes'
		ORDER BY added DESC, id DESC
		LIMIT 40
	") or sqlerr(__FILE__, __LINE__);
	echo '<div class="tp1_title"><b>Сообщения техподдержки</b></div><div class="tp1_body">';
	echo '<table class="tables2 w100p"><tr><td class="colhead">Дата</td><td class="colhead center">Вкладка</td><td class="colhead">Пользователь</td><td class="colhead">Текст</td><td class="colhead center">Действие</td></tr>';
	while ($row = mysqli_fetch_assoc($chat)) {
		echo '<tr><td>' . kz_pay_h($row['added']) . '</td><td class="center">' . ((int)$row['tab'] === 2 ? 'Проблемы' : 'Вопросы') . '</td><td>' . kz_pay_user_link($row) . '</td><td>' . nl2br(kz_pay_h($row['text'])) . '</td><td class="center"><form method="post" action="' . kz_pay_h($admin_file) . '.php?op=PayAdmin"><input type="hidden" name="delete_chat_id" value="' . (int)$row['id'] . '"><input type="submit" class="btn" value="Скрыть"></form></td></tr>';
	}
	echo '</table></div>';
	echo '</div>';
}

switch ($op) {
	case 'PayAdmin':
		PayAdmin();
		break;
}

?>
