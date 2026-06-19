<?php

require_once __DIR__ . '/include/bittorrent.php';
require_once __DIR__ . '/include/messages.php';

dbconn(false);
loggedinorreturn();

if (get_user_class() < UC_ADMINISTRATOR) {
	stderr($tracker_lang['error'] ?? 'Ошибка', $tracker_lang['access_denied'] ?? 'Доступ запрещен.');
}

function staffmess_h($value)
{
	return function_exists('htmlspecialchars_uni')
		? htmlspecialchars_uni((string)$value)
		: htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function staffmess_token()
{
	global $CURUSER;

	return (string)($CURUSER['hash4u'] ?? ($CURUSER['logout_hash'] ?? ''));
}

function staffmess_selected($current, $value)
{
	return ((string)$current === (string)$value) ? ' checked' : '';
}

function staffmess_checked($value)
{
	return $value ? ' checked' : '';
}

function staffmess_selected_classes($values)
{
	$classes = array();

	foreach ((array)$values as $value) {
		if ($value === '' || !is_numeric($value)) {
			continue;
		}

		$value = (int)$value;
		if (is_valid_user_class($value)) {
			$classes[$value] = $value;
		}
	}

	ksort($classes);
	return array_values($classes);
}

function staffmess_default_classes()
{
	$classes = array();

	for ($i = UC_USER; $i <= UC_SYSOP; $i++) {
		$classes[] = $i;
	}

	return $classes;
}

function staffmess_parse_user_list($raw)
{
	$ids = array();
	$names = array();
	$raw = trim((string)$raw);

	if ($raw === '') {
		return array($ids, $names);
	}

	$parts = preg_split('/[\s,;]+/u', $raw, -1, PREG_SPLIT_NO_EMPTY);
	if (!is_array($parts)) {
		return array($ids, $names);
	}

	foreach ($parts as $part) {
		$part = trim((string)$part);
		if ($part === '') {
			continue;
		}

		if (ctype_digit($part)) {
			$id = (int)$part;
			if ($id > 0) {
				$ids[$id] = $id;
			}
			continue;
		}

		$partLength = function_exists('mb_strlen') ? mb_strlen($part, 'UTF-8') : strlen($part);
		if ($partLength <= 40) {
			$names[$part] = $part;
		}
	}

	return array(array_values($ids), array_values($names));
}

function staffmess_filters_from_request()
{
	$target = (string)($_POST['target'] ?? 'all');
	if (!in_array($target, array('all', 'staff', 'class', 'list'), true)) {
		$target = 'all';
	}

	$classes = staffmess_selected_classes($_POST['classes'] ?? array());
	if ($_SERVER['REQUEST_METHOD'] !== 'POST' && !$classes) {
		$classes = staffmess_default_classes();
	}

	$senderMode = (string)($_POST['sender_mode'] ?? 'system');
	if (!in_array($senderMode, array('system', 'admin'), true)) {
		$senderMode = 'system';
	}

	return array(
		'target' => $target,
		'classes' => $classes,
		'user_list' => (string)($_POST['user_list'] ?? ''),
		'enabled_only' => !isset($_POST['enabled_only']) ? true : ((string)$_POST['enabled_only'] === 'yes'),
		'skip_parked' => !isset($_POST['skip_parked']) ? true : ((string)$_POST['skip_parked'] === 'yes'),
		'exclude_self' => !isset($_POST['exclude_self']) ? true : ((string)$_POST['exclude_self'] === 'yes'),
		'sender_mode' => $senderMode,
	);
}

function staffmess_build_where(array $filters, array &$errors)
{
	global $CURUSER;

	$where = array("u.id > 0", "u.status = 'confirmed'");

	if ($filters['enabled_only']) {
		$where[] = "u.enabled = 'yes'";
	}

	if ($filters['skip_parked']) {
		$where[] = "u.parked = 'no'";
	}

	if ($filters['exclude_self']) {
		$where[] = 'u.id <> ' . (int)$CURUSER['id'];
	}

	if ($filters['target'] === 'staff') {
		$where[] = 'u.class >= ' . (int)UC_MODERATOR;
	} elseif ($filters['target'] === 'class') {
		if (!$filters['classes']) {
			$errors[] = 'Выберите хотя бы один класс пользователей.';
		} else {
			$where[] = 'u.class IN (' . implode(',', array_map('intval', $filters['classes'])) . ')';
		}
	} elseif ($filters['target'] === 'list') {
		list($ids, $names) = staffmess_parse_user_list($filters['user_list']);
		$parts = array();

		if ($ids) {
			$parts[] = 'u.id IN (' . implode(',', array_map('intval', $ids)) . ')';
		}

		if ($names) {
			$escaped = array();
			foreach ($names as $name) {
				$escaped[] = sqlesc($name, true);
			}
			$parts[] = 'u.username IN (' . implode(',', $escaped) . ')';
		}

		if (!$parts) {
			$errors[] = 'Введите хотя бы один ID или логин получателя.';
		} else {
			$where[] = '(' . implode(' OR ', $parts) . ')';
		}
	}

	return $where;
}

function staffmess_count_recipients(array $where)
{
	$res = sql_query('SELECT COUNT(*) AS total FROM users AS u WHERE ' . implode(' AND ', $where)) or sqlerr(__FILE__, __LINE__);
	$row = mysqli_fetch_assoc($res);

	return (int)($row['total'] ?? 0);
}

function staffmess_fetch_sample(array $where, $limit = 30)
{
	$limit = max(1, (int)$limit);
	$res = sql_query("
		SELECT u.id, u.username, u.class
		FROM users AS u
		WHERE " . implode(' AND ', $where) . "
		ORDER BY u.class DESC, u.username ASC
		LIMIT $limit
	") or sqlerr(__FILE__, __LINE__);

	$rows = array();
	while ($row = mysqli_fetch_assoc($res)) {
		$rows[] = $row;
	}

	return $rows;
}

function staffmess_insert_messages(array $where, $subject, $message, $senderMode)
{
	global $CURUSER, $link;

	$poster = (int)$CURUSER['id'];
	$sender = $senderMode === 'admin' ? $poster : 0;
	$added = sqlesc(get_date_time(), true);
	$subject = sqlesc((string)$subject, true);
	$message = sqlesc((string)$message, true);

	sql_query("
		INSERT INTO messages (poster, sender, receiver, added, msg, subject, saved, location)
		SELECT $poster, $sender, u.id, $added, $message, $subject, 'no', " . KZ_PM_INBOX . "
		FROM users AS u
		WHERE " . implode(' AND ', $where) . "
	") or sqlerr(__FILE__, __LINE__);

	return $link instanceof mysqli ? mysqli_affected_rows($link) : 0;
}

function staffmess_subject($subject)
{
	$subject = trim((string)$subject);

	if (function_exists('mb_substr')) {
		return mb_substr($subject, 0, 255, 'UTF-8');
	}

	return substr($subject, 0, 255);
}

$filters = staffmess_filters_from_request();
$subject = staffmess_subject($_POST['subject'] ?? '');
$message = trim((string)($_POST['msg'] ?? ''));
$action = (string)($_POST['action'] ?? '');
if (isset($_POST['action_preview'])) {
	$action = 'preview';
} elseif (isset($_POST['action_send'])) {
	$action = 'send';
}
$errors = array();
$notice = '';
$preview = false;
$recipientCount = 0;
$recipientSample = array();

if (isset($_GET['sent']) && ctype_digit((string)$_GET['sent'])) {
	$notice = 'Рассылка отправлена. Получателей: ' . (int)$_GET['sent'] . '.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$token = staffmess_token();
	if ($token !== '' && (string)($_POST['hash4u'] ?? '') !== $token) {
		stderr($tracker_lang['error'] ?? 'Ошибка', 'Неверный ключ формы.');
	}

	if ($subject === '') {
		$errors[] = 'Введите тему сообщения.';
	}

	if ($message === '') {
		$errors[] = 'Введите текст сообщения.';
	}

	$where = staffmess_build_where($filters, $errors);

	if (!$errors) {
		$recipientCount = staffmess_count_recipients($where);
		if ($recipientCount <= 0) {
			$errors[] = 'По выбранным условиям получателей не найдено.';
		} else {
			$recipientSample = staffmess_fetch_sample($where);
			$preview = true;
		}
	}

	if ($action === 'send' && !$errors) {
		if ((string)($_POST['confirm_send'] ?? '') !== 'yes') {
			$errors[] = 'Подтвердите массовую отправку.';
		} else {
			$sent = staffmess_insert_messages($where, $subject, $message, $filters['sender_mode']);
			write_log(
				'Массовая рассылка ЛС: администратор ' . ($CURUSER['username'] ?? ('#' . (int)$CURUSER['id'])) . ' отправил сообщение "' . $subject . '" получателям: ' . $sent . '.',
				'F25B61',
				'staffmess'
			);
			header('Location: /staffmess.php?sent=' . (int)$sent);
			exit;
		}
	}
}

$hash = staffmess_h(staffmess_token());
$hide_right_blocks = true;
stdhead('Массовая рассылка ЛС');
echo msg_scripts_and_style();
?>
<style type="text/css">
.staffmess-editor .cmet_e_but {
	display: block;
	margin: 0 0 5px;
	padding: 0;
	overflow: hidden;
}
.staffmess-editor .cmet_e_but ul {
	display: flex;
	flex-wrap: wrap;
	float: none;
	align-items: center;
	gap: 4px;
	list-style: none;
	margin: 0;
	padding: 0;
}
.staffmess-editor .cmet_e_but ul li {
	display: block;
	float: none;
	list-style: none;
	margin: 0;
	padding: 0;
}
.staffmess-editor .cmet_e_but .buttonS {
	min-width: 32px;
	padding: 3px 7px;
	font-size: 10px;
	line-height: 14px;
	text-align: center;
}
.staffmess-editor .cmet_e_inp {
	clear: both;
	display: block;
	margin: 0 0 5px;
	padding: 0;
}
.staffmess-editor .cmet_e_inp textarea {
	display: block;
	box-sizing: border-box;
	width: 100%;
	max-width: 100%;
	min-height: 150px;
	margin: 0;
	resize: vertical;
}
</style>

<div style="width: 100%; text-align: center;">
	<div style="width: 100%; max-width: 860px; display: inline-block; text-align: left;">
		<?php if ($notice !== '') { ?>
			<div class="bx1_0">
				<div class="pad10x10 center green"><b><?= staffmess_h($notice) ?></b></div>
			</div>
		<?php } ?>

		<?php if ($errors) { ?>
			<div class="bx1_0">
				<div class="pad10x10 red">
					<b>Проверьте форму:</b><br>
					<?= implode('<br>', array_map('staffmess_h', $errors)) ?>
				</div>
			</div>
		<?php } ?>

		<?php if ($preview) { ?>
			<div class="mn_wrap">
				<div class="tp1_title"><b>Предпросмотр рассылки</b></div>
				<div class="tp1_body">
					<div class="pad0x0x10x0">
						Получателей: <b><?= (int)$recipientCount ?></b>.
						Отправитель: <b><?= $filters['sender_mode'] === 'admin' ? staffmess_h($CURUSER['username'] ?? 'Администратор') : 'Администрация' ?></b>.
					</div>
					<table class="tables1 w100p">
						<tr>
							<td class="rowhead w150">Тема</td>
							<td><?= staffmess_h($subject) ?></td>
						</tr>
						<tr>
							<td class="rowhead top">Сообщение</td>
							<td><?= msg_format($message) ?></td>
						</tr>
						<tr>
							<td class="rowhead top">Первые получатели</td>
							<td>
								<?php foreach ($recipientSample as $user) { ?>
									<a href="/userdetails.php?id=<?= (int)$user['id'] ?>" class="u<?= (int)$user['class'] ?>"><?= staffmess_h($user['username']) ?></a>
								<?php } ?>
								<?php if ($recipientCount > count($recipientSample)) { ?>
									<br><span class="small">Показаны первые <?= count($recipientSample) ?> из <?= (int)$recipientCount ?>.</span>
								<?php } ?>
							</td>
						</tr>
					</table>
				</div>
			</div>
		<?php } ?>

		<div class="mn_wrap">
			<div class="tp1_title"><b>Массовая рассылка ЛС</b></div>
			<div class="tp1_body">
				<form method="post" action="/staffmess.php" name="message" autocomplete="off">
					<input type="hidden" name="hash4u" value="<?= $hash ?>">

					<table class="tables1 w100p">
						<tr>
							<td class="rowhead w180">Получатели</td>
							<td>
								<label><input class="styled" type="radio" name="target" value="all"<?= staffmess_selected($filters['target'], 'all') ?>> все подтвержденные пользователи</label><br>
								<label><input class="styled" type="radio" name="target" value="staff"<?= staffmess_selected($filters['target'], 'staff') ?>> персонал сайта (модераторы и выше)</label><br>
								<label><input class="styled" type="radio" name="target" value="class"<?= staffmess_selected($filters['target'], 'class') ?>> выбранные классы</label><br>
								<label><input class="styled" type="radio" name="target" value="list"<?= staffmess_selected($filters['target'], 'list') ?>> список ID или логинов</label>
							</td>
						</tr>
						<tr>
							<td class="rowhead top">Классы</td>
							<td>
								<?php for ($i = UC_USER; $i <= UC_SYSOP; $i++) { ?>
									<label class="nw" style="display:inline-block; min-width:190px; padding:2px 0;">
										<input class="styled" type="checkbox" name="classes[]" value="<?= $i ?>"<?= staffmess_checked(in_array($i, $filters['classes'], true)) ?>>
										<?= staffmess_h(get_user_class_name($i)) ?>
									</label>
								<?php } ?>
								<div class="small">Используется, когда выбран режим "выбранные классы".</div>
							</td>
						</tr>
						<tr>
							<td class="rowhead top"><label for="staffmess-user-list">Список</label></td>
							<td>
								<textarea id="staffmess-user-list" name="user_list" rows="4" class="w98p"><?= staffmess_h($filters['user_list']) ?></textarea>
								<div class="small">ID или логины через пробел, запятую, точку с запятой или с новой строки.</div>
							</td>
						</tr>
						<tr>
							<td class="rowhead">Фильтры</td>
							<td>
								<label><input class="styled" type="checkbox" name="enabled_only" value="yes"<?= staffmess_checked($filters['enabled_only']) ?>> только включенные аккаунты</label><br>
								<label><input class="styled" type="checkbox" name="skip_parked" value="yes"<?= staffmess_checked($filters['skip_parked']) ?>> не отправлять припаркованным</label><br>
								<label><input class="styled" type="checkbox" name="exclude_self" value="yes"<?= staffmess_checked($filters['exclude_self']) ?>> не отправлять себе</label>
							</td>
						</tr>
						<tr>
							<td class="rowhead">Отправитель</td>
							<td>
								<label><input class="styled" type="radio" name="sender_mode" value="system"<?= staffmess_selected($filters['sender_mode'], 'system') ?>> Администрация</label><br>
								<label><input class="styled" type="radio" name="sender_mode" value="admin"<?= staffmess_selected($filters['sender_mode'], 'admin') ?>> <?= staffmess_h($CURUSER['username'] ?? 'мой аккаунт') ?></label>
							</td>
						</tr>
						<tr>
							<td class="rowhead"><label for="staffmess-subject">Тема</label></td>
							<td><input id="staffmess-subject" type="text" name="subject" class="w98p" maxlength="255" value="<?= staffmess_h($subject) ?>"></td>
						</tr>
						<tr>
							<td class="rowhead top"><label for="msg">Сообщение</label></td>
							<td>
								<div class="staffmess-editor">
									<div class="cmet_e_but"><ul>
										<li><input class="buttonS" type="button" value="b" style="font-weight:bold;" onclick="InsertCode('msg','b')"></li>
										<li><input class="buttonS" type="button" value="i" style="font-style:italic;" onclick="InsertCode('msg','i')"></li>
										<li><input class="buttonS" type="button" value="u" style="text-decoration:underline;" onclick="InsertCode('msg','u')"></li>
										<li><input class="buttonS" type="button" value="quote" onclick="InsertCode('msg','quote')"></li>
										<li><input class="buttonS" type="button" value="url" onclick="InsertCode('msg','url')"></li>
										<li><input class="buttonS" type="button" value="img" onclick="InsertCode('msg','img')"></li>
									</ul><div class="clr"></div></div>
									<div class="cmet_e_inp"><textarea id="msg" name="msg" rows="12" class="w98p"><?= staffmess_h($message) ?></textarea></div>
								</div>
							</td>
						</tr>
						<tr>
							<td class="rowhead">Подтверждение</td>
							<td>
								<label><input class="styled" type="checkbox" name="confirm_send" value="yes"> подтверждаю массовую отправку выбранным получателям</label>
							</td>
						</tr>
						<tr>
							<td></td>
							<td>
								<input class="buttonS" type="submit" name="action_preview" value="Предпросмотр">
								<input class="buttonS" type="submit" name="action_send" value="Отправить ЛС">
							</td>
						</tr>
					</table>
				</form>
			</div>
		</div>
	</div>
</div>

<?php
stdfoot();
