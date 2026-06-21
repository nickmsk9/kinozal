<?

require_once("include/bittorrent.php");

dbconn(false);
loggedinorreturn();

function my_h($value) {
	return htmlspecialchars_uni((string)$value);
}

function my_menu($user) {
	$id = (int)$user["id"];
	$name = my_h($user["username"]);
	$avatar = !empty($user["avatar"]) ? my_h($user["avatar"]) : "/pic/default_avatar.gif";
	$reputation = function_exists('reputation_value') ? reputation_value($user) : (isset($user["simpaty"]) ? max(0, (int)$user["simpaty"]) : 0);
	$bonus = function_exists('pay_user_votes_from_array') ? number_format(pay_user_votes_from_array($user), 0, '.', ' ') : (isset($user["bonus"]) ? number_format((float)$user["bonus"], 0, '.', ' ') : 0);

	return '
	<div class="mn1_menu">
		<ul class="men w200">
			<li class="img"><a href="/userdetails.php?id=' . $id . '"><img src="' . $avatar . '" class="p200" alt=""></a></li>
			<li class="tp">Меню пользователя</li>
			<li><span class="bulet"></span><a href="/inbox.php">Личные сообщения</a></li>
			<li><span class="bulet"></span><a href="/userdetails.php?id=' . $id . '">Мой профиль</a></li>
			<li><span class="bulet"></span><a href="/my.php">Редактировать профиль</a></li>
			<li><span class="bulet"></span><a href="/mygroups.php">Мои группы</a></li>
			<li><span class="bulet"></span><a href="/friends.php?id=' . $id . '">Мой список друзей</a></li>
			<li class="sf"><span class="bulet"></span><a href="/mytorrents.php?id=' . $id . '">Мои раздачи</a></li>
			<li class="tp">Репутация<span class="floatright"><a href="/pay_mode_b.php?userid=' . $id . '" title="Понизить репутацию"><img border="0" src="/pic/minus.gif" alt=""></a> <b>' . $reputation . '</b> <a href="/pay_mode_b.php?userid=' . $id . '" title="Повысить репутацию"><img border="0" src="/pic/plus.gif" alt=""></a></span></li>
			<li><span class="bulet"></span><a href="/user_reputation.php?id=' . $id . '">Полученные отзывы</a></li>
			<li><span class="bulet"></span><a href="/user_reputation.php?id=' . $id . '&amp;type=2">Отданные отзывы</a></li>
			<li class="tp">Закладки</li>
			<li><span class="bulet"></span><a href="/bookmarks.php?type=1">Раздачи</a></li>
			<li><span class="bulet"></span><a href="/bookmarks.php?type=2">Группы</a></li>
			<li><span class="bulet"></span><a href="/bookmarks.php?type=3">Пользователи</a></li>
			<li class="sf"><span class="bulet"></span><a href="/bookmarks.php?type=4">Персоны</a></li>
			<li class="tp">История</li>
			<li><span class="bulet"></span><a href="/hytorrents.php?id=' . $id . '">Скачанного</a></li>
			<li><span class="bulet"></span><a href="/userhistory.php?id=' . $id . '">Комментариев</a></li>
			<li><span class="bulet"></span><a href="/uservotes.php?id=' . $id . '">Голосований</a></li>
			<li class="tp">Голоса<span class="floatright b">' . $bonus . '</span></li>
			<li><span class="bulet"></span><a href="/pay.php">Получить голоса</a></li>
			<li><span class="bulet"></span><a href="/pay_mode.php">Управление голосами</a></li>
				<li><span class="bulet"></span><a href="/pay_wishes.php">Пожелания</a></li>
				<li><span class="bulet"></span><a href="/pay_mode.php">Обнулить счетчик скачиваний</a></li>
				<li><span class="bulet"></span><a href="/pay_help.php">Техподдержка</a></li>
		</ul>
	</div>';
}

function my_select_range($name, $min, $max, $selected, $default) {
	$html = '<select name="' . $name . '" class="styled"><option value="00">' . $default . '</option>';
	for ($i = $min; $i <= $max; $i++) {
		$value = sprintf('%02d', $i);
		$html .= '<option value="' . $value . '"' . ((string)$selected === (string)$value ? ' selected' : '') . '>' . $value . '</option>';
	}
	return $html . '</select>';
}

