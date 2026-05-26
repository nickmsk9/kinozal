<?

require "include/bittorrent.php";

dbconn(false);
loggedinorreturn();

function bark($msg) {
	global $tracker_lang;
	stdhead($tracker_lang['error']);
	stdmsg($tracker_lang['error'], $msg);
	stdfoot();
	exit;
}

function ud_h($value) {
	return htmlspecialchars_uni((string)$value);
}

function ud_size($bytes) {
	return function_exists('mksize') ? mksize((float)$bytes) : (string)$bytes;
}

function ud_minutes($minutes) {
	$minutes = max(0, (int)$minutes);
	$hours = floor($minutes / 60);
	$mins = $minutes % 60;
	return number_format($hours, 0, '.', ' ') . " час. " . $mins . " мин.";
}

function ud_datetime($value) {
	global $tracker_lang;
	if (empty($value) || $value == "0000-00-00 00:00:00") {
		return $tracker_lang['never'] ?? 'никогда';
	}
	$ts = sql_timestamp_to_unix_timestamp($value);
	return ud_h(date('d.m.Y в H:i', $ts)) . " ( " . get_et($ts) . " назад )";
}

function ud_birthday($value) {
	if (empty($value) || $value == "0000-00-00") {
		return "не указана";
	}
	$months = array(
		"01" => "января", "02" => "февраля", "03" => "марта", "04" => "апреля",
		"05" => "мая", "06" => "июня", "07" => "июля", "08" => "августа",
		"09" => "сентября", "10" => "октября", "11" => "ноября", "12" => "декабря"
	);
	$ts = strtotime($value);
	$day = date("d", $ts);
	$month = date("m", $ts);
	$year = date("Y", $ts);
	return '<a href="/users.php?s6=' . $day . '-' . $month . '" class="sba">' . $day . ' ' . ($months[$month] ?? $month) . '</a> ' . $year . ' года';
}

function ud_rank_name($user) {
	$class = isset($user["class"]) ? (int)$user["class"] : UC_USER;
	if ($class === UC_POWER_USER && !empty($user["added"]) && $user["added"] !== "0000-00-00 00:00:00") {
		$registered_at = sql_timestamp_to_unix_timestamp($user["added"]);
		if ($registered_at > 0 && (TIMENOW - $registered_at) >= (86400 * 365 * 3)) {
			return 'Заслуженный Зритель';
		}
	}
	return get_user_class_name($class);
}

function ud_ratio($uploaded, $downloaded) {
	if ((float)$downloaded > 0) {
		return number_format((float)$uploaded / (float)$downloaded, 2);
	}
	return ((float)$uploaded > 0) ? "Inf." : "0.00";
}

function ud_rating_img($ratio) {
	if (!is_numeric($ratio)) {
		return "";
	}
	$rating = max(1, min(5, (int)ceil((float)$ratio)));
	return "<img src='/pic/r" . $rating . ".gif' title='Рейтинг: " . ud_h($ratio) . "' alt=''> ";
}

function ud_table_row($title, $value) {
	return "<tr><td class=\"w135\">" . $title . "</td><td>" . $value . "</td></tr>\n";
}

