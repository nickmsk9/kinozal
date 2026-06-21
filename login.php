<?

require_once("include/bittorrent.php");
dbconn();

if (!empty($CURUSER)) {
	stderr($tracker_lang['error'], "Вы уже вошли на $SITENAME!");
}

stdhead("Вход");

$returnto = '';
if (!empty($_GET["returnto"])) {
	$returnto = (string)$_GET["returnto"];
}
?>
<div style="width: 100%; text-align: center;"><div style="width: 700px; display: inline-block;text-align: left;">
<div class="pad0x0x5x0"><ul class="lis"><li class="mn"><a href="/login.php">Вход</a></li><li><a href="/signup.php">Регистрация в Кинозал.ТВ</a></li><li><a href="/recover.php">Восстановление пароля</a></li></ul></div>
<?= tracker_login_form_html(array('variant' => 'full', 'returnto' => $returnto)) ?></div></div>
<?
stdfoot();
?>
