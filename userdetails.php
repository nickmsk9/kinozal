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

function maketable($res)
{
  global $tracker_lang, $use_ttl, $ttl_days;
  $ret = "<table class=main border=1 cellspacing=0 cellpadding=5>" .
    "<tr><td class=colhead align=left>".$tracker_lang['type']."</td><td class=colhead>".$tracker_lang['name']."</td>".($use_ttl ? "<td class=colhead align=center>".$tracker_lang['ttl']."</td>" : "")."<td class=colhead align=center>".$tracker_lang['size']."</td><td class=colhead align=right>".$tracker_lang['details_seeding']."</td><td class=colhead align=right>".$tracker_lang['details_leeching']."</td><td class=colhead align=center>".$tracker_lang['uploaded']."</td>\n" .
    "<td class=colhead align=center>".$tracker_lang['downloaded']."</td><td class=colhead align=center>".$tracker_lang['ratio']."</td></tr>\n";
  while ($arr = mysqli_fetch_assoc($res))
  {
    if ($arr["downloaded"] > 0)
    {
      $ratio = number_format($arr["uploaded"] / $arr["downloaded"], 3);
      $ratio = "<font color=" . get_ratio_color($ratio) . ">$ratio</font>";
    }
    else
      if ($arr["uploaded"] > 0)
        $ratio = "Inf.";
      else
        $ratio = "---";
    $catid = $arr["catid"];
	$catimage = htmlspecialchars_uni($arr["image"]);
	$catname = htmlspecialchars_uni($arr["catname"]);
	$ttl = ($ttl_days*24) - floor((gmtime() - sql_timestamp_to_unix_timestamp($arr["added"])) / 3600);
	if ($ttl == 1) $ttl .= "&nbsp;час"; else $ttl .= "&nbsp;часов";
	$size = str_replace(" ", "<br />", mksize($arr["size"]));
	$uploaded = str_replace(" ", "<br />", mksize($arr["uploaded"]));
	$downloaded = str_replace(" ", "<br />", mksize($arr["downloaded"]));
	$seeders = number_format($arr["seeders"]);
	$leechers = number_format($arr["leechers"]);
    $ret .= "<tr><td style='padding: 0px'><a href=\"browse.php?cat=$catid\"><img src=\"pic/cats/$catimage\" alt=\"$catname\" border=\"0\" /></a></td>\n" .
		"<td><a href=details.php?id=$arr[torrent]&amp;hit=1><b>" . $arr["torrentname"] .
		"</b></a></td>".($use_ttl ? "<td align=center>$ttl</td>" : "")."<td align=center>$size</td><td align=right>$seeders</td><td align=right>$leechers</td><td align=center>$uploaded</td>\n" .
		"<td align=center>$downloaded</td><td align=center>$ratio</td></tr>\n";
  }
  $ret .= "</table>\n";
  return $ret;
}

$id = intval($_GET["id"]);

if (!is_valid_id($id))
  bark($tracker_lang['invalid_id']);

$r = @sql_query("SELECT * FROM users WHERE id=$id") or sqlerr(__FILE__, __LINE__);
$user = mysqli_fetch_array($r) or bark("Нет пользователя с таким ID $id.");
if ($user["status"] == "pending") die;
$r = sql_query("SELECT torrents.id, torrents.name, torrents.seeders, torrents.added, torrents.leechers, torrents.category, categories.name AS catname, categories.image AS catimage, categories.id AS catid FROM torrents LEFT JOIN categories ON torrents.category = categories.id WHERE owner=$id ORDER BY name") or sqlerr(__FILE__, __LINE__);
if (mysqli_num_rows($r) > 0) {
  $torrents = "<table class=main border=1 cellspacing=0 cellpadding=5>\n" .
    "<tr><td class=colhead>".$tracker_lang['type']."</td><td class=colhead>".$tracker_lang['name']."</td>".($use_ttl ? "<td class=colhead align=center>".$tracker_lang['ttl']."</td>" : "")."<td class=colhead>".$tracker_lang['tracker_seeders']."</td><td class=colhead>".$tracker_lang['tracker_leechers']."</td></tr>\n";
  while ($a = mysqli_fetch_assoc($r)) {
	$ttl = ($ttl_days*24) - floor((gmtime() - sql_timestamp_to_unix_timestamp($a["added"])) / 3600);
	if ($ttl == 1) $ttl .= "&nbsp;час"; else $ttl .= "&nbsp;часов";
		//$r2 = sql_query("SELECT name, image FROM categories WHERE id=$a[category]") or sqlerr(__FILE__, __LINE__);
		//$a2 = mysqli_fetch_assoc($r2);
		$cat = "<a href=\"browse.php?cat=$a[catid]\"><img src=\"pic/cats/$a[catimage]\" alt=\"$a[catname]\" border=\"0\" /></a>";
      $torrents .= "<tr><td style='padding: 0px'>$cat</td><td><a href=\"details.php?id=" . $a["id"] . "&hit=1\"><b>" . $a["name"] . "</b></a></td>"
      	.($use_ttl ? "<td align=center>$ttl</td>" : "")
        ."<td align=right>$a[seeders]</td><td align=right>$a[leechers]</td></tr>\n";
  }
  $torrents .= "</table>";
}

