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
<form method="post" action="/takelogin.php" name="upt"><input type="hidden" name="touser" value="1"><div class="bx1_0"><div class="pad10x10 floatleft"><table class="tables1"><tr><td class="w150 nw b">Логин</td><td><input type="text" size="35" id="username" name="username" value=""></td></tr><tr><td class="w150 nw b">Пароль</td><td><input type="password" size="35" id="password" name="password" value=""></td></tr><tr><td colspan="2" class="right"><input type="hidden" name="returnto" value="<?= htmlspecialchars_uni($returnto) ?>"><input class="buttonS" type="submit" value=" Войти "></td></tr></table></div><div class="pad10x10" style="margin: 0 5px 0 380px;">Рады Вас приветствовать в Кинозал.ТВ<br>Для входа на сайт введите логин и пароль<br>Не зарегистрированы в Кинозал.ТВ? <a href="/signup.php" class="sba">Присоединиться</a><br></div></div></form></div></div>
<?
stdfoot();
?>
