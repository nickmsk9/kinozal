<?php

/*
// +--------------------------------------------------------------------------+
// | Project:    TBDevYSE - TBDev Yuna Scatari Edition                        |
// +--------------------------------------------------------------------------+
// |                                               Do not remove above lines! |
// +--------------------------------------------------------------------------+
*/

require_once("include/bittorrent.php");

dbconn(false);

$action = $_GET['action'] ?? '';

function comment_h($value)
{
	return htmlspecialchars_uni((string)$value);
}

function comment_back_url($torrentid, $commentid = 0)
{
	$torrentid = (int)$torrentid;
	$commentid = (int)$commentid;
	if ($commentid > 0) {
		return "details.php?id=$torrentid&page=0#cm$commentid";
	}
	return "details.php?id=$torrentid#startcomments";
}

function comment_safe_return($url, $fallback)
{
	$url = (string)$url;
	if ($url === '' || preg_match('/[\r\n]/', $url) || preg_match('#^[a-z]+://#i', $url)) {
		return $fallback;
	}
	return $url;
}

function comment_form($title, $action_url, $torrentid, $text, $submit, $returnto = '')
{
	$hidden_return = $returnto !== '' ? '<input type="hidden" name="returnto" value="' . comment_h($returnto) . '">' : '';
	stdhead($title);
	print '<div class="bx1">';
	print '<form method="post" name="comment" action="' . comment_h($action_url) . '">';
	print '<input type="hidden" name="tid" value="' . (int)$torrentid . '">' . $hidden_return;
	print '<div class="pad5x5"><b>' . comment_h($title) . '</b></div>';
	print '<div class="pad10x10"><textarea id="text" name="text" cols="70" rows="8" class="w98p">' . comment_h($text) . '</textarea></div>';
	print '<div class="pad5x5"><input type="submit" value="' . comment_h($submit) . '" class="buts"></div>';
	print '</form></div>';
	stdfoot();
	exit;
}

if ($action === '' && isset($_GET['id'])) {
	$torrentid = (int)$_GET['id'];
	$page = max(0, (int)($_GET['page'] ?? 0));
	if (is_valid_id($torrentid)) {
		header("Location: details.php?id=$torrentid&page=$page#startcomments");
		exit;
	}
}

loggedinorreturn();
parked();

if ($action === 'add') {
	if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
		$torrentid = (int)($_GET['tid'] ?? 0);
		if (!is_valid_id($torrentid)) {
			stderr($tracker_lang['error'], $tracker_lang['invalid_id']);
		}
		$res = sql_query("SELECT name FROM torrents WHERE id = $torrentid") or sqlerr(__FILE__, __LINE__);
		$torrent = mysqli_fetch_assoc($res);
		if (!$torrent) {
			stderr($tracker_lang['error'], $tracker_lang['no_torrent_with_such_id']);
		}
		comment_form('Добавление комментария к "' . $torrent['name'] . '"', 'comment.php?action=add', $torrentid, '', 'Добавить');
	}

	$torrentid = (int)($_POST['tid'] ?? 0);
	if (!is_valid_id($torrentid)) {
		stderr($tracker_lang['error'], $tracker_lang['invalid_id']);
	}

	$res = sql_query("SELECT name FROM torrents WHERE id = $torrentid") or sqlerr(__FILE__, __LINE__);
	$torrent = mysqli_fetch_assoc($res);
	if (!$torrent) {
		stderr($tracker_lang['error'], $tracker_lang['no_torrent_with_such_id']);
	}

	$text = trim((string)($_POST['text'] ?? ''));
	if ($text === '') {
		stderr($tracker_lang['error'], $tracker_lang['comment_cant_be_empty']);
	}

	sql_query("
		INSERT INTO comments (user, torrent, added, text, ori_text, ip)
		VALUES (" . (int)$CURUSER['id'] . ", $torrentid, " . sqlesc(get_date_time()) . ", " . sqlesc($text) . ", " . sqlesc($text) . ", " . sqlesc(getip()) . ")
	") or sqlerr(__FILE__, __LINE__);

	global $link;
	$newid = mysqli_insert_id($link);
	sql_query("UPDATE torrents SET comments = comments + 1 WHERE id = $torrentid") or sqlerr(__FILE__, __LINE__);

	header('Location: ' . comment_back_url($torrentid, $newid));
	exit;
}