function ud_print_moderator_block($user, $id, $enabled) {
	global $CURUSER, $DEFAULTBASEURL, $tracker_lang;

	if (get_user_class() < UC_MODERATOR || (int)$user["class"] >= get_user_class()) {
		return;
	}

	begin_frame("Редактирование пользователя", true);
	print("<form method=\"post\" action=\"modtask.php\">\n");
	print("<input type=\"hidden\" name=\"action\" value=\"edituser\">\n");
	print("<input type=\"hidden\" name=\"userid\" value=\"" . (int)$id . "\">\n");
	print("<input type=\"hidden\" name=\"returnto\" value=\"userdetails.php?id=" . (int)$id . "\">\n");
	print("<table class=\"main\" border=\"1\" cellspacing=\"0\" cellpadding=\"5\">\n");
	print("<tr><td class=\"rowhead\">Заголовок</td><td colspan=\"2\" align=\"left\"><input type=\"text\" size=\"60\" name=\"title\" value=\"" . ud_h($user["title"] ?? "") . "\"></td></tr>\n");
	print("<tr><td class=\"rowhead\">Аватар</td><td colspan=\"2\" align=\"left\"><input type=\"text\" size=\"60\" name=\"avatar\" value=\"" . ud_h($user["avatar"] ?? "") . "\"></td></tr>\n");

	if ($CURUSER["class"] < UC_ADMINISTRATOR) {
		print("<input type=\"hidden\" name=\"donor\" value=\"" . ud_h($user["donor"] ?? "no") . "\">\n");
	} else {
		print("<tr><td class=\"rowhead\">Донор</td><td colspan=\"2\" align=\"left\"><input type=\"radio\" name=\"donor\" value=\"yes\"" . (($user["donor"] ?? "no") == "yes" ? " checked" : "") . ">Да <input type=\"radio\" name=\"donor\" value=\"no\"" . (($user["donor"] ?? "no") == "no" ? " checked" : "") . ">Нет</td></tr>\n");
	}

	if (get_user_class() == UC_MODERATOR && (int)$user["class"] > UC_VIP) {
		print("<input type=\"hidden\" name=\"class\" value=\"" . (int)$user["class"] . "\">\n");
	} else {
		print("<tr><td class=\"rowhead\">Класс</td><td colspan=\"2\" align=\"left\"><select name=\"class\">\n");
		$maxclass = (get_user_class() == UC_SYSOP) ? UC_SYSOP : ((get_user_class() == UC_MODERATOR) ? UC_VIP : get_user_class() - 1);
		for ($i = 0; $i <= $maxclass; ++$i) {
			print("<option value=\"$i\"" . ((int)$user["class"] == $i ? " selected" : "") . ">" . get_user_class_name($i) . "</option>\n");
		}
		print("</select></td></tr>\n");
	}

	if (get_user_class() >= UC_ADMINISTRATOR && function_exists('kz_cups_catalog') && function_exists('kz_cups_user_manual_ids')) {
		$manual_cups = kz_cups_user_manual_ids($id);
		$manual_cups_map = array_fill_keys($manual_cups, true);
		$cup_options = "";
		foreach (kz_cups_catalog() as $cup) {
			$cup_id = (int)$cup["id"];
			$checked = isset($manual_cups_map[$cup_id]) ? " checked" : "";
			$cup_options .= "<label><input type=\"checkbox\" name=\"manual_cups[]\" value=\"$cup_id\"$checked> " . ud_h($cup["icon"]) . " " . ud_h($cup["title"]) . "</label><br />\n";
		}
		print("<tr><td class=\"rowhead\">Переходящие кубки</td><td colspan=\"2\" align=\"left\">$cup_options</td></tr>\n");
	}

	print("<tr><td class=\"rowhead\">Сбросить день рождения</td><td colspan=\"2\" align=\"left\"><input type=\"radio\" name=\"resetb\" value=\"yes\">Да <input type=\"radio\" name=\"resetb\" value=\"no\" checked>Нет</td></tr>\n");
	print("<tr><td class=\"rowhead\">Поддержка</td><td colspan=\"2\" align=\"left\"><input type=\"radio\" name=\"support\" value=\"yes\"" . (($user["support"] ?? "no") == "yes" ? " checked" : "") . ">Да <input type=\"radio\" name=\"support\" value=\"no\"" . (($user["support"] ?? "no") == "no" ? " checked" : "") . ">Нет</td></tr>\n");
	print("<tr><td class=\"rowhead\">Поддержка для:</td><td colspan=\"2\" align=\"left\"><textarea cols=\"60\" rows=\"6\" name=\"supportfor\">" . ud_h($user["supportfor"] ?? "") . "</textarea></td></tr>\n");
	print("<tr><td class=\"rowhead\">История пользователя</td><td colspan=\"2\" align=\"left\"><textarea cols=\"60\" rows=\"6\"" . (get_user_class() < UC_SYSOP ? " readonly" : " name=\"modcomment\"") . ">" . ud_h($user["modcomment"] ?? "") . "</textarea></td></tr>\n");
	print("<tr><td class=\"rowhead\">Добавить заметку</td><td colspan=\"2\" align=\"left\"><textarea cols=\"60\" rows=\"3\" name=\"modcomm\"></textarea></td></tr>\n");

	$warned = ($user["warned"] ?? "no") == "yes";
	print("<tr><td class=\"rowhead\" rowspan=\"2\">Предупреждение</td><td align=\"center\" colspan=\"2\">" . ($warned ? "<font color=\"red\">Пользователь предупреждён</font>" : "<font color=\"green\">Предупреждения нет</font>") . "</td></tr>");
	if ($warned) {
		print("<tr><td>Оставить предупреждённым?<br><input name=\"warned\" value=\"yes\" type=\"radio\" checked>Да <input name=\"warned\" value=\"no\" type=\"radio\">Нет</td><td align=\"center\">");
		$warneduntil = $user["warneduntil"] ?? "";
		print((empty($warneduntil) || $warneduntil == "0000-00-00 00:00:00") ? "Предупреждение на неограниченный срок" : "Предупреждение действует до<br>" . date("d.m.Y H:i:s", strtotime($warneduntil)) . " (осталось " . get_lt(strtotime($warneduntil)) . ")");
		print("</td></tr>\n");
	} else {
		print("<input type=\"hidden\" name=\"warned\" value=\"no\">\n");
		print("<tr><td>Предупредить на:<br><select name=\"warnlength\"><option value=\"0\">------</option><option value=\"1\">1 неделю</option><option value=\"2\">2 недели</option><option value=\"4\">4 недели</option><option value=\"8\">8 недель</option><option value=\"255\">Неограничено</option></select></td><td>Причина предупреждения:<br><input type=\"text\" size=\"60\" name=\"warnpm\"></td></tr>");
	}

	print("<tr><td class=\"rowhead\" rowspan=\"2\">Включен</td><td align=\"center\" colspan=\"2\">" . ($enabled ? "<font color=\"green\">Пользователь включен</font>" : "<font color=\"red\">Пользователь отключен</font>") . "</td></tr>");
	$disabler = "<select name=\"dislength\"><option value=\"0\">------</option><option value=\"1\">1 неделю</option><option value=\"2\">2 недели</option><option value=\"4\">4 недели</option><option value=\"8\">8 недель</option><option value=\"255\">Неограничено</option></select>";
	if ($enabled) {
		print("<input type=\"hidden\" name=\"enabled\" value=\"yes\">\n");
		print("<tr><td>Отключить на:<br>$disabler</td><td>Причина отключения:<br><input type=\"text\" name=\"disreason\" size=\"60\"></td></tr>");
	} else {
		print("<tr><td>Включить?<br><input name=\"enabled\" value=\"yes\" type=\"radio\">Да <input name=\"enabled\" value=\"no\" type=\"radio\" checked>Нет</td><td>Причина включения:<br><input type=\"text\" name=\"enareason\" size=\"60\"></td></tr>");
	}
	?>
	<script type="text/javascript">
	function togglepic(bu, picid, formid) {
		var pic = document.getElementById(picid);
		var form = document.getElementById(formid);
		if (pic.src == bu + "/pic/plus.gif") {
			pic.src = bu + "/pic/minus.gif";
			form.value = "minus";
		} else {
			pic.src = bu + "/pic/plus.gif";
			form.value = "plus";
		}
	}
	</script>
	<?
	print("<tr><td class=\"rowhead\">Изменить раздачу</td><td align=\"left\"><img src=\"pic/plus.gif\" id=\"uppic\" onclick=\"togglepic('$DEFAULTBASEURL','uppic','upchange')\" style=\"cursor: pointer;\"> <input type=\"text\" name=\"amountup\" size=\"10\"></td><td><select name=\"formatup\"><option value=\"mb\">MB</option><option value=\"gb\">GB</option></select></td></tr>");
	print("<tr><td class=\"rowhead\">Изменить скачку</td><td align=\"left\"><img src=\"pic/plus.gif\" id=\"downpic\" onclick=\"togglepic('$DEFAULTBASEURL','downpic','downchange')\" style=\"cursor: pointer;\"> <input type=\"text\" name=\"amountdown\" size=\"10\"></td><td><select name=\"formatdown\"><option value=\"mb\">MB</option><option value=\"gb\">GB</option></select></td></tr>");
	print("<tr><td class=\"rowhead\">Сбросить passkey</td><td colspan=\"2\" align=\"left\"><input name=\"resetkey\" value=\"1\" type=\"checkbox\"></td></tr>\n");
	print($CURUSER["class"] < UC_ADMINISTRATOR ? "<input type=\"hidden\" name=\"deluser\" value=\"\">" : "<tr><td class=\"rowhead\">Удалить</td><td colspan=\"2\" align=\"left\"><input type=\"checkbox\" name=\"deluser\" value=\"1\"></td></tr>");
	print("<tr><td colspan=\"3\" align=\"center\"><input type=\"submit\" class=\"btn\" value=\"ОК\"></td></tr>\n");
	print("</table>\n");
	print("<input type=\"hidden\" id=\"upchange\" name=\"upchange\" value=\"plus\"><input type=\"hidden\" id=\"downchange\" name=\"downchange\" value=\"plus\">\n");
	print("</form>\n");
	end_frame();
}

