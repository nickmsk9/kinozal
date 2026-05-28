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

function ud_birthday($value, $link_class = "sba") {
	if (empty($value) || $value == "0000-00-00") {
		return '<a href="/users.php" class="' . ud_h($link_class) . '">не указано</a>';
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
	return '<a href="/users.php?s6=' . $month . '-' . $day . '" class="' . ud_h($link_class) . '">' . $day . ' ' . ($months[$month] ?? $month) . '</a> ' . $year . ' года';
}

function ud_rank_name($user) {
	$class = isset($user["class"]) ? (int)$user["class"] : UC_USER;
	return get_user_class_name($class);
}

function ud_ratio($uploaded, $downloaded) {
	if ((float)$downloaded > 0) {
		return number_format((float)$uploaded / (float)$downloaded, 2);
	}
	return ((float)$uploaded > 0) ? "Inf." : "0.00";
}

function ud_rating_img($ratio) {
	if ($ratio === 'Inf.') {
		return "<img src='/pic/r5.gif' title='Рейтинг: Inf.' alt=''> ";
	}
	if (!is_numeric($ratio)) {
		return "";
	}
	$rating = max(1, min(5, (int)ceil((float)$ratio)));
	return "<img src='/pic/r" . $rating . ".gif' title='Рейтинг: " . ud_h($ratio) . "' alt=''> ";
}

function ud_table_row($title, $value) {
	return "<tr><td class=\"w135\">" . $title . "</td><td>" . $value . "</td></tr>\n";
}

function ud_search_links($value, $param, $link_class, $split = false) {
	$value = trim((string)$value);
	if ($value === '') {
		return '<a href="/users.php" class="' . ud_h($link_class) . '">не указано</a>';
	}

	$items = $split ? preg_split('/\s*,\s*/u', $value, -1, PREG_SPLIT_NO_EMPTY) : array($value);
	$links = array();
	foreach ($items as $item) {
		$item = trim((string)$item);
		if ($item === '') {
			continue;
		}
		$links[] = '<a href="/users.php?' . rawurlencode($param) . '=' . urlencode($item) . '" class="' . ud_h($link_class) . '">' . ud_h($item) . '</a>';
	}

	return $links ? implode(', ', $links) : '<a href="/users.php" class="' . ud_h($link_class) . '">не указано</a>';
}

function ud_cup_history_modcomment($userid, $modcomment) {
	$userid = (int)$userid;
	$modcomment = (string)$modcomment;

	if (!is_valid_id($userid)) {
		return $modcomment;
	}

	$res = sql_query("
		SELECT c.title, uc.source, uc.assigned_at, u.username AS assigned_username
		FROM user_cups AS uc
		INNER JOIN cups AS c ON c.id = uc.cup_id
		LEFT JOIN users AS u ON u.id = uc.assigned_by
		WHERE uc.userid = $userid
		ORDER BY uc.assigned_at DESC, c.sort DESC, c.id DESC
	") or sqlerr(__FILE__, __LINE__);

	$lines = array();
	while ($row = mysqli_fetch_assoc($res)) {
		$title = (string)$row['title'];
		if ($title === '' || strpos($modcomment, $title) !== false) {
			continue;
		}

		$date = !empty($row['assigned_at']) && $row['assigned_at'] !== '0000-00-00 00:00:00'
			? date('Y-m-d', strtotime($row['assigned_at']))
			: date('Y-m-d');
		$by = !empty($row['assigned_username'])
			? 'пользователем ' . $row['assigned_username']
			: ((string)$row['source'] === 'auto' ? 'автоматически' : 'администратором');
		$lines[] = $date . ' - Назначен переходящий кубок "' . $title . '" ' . $by . '.';
	}

	if (!$lines) {
		return $modcomment;
	}

	return implode("\n", $lines) . "\n" . $modcomment;
}

function ud_print_moderator_block($user, $id, $enabled) {
	global $CURUSER, $DEFAULTBASEURL, $tracker_lang;

	if (get_user_class() < UC_MODERATOR || (int)$user["class"] >= get_user_class()) {
		return;
	}

	begin_frame("Редактирование пользователя", false, 5);
	print("<div class=\"pad0x0x5x0\"><a href=\"#\" id=\"modEditToggle\" class=\"sba\" onclick=\"return toggleUserEditBlock();\">Показать редактирование пользователя</a></div>\n");
	print("<div id=\"modEditBlock\" class=\"pad5x5\" style=\"display: none;\">\n");
	print("<form method=\"post\" action=\"modtask.php\">\n");
	print("<input type=\"hidden\" name=\"action\" value=\"edituser\">\n");
	print("<input type=\"hidden\" name=\"userid\" value=\"" . (int)$id . "\">\n");
	print("<input type=\"hidden\" name=\"returnto\" value=\"userdetails.php?id=" . (int)$id . "\">\n");
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
	print("<table class=\"tables1 w100p\">\n");
	print("<tr><td class=\"rowhead w175\">Заголовок</td><td colspan=\"2\"><input type=\"text\" class=\"w100p\" name=\"title\" value=\"" . ud_h($user["title"] ?? "") . "\"></td></tr>\n");
	print("<tr><td class=\"rowhead w175\">Аватар</td><td colspan=\"2\"><input type=\"text\" class=\"w100p\" name=\"avatar\" value=\"" . ud_h($user["avatar"] ?? "") . "\"></td></tr>\n");

	if ($CURUSER["class"] < UC_ADMINISTRATOR) {
		print("<input type=\"hidden\" name=\"donor\" value=\"" . ud_h($user["donor"] ?? "no") . "\">\n");
	} else {
		print("<tr><td class=\"rowhead w175\">Донор</td><td colspan=\"2\"><label><input type=\"radio\" name=\"donor\" value=\"yes\"" . (($user["donor"] ?? "no") == "yes" ? " checked" : "") . "> Да</label> <label><input type=\"radio\" name=\"donor\" value=\"no\"" . (($user["donor"] ?? "no") == "no" ? " checked" : "") . "> Нет</label></td></tr>\n");
	}

	if (get_user_class() == UC_MODERATOR && (int)$user["class"] > UC_VIP) {
		print("<input type=\"hidden\" name=\"class\" value=\"" . (int)$user["class"] . "\">\n");
	} else {
		print("<tr><td class=\"rowhead w175\">Класс</td><td colspan=\"2\"><span class=\"sw190\"><select name=\"class\" class=\"w190 styled\">\n");
		$maxclass = (get_user_class() == UC_SYSOP) ? UC_SYSOP : ((get_user_class() == UC_MODERATOR) ? UC_VIP : get_user_class() - 1);
		for ($i = 0; $i <= $maxclass; ++$i) {
			print("<option value=\"$i\"" . ((int)$user["class"] == $i ? " selected" : "") . ">" . get_user_class_name($i) . "</option>\n");
		}
		print("</select></span></td></tr>\n");
	}

	if (get_user_class() >= UC_ADMINISTRATOR && function_exists('kz_cups_catalog') && function_exists('kz_cups_user_manual_ids')) {
		$manual_cups = kz_cups_user_manual_ids($id);
		$manual_cups_map = array_fill_keys($manual_cups, true);
		$cup_options = "";
		foreach (kz_cups_catalog() as $cup) {
			$cup_id = (int)$cup["id"];
			$checked = isset($manual_cups_map[$cup_id]) ? " checked" : "";
			$cup_options .= "<label><input type=\"checkbox\" name=\"manual_cups[]\" value=\"$cup_id\"$checked> <i class=\"i1 " . ud_h($cup["icon"]) . "\"></i> " . ud_h($cup["title"]) . "</label><br />\n";
		}
		print("<tr><td class=\"rowhead w175\">Переходящие кубки</td><td colspan=\"2\">$cup_options</td></tr>\n");
	}

	print("<tr><td class=\"rowhead w175\">Сбросить день рождения</td><td colspan=\"2\"><label><input type=\"radio\" name=\"resetb\" value=\"yes\"> Да</label> <label><input type=\"radio\" name=\"resetb\" value=\"no\" checked> Нет</label></td></tr>\n");
	print("<tr><td class=\"rowhead w175\">Поддержка</td><td colspan=\"2\"><label><input type=\"radio\" name=\"support\" value=\"yes\"" . (($user["support"] ?? "no") == "yes" ? " checked" : "") . "> Да</label> <label><input type=\"radio\" name=\"support\" value=\"no\"" . (($user["support"] ?? "no") == "no" ? " checked" : "") . "> Нет</label></td></tr>\n");
	print("<tr><td class=\"rowhead w175\">Поддержка для:</td><td colspan=\"2\"><textarea rows=\"6\" class=\"w100p\" name=\"supportfor\">" . ud_h($user["supportfor"] ?? "") . "</textarea></td></tr>\n");
	$history_modcomment = ud_cup_history_modcomment((int)$id, $user["modcomment"] ?? "");
	print("<tr><td class=\"rowhead w175\">История пользователя</td><td colspan=\"2\"><textarea rows=\"6\" class=\"w100p\"" . (get_user_class() < UC_SYSOP ? " readonly" : " name=\"modcomment\"") . ">" . ud_h($history_modcomment) . "</textarea></td></tr>\n");
	print("<tr><td class=\"rowhead w175\">Добавить заметку</td><td colspan=\"2\"><textarea rows=\"3\" class=\"w100p\" name=\"modcomm\"></textarea></td></tr>\n");

	$warned = ($user["warned"] ?? "no") == "yes";
	print("<tr><td class=\"rowhead w175\">Предупреждение</td><td colspan=\"2\">" . ($warned ? "<span class=\"red b\">Пользователь предупреждён</span>" : "<span class=\"green b\">Предупреждения нет</span>") . "</td></tr>");
	if ($warned) {
		print("<tr><td class=\"rowhead w175\">Оставить предупреждённым?</td><td><label><input name=\"warned\" value=\"yes\" type=\"radio\" checked> Да</label> <label><input name=\"warned\" value=\"no\" type=\"radio\"> Нет</label></td><td>");
		$warneduntil = $user["warneduntil"] ?? "";
		print((empty($warneduntil) || $warneduntil == "0000-00-00 00:00:00") ? "Предупреждение на неограниченный срок" : "Предупреждение действует до<br>" . date("d.m.Y H:i:s", strtotime($warneduntil)) . " (осталось " . get_lt(strtotime($warneduntil)) . ")");
		print("</td></tr>\n");
	} else {
		print("<input type=\"hidden\" name=\"warned\" value=\"no\">\n");
		print("<tr><td class=\"rowhead w175\"></td><td>Предупредить на:<br><span class=\"sw190\"><select name=\"warnlength\" class=\"w190 styled\"><option value=\"0\">------</option><option value=\"1\">1 неделю</option><option value=\"2\">2 недели</option><option value=\"4\">4 недели</option><option value=\"8\">8 недель</option><option value=\"255\">Неограничено</option></select></span></td><td>Причина предупреждения:<br><input type=\"text\" class=\"w100p\" name=\"warnpm\"></td></tr>");
	}

	print("<tr><td class=\"rowhead w175\">Включен</td><td colspan=\"2\">" . ($enabled ? "<span class=\"green b\">Пользователь включен</span>" : "<span class=\"red b\">Пользователь отключен</span>") . "</td></tr>");
	$disabler = "<span class=\"sw190\"><select name=\"dislength\" class=\"w190 styled\"><option value=\"0\">------</option><option value=\"1\">1 неделю</option><option value=\"2\">2 недели</option><option value=\"4\">4 недели</option><option value=\"8\">8 недель</option><option value=\"255\">Неограничено</option></select></span>";
	if ($enabled) {
		print("<input type=\"hidden\" name=\"enabled\" value=\"yes\">\n");
		print("<tr><td class=\"rowhead w175\"></td><td>Отключить на:<br>$disabler</td><td>Причина отключения:<br><input type=\"text\" class=\"w100p\" name=\"disreason\"></td></tr>");
	} else {
		print("<tr><td class=\"rowhead w175\">Включить?</td><td><label><input name=\"enabled\" value=\"yes\" type=\"radio\"> Да</label> <label><input name=\"enabled\" value=\"no\" type=\"radio\" checked> Нет</label></td><td>Причина включения:<br><input type=\"text\" class=\"w100p\" name=\"enareason\"></td></tr>");
	}
	print("<tr><td class=\"rowhead w175\">Изменить раздачу</td><td><img src=\"pic/plus.gif\" id=\"uppic\" onclick=\"togglepic('$DEFAULTBASEURL','uppic','upchange')\" class=\"pointer\" alt=\"\"> <input type=\"text\" name=\"amountup\" class=\"w90\"></td><td><span class=\"sw90\"><select name=\"formatup\" class=\"w90 styled\"><option value=\"mb\">MB</option><option value=\"gb\">GB</option></select></span></td></tr>");
	print("<tr><td class=\"rowhead w175\">Изменить скачку</td><td><img src=\"pic/plus.gif\" id=\"downpic\" onclick=\"togglepic('$DEFAULTBASEURL','downpic','downchange')\" class=\"pointer\" alt=\"\"> <input type=\"text\" name=\"amountdown\" class=\"w90\"></td><td><span class=\"sw90\"><select name=\"formatdown\" class=\"w90 styled\"><option value=\"mb\">MB</option><option value=\"gb\">GB</option></select></span></td></tr>");
	print("<tr><td class=\"rowhead w175\">Сбросить passkey</td><td colspan=\"2\"><input name=\"resetkey\" value=\"1\" type=\"checkbox\"></td></tr>\n");
	print($CURUSER["class"] < UC_ADMINISTRATOR ? "<input type=\"hidden\" name=\"deluser\" value=\"\">" : "<tr><td class=\"rowhead w175\">Удалить</td><td colspan=\"2\"><input type=\"checkbox\" name=\"deluser\" value=\"1\"></td></tr>");
	print("<tr><td colspan=\"3\" class=\"center\"><input type=\"submit\" class=\"buttonS\" value=\"ОК\"></td></tr>\n");
	print("</table>\n");
	print("<input type=\"hidden\" id=\"upchange\" name=\"upchange\" value=\"plus\"><input type=\"hidden\" id=\"downchange\" name=\"downchange\" value=\"plus\">\n");
	print("</form>\n");
	print("</div>\n");
	?>
	<script type="text/javascript">
	function toggleUserEditBlock() {
		var block = document.getElementById('modEditBlock');
		var toggle = document.getElementById('modEditToggle');
		if (!block || !toggle) {
			return false;
		}
		if (block.style.display === 'none' || block.style.display === '') {
			block.style.display = 'block';
			toggle.innerHTML = 'Скрыть редактирование пользователя';
		} else {
			block.style.display = 'none';
			toggle.innerHTML = 'Показать редактирование пользователя';
		}
		return false;
	}
	</script>
	<?
	end_frame();
}

$id = isset($_GET["id"]) ? (int)$_GET["id"] : 0;

if (!is_valid_id($id)) {
	bark($tracker_lang['invalid_id']);
}

$r = sql_query("SELECT * FROM users WHERE id = $id") or sqlerr(__FILE__, __LINE__);
$user = mysqli_fetch_assoc($r) or bark("Нет пользователя с таким ID $id.");

if ($user["status"] == "pending") {
	bark("Аккаунт пользователя еще не подтвержден.");
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
$user_class_css = 'u' . (int)$user["class"];
$user_icons = function_exists('get_user_icons') ? get_user_icons($user) : '';
$daily_limit = function_exists('kz_user_effective_torrent_limit') ? kz_user_effective_torrent_limit($user) : 20;
$daily_downloaded = function_exists('kz_torrent_downloads_today') ? kz_torrent_downloads_today($id) : 0;
$is_own_profile = !empty($CURUSER["id"]) && (int)$CURUSER["id"] === $id;

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

$last_torrent_link = '<a href="/browse.php" class="' . $user_class_css . '">здесь</a>';
$res_last = sql_query("SELECT torrent FROM snatched WHERE userid = $id ORDER BY completedat DESC, last_action DESC LIMIT 1") or sqlerr(__FILE__, __LINE__);
if ($last = mysqli_fetch_assoc($res_last)) {
	$last_torrent_link = '<a href="/details.php?id=' . (int)$last["torrent"] . '" class="' . $user_class_css . '">здесь</a>';
}

$city = ud_search_links($user["city"] ?? '', 's2', 'sba');
$favorite_movie = ud_search_links($user["favorite_movie"] ?? '', 's3', 'sba');
$favorite_persons = ud_search_links($user["favorite_persons"] ?? '', 's4', 'sba', true);
$recent_reputation = function_exists('kz_reputation_rows') ? kz_reputation_rows($id, 1, 10) : array();

$hide_right_blocks = true;
stdhead("Пользователь :: " . $user["username"]);

if (!$enabled) {
	print("<div class=\"bx1 center b red\">Этот аккаунт отключен</div>\n");
}
?>
<div class="mn_wrap">
	<div class="mn1_menu">
		<?= function_exists('kz_profile_menu_html') ? kz_profile_menu_html($user, $CURUSER) : '' ?>
		<? if (false) { ?>
		<ul class="men w200">
			<li class="img"><a href="/userdetails.php?id=<?= $id ?>"><img src="<?= $avatar_url ?>" class="p200" alt=""></a></li>
			<li class="tp">Меню пользователя</li>
			<li><span class="bulet"></span><a href="/message.php" class="<?= $user_class_css ?>">Личные сообщения</a></li>
			<li><span class="bulet"></span><a href="/userdetails.php?id=<?= $id ?>" class="<?= $user_class_css ?>">Мой профиль</a></li>
			<li><span class="bulet"></span><a href="/my.php" class="<?= $user_class_css ?>">Редактировать профиль</a></li>
			<li><span class="bulet"></span><a href="/mygroups.php" class="<?= $user_class_css ?>">Мои группы</a></li>
			<li><span class="bulet"></span><a href="/friends.php?id=<?= $id ?>" class="<?= $user_class_css ?>">Мой список друзей</a></li>
			<li class="sf"><span class="bulet"></span><a href="/mytorrents.php?id=<?= $id ?>" class="<?= $user_class_css ?>">Мои раздачи</a></li>
			<li class="tp">Репутация<span class="floatright"><a href="/pay_mode_b.php?userid=<?= $id ?>" title="Понизить репутацию"><img border="0" src="/pic/minus.gif" alt=""></a> <b><?= $reputation ?></b> <a href="/pay_mode_b.php?userid=<?= $id ?>" title="Повысить репутацию"><img border="0" src="/pic/plus.gif" alt=""></a></span></li>
			<li><span class="bulet"></span><a href="/user_reputation.php?id=<?= $id ?>" class="<?= $user_class_css ?>">Полученные отзывы</a></li>
			<li><span class="bulet"></span><a href="/user_reputation.php?id=<?= $id ?>&amp;type=2" class="<?= $user_class_css ?>">Отданные отзывы</a></li>
			<li class="tp">Закладки</li>
			<li><span class="bulet"></span><a href="/bookmarks.php?type=1" class="<?= $user_class_css ?>">Раздачи</a></li>
			<li><span class="bulet"></span><a href="/bookmarks.php?type=2" class="<?= $user_class_css ?>">Группы</a></li>
			<li><span class="bulet"></span><a href="/bookmarks.php?type=3" class="<?= $user_class_css ?>">Пользователи</a></li>
			<li class="sf"><span class="bulet"></span><a href="/bookmarks.php?type=4" class="<?= $user_class_css ?>">Персоны</a></li>
			<li class="tp">История</li>
			<li><span class="bulet"></span><a href="/hytorrents.php?id=<?= $id ?>" class="<?= $user_class_css ?>">Скачанного</a></li>
			<li><span class="bulet"></span><a href="/userhistory.php?id=<?= $id ?>" class="<?= $user_class_css ?>">Комментариев</a></li>
			<li><span class="bulet"></span><a href="/uservotes.php?id=<?= $id ?>" class="<?= $user_class_css ?>">Голосований</a></li>
			<li class="tp">Голоса<span class="floatright b"><?= number_format($bonus, 0, '.', ' ') ?></span></li>
			<li><span class="bulet"></span><a href="/pay.php" class="<?= $user_class_css ?>">Получить голоса</a></li>
			<li><span class="bulet"></span><a href="/pay_mode.php" class="<?= $user_class_css ?>">Управление голосами</a></li>
			<li><span class="bulet"></span><a href="/pay_mode.php" class="<?= $user_class_css ?>">Оставить пожелание</a></li>
			<li><span class="bulet"></span><a href="/pay_mode.php" class="<?= $user_class_css ?>">Обнулить счетчик скачиваний</a></li>
		</ul>
		<? } ?>
	</div>
	<div class="mn1_content">
		<div class="bx1 <?= $user_class_css ?>"><a href="/userdetails.php?id=<?= $id ?>" class="<?= $user_class_css ?>"><?= $profile_name ?></a> <?= $user_icons ?></div>
		<div class="bx1_0"><table class="tables1 <?= $user_class_css ?>">
			<?= ud_table_row("Звание", $rank_name) ?>
			<? $cups_html = function_exists('kz_cups_user_profile_html') ? kz_cups_user_profile_html($id, (int)$user["class"]) : ''; ?>
			<? if ($cups_html !== '') { echo ud_table_row("Кубок", $cups_html); } ?>
		</table></div>
		<div class="bx1_0"><table class="tables1 <?= $user_class_css ?>">
			<?= ud_table_row("Залил", $uploaded_total . " ( сегодня: 0 Б )") ?>
			<?= ud_table_row("Скачал", $downloaded_total . " ( сегодня: 0 Б )") ?>
			<?= ud_table_row("Рейтинг", ud_rating_img($ratio_value) . $ratio_value) ?>
			<?= ud_table_row("Сид", $seed_total . " ( сегодня: 0 мин. )") ?>
			<?= ud_table_row("Пир", $leech_total . " ( сегодня: 0 мин. )") ?>
			<?= ud_table_row("Торренты", "Доступно в сутки ( $daily_limit ) Скачано ( $daily_downloaded ) Последний ( $last_torrent_link )") ?>
		</table></div>
		<div class="bx1_0"><table class="tables1 <?= $user_class_css ?>">
			<?= ud_table_row("Раздачи", $torrent_count > 0 ? '<a href="/mytorrents.php?id=' . $id . '" class="' . $user_class_css . '">' . $torrent_count . ' раздач</a>' : "нет раздач") ?>
			<?= ud_table_row("Комментарии", $comment_count > 0 ? '<a href="/userhistory.php?id=' . $id . '" class="' . $user_class_css . '">' . $comment_count . ' комментариев</a>' : "нет комментариев") ?>
		</table></div>
		<div class="bx1_0"><table class="tables1 <?= $user_class_css ?>">
			<?= ud_table_row("Зарегистрирован", ud_datetime($user["added"])) ?>
			<?= ud_table_row("Был на трекере", ud_datetime($user["last_access"])) ?>
			<?= ud_table_row("Место жительства", ($country_flag !== '' ? "<img src='/pic/flag/$country_flag' class='i2 c$country_id' alt=''> " : "") . "<a href='/users.php?co=$country_id' class='$user_class_css'>$country_name</a>") ?>
			<?= ud_table_row("Дата рождения", ud_birthday($user["birthday"], $user_class_css)) ?>
			<?= ud_table_row("Города", $city) ?>
			<?= ud_table_row("Любимый фильм", $favorite_movie) ?>
			<?= ud_table_row("Любимые персоны", $favorite_persons) ?>
		</table></div>
		<div id="connecto"><div class="bx1 <?= $user_class_css ?>"><span id="connecto_msg">Проверить подключения ( сид / пир ) к трекеру ( <a href="#" onclick="manage_Connect(); return false;" class="<?= $user_class_css ?>">проверить</a> )</span></div></div>
		<script type="text/javascript">
		function manage_Connect() {
			$('#connecto_msg').html('Загрузка, идет проверка информации...');
			$.get("/get_srv_userdetails.php?id=<?= $id ?>&class=<?= (int)$user["class"] ?>", function(s) {
				$('#connecto').html(s);
			});
		}
		</script>
		<? if (!$is_own_profile && trim((string)($user["info"] ?? "")) !== '') { ?>
		<div class="bx1"><div class="<?= $user_class_css ?>"><span class="bulet"></span>&#1048;&#1085;&#1092;&#1086;&#1088;&#1084;&#1072;&#1094;&#1080;&#1103; &#1087;&#1086;&#1083;&#1100;&#1079;&#1086;&#1074;&#1072;&#1090;&#1077;&#1083;&#1103;</div><div class="pad5x5"><?= function_exists('format_comment') ? format_comment($user["info"]) : nl2br(ud_h($user["info"])) ?></div></div>
		<? } ?>
		<?= function_exists('kz_reputation_table_html') ? kz_reputation_table_html($recent_reputation, $user_class_css, 1, true) : '' ?>
	</div>
	<div class="clear"></div>
</div>
<div class="bx2_0"><ul class="men"><li class="tp2 center">Кто ОнЛайн здесь, на этой странице [ <a class="sba" href="/pay.php">помочь проекту</a> ]</li><li><div class="pad5x5"><a href="/userdetails.php?id=<?= $id ?>" class="<?= $user_class_css ?>"><?= $profile_name ?></a></div></li></ul></div>
<?
ud_print_moderator_block($user, $id, $enabled);
stdfoot();

?>