if ($action === 'quote') {
	$commentid = (int)($_GET['cid'] ?? 0);
	if (!is_valid_id($commentid)) {
		stderr($tracker_lang['error'], $tracker_lang['invalid_id']);
	}

	$res = sql_query("
		SELECT c.text, t.name, t.id AS tid, u.username
		FROM comments AS c
		LEFT JOIN torrents AS t ON c.torrent = t.id
		LEFT JOIN users AS u ON c.user = u.id
		WHERE c.id = $commentid
		LIMIT 1
	") or sqlerr(__FILE__, __LINE__);
	$arr = mysqli_fetch_assoc($res);
	if (!$arr) {
		stderr($tracker_lang['error'], $tracker_lang['invalid_id']);
	}

	$text = '[quote=' . ($arr['username'] ?: 'unknown') . ']' . $arr['text'] . "[/quote]\n";
	comment_form('Добавление комментария к "' . $arr['name'] . '"', 'comment.php?action=add', (int)$arr['tid'], $text, 'Добавить');
}

if ($action === 'edit') {
	$commentid = (int)($_GET['cid'] ?? 0);
	if (!is_valid_id($commentid)) {
		stderr($tracker_lang['error'], $tracker_lang['invalid_id']);
	}

	$res = sql_query("
		SELECT c.*, t.name, t.id AS tid
		FROM comments AS c
		LEFT JOIN torrents AS t ON c.torrent = t.id
		WHERE c.id = $commentid
		LIMIT 1
	") or sqlerr(__FILE__, __LINE__);
	$arr = mysqli_fetch_assoc($res);
	if (!$arr) {
		stderr($tracker_lang['error'], $tracker_lang['invalid_id']);
	}

	if ((int)$arr['user'] !== (int)$CURUSER['id'] && get_user_class() < UC_MODERATOR) {
		stderr($tracker_lang['error'], $tracker_lang['access_denied']);
	}

	if ($_SERVER['REQUEST_METHOD'] === 'POST') {
		$text = trim((string)($_POST['text'] ?? ''));
		if ($text === '') {
			stderr($tracker_lang['error'], $tracker_lang['comment_cant_be_empty']);
		}

		sql_query("
			UPDATE comments
			SET text = " . sqlesc($text) . ", editedat = " . sqlesc(get_date_time()) . ", editedby = " . (int)$CURUSER['id'] . "
			WHERE id = $commentid
		") or sqlerr(__FILE__, __LINE__);

		$returnto = comment_safe_return($_POST['returnto'] ?? '', comment_back_url((int)$arr['tid'], $commentid));
		header("Location: $returnto");
		exit;
	}

	comment_form(
		'Редактирование комментария к "' . $arr['name'] . '"',
		'comment.php?action=edit&amp;cid=' . $commentid,
		(int)$arr['tid'],
		$arr['text'],
		'Отредактировать',
		comment_back_url((int)$arr['tid'], $commentid)
	);
}

if ($action === 'delete') {
	if (get_user_class() < UC_MODERATOR) {
		stderr($tracker_lang['error'], $tracker_lang['access_denied']);
	}

	$commentid = (int)($_GET['cid'] ?? 0);
	if (!is_valid_id($commentid)) {
		stderr($tracker_lang['error'], $tracker_lang['invalid_id']);
	}

	$res = sql_query("SELECT torrent FROM comments WHERE id = $commentid LIMIT 1") or sqlerr(__FILE__, __LINE__);
	$arr = mysqli_fetch_assoc($res);
	if (!$arr) {
		stderr($tracker_lang['error'], $tracker_lang['invalid_id']);
	}

	global $link;
	sql_query("DELETE FROM comments WHERE id = $commentid") or sqlerr(__FILE__, __LINE__);
	$deleted = mysqli_affected_rows($link);
	sql_query("DELETE FROM comments_parsed WHERE cid = $commentid");

	if ($deleted > 0) {
		sql_query("UPDATE torrents SET comments = IF(comments > 0, comments - 1, 0) WHERE id = " . (int)$arr['torrent']) or sqlerr(__FILE__, __LINE__);
	}

	header('Location: ' . comment_back_url((int)$arr['torrent']));
	exit;
}

if ($action === 'vieworiginal') {
	if (get_user_class() < UC_MODERATOR) {
		stderr($tracker_lang['error'], $tracker_lang['access_denied']);
	}

	$commentid = (int)($_GET['cid'] ?? 0);
	if (!is_valid_id($commentid)) {
		stderr($tracker_lang['error'], $tracker_lang['invalid_id']);
	}

	$res = sql_query("
		SELECT c.ori_text, c.text, t.name, t.id AS tid
		FROM comments AS c
		LEFT JOIN torrents AS t ON t.id = c.torrent
		WHERE c.id = $commentid
		LIMIT 1
	") or sqlerr(__FILE__, __LINE__);
	$arr = mysqli_fetch_assoc($res);
	if (!$arr) {
		stderr($tracker_lang['error'], $tracker_lang['invalid_id']);
	}

	stdhead('Оригинал комментария');
	print '<div class="bx1"><div class="pad5x5"><b>' . comment_h($arr['name']) . '</b></div>';
	print '<div class="pad10x10"><pre>' . comment_h($arr['ori_text'] !== '' ? $arr['ori_text'] : $arr['text']) . '</pre></div>';
	print '<div class="pad5x5"><a href="' . comment_h(comment_back_url((int)$arr['tid'], $commentid)) . '" class="sba">Назад</a></div></div>';
	stdfoot();
	exit;
}

if ($action === 'check' || $action === 'checkoff') {
	$tid = (int)($_GET['tid'] ?? 0);
	if (!is_valid_id($tid)) {
		stderr($tracker_lang['error'], "Неверный идентификатор $tid.");
	}

	$res = sql_query("SELECT COUNT(*) FROM checkcomm WHERE checkid = $tid AND userid = " . (int)$CURUSER['id'] . " AND torrent = 1") or sqlerr(__FILE__, __LINE__);
	$exists = mysqli_fetch_row($res);

	if ($action === 'check') {
		if ((int)$exists[0] > 0) {
			stderr($tracker_lang['error'], '<p>Вы уже подписаны на комментарии к этому торренту.</p><a href="details.php?id=' . $tid . '#startcomments">Назад</a>');
		}
		sql_query("INSERT INTO checkcomm (checkid, userid, torrent) VALUES ($tid, " . (int)$CURUSER['id'] . ", 1)") or sqlerr(__FILE__, __LINE__);
		stderr($tracker_lang['success'], '<p>Теперь вы следите за комментариями к этому торренту.</p><a href="details.php?id=' . $tid . '#startcomments">Назад</a>');
	}

	sql_query("DELETE FROM checkcomm WHERE checkid = $tid AND userid = " . (int)$CURUSER['id'] . " AND torrent = 1") or sqlerr(__FILE__, __LINE__);
	stderr($tracker_lang['success'], '<p>Теперь вы не следите за комментариями к этому торренту.</p><a href="details.php?id=' . $tid . '#startcomments">Назад</a>');
}

stderr($tracker_lang['error'], $tracker_lang['invalid_id']);

?>