$id = isset($_GET["id"]) ? (int)$_GET["id"] : 0;

if (!is_valid_id($id)) {
	bark($tracker_lang['invalid_id']);
}

$r = sql_query("SELECT * FROM users WHERE id = $id") or sqlerr(__FILE__, __LINE__);
$user = mysqli_fetch_assoc($r) or bark("Нет пользователя с таким ID $id.");

if ($user["status"] == "pending") {
	die;
}

$enabled = ($user["enabled"] == "yes");
$profile_name = ud_h($user["username"]);
$avatar_url = !empty($user["avatar"]) ? ud_h($user["avatar"]) : "/pic/default_avatar.gif";
$ratio_value = ud_ratio($user["uploaded"], $user["downloaded"]);
$uploaded_total = ud_size($user["uploaded"]);
$downloaded_total = ud_size($user["downloaded"]);
$seed_total = ud_minutes($user["seedtime"] ?? 0);
$leech_total = ud_minutes($user["leechtime"] ?? 0);
$bonus = isset($user["bonus"]) ? (float)$user["bonus"] : 0;
$reputation = isset($user["simpaty"]) ? (int)$user["simpaty"] : 0;
$rank_name = ud_h(ud_rank_name($user));

$country_name = "не указано";
$country_flag = "";
$country_id = (int)($user["country"] ?? 0);
if ($country_id > 0) {
	$res_country = sql_query("SELECT name, flagpic FROM countries WHERE id = $country_id LIMIT 1") or sqlerr(__FILE__, __LINE__);
	if ($country_row = mysqli_fetch_assoc($res_country)) {
		$country_name = ud_h($country_row["name"]);
		$country_flag = ud_h($country_row["flagpic"]);
	}
}

