<?php

require_once 'include/bittorrent.php';

dbconn();
loggedinorreturn();

if (get_user_class() < UC_ADMINISTRATOR) {
	stderr($tracker_lang['error'], 'Ошибка доступа.');
}

$action = isset($_GET['action']) ? (string)$_GET['action'] : '';
$warning = '';

function news_h($value)
{
	return htmlspecialchars_uni((string)$value);
}

function news_text($value)
{
	return nl2br(htmlspecialchars_uni((string)$value));
}

function news_form($action_url, $title, $subject = '', $body = '', $button = 'Сохранить')
{
	?>
	<form name="news" method="post" action="<?php echo news_h($action_url); ?>">
		<table class="tables2 w100p">
			<tr>
				<td class="colhead" colspan="2"><?php echo news_h($title); ?></td>
			</tr>
			<tr>
				<td class="rowhead w120">Тема</td>
				<td>
					<input type="text" name="subject" maxlength="70" size="70" value="<?php echo news_h($subject); ?>" />
				</td>
			</tr>
			<tr>
				<td class="rowhead top">Текст</td>
				<td>
					<textarea name="body" rows="10"><?php echo news_h($body); ?></textarea>
				</td>
			</tr>
			<tr>
				<td colspan="2" class="center">
					<input type="submit" class="buttonS" value="<?php echo news_h($button); ?>" />
				</td>
			</tr>
		</table>
	</form>
	<?php
}

/*
 * Удаление новости
 */
if ($action === 'delete') {
	$newsid = isset($_GET['newsid']) ? (int)$_GET['newsid'] : 0;

	if (!is_valid_id($newsid)) {
		stderr($tracker_lang['error'], 'Неверный идентификатор новости.');
	}

	$sure = isset($_GET['sure']) ? (int)$_GET['sure'] : 0;

	if (!$sure) {
		stderr(
			'Удаление новости',
			'Вы действительно хотите удалить эту новость?<br /><br />
			<a href="?action=delete&amp;newsid=' . $newsid . '&amp;sure=1"><b>Да, удалить</b></a>'
		);
	}

	sql_query('DELETE FROM news WHERE id = ' . $newsid . ' LIMIT 1') or sqlerr(__FILE__, __LINE__);

	$warning = 'Новость успешно удалена.';
}

/*
 * Добавление новости
 */
if ($action === 'add') {
	if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
		stderr($tracker_lang['error'], 'Неверный метод запроса.');
	}

	$subject = isset($_POST['subject']) ? trim((string)$_POST['subject']) : '';
	$body = isset($_POST['body']) ? trim((string)$_POST['body']) : '';

	if ($subject === '') {
		stderr($tracker_lang['error'], 'Тема новости не может быть пустой.');
	}

	if ($body === '') {
		stderr($tracker_lang['error'], 'Текст новости не может быть пустым.');
	}

	sql_query("
		INSERT INTO news 
			(userid, added, body, subject)
		VALUES 
			(" . (int)$CURUSER['id'] . ", NOW(), " . sqlesc($body) . ", " . sqlesc($subject) . ")
	") or sqlerr(__FILE__, __LINE__);

	$warning = 'Новость успешно добавлена.';
}

/*
 * Редактирование новости
 */
if ($action === 'edit') {
	$newsid = isset($_GET['newsid']) ? (int)$_GET['newsid'] : 0;

	if (!is_valid_id($newsid)) {
		stderr($tracker_lang['error'], 'Неверный идентификатор новости.');
	}

	$res = sql_query('SELECT * FROM news WHERE id = ' . $newsid . ' LIMIT 1') or sqlerr(__FILE__, __LINE__);

	if (mysqli_num_rows($res) !== 1) {
		stderr($tracker_lang['error'], 'Новость не найдена.');
	}

	$arr = mysqli_fetch_assoc($res);

	if ($_SERVER['REQUEST_METHOD'] === 'POST') {
		$subject = isset($_POST['subject']) ? trim((string)$_POST['subject']) : '';
		$body = isset($_POST['body']) ? trim((string)$_POST['body']) : '';

		if ($subject === '') {
			stderr($tracker_lang['error'], 'Тема новости не может быть пустой.');
		}

		if ($body === '') {
			stderr($tracker_lang['error'], 'Текст новости не может быть пустым.');
		}

		sql_query("
			UPDATE news 
			SET 
				body = " . sqlesc($body) . ",
				subject = " . sqlesc($subject) . "
			WHERE id = " . $newsid . "
			LIMIT 1
		") or sqlerr(__FILE__, __LINE__);

		$warning = 'Новость успешно отредактирована.';
	} else {
		stdhead('Редактирование новости');

		news_form(
			'?action=edit&amp;newsid=' . $newsid,
			'Редактирование новости',
			isset($arr['subject']) ? $arr['subject'] : '',
			isset($arr['body']) ? $arr['body'] : '',
			'Отредактировать'
		);

		stdfoot();
		die;
	}
}

stdhead('Новости');

if ($warning !== '') {
	echo '<div class="bx1"><span class="green"><b>' . news_h($warning) . '</b></span></div>';
}

news_form(
	'?action=add',
	'Добавить новость',
	'',
	'',
	'Добавить'
);

echo '<br />';

$query = sql_query("
	SELECT 
		news.*,
		users.username
	FROM news
	LEFT JOIN users ON news.userid = users.id
	ORDER BY news.added DESC
") or sqlerr(__FILE__, __LINE__);

if (mysqli_num_rows($query) > 0) {
	begin_main_frame();
	begin_frame();

	while ($result = mysqli_fetch_assoc($query)) {
		$newsid = isset($result['id']) ? (int)$result['id'] : 0;
		$userid = isset($result['userid']) ? (int)$result['userid'] : 0;
		$subject = isset($result['subject']) ? (string)$result['subject'] : '';
		$body = isset($result['body']) ? (string)$result['body'] : '';
		$username = isset($result['username']) ? (string)$result['username'] : '';
		$added_raw = isset($result['added']) ? (string)$result['added'] : '';

		$added = news_h($added_raw);

		if ($added_raw !== '' && function_exists('sql_timestamp_to_unix_timestamp') && function_exists('get_elapsed_time')) {
			$added .= ' GMT (' . news_h(get_elapsed_time(sql_timestamp_to_unix_timestamp($added_raw))) . ' назад)';
		}

		if ($username === '') {
			$by = 'Неизвестно [' . $userid . ']';
		} else {
			$by = '<a href="userdetails.php?id=' . $userid . '"><b>' . news_h($username) . '</b></a>';
		}

		echo '<table class="tables2 w100p">';
		echo '<tr>';
		echo '<td class="small">';
		echo 'Добавлена ' . $added . ' - ' . $by;
		echo ' - [<a href="?action=edit&amp;newsid=' . $newsid . '"><b>Редактировать</b></a>]';
		echo ' - [<a href="?action=delete&amp;newsid=' . $newsid . '"><b>Удалить</b></a>]';
		echo '</td>';
		echo '</tr>';
		echo '</table>';

		echo '<table class="tables2 w100p">';
		echo '<tr>';
		echo '<td class="colhead">' . news_h($subject) . '</td>';
		echo '</tr>';
		echo '<tr>';
		echo '<td class="comment">' . news_text($body) . '</td>';
		echo '</tr>';
		echo '</table>';

		echo '<br />';
	}

	end_frame();
	end_main_frame();
} else {
	stdmsg('Новости', 'Новостей пока нет.');
}

stdfoot();

?>