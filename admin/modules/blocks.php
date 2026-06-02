<?php

if (!defined('ADMIN_FILE')) die("Illegal File Access");

$prefix = "orbital";

// This change is made to list all PHPs in tracker root, to make
// manual adding of pages to block system obsolete
// Howewer, you can use array below to map filename to some human-readable name
$existing_modules = str_replace('.php', '', glob('*.php'));
$allowed_modules = array_combine($existing_modules, array_map(
function ($el) {
	return "<i>{$el}</i>";
}, $existing_modules));

$allowed_modules = array_merge($allowed_modules, array(
	"admincp" => "Админка",
	"browse" => "Обзор",
	"forums" => "Форум",
	"upload" => "Загрузить",
	"details" => "Детали",
	"my" => "Панель управ.",
	"userdetails" => "Профиль",
	"viewrequests" => "Запросы",
	"viewoffers" => "Предложения",
	"log" => "Журнал",
	"rules" => "Правила",
	"message" => "Личка",
	"recover" => "Восстан. пароля",
	"signup" => "Регистрация",
	"login" => "Вход",
		"mybonus" => "Мой Бонус",
		"pay" => "Голоса и рейтинг",
		"pay_mode" => "Управление голосами",
		"pay_wishes" => "Пожелания",
		"pay_help" => "Техподдержка",
	"bookmarks" => "Закладки",
));

function BlocksNavi() {
	global $admin_file;
	echo "<h2>Управление блоками</h2><br />"
	."[ <a href=\"".$admin_file.".php?op=BlocksAdmin\">Главная</a>"
	." | <a href=\"".$admin_file.".php?op=BlocksNew\">Добавить новый блок</a>"
	." | <a href=\"".$admin_file.".php?op=BlocksFile\">Добавить новый файловый блок</a>"
	." | <a href=\"".$admin_file.".php?op=BlocksFileEdit\">Редактировать блок</a> ]";
}