$torrent_count = 0;
$res_torrents = sql_query("SELECT COUNT(*) FROM torrents WHERE owner = $id") or sqlerr(__FILE__, __LINE__);
if ($row_torrents = mysqli_fetch_row($res_torrents)) {
	$torrent_count = (int)$row_torrents[0];
}

$comment_count = 0;
$res_comments = sql_query("SELECT COUNT(*) FROM comments WHERE user = $id") or sqlerr(__FILE__, __LINE__);
if ($row_comments = mysqli_fetch_row($res_comments)) {
	$comment_count = (int)$row_comments[0];
}

$last_torrent_link = '<a href="/browse.php" class="sba">здесь</a>';
$res_last = sql_query("SELECT torrent FROM snatched WHERE userid = $id ORDER BY completedat DESC, last_action DESC LIMIT 1") or sqlerr(__FILE__, __LINE__);
if ($last = mysqli_fetch_assoc($res_last)) {
	$last_torrent_link = '<a href="/details.php?id=' . (int)$last["torrent"] . '" class="sba">здесь</a>';
}

$city = !empty($user["city"]) ? '<a href="/users.php?s2=' . urlencode($user["city"]) . '" class="sba">' . ud_h($user["city"]) . '</a>' : 'не указано';
$favorite_movie = !empty($user["favorite_movie"]) ? '<a href="/users.php?s3=' . urlencode($user["favorite_movie"]) . '" class="sba">' . ud_h($user["favorite_movie"]) . '</a>' : 'не указано';
$favorite_persons = !empty($user["favorite_persons"]) ? '<a href="/users.php?s4=' . urlencode($user["favorite_persons"]) . '" class="sba">' . ud_h($user["favorite_persons"]) . '</a>' : 'не указано';