$it = sql_query("SELECT u.id, u.username, u.class, i.id AS invitedid, i.username AS invitedname, i.class AS invitedclass FROM users AS u LEFT JOIN users AS i ON i.id = u.invitedby WHERE u.invitedroot = $id OR u.invitedby = $id ORDER BY u.invitedby");
if (mysqli_num_rows($it) >= 1) {
	$invitetree = "<table class=\"main\" border=\"1\" cellspacing=\"0\" cellpadding=\"5\"><tr>".
		"<td class=\"colhead\">Пользователь</td><td class=\"colhead\">Пригласил</td>";
	while ($inviter = mysqli_fetch_array($it))
		$invitetree .= "<tr><td><a href=\"userdetails.php?id=$inviter[id]\">".get_user_class_color($inviter["class"], $inviter["username"])."</a></td><td><a href=\"userdetails.php?id=$inviter[invitedid]\">".get_user_class_color($inviter["invitedclass"], $inviter["invitedname"])."</a></td></tr>";
	$invitetree .= "</table>";
}

if ($user["ip"] && (get_user_class() >= UC_MODERATOR || $user["id"] == $CURUSER["id"])) {
  $ip = $user["ip"];
  $dom = @gethostbyaddr($user["ip"]);
  if ($dom == $user["ip"] || @gethostbyname($dom) != $user["ip"])
    $addr = $ip;
  else
  {
    $dom = strtoupper($dom);
    $domparts = explode(".", $dom);
    $domain = $domparts[count($domparts) - 2];
    if ($domain == "COM" || $domain == "CO" || $domain == "NET" || $domain == "NE" || $domain == "ORG" || $domain == "OR" )
      $l = 2;
    else
      $l = 1;
    $addr = "$ip ($dom)";
  }
}

$r = sql_query("SELECT snatched.torrent AS id, snatched.uploaded, snatched.seeder, snatched.downloaded, snatched.startdat, snatched.completedat, snatched.last_action, categories.name AS catname, categories.image AS catimage, categories.id AS catid, torrents.name, torrents.seeders, torrents.leechers FROM snatched JOIN torrents ON torrents.id = snatched.torrent JOIN categories ON torrents.category = categories.id WHERE snatched.finished='yes' AND userid = $id ORDER BY torrent") or sqlerr(__FILE__,__LINE__);
if (mysqli_num_rows($r) > 0) {
$completed = "<table class=\"main\" border=\"1\" cellspacing=\"0\" cellpadding=\"5\">\n" .
  "<tr><td class=\"colhead\">Тип</td><td class=\"colhead\">Название</td><td class=\"colhead\">Раздающих</td><td class=\"colhead\">Качающих</td><td class=\"colhead\">Раздал</td><td class=\"colhead\">Скачал</td><td class=\"colhead\">Рейтинг</td><td class=\"colhead\">Начал / Закончил</td><td class=\"colhead\">Действие</td><td class=\"colhead\">Сидирует</td></tr>\n";
while ($a = mysqli_fetch_array($r)) {
if ($a["downloaded"] > 0) {
      $ratio = number_format($a["uploaded"] / $a["downloaded"], 3);
      $ratio = "<font color=\"" . get_ratio_color($ratio) . "\">$ratio</font>";
   } else
	if ($a["uploaded"] > 0)
        $ratio = "Inf.";
	else
		$ratio = "---";
$uploaded = mksize($a["uploaded"]);
$downloaded = mksize($a["downloaded"]);
if ($a["seeder"] == 'yes')
	$seeder = "<font color=\"green\">Да</font>";
else
	$seeder = "<font color=\"red\">Нет</font>";
	$cat = "<a href=\"browse.php?cat=$a[catid]\"><img src=\"pic/cats/$a[catimage]\" alt=\"$a[catname]\" border=\"0\" /></a>";
    $completed .= "<tr><td style=\"padding: 0px\">$cat</td><td><nobr><a href=\"details.php?id=" . $a["id"] . "&amp;hit=1\"><b>" . $a["name"] . "</b></a></nobr></td>" .
      "<td align=\"right\">$a[seeders]</td><td align=\"right\">$a[leechers]</td><td align=\"right\">$uploaded</td><td align=\"right\">$downloaded</td><td align=\"center\">$ratio</td><td align=\"center\"><nobr>$a[startdat]<br />$a[completedat]</nobr></td><td align=\"center\"><nobr>$a[last_action]</nobr></td><td align=\"center\">$seeder</td>\n";
}
$completed .= "</table>";
}

