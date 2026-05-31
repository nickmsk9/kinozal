<?

require_once("include/bittorrent.php");
require_once("include/kz_messages.php");

dbconn(false);
loggedinorreturn();
parked();

$receiver_id = isset($_GET['receiver']) ? (int)$_GET['receiver'] : (int)($_POST['receiver'] ?? 0);
if (!is_valid_id($receiver_id)) {
	stderr($tracker_lang['error'], 'Неверный ID получателя.');
}

$res = sql_query("SELECT * FROM users WHERE id = $receiver_id LIMIT 1") or sqlerr(__FILE__, __LINE__);
$receiver = mysqli_fetch_assoc($res);
if (!$receiver) {
	stderr($tracker_lang['error'], 'Пользователь не найден.');
}

$subject = '';
$body = '';
$replyto = isset($_GET['replyto']) ? (int)$_GET['replyto'] : 0;
if ($replyto > 0) {
	$res = sql_query("SELECT * FROM messages WHERE id = $replyto AND receiver = " . (int)$CURUSER['id'] . " LIMIT 1") or sqlerr(__FILE__, __LINE__);
	$orig = mysqli_fetch_assoc($res);
	if ($orig) {
		$subject = preg_match('/^Re:/i', (string)$orig['subject']) ? (string)$orig['subject'] : 'Re: ' . (string)$orig['subject'];
		$body = "\n\n[quote]" . (string)$orig['msg'] . "[/quote]";
	}
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['prev'])) {
	$subject = trim((string)($_POST['subject'] ?? ''));
	$body = trim((string)($_POST['msg'] ?? ''));
}

$hash = kz_msg_h($CURUSER['hash4u'] ?? ($CURUSER['logout_hash'] ?? ''));
$returnto = kz_msg_h($_GET['returnto'] ?? ($_POST['returnto'] ?? ('/userdetails.php?id=' . $receiver_id)));
$hide_right_blocks = true;
stdhead('Отправить сообщение');
echo kz_msg_scripts_and_style();
?>
<div class="mn_wrap">
	<?= kz_msg_profile_menu($receiver, false) ?>
	<div class="mn1_content">
		<div class="bx1 u<?= (int)$receiver['class'] ?>"><a href="/userdetails.php?id=<?= $receiver_id ?>" class="u<?= (int)$receiver['class'] ?>"><?= kz_msg_h($receiver['username']) ?></a></div>
		<div class="bx1 justify"><b class="u<?= (int)$receiver['class'] ?>">Отправить сообщение</b> - Пожалуйста, будьте предельно вежливы и учтивы. Запрещено рассылать рекламу!</div>
		<?php if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['prev']) && $body !== '') { ?>
			<div class="bx1"><ul class="men"><li class="tp2 b">Предварительный просмотр</li><li class="pad5x5"><?= kz_msg_format($body) ?></li></ul></div>
		<?php } ?>
		<form method="post" action="/takemessage.php" name="message">
			<input type="hidden" name="hash4u" value="<?= $hash ?>">
			<input type="hidden" name="receiver" value="<?= $receiver_id ?>">
			<input type="hidden" name="returnto" value="<?= $returnto ?>">
			<input type="hidden" name="replyto" value="<?= $replyto ?>">
			<input type="hidden" name="prev" value="prev">
			<div class="bx1_0">
				<table class="tables1 w100p">
					<tr><td>Сообщение для: <?= kz_msg_user_link($receiver_id, $receiver['username'], (int)$receiver['class']) ?></td><td class="right"><label><input class="styled" type="checkbox" id="save" name="save" value="yes" checked> Сохранить в отправленных</label></td></tr>
					<tr><td colspan="2">Тема: <input type="text" name="subject" class="w98p" value="<?= kz_msg_h($subject) ?>"></td></tr>
					<tr><td colspan="2">
						<div class="cmet_e_but"><ul>
							<li><input class="buttonS" type="button" value="b" style="font-weight:bold;" onclick="InsertCode('msg','b')"></li>
							<li><input class="buttonS" type="button" value="i" style="font-style:italic;" onclick="InsertCode('msg','i')"></li>
							<li><input class="buttonS" type="button" value="u" style="text-decoration:underline;" onclick="InsertCode('msg','u')"></li>
							<li><input class="buttonS" type="button" value="quote" onclick="InsertCode('msg','quote')"></li>
							<li><input class="buttonS" type="button" value="url" onclick="InsertCode('msg','url')"></li>
							<li><input class="buttonS" type="button" value="img" onclick="InsertCode('msg','img')"></li>
						</ul><div class="clr"></div></div>
						<div class="cmet_e_inp"><textarea id="msg" name="msg" cols="70" rows="10" class="w98p"><?= kz_msg_h($body) ?></textarea></div>
						<div class="clr"></div>
						<div class="cmet_e_inp"><input class="buttonS" type="submit" value="Отправить"> <input class="buttonS" type="button" value="Предв. просмотр" onclick="Prev();"></div>
					</td></tr>
				</table>
			</div>
		</form>
	</div>
	<div class="clear"></div>
</div>
<?
stdfoot();

?>
