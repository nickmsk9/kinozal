<?

/*
// +--------------------------------------------------------------------------+
// | Project:    TBDevYSE - TBDev Yuna Scatari Edition                        |
// +--------------------------------------------------------------------------+
// | This file is part of TBDevYSE. TBDevYSE is based on TBDev,               |
// | originally by RedBeard of TorrentBits, extensively modified by           |
// | Gartenzwerg.                                                             |
// |                                                                          |
// | TBDevYSE is free software; you can redistribute it and/or modify         |
// | it under the terms of the GNU General Public License as published by     |
// | the Free Software Foundation; either version 2 of the License, or        |
// | (at your option) any later version.                                      |
// |                                                                          |
// | TBDevYSE is distributed in the hope that it will be useful,              |
// | but WITHOUT ANY WARRANTY; without even the implied warranty of           |
// | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the            |
// | GNU General Public License for more details.                             |
// |                                                                          |
// | You should have received a copy of the GNU General Public License        |
// | along with TBDevYSE; if not, write to the Free Software Foundation,      |
// | Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA            |
// +--------------------------------------------------------------------------+
// |                                               Do not remove above lines! |
// +--------------------------------------------------------------------------+
*/

require_once("include/bittorrent.php");
dbconn(false);
loggedinorreturn();

function puke($text = "You have forgotten here someting?") {
	global $tracker_lang;
	stderr($tracker_lang['error'], $text);
}

function barf($text = "Пользователь удален") {
	global $tracker_lang;
	stderr($tracker_lang['success'], $text);
}

if (get_user_class() < UC_MODERATOR)
	puke($tracker_lang['access_denied']);

	$action = $_POST["action"] ?? "";