if ($user["added"] == "0000-00-00 00:00:00") {
    $joindate = 'N/A';
} else {
    $joindate = $user["added"] . " (" . get_et(sql_timestamp_to_unix_timestamp($user["added"])) . " " . $tracker_lang['ago'] . ")";
}

$lastseen = $user["last_access"];

if ($lastseen == "0000-00-00 00:00:00") {
    $lastseen = $tracker_lang['never'];
} else {
    $lastseen .= " (" . get_et(sql_timestamp_to_unix_timestamp($lastseen)) . " " . $tracker_lang['ago'] . ")";
}

$res = sql_query("SELECT COUNT(*) FROM comments WHERE user = " . sqlesc((int)$user["id"])) or sqlerr(__FILE__, __LINE__);
$arr3 = mysqli_fetch_row($res);
$torrentcomments = (int)$arr3[0];
//if ($user['donated'] > 0)
//  $don = "<img src=pic/starbig.gif>";

$res = sql_query("SELECT name, flagpic FROM countries WHERE id = $user[country] LIMIT 1") or sqlerr(__FILE__, __LINE__);
if (mysqli_num_rows($res) == 1)
{
  $arr = mysqli_fetch_assoc($res);
  $country = "<td class=\"embedded\"><img src=\"pic/flag/$arr[flagpic]\" alt=\"$arr[name]\" style=\"margin-left: 8pt\"></td>";
}

//if ($user["donor"] == "yes") $donor = "<td class=embedded><img src=pic/starbig.gif alt='Donor' style='margin-left: 4pt'></td>";
//if ($user["warned"] == "yes") $warned = "<td class=embedded><img src=pic/warnedbig.gif alt='Warned' style='margin-left: 4pt'></td>";

if ($user["gender"] == "1") $gender = "<img src=\"".$pic_base_url."/male.gif\" alt=\"Парень\" title=\"Парень\">";
elseif ($user["gender"] == "2") $gender = "<img src=\"".$pic_base_url."/female.gif\" alt=\"Девушка\" title=\"Девушка\">";
//elseif ($user["gender"] == "Н/Д") $gender = "<td class=embedded><img src=".$pic_base_url."/na.gif alt='Н/Д' style='margin-left: 4pt'></td>";

$res = sql_query("SELECT torrent, added, uploaded, downloaded, torrents.name AS torrentname, categories.name AS catname, categories.id AS catid, size, image, category, seeders, leechers FROM peers LEFT JOIN torrents ON peers.torrent = torrents.id LEFT JOIN categories ON torrents.category = categories.id WHERE userid = $id AND seeder='no'") or sqlerr(__FILE__, __LINE__);
if (mysqli_num_rows($res) > 0)
  $leeching = maketable($res);
$res = sql_query("SELECT torrent, added, uploaded, downloaded, torrents.name AS torrentname, categories.name AS catname, categories.id AS catid, size, image, category, seeders, leechers FROM peers LEFT JOIN torrents ON peers.torrent = torrents.id LEFT JOIN categories ON torrents.category = categories.id WHERE userid = $id AND seeder='yes'") or sqlerr(__FILE__, __LINE__);
if (mysqli_num_rows($res) > 0)
  $seeding = maketable($res);

///////////////// BIRTHDAY MOD /////////////////////

$age = '';

if (!empty($user["birthday"]) && $user["birthday"] != "0000-00-00") {
    $tzoffset = isset($CURUSER["tzoffset"]) ? (int)$CURUSER["tzoffset"] : 0;

    $current = date("Y-m-d", time() + $tzoffset * 60);

    list($year2, $month2, $day2) = explode('-', $current);

    $birthday = date("Y-m-d", strtotime($user["birthday"]));
    list($year1, $month1, $day1) = explode('-', $birthday);

    $age = (int)$year2 - (int)$year1;

    if ((int)$month2 < (int)$month1 || ((int)$month2 == (int)$month1 && (int)$day2 < (int)$day1)) {
        $age--;
    }
}

///////////////// BIRTHDAY MOD /////////////////////
stdhead("Просмотр профиля " . $user["username"]);

$enabled = $user["enabled"] == 'yes';

print("<div class=\"mn_wrap\">\n");

print("<div class=\"bx2_0\">\n");
print("<ul class=\"men\">\n");
print("<li class=\"tp2 b\">");
print(htmlspecialchars_uni($user["username"]) . get_user_icons($user, true));
print($country ? " " . $country : "");
print("</li>\n");
print("</ul>\n");
print("</div>\n");

