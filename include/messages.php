<?php

if (!defined('KZ_PM_INBOX')) {
	define('KZ_PM_ARCHIVE', 0);
	define('KZ_PM_INBOX', 1);
}

if (!defined('KZ_AUTO_MIGRATIONS')) {
	define('KZ_AUTO_MIGRATIONS', false);
}

function msg_index_exists($index)
{
	$res = sql_query("SHOW INDEX FROM messages WHERE Key_name = " . sqlesc($index));
	return $res && mysqli_num_rows($res) > 0;
}

function msg_ensure_schema()
{
	static $done = false;

	if ($done) {
		return;
	}

	$done = true;

	if (!defined('KZ_AUTO_MIGRATIONS') || KZ_AUTO_MIGRATIONS !== true) {
		return;
	}

	$indexes = array(
		array('receiver_location_id', 'ALTER TABLE messages ADD KEY receiver_location_id (receiver, location, id)'),
		array('receiver_location_unread_id', 'ALTER TABLE messages ADD KEY receiver_location_unread_id (receiver, location, unread, id)'),
		array('sender_saved_location_id', 'ALTER TABLE messages ADD KEY sender_saved_location_id (sender, saved, location, id)'),
	);

	foreach ($indexes as $index) {
		if (!msg_index_exists($index[0])) {
			sql_query($index[1]) or sqlerr(__FILE__, __LINE__);
		}
	}
}

