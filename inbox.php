<?

require_once("include/bittorrent.php");
require_once("include/messages.php");

dbconn(false);
loggedinorreturn();
parked();

$box = msg_box_type();
$title = $box === 'out' ? 'Отправленные сообщения' : ($box === 'arch' ? 'Архив сообщений' : 'Принятые сообщения');
$rows = msg_fetch_box($box, (int)$CURUSER['id']);

if ($box === 'in') {
	sql_query("UPDATE messages SET unread = 'no' WHERE receiver = " . (int)$CURUSER['id'] . " AND location = " . KZ_PM_INBOX) or sqlerr(__FILE__, __LINE__);
}

$hide_right_blocks = true;
stdhead($title);
echo msg_scripts_and_style();
?>
<div class="mn_wrap">
	<?= msg_profile_menu($CURUSER, true) ?>
	<div class="mn1_content">
		<div class="bx1 u<?= (int)$CURUSER['class'] ?>"><a href="/userdetails.php?id=<?= (int)$CURUSER['id'] ?>" class="u<?= (int)$CURUSER['class'] ?>"><?= msg_h($CURUSER['username']) ?></a></div>
		<?= msg_tabs($box) ?>
		<?= msg_render_box($rows, $box) ?>
	</div>
	<div class="clear"></div>
</div>
<?
stdfoot();

?>