function my_country_select($selected) {
	$html = '<select name="country" id="country" class="styled"><option value="0">Выберите страну</option>';
	$res = sql_query("SELECT id, name FROM countries ORDER BY name ASC") or sqlerr(__FILE__, __LINE__);
	while ($row = mysqli_fetch_assoc($res)) {
		$id = (int)$row["id"];
		$html .= '<option value="' . $id . '"' . ((int)$selected === $id ? ' selected' : '') . '>' . my_h($row["name"]) . '</option>';
	}
	return $html . '</select>';
}

function my_theme_select($selected) {
	$html = '<form method="post" action="/changetheme.php" class="inlineform" style="display:inline;margin:0;padding:0;">';
	$html .= '<input type="hidden" name="hash4u" value="' . my_h(tracker_user_form_token()) . '">';
	$html .= '<input type="hidden" name="returnto" value="' . my_h($_SERVER['REQUEST_URI'] ?? '/my.php') . '">';
	$html .= '<select name="theme" class="styled w200" onchange="this.form.submit();">';
	$selectedResolved = theme_resolve_name($selected);
	foreach (get_themes() as $theme) {
		$label = theme_display_name($theme);
		$value = $theme;
		$isSelected = (theme_resolve_name($theme) === $selectedResolved);
		$html .= '<option value="' . my_h($value) . '"' . ($isSelected ? ' selected' : '') . '>' . my_h($label) . '</option>';
	}
	return $html . '</select><noscript><input type="submit" class="buttonS" value="Сменить"></noscript></form>';
}

$id = (int)$CURUSER["id"];
$profile_name = my_h($CURUSER["username"]);
$profile_class_css = 'u' . (int)($CURUSER["class"] ?? UC_USER);
$avatar = !empty($CURUSER["avatar"]) ? my_h($CURUSER["avatar"]) : "/pic/default_avatar.gif";
$birthday = (!empty($CURUSER["birthday"]) && $CURUSER["birthday"] !== "0000-00-00") ? $CURUSER["birthday"] : "1990-01-01";
list($b_year, $b_month, $b_day) = explode('-', date('Y-m-d', strtotime($birthday)));

$profile_passkey = tracker_valid_passkey($CURUSER["passkey"] ?? '')
	? my_h($CURUSER["passkey"])
	: "Новые ключи выдаются при скачивании .torrent и не хранятся в открытом виде";
$form_hash = my_h($CURUSER['hash4u'] ?? tracker_user_form_token());
$hide_right_blocks = true;
stdhead($tracker_lang['my_my'] ?? 'Мой профиль');