if (!$enabled) {
	print("<div class=\"bx1 center b red\">Этот аккаунт отключен</div>\n");
} elseif ($CURUSER["id"] <> $user["id"]) {
	$r = sql_query("SELECT id FROM friends WHERE userid=" . sqlesc($CURUSER["id"]) . " AND friendid=" . sqlesc($id)) or sqlerr(__FILE__, __LINE__);
	$friend = mysqli_num_rows($r);

	$r = sql_query("SELECT id FROM blocks WHERE userid=" . sqlesc($CURUSER["id"]) . " AND blockid=" . sqlesc($id)) or sqlerr(__FILE__, __LINE__);
	$block = mysqli_num_rows($r);

	print("<div class=\"bx1 center\">\n");

	if ($friend) {
		print("<a class=\"sbab\" href=\"friends.php?action=delete&amp;type=friend&amp;targetid=$id\">Убрать из друзей</a>\n");
	} elseif ($block) {
		print("<a class=\"sbab\" href=\"friends.php?action=delete&amp;type=block&amp;targetid=$id\">Убрать из блокированных</a>\n");
	} else {
		print("<a class=\"sbab\" href=\"friends.php?action=add&amp;type=friend&amp;targetid=$id\">Добавить в друзья</a>");
		print(" &nbsp;|&nbsp; ");
		print("<a class=\"sbab\" href=\"friends.php?action=add&amp;type=block&amp;targetid=$id\">Добавить в блокированные</a>\n");
	}

	print("</div>\n");
}

print("<table class=\"tables3 w100p\">\n");

print("<tr class=\"first\"><td class=\"rowhead w190\">Зарегистрирован</td><td class=\"left\">$joindate</td></tr>\n");
print("<tr><td class=\"rowhead\">Последний раз был на трекере</td><td class=\"left\">$lastseen</td></tr>\n");

if (get_user_class() >= UC_MODERATOR) {
	print("<tr><td class=\"rowhead\">Email</td><td class=\"left\"><a href=\"mailto:" . htmlspecialchars_uni($user["email"]) . "\">" . htmlspecialchars_uni($user["email"]) . "</a></td></tr>\n");
}

if ($addr) {
	print("<tr><td class=\"rowhead\">IP</td><td class=\"left\">$addr</td></tr>\n");
}

print("<tr><td class=\"rowhead\">Раздал</td><td class=\"left\">" . mksize($user["uploaded"]) . "</td></tr>\n");
print("<tr><td class=\"rowhead\">Скачал</td><td class=\"left\">" . mksize($user["downloaded"]) . "</td></tr>\n");

if (get_user_class() >= UC_MODERATOR) {
	print("<tr><td class=\"rowhead\">Приглашений</td><td class=\"left\"><a href=\"invite.php?id=$id\">" . (int)$user["invites"] . "</a></td></tr>\n");
}

if ($user["invitedby"] != 0) {
	$inviter = mysqli_fetch_assoc(sql_query("SELECT username FROM users WHERE id = " . sqlesc($user["invitedby"])));
	print("<tr><td class=\"rowhead\">Пригласил</td><td class=\"left\"><a href=\"userdetails.php?id=" . (int)$user["invitedby"] . "\">" . htmlspecialchars_uni($inviter["username"]) . "</a></td></tr>\n");
}

if ($user["downloaded"] > 0) {
	$sr = $user["uploaded"] / $user["downloaded"];

	if ($sr >= 4) {
		$s = "w00t";
	} elseif ($sr >= 2) {
		$s = "grin";
	} elseif ($sr >= 1) {
		$s = "smile1";
	} elseif ($sr >= 0.5) {
		$s = "noexpression";
	} elseif ($sr >= 0.25) {
		$s = "sad";
	} else {
		$s = "cry";
	}

	$sr = floor($sr * 1000) / 1000;

	$ratio = "<span style=\"color:" . get_ratio_color($sr) . "\">" . number_format($sr, 3) . "</span>";
	$ratio .= " &nbsp;<img src=\"pic/smilies/$s.gif\" alt=\"\">";

	print("<tr><td class=\"rowhead\">Рейтинг</td><td class=\"left\">$ratio</td></tr>\n");
}
//}
if ($user["icq"] || $user["msn"] || $user["aim"] || $user["yahoo"] || $user["skype"] || $user["mirc"]) {
	print("<tr><td class=\"rowhead\">Связь</td><td class=\"left\">\n");

	if ($user["icq"]) {
		print("<img src=\"http://web.icq.com/whitepages/online?icq=" . htmlspecialchars_uni($user["icq"]) . "&amp;img=5\" alt=\"icq\"> " . htmlspecialchars_uni($user["icq"]) . "<br>\n");
	}

	if ($user["msn"]) {
		print("<img src=\"pic/contact/msn.gif\" alt=\"msn\"> " . htmlspecialchars_uni($user["msn"]) . "<br>\n");
	}

	if ($user["aim"]) {
		print("<img src=\"pic/contact/aim.gif\" alt=\"aim\"> " . htmlspecialchars_uni($user["aim"]) . "<br>\n");
	}

	if ($user["yahoo"]) {
		print("<img src=\"pic/contact/yahoo.gif\" alt=\"yahoo\"> " . htmlspecialchars_uni($user["yahoo"]) . "<br>\n");
	}

	if ($user["skype"]) {
		print("<img src=\"pic/contact/skype.gif\" alt=\"skype\"> " . htmlspecialchars_uni($user["skype"]) . "<br>\n");
	}

	if ($user["mirc"]) {
		print("<img src=\"pic/contact/mirc.gif\" alt=\"mirc\"> " . htmlspecialchars_uni($user["mirc"]) . "\n");
	}

	print("</td></tr>\n");
}