stdhead("Пользователь :: " . $user["username"]);

if (!$enabled) {
	print("<div class=\"bx1 center b red\">Этот аккаунт отключен</div>\n");
}
?>
<div class="mn_wrap">
	<div class="mn1_menu">
		<ul class="men u2 w200">
			<li class="img"><a href="/userdetails.php?id=<?= $id ?>"><img src="<?= $avatar_url ?>" class="p200" alt=""></a></li>
			<li class="tp">Меню пользователя</li>
			<li><span class="bulet"></span><a href="/message.php">Личные сообщения</a></li>
			<li><span class="bulet"></span><a href="/userdetails.php?id=<?= $id ?>">Мой профиль</a></li>
			<li><span class="bulet"></span><a href="/my.php">Редактировать профиль</a></li>
			<li><span class="bulet"></span><a href="/mygroups.php">Мои группы</a></li>
			<li><span class="bulet"></span><a href="/friends.php?id=<?= $id ?>">Мой список друзей</a></li>
			<li class="sf"><span class="bulet"></span><a href="/mytorrents.php?id=<?= $id ?>">Мои раздачи</a></li>
			<li class="tp">Репутация<span class="floatright"><a href="/pay_mode_b.php?userid=<?= $id ?>" title="Понизить репутацию"><img border="0" src="/pic/minus.gif" alt=""></a> <b><?= $reputation ?></b> <a href="/pay_mode_b.php?userid=<?= $id ?>" title="Повысить репутацию"><img border="0" src="/pic/plus.gif" alt=""></a></span></li>
			<li><span class="bulet"></span><a href="/user_reputation.php?id=<?= $id ?>">Полученные отзывы</a></li>
			<li><span class="bulet"></span><a href="/user_reputation.php?id=<?= $id ?>&amp;type=2">Отданные отзывы</a></li>
			<li class="tp">Закладки</li>
			<li><span class="bulet"></span><a href="/bookmarks.php?type=1">Раздачи</a></li>
			<li><span class="bulet"></span><a href="/bookmarks.php?type=2">Группы</a></li>
			<li><span class="bulet"></span><a href="/bookmarks.php?type=3">Пользователи</a></li>
			<li class="sf"><span class="bulet"></span><a href="/bookmarks.php?type=4">Персоны</a></li>
			<li class="tp">История</li>
			<li><span class="bulet"></span><a href="/hytorrents.php?id=<?= $id ?>">Скачанного</a></li>
			<li><span class="bulet"></span><a href="/userhistory.php?id=<?= $id ?>">Комментариев</a></li>
			<li><span class="bulet"></span><a href="/uservotes.php?id=<?= $id ?>">Голосований</a></li>
			<li class="tp">Голоса<span class="floatright b"><?= number_format($bonus, 0, '.', ' ') ?></span></li>
			<li><span class="bulet"></span><a href="/pay.php">Получить голоса</a></li>
			<li><span class="bulet"></span><a href="/pay_mode.php">Управление голосами</a></li>
			<li><span class="bulet"></span><a href="/pay_mode.php">Оставить пожелание</a></li>
			<li><span class="bulet"></span><a href="/pay_mode.php">Обнулить счетчик скачиваний</a></li>
		</ul>
	</div>
	<div class="mn1_content">
		<div class="bx1 u2"><a href="/userdetails.php?id=<?= $id ?>" class="u2"><?= $profile_name ?></a></div>
		<div class="bx1_0"><table class="tables1 u2">
			<?= ud_table_row("Звание", $rank_name) ?>
			<? $cups_html = function_exists('kz_cups_user_profile_html') ? kz_cups_user_profile_html($id) : ''; ?>
			<? if ($cups_html !== '') { echo ud_table_row("Кубок", $cups_html); } ?>
		</table></div>
		<div class="bx1_0"><table class="tables1 u2">
			<?= ud_table_row("Залил", $uploaded_total . " ( сегодня: 0 Б )") ?>
			<?= ud_table_row("Скачал", $downloaded_total . " ( сегодня: 0 Б )") ?>
			<?= ud_table_row("Рейтинг", ud_rating_img($ratio_value) . $ratio_value) ?>
			<?= ud_table_row("Сид", $seed_total . " ( сегодня: 0 мин. )") ?>
			<?= ud_table_row("Пир", $leech_total . " ( сегодня: 0 мин. )") ?>
			<?= ud_table_row("Торренты", "Доступно в сутки ( 20 ) Скачано ( 0 ) Последний ( $last_torrent_link )") ?>
		</table></div>
		<div class="bx1_0"><table class="tables1 u2">
			<?= ud_table_row("Раздачи", $torrent_count > 0 ? '<a href="/mytorrents.php?id=' . $id . '" class="sba">' . $torrent_count . ' раздач</a>' : "нет раздач") ?>
			<?= ud_table_row("Комментарии", $comment_count > 0 ? '<a href="/userhistory.php?id=' . $id . '" class="sba">' . $comment_count . ' комментариев</a>' : "нет комментариев") ?>
		</table></div>
		<div class="bx1_0"><table class="tables1 u2">
			<?= ud_table_row("Зарегистрирован", ud_datetime($user["added"])) ?>
			<?= ud_table_row("Был на трекере", ud_datetime($user["last_access"])) ?>
			<?= ud_table_row("Место жительства", ($country_flag !== '' ? "<img src='/pic/flag/$country_flag' class='i2 c$country_id' alt=''> " : "") . "<a href='/users.php?co=$country_id' class='sba'>$country_name</a>") ?>
			<?= ud_table_row("Дата рождения", ud_birthday($user["birthday"])) ?>
			<?= ud_table_row("Города", $city) ?>
			<?= ud_table_row("Любимый фильм", $favorite_movie) ?>
			<?= ud_table_row("Любимые персоны", $favorite_persons) ?>
		</table></div>
		<div id="connecto"><div class="bx1 u2"><span id="connecto_msg">Проверить подключения ( сид / пир ) к трекеру ( <a href="#" onclick="manage_Connect(); return false;" class="sba">проверить</a> )</span></div></div>
		<script type="text/javascript">
		function manage_Connect() {
			$('#connecto_msg').html('Загрузка, идет проверка информации...');
			$.get("/get_srv_userdetails.php?id=<?= $id ?>&class=<?= (int)$user["class"] ?>", function(s) {
				$('#connecto').html(s);
			});
		}
		</script>
	</div>
	<div class="clear"></div>
</div>
<div class="bx2_0"><ul class="men"><li class="tp2 center">Кто ОнЛайн здесь, на этой странице [ <a class="sba" href="/pay.php">помочь проекту</a> ]</li><li><div class="pad5x5"><a href="/userdetails.php?id=<?= $id ?>" class="u2"><?= $profile_name ?></a></div></li></ul></div>
<?
ud_print_moderator_block($user, $id, $enabled);
stdfoot();

?>