if (isset($_GET["edited"])) {
	print("<div class=\"bx1 center b\">Профиль обновлен</div>\n");
}
if (isset($_GET["mailsent"])) {
	print("<div class=\"bx1 center b\">Письмо для подтверждения отправлено</div>\n");
}
?>
	<div class="mn_wrap">
		<?= my_menu($CURUSER) ?>
		<div class="mn1_content">
		<div class="bx1 <?= $profile_class_css ?>"><a href="/userdetails.php?id=<?= $id ?>" class="<?= $profile_class_css ?>"><?= $profile_name ?></a></div>

		<form name="myc" method="post" action="/takeprofedit.php?act=1">
			<input type="hidden" name="hash4u" value="<?= $form_hash ?>">
			<div class="bx1_0">
				<div class="pad5x5 u2"><span class="bulet"></span>Основные настройки</div>
				<table class="tables4 w100p">
					<tr><td class="w120 right nw">Место жительства</td><td><span class="sw200"><?= my_country_select($CURUSER["country"] ?? 0) ?></span></td></tr>
					<tr><td class="right">Пол</td><td class="line20"><input class="styled" type="radio" name="gender"<?= ($CURUSER["gender"] == "1" ? " checked" : "") ?> value="1" id="Male"><label for="Male" class="label_lf">Мужской</label> <input class="styled" type="radio" name="gender"<?= ($CURUSER["gender"] == "2" ? " checked" : "") ?> value="2" id="Female"><label for="Female" class="label_lf">Женский</label></td></tr>
					<tr><td class="right">Дата рождения</td><td><?= my_select_range('bday_day', 1, 31, $b_day, 'число') ?> <select name="bday_month" class="styled"><option value="00">месяц</option><? $months = array("01"=>"января","02"=>"февраля","03"=>"марта","04"=>"апреля","05"=>"мая","06"=>"июня","07"=>"июля","08"=>"августа","09"=>"сентября","10"=>"октября","11"=>"ноября","12"=>"декабря"); foreach ($months as $num => $name) { ?><option value="<?= $num ?>"<?= ($b_month == $num ? " selected" : "") ?>><?= $name ?></option><? } ?></select> <?= my_select_range('bday_year', 1930, (int)date('Y') - 13, $b_year, 'год') ?></td></tr>
					<tr><td class="right nw">Временная зона</td><td><select name="timezone" class="styled"><option value="0" selected>00:00</option></select> Сейчас на сервере: <b><?= date('d.m.Y H:i') ?></b> ( Московское время )</td></tr>
					<tr><td class="right">Ваши города</td><td><span class="w200"><input type="text" name="sr_citys" size="28" value="<?= my_h($CURUSER["city"] ?? "") ?>"></span> Дюссельдорф, Москва</td></tr>
					<tr><td class="right">Любимый фильм</td><td><input type="text" name="sr_film" size="28" value="<?= my_h($CURUSER["favorite_movie"] ?? "") ?>"> Собака на сене</td></tr>
					<tr><td class="right">Любимые персоны</td><td><input type="text" name="sr_persons" size="28" value="<?= my_h($CURUSER["favorite_persons"] ?? "") ?>"> Александр Абдулов, Джеки Чан</td></tr>
					<tr><td class="right nw">Пароль</td><td><ul class="men"><li><input type="password" name="psw" size="28" value=""> Требуется для смены данных</li></ul></td></tr>
					<tr><td colspan="2"><input type="submit" value="Изменить" class="buttonS w200"></td></tr>
				</table>
			</div>
		</form>

		<form name="mycphpto" method="post" action="/takeprofedit.php?act=2">
			<input type="hidden" name="hash4u" value="<?= $form_hash ?>">
			<div class="bx1_0">
				<div class="pad5x5 u2"><span class="bulet"></span>Ваша фотография</div>
				<div class="w200 nw floatleft"><img src="<?= $avatar ?>" class="w200 block pad5x5" alt=""></div>
				<div style="padding: 0 20px 0 220px;">
					<div class="justify">Рекомендуем использовать аватар размером 200x200, предварительно разместив его <a href="https://forum.kinozal.tv/showthread.php?t=78697" target="blank" class="sba">здесь</a>. Уменьшайте аватар по горизонтали до 200 пикселей, высота не выше 350 пикселей.</div>
					<div style="padding: 20px 0 20px 0;"><input type="text" name="avatar" class="w90p" value="<?= my_h($CURUSER["avatar"] ?? "") ?>"><div style="padding: 5px 0 5px 0;">Пример: https://i115.fastpic.org/a3d526.jpg ( Обратите внимание, HTTPS ссылка на картинку .[JPG/JPEG] )</div></div>
					<div><input type="submit" value="Обновить фотографию" class="buttonS w200"></div>
				</div>
			</div>
		</form>

		<form name="mypassk" method="post" action="/takeprofedit.php?act=10" onsubmit="return confirm('Внимание, после смены пасскей Вам необходимо будет заново скачать все активные торренты!')">
			<input type="hidden" name="hash4u" value="<?= $form_hash ?>">
			<div class="bx1"><table class="tables4">
				<tr><td class="w100 right nw">Ваш пасскей:</td><td class="b"><?= $profile_passkey ?></td></tr>
				<tr><td class="right nw">Пароль</td><td><ul class="men"><li><input type="password" name="psw" size="28" value=""> Требуется для смены данных</li></ul></td></tr>
				<tr><td colspan="2"><input type="submit" value="Сменить пасскей" class="buttonS w200"></td></tr>
			</table></div>
		</form>

		<div class="bx1"><div class="w200 nw floatleft"><span class="sw200"><?= my_theme_select($CURUSER["theme"] ?? "") ?></span></div><div style="padding: 0 0 0 220px;"><b class="u2">Стиль отображения</b> - Вы можете выбрать один из стилей, который более подходит Вам</div></div>
		<div class="bx1"><div class="w200 nw floatleft"><input type="button" value="Редактировать информацию" onclick="document.location.href='my_info.php'" class="buttonS w200"></div><div style="padding: 0 0 0 220px;"><b class="u2">Информация пользователя</b> - Вы можете разместить интересную и познавательную информацию здесь</div></div>

		<form name="mypark" method="post" action="/takeprofedit.php?act=11">
			<input type="hidden" name="hash4u" value="<?= $form_hash ?>">
			<div class="bx1"><div class="pad5x5 u2"><span class="bulet"></span>Припарковать профиль</div><table class="tables4">
				<tr><td class="w120 right nw">Профиль припаркован</td><td class="line20"><input class="styled" type="radio" id="prk1" name="parked" value="yes"<?= ($CURUSER["parked"] == "yes" ? " checked" : "") ?>><label for="prk1" class="label_lf">Да</label> <input class="styled" type="radio" id="prk2" name="parked" value="no"<?= ($CURUSER["parked"] == "no" ? " checked" : "") ?>><label for="prk2" class="label_lf">Нет</label></td></tr>
				<tr><td class="right nw">Пароль</td><td><ul class="men"><li><input type="password" name="psw" size="28" value=""> Требуется для смены данных</li></ul></td></tr>
				<tr><td colspan="2"><input type="submit" value="Изменить" class="buttonS w200"></td></tr>
			</table></div>
		</form>

		<form name="mypass" method="post" action="/takeprofedit.php?act=12">
			<input type="hidden" name="hash4u" value="<?= $form_hash ?>">
			<div class="bx1"><div class="pad5x5 u2"><span class="bulet"></span>Сменить пароль</div><table class="tables4">
				<tr><td class="w120 right nw">Старый пароль</td><td><input type="password" name="pass" value="" size="28" autocomplete="off"></td><td><a href="/recover.php" class="sba">Забыли пароль ?</a></td></tr>
				<tr><td class="w120 right nw">Новый пароль</td><td><input type="password" name="chpass" value="" size="28" autocomplete="off"></td><td></td></tr>
				<tr><td class="w120 right nw">Подтвердите пароль</td><td><input type="password" name="passagain" value="" size="28" autocomplete="off"></td><td></td></tr>
				<tr><td colspan="3"><input type="submit" value="Сменить пароль" class="buttonS w200"></td></tr>
			</table></div>
		</form>

		<form name="mymail" method="post" action="/takeprofedit.php?act=13">
			<input type="hidden" name="hash4u" value="<?= $form_hash ?>">
			<div class="bx1"><div class="pad5x5 u2"><span class="bulet"></span>Сменить почтовый ящик</div><table class="tables4">
				<tr><td class="w120 right nw">Ваша почта</td><td colspan="2"><b><?= my_h($CURUSER["email"]) ?></b> ( При смене адреса письмо для подтверждения высылается на новый адрес )</td></tr>
					<tr><td class="w120 right nw">Новая почта</td><td><input type="text" name="mail" value="" size="28" autocomplete="off"></td><td><a class="sba" href="/pay_help.php">Для смены почты обратитесь в техподдержку</a></td></tr>
				<tr><td class="w120 right nw">Подтвердите почту</td><td><input type="text" name="mailagain" value="" size="28" autocomplete="off"></td><td></td></tr>
				<tr><td colspan="3"><input type="submit" value="Сменить почту" class="buttonS w200"></td></tr>
			</table></div>
		</form>

		<div class="bx1_0">
			<div class="pad5x5 red"><span class="bulet_red"></span><b>Удаление аккаунта</b></div>
			<div class="pad10x10">
				<div class="w200 nw floatleft">
					<a href="/delacct.php" class="buttonS w200">Удалить аккаунт</a>
				</div>
				<div style="padding: 0 0 0 220px;">
					Удаление необратимо. На следующей странице потребуется повторно ввести пароль и подтвердить действие.
				</div>
			</div>
		</div>
	</div>
	<div class="clr"></div>
</div>
<?
stdfoot();

?>