if ($user["website"]) {
	print("<tr><td class=\"rowhead\">Сайт</td><td class=\"left\"><a href=\"" . htmlspecialchars_uni($user["website"]) . "\" target=\"_blank\">" . htmlspecialchars_uni($user["website"]) . "</a></td></tr>\n");
}

if ($user["avatar"]) {
	print("<tr><td class=\"rowhead\">Аватар</td><td class=\"left\"><img class=\"p100\" src=\"" . htmlspecialchars_uni($user["avatar"]) . "\" alt=\"\"></td></tr>\n");
}

print("<tr><td class=\"rowhead\">Класс</td><td class=\"left b\">" . get_user_class_color($user["class"], get_user_class_name($user["class"])) . ($user["title"] != "" ? " / <span style=\"color: purple;\">" . htmlspecialchars_uni($user["title"]) . "</span>" : "") . "</td></tr>\n");

print("<tr><td class=\"rowhead\">Пол</td><td class=\"left\">$gender</td></tr>\n");

if ($user["birthday"] != '0000-00-00') {
	print("<tr><td class=\"rowhead\">Возраст</td><td class=\"left\">$age</td></tr>\n");

	$birthday = date("d.m.Y", strtotime($birthday));
	print("<tr><td class=\"rowhead\">Дата рождения</td><td class=\"left\">$birthday</td></tr>\n");

	$month_of_birth = substr($user["birthday"], 5, 2);
	$day_of_birth = substr($user["birthday"], 8, 2);

	for ($i = 0; $i < count($zodiac); $i++) {
		if ($month_of_birth == substr($zodiac[$i][2], 3, 2)) {
			if ($day_of_birth >= substr($zodiac[$i][2], 0, 2)) {
				$zodiac_img = $zodiac[$i][1];
				$zodiac_name = $zodiac[$i][0];
			} else {
				if ($i == 11) {
					$zodiac_img = $zodiac[0][1];
					$zodiac_name = $zodiac[0][0];
				} else {
					$zodiac_img = $zodiac[$i + 1][1];
					$zodiac_name = $zodiac[$i + 1][0];
				}
			}
		}
	}

	print("<tr><td class=\"rowhead\">Знак зодиака</td><td class=\"left\"><img src=\"pic/zodiac/" . htmlspecialchars_uni($zodiac_img) . "\" alt=\"" . htmlspecialchars_uni($zodiac_name) . "\" title=\"" . htmlspecialchars_uni($zodiac_name) . "\"></td></tr>\n");
}

if ($user["simpaty"] != 0) {
	if ((get_user_class() >= UC_MODERATOR && $user["class"] < get_user_class()) || $user["id"] == $CURUSER["id"]) {
		$simpaty = ($user["simpaty"] > 0)
			? '<img src="pic/thum_good.gif" alt=""> <a href="mysimpaty.php?id=' . (int)$user["id"] . '">' . (int)$user["simpaty"] . '</a>'
			: '<img src="pic/thum_bad.gif" alt=""> <a href="mysimpaty.php?id=' . (int)$user["id"] . '">' . (int)$user["simpaty"] . '</a>';
	} else {
		$simpaty = ($user["simpaty"] > 0)
			? '<img src="pic/thum_good.gif" alt=""> ' . (int)$user["simpaty"]
			: '<img src="pic/thum_bad.gif" alt=""> ' . (int)$user["simpaty"];
	}

	print("<tr><td class=\"rowhead\">Респектов</td><td class=\"left\">$simpaty</td></tr>\n");
}

print("<tr><td class=\"rowhead\">Комментариев</td>");

