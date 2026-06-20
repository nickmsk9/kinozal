<?

require_once("include/bittorrent.php");
require_once("include/messages.php");

dbconn(false);
loggedinorreturn();
parked();
msg_ensure_schema();

$box = msg_box_type();
$uid = (int)$CURUSER['id'];
$title = $box === 'out' ? 'Отправленные сообщения' : ($box === 'arch' ? 'Архив сообщений' : 'Принятые сообщения');
$perpage = (int)($CURUSER['postsperpage'] ?? 0);
if ($perpage < 1) {
	$perpage = 25;
}
$perpage = min(100, max(10, $perpage));
$counts = msg_box_counts($uid);
$count = $box === 'out' ? $counts['outbox'] : ($box === 'arch' ? $counts['archive'] : $counts['inbox']);
list($pagertop, $pagerbottom, $limit) = pager($perpage, $count, msg_box_url($box, true));
$rows = msg_fetch_box($box, $uid, $limit);

if ($box === 'in') {
	$marked_read = msg_mark_rows_read($rows, $uid);
	if ($marked_read > 0) {
		$counts['unread'] = max(0, (int)$counts['unread'] - $marked_read);
	}
}

$hide_right_blocks = true;
stdhead($title);
echo msg_scripts_and_style();
?>
<div class="mn_wrap">
	<?= msg_profile_menu($CURUSER, true) ?>
	<div class="mn1_content">
		<div class="bx1 u<?= (int)$CURUSER['class'] ?>"><a href="/userdetails.php?id=<?= (int)$CURUSER['id'] ?>" class="u<?= (int)$CURUSER['class'] ?>"><?= msg_h($CURUSER['username']) ?></a></div>
		<?= msg_tabs($box, $counts) ?>
		<?= $pagertop ?>
		<?= msg_render_box($rows, $box) ?>
		<?= $pagerbottom ?>
	</div>
	<div class="clear"></div>
</div>
<?
stdfoot();

?>