if ($action == "edituser") {
		$userid = (int)($_POST["userid"] ?? 0);
		$title = trim((string)($_POST["title"] ?? ""));
		$avatar = trim((string)($_POST["avatar"] ?? ""));
		$updateset = array();
	// Check remote avatar size
	if ($avatar) {
		if (mb_strlen($avatar, "UTF-8") > 500)
			stderr($tracker_lang['error'], "Длина ссылки на аватар превышает 500 символов.");
		if (!preg_match('#^((http)|(ftp):\/\/[a-zA-Z0-9\-]+?\.([a-zA-Z0-9\-]+\.)+[a-zA-Z]+(:[0-9]+)*\/.*?\.(gif|jpg|jpeg|png)$)#is', $avatar))
						stderr($tracker_lang['error'], $tracker_lang['avatar_adress_invalid']);
		if(!(list($width, $height) = getimagesize($avatar)))
						stderr($tracker_lang['error'], $tracker_lang['avatar_adress_invalid']);
		if ($width > $avatar_max_width || $height > $avatar_max_height)
						stderr($tracker_lang['error'], sprintf($tracker_lang['avatar_is_too_big'], $avatar_max_width, $avatar_max_height));
	}
// Check remote avatar size
		$resetb = $_POST["resetb"] ?? "no";
		$birthday = ($resetb=='yes'?", birthday = NULL":"");
		$enabled = $_POST["enabled"] ?? "";
		$warned = $_POST["warned"] ?? "";
		$warnlength = intval($_POST["warnlength"] ?? 0);
		$dislength = intval($_POST["dislength"] ?? 0);
		$warnpm = $_POST["warnpm"] ?? "";
		$donor = $_POST["donor"] ?? "no";
		$uploadtoadd = $_POST["amountup"] ?? 0;
		$downloadtoadd=  $_POST["amountdown"] ?? 0;
		$formatup = $_POST["formatup"] ?? "mb";
		$formatdown = $_POST["formatdown"] ?? "mb";
		$mpup = $_POST["upchange"] ?? "plus";
		$mpdown = $_POST["downchange"] ?? "plus";
		$support = $_POST["support"] ?? "no";
		$supportfor = htmlspecialchars_uni($_POST["supportfor"] ?? "");
		$modcomm = htmlspecialchars_uni($_POST["modcomm"] ?? "");
		$deluser = $_POST["deluser"] ?? "";
		$country = (int)($_POST["country"] ?? 0);
		$gender = (string)($_POST["gender"] ?? "1");
		$city = trim((string)($_POST["city"] ?? ""));
		$favorite_movie = trim((string)($_POST["favorite_movie"] ?? ""));
		$favorite_persons = trim((string)($_POST["favorite_persons"] ?? ""));
		$parked = (($_POST["parked"] ?? "no") === "yes") ? "yes" : "no";
		$avatars = (($_POST["avatars"] ?? "yes") === "no") ? "no" : "yes";
		$acceptpms = (string)($_POST["acceptpms"] ?? "yes");
		$deletepms = (($_POST["deletepms"] ?? "yes") === "no") ? "no" : "yes";
		$savepms = (($_POST["savepms"] ?? "no") === "yes") ? "yes" : "no";

		$class = intval($_POST["class"] ?? 0);
	if (!is_valid_id($userid) || !is_valid_user_class($class))
		stderr($tracker_lang['error'], "Неверный идентификатор пользователя или класса.");
	// check target user class
	$res = sql_query("
		SELECT warned, enabled, username, class, modcomment, uploaded, downloaded, parked, avatars, acceptpms, deletepms, savepms, country, gender, city, favorite_movie, favorite_persons
		FROM users
		WHERE id = $userid
	") or sqlerr(__FILE__, __LINE__);
	$arr = mysqli_fetch_assoc($res) or puke("Ошибка MySQL.");
	$curenabled = $arr["enabled"];
	$curclass = (int)$arr["class"];
	$curwarned = $arr["warned"];
	$self_edit = !empty($CURUSER["id"]) && (int)$CURUSER["id"] === $userid;
	if (!array_key_exists("country", $_POST))
		$country = (int)$arr["country"];
	if (!array_key_exists("gender", $_POST))
		$gender = (string)$arr["gender"];
	if (!array_key_exists("city", $_POST))
		$city = (string)$arr["city"];
	if (!array_key_exists("favorite_movie", $_POST))
		$favorite_movie = (string)$arr["favorite_movie"];
	if (!array_key_exists("favorite_persons", $_POST))
		$favorite_persons = (string)$arr["favorite_persons"];
	if (!array_key_exists("parked", $_POST))
		$parked = (string)$arr["parked"];
	if (!array_key_exists("avatars", $_POST))
		$avatars = (string)$arr["avatars"];
	if (!array_key_exists("acceptpms", $_POST))
		$acceptpms = (string)$arr["acceptpms"];
	if (!array_key_exists("deletepms", $_POST))
		$deletepms = (string)$arr["deletepms"];
	if (!array_key_exists("savepms", $_POST))
		$savepms = (string)$arr["savepms"];
	if (get_user_class() == UC_SYSOP)
		$modcomment = $_POST["modcomment"] ?? $arr["modcomment"];
	else
		$modcomment = $arr["modcomment"];
	// User may not edit someone with same or higher class than himself!
	if (!$self_edit && ($curclass >= get_user_class() || $class >= get_user_class()))
		puke("Так нельзя делать!");
	if ($self_edit) {
		$class = $curclass;
		$deluser = "";
		$enabled = $curenabled;
		$warned = $curwarned;
		$warnlength = 0;
		$dislength = 0;
		$uploadtoadd = 0;
		$downloadtoadd = 0;
	}
	if (mb_strlen($title, "UTF-8") > 30)
		stderr($tracker_lang['error'], "Заголовок слишком длинный (макс. 30 символов).");
	if ($country < 0)
		$country = 0;
	if ($country > 0) {
		$country_res = sql_query("SELECT id FROM countries WHERE id = $country LIMIT 1") or sqlerr(__FILE__, __LINE__);
		if (mysqli_num_rows($country_res) == 0)
			stderr($tracker_lang['error'], "Выбрана несуществующая страна.");
	}
	if ($gender !== "1" && $gender !== "2" && $gender !== "3")
		$gender = "1";
	if ($support !== "yes")
		$support = "no";
	if ($donor !== "yes")
		$donor = "no";
	if ($acceptpms !== "yes" && $acceptpms !== "friends" && $acceptpms !== "no")
		$acceptpms = "yes";
	if (mb_strlen($city, "UTF-8") > 100)
		stderr($tracker_lang['error'], "Название города слишком длинное (макс. 100 символов).");
	if (mb_strlen($favorite_movie, "UTF-8") > 255)
		stderr($tracker_lang['error'], "Название любимого фильма слишком длинное (макс. 255 символов).");
	if (mb_strlen($favorite_persons, "UTF-8") > 255)
		stderr($tracker_lang['error'], "Поле любимых персон слишком длинное (макс. 255 символов).");

	if (get_user_class() >= UC_ADMINISTRATOR) {
		$manual_cups = isset($_POST["manual_cups"]) && is_array($_POST["manual_cups"]) ? $_POST["manual_cups"] : array();
		$cup_changes = cups_save_profile_manual($userid, $manual_cups, (int)$CURUSER["id"]);

		if (!empty($cup_changes['added']) || !empty($cup_changes['removed'])) {
			$cup_titles = array();
			foreach (cups_catalog() as $cup) {
				$cup_titles[(int)$cup['id']] = $cup['title'];
			}

			foreach ($cup_changes['added'] as $cup_id) {
				$cup_title = $cup_titles[(int)$cup_id] ?? ('Кубок #' . (int)$cup_id);
				$modcomment = date("Y-m-d") . " - Назначен переходящий кубок \"" . $cup_title . "\" пользователем " . $CURUSER["username"] . ".\n" . $modcomment;
			}

			foreach ($cup_changes['removed'] as $cup_id) {
				$cup_title = $cup_titles[(int)$cup_id] ?? ('Кубок #' . (int)$cup_id);
				$modcomment = date("Y-m-d") . " - Снят переходящий кубок \"" . $cup_title . "\" пользователем " . $CURUSER["username"] . ".\n" . $modcomment;
			}
		}
	}

	if($uploadtoadd > 0) {
		if ($mpup == "plus")
				$newupload = $arr["uploaded"] + ($formatup == "mb" ? ($uploadtoadd * 1048576) : ($uploadtoadd * 1073741824));
			else
				$newupload = $arr["uploaded"] - ($formatup == "mb" ? ($uploadtoadd * 1048576) : ($uploadtoadd * 1073741824));
		if ($newupload < 0)
			stderr($tracker_lang['error'], "Вы хотите отнять у пользователя отданого больше чем у него есть!");
		$updateset[] = "uploaded = $newupload";
			$modcomment = date("Y-m-d") . " - Пользователь " . $CURUSER["username"] . " ".($mpup == "plus" ? "добавил " : "отнял ").$uploadtoadd.($formatup == "mb" ? " MB" : " GB")." к раздаче.\n". $modcomment;
	}

	if($downloadtoadd > 0) {
		if ($mpdown == "plus")
				$newdownload = $arr["downloaded"] + ($formatdown == "mb" ? ($downloadtoadd * 1048576) : ($downloadtoadd * 1073741824));
			else
				$newdownload = $arr["downloaded"] - ($formatdown == "mb" ? ($downloadtoadd * 1048576) : ($downloadtoadd * 1073741824));
		if ($newdownload < 0)
			stderr($tracker_lang['error'], "Вы хотите отнять у пользователя скачаного больше чем у него есть!");
		$updateset[] = "downloaded = $newdownload";
			$modcomment = date("Y-m-d") . " - Пользователь " . $CURUSER["username"] . " ".($mpdown == "plus" ? "добавил " : "отнял ").$downloadtoadd.($formatdown == "mb" ? " MB" : " GB")." к скачаному.\n". $modcomment;
	}

	if ($curclass != $class) {
		// Notify user
		$what = ($class > $curclass ? "повышены" : "понижены");
			$msg = "Вы были $what до класса \"" . get_user_class_name($class) . "\" пользователем " . $CURUSER["username"] . ".";
		$subject = "Вы были $what";
		send_pm(0, $userid, get_date_time(), $subject, $msg);
		//sql_query("INSERT INTO messages (sender, receiver, msg, added, subject) VALUES(0, $userid, $msg, $added, $subject)") or sqlerr(__FILE__, __LINE__);
		$updateset[] = "class = $class";
		$what = ($class > $curclass ? "Повышен" : "Пониженен");
	 		$modcomment = date("Y-m-d") . " - $what до класса \"" . get_user_class_name($class) . "\" пользователем " . $CURUSER["username"] . ".\n". $modcomment;
	}

	// some Helshad fun
	// $fun = ($CURUSER['id'] == 277) ? " Tremble in fear, mortal." : "";

	if ($warned && $curwarned != $warned) {
		$updateset[] = "warned = " . sqlesc($warned);
		$updateset[] = "warneduntil = NULL";
		$subject = "Ваше предупреждение снято";
		if ($warned == 'no')
		{
			$modcomment = date("Y-m-d") . " - Предупреждение снял пользователь " . $CURUSER['username'] . ".\n". $modcomment;
			$msg = "Ваше предупреждение снял пользователь " . $CURUSER['username'] . ".";
		}
		send_pm(0, $userid, get_date_time(), $subject, $msg);
		//sql_query("INSERT INTO messages (sender, receiver, msg, added, subject) VALUES (0, $userid, $msg, $added, $subject)") or sqlerr(__FILE__, __LINE__);
	} elseif ($warnlength) {
		if (strlen($warnpm) == 0)
			stderr($tracker_lang['error'], "Вы должны указать причину по которой ставите предупреждение!");
		if ($warnlength == 255) {
			$modcomment = date("Y-m-d") . " - Предупрежден пользователем " . $CURUSER['username'] . ".\nПричина: $warnpm\n" . $modcomment;
				$msg = "Вы получили [url=rules.php#warning]предупреждение[/url] на неограниченый срок от " . $CURUSER["username"] . ($warnpm ? "\n\nПричина: $warnpm" : "");
			$updateset[] = "warneduntil = NULL";
		} else {
			$warneduntil = get_date_time(gmtime() + $warnlength * 604800);
			$dur = $warnlength . " недел" . ($warnlength > 1 ? "и" : "ю");
			$msg = "Вы получили [url=rules.php#warning]предупреждение[/url] на $dur от пользователя " . $CURUSER['username'] . ($warnpm ? "\n\nПричина: $warnpm" : "");
			$modcomment = date("Y-m-d") . " - Предупрежден на $dur пользователем " . $CURUSER['username'] .	".\nПричина: $warnpm\n" . $modcomment;
			$updateset[] = "warneduntil = '$warneduntil'";
		}
 		$subject = "Вы получили предупреждение";
 		send_pm(0, $userid, get_date_time(), $subject, $msg);
		//sql_query("INSERT INTO messages (sender, receiver, msg, added, subject) VALUES (0, $userid, $msg, $added, $subject)") or sqlerr(__FILE__, __LINE__);
		$updateset[] = "warned = 'yes'";
	}

	if ($enabled != $curenabled && (!empty($enabled) || $dislength != 0)) {
		$modifier = (int) $CURUSER['id'];
		if ($enabled == 'yes') {
			$nowdate = sqlesc(get_date_time());
			if (!isset($_POST["enareason"]) || empty($_POST["enareason"]))
				puke("Введите причину почему вы включаете пользователя!");
			$enareason = htmlspecialchars_uni($_POST["enareason"]);
			$modcomment = date("Y-m-d") . " - Включен пользователем " . $CURUSER['username'] . ".\nПричина: $enareason\n" . $modcomment;
			sql_query('DELETE FROM users_ban WHERE userid = '.$userid) or sqlerr(__FILE__,__LINE__);
			$updateset[] = "enabled = 'yes'";
		} else {
			$date = sqlesc(get_date_time());
			$dateline = sqlesc(time());
			$disuntil = get_date_time(gmtime() + $dislength * 604800);
			$dur = $dislength . " недел" . ($dislength > 1 ? "и" : "ю");
			if (!isset($_POST["disreason"]) || empty($_POST["disreason"]))
				puke("Введите причину почему вы отключаете пользователя!");
			$disreason = htmlspecialchars_uni($_POST["disreason"]);
			$modcomment = date("Y-m-d") . " - Отключен пользователем " . $CURUSER['username'] . ($disuntil != '0000-00-00 00:00:00' ? ' на ' . $dur : '') . ".\nПричина: $disreason\n" . $modcomment;
			sql_query('INSERT INTO users_ban (userid, disuntil, disby, reason) VALUES ('.implode(', ', array_map('sqlesc', array($userid, $disuntil, $modifier, $disreason))).')') or sqlerr(__FILE__,__LINE__);
			$updateset[] = "enabled = 'no'";
		}
	}

	$updateset[] = "donor = " . sqlesc($donor);
	$updateset[] = "supportfor = " . sqlesc($supportfor);
	$updateset[] = "support = " . sqlesc($support);
	$updateset[] = "avatar = " . sqlesc($avatar);
	$updateset[] = "title = " . sqlesc($title);
	$updateset[] = "country = $country";
	$updateset[] = "gender = " . sqlesc($gender);
	$updateset[] = "city = " . sqlesc(htmlspecialchars_uni($city));
	$updateset[] = "favorite_movie = " . sqlesc(htmlspecialchars_uni($favorite_movie));
	$updateset[] = "favorite_persons = " . sqlesc(htmlspecialchars_uni($favorite_persons));
	$updateset[] = "parked = " . sqlesc($parked);
	$updateset[] = "avatars = " . sqlesc($avatars);
	$updateset[] = "acceptpms = " . sqlesc($acceptpms);
	$updateset[] = "deletepms = " . sqlesc($deletepms);
	$updateset[] = "savepms = " . sqlesc($savepms);
	if (!empty($modcomm))
			$modcomment = date("Y-m-d") . " - Заметка от " . $CURUSER["username"] . ": $modcomm\n" . $modcomment;
	$updateset[] = "modcomment = " . sqlesc($modcomment);
	if (!empty($_POST['resetkey'])) {
		$passkey = tracker_generate_passkey($userid);
		$updateset[] = "passkey = " . sqlesc($passkey);
	}
	sql_query("UPDATE users SET	" . implode(", ", $updateset) . " $birthday WHERE id = $userid") or sqlerr(__FILE__, __LINE__);
	if (!empty($_POST["deluser"])) {
		$res=@sql_query("SELECT * FROM users WHERE id = $userid") or sqlerr(__FILE__, __LINE__);
		$user = mysqli_fetch_array($res);
		$username = $user["username"];
		$email=$user["email"];
		sql_query("DELETE FROM users WHERE id = $userid") or sqlerr(__FILE__, __LINE__);
		sql_query("DELETE FROM messages WHERE receiver = $userid") or sqlerr(__FILE__,__LINE__);
		sql_query("DELETE FROM friends WHERE userid = $userid") or sqlerr(__FILE__,__LINE__);
		sql_query("DELETE FROM friends WHERE friendid = $userid") or sqlerr(__FILE__,__LINE__);
		sql_query("DELETE FROM blocks WHERE userid = $userid") or sqlerr(__FILE__,__LINE__);
		sql_query("DELETE FROM blocks WHERE blockid = $userid") or sqlerr(__FILE__,__LINE__);
		sql_query("DELETE FROM bookmarks WHERE userid = $userid") or sqlerr(__FILE__,__LINE__);
		sql_query("DELETE FROM peers WHERE userid = $userid") or sqlerr(__FILE__,__LINE__);
		sql_query("DELETE FROM readtorrents WHERE userid = $userid") or sqlerr(__FILE__,__LINE__);
		sql_query("DELETE FROM simpaty WHERE fromuserid = $userid") or sqlerr(__FILE__,__LINE__);
		sql_query("DELETE FROM checkcomm WHERE userid = $userid") or sqlerr(__FILE__,__LINE__);
		sql_query("DELETE FROM sessions WHERE uid = $userid") or sqlerr(__FILE__,__LINE__);
		sql_query("DELETE FROM user_cups WHERE userid = $userid") or sqlerr(__FILE__,__LINE__);
		$deluserid=$CURUSER["username"];
		write_log("Пользователь $username был удален пользователем $deluserid");
		barf();
	} else {
		$returnto = htmlentities($_POST["returnto"]);
		header("Refresh: 0; url=$DEFAULTBASEURL/$returnto");
		die;
	}
} elseif ($action == "confirmuser") {
	$userid = $_POST["userid"];
	$confirm = $_POST["confirm"];
	if (!is_valid_id($userid))
		stderr($tracker_lang['error'], $tracker_lang['invalid_id']);
	$updateset[] = "status = " . sqlesc($confirm);
	$updateset[] = "last_login = ".sqlesc(get_date_time());
	$updateset[] = "last_access = ".sqlesc(get_date_time());
	//print("UPDATE users SET " . implode(", ", $updateset) . " WHERE id=$userid");
	sql_query("UPDATE users SET " . implode(", ", $updateset) . " WHERE id = $userid") or sqlerr(__FILE__, __LINE__);
	$returnto = htmlentities($_POST["returnto"]);

	header("Location: $DEFAULTBASEURL/$returnto");
}

puke();

?>