if ($torrentcomments && (($user["class"] >= UC_POWER_USER && $user["id"] == $CURUSER["id"]) || get_user_class() >= UC_MODERATOR)) {
	print("<td class=\"left\"><a href=\"userhistory.php?action=viewcomments&amp;id=$id\">$torrentcomments</a></td></tr>\n");
} else {
	print("<td class=\"left\">$torrentcomments</td></tr>\n");
}

print("<script type=\"text/javascript\" src=\"js/show_hide.js\"></script>\n");

if ($torrents) {
	print("<tr><td class=\"rowhead top\">Залитые&nbsp;торренты</td><td class=\"left\"><a href=\"javascript:show_hide('s1')\"><img src=\"pic/plus.gif\" id=\"pics1\" title=\"Показать\" alt=\"\"></a><div id=\"ss1\" class=\"displaynone\">$torrents</div></td></tr>\n");
}

if ($seeding) {
	print("<tr><td class=\"rowhead top\">Сейчас&nbsp;раздаёт</td><td class=\"left\"><a href=\"javascript:show_hide('s2')\"><img src=\"pic/plus.gif\" id=\"pics2\" title=\"Показать\" alt=\"\"></a><div id=\"ss2\" class=\"displaynone\">$seeding</div></td></tr>\n");
}

if ($leeching) {
	print("<tr><td class=\"rowhead top\">Сейчас&nbsp;качает</td><td class=\"left\"><a href=\"javascript:show_hide('s3')\"><img src=\"pic/plus.gif\" id=\"pics3\" title=\"Показать\" alt=\"\"></a><div id=\"ss3\" class=\"displaynone\">$leeching</div></td></tr>\n");
}

if ($completed) {
	print("<tr><td class=\"rowhead top\">Скачанные&nbsp;торренты</td><td class=\"left\"><a href=\"javascript:show_hide('s4')\"><img src=\"pic/plus.gif\" id=\"pics4\" title=\"Показать\" alt=\"\"></a><div id=\"ss4\" class=\"displaynone\">$completed</div></td></tr>\n");
}

if ($invitetree) {
	print("<tr><td class=\"rowhead top\">Приглашённые</td><td class=\"left\"><a href=\"javascript:show_hide('s5')\"><img src=\"pic/plus.gif\" id=\"pics5\" title=\"Показать\" alt=\"\"></a><div id=\"ss5\" class=\"displaynone\">$invitetree</div></td></tr>\n");
}

if ($user["info"]) {
	print("<tr><td colspan=\"2\" class=\"text\">" . format_comment($user["info"]) . "</td></tr>\n");
}

if (!isset($showpmbutton)) {
	$showpmbutton = 0;
}

if ($CURUSER["id"] != $user["id"]) {
	if (get_user_class() >= UC_MODERATOR) {
		$showpmbutton = 1;
	} elseif ($user["acceptpms"] == "yes") {
		$r = sql_query("SELECT id FROM blocks WHERE userid = " . sqlesc($user["id"]) . " AND blockid = " . sqlesc($CURUSER["id"])) or sqlerr(__FILE__, __LINE__);
		$showpmbutton = (mysqli_num_rows($r) == 1 ? 0 : 1);
	} elseif ($user["acceptpms"] == "friends") {
		$r = sql_query("SELECT id FROM friends WHERE userid = " . sqlesc($user["id"]) . " AND friendid = " . sqlesc($CURUSER["id"])) or sqlerr(__FILE__, __LINE__);
		$showpmbutton = (mysqli_num_rows($r) == 1 ? 1 : 0);
	}
}