function BlocksAdmin()
{
	global $admin_file, $prefix;

	BlocksNavi();

	echo "
	<div class=\"mn_wrap\">
		<div class=\"tp1_title\"><b>Список блоков</b></div>
		<div class=\"tp1_body\">
			<table class=\"tables2 w100p\">
				<tr>
					<td class=\"colhead center\">№</td>
					<td class=\"colhead\">Заголовок</td>
					<td class=\"colhead center\">Позиция</td>
					<td class=\"colhead center\">Вес</td>
					<td class=\"colhead center\">Порядок</td>
					<td class=\"colhead center\">Тип</td>
					<td class=\"colhead center\">Статус</td>
					<td class=\"colhead center\">Кто видит</td>
					<td class=\"colhead center\">Функции</td>
				</tr>";

	$result = sql_query("
		SELECT 
			a.bid, a.bkey, a.title, a.bposition, a.weight, a.active, a.blockfile, 
			a.view, a.expire, a.action,
			b.bid AS prev_bid,
			c.bid AS next_bid
		FROM " . $prefix . "_blocks AS a
		LEFT JOIN " . $prefix . "_blocks AS b 
			ON b.bposition = a.bposition AND b.weight = a.weight - 1
		LEFT JOIN " . $prefix . "_blocks AS c 
			ON c.bposition = a.bposition AND c.weight = a.weight + 1
		ORDER BY a.bposition, a.weight
	") or sqlerr(__FILE__, __LINE__);

	$count = 0;

	while ($row = mysqli_fetch_assoc($result)) {
		$count++;

		$bid       = (int)$row['bid'];
		$bkey      = (string)$row['bkey'];
		$title     = (string)$row['title'];
		$position  = (string)$row['bposition'];
		$weight    = (int)$row['weight'];
		$active    = (int)$row['active'];
		$blockfile = (string)$row['blockfile'];
		$view      = (int)$row['view'];
		$expire    = (int)$row['expire'];
		$action    = (string)$row['action'];
		$con1      = (int)$row['prev_bid'];
		$con2      = (int)$row['next_bid'];

		if (($expire && $expire < time()) || (!$active && $expire)) {
			if ($action === "d") {
				sql_query("UPDATE " . $prefix . "_blocks SET active='0', expire='0' WHERE bid=" . sqlesc($bid));
			} elseif ($action === "r") {
				sql_query("DELETE FROM " . $prefix . "_blocks WHERE bid=" . sqlesc($bid));
			}
		}

		switch ($position) {
			case "l":
				$position_title = "<img src=\"admin/pic/left.gif\" alt=\"Левый блок\" title=\"Левый блок\" /> Слева";
				break;

			case "r":
				$position_title = "Справа <img src=\"admin/pic/right.gif\" alt=\"Правый блок\" title=\"Правый блок\" />";
				break;

			case "c":
				$position_title = "<img src=\"admin/pic/right.gif\" alt=\"Центральный блок\" title=\"Центральный блок\" />&nbsp;По центру вверху&nbsp;<img src=\"admin/pic/left.gif\" alt=\"Центральный блок\" title=\"Центральный блок\" />";
				break;

			case "d":
				$position_title = "<img src=\"admin/pic/right.gif\" alt=\"Центральный блок\" title=\"Центральный блок\" />&nbsp;По центру внизу&nbsp;<img src=\"admin/pic/left.gif\" alt=\"Центральный блок\" title=\"Центральный блок\" />";
				break;

			case "b":
				$position_title = "<img src=\"admin/pic/up.gif\" alt=\"Баннер\" title=\"Баннер\" />&nbsp;Верхний баннер&nbsp;<img src=\"admin/pic/up.gif\" alt=\"Баннер\" title=\"Баннер\" />";
				break;

			case "f":
				$position_title = "<img src=\"admin/pic/down.gif\" alt=\"Баннер\" title=\"Баннер\" />&nbsp;Нижний баннер&nbsp;<img src=\"admin/pic/down.gif\" alt=\"Баннер\" title=\"Баннер\" />";
				break;

			default:
				$position_title = htmlspecialchars_uni($position);
				break;
		}

		$order = "";

		if ($con1) {
			$order .= "<a href=\"" . $admin_file . ".php?op=BlocksOrder&amp;weight=" . $weight . "&amp;bidori=" . $bid . "&amp;weightrep=" . ($weight - 1) . "&amp;bidrep=" . $con1 . "\">
				<img src=\"admin/pic/up.gif\" alt=\"Переместить вверх\" title=\"Переместить вверх\" />
			</a> ";
		}

		if ($con2) {
			$order .= "<a href=\"" . $admin_file . ".php?op=BlocksOrder&amp;weight=" . $weight . "&amp;bidori=" . $bid . "&amp;weightrep=" . ($weight + 1) . "&amp;bidrep=" . $con2 . "\">
				<img src=\"admin/pic/down.gif\" alt=\"Переместить вниз\" title=\"Переместить вниз\" />
			</a>";
		}

		if ($bkey === "") {
			$type = ($blockfile !== "") ? "Файл" : "HTML";
		} else {
			$type = "Системный";
		}

		$block_act = $active;

		if ($active === 1) {
			$status = "<span class=\"green\">Вкл.</span>";
			$change = "title=\"Выкл.\"><img src=\"admin/pic/inactive.gif\" alt=\"Выкл.\" /></a>";
		} else {
			$status = "<span class=\"red\">Выкл.</span>";
			$change = "title=\"Вкл.\"><img src=\"admin/pic/activate.gif\" alt=\"Вкл.\" /></a>";
		}

		switch ($view) {
			case 1:
				$who_view = "Только пользователи";
				break;

			case 2:
				$who_view = "Только администраторы";
				break;

			case 3:
				$who_view = "Только анонимы";
				break;

			default:
				$who_view = "Все посетители";
				break;
		}

		$title_safe = htmlspecialchars_uni($title);

		echo "
				<tr class=\"bov\">
					<td class=\"center\">" . $bid . "</td>
					<td>" . $title_safe . "</td>
					<td class=\"center nowrap\">" . $position_title . "</td>
					<td class=\"center\">" . $weight . "</td>
					<td class=\"center nowrap\">" . $order . "</td>
					<td class=\"center\">" . $type . "</td>
					<td class=\"center\">" . $status . "</td>
					<td class=\"center nowrap\">" . $who_view . "</td>
					<td class=\"center nowrap\">
						<a href=\"" . $admin_file . ".php?op=BlocksEdit&amp;bid=" . $bid . "\" title=\"Редактировать\">
							<img src=\"admin/pic/edit.gif\" alt=\"Редактировать\" />
						</a>
						<a href=\"" . $admin_file . ".php?op=BlocksChange&amp;bid=" . $bid . "\" " . $change;

		if ($bkey === "") {
			echo "
						<a href=\"" . $admin_file . ".php?op=BlocksDelete&amp;bid=" . $bid . "\" onclick=\"return DelCheck(this, 'Удалить &quot;" . $title_safe . "&quot;?');\" title=\"Удалить\">
							<img src=\"admin/pic/delete.gif\" alt=\"Удалить\" />
						</a>";
		}

		if ($block_act === 0) {
			echo "
						<a href=\"" . $admin_file . ".php?op=BlocksShow&amp;bid=" . $bid . "\" title=\"Показать\">
							<img src=\"admin/pic/show.gif\" alt=\"Показать\" />
						</a>";
		}

		echo "
					</td>
				</tr>";
	}

	if ($count === 0) {
		echo "
				<tr>
					<td colspan=\"9\" class=\"center\">Нет блоков.</td>
				</tr>";
	}

	echo "
			</table>
			<br />
			<div class=\"center\">
				<a class=\"sbab\" href=\"" . $admin_file . ".php?op=BlocksFixweight\">Зафиксировать позицию и положение блоков</a>
			</div>
		</div>
	</div>";
}

function BlocksNew() {
	global $prefix, $admin_file;
	BlocksNavi();
	echo "<h2>Добавить новый блок</h2>"
	."<form action=\"".$admin_file.".php\" method=\"post\">"
	."<table border=\"0\" align=\"center\">"
	."<tr><td>Заголовок:</td><td><input type=\"text\" name=\"title\" size=\"65\" style=\"width:400px\" maxlength=\"60\"></td></tr>"
	."<tr><td>Имя файла:</td><td>"
	."<select name=\"blockfile\" style=\"width:400px\">"
	."<option name=\"blockfile\" value=\"\" selected>Нет</option>";
	$handle = opendir("blocks");
	while ($file = readdir($handle)) {
		if (preg_match("/^block\-(.+)\.php/", $file, $matches)) {
			$found = str_replace("_", " ", $matches[1]);
			$check = sql_query("SELECT bid FROM ".$prefix."_blocks WHERE blockfile=" . sqlesc($file) . " LIMIT 1");
			if ($check && mysqli_num_rows($check) == 0) echo "<option value=\"$file\">$found</option>\n";
		}
	}
	closedir($handle);
	echo "</select></td></tr>"
	."<tr><td>Содержание:</td><td><textarea name=\"content\" cols=\"65\" rows=\"15\" style=\"width:400px\"></textarea></td></tr>"
	."<tr><td>Позиция:</td><td><select name=\"bposition\" style=\"width:400px\">"
	."<option name=\"bposition\" value=\"l\">Слева</option>"
	."<option name=\"bposition\" value=\"c\">По центру вверху</option>"
	."<option name=\"bposition\" value=\"d\">По центру внизу</option>"
	."<option name=\"bposition\" value=\"r\">Справа</option>"
	."<option name=\"bposition\" value=\"b\">Верхний баннер</option>"
	."<option name=\"bposition\" value=\"f\">Нижний баннер</option>"
	."</select></td></tr>";
	echo "<tr><td>Отображать блок в модулях:</td><td align=\"center\"><table border=\"0\" cellpadding=\"3\" cellspacing=\"0\" align=\"center\" style=\"width:400px\"><tr>";
	echo "<td><input type=\"checkbox\" name=\"blockwhere[]\" value=\"ihome\"></td><td>Главная</td>";
	global $allowed_modules;
	$a = 1;
	foreach ($allowed_modules as $name => $title) {
		$i++;
		$title = preg_replace("/_/", " ", $title);
		echo "<td><input type=\"checkbox\" name=\"blockwhere[]\" value=\"".$name."\"></td><td>$title</td>";
		if ($a == 2) {
			echo "</tr><tr>";
			$a = 0;
		} else {
			$a++;
		}
	}
	echo "</tr><tr><td><input type=\"checkbox\" name=\"blockwhere[]\" value=\"all\"></td><td><b>Во всех модулях</b></td><td><input type=\"checkbox\" name=\"blockwhere[]\" value=\"home\"></td><td><b>Только на главной</b></td><td><input type=\"checkbox\" name=\"blockwhere[]\" value=\"infly\"></td><td><b>Свободный блок</b></td></tr></table></td></tr>";
	echo "<tr><td>Скрывать?</td><td><input type=\"radio\" name=\"hide\" value=\"yes\" checked>Да &nbsp;&nbsp; <input type=\"radio\" name=\"hide\" value=\"no\">Нет</td></tr>";
	echo "<tr><td>Включить?</td><td><input type=\"radio\" name=\"active\" value=\"1\" checked>Да &nbsp;&nbsp; <input type=\"radio\" name=\"active\" value=\"0\">Нет</td></tr>"
	."<tr><td>Время работы, в днях:</td><td><input type=\"text\" name=\"expire\" maxlength=\"3\" value=\"0\" size=\"65\" style=\"width:400px\"></td></tr>"
	."<tr><td>После истечения:</td><td><select name=\"action\" style=\"width:400px\">"
	."<option name=\"action\" value=\"d\">Выкл.</option>"
	."<option name=\"action\" value=\"r\">Удалить</option></select></td></tr>"
	."<tr><td>Кто это будет видеть?</td><td><select name=\"view\" style=\"width:400px\">"
	."<option value=\"0\" >Все посетители</option>"
	."<option value=\"1\" >Только пользователи</option>"
	."<option value=\"2\" >Только администраторы</option>"
	."<option value=\"3\" >Только анонимы</option>"
	."</select></td></tr>"
	."<tr><td colspan=\"2\" align=\"center\"><br /><input type=\"hidden\" name=\"op\" value=\"BlocksAdd\"><input type=\"submit\" value=\"Создать блок\"></td></tr></table></form>";
}

function BlocksFile() {
	global $admin_file;
	BlocksNavi();
	echo "<h2>Добавить новый файловый блок</h2>"
	."<form action=\"".$admin_file.".php\" method=\"post\">"
	."<table border=\"0\" align=\"center\">"
	."<tr><td>Имя файла:</td><td><input type=\"text\" name=\"bf\" size=\"65\" style=\"width:400px\" maxlength=\"200\">"
	."<tr><td>Тип:</td><td><input type=\"radio\" name=\"flag\" value=\"php\" checked>PHP &nbsp;&nbsp; <input type=\"radio\" name=\"flag\" value=\"html\">HTML</td></tr>"
	."<tr><td colspan=\"2\" align=\"center\"><br /><input type=\"hidden\" name=\"op\" value=\"BlocksbfEdit\">"
	."<input type=\"submit\" value=\"Создать блок\"></td></tr></table></form>";
}

function BlocksOrder($weightrep,$weight,$bidrep,$bidori) {
	global $prefix, $admin_file;
	$result = sql_query("UPDATE ".$prefix."_blocks SET weight='$weight' WHERE bid='$bidrep'");
	$result2 = sql_query("UPDATE ".$prefix."_blocks SET weight='$weightrep' WHERE bid='$bidori'");
	Header("Location: ".$admin_file.".php?op=BlocksAdmin");
}

function BlocksFixweight() {
	global $prefix, $admin_file;
	$leftpos = "l";
	$rightpos = "r";
	$centerpos = "c";
	$result = sql_query("SELECT bid FROM ".$prefix."_blocks WHERE bposition='$leftpos' ORDER BY weight ASC");
	$weight = 0;
	while ($row = mysqli_fetch_assoc($result)) {
		$bid = intval($row['bid']);
		$weight++;
		sql_query("UPDATE ".$prefix."_blocks SET weight='$weight' WHERE bid='$bid'");
	}
	$result2 = sql_query("SELECT bid FROM ".$prefix."_blocks WHERE bposition='$rightpos' ORDER BY weight ASC");
	$weight = 0;
	while ($row2 = mysqli_fetch_assoc($result2)) {
		$bid = intval($row2['bid']);
		$weight++;
		sql_query("UPDATE ".$prefix."_blocks SET weight='$weight' WHERE bid='$bid'");
	}
	$result3 = sql_query("SELECT bid FROM ".$prefix."_blocks WHERE bposition='$centerpos' ORDER BY weight ASC");
	$weight = 0;
	while ($row3 = mysqli_fetch_assoc($result3)) {
		$bid = intval($row3['bid']);
		$weight++;
		sql_query("UPDATE ".$prefix."_blocks SET weight='$weight' WHERE bid='$bid'");
	}
	Header("Location: ".$admin_file.".php?op=BlocksAdmin");
}

function BlocksAdd($title, $content, $bposition, $active, $hide, $blockfile, $view, $expire, $action) {
	global $prefix, $admin_file;
	$weight_res = sql_query("SELECT weight FROM ".$prefix."_blocks WHERE bposition=".sqlesc($bposition)." ORDER BY weight DESC LIMIT 1");
	$weight_row = $weight_res ? mysqli_fetch_row($weight_res) : false;
	$weight = (int)($weight_row[0] ?? 0) + 1;
	$bkey = "";
	$btime = "";
	$which = "";
	if ($blockfile != "") {
		$url = "";
		if ($title == "") {
			$title = str_replace("block-", "", $blockfile);
			$title = str_replace(".php", "", $title);
			$title = str_replace("_", " ", $title);
		}
	}

	if (($content == "") && ($blockfile == "")) {
		stdmsg("Ошибка", "Блок не может быть пустым!", 'error');
	} else {
		if ($expire == "" || $expire == 0) {
			$expire = 0;
		} else {
			$expire = time() + ($expire * 86400);
		}
		if (isset($_POST['blockwhere'])) {
			$blockwhere = $_POST['blockwhere'];
			$which = (in_array("all", $blockwhere)) ? "all" : $which;
			$which = (in_array("home", $blockwhere)) ? "home" : $which;
			if ($which == "") {
				foreach ($blockwhere as $val) {
					$which .= "{$val},";
				}
			}
		}
		sql_query("INSERT INTO ".$prefix."_blocks VALUES (NULL, ".implode(", ", array_map("sqlesc", array($bkey, $title, $content, $bposition, $weight, $active, $btime, $blockfile, $view, $expire, $action, $which, $hide))).")") or sqlerr(__FILE__,__LINE__);
		Header("Location: ".$admin_file.".php?op=BlocksAdmin");
	}
}

function BlocksEdit($bid) {
	global $prefix, $admin_file;
	BlocksNavi();
	$bid = intval($bid);
	$edit_res = sql_query("SELECT bkey, title, content, bposition, weight, active, allow_hide, blockfile, view, expire, action, which FROM ".$prefix."_blocks WHERE bid='$bid'");
	$edit_row = $edit_res ? mysqli_fetch_row($edit_res) : false;
	list($bkey, $title, $content, $bposition, $weight, $active, $hide, $blockfile, $view, $expire, $action, $which) = $edit_row ?: array("", "", "", "l", 0, 0, "yes", "", 0, 0, "d", "");
	if ($blockfile != "") {
		$type = "(Файловый блок)";
	} else {
		$type = "(HTML блок)";
	}
	echo "<h2>Блок: $title $type</h2>"
	."<form action=\"".$admin_file.".php\" method=\"post\">"
	."<table border=\"0\" align=\"center\">"
	."<tr><td>Заголовок:</td><td><input type=\"text\" name=\"title\" maxlength=\"50\" size=\"65\" style=\"width:400px\" value=\"$title\"></td></tr>";
	if ($blockfile != "") {
		echo "<tr><td>Имя файла:</td><td><select name=\"blockfile\" style=\"width:400px\">";
		$dir = opendir("blocks");
		while ($file = readdir($dir)) {
			if (preg_match("/^block\-(.+)\.php/", $file, $matches)) {
				$found = str_replace("_", " ", $matches[1]);
				$selected = ($blockfile == $file) ? "selected" : "";
				echo "<option value=\"$file\" $selected>".$found."</option>";
			}
		}
		closedir($dir);
	} else {
		echo "<tr><td>Содержание:</td><td><textarea name=\"content\" cols=\"65\" rows=\"15\" style=\"width:400px\">".htmlspecialchars_uni($content)."</textarea></td></tr>";
	}
	$oldposition = $bposition;
	echo "<input type=\"hidden\" name=\"oldposition\" value=\"$oldposition\">";
	$sel1 = ($bposition == "l") ? "selected" : "";
	$sel2 = ($bposition == "c") ? "selected" : "";
	$sel3 = ($bposition == "r") ? "selected" : "";
	$sel4 = ($bposition == "d") ? "selected" : "";
	$sel5 = ($bposition == "b") ? "selected" : "";
	$sel6 = ($bposition == "f") ? "selected" : "";
	echo "<tr><td>Позиция:</td><td><select name=\"bposition\" style=\"width:400px\">"
	."<option name=\"bposition\" value=\"l\" $sel1>Слева</option>"
	."<option name=\"bposition\" value=\"c\" $sel2>По центру вверху</option>"
	."<option name=\"bposition\" value=\"d\" $sel4>По центру внизу</option>"
	."<option name=\"bposition\" value=\"r\" $sel3>Справа</option>"
	."<option name=\"bposition\" value=\"b\" $sel5>Верхний баннер</option>"
	."<option name=\"bposition\" value=\"f\" $sel6>Нижний баннер</option>"
	."</select></td></tr>";
	echo "<tr><td>Отображать блок в модулях:</td><td align=\"center\"><table border=\"0\" cellpadding=\"3\" cellspacing=\"0\" align=\"center\" style=\"width:400px\"><tr>";
	$where_mas = explode(",", $which);
	$cel = ($where_mas[0] == "ihome") ? " checked" : "";
	echo "<td><input type=\"checkbox\" name=\"blockwhere[]\" value=\"ihome\"$cel></td><td>Главная</td>";
	global $allowed_modules;
	$a = 1;
	foreach ($allowed_modules as $name => $title) {
		$i++;
		$cel = "";
		foreach ($where_mas as $key => $val) {
			if ($val == $name) { $cel = " checked"; $title = "<b>$title</b>"; } // Just to highlight selected pages on block edit, due to many checkboxes now...
		}
		$title = str_replace("_", " ", $title);
		echo "<td><input type=\"checkbox\" name=\"blockwhere[]\" value=\"".$name."\"$cel></td><td>$title</td>";
		if ($a == 2) {
			echo "</tr><tr>";
			$a = 0;
		} else {
			$a++;
		}
	}
	$where_mas = explode(",", $which);
    $cel = "";
    $hel = "";
    $fel = "";
	switch ($where_mas[0]) {
		case "all":
		$cel = " checked";
		break;
		case "home":
		$hel = " checked";
		break;
		case "infly":
		$fel = " checked";
		break;
	}
	echo "</tr><tr><td><input type=\"checkbox\" name=\"blockwhere[]\" value=\"all\"$cel></td><td><b>Во всех модулях</b></td><td><input type=\"checkbox\" name=\"blockwhere[]\" value=\"home\"$hel></td><td><b>Только на главной</b></td><td><input type=\"checkbox\" name=\"blockwhere[]\" value=\"infly\"$fel></td><td><b>Свободный блок</b></td></tr></table></td></tr>";
	$sel1 = ($active == 1) ? "checked" : "";
	$sel2 = ($active == 0) ? "checked" : "";
	$hide1 = ($hide == 'yes') ? "checked" : "";
	$hide2 = ($hide == 'no') ? "checked" : "";
	if ($expire != 0) {
		$newexpire = 0;
		$oldexpire = $expire;
		$expire = intval(($expire - time()) / 3600);
		$exp_day = $expire / 24;
		$expire_text = "<input type=\"hidden\" name=\"expire\" value=\"$oldexpire\">Осталось: $expire часы (".substr($exp_day,0,5)." дней)";
	} else {
		$newexpire = 1;
		$expire_text = "<input type=\"text\" name=\"expire\" value=\"0\" maxlength=\"3\" size=\"65\" style=\"width:400px\">";
	}
	$selact1 = ($action == "d") ? "selected" : "";
	$selact2 = ($action == "r") ? "selected" : "";
	echo "<tr><td>Сворачивать?</td><td><input type=\"radio\" name=\"hide\" value=\"yes\" $hide1>Да &nbsp;&nbsp;"
	."<input type=\"radio\" name=\"hide\" value=\"no\" $hide2>Нет</td></tr>";
	echo "<tr><td>Включить?</td><td><input type=\"radio\" name=\"active\" value=\"1\" $sel1>Да &nbsp;&nbsp;"
	."<input type=\"radio\" name=\"active\" value=\"0\" $sel2>Нет</td></tr>"
	."<tr><td>Время работы, в днях:</td><td>$expire_text</td></tr>"
	."<tr><td>После истечения:</td><td><select name=\"action\" style=\"width:400px\">"
	."<option name=\"action\" value=\"d\" $selact1>Выкл.</option>"
	."<option name=\"action\" value=\"r\" $selact2>Удалить</option></select></td></tr>";
	$sel1 = ($view == 0) ? "selected" : "";
	$sel2 = ($view == 1) ? "selected" : "";
	$sel3 = ($view == 2) ? "selected" : "";
	$sel4 = ($view == 3) ? "selected" : "";
	echo "</td></tr><tr><td>Кто это будет видеть?</td><td><select name=\"view\" style=\"width:400px\">"
	."<option value=\"0\" $sel1>Все посетители</option>"
	."<option value=\"1\" $sel2>Только пользователи</option>"
	."<option value=\"2\" $sel3>Только администраторы</option>"
	."<option value=\"3\" $sel4>Только анонимы</option>"
	."</select></td></tr></table><br>"
	."<center><input type=\"hidden\" name=\"bid\" value=\"$bid\">"
	."<input type=\"hidden\" name=\"newexpire\" value=\"$newexpire\">"
	."<input type=\"hidden\" name=\"bkey\" value=\"$bkey\">"
	."<input type=\"hidden\" name=\"weight\" value=\"$weight\">"
	."<input type=\"hidden\" name=\"op\" value=\"BlocksEditSave\">"
	."<input type=\"submit\" value=\"Сохранить\"></form></center>";
}

function BlocksEditSave($newexpire, $bid, $bkey, $title, $content, $oldposition, $bposition, $active, $hide, $weight, $blockfile, $view, $expire, $action) {
	global $prefix, $db, $admin_file;
	if (isset($_POST['blockwhere'])) {
		$blockwhere = $_POST['blockwhere'];
		$which = "";
		$which = (in_array("all", $blockwhere)) ? "all" : $which;
		$which = (in_array("home", $blockwhere)) ? "home" : $which;
		if ($which == "") {
			foreach ($blockwhere as $val) {
				$which .= "{$val},";
			}
		}
		sql_query("UPDATE ".$prefix."_blocks SET which=".sqlesc($which)." WHERE bid=".sqlesc($bid));
	} else {
		sql_query("UPDATE ".$prefix."_blocks SET which='' WHERE bid=".sqlesc($bid));
	}
		if ($oldposition != $bposition) {
			$result5 = sql_query("SELECT bid FROM ".$prefix."_blocks WHERE weight>=".sqlesc($weight)." AND bposition=".sqlesc($bposition));
			$fweight = $weight;
			$oweight = $weight;
			while ($row5 = mysqli_fetch_row($result5)) {
				$nbid = $row5[0];
				$weight++;
				sql_query("UPDATE ".$prefix."_blocks SET weight=".sqlesc($weight)." WHERE bid=".sqlesc($nbid)) or sqlerr(__FILE__,__LINE__);
			}
			$result6 = sql_query("SELECT bid FROM ".$prefix."_blocks WHERE weight>".sqlesc($oweight)." AND bposition=".sqlesc($oldposition)) or sqlerr(__FILE__,__LINE__);
			while ($row6 = mysqli_fetch_row($result6)) {
				$obid = $row6[0];
				sql_query("UPDATE ".$prefix."_blocks SET weight=".sqlesc($oweight)." WHERE bid=".sqlesc($obid));
				$oweight++;
			}
			$lastw_res = sql_query("SELECT weight FROM ".$prefix."_blocks WHERE bposition=".sqlesc($bposition)." ORDER BY weight DESC LIMIT 0,1");
			$lastw_row = $lastw_res ? mysqli_fetch_row($lastw_res) : false;
			$lastw = (int)($lastw_row[0] ?? 0);
			if ($lastw <= $fweight) {
				$lastw++;
				sql_query("UPDATE ".$prefix."_blocks SET title=".sqlesc($title).", content=".sqlesc($content).", bposition=".sqlesc($bposition).", weight=".sqlesc($lastw).", active=".sqlesc($active).", allow_hide=".sqlesc($hide).", blockfile=".sqlesc($blockfile).", view=".sqlesc($view)." WHERE bid=".sqlesc($bid)) or sqlerr(__FILE__,__LINE__);
			} else {
				sql_query("UPDATE ".$prefix."_blocks SET title=".sqlesc($title).", content=".sqlesc($content).", bposition=".sqlesc($bposition).", weight=".sqlesc($fweight).", active=".sqlesc($active).", allow_hide=".sqlesc($hide).", blockfile=".sqlesc($blockfile).", view=".sqlesc($view)." WHERE bid=".sqlesc($bid)) or sqlerr(__FILE__,__LINE__);
			}
		} else {
			if ($expire == "") $expire = 0;
			if ($newexpire == 1 && $expire != 0) $expire = time() + ($expire * 86400);
			$result8 = sql_query("UPDATE ".$prefix."_blocks SET bkey=".sqlesc($bkey).", title=".sqlesc($title).", content=".sqlesc($content).", bposition=".sqlesc($bposition).", weight=".sqlesc($weight).", active=".sqlesc($active).", allow_hide=".sqlesc($hide).", blockfile=".sqlesc($blockfile).", view=".sqlesc($view).", expire=".sqlesc($expire).", action=".sqlesc($action)." WHERE bid=".sqlesc($bid)) or sqlerr(__FILE__,__LINE__);
		}
		Header("Location: ".$admin_file.".php?op=BlocksAdmin");
}

function BlocksShow($bid) {
	global $prefix, $db, $admin_file;
	BlocksNavi();
	$show_res = sql_query("SELECT bid, bkey, title, content, bposition, blockfile FROM ".$prefix."_blocks WHERE bid='$bid'");
	$show_row = $show_res ? mysqli_fetch_row($show_res) : false;
	list($bid, $bkey, $title, $content, $bposition, $blockfile) = $show_row ?: array(0, "", "", "", "c", "");
	$bid = intval($bid);
	echo "<p />";
	render_blocks($blockfile, $title, $content, $bid, 'c', 'no');
	echo "<h4>[ <a href=\"".$admin_file.".php?op=BlocksChange&bid=$bid\">Включить</a> | <a href=\"".$admin_file.".php?op=BlocksEdit&bid=$bid\">Редактировать</a>";
	if ($bkey == "") echo " | <a href=\"".$admin_file.".php?op=BlocksDelete&bid=$bid\" OnClick=\"return DelCheck(this, 'Удалить &quot;$title&quot;?');\">Удалить</a>";
	echo " | <a href=\"".$admin_file.".php?op=BlocksAdmin\">Главная</a> ]</h4>";
}

function BlocksFileEdit() {
	global $prefix, $admin_file;
	BlocksNavi();
	echo "<h2>Редактировать блок</h2>"
	."<form action=\"".$admin_file.".php\" method=\"post\">"
	."<table border=\"0\" align=\"center\">"
	."<tr><td>Имя файла:</td><td>"
	."<select name=\"bf\" style=\"width:400px\">";
	$handle = opendir("blocks");
	while ($file = readdir($handle)) {
		if (preg_match("/^block\-(.+)\.php/", $file, $matches)) {
			$found = str_replace("-", " ", $matches[1]);
			$check = sql_query("SELECT bid FROM ".$prefix."_blocks WHERE blockfile=" . sqlesc($file) . " LIMIT 1");
			if ($check && mysqli_num_rows($check) > 0) echo "<option value=\"$file\">$found</option>\n";
		}
	}
	closedir($handle);
	echo "</select></td></tr>"
	."<tr><td colspan=\"2\" align=\"center\"><input type=\"hidden\" name=\"op\" value=\"BlocksbfEdit\"><input type=\"submit\" value=\"Редактировать блок\"></td></tr></table></form>";
}

function BlocksChange($bid, $ok=0) {
	global $prefix, $admin_file;
	$bid = intval($bid);
	$change_res = sql_query("SELECT active FROM ".$prefix."_blocks WHERE bid='$bid'");
	$row = $change_res ? mysqli_fetch_assoc($change_res) : array('active' => 0);
	$active = intval($row['active']);
	if (($ok) || ($active == 0)) {
		if ($active == 0) {
			$active = 1;
		} elseif ($active == 1) {
			$active = 0;
		}
		$result = sql_query("UPDATE ".$prefix."_blocks SET active='$active' WHERE bid='$bid'");
		Header("Location: ".$admin_file.".php?op=BlocksAdmin");
	} else {
		$title_res = sql_query("SELECT title, content, active FROM ".$prefix."_blocks WHERE bid='$bid'");
		$title_row = $title_res ? mysqli_fetch_row($title_res) : false;
		list($title, $content, $active) = $title_row ?: array('', '', 0);
		if ($active == 0) {
			echo "<center>Активировать блок \"$title\"?<br /><br />";
		} else {
			echo "<center>Деактивировать блок \"$title\"?<br /><br />";
		}
		echo "[ <a href=\"".$admin_file.".php?op=BlocksChange&bid=$bid&ok=1\">Да</a> | <a href=\"".$admin_file.".php?op=BlocksAdmin\">Нет</a> ]</center>";
	}
}

function BlocksbfEdit() {
	global $prefix, $db, $admin_file;
	if ($_REQUEST['bf'] != "") {
		$bf = $_REQUEST['bf'];
		if (isset($_POST['flag'])) {
			$flaged = $_POST['flag'];
			$bf = str_replace("block-", "",$bf);
			$bf = str_replace(".php", "",$bf);
			$bf = 'block-'.$bf.'.php';
		} else {
			$bfstr = file_get_contents('blocks/'.$bf);
			if (strpos($bfstr,'BLOCKHTML') === false) {
				$flaged = 'php';
				preg_match("/<\?php.*if.*\(\!defined\(\'BLOCK_FILE\'\)\).*exit;.*?}(.*)\?>/is", $bfstr, $out);
				unset($out[0]);
			} else {
				$flaged = 'html';
				preg_match("/<<<BLOCKHTML(.*)BLOCKHTML;/is", $bfstr, $out);
				unset($out[0]);
			}
		}
		BlocksNavi();
		$permtest = end_chmod("blocks", 777);
		if ($permtest)
			stdmsg("Ошибка", $permtest, 'error');
		echo "<h2>Блок: $bf</h2>"
		."<form action=\"".$admin_file.".php\" method=\"post\">"
		."<table border=\"0\" align=\"center\">"
		."<tr><td>Содержание:</td><td><textarea wrap=\"virtual\" name=\"blocktext\" cols=\"65\" rows=\"25\" style=\"width:400px\">".$out[1]."</textarea></td></tr>"
		."<tr><td colspan=\"2\" align=\"center\"><br /><input type=\"hidden\" name=\"bf\" value=\"".$bf."\">"
		."<input type=\"hidden\" name=\"flag\" value=\"".$flaged."\">"
		."<input type=\"hidden\" name=\"op\" value=\"BlocksbfSave\">"
		."<input type=\"submit\" value=\"Сохранить\"> <input type=\"button\" value=\"Назад\" onClick=\"javascript:history.go(-1)\"></td></tr></table></form>";
	} else {
		Header("Location: ".$admin_file.".php?op=BlocksFile");
	}
}

function BlocksbfSave() {
	global $prefix, $db, $admin_file;
	if (isset($_POST['blocktext'])) {
		if (!empty($_POST['blocktext'])) {
			if (isset($_POST['bf'])) {
				$bf = $_POST['bf'];
				if ($handle = fopen('blocks/'.$bf, 'w')) {
					$htmlB = "";
					$htmlE = "";
					if (isset($_POST['flag'])) {
						$flaged = $_POST['flag'];
						if ($flaged == 'html') {
							$htmlB = "\$content=<<<BLOCKHTML\n";
							$htmlE = "\nBLOCKHTML;\n";
						}
					}
					$str_set = $_POST['blocktext'];
					fwrite($handle, "<?php\n\nif (!defined('BLOCK_FILE')) {\nheader(\"Location: ../index.php\");\nexit;\n}\n\n".$htmlB.$str_set.$htmlE."\r\n?>");
					Header("Location: ".$admin_file.".php?op=BlocksAdmin");
				}
				fclose($handle);
			}
		}
	}
}

switch($op) {
	case "BlocksAdmin":
	BlocksAdmin();
	break;
	
	case "BlocksNew":
	BlocksNew();
	break;
	
	case "BlocksFile":
	BlocksFile();
	break;
	
	case "BlocksFileEdit":
	BlocksFileEdit();
	break;
	
	case "BlocksAdd":
	BlocksAdd($title, $content, $bposition, $active, $hide, $blockfile, $view, $expire, $action);
	break;
	
	case "BlocksEdit":
	BlocksEdit($bid);
	break;
	
	case "BlocksEditSave":
	BlocksEditSave($newexpire, $bid, $bkey, $title, $content, $oldposition, $bposition, $active, $hide, $weight, $blockfile, $view, $expire, $action);
	break;
	
	case "BlocksChange":
	$bid = isset($_REQUEST['bid']) ? (int)$_REQUEST['bid'] : 0;
	$ok = isset($_REQUEST['ok']) ? (int)$_REQUEST['ok'] : 0;
	BlocksChange($bid, $ok);
	break;
	
	case "BlocksDelete":
	$bid = intval($_REQUEST['bid']);
	$delete_res = sql_query("SELECT bposition, weight FROM ".$prefix."_blocks WHERE bid='$bid'");
	$delete_row = $delete_res ? mysqli_fetch_row($delete_res) : false;
	list($bposition, $weight) = $delete_row ?: array('', 0);
	$result = sql_query("SELECT bid FROM ".$prefix."_blocks WHERE weight>'$weight' AND bposition='$bposition'");
	while ($delete_shift_row = mysqli_fetch_row($result)) {
		$nbid = $delete_shift_row[0];
		sql_query("UPDATE ".$prefix."_blocks SET weight='$weight' WHERE bid='$nbid'");
		$weight++;
	}
	sql_query("DELETE FROM ".$prefix."_blocks WHERE bid='$bid'");
	Header("Location: ".$admin_file.".php?op=BlocksAdmin");
	break;
	
	case "BlocksOrder":
	BlocksOrder($weightrep, $weight, $bidrep, $bidori);
	break;
	
	case "BlocksFixweight":
	BlocksFixweight();
	break;
	
	case "BlocksShow":
	BlocksShow($bid);
	break;
	
	case "BlocksbfEdit":
	BlocksbfEdit();
	break;
	
	case "BlocksbfSave":
	BlocksbfSave();
	break;
}
?>
