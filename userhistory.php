<?php

require_once("include/bittorrent.php");

dbconn(false);
loggedinorreturn();

function uh_h($value)
{
	return htmlspecialchars_uni((string)$value);
}

function uh_comment_page_url($torrentid, $commentid)
{
	$torrentid = (int)$torrentid;
	$commentid = (int)$commentid;

	if ($torrentid <= 0 || $commentid <= 0) {
		return '';
	}

	$res = sql_query("SELECT COUNT(*) FROM comments WHERE torrent = $torrentid AND id > $commentid") or sqlerr(__FILE__, __LINE__);
	$row = mysqli_fetch_row($res);
	$count = (int)($row[0] ?? 0);
	$page = (int)floor($count / 20);

	return 'details.php?id=' . $torrentid . '&amp;tocomm=1' . ($page > 0 ? '&amp;page=' . $page : '') . '#cm' . $commentid;
}

$userid = (int)($_GET['id'] ?? ($_GET['userid'] ?? 0));

if (!is_valid_id($userid)) {
	stderr($tracker_lang['error'], $tracker_lang['invalid_id'] ?? 'Invalid ID');
}

if (get_user_class() < UC_POWER_USER || ((int)$CURUSER['id'] !== $userid && get_user_class() < UC_MODERATOR)) {
	stderr($tracker_lang['error'], 'Нет доступа');
}

$res = sql_query("SELECT * FROM users WHERE id = $userid LIMIT 1") or sqlerr(__FILE__, __LINE__);
$user = mysqli_fetch_assoc($res) or stderr($tracker_lang['error'], $tracker_lang['invalid_id'] ?? 'Invalid ID');

$book = (int)($_GET['tid'] ?? 0);
if ($book < 0 || $book > 2) {
	$book = 0;
}

$bookLabels = array('Книга первая', 'Книга вторая', 'Книга третья');
$perBook = 25;
$offset = $book * $perBook;

$res = sql_query("SELECT COUNT(*) FROM comments WHERE user = $userid") or sqlerr(__FILE__, __LINE__);
$row = mysqli_fetch_row($res);
$commentCount = (int)($row[0] ?? 0);

$comments = array();
if ($commentCount > $offset) {
	$res = sql_query("
		SELECT
			c.id,
			c.torrent AS torrentid,
			c.added,
			c.text,
			t.name AS torrent_name
		FROM comments AS c
		LEFT JOIN torrents AS t ON t.id = c.torrent
		WHERE c.user = $userid
		ORDER BY c.id DESC
		LIMIT " . (int)$offset . ", " . (int)$perBook
	) or sqlerr(__FILE__, __LINE__);

	while ($row = mysqli_fetch_assoc($res)) {
		$comments[] = $row;
	}
}

$profileClass = 'u' . (int)($user['class'] ?? UC_USER);
$hide_right_blocks = true;

stdhead('История комментариев');
?>

<div class="mn_wrap">
	<div class="mn1_menu">
		<?= function_exists('profile_menu_html') ? profile_menu_html($user, $CURUSER) : '' ?>
	</div>
	<div class="mn1_content">
		<div class="bx1 <?= $profileClass ?>">
			<a href="/userdetails.php?id=<?= $userid ?>" class="<?= $profileClass ?>"><?= uh_h($user['username']) ?></a>
		</div>

		<div class="bx1 justify n">
			<b class="<?= $profileClass ?>">История комментариев</b>
			- В таблице представлены комментарии к раздачам, которые были добавлены. За интересные и объективные комментарии выставляется статус риторика, а также кинооператорами начисляются бонусы.
		</div>

		<div class="pad0x0x5x0">
			<ul class="lis">
				<?php foreach ($bookLabels as $i => $label) { ?>
					<li<?= $book === $i ? ' class="mn"' : '' ?>><a href="/userhistory.php?id=<?= $userid ?>&amp;tid=<?= $i ?>"><?= uh_h($label) ?></a></li>
				<?php } ?>
			</ul>
		</div>

		<?php if (empty($comments)) { ?>
			<div class="bx1 b">Нет комментариев к раздачам в этой книге</div>
		<?php } else { ?>
			<?php foreach ($comments as $comment) {
				$commentid = (int)$comment['id'];
				$torrentid = (int)$comment['torrentid'];
				$torrentName = trim((string)($comment['torrent_name'] ?? ''));
				$commentUrl = uh_comment_page_url($torrentid, $commentid);
				$added = uh_h($comment['added']);
				if (!empty($comment['added'])) {
					$added .= ' GMT (' . uh_h(get_elapsed_time(sql_timestamp_to_unix_timestamp($comment['added']))) . ' назад)';
				}
			?>
				<div class="bx2_0">
					<div class="pad5x5 small">
						<b><?= $added ?></b>
						&nbsp;---&nbsp;<b>Торрент:</b>
						<?php if ($torrentid > 0 && $torrentName !== '') { ?>
							<a href="/details.php?id=<?= $torrentid ?>&amp;tocomm=1" class="sba"><?= uh_h($torrentName) ?></a>
						<?php } else { ?>
							[Удален]
						<?php } ?>
						&nbsp;---&nbsp;<b>Комментарий:</b>
						<?php if ($commentUrl !== '') { ?>
							#<a href="/<?= $commentUrl ?>" class="sba"><?= $commentid ?></a>
						<?php } else { ?>
							#<?= $commentid ?>
						<?php } ?>
					</div>
					<div class="mn2 cmet_bx">
						<div class="cmet_sbx" style="margin-left:0;">
							<div class="tx" id="cm<?= $commentid ?>"><?= format_comment($comment['text']) ?></div>
						</div>
						<div class="clr"></div>
					</div>
				</div>
			<?php } ?>
		<?php } ?>
	</div>
	<div class="clr"></div>
</div>
<?php
stdfoot();

?>