if ($showpmbutton) {
	print("<tr><td colspan=\"2\" class=\"center\">
		<form method=\"get\" action=\"message.php\">
			<input type=\"hidden\" name=\"receiver\" value=\"" . (int)$user["id"] . "\">
			<input type=\"hidden\" name=\"action\" value=\"sendmessage\">
			<input class=\"buttonS\" type=\"submit\" value=\"Послать ЛС\">
		</form>
	</td></tr>\n");
}

print("</table>\n");
print("</div>\n");

if (get_user_class() >= UC_MODERATOR && $user["class"] < get_user_class())
{
  begin_frame("Редактирование пользователя", true);
  print("<form method=\"post\" action=\"modtask.php\">\n");
  print("<input type=\"hidden\" name=\"action\" value=\"edituser\">\n");
  print("<input type=\"hidden\" name=\"userid\" value=\"$id\">\n");
  print("<input type=\"hidden\" name=\"returnto\" value=\"userdetails.php?id=$id\">\n");
  print("<table class=\"main\" border=\"1\" cellspacing=\"0\" cellpadding=\"5\">\n");
  print("<tr><td class=\"rowhead\">Заголовок</td><td colspan=\"2\" align=\"left\"><input type=\"text\" size=\"60\" name=\"title\" value=\"" . htmlspecialchars_uni($user[title]) . "\"></tr>\n");
	$avatar = htmlspecialchars_uni($user["avatar"]);
  print("<tr><td class=\"rowhead\">Аватар</td><td colspan=\"2\" align=\"left\"><input type=\"text\" size=\"60\" name=\"avatar\" value=\"$avatar\"></tr>\n");
	// we do not want mods to be able to change user classes or amount donated...
	if ($CURUSER["class"] < UC_ADMINISTRATOR)
	  print("<input type=\"hidden\" name=\"donor\" value=\"$user[donor]\">\n");
	else {
	  print("<tr><td class=\"rowhead\">Донор</td><td colspan=\"2\" align=\"left\"><input type=\"radio\" name=\"donor\" value=\"yes\"" .($user["donor"] == "yes" ? " checked" : "").">Да <input type=\"radio\" name=\"donor\" value=\"no\"" .($user["donor"] == "no" ? " checked" : "").">Нет</td></tr>\n");
	}

	if (get_user_class() == UC_MODERATOR && $user["class"] > UC_VIP)
	  print("<input type=\"hidden\" name=\"class\" value=\"$user[class]\">\n");
	else
	{
	  print("<tr><td class=\"rowhead\">Класс</td><td colspan=\"2\" align=\"left\"><select name=\"class\">\n");
	  if (get_user_class() == UC_SYSOP)
	  	$maxclass = UC_SYSOP;
	  elseif (get_user_class() == UC_MODERATOR)
	    $maxclass = UC_VIP;
	  else
	    $maxclass = get_user_class() - 1;
	  for ($i = 0; $i <= $maxclass; ++$i)
	    print("<option value=\"$i\"" . ($user["class"] == $i ? " selected" : "") . ">$prefix" . get_user_class_name($i) . "\n");
	  print("</select></td></tr>\n");
	}
	print("<tr><td class=\"rowhead\">Сбросить день рождения</td><td colspan=\"2\" align=\"left\"><input type=\"radio\" name=\"resetb\" value=\"yes\">Да<input type=\"radio\" name=\"resetb\" value=\"no\" checked>Нет</td></tr>\n");
	$modcomment = htmlspecialchars_uni($user["modcomment"]);
	$supportfor = htmlspecialchars_uni($user["supportfor"]);
	print("<tr><td class=rowhead>Поддержка</td><td colspan=2 align=left><input type=radio name=support value=yes" .($user["support"] == "yes" ? " checked" : "").">Да <input type=radio name=support value=no" .($user["support"] == "no" ? " checked" : "").">Нет</td></tr>\n");
	print("<tr><td class=rowhead>Поддержка для:</td><td colspan=2 align=left><textarea cols=60 rows=6 name=supportfor>$supportfor</textarea></td></tr>\n");
	print("<tr><td class=rowhead>История пользователя</td><td colspan=2 align=left><textarea cols=60 rows=6".(get_user_class() < UC_SYSOP ? " readonly" : " name=modcomment").">$modcomment</textarea></td></tr>\n");
	print("<tr><td class=rowhead>Добавить заметку</td><td colspan=2 align=left><textarea cols=60 rows=3 name=modcomm></textarea></td></tr>\n");
	$warned = $user["warned"] == "yes";

 	print("<tr><td class=\"rowhead\" rowspan=\"2\">Предупреждение</td>
 	<td align=\"center\" colspan=\"2\">" .
  ( $warned
  ? "<font color=\"red\">Пользователь предупреждён</font>"
 	: "<font color=\"green\">Предупреждения нет</font>" ) ."</td></tr>");

	if ($warned) {

		print("<tr><td>Оставить предупреждённым?<br />");
		print("<input name=\"warned\" value=\"yes\" type=\"radio\" checked>Да<input name=\"warned\" value=\"no\" type=\"radio\">Нет");

		$warneduntil = $user['warneduntil'];
		if ($warneduntil == '0000-00-00 00:00:00')
    		print("<td align=\"center\">Предупреждение на неограниченый срок</td></tr>\n");
		else {
    		print("<td align=\"center\">Предупреждение действует до<br />" . date('d.m.Y H:i:s', strtotime($warneduntil)));
	    	print(" (осталось " . get_lt(strtotime($warneduntil)) . ")</td></tr>\n");
 	    }
  } else {
    print("<tr><td>Предупредить на:<br />");
    print("<select name=\"warnlength\">\n");
    print("<option value=\"0\">------</option>\n");
    print("<option value=\"1\">1 неделю</option>\n");
    print("<option value=\"2\">2 недели</option>\n");
    print("<option value=\"4\">4 недели</option>\n");
    print("<option value=\"8\">8 недель</option>\n");
    print("<option value=\"255\">Неограничено</option>\n");
    print("</select></td><td>Причина предупреждения:<br />");
    print("<input type=\"text\" size=\"60\" name=\"warnpm\"></td></tr>");
  }
    /*print("<tr><td class=\"rowhead\" rowspan=\"2\">Включен</td><td colspan=\"2\" align=\"left\"><input name=\"enabled\" value=\"yes\" type=\"radio\"" . ($enabled ? " checked" : "") . ">Да <input name=\"enabled\" value=\"no\" type=\"radio\"" . (!$enabled ? " checked" : "") . ">Нет</td></tr>\n");
    if ($enabled)
    	print("<tr><td colspan=\"2\" align=\"left\">Причина отключения:&nbsp;<input type=\"text\" name=\"disreason\" size=\"60\" /></td></tr>");
	else
		print("<tr><td colspan=\"2\" align=\"left\">Причина включения:&nbsp;<input type=\"text\" name=\"enareason\" size=\"60\" /></td></tr>");*/

	print("<tr><td class=\"rowhead\" rowspan=\"2\">Включен</td><td align=\"center\" colspan=\"2\">".($enabled ? "<font color=\"green\">Пользователь включен</font>" : "<font color=\"red\">Пользователь отключен</font>")."</td></tr>");

$disabler = <<<DIS
<select name="dislength">
	<option value="0">------</option>
	<option value="1">1 неделю</option>
	<option value="2">2 недели</option>
	<option value="4">4 недели</option>
	<option value="8">8 недель</option>
	<option value="255">Неограничено</option>
</select>
DIS;

	if ($enabled)
		print("<tr><td>Отключить на:<br />$disabler</td><td>Причина отключения:<br /><input type=\"text\" name=\"disreason\" size=\"60\" /></td></td></tr>");
	else
		print("<tr><td>Включить?<br /><input name=\"enabled\" value=\"yes\" type=\"radio\">Да <input name=\"enabled\" value=\"no\" type=\"radio\" checked>Нет<br /></td><td>Причина включения:<br /><input type=\"text\" name=\"enareason\" size=\"60\" /></td></tr>");

?>
<script type="text/javascript">

function togglepic(bu, picid, formid)
{
    var pic = document.getElementById(picid);
    var form = document.getElementById(formid);
    
    if(pic.src == bu + "/pic/plus.gif")
    {
        pic.src = bu + "/pic/minus.gif";
        form.value = "minus";
    }else{
        pic.src = bu + "/pic/plus.gif";
        form.value = "plus";
    }
}

</script>
<?
  print("<tr><td class=\"rowhead\">Изменить раздачу</td><td align=\"left\"><img src=\"pic/plus.gif\" id=\"uppic\" onClick=\"togglepic('$DEFAULTBASEURL','uppic','upchange')\" style=\"cursor: pointer;\">&nbsp;<input type=\"text\" name=\"amountup\" size=\"10\" /><td>\n<select name=\"formatup\">\n<option value=\"mb\">MB</option>\n<option value=\"gb\">GB</option></select></td></tr>");
  print("<tr><td class=\"rowhead\">Изменить скачку</td><td align=\"left\"><img src=\"pic/plus.gif\" id=\"downpic\" onClick=\"togglepic('$DEFAULTBASEURL','downpic','downchange')\" style=\"cursor: pointer;\">&nbsp;<input type=\"text\" name=\"amountdown\" size=\"10\" /><td>\n<select name=\"formatdown\">\n<option value=\"mb\">MB</option>\n<option value=\"gb\">GB</option></select></td></tr>");
  print("<tr><td class=\"rowhead\">Сбросить passkey</td><td colspan=\"2\" align=\"left\"><input name=\"resetkey\" value=\"1\" type=\"checkbox\"></td></tr>\n");
  if ($CURUSER["class"] < UC_ADMINISTRATOR)
  	print("<input type=\"hidden\" name=\"deluser\">");
  else
  	print("<tr><td class=\"rowhead\">Удалить</td><td colspan=\"2\" align=\"left\"><input type=\"checkbox\" name=\"deluser\"></td></tr>");
  print("</td></tr>");
  print("<tr><td colspan=\"3\" align=\"center\"><input type=\"submit\" class=\"btn\" value=\"ОК\"></td></tr>\n");
  print("</table>\n");
  print("<input type=\"hidden\" id=\"upchange\" name=\"upchange\" value=\"plus\"><input type=\"hidden\" id=\"downchange\" name=\"downchange\" value=\"plus\">\n");
  print("</form>\n");
  end_frame();
}
end_main_frame();
end_frame();
stdfoot();