function msg_h($value)
{
	return function_exists('htmlspecialchars_uni') ? htmlspecialchars_uni((string)$value) : htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function msg_avatar(array $user)
{
	$avatar = trim((string)($user['avatar'] ?? ''));
	return $avatar !== '' ? $avatar : '/pic/ava_m.jpg';
}

function msg_user_link($id, $username, $class = 0)
{
	$id = (int)$id;
	if ($id <= 0 || (string)$username === '') {
		return '<i>system</i>';
	}
	return '<a href="/userdetails.php?id=' . $id . '" class="u' . (int)$class . '">' . msg_h($username) . '</a>';
}

function msg_date($value)
{
	$ts = strtotime((string)$value);
	if (!$ts) {
		return msg_h($value);
	}
	if (date('Y-m-d', $ts) === date('Y-m-d')) {
		return 'сегодня в ' . date('H:i', $ts);
	}
	if (date('Y-m-d', $ts) === date('Y-m-d', time() - 86400)) {
		return 'вчера в ' . date('H:i', $ts);
	}
	return date('d.m.Y в H:i', $ts);
}

function msg_format($text)
{
	return function_exists('format_comment') ? format_comment((string)$text) : nl2br(msg_h($text));
}

function msg_tabs($box, $counts = null)
{
	$items = array(
		'in' => array('/inbox.php', 'Принятые сообщения'),
		'out' => array('/inbox.php?out=1', 'Отправленные сообщения'),
		'arch' => array('/inbox.php?arch=1', 'Архив сообщений'),
	);
	if (is_array($counts)) {
		$items['in'][1] .= ' (' . (int)($counts['inbox'] ?? 0) . '/' . (int)($counts['unread'] ?? 0) . ')';
		$items['out'][1] .= ' (' . (int)($counts['outbox'] ?? 0) . ')';
		$items['arch'][1] .= ' (' . (int)($counts['archive'] ?? 0) . ')';
	}
	$html = '<div class="pad0x0x5x0"><ul class="lis">';
	foreach ($items as $key => $item) {
		$html .= '<li' . ($box === $key ? ' class="tp"' : '') . '><a href="' . $item[0] . '">' . $item[1] . '</a>';
	}
	return $html . '</ul></div>';
}

function msg_box_url($box, $pager = false)
{
	if ($box === 'out') {
		return $pager ? '/inbox.php?out=1&amp;' : '/inbox.php?out=1';
	}
	if ($box === 'arch') {
		return $pager ? '/inbox.php?arch=1&amp;' : '/inbox.php?arch=1';
	}
	return $pager ? '/inbox.php?' : '/inbox.php';
}

function msg_profile_menu(array $user, $self = true)
{
	$id = (int)$user['id'];
	$avatar = msg_h(msg_avatar($user));
	$rep = function_exists('reputation_value') ? reputation_value($user) : max(0, (int)($user['simpaty'] ?? 0));
	$bonus = function_exists('pay_user_votes_from_array') ? number_format(pay_user_votes_from_array($user), 0, '.', ' ') : number_format((float)($user['bonus'] ?? 0), 0, '.', ' ');
	$hash = msg_h($user['hash4u'] ?? ($user['logout_hash'] ?? ''));

	if (!$self) {
		return '<div class="mn1_menu"><ul class="men u2 w200">'
			. '<li class="img"><a href="/userdetails.php?id=' . $id . '"><img src="' . $avatar . '" class="p200" alt=""></a></li>'
			. '<li class="tp">Меню пользователя</li>'
			. '<li><span class="bulet"></span><a href="/sendmessage.php?receiver=' . $id . '">Отправить сообщение</a></li>'
			. '<li><span class="bulet"></span><a href="/userdetails.php?id=' . $id . '">Профиль пользователя</a></li>'
			. '<li class="sf"><span class="bulet"></span><a href="/mytorrents.php?userid=' . $id . '">Раздачи пользователя</a></li>'
			. '<li class="tp">Репутация<span class="floatright"><a href="/pay_mode_b.php?userid=' . $id . '" title="Понизить репутацию"><img border="0" src="/pic/minus.gif" alt=""></a> <b>' . $rep . '</b> <a href="/pay_mode_b.php?userid=' . $id . '" title="Повысить репутацию"><img border="0" src="/pic/plus.gif" alt=""></a></span></li>'
			. '<li><span class="bulet"></span><a href="/pay_mode_b.php?userid=' . $id . '">Оставить отзыв</a></li>'
			. '<li><span class="bulet"></span><a href="/user_reputation.php?id=' . $id . '">Полученные отзывы</a></li>'
			. '<li><span class="bulet"></span><a href="/user_reputation.php?id=' . $id . '&amp;type=2">Отданные отзывы</a></li>'
			. '<li class="tp">История</li>'
			. '<li><span class="bulet"></span><a href="/userhistory.php?id=' . $id . '">Комментариев</a></li>'
			. '<li class="sf"><span class="bulet"></span><a href="/uservotes.php?id=' . $id . '">Голосований</a></li>'
			. '<li class="tp">Действия</li>'
			. '<li><span class="bulet"></span><a href="/bookmarks.php?type=3&amp;add=' . $id . '&amp;hash4u=' . $hash . '">Внести в закладки</a></li>'
			. '<li><span class="bulet"></span><a href="/friends.php?action=add&amp;type=friend&amp;targetid=' . $id . '&amp;hash4u=' . $hash . '">Внести в друзья</a></li>'
			. '<li><span class="bulet"></span><a href="/friends.php?action=add&amp;type=block&amp;targetid=' . $id . '&amp;hash4u=' . $hash . '">Внести в игнор</a></li>'
			. '</ul></div>';
	}

	return '<div class="mn1_menu"><ul class="men u2 w200">'
		. '<li class="img"><a href="/userdetails.php?id=' . $id . '"><img src="' . $avatar . '" class="p200" alt=""></a></li>'
		. '<li class="tp">Меню пользователя</li>'
		. '<li><span class="bulet"></span><a href="/inbox.php">Личные сообщения</a></li>'
		. '<li><span class="bulet"></span><a href="/userdetails.php?id=' . $id . '">Мой профиль</a></li>'
		. '<li><span class="bulet"></span><a href="/my.php">Редактировать профиль</a></li>'
		. '<li><span class="bulet"></span><a href="/mygroups.php">Мои группы</a></li>'
		. '<li><span class="bulet"></span><a href="/friends.php?id=' . $id . '">Мой список друзей</a></li>'
		. '<li class="sf"><span class="bulet"></span><a href="/mytorrents.php?id=' . $id . '">Мои раздачи</a></li>'
		. '<li class="tp">Репутация<span class="floatright"><a href="/pay_mode_b.php?userid=' . $id . '" title="Понизить репутацию"><img border="0" src="/pic/minus.gif" alt=""></a> <b>' . $rep . '</b> <a href="/pay_mode_b.php?userid=' . $id . '" title="Повысить репутацию"><img border="0" src="/pic/plus.gif" alt=""></a></span></li>'
		. '<li><span class="bulet"></span><a href="/user_reputation.php?id=' . $id . '">Полученные отзывы</a></li>'
		. '<li><span class="bulet"></span><a href="/user_reputation.php?id=' . $id . '&amp;type=2">Отданные отзывы</a></li>'
		. '<li class="tp">Закладки</li>'
		. '<li><span class="bulet"></span><a href="/bookmarks.php?type=1">Раздачи</a></li>'
		. '<li><span class="bulet"></span><a href="/bookmarks.php?type=2">Группы</a></li>'
		. '<li><span class="bulet"></span><a href="/bookmarks.php?type=3">Пользователи</a></li>'
		. '<li class="sf"><span class="bulet"></span><a href="/bookmarks.php?type=4">Персоны</a></li>'
		. '<li class="tp">История</li>'
		. '<li><span class="bulet"></span><a href="/hytorrents.php?id=' . $id . '">Скачанного</a></li>'
		. '<li><span class="bulet"></span><a href="/userhistory.php?id=' . $id . '">Комментариев</a></li>'
		. '<li><span class="bulet"></span><a href="/uservotes.php?id=' . $id . '">Голосований</a></li>'
		. '<li class="tp">Голоса<span class="floatright b">' . $bonus . '</span></li>'
		. '<li><span class="bulet"></span><a href="/pay.php">Получить голоса</a></li>'
		. '<li><span class="bulet"></span><a href="/pay_mode.php">Управление голосами</a></li>'
		. '<li><span class="bulet"></span><a href="/pay_mode.php">Оставить пожелание</a></li>'
		. '<li><span class="bulet"></span><a href="/pay_mode.php">Обнулить счетчик скачиваний</a></li>'
		. '</ul></div>';
}

function msg_box_type()
{
	if (!empty($_GET['out'])) {
		return 'out';
	}
	if (!empty($_GET['arch'])) {
		return 'arch';
	}
	return 'in';
}

function msg_limit_sql($limit)
{
	$limit = trim((string)$limit);
	return preg_match('/^LIMIT\s+\d+\s*,\s*\d+$/i', $limit) ? $limit : '';
}

function msg_box_counts($userid)
{
	$userid = (int)$userid;
	$load = function () use ($userid) {
		$res = sql_query("
			SELECT
				(SELECT COUNT(*) FROM messages WHERE receiver = $userid AND location = " . KZ_PM_INBOX . ") AS inbox,
				(SELECT COUNT(*) FROM messages WHERE receiver = $userid AND location = " . KZ_PM_INBOX . " AND unread = 'yes') AS unread,
				(SELECT COUNT(*) FROM messages WHERE sender = $userid AND saved = 'yes' AND location <> " . KZ_PM_ARCHIVE . ") AS outbox,
				(
					(SELECT COUNT(*) FROM messages WHERE receiver = $userid AND location = " . KZ_PM_ARCHIVE . ")
					+
					(SELECT COUNT(*) FROM messages WHERE sender = $userid AND saved = 'yes' AND location = " . KZ_PM_ARCHIVE . " AND receiver <> $userid)
				) AS archive
		") or sqlerr(__FILE__, __LINE__);
		return mysqli_fetch_assoc($res);
	};

	$row = function_exists('tracker_cache_remember')
		? tracker_cache_remember('messages:box-counts:' . $userid, 15, $load)
		: $load();

	return array(
		'inbox' => (int)($row['inbox'] ?? 0),
		'unread' => (int)($row['unread'] ?? 0),
		'outbox' => (int)($row['outbox'] ?? 0),
		'archive' => (int)($row['archive'] ?? 0),
	);
}

function msg_count_box($box, $userid)
{
	$counts = msg_box_counts($userid);
	if ($box === 'out') {
		return $counts['outbox'];
	}
	if ($box === 'arch') {
		return $counts['archive'];
	}
	return $counts['inbox'];
}

function msg_fetch_box($box, $userid, $limit = '')
{
	$userid = (int)$userid;
	$limit = msg_limit_sql($limit);
	if ($box === 'out') {
		$sql = "
			SELECT m.id, m.sender, m.receiver, m.added, m.subject, m.msg, m.unread, m.poster, m.location, m.saved,
			       u.id AS user_id, u.username, u.class, u.avatar
			FROM messages AS m
			LEFT JOIN users AS u ON u.id = m.receiver
			WHERE m.sender = $userid AND m.saved = 'yes' AND m.location <> " . KZ_PM_ARCHIVE . "
			ORDER BY m.id DESC
			$limit
		";
	} elseif ($box === 'arch') {
		$sql = "
			SELECT m.id, m.sender, m.receiver, m.added, m.subject, m.msg, m.unread, m.poster, m.location, m.saved,
			       CASE WHEN m.sender = $userid THEN ru.id ELSE su.id END AS user_id,
			       CASE WHEN m.sender = $userid THEN ru.username ELSE su.username END AS username,
			       CASE WHEN m.sender = $userid THEN ru.class ELSE su.class END AS class,
			       CASE WHEN m.sender = $userid THEN ru.avatar ELSE su.avatar END AS avatar
			FROM (
				SELECT id FROM messages WHERE receiver = $userid AND location = " . KZ_PM_ARCHIVE . "
				UNION
				SELECT id FROM messages WHERE sender = $userid AND saved = 'yes' AND location = " . KZ_PM_ARCHIVE . "
			) AS ids
			INNER JOIN messages AS m ON m.id = ids.id
			LEFT JOIN users AS su ON su.id = m.sender
			LEFT JOIN users AS ru ON ru.id = m.receiver
			ORDER BY m.id DESC
			$limit
		";
	} else {
		$sql = "
			SELECT m.id, m.sender, m.receiver, m.added, m.subject, m.msg, m.unread, m.poster, m.location, m.saved,
			       u.id AS user_id, u.username, u.class, u.avatar
			FROM messages AS m
			LEFT JOIN users AS u ON u.id = m.sender
			WHERE m.receiver = $userid AND m.location = " . KZ_PM_INBOX . "
			ORDER BY m.id DESC
			$limit
		";
	}
	$res = sql_query($sql) or sqlerr(__FILE__, __LINE__);
	$rows = array();
	while ($row = mysqli_fetch_assoc($res)) {
		$rows[] = $row;
	}
	return $rows;
}

function msg_mark_rows_read(array $rows, $userid)
{
	$userid = (int)$userid;
	$ids = array();

	foreach ($rows as $row) {
		if (
			($row['unread'] ?? 'no') === 'yes'
			&& (int)($row['receiver'] ?? 0) === $userid
			&& (int)($row['location'] ?? 0) === KZ_PM_INBOX
		) {
			$ids[] = (int)$row['id'];
		}
	}

	$ids = array_values(array_unique(array_filter($ids)));
	if (!$ids) {
		return 0;
	}

	sql_query("UPDATE messages SET unread = 'no' WHERE id IN (" . implode(',', $ids) . ") AND receiver = $userid AND location = " . KZ_PM_INBOX . " AND unread = 'yes'") or sqlerr(__FILE__, __LINE__);
	return count($ids);
}

function msg_selected_ids($value, $max = 500)
{
	if (!is_array($value)) {
		return array();
	}

	$ids = array_values(array_unique(array_filter(array_map('intval', $value), function ($id) {
		return $id > 0;
	})));
	if ($max > 0 && count($ids) > $max) {
		$ids = array_slice($ids, 0, $max);
	}
	return $ids;
}

function msg_apply_bulk_action(array $ids, $toarch, $userid)
{
	$userid = (int)$userid;
	$ids = msg_selected_ids($ids);

	if (!$ids || $userid <= 0) {
		return 0;
	}

	$in = implode(',', $ids);
	if ($toarch) {
		sql_query("UPDATE messages SET location = " . KZ_PM_ARCHIVE . ", saved = 'yes', unread = 'no' WHERE id IN ($in) AND (receiver = $userid OR sender = $userid)") or sqlerr(__FILE__, __LINE__);
		return count($ids);
	}

	sql_query("DELETE FROM messages WHERE id IN ($in) AND ((receiver = $userid AND sender = $userid) OR (receiver = $userid AND saved = 'no'))") or sqlerr(__FILE__, __LINE__);
	sql_query("UPDATE messages SET saved = 'no' WHERE id IN ($in) AND sender = $userid AND receiver <> $userid") or sqlerr(__FILE__, __LINE__);
	sql_query("UPDATE messages SET location = " . KZ_PM_ARCHIVE . ", unread = 'no' WHERE id IN ($in) AND receiver = $userid") or sqlerr(__FILE__, __LINE__);

	return count($ids);
}

function msg_render_empty($box)
{
	$text = $box === 'arch' ? 'Нет в Архив сообщений!' : 'Ваш личный ящик пуст!';
	return '<div class="bx1"><ul class="men"><li class="b"><span class="bulet"></span>Информация</li><li><div class="pad5x5">' . $text . '</div></li></ul></div>';
}

function msg_render_box(array $rows, $box)
{
	if (!$rows) {
		return msg_render_empty($box);
	}

	$type = $box === 'out' ? 'out' : ($box === 'arch' ? 'arch' : 'in');
	$html = '<form action="/deletemessage.php" method="post" name="deletemessage" id="deletemessage">';
	$html .= '<input name="type" type="hidden" value="' . $type . '"><input name="toarch" type="hidden" value="">';
	$html .= '<div class="bx1_0"><table class="tables1 floatright"><tr><td class="line20"><label for="checkall1">Выделить все сообщения</label><input id="checkall1" class="styled" type="checkbox" onclick="check_all(this);" value=""></td><td><input class="buttonS w150" value="удалить выбранные" type="button" onclick="expSubm()"></td>';
	if ($box !== 'arch') {
		$html .= '<td><input class="buttonS w100" value="в архив" type="button" onclick="exp2Subm()"></td>';
	}
	$html .= '</tr></table></div>';

	foreach ($rows as $row) {
		$id = (int)$row['id'];
		$user_id = (int)($row['user_id'] ?? 0);
		$class = (int)($row['class'] ?? 0);
		$name = (string)($row['username'] ?? '');
		$avatar = msg_h(msg_avatar($row));
		$unread = ($row['unread'] === 'yes' && (int)$row['receiver'] !== (int)$row['sender']) ? " <span class='red b'>не прочитано</span>" : '';
		$open_id = 'sb_' . $id;
		$msg_id = 'sw_' . $id;
		$subject = trim((string)($row['subject'] ?? ''));
		if ($subject === '') {
			$subject = 'Без темы';
		}
		$direction = $box === 'out' ? 'Отправлено' : 'Получено';
		if ($box === 'arch' && (int)$row['sender'] === (int)($GLOBALS['CURUSER']['id'] ?? 0)) {
			$direction = 'Отправлено';
		}

		$html .= '<div class="bx2_0 inb_bx"><img src="' . $avatar . '" class="inb_ava rot180" alt="" onclick="$(\'#' . $open_id . '\').toggle();">';
		$html .= '<div class="inb_sbx">' . msg_user_link($user_id, $name, $class);
		$html .= '<br>' . msg_date($row['added']) . $unread . ', <span onclick="$(\'#' . $open_id . '\').toggle();" class="sba pointer">открыть</span>';
		$html .= '<div id="' . $open_id . '" class="inb_sbx_ms displaynone">';
		$html .= '<div class="mn2 inb_sbx_ms"><dl><dt onclick="$(\'#' . $msg_id . '\').toggle();"><i class="i1 s_msgs"></i> ' . $direction . ' ' . msg_date($row['added']) . ', <span class="b">' . msg_h($subject) . '</span>' . $unread . '</dt>';
		$html .= '<dd><input class="styled" name="cbox[]" type="checkbox" value="' . $id . '"></dd><dd class="sba pointer" onclick="$(\'#' . $msg_id . '\').toggle();">смотреть</dd>';
		if ($box !== 'out' && $user_id > 0) {
			$html .= '<dd><a class="sba" href="/sendmessage.php?receiver=' . $user_id . '&amp;replyto=' . $id . '">ответить</a></dd>';
		}
		$html .= '</dl><div id="' . $msg_id . '" class="hrs2 inb_sbx_ms_tx"><div>' . msg_format($row['msg']) . '</div></div></div></div></div></div>';
	}

	return $html . '</form>';
}

function msg_scripts_and_style()
{
	return "<style type='text/css'>
.inb_bx {padding: 3px; display: block;}
.inb_ava {border: 0; display: block; width: 50px; float:left; cursor: pointer; position: relative;}
.inb_sbx {padding: 0; margin: 0 0 0 55px; display: block; position: relative;}
.inb_sbx_ms {padding: 0; margin: 0; overflow: hidden; position: relative;}
.inb_sbx_ms dl {padding: 0; margin: 0; position: relative; overflow: hidden; display: block; font-weight: bold; line-height:22px;}
.inb_sbx_ms dt {float: left; overflow: hidden; display: block; padding: 3px; margin: 0; cursor: pointer; width: 65%;}
.inb_sbx_ms dd {float: right; overflow: hidden; display: block; padding: 3px; margin: 0;}
.inb_sbx_ms .inb_sbx_ms_tx {padding: 10px; margin: 0 0 0 13px; overflow: hidden; position: relative;}
</style>
<script type=\"text/javascript\">
function check_all(u) {
	$(\"form#deletemessage INPUT[name='cbox[]'][type='checkbox']\").attr('checked', u.checked);
	if (u.checked == true) {
		$(\"form#deletemessage INPUT[name='cbox[]'][type='checkbox']\").closest('.checker > span').addClass('checked');
	} else {
		$(\"form#deletemessage INPUT[name='cbox[]'][type='checkbox']\").closest('.checker > span').removeClass('checked');
	}
}
function expSubm() {
	var fr = document.forms['deletemessage'];
	if (fr && mess_out('Вы действительно хотите удалить сообщения?')) {
		fr.submit();
	}
}
function exp2Subm() {
	var fr = document.forms['deletemessage'];
	if (fr) {
		fr.toarch.value = 1;
		fr.submit();
	}
}
function InsertCode(field, tag) {
	var el = document.getElementById(field);
	if (!el) return false;
	var start = el.selectionStart || 0;
	var end = el.selectionEnd || 0;
	var value = el.value;
	var selected = value.substring(start, end);
	el.value = value.substring(0, start) + '[' + tag + ']' + selected + '[/' + tag + ']' + value.substring(end);
	el.focus();
	return false;
}
function em(field, code) {
	var el = document.getElementById(field);
	if (!el) return false;
	el.value += code;
	el.focus();
	return false;
}
function Prev() {
	var df = document.forms['message'];
	if (df) {
		df.action = '';
		df.submit();
	}
}
</script>";
}

function msg_can_send_to(array $receiver)
{
	global $CURUSER;
	if (($receiver['parked'] ?? 'no') === 'yes') {
		return 'Этот аккаунт припаркован.';
	}
	if (get_user_class() >= UC_MODERATOR) {
		return '';
	}
	$rid = (int)$receiver['id'];
	$uid = (int)$CURUSER['id'];
	if (($receiver['acceptpms'] ?? 'yes') === 'yes') {
		$res = sql_query("SELECT id FROM blocks WHERE userid = $rid AND blockid = $uid LIMIT 1") or sqlerr(__FILE__, __LINE__);
		return mysqli_num_rows($res) ? 'Этот пользователь добавил вас в черный список.' : '';
	}
	if ($receiver['acceptpms'] === 'friends') {
		$res = sql_query("SELECT id FROM friends WHERE userid = $rid AND friendid = $uid LIMIT 1") or sqlerr(__FILE__, __LINE__);
		return mysqli_num_rows($res) ? '' : 'Этот пользователь принимает сообщения только из списка своих друзей.';
	}
	return 'Этот пользователь не принимает сообщения.';
}

?>
